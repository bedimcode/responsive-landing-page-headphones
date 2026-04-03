<?php
/**
 * Admin — Analytics & Reports
 */
$adminPageTitle = 'Analytics';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Date range
$range    = $_GET['range'] ?? '30';
$validRanges = ['7','14','30','90','365'];
if (!in_array($range, $validRanges)) $range = '30';
$rangeLabel = ['7'=>'7 days','14'=>'14 days','30'=>'30 days','90'=>'90 days','365'=>'1 year'][$range];

// ---- KPIs for the range ----
$kpi = $db->prepare("
    SELECT
        COUNT(*)                                              AS total_orders,
        COALESCE(SUM(CASE WHEN payment_status='paid' THEN total END), 0) AS revenue,
        COALESCE(AVG(CASE WHEN payment_status='paid' THEN total END), 0) AS avg_order,
        SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END)  AS cancelled
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
");
$kpi->execute([$range]);
$kpi = $kpi->fetch();

$newCustomers = (int) $db->prepare("SELECT COUNT(*) FROM users WHERE role='customer' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)")->execute([$range])
    ? call_user_func(function() use ($db, $range) {
        $s = $db->prepare("SELECT COUNT(*) FROM users WHERE role='customer' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)");
        $s->execute([$range]); return (int)$s->fetchColumn();
    }) : 0;

$conversionRate = $kpi['total_orders'] > 0 && $newCustomers > 0
    ? round(($kpi['total_orders'] / max(1, $newCustomers)) * 100, 1)
    : 0;

// ---- Daily revenue chart ----
$days = (int)$range;
$dailyRaw = $db->prepare("
    SELECT DATE(created_at) AS day,
           COALESCE(SUM(CASE WHEN payment_status='paid' THEN total END), 0) AS revenue,
           COUNT(*) AS orders
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$dailyRaw->execute([$days]);
$dailyMap = [];
foreach ($dailyRaw->fetchAll() as $r) $dailyMap[$r['day']] = $r;

$chartLabels = $chartRevenue = $chartOrders = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[]  = date($days > 30 ? 'M j' : 'D, M j', strtotime($d));
    $chartRevenue[] = (float)($dailyMap[$d]['revenue'] ?? 0);
    $chartOrders[]  = (int)($dailyMap[$d]['orders'] ?? 0);
}

// ---- Orders by status ----
$statusData = $db->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status ORDER BY cnt DESC")->fetchAll();

