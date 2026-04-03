<?php
/**
 * Admin Dashboard — Comprehensive with Charts & Analytics
 */
$adminPageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

// ---- Stats ----
$stats = ['orders'=>0,'revenue'=>0.0,'products'=>0,'customers'=>0,'pending'=>0,'today_revenue'=>0.0];
$trend = ['revenue'=>0.0,'orders'=>0,'customers'=>0,'rev_prev'=>0.0,'ord_prev'=>0,'cust_prev'=>0];
$recentOrders=$topProducts=$lowStockProducts=$chartRevenue=$chartStatus=[];

try {
    $db = getDB();

    $stats['orders']        = (int)   $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['revenue']       = (float) $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid'")->fetchColumn();
    $stats['products']      = (int)   $db->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
    $stats['customers']     = (int)   $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
    $stats['pending']       = (int)   $db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
    $stats['today_revenue'] = (float) $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)=CURDATE() AND payment_status='paid'")->fetchColumn();

    // Month-over-month trends
    $tr = $db->query("SELECT
        SUM(CASE WHEN YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE()) AND payment_status='paid' THEN total ELSE 0 END) rev_cur,
        SUM(CASE WHEN YEAR(created_at)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND MONTH(created_at)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND payment_status='paid' THEN total ELSE 0 END) rev_prev,
        SUM(CASE WHEN YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE()) THEN 1 ELSE 0 END) ord_cur,
        SUM(CASE WHEN YEAR(created_at)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND MONTH(created_at)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) THEN 1 ELSE 0 END) ord_prev
        FROM orders")->fetch();
    $trend['revenue']  = (float)($tr['rev_cur']??0);  $trend['rev_prev']  = (float)($tr['rev_prev']??0);
    $trend['orders']   = (int)  ($tr['ord_cur']??0);  $trend['ord_prev']  = (int)  ($tr['ord_prev']??0);
    $ct = $db->query("SELECT
        SUM(CASE WHEN YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE()) THEN 1 ELSE 0 END) cur,
        SUM(CASE WHEN YEAR(created_at)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND MONTH(created_at)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) THEN 1 ELSE 0 END) prev
        FROM users WHERE role='customer'")->fetch();
    $trend['customers']=$trend['cust_cur']=(int)($ct['cur']??0); $trend['cust_prev']=(int)($ct['prev']??0);

    // 14-day revenue chart
    $raw14 = $db->query("SELECT DATE(created_at) day, COALESCE(SUM(total),0) revenue, COUNT(*) orders
        FROM orders WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 13 DAY) AND payment_status='paid'
        GROUP BY DATE(created_at) ORDER BY day ASC")->fetchAll(PDO::FETCH_ASSOC);
    $rawMap=[];
    foreach($raw14 as $r) $rawMap[$r['day']]=$r;
    for($i=13;$i>=0;$i--){
        $d=date('Y-m-d',strtotime("-{$i} days"));
        $chartRevenue[]=['label'=>date('M j',strtotime($d)),'revenue'=>(float)($rawMap[$d]['revenue']??0),'orders'=>(int)($rawMap[$d]['orders']??0)];
    }

    // Status donut
    $chartStatus=$db->query("SELECT status, COUNT(*) count FROM orders GROUP BY status ORDER BY count DESC")->fetchAll();

    // Recent orders
    $recentOrders=$db->query("SELECT id,order_number,shipping_name,total,status,payment_status,created_at FROM orders ORDER BY created_at DESC LIMIT 10")->fetchAll();

    // Top products
    $topProducts=$db->query("SELECT p.id,p.name,p.price,p.stock,COALESCE(SUM(oi.quantity),0) sold,COALESCE(SUM(oi.quantity*oi.price),0) revenue
        FROM products p LEFT JOIN order_items oi ON oi.product_id=p.id GROUP BY p.id ORDER BY sold DESC LIMIT 5")->fetchAll();

    // Low stock
    $lowStockProducts=$db->query("SELECT id,name,stock FROM products WHERE stock<10 AND is_active=1 ORDER BY stock ASC LIMIT 8")->fetchAll();

} catch (Exception $e) {}

