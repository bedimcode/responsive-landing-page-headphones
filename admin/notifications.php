<?php
/**
 * Admin Notifications Centre
 */
$adminPageTitle = 'Notifications';
require_once __DIR__ . '/includes/header.php';

$notifications = [];
$unreadCount   = 0;

try {
    $db = getDB();

    // Pending / new orders
    $orders = $db->query("
        SELECT id, order_number, shipping_name, total, status, created_at
        FROM orders WHERE status='pending'
        ORDER BY created_at DESC LIMIT 20
    ")->fetchAll();
    foreach ($orders as $o) {
        $notifications[] = [
            'type'  => 'order',
            'icon'  => 'ri-shopping-bag-3-line',
            'color' => 'text-neon-blue bg-neon-blue/10',
            'title' => 'New pending order from ' . e($o['shipping_name']),
            'body'  => 'Order #' . e($o['order_number']) . ' · '.formatPrice($o['total']),
            'time'  => $o['created_at'],
            'href'  => $basePath . '/admin/order-view.php?id=' . (int)$o['id'],
            'read'  => false,
            'badge' => 'Pending',
            'badge_class' => 'bg-yellow-500/10 text-yellow-400',
        ];
        $unreadCount++;
    }

    // Unread messages
    $msgs = $db->query("
        SELECT id, name, email, subject, created_at
        FROM contact_messages WHERE is_read=0
        ORDER BY created_at DESC LIMIT 20
    ")->fetchAll();
    foreach ($msgs as $m) {
        $notifications[] = [
            'type'  => 'message',
            'icon'  => 'ri-message-3-line',
            'color' => 'text-neon-purple bg-neon-purple/10',
            'title' => 'New message from ' . e($m['name']),
            'body'  => e($m['subject']) . ' · ' . e($m['email']),
            'time'  => $m['created_at'],
            'href'  => $basePath . '/admin/messages.php?view=' . (int)$m['id'],
            'read'  => false,
            'badge' => 'Unread',
            'badge_class' => 'bg-neon-purple/10 text-neon-purple',
        ];
        $unreadCount++;
    }

    // Low stock (at or below the threshold in Settings; 3 or fewer is critical)
    $lowStockStmt = $db->prepare("
        SELECT id, name, stock, price
        FROM products WHERE stock <= ? AND is_active=1
        ORDER BY stock ASC LIMIT 20
    ");
    $lowStockStmt->execute([(int) setting('low_stock_threshold')]);
    $lowStock = $lowStockStmt->fetchAll();
    foreach ($lowStock as $p) {
        $critical = $p['stock'] <= 3;
        $notifications[] = [
            'type'  => 'stock',
            'icon'  => $critical ? 'ri-close-circle-line' : 'ri-error-warning-line',
            'color' => $critical ? 'text-red-400 bg-red-500/10' : 'text-yellow-400 bg-yellow-500/10',
            'title' => ($critical ? 'Critical: ' : 'Low stock: ') . e($p['name']),
            'body'  => 'Only ' . (int)$p['stock'] . ' unit' . ($p['stock'] == 1 ? '' : 's') . ' remaining · $' . number_format($p['price'], 2),
            'time'  => null,
            'href'  => $basePath . '/admin/product-edit.php?id=' . (int)$p['id'],
            'read'  => !$critical,
            'badge' => $critical ? 'Critical' : 'Low Stock',
            'badge_class' => $critical ? 'bg-red-500/10 text-red-400' : 'bg-yellow-500/10 text-yellow-400',
        ];
        if ($critical) $unreadCount++;
    }

    // New customers this week
    $newCustomers = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='customer' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    if ($newCustomers > 0) {
        $notifications[] = [
            'type'  => 'customer',
            'icon'  => 'ri-user-add-line',
            'color' => 'text-neon-pink bg-neon-pink/10',
            'title' => $newCustomers . ' new customer' . ($newCustomers > 1 ? 's' : '') . ' this week',
            'body'  => 'View all registered customers',
            'time'  => null,
            'href'  => $basePath . '/admin/customers.php',
            'read'  => true,
            'badge' => 'New',
            'badge_class' => 'bg-neon-pink/10 text-neon-pink',
        ];
    }

    // Sort: unread first, then by time desc
    usort($notifications, function ($a, $b) {
        if ($a['read'] !== $b['read']) return $a['read'] ? 1 : -1;
        if ($a['time'] && $b['time']) return strtotime($b['time']) - strtotime($a['time']);
        if ($a['time']) return -1;
        if ($b['time']) return 1;
        return 0;
    });

} catch (Exception $e) {}

// Active filter
$filter = $_GET['type'] ?? 'all';
$filtered = $filter === 'all' ? $notifications
    : array_values(array_filter($notifications, fn($n) => $n['type'] === $filter));

// Counts per type
$typeCounts = array_count_values(array_column($notifications, 'type'));

function notifTimeAgo(?string $dt): string {
    if (!$dt) return '';
    $diff = time() - strtotime($dt);
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff/60).'m ago';
    if ($diff < 86400) return floor($diff/3600).'h ago';
    return date('M j', strtotime($dt));
}
?>

<!-- Page header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="font-display font-bold text-white text-xl">Notifications</h2>
        <p class="text-gray-500 text-sm mt-0.5">
            <?= $unreadCount ?> unread · <?= count($notifications) ?> total
        </p>
    </div>
    <?php if ($unreadCount > 0): ?>
    <a href="<?= e($basePath) ?>/admin/messages.php?mark_all_read=1"
       class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-xl text-[12px] font-medium
              border border-white/5 text-gray-400 hover:text-white hover:border-white/10 hover:bg-white/5 transition-all">
        <i class="ri-check-double-line"></i> Mark messages read
    </a>
    <?php endif; ?>
</div>

<!-- Stats strip -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <?php
    $typeStats = [
        ['label'=>'Orders',    'type'=>'order',    'icon'=>'ri-shopping-bag-3-line', 'color'=>'text-neon-blue bg-neon-blue/10'],
        ['label'=>'Messages',  'type'=>'message',  'icon'=>'ri-message-3-line',      'color'=>'text-neon-purple bg-neon-purple/10'],
        ['label'=>'Stock',     'type'=>'stock',    'icon'=>'ri-error-warning-line',  'color'=>'text-yellow-400 bg-yellow-500/10'],
        ['label'=>'Customers', 'type'=>'customer', 'icon'=>'ri-user-add-line',       'color'=>'text-neon-pink bg-neon-pink/10'],
    ];
    foreach ($typeStats as $ts):
    ?>
    <a href="?type=<?= $ts['type'] ?>"
       class="bg-dark-900 border <?= $filter===$ts['type']?'border-neon-blue/30':'border-white/5' ?> rounded-xl p-3.5 flex items-center gap-3 hover:border-white/10 transition-all">
        <span class="w-8 h-8 rounded-xl <?= $ts['color'] ?> flex items-center justify-center flex-shrink-0 text-sm">
            <i class="<?= $ts['icon'] ?>"></i>
        </span>
        <div>
            <div class="font-display font-bold text-white text-lg leading-none"><?= $typeCounts[$ts['type']] ?? 0 ?></div>
            <div class="text-[11px] text-gray-500"><?= $ts['label'] ?></div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter tabs -->
<div class="flex items-center gap-1 mb-4 bg-dark-900 border border-white/5 rounded-xl p-1 w-fit flex-wrap">
    <?php
    $tabs = [
        ['type'=>'all',      'label'=>'All',      'count'=>count($notifications)],
        ['type'=>'order',    'label'=>'Orders',   'count'=>$typeCounts['order']??0],
        ['type'=>'message',  'label'=>'Messages', 'count'=>$typeCounts['message']??0],
        ['type'=>'stock',    'label'=>'Stock',    'count'=>$typeCounts['stock']??0],
        ['type'=>'customer', 'label'=>'Customers','count'=>$typeCounts['customer']??0],
    ];
    foreach ($tabs as $tab):
    ?>
    <a href="?type=<?= $tab['type'] ?>"
       class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-medium transition-all
              <?= $filter===$tab['type'] ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-white' ?>">
        <?= $tab['label'] ?>
        <?php if ($tab['count'] > 0): ?>
        <span class="min-w-[18px] h-[18px] rounded-full bg-white/10 flex items-center justify-center text-[10px] font-bold px-1">
            <?= $tab['count'] ?>
        </span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Notifications list -->
<?php if (empty($filtered)): ?>
<div class="bg-dark-900 border border-white/5 rounded-2xl p-12 text-center">
    <i class="ri-notification-off-line text-4xl text-gray-700 block mb-3"></i>
    <p class="text-gray-500 text-sm">No notifications in this category</p>
    <a href="?type=all" class="text-neon-blue text-sm hover:underline mt-2 inline-block">View all →</a>
</div>
<?php else: ?>
<div class="space-y-2">
    <?php foreach ($filtered as $n): ?>
    <a href="<?= e($n['href']) ?>"
       class="flex items-start gap-4 p-4 rounded-2xl border transition-all
              <?= $n['read']
                  ? 'bg-dark-900 border-white/5 hover:border-white/10 hover:bg-dark-800'
                  : 'bg-dark-800 border-white/10 hover:bg-dark-900 ring-1 ring-white/5' ?>">

        <!-- Icon -->
        <span class="mt-0.5 w-9 h-9 rounded-xl <?= $n['color'] ?> flex items-center justify-center flex-shrink-0 text-base">
            <i class="<?= $n['icon'] ?>"></i>
        </span>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <p class="text-sm font-medium text-white"><?= $n['title'] ?></p>
                <?php if (!$n['read']): ?>
                <span class="w-1.5 h-1.5 rounded-full bg-neon-blue flex-shrink-0"></span>
                <?php endif; ?>
            </div>
            <p class="text-[12px] text-gray-500 mt-0.5 truncate"><?= $n['body'] ?></p>
        </div>

        <!-- Meta -->
        <div class="flex flex-col items-end gap-2 flex-shrink-0 ml-2">
            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $n['badge_class'] ?>">
                <?= $n['badge'] ?>
            </span>
            <?php if ($n['time']): ?>
            <span class="text-[11px] text-gray-600"><?= notifTimeAgo($n['time']) ?></span>
            <?php endif; ?>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
