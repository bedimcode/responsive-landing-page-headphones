<?php
/**
 * Admin — Orders List
 */
$adminPageTitle = 'Orders';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Filter
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($statusFilter && in_array($statusFilter, ['pending','processing','shipped','delivered','cancelled'])) {
    $where[]  = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $where[]  = '(o.order_number LIKE ? OR o.shipping_name LIKE ? OR o.shipping_email LIKE ?)';
    $like      = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total      = (int) $db->prepare("SELECT COUNT(*) FROM orders o $whereSql")->execute($params) ? call_user_func(function() use ($db, $whereSql, $params) {
    $st = $db->prepare("SELECT COUNT(*) FROM orders o $whereSql"); $st->execute($params); return (int)$st->fetchColumn();
}) : 0;
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $db->prepare("
    SELECT o.id, o.order_number, o.shipping_name, o.shipping_email, o.total, o.status, o.payment_status, o.created_at,
           COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    $whereSql
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusColors = [
    'pending'    => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
    'processing' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    'shipped'    => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
    'delivered'  => 'bg-green-500/10 text-green-400 border-green-500/20',
    'cancelled'  => 'bg-red-500/10 text-red-400 border-red-500/20',
];
$payColors = ['pending'=>'text-yellow-400','paid'=>'text-green-400','failed'=>'text-red-400','refunded'=>'text-gray-400'];

$statuses = ['', 'pending', 'processing', 'shipped', 'delivered', 'cancelled'];
?>

<!-- Filters -->
<div class="flex flex-wrap gap-3 mb-6 items-center">
    <form method="GET" class="flex flex-wrap gap-3 flex-1">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search order / customer…"
            class="flex-1 min-w-[180px] px-4 py-2.5 bg-dark-900/60 border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors">
        <select name="status" onchange="this.form.submit()"
            class="px-4 py-2.5 bg-dark-900/60 border border-white/10 rounded-xl text-sm text-white focus:border-neon-blue/50 focus:outline-none transition-colors">
            <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>" <?= $statusFilter===$s?'selected':'' ?> class="bg-dark-900">
                <?= $s ? ucfirst($s) : 'All Statuses' ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-4 py-2.5 bg-neon-blue/10 border border-neon-blue/20 rounded-xl text-sm text-neon-blue hover:bg-neon-blue/20 transition-colors">
            <i class="ri-search-line"></i> Search
        </button>
        <?php if ($search || $statusFilter): ?>
        <a href="<?= e($basePath) ?>/admin/orders.php" class="px-4 py-2.5 bg-white/5 rounded-xl text-sm text-gray-400 hover:text-white transition-colors">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Table -->
<div class="bg-dark-900/60 border border-white/5 rounded-2xl overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-4 border-b border-white/5">
        <span class="text-sm text-gray-500"><?= $total ?> order<?= $total !== 1 ? 's' : '' ?></span>
    </div>
    <?php if (empty($orders)): ?>
    <div class="px-6 py-16 text-center text-gray-600">
        <i class="ri-shopping-bag-3-line text-5xl block mb-3"></i>
        No orders found.
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/5">
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Customer</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Items</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Payment</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach ($orders as $o): ?>
                <tr class="hover:bg-white/2 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-mono text-white text-xs font-medium"><?= e($o['order_number']) ?></div>
                    </td>
                    <td class="px-4 py-4 hidden md:table-cell">
                        <div class="text-white text-sm"><?= e($o['shipping_name']) ?></div>
                        <div class="text-gray-500 text-xs"><?= e($o['shipping_email']) ?></div>
                    </td>
                    <td class="px-4 py-4 text-center text-gray-400 text-sm hidden sm:table-cell"><?= (int)$o['item_count'] ?></td>
                    <td class="px-4 py-4 text-right font-semibold text-white">$<?= number_format($o['total'], 2) ?></td>
                    <td class="px-4 py-4 text-center">
                        <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-semibold border <?= $statusColors[$o['status']] ?? 'text-gray-400 border-white/10' ?>">
                            <?= ucfirst(e($o['status'])) ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 text-center hidden lg:table-cell">
                        <span class="text-xs font-medium <?= $payColors[$o['payment_status']] ?? 'text-gray-400' ?>">
                            <?= ucfirst(e($o['payment_status'])) ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 text-gray-500 text-xs hidden lg:table-cell"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                    <td class="px-4 py-4 text-right">
                        <a href="<?= e($basePath) ?>/admin/order-view.php?id=<?= (int)$o['id'] ?>"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-neon-blue/10 text-neon-blue border border-neon-blue/20 rounded-lg text-xs font-medium hover:bg-neon-blue/20 transition-colors">
                            <i class="ri-eye-line"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-center gap-2">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <?php $qStr = http_build_query(['q' => $search, 'status' => $statusFilter, 'page' => $i]); ?>
    <a href="?<?= $qStr ?>"
       class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-colors <?= $i === $page ? 'bg-neon-blue text-dark-950' : 'bg-dark-900/60 border border-white/10 text-gray-400 hover:text-white' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