// ---- Top products by revenue ----
$topProducts = $db->prepare("
    SELECT p.name, SUM(oi.quantity) AS sold, SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN orders o ON o.id = oi.order_id
    WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 10
");
$topProducts->execute([$range]);
$topProducts = $topProducts->fetchAll();

// ---- Revenue by payment method ----
$payMethods = $db->query("
    SELECT COALESCE(payment_method,'Unknown') AS method, COUNT(*) AS cnt, COALESCE(SUM(total),0) AS total
    FROM orders WHERE payment_status='paid'
    GROUP BY payment_method ORDER BY total DESC
")->fetchAll();

// ---- Recent activity timeline ----
$timeline = $db->query("
    (SELECT 'order' AS type, order_number AS ref, shipping_name AS who, total AS amount, created_at FROM orders ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'customer' AS type, '' AS ref, name AS who, 0 AS amount, created_at FROM users WHERE role='customer' ORDER BY created_at DESC LIMIT 3)
    UNION ALL
    (SELECT 'message' AS type, subject AS ref, name AS who, 0 AS amount, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 3)
    ORDER BY created_at DESC LIMIT 10
")->fetchAll();

$statusColors = ['pending'=>'#f59e0b','processing'=>'#3b82f6','shipped'=>'#a855f7','delivered'=>'#10b981','cancelled'=>'#ef4444'];
?>

<!-- Range selector -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <p class="text-sm text-gray-500">Showing data for the last <span class="text-white font-medium"><?= $rangeLabel ?></span></p>
    </div>
    <div class="flex gap-1 bg-dark-900 border border-white/[.06] rounded-xl p-1">
        <?php foreach (['7'=>'7D','14'=>'14D','30'=>'30D','90'=>'90D','365'=>'1Y'] as $v => $l): ?>
        <a href="?range=<?= $v ?>"
           class="px-3 py-1.5 rounded-lg text-[12px] font-medium transition-all
                  <?= $range === $v ? 'bg-neon-blue text-dark-950' : 'text-gray-500 hover:text-white' ?>">
            <?= $l ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <?php
    $cards = [
        ['Revenue',     '$' . number_format($kpi['revenue'], 0), 'ri-money-dollar-circle-line', 'neon-blue'],
        ['Orders',      number_format($kpi['total_orders']),       'ri-shopping-bag-3-line',      'neon-purple'],
        ['Avg. Order',  '$' . number_format($kpi['avg_order'], 2), 'ri-funds-line',               'neon-cyan'],
        ['New Customers', $newCustomers,                           'ri-user-add-line',            'neon-pink'],
        ['Cancelled',   (int)$kpi['cancelled'],                    'ri-close-circle-line',        'red-400'],
    ];
    foreach ($cards as [$label,$val,$icon,$color]):
    ?>
    <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider"><?= $label ?></span>
            <span class="w-8 h-8 rounded-xl bg-<?= $color ?>/10 flex items-center justify-center"><i class="<?= $icon ?> text-<?= $color ?>"></i></span>
        </div>
        <div class="font-display font-bold text-white text-2xl"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts row -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">

    <!-- Revenue + Orders chart (2/3) -->
    <div class="xl:col-span-2 bg-dark-900 border border-white/[.06] rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-semibold text-white text-sm">Revenue & Orders</h3>
                <p class="text-[11px] text-gray-500 mt-0.5">Daily breakdown — paid orders</p>
            </div>
            <div class="flex items-center gap-4 text-[11px] text-gray-500">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-neon-blue inline-block"></span>Revenue</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-[2px] bg-neon-purple inline-block"></span>Orders</span>
            </div>
        </div>
        <div class="h-60"><canvas id="revenueChart"></canvas></div>
    </div>

    <!-- Order status donut (1/3) -->
    <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-5">
        <h3 class="font-semibold text-white text-sm mb-4">Orders by Status</h3>
        <?php if (!empty($statusData)): ?>
        <div class="h-44 flex items-center justify-center"><canvas id="statusChart"></canvas></div>
        <div class="mt-4 space-y-2">
            <?php foreach ($statusData as $row): ?>
            <div class="flex items-center gap-2 text-[11px]">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:<?= $statusColors[$row['status']] ?? '#666' ?>"></span>
                <span class="text-gray-400 flex-1"><?= ucfirst(e($row['status'])) ?></span>
                <span class="font-medium text-white"><?= (int)$row['cnt'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="py-10 text-center text-gray-600 text-sm"><i class="ri-pie-chart-line text-3xl block mb-2"></i>No orders yet</div>
        <?php endif; ?>
    </div>
</div>

<!-- Second row: Top Products + Payment Methods + Timeline -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

    <!-- Top products -->
    <div class="lg:col-span-2 bg-dark-900 border border-white/[.06] rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-white/[.06]">
            <h3 class="font-semibold text-white text-sm">Top Products by Revenue</h3>
        </div>
        <?php if (empty($topProducts)): ?>
        <div class="px-5 py-10 text-center text-gray-600 text-sm">No product sales in this period</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[10px] text-gray-500 uppercase tracking-wider border-b border-white/[.06]">
                        <th class="px-5 py-3 text-left font-medium">#</th>
                        <th class="px-3 py-3 text-left font-medium">Product</th>
                        <th class="px-3 py-3 text-right font-medium">Units Sold</th>
                        <th class="px-3 py-3 text-right font-medium">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[.04]">
                    <?php foreach ($topProducts as $i => $p): ?>
                    <tr class="hover:bg-white/[.02] transition-colors">
                        <td class="px-5 py-3 text-gray-600 text-xs"><?= $i + 1 ?></td>
                        <td class="px-3 py-3 text-white font-medium"><?= e($p['name']) ?></td>
                        <td class="px-3 py-3 text-right text-gray-400"><?= number_format($p['sold']) ?></td>
                        <td class="px-3 py-3 text-right text-white font-semibold">$<?= number_format($p['revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent activity -->
    <div class="bg-dark-900 border border-white/[.06] rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-white/[.06]">
            <h3 class="font-semibold text-white text-sm">Recent Activity</h3>
        </div>
        <div class="divide-y divide-white/[.04] max-h-[360px] overflow-y-auto">
            <?php if (empty($timeline)): ?>
            <div class="px-5 py-10 text-center text-gray-600 text-sm">No recent activity</div>
            <?php else: foreach ($timeline as $t):
                $iconMap = ['order'=>['ri-shopping-bag-3-line','text-neon-blue bg-neon-blue/10'],
                            'customer'=>['ri-user-add-line','text-neon-pink bg-neon-pink/10'],
                            'message'=>['ri-message-3-line','text-neon-purple bg-neon-purple/10']];
                $ic = $iconMap[$t['type']] ?? ['ri-notification-3-line','text-gray-400 bg-white/5'];
            ?>
            <div class="flex items-start gap-3 px-5 py-3">
                <span class="w-8 h-8 rounded-lg <?= $ic[1] ?> flex items-center justify-center flex-shrink-0 mt-0.5 text-sm">
                    <i class="<?= $ic[0] ?>"></i>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-[12px] text-white font-medium truncate">
                        <?php if ($t['type'] === 'order'): ?>
                            Order #<?= e($t['ref']) ?> by <?= e($t['who']) ?>
                        <?php elseif ($t['type'] === 'customer'): ?>
                            New customer: <?= e($t['who']) ?>
                        <?php else: ?>
                            Message: <?= e($t['ref']) ?> from <?= e($t['who']) ?>
                        <?php endif; ?>
                    </p>
                    <p class="text-[11px] text-gray-500">
                        <?= $t['amount'] > 0 ? '$'.number_format($t['amount'],2).' · ' : '' ?><?= adminTimeAgo($t['created_at']) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Payment methods breakdown -->
<?php if (!empty($payMethods)): ?>
<div class="bg-dark-900 border border-white/[.06] rounded-2xl p-5 mb-6">
    <h3 class="font-semibold text-white text-sm mb-4">Revenue by Payment Method</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($payMethods as $pm): ?>
        <div class="bg-white/[.02] border border-white/[.04] rounded-xl p-4">
            <div class="text-[11px] text-gray-500 uppercase tracking-wider mb-1"><?= e($pm['method'] ?: 'Unknown') ?></div>
            <div class="font-display font-bold text-white text-lg">$<?= number_format($pm['total'], 2) ?></div>
            <div class="text-[11px] text-gray-500 mt-1"><?= (int)$pm['cnt'] ?> order<?= $pm['cnt'] > 1 ? 's' : '' ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels  = <?= json_encode($chartLabels) ?>;
const revenue = <?= json_encode($chartRevenue) ?>;
const orders  = <?= json_encode($chartOrders) ?>;

// Revenue + orders line chart
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            {label:'Revenue', data:revenue, borderColor:'#00d4ff', backgroundColor:'rgba(0,212,255,.08)',
             fill:true, tension:.4, borderWidth:2, pointRadius:0, pointHoverRadius:5, yAxisID:'y'},
            {label:'Orders', data:orders, borderColor:'#a855f7', backgroundColor:'transparent',
             borderDash:[5,3], tension:.4, borderWidth:1.5, pointRadius:0, pointHoverRadius:4, yAxisID:'y1'}
        ]
    },
    options:{
        responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false},
        plugins:{legend:{display:false}, tooltip:{backgroundColor:'#141418',titleColor:'#fff',bodyColor:'#9ca3af',
                 borderColor:'rgba(255,255,255,.06)',borderWidth:1,cornerRadius:12,padding:12,
                 callbacks:{label:ctx=>ctx.dataset.label+': '+(ctx.datasetIndex===0?'$'+ctx.parsed.y.toLocaleString():ctx.parsed.y)}}},
        scales:{
            x:{grid:{color:'rgba(255,255,255,.03)'},ticks:{color:'#555',font:{size:10},maxRotation:0,autoSkip:true,maxTicksLimit:<?= min($days, 14) ?>}},
            y:{position:'left',grid:{color:'rgba(255,255,255,.03)'},ticks:{color:'#555',font:{size:10},callback:v=>'$'+v.toLocaleString()}},
            y1:{position:'right',grid:{display:false},ticks:{color:'#555',font:{size:10},precision:0}}
        }
    }
});

<?php if (!empty($statusData)): ?>
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(fn($r)=>ucfirst($r['status']), $statusData)) ?>,
        datasets: [{
            data: <?= json_encode(array_column($statusData, 'cnt')) ?>,
            backgroundColor: <?= json_encode(array_map(fn($r)=>$statusColors[$r['status']]??'#666', $statusData)) ?>,
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options:{responsive:true,maintainAspectRatio:false,cutout:'70%',
        plugins:{legend:{display:false},
                 tooltip:{backgroundColor:'#141418',titleColor:'#fff',bodyColor:'#9ca3af',borderColor:'rgba(255,255,255,.06)',borderWidth:1,cornerRadius:10,padding:10}}}
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
