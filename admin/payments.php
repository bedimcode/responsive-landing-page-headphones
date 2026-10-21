<?php
/**
 * Admin — Payments & Transactions
 */
$adminPageTitle = 'Payments';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Filters
$statusFilter  = $_GET['status']  ?? '';
$paymentFilter = $_GET['payment'] ?? '';
$search        = trim($_GET['q']  ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = max(5, (int) setting('orders_per_page')); // Admin → Settings → Commerce
$offset        = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($statusFilter && in_array($statusFilter, ['pending','paid','failed','refunded'])) {
    $where[]  = 'o.payment_status = ?';
    $params[] = $statusFilter;
}
if ($paymentFilter) {
    $where[]  = 'o.payment_method = ?';
    $params[] = $paymentFilter;
}
if ($search !== '') {
    $where[]  = '(o.order_number LIKE ? OR o.shipping_name LIKE ? OR o.shipping_email LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like]);
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o $whereSql");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Fetch
$stmt = $db->prepare("
    SELECT o.id, o.order_number, o.shipping_name, o.shipping_email, o.total,
           o.subtotal, o.shipping_cost, o.tax, o.payment_method, o.payment_status,
           o.status AS order_status, o.created_at
    FROM orders o
    $whereSql
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Summary stats
$summary = $db->query("
    SELECT
        COALESCE(SUM(CASE WHEN payment_status='paid'     THEN total END), 0) AS paid_total,
        COALESCE(SUM(CASE WHEN payment_status='pending'  THEN total END), 0) AS pending_total,
        COALESCE(SUM(CASE WHEN payment_status='refunded' THEN total END), 0) AS refunded_total,
        COALESCE(SUM(CASE WHEN payment_status='failed'   THEN total END), 0) AS failed_total,
        COUNT(CASE WHEN payment_status='paid' THEN 1 END)     AS paid_count,
        COUNT(CASE WHEN payment_status='pending' THEN 1 END)  AS pending_count,
        COUNT(CASE WHEN payment_status='refunded' THEN 1 END) AS refunded_count,
        COUNT(CASE WHEN payment_status='failed' THEN 1 END)   AS failed_count
    FROM orders
")->fetch();

// Handle status update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newPaySt  = $_POST['new_payment_status'] ?? '';
    $validPay  = ['pending','paid','failed','refunded'];

    if ($orderId && in_array($newPaySt, $validPay)) {
        $db->prepare('UPDATE orders SET payment_status = ? WHERE id = ?')->execute([$newPaySt, $orderId]);
        setFlash('success', 'Payment status updated.');
    }
    header('Location: ' . $basePath . '/admin/payments.php?' . http_build_query($_GET)); exit;
}

$payStatusColors = [
    'paid'     => 'bg-green-500/10 text-green-400 border-green-500/20',
    'pending'  => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
    'failed'   => 'bg-red-500/10 text-red-400 border-red-500/20',
    'refunded' => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
];
?>

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $cards = [
        ['Paid',     $summary['paid_total'],     $summary['paid_count'],     'green-400',  'ri-checkbox-circle-line'],
        ['Pending',  $summary['pending_total'],  $summary['pending_count'],  'yellow-400', 'ri-time-line'],
        ['Refunded', $summary['refunded_total'], $summary['refunded_count'], 'gray-400',   'ri-refund-2-line'],
        ['Failed',   $summary['failed_total'],   $summary['failed_count'],   'red-400',    'ri-close-circle-line'],
    ];
    foreach ($cards as [$label, $amount, $count, $color, $icon]):
    ?>
    <a href="?status=<?= strtolower($label) ?>"
       class="bg-dark-900 border <?= $statusFilter === strtolower($label) ? 'border-'.$color.'/30' : 'border-white/[.06]' ?> rounded-2xl p-5 hover:border-white/10 transition-all group">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider"><?= $label ?></span>
            <span class="w-8 h-8 rounded-xl bg-<?= $color ?>/10 flex items-center justify-center">
                <i class="<?= $icon ?> text-<?= $color ?>"></i>
            </span>
        </div>
        <div class="font-display font-bold text-white text-2xl"><?= formatPrice($amount) ?></div>
        <div class="text-[11px] text-gray-500 mt-1"><?= $count ?> transaction<?= $count != 1 ? 's' : '' ?></div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="flex flex-wrap gap-3 mb-6 items-center">
    <form method="GET" class="flex flex-wrap gap-3 flex-1">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search order # or customer…"
               class="flex-1 min-w-[180px] px-4 py-2.5 bg-dark-900/60 border border-white/[.08] rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors">
        <select name="status" onchange="this.form.submit()"
                class="px-4 py-2.5 bg-dark-900/60 border border-white/[.08] rounded-xl text-sm text-white focus:border-neon-blue/50 focus:outline-none">
            <option value="" class="bg-dark-900">All Payment Status</option>
            <?php foreach (['pending','paid','failed','refunded'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?> class="bg-dark-900"><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-4 py-2.5 bg-neon-blue/10 border border-neon-blue/20 rounded-xl text-sm text-neon-blue hover:bg-neon-blue/20 transition-colors">
            <i class="ri-search-line"></i> Search
        </button>
        <?php if ($search || $statusFilter || $paymentFilter): ?>
        <a href="<?= e($basePath) ?>/admin/payments.php" class="px-4 py-2.5 bg-white/5 rounded-xl text-sm text-gray-400 hover:text-white transition-colors">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Payments Table -->
<div class="bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden mb-6">
    <div class="flex items-center justify-between px-6 py-4 border-b border-white/[.06]">
        <span class="text-sm text-gray-500"><?= $total ?> transaction<?= $total !== 1 ? 's' : '' ?></span>
        <span class="text-[11px] text-gray-600">Page <?= $page ?> of <?= $totalPages ?></span>
    </div>

    <?php if (empty($payments)): ?>
    <div class="px-6 py-16 text-center text-gray-600">
        <i class="ri-bank-card-line text-5xl block mb-3"></i>
        No transactions found.
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/[.06]">
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Customer</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Method</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Payment</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Date</th>
                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[.04]">
                <?php foreach ($payments as $p): ?>
                <tr class="hover:bg-white/[.02] transition-colors">
                    <td class="px-6 py-4">
                        <a href="<?= e($basePath) ?>/admin/order-view.php?id=<?= (int)$p['id'] ?>"
                           class="font-mono text-xs text-neon-blue hover:text-white transition-colors">#<?= e($p['order_number']) ?></a>
                    </td>
                    <td class="px-4 py-4 hidden md:table-cell">
                        <div class="text-white text-sm"><?= e($p['shipping_name']) ?></div>
                        <div class="text-gray-600 text-xs"><?= e($p['shipping_email']) ?></div>
                    </td>
                    <td class="px-4 py-4 hidden lg:table-cell">
                        <span class="text-[12px] text-gray-400"><?= e($p['payment_method'] ? paymentMethodLabel($p['payment_method']) : '—') ?></span>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <div class="font-semibold text-white"><?= formatPrice($p['total']) ?></div>
                        <?php if ($p['tax'] > 0): ?>
                        <div class="text-[10px] text-gray-600">Tax: <?= formatPrice($p['tax']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-semibold border <?= $payStatusColors[$p['payment_status']] ?? 'text-gray-400 border-white/10' ?>">
                            <?= ucfirst(e($p['payment_status'])) ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 text-gray-500 text-xs hidden lg:table-cell"><?= date('M j, Y g:ia', strtotime($p['created_at'])) ?></td>
                    <td class="px-4 py-4 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <!-- Quick status update -->
                            <form method="POST" class="inline-flex items-center gap-1">
                                <?= csrfField() ?>
                                <input type="hidden" name="order_id" value="<?= (int)$p['id'] ?>">
                                <select name="new_payment_status"
                                        class="px-2 py-1 bg-dark-800 border border-white/10 rounded-lg text-[11px] text-white focus:outline-none focus:border-neon-blue/50">
                                    <?php foreach (['pending','paid','failed','refunded'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $p['payment_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit"
                                        class="px-2 py-1 bg-neon-blue/10 border border-neon-blue/20 rounded-lg text-[10px] text-neon-blue font-medium hover:bg-neon-blue/20 transition-colors">
                                    Update
                                </button>
                            </form>
                            <a href="<?= e($basePath) ?>/admin/order-view.php?id=<?= (int)$p['id'] ?>"
                               class="w-7 h-7 flex items-center justify-center rounded-lg bg-white/5 text-gray-400 hover:text-white hover:bg-white/10 transition-colors text-xs"
                               title="View order">
                                <i class="ri-eye-line"></i>
                            </a>
                        </div>
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
    <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
       class="px-3 py-1.5 bg-dark-900 border border-white/[.06] rounded-lg text-xs text-gray-400 hover:text-white transition-colors">
        <i class="ri-arrow-left-s-line"></i> Prev
    </a>
    <?php endif; ?>

    <?php
    $start = max(1, $page - 2);
    $end   = min($totalPages, $page + 2);
    for ($i = $start; $i <= $end; $i++):
    ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
       class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-medium transition-colors
              <?= $i === $page ? 'bg-neon-blue text-dark-950' : 'bg-dark-900 border border-white/[.06] text-gray-400 hover:text-white' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
       class="px-3 py-1.5 bg-dark-900 border border-white/[.06] rounded-lg text-xs text-gray-400 hover:text-white transition-colors">
        Next <i class="ri-arrow-right-s-line"></i>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
