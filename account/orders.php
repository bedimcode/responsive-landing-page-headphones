<?php
/**
 * User Dashboard — Order History
 */
$accountPageTitle = 'My Orders';
require_once __DIR__ . '/includes/header.php';

$db  = getDB();
$uid = currentUserId();

// Filters
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 10;

// Build query
$where  = 'WHERE o.user_id = ?';
$params = [$uid];

if ($statusFilter && in_array($statusFilter, ['pending','processing','shipped','delivered','cancelled'])) {
    $where .= ' AND o.status = ?';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $where .= ' AND (o.order_number LIKE ?)';
    $params[] = '%' . $search . '%';
}

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o $where");
$countStmt->execute($params);
$totalOrders = (int) $countStmt->fetchColumn();
$totalPages  = max(1, ceil($totalOrders / $perPage));
$page        = min($page, $totalPages);

// Fetch
$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
                       FROM orders o $where ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Status counts
$statusCounts = [];
foreach (['pending','processing','shipped','delivered','cancelled'] as $s) {
    $cs = $db->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = ?');
    $cs->execute([$uid, $s]);
    $statusCounts[$s] = (int) $cs->fetchColumn();
}
?>

<!-- Filters -->
<div class="flex flex-col sm:flex-row gap-3 mb-6">
    <!-- Search -->
    <form method="get" class="flex-1 flex">
        <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= e($statusFilter) ?>"><?php endif; ?>
        <div class="relative flex-1">
            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search by order number…"
                   class="w-full pl-9 pr-4 py-2.5 bg-dark-900/60 border border-white/[.06] rounded-xl text-sm text-white placeholder-gray-500
                          focus:outline-none focus:border-neon-blue/30 transition-colors">
        </div>
    </form>

    <!-- Status filter -->
    <div class="flex flex-wrap gap-1.5">
        <a href="?<?= $search ? 'q=' . urlencode($search) : '' ?>"
           class="px-3 py-2 rounded-xl text-[11px] sm:text-xs font-medium border transition-all
                  <?= !$statusFilter ? 'bg-neon-blue/10 text-neon-blue border-neon-blue/20' : 'bg-dark-900/60 text-gray-400 border-white/[.06] hover:text-white hover:border-white/10' ?>">
            All <span class="text-gray-600 ml-1"><?= $totalOrders ?></span>
        </a>
        <?php foreach ($statusCounts as $s => $c): if ($c === 0) continue; ?>
        <a href="?status=<?= $s ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
           class="px-3 py-2 rounded-xl text-[11px] sm:text-xs font-medium border transition-all
                  <?= $statusFilter === $s ? 'bg-neon-blue/10 text-neon-blue border-neon-blue/20' : 'bg-dark-900/60 text-gray-400 border-white/[.06] hover:text-white hover:border-white/10' ?>">
            <?= ucfirst($s) ?> <span class="text-gray-600 ml-1"><?= $c ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Orders Table -->
<div class="bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden">
    <?php if (empty($orders)): ?>
    <div class="px-5 py-16 text-center">
        <i class="ri-shopping-bag-3-line text-5xl text-gray-700 mb-4 block"></i>
        <p class="text-gray-400 mb-2">No orders found.</p>
        <p class="text-sm text-gray-600 mb-6">
            <?= $statusFilter || $search ? 'Try adjusting your filters.' : "You haven't placed any orders yet." ?>
        </p>
        <?php if (!$statusFilter && !$search): ?>
        <a href="<?= e($basePath) ?>/shop.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-neon-blue text-dark-950 text-sm font-semibold rounded-xl hover:bg-neon-blue/90 transition-colors">
            <i class="ri-store-2-line"></i> Start Shopping
        </a>
        <?php else: ?>
        <a href="<?= e($basePath) ?>/account/orders.php" class="text-sm text-neon-blue hover:text-white transition-colors">Clear Filters</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/[.06]">
                    <th class="text-left px-4 sm:px-5 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Order</th>
                    <th class="text-left px-3 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Date</th>
                    <th class="text-center px-3 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Items</th>
                    <th class="text-left px-3 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-left px-3 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Payment</th>
                    <th class="text-right px-4 sm:px-5 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[.04]">
                <?php foreach ($orders as $order): ?>
                <tr class="hover:bg-white/[.02] transition-colors">
                    <td class="px-4 sm:px-5 py-3.5">
                        <div class="font-medium text-white text-xs sm:text-sm">#<?= e($order['order_number']) ?></div>
                        <div class="text-[10px] text-gray-600 mt-0.5 sm:hidden"><?= date('M j, Y', strtotime($order['created_at'])) ?></div>
                    </td>
                    <td class="px-3 py-3.5 text-gray-400 text-xs hidden sm:table-cell"><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                    <td class="px-3 py-3.5 text-center text-gray-400 text-xs hidden md:table-cell"><?= (int) $order['item_count'] ?></td>
                    <td class="px-3 py-3.5">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold border <?= orderStatusBadge($order['status']) ?>">
                            <?= ucfirst(e($order['status'])) ?>
                        </span>
                    </td>
                    <td class="px-3 py-3.5 hidden sm:table-cell">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-medium <?= paymentStatusBadge($order['payment_status']) ?>">
                            <?= ucfirst(e($order['payment_status'])) ?>
                        </span>
                    </td>
                    <td class="px-4 sm:px-5 py-3.5 text-right font-semibold text-white text-xs sm:text-sm"><?= formatPrice($order['total']) ?></td>
                    <td class="px-3 py-3.5">
                        <a href="<?= e($basePath) ?>/account/order-view.php?id=<?= (int) $order['id'] ?>"
                           class="w-8 h-8 rounded-lg bg-white/[.04] border border-white/[.06] inline-flex items-center justify-center text-gray-400 hover:text-white hover:border-white/10 transition-all"
                           title="View Order">
                            <i class="ri-eye-line text-sm"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-between px-4 sm:px-5 py-3 border-t border-white/[.06]">
        <span class="text-[11px] text-gray-500">Page <?= $page ?> of <?= $totalPages ?> (<?= $totalOrders ?> orders)</span>
        <div class="flex gap-1.5">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?><?= $statusFilter ? '&status=' . e($statusFilter) : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
               class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white/[.04] border border-white/[.06] text-gray-300 hover:text-white hover:border-white/10 transition-all">← Prev</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?><?= $statusFilter ? '&status=' . e($statusFilter) : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
               class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white/[.04] border border-white/[.06] text-gray-300 hover:text-white hover:border-white/10 transition-all">Next →</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