function trendBadge(float|int $cur, float|int $prev, bool $up=true): string {
    if($prev==0) return $cur>0?'<span class="text-[11px] text-green-400 bg-green-500/10 px-1.5 py-0.5 rounded-full">New</span>':'';
    $pct=round(($cur-$prev)/$prev*100,1);
    $isUp=$pct>=0; $good=$up?$isUp:!$isUp;
    $arrow=$isUp?'ri-arrow-up-line':'ri-arrow-down-line';
    $c=$good?'text-green-400 bg-green-500/10':'text-red-400 bg-red-500/10';
    return '<span class="inline-flex items-center gap-0.5 text-[11px] font-medium '.$c.' px-1.5 py-0.5 rounded-full"><i class="'.$arrow.' text-[10px]"></i>'.abs($pct).'%</span>';
}
function osBadge(string $st): string {
    $m=['pending'=>'bg-yellow-500/10 text-yellow-400 border-yellow-500/20','processing'=>'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'shipped'=>'bg-purple-500/10 text-purple-400 border-purple-500/20','delivered'=>'bg-green-500/10 text-green-400 border-green-500/20',
        'cancelled'=>'bg-red-500/10 text-red-400 border-red-500/20'];
    $c=$m[$st]??'bg-gray-500/10 text-gray-400 border-gray-500/20';
    return '<span class="text-[10px] font-medium px-2 py-0.5 rounded-full border '.$c.'">'.ucfirst($st).'</span>';
}

$chartLabelsJson  = json_encode(array_column($chartRevenue,'label'));
$chartRevenueJson = json_encode(array_column($chartRevenue,'revenue'));
$chartOrdersJson  = json_encode(array_column($chartRevenue,'orders'));
$donutLabels  = json_encode(array_map(fn($r)=>ucfirst($r['status']),$chartStatus));
$donutCounts  = json_encode(array_column($chartStatus,'count'));
$donutColors  = ['#00d4ff','#a855f7','#10b981','#f59e0b','#ef4444','#06b6d4'];
$donutColorsJ = json_encode(array_slice($donutColors,0,count($chartStatus)));
?>

<!-- Page header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="font-display font-bold text-white text-xl">Welcome back, <?= e(currentUserName()) ?> 👋</h2>
        <p class="text-gray-500 text-sm mt-0.5"><?= date('l, F j, Y') ?></p>
    </div>
    <a href="<?= e($basePath) ?>/admin/product-edit.php"
       class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-neon-blue/10 border border-neon-blue/20
              text-neon-blue text-sm font-medium hover:bg-neon-blue/20 transition-all">
        <i class="ri-add-line"></i> New Product
    </a>
</div>

<!-- KPI CARDS -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="col-span-2 sm:col-span-1 bg-dark-900 border border-white/5 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Total Revenue</span>
            <span class="w-8 h-8 rounded-xl bg-neon-blue/10 flex items-center justify-center"><i class="ri-money-dollar-circle-line text-neon-blue"></i></span>
        </div>
        <div class="font-display font-bold text-white text-2xl mb-1">$<?= number_format($stats['revenue'], 0) ?></div>
        <div class="flex items-center gap-2"><?= trendBadge($trend['revenue'],$trend['rev_prev']) ?><span class="text-[11px] text-gray-500">vs last month</span></div>
    </div>
    <div class="bg-dark-900 border border-white/5 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Orders</span>
            <span class="w-8 h-8 rounded-xl bg-neon-purple/10 flex items-center justify-center"><i class="ri-shopping-bag-3-line text-neon-purple"></i></span>
        </div>
        <div class="font-display font-bold text-white text-2xl mb-1"><?= number_format($stats['orders']) ?></div>
        <div class="flex items-center gap-2"><?= trendBadge($trend['orders'],$trend['ord_prev']) ?><span class="text-[11px] text-gray-500">vs last month</span></div>
    </div>
    <div class="bg-dark-900 border border-white/5 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Customers</span>
            <span class="w-8 h-8 rounded-xl bg-neon-pink/10 flex items-center justify-center"><i class="ri-user-3-line text-neon-pink"></i></span>
        </div>
        <div class="font-display font-bold text-white text-2xl mb-1"><?= number_format($stats['customers']) ?></div>
        <div class="flex items-center gap-2"><?= trendBadge($trend['cust_cur']??0,$trend['cust_prev']) ?><span class="text-[11px] text-gray-500">vs last month</span></div>
    </div>
    <div class="bg-dark-900 border border-white/5 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Pending</span>
            <span class="w-8 h-8 rounded-xl bg-yellow-500/10 flex items-center justify-center"><i class="ri-time-line text-yellow-400"></i></span>
        </div>
        <div class="font-display font-bold text-white text-2xl mb-1"><?= $stats['pending'] ?></div>
        <a href="<?= e($basePath) ?>/admin/orders.php?status=pending" class="text-[11px] text-yellow-400 hover:underline">Review →</a>
    </div>
</div>

<!-- Mini-stat strip -->
<div class="grid grid-cols-3 gap-3 mb-6">
    <div class="bg-dark-900 border border-white/5 rounded-xl px-4 py-3 flex items-center gap-3">
        <i class="ri-sun-line text-neon-cyan text-lg flex-shrink-0"></i>
        <div><div class="text-[11px] text-gray-500">Today's Revenue</div><div class="font-semibold text-white text-sm">$<?= number_format($stats['today_revenue'],2) ?></div></div>
    </div>
    <div class="bg-dark-900 border border-white/5 rounded-xl px-4 py-3 flex items-center gap-3">
        <i class="ri-box-3-line text-neon-purple text-lg flex-shrink-0"></i>
        <div><div class="text-[11px] text-gray-500">Active Products</div><div class="font-semibold text-white text-sm"><?= $stats['products'] ?></div></div>
    </div>
    <div class="bg-dark-900 border border-white/5 rounded-xl px-4 py-3 flex items-center gap-3">
        <i class="ri-error-warning-line text-red-400 text-lg flex-shrink-0"></i>
        <div><div class="text-[11px] text-gray-500">Low Stock</div><div class="font-semibold text-white text-sm"><?= count($lowStockProducts) ?> items</div></div>
    </div>
</div>

<!-- CHARTS ROW -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">

    <!-- Revenue line (2/3) -->
    <div class="xl:col-span-2 bg-dark-900 border border-white/5 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-semibold text-white text-sm">Revenue Overview</h3>
                <p class="text-[11px] text-gray-500 mt-0.5">Last 14 days · paid orders only</p>
            </div>
            <div class="flex items-center gap-4 text-[11px] text-gray-500">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-neon-blue inline-block"></span>Revenue</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-[2px] bg-neon-purple inline-block"></span>Orders</span>
            </div>
        </div>
        <div class="h-52"><canvas id="revenueChart"></canvas></div>
    </div>

    <!-- Status donut (1/3) -->
    <div class="bg-dark-900 border border-white/5 rounded-2xl p-5">
        <div class="mb-4">
            <h3 class="font-semibold text-white text-sm">Orders by Status</h3>
            <p class="text-[11px] text-gray-500 mt-0.5">All-time distribution</p>
        </div>
        <div class="h-36 flex items-center justify-center"><canvas id="statusChart"></canvas></div>
        <div class="mt-4 space-y-2">
            <?php foreach($chartStatus as $i=>$row): $col=$donutColors[$i%count($donutColors)]; ?>
            <div class="flex items-center gap-2 text-[11px]">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:<?=$col?>"></span>
                <span class="text-gray-400 flex-1"><?= ucfirst(e($row['status'])) ?></span>
                <span class="font-medium text-white"><?= $row['count'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php if(empty($chartStatus)): ?><p class="text-[11px] text-gray-600 text-center py-2">No orders yet</p><?php endif; ?>
        </div>
    </div>
</div>

<!-- DATA ROW -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

    <!-- Recent Orders (2/3) -->
    <div class="xl:col-span-2 bg-dark-900 border border-white/5 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-white/5">
            <h3 class="font-semibold text-white text-sm">Recent Orders</h3>
            <a href="<?= e($basePath) ?>/admin/orders.php" class="text-[12px] text-neon-blue hover:underline font-medium">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[10px] text-gray-500 uppercase tracking-wider border-b border-white/5">
                        <th class="px-5 py-3 text-left font-medium">Order</th>
                        <th class="px-3 py-3 text-left font-medium hidden sm:table-cell">Customer</th>
                        <th class="px-3 py-3 text-left font-medium">Status</th>
                        <th class="px-3 py-3 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if(empty($recentOrders)): ?>
                    <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500 text-xs">No orders yet</td></tr>
                    <?php else: ?>
                    <?php foreach($recentOrders as $o): ?>
                    <tr class="hover:bg-white/2 transition-colors">
                        <td class="px-5 py-3">
                            <a href="<?= e($basePath) ?>/admin/order-view.php?id=<?= (int)$o['id'] ?>"
                               class="font-mono text-xs text-neon-blue hover:text-white transition-colors">#<?= e($o['order_number']) ?></a>
                            <div class="text-[10px] text-gray-600 mt-0.5"><?= date('M j, g:ia', strtotime($o['created_at'])) ?></div>
                        </td>
                        <td class="px-3 py-3 hidden sm:table-cell text-[13px] text-gray-300"><?= e($o['shipping_name']) ?></td>
                        <td class="px-3 py-3"><?= osBadge($o['status']) ?></td>
                        <td class="px-3 py-3 text-right font-semibold text-white text-[13px]">$<?= number_format($o['total'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right sidebar -->
    <div class="space-y-4">

        <!-- Top Products -->
        <div class="bg-dark-900 border border-white/5 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3.5 border-b border-white/5">
                <h3 class="font-semibold text-white text-sm">Top Products</h3>
                <a href="<?= e($basePath) ?>/admin/products.php" class="text-[12px] text-neon-blue hover:underline">View all →</a>
            </div>
            <div class="divide-y divide-white/5">
                <?php if(empty($topProducts)): ?>
                <p class="px-4 py-5 text-xs text-gray-500 text-center">No products yet</p>
                <?php else: ?>
                <?php foreach($topProducts as $i=>$p): ?>
                <div class="flex items-center gap-3 px-4 py-3">
                    <span class="w-5 h-5 rounded-full bg-white/5 flex items-center justify-center text-[10px] font-bold text-gray-500"><?= $i+1 ?></span>
                    <div class="flex-1 min-w-0">
                        <div class="text-[12px] font-medium text-white truncate"><?= e($p['name']) ?></div>
                        <div class="text-[10px] text-gray-500"><?= (int)$p['sold'] ?> sold</div>
                    </div>
                    <span class="text-[12px] font-medium text-gray-300">$<?= number_format($p['price'],0) ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Low Stock -->
        <?php if(!empty($lowStockProducts)): ?>
        <div class="bg-dark-900 border border-yellow-500/20 rounded-2xl overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-3 border-b border-yellow-500/10">
                <i class="ri-error-warning-line text-yellow-400 text-sm"></i>
                <h3 class="font-semibold text-yellow-400 text-sm">Low Stock Alert</h3>
            </div>
            <div class="divide-y divide-white/5">
                <?php foreach($lowStockProducts as $p): ?>
                <div class="flex items-center justify-between px-4 py-2.5">
                    <span class="text-[12px] text-gray-300 truncate max-w-[62%]"><?= e($p['name']) ?></span>
                    <span class="text-[11px] font-semibold <?= $p['stock']<=3?'text-red-400':'text-yellow-400' ?>"><?= (int)$p['stock'] ?> left</span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="px-4 py-2.5 border-t border-yellow-500/10">
                <a href="<?= e($basePath) ?>/admin/products.php" class="text-[12px] text-yellow-400 hover:underline font-medium">Manage inventory →</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="bg-dark-900 border border-white/5 rounded-2xl p-4">
            <h3 class="font-semibold text-white text-sm mb-3">Quick Actions</h3>
            <div class="grid grid-cols-2 gap-2">
                <?php $qa=[
                    ['href'=>$basePath.'/admin/product-edit.php','icon'=>'ri-add-circle-line','label'=>'Add Product','hov'=>'hover:bg-neon-blue/10 hover:text-neon-blue'],
                    ['href'=>$basePath.'/admin/orders.php?status=pending','icon'=>'ri-time-line','label'=>'Pending','hov'=>'hover:bg-yellow-500/10 hover:text-yellow-400'],
                    ['href'=>$basePath.'/admin/messages.php','icon'=>'ri-message-3-line','label'=>'Messages','hov'=>'hover:bg-neon-purple/10 hover:text-neon-purple'],
                    ['href'=>$basePath.'/admin/customers.php','icon'=>'ri-user-3-line','label'=>'Customers','hov'=>'hover:bg-neon-pink/10 hover:text-neon-pink'],
                ]; ?>
                <?php foreach($qa as $a): ?>
                <a href="<?= e($a['href']) ?>"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-white/3 <?= $a['hov'] ?> transition-all text-gray-400 text-center">
                    <i class="<?= $a['icon'] ?> text-lg"></i>
                    <span class="text-[11px] font-medium"><?= $a['label'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color='#6b7280';
Chart.defaults.borderColor='rgba(255,255,255,0.05)';

(function(){
    const ctx=document.getElementById('revenueChart').getContext('2d');
    const bg=ctx.createLinearGradient(0,0,0,200);
    bg.addColorStop(0,'rgba(0,212,255,0.2)');bg.addColorStop(1,'rgba(0,212,255,0)');
    new Chart(ctx,{type:'line',data:{labels:<?=$chartLabelsJson?>,datasets:[
        {label:'Revenue',data:<?=$chartRevenueJson?>,borderColor:'#00d4ff',backgroundColor:bg,borderWidth:2,tension:0.4,fill:true,pointRadius:3,pointHoverRadius:5,pointBackgroundColor:'#00d4ff',yAxisID:'y'},
        {label:'Orders',data:<?=$chartOrdersJson?>,borderColor:'#a855f7',backgroundColor:'transparent',borderWidth:2,tension:0.4,fill:false,pointRadius:3,pointHoverRadius:5,pointBackgroundColor:'#a855f7',borderDash:[4,3],yAxisID:'y1'}
    ]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{mode:'index',intersect:false,backgroundColor:'#0a0a0d',borderColor:'rgba(255,255,255,0.1)',borderWidth:1,callbacks:{label:c=>c.datasetIndex===0?' $'+c.parsed.y.toFixed(2):' '+c.parsed.y+' orders'}}},
    scales:{x:{grid:{display:false},ticks:{font:{size:10},maxRotation:0}},y:{position:'left',grid:{color:'rgba(255,255,255,0.04)'},ticks:{font:{size:10},callback:v=>'$'+v}},y1:{position:'right',grid:{display:false},ticks:{font:{size:10}}}}}});
})();

(function(){
    const ctx=document.getElementById('statusChart').getContext('2d');
    new Chart(ctx,{type:'doughnut',data:{labels:<?=$donutLabels?>,datasets:[{data:<?=$donutCounts?>,backgroundColor:<?=$donutColorsJ?>,borderWidth:0,hoverOffset:4}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false},tooltip:{backgroundColor:'#0a0a0d',borderColor:'rgba(255,255,255,0.1)',borderWidth:1}}}});
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
