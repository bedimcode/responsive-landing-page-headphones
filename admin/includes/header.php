<?php
/**
 * Admin Panel — Shared Header, Sidebar & Layout
 * Included at the top of every admin page.
 * Set $adminPageTitle before including.
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__DIR__)));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/migrations.php';

requireAdmin();

$adminPageTitle   = $adminPageTitle ?? 'Dashboard';
$basePath         = rtrim(APP_URL, '/');
$currentPage      = basename($_SERVER['PHP_SELF'], '.php');

/* ------------------------------------------------------------------ */
/*  Live counts for badges                                            */
/* ------------------------------------------------------------------ */
$_hdrUnreadMsgs = $pendingOrders = $lowStockCount = 0;
$_hdrNotifItems = [];
$_hdrDefaultPassword = false;
$_hdrPendingMigrations = 0;

try {
    $db = getDB();
    // Setup warnings: published default passwords and missing database updates
    // (bcrypt checks are slow, so the answer is cached per session; profile.php clears it)
    if (!isset($_SESSION['admin_default_pw'])) {
        $pwStmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
        $pwStmt->execute([currentUserId()]);
        $pwHash = (string) $pwStmt->fetchColumn();
        $_SESSION['admin_default_pw'] = password_verify('Admin@123', $pwHash) || password_verify('password', $pwHash);
    }
    $_hdrDefaultPassword = $_SESSION['admin_default_pw'];
    $_hdrPendingMigrations = count(pendingMigrations($db));

    $_hdrUnreadMsgs = (int) $db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn();
    $pendingOrders  = (int) $db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
    $lowStockStmt   = $db->prepare("SELECT COUNT(*) FROM products WHERE stock <= ? AND is_active=1");
    $lowStockStmt->execute([(int) setting('low_stock_threshold')]);
    $lowStockCount  = (int) $lowStockStmt->fetchColumn();

    // -- build notification dropdown items --
    foreach ($db->query("SELECT id,order_number,shipping_name,total,created_at FROM orders WHERE status='pending' ORDER BY created_at DESC LIMIT 3")->fetchAll() as $o) {
        $_hdrNotifItems[] = ['icon'=>'ri-shopping-bag-3-line','cls'=>'text-neon-blue bg-neon-blue/10',
            'title'=>'Order #'.e($o['order_number']),
            'sub'=>e($o['shipping_name']).' · '.formatPrice($o['total']),
            'time'=>$o['created_at'],
            'href'=>$basePath.'/admin/order-view.php?id='.(int)$o['id']];
    }
    foreach ($db->query("SELECT id,name,subject,created_at FROM contact_messages WHERE is_read=0 ORDER BY created_at DESC LIMIT 3")->fetchAll() as $m) {
        $_hdrNotifItems[] = ['icon'=>'ri-message-3-line','cls'=>'text-neon-purple bg-neon-purple/10',
            'title'=>'Message from '.e($m['name']),
            'sub'=>e($m['subject']),
            'time'=>$m['created_at'],
            'href'=>$basePath.'/admin/messages.php?view='.(int)$m['id']];
    }
    usort($_hdrNotifItems, fn($a,$b)=>strtotime($b['time'])-strtotime($a['time']));
    $_hdrNotifItems = array_slice($_hdrNotifItems, 0, 6);
    if ($lowStockCount > 0) {
        array_unshift($_hdrNotifItems, ['icon'=>'ri-error-warning-line','cls'=>'text-yellow-400 bg-yellow-500/10',
            'title'=>$lowStockCount.' product'.($lowStockCount>1?'s':'').' low on stock',
            'sub'=>'Review inventory','time'=>null,
            'href'=>$basePath.'/admin/products.php']);
    }
} catch (Exception $e) {}

$_hdrNotifTotal = $_hdrUnreadMsgs + $pendingOrders + ($lowStockCount > 0 ? 1 : 0);

/* ------------------------------------------------------------------ */
/*  Sidebar navigation config                                        */
/* ------------------------------------------------------------------ */
$navGroups = [
    'MAIN' => [
        ['href'=>$basePath.'/admin/',                'icon'=>'ri-dashboard-3-line',    'label'=>'Dashboard',     'page'=>'index'],
        ['href'=>$basePath.'/admin/analytics.php',   'icon'=>'ri-line-chart-line',     'label'=>'Analytics',     'page'=>'analytics'],
    ],
    'STORE' => [
        ['href'=>$basePath.'/admin/orders.php',      'icon'=>'ri-shopping-bag-3-line', 'label'=>'Orders',        'page'=>'orders',       'badge'=>$pendingOrders],
        ['href'=>$basePath.'/admin/products.php',    'icon'=>'ri-headphone-line',      'label'=>'Products',      'page'=>'products'],
        ['href'=>$basePath.'/admin/payments.php',    'icon'=>'ri-bank-card-line',      'label'=>'Payments',      'page'=>'payments'],
        ['href'=>$basePath.'/admin/coupons.php',     'icon'=>'ri-coupon-3-line',       'label'=>'Coupons',       'page'=>'coupons'],
    ],
    'CUSTOMERS' => [
        ['href'=>$basePath.'/admin/customers.php',   'icon'=>'ri-group-line',          'label'=>'Customers',     'page'=>'customers'],
        ['href'=>$basePath.'/admin/newsletter.php',  'icon'=>'ri-mail-send-line',      'label'=>'Newsletter',    'page'=>'newsletter'],
        ['href'=>$basePath.'/admin/testimonials.php','icon'=>'ri-chat-quote-line',     'label'=>'Testimonials',  'page'=>'testimonials'],
    ],
    'SUPPORT' => [
        ['href'=>$basePath.'/admin/messages.php',    'icon'=>'ri-message-3-line',      'label'=>'Messages',      'page'=>'messages',        'badge'=>$_hdrUnreadMsgs],
        ['href'=>$basePath.'/admin/notifications.php','icon'=>'ri-notification-3-line','label'=>'Notifications', 'page'=>'notifications',   'badge'=>$_hdrNotifTotal],
    ],
    'SYSTEM' => [
        ['href'=>$basePath.'/admin/profile.php',     'icon'=>'ri-user-settings-line',  'label'=>'My Profile',    'page'=>'profile'],
        ['href'=>$basePath.'/admin/settings.php',    'icon'=>'ri-settings-3-line',     'label'=>'Settings',      'page'=>'settings'],
    ],
];

function adminTimeAgo(?string $dt): string {
    if (!$dt) return '';
    $diff = time() - strtotime($dt);
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff/60).'m ago';
    if ($diff < 86400) return floor($diff/3600).'h ago';
    if ($diff < 604800) return floor($diff/86400).'d ago';
    return date('M j', strtotime($dt));
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminPageTitle) ?> — Admin · <?= e(storeName()) ?></title>
    <meta name="robots" content="noindex, nofollow">


    <!-- Icons + Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Scrollbar */
        ::-webkit-scrollbar{width:5px;height:5px}
        ::-webkit-scrollbar-track{background:transparent}
        ::-webkit-scrollbar-thumb{background:rgba(0,212,255,.15);border-radius:99px}
        ::-webkit-scrollbar-thumb:hover{background:rgba(0,212,255,.3)}

        /* Sidebar active item */
        .nav-active{background:rgba(0,212,255,.08);color:#00d4ff}
        .nav-active .nav-icon{color:#00d4ff}

        /* Dropdown animation */
        .dd-panel{opacity:0;transform:translateY(-6px);pointer-events:none;transition:opacity .15s ease,transform .15s ease}
        .dd-panel.open{opacity:1;transform:translateY(0);pointer-events:auto}

        /* Print helper */
        @media print{aside,header,.no-print{display:none!important}.flex-1{margin-left:0!important}}
    </style>
    <link rel="stylesheet" href="<?= e(assetUrl('assets/css/tailwind.css')) ?>">
</head>
<body class="bg-dark-950 text-gray-300 font-sans antialiased min-h-screen flex">

<!-- ========================================
     SIDEBAR
======================================== -->
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-50 w-[260px] bg-dark-900/95 backdrop-blur-xl border-r border-white/[.06]
              flex flex-col transition-transform duration-300 ease-out -translate-x-full lg:translate-x-0">

    <!-- Brand -->
    <div class="flex items-center gap-3 px-5 h-[65px] border-b border-white/[.06] flex-shrink-0">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-neon-blue/20 to-neon-purple/20
                    border border-neon-blue/20 flex items-center justify-center">
            <i class="ri-headphone-fill text-neon-blue text-lg"></i>
        </div>
        <div class="flex-1 min-w-0">
            <div class="font-display font-bold text-white text-[15px] leading-tight tracking-tight"><?= e(storeName()) ?></div>
            <div class="text-[10px] text-gray-500 font-medium uppercase tracking-[.18em]">Admin Panel</div>
        </div>
        <button onclick="closeSidebar()" class="lg:hidden w-7 h-7 rounded-lg flex items-center justify-center
                       text-gray-500 hover:text-white hover:bg-white/5 transition-colors">
            <i class="ri-close-line text-lg"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        <?php foreach ($navGroups as $groupLabel => $items): ?>
        <div>
            <div class="px-3 mb-2 text-[10px] font-bold text-gray-600 uppercase tracking-[.2em]"><?= $groupLabel ?></div>
            <div class="space-y-0.5">
                <?php foreach ($items as $item):
                    $isActive = $currentPage === $item['page']
                        || ($item['page'] === 'orders'   && $currentPage === 'order-view')
                        || ($item['page'] === 'products' && $currentPage === 'product-edit');
                ?>
                <a href="<?= e($item['href']) ?>"
                   class="group flex items-center gap-3 px-3 py-[9px] rounded-xl text-[13px] font-medium transition-all duration-150
                          <?= $isActive ? 'nav-active' : 'text-gray-400 hover:text-white hover:bg-white/[.04]' ?>">
                    <i class="nav-icon <?= $item['icon'] ?> text-[17px] w-5 text-center flex-shrink-0
                              <?= $isActive ? '' : 'text-gray-500 group-hover:text-gray-300' ?> transition-colors"></i>
                    <span class="flex-1 truncate"><?= e($item['label']) ?></span>
                    <?php if (!empty($item['badge'])): ?>
                    <span class="min-w-[20px] h-5 bg-neon-blue text-dark-950 text-[10px] font-bold rounded-full
                                 flex items-center justify-center px-1.5 flex-shrink-0 leading-none">
                        <?= (int) $item['badge'] ?>
                    </span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom profile card -->
    <div class="px-3 py-3 border-t border-white/[.06] flex-shrink-0">
        <a href="<?= e($basePath) ?>/admin/profile.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/[.04] transition-all group">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-neon-blue/25 to-neon-purple/25
                        border border-white/10 flex items-center justify-center
                        text-neon-blue text-[13px] font-bold flex-shrink-0">
                <?= strtoupper(substr(currentUserName(), 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[13px] font-semibold text-white truncate"><?= e(currentUserName()) ?></div>
                <div class="text-[10px] text-gray-500 font-medium">Administrator</div>
            </div>
            <i class="ri-more-2-fill text-gray-600 group-hover:text-gray-400 transition-colors text-sm rotate-90"></i>
        </a>
        <div class="flex gap-1.5 mt-2">
            <a href="<?= e($basePath) ?>/" target="_blank"
               class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl text-[11px] font-medium
                      text-gray-500 hover:text-white hover:bg-white/[.04] border border-white/[.04] transition-all">
                <i class="ri-store-2-line text-xs"></i> Visit Store
            </a>
            <a href="<?= e($basePath) ?>/auth/logout.php"
               class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl text-[11px] font-medium
                      text-red-400/70 hover:text-red-400 hover:bg-red-500/[.06] border border-white/[.04] transition-all">
                <i class="ri-logout-box-r-line text-xs"></i> Logout
            </a>
        </div>
    </div>
</aside>

<!-- Mobile overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden lg:hidden" onclick="closeSidebar()"></div>

<!-- ========================================
     MAIN LAYOUT
======================================== -->
<div class="flex-1 lg:ml-[260px] flex flex-col min-h-screen">

    <!-- Top bar -->
    <header class="sticky top-0 z-30 h-[65px] bg-dark-950/80 backdrop-blur-xl border-b border-white/[.06]
                   flex items-center gap-3 px-4 sm:px-6 flex-shrink-0">

        <!-- Mobile hamburger -->
        <button onclick="openSidebar()"
                class="lg:hidden w-9 h-9 rounded-xl bg-white/[.04] border border-white/[.06]
                       flex items-center justify-center text-gray-400 hover:text-white transition-colors flex-shrink-0">
            <i class="ri-menu-2-line text-lg"></i>
        </button>

        <!-- Page title + breadcrumb -->
        <div class="flex items-center gap-2 min-w-0">
            <h1 class="font-display font-semibold text-white text-sm sm:text-base truncate"><?= e($adminPageTitle) ?></h1>
        </div>

        <div class="flex-1"></div>

        <!-- Search toggle (visual) -->
        <div class="hidden md:flex items-center gap-2 bg-white/[.03] border border-white/[.06] rounded-xl px-3 py-2 w-52 lg:w-64
                    text-[12px] text-gray-500 cursor-pointer hover:border-white/10 transition-colors"
             onclick="document.getElementById('searchModal').classList.remove('hidden')">
            <i class="ri-search-line text-sm"></i>
            <span>Search…</span>
            <kbd class="ml-auto text-[10px] bg-white/[.06] px-1.5 py-0.5 rounded text-gray-600">⌘K</kbd>
        </div>

        <!-- Right actions -->
        <div class="flex items-center gap-1.5">

            <!-- Visit store -->
            <a href="<?= e($basePath) ?>/" target="_blank"
               class="hidden lg:flex w-9 h-9 rounded-xl bg-white/[.04] border border-white/[.06]
                      items-center justify-center text-gray-400 hover:text-white transition-colors"
               title="Visit Store">
                <i class="ri-store-2-line text-[15px]"></i>
            </a>

            <!-- Notification bell -->
            <div class="relative" id="notif-wrap">
                <button onclick="toggleDD('notif')"
                        class="relative w-9 h-9 rounded-xl bg-white/[.04] border border-white/[.06]
                               flex items-center justify-center text-gray-400 hover:text-white transition-colors">
                    <i class="ri-notification-3-line text-[15px]"></i>
                    <?php if ($_hdrNotifTotal > 0): ?>
                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-neon-blue text-dark-950
                                 text-[9px] font-bold rounded-full flex items-center justify-center px-1 leading-none ring-2 ring-dark-950">
                        <?= min($_hdrNotifTotal, 99) ?>
                    </span>
                    <?php endif; ?>
                </button>

                <!-- Notification dropdown -->
                <div id="notif-dd" class="dd-panel absolute right-0 top-[calc(100%+8px)] w-[340px] max-w-[calc(100vw-2rem)]
                                         bg-dark-900 border border-white/10 rounded-2xl shadow-2xl shadow-black/40 overflow-hidden z-50">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-white/[.06]">
                        <span class="font-semibold text-white text-sm">Notifications</span>
                        <?php if ($_hdrNotifTotal > 0): ?>
                        <span class="text-[11px] bg-neon-blue/10 text-neon-blue px-2 py-0.5 rounded-full font-medium"><?= $_hdrNotifTotal ?> new</span>
                        <?php endif; ?>
                    </div>
                    <div class="divide-y divide-white/[.04] max-h-80 overflow-y-auto">
                        <?php if (empty($_hdrNotifItems)): ?>
                        <div class="px-4 py-8 text-center text-sm text-gray-600">
                            <i class="ri-notification-off-line text-3xl block mb-2 text-gray-700"></i>
                            All caught up!
                        </div>
                        <?php else: foreach ($_hdrNotifItems as $ni): ?>
                        <a href="<?= e($ni['href']) ?>"
                           class="flex items-start gap-3 px-4 py-3 hover:bg-white/[.02] transition-colors">
                            <span class="mt-0.5 w-8 h-8 rounded-lg <?= $ni['cls'] ?> flex items-center justify-center flex-shrink-0 text-sm">
                                <i class="<?= $ni['icon'] ?>"></i>
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-[12px] font-medium text-white truncate"><?= $ni['title'] ?></p>
                                <p class="text-[11px] text-gray-500 truncate"><?= $ni['sub'] ?></p>
                            </div>
                            <?php if ($ni['time']): ?>
                            <span class="text-[10px] text-gray-600 flex-shrink-0 mt-1"><?= adminTimeAgo($ni['time']) ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; endif; ?>
                    </div>
                    <a href="<?= e($basePath) ?>/admin/notifications.php"
                       class="block text-center text-[12px] text-neon-blue hover:text-white font-medium py-2.5 border-t border-white/[.06] transition-colors">
                        View all notifications →
                    </a>
                </div>
            </div>

            <!-- Profile avatar + dropdown -->
            <div class="relative" id="profile-wrap">
                <button onclick="toggleDD('profile')"
                        class="flex items-center gap-2 pl-1.5 pr-1 sm:pr-2.5 py-1.5 rounded-xl hover:bg-white/[.04] transition-colors">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-neon-blue/25 to-neon-purple/25
                                border border-white/10 flex items-center justify-center
                                text-neon-blue text-[12px] font-bold select-none">
                        <?= strtoupper(substr(currentUserName(), 0, 1)) ?>
                    </div>
                    <span class="hidden sm:block text-[12px] font-medium text-gray-300 max-w-[90px] truncate"><?= e(currentUserName()) ?></span>
                    <i class="ri-arrow-down-s-line text-gray-500 text-sm hidden sm:block"></i>
                </button>

                <div id="profile-dd" class="dd-panel absolute right-0 top-[calc(100%+8px)] w-56 bg-dark-900 border border-white/10 rounded-2xl shadow-2xl shadow-black/40 overflow-hidden z-50">
                    <div class="px-4 py-3 border-b border-white/[.06]">
                        <div class="font-semibold text-white text-sm truncate"><?= e(currentUserName()) ?></div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Administrator</div>
                    </div>
                    <div class="p-1.5 space-y-0.5">
                        <a href="<?= e($basePath) ?>/admin/profile.php"   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[12px] font-medium text-gray-300 hover:text-white hover:bg-white/[.04] transition-all"><i class="ri-user-settings-line text-sm w-4 text-center"></i>My Profile</a>
                        <a href="<?= e($basePath) ?>/admin/settings.php"  class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[12px] font-medium text-gray-300 hover:text-white hover:bg-white/[.04] transition-all"><i class="ri-settings-3-line text-sm w-4 text-center"></i>Settings</a>
                        <a href="<?= e($basePath) ?>/" target="_blank"    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[12px] font-medium text-gray-300 hover:text-white hover:bg-white/[.04] transition-all"><i class="ri-store-2-line text-sm w-4 text-center"></i>View Store</a>
                        <div class="my-1 border-t border-white/[.06]"></div>
                        <a href="<?= e($basePath) ?>/auth/logout.php"    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[12px] font-medium text-red-400 hover:text-red-300 hover:bg-red-500/[.06] transition-all"><i class="ri-logout-box-r-line text-sm w-4 text-center"></i>Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash message -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="mx-4 sm:mx-6 mt-4 p-3.5 rounded-xl text-sm flex items-center gap-3 animate-[fadeIn_.3s_ease]
                <?= $flash['type']==='success'
                    ? 'bg-green-500/10 border border-green-500/20 text-green-400'
                    : 'bg-red-500/10 border border-red-500/20 text-red-400' ?>">
        <i class="<?= $flash['type']==='success' ? 'ri-checkbox-circle-fill':'ri-error-warning-fill' ?> text-lg flex-shrink-0"></i>
        <span class="flex-1"><?= e($flash['message']) ?></span>
        <button onclick="this.parentElement.remove()" class="ml-auto text-current opacity-50 hover:opacity-100 transition-opacity"><i class="ri-close-line"></i></button>
    </div>
    <?php endif; ?>

    <?php if ($_hdrDefaultPassword): ?>
    <!-- Security: default admin password still in use -->
    <div class="mx-4 sm:mx-6 mt-4 p-3.5 rounded-xl text-sm flex flex-wrap items-center gap-3 bg-red-500/10 border border-red-500/30 text-red-300">
        <i class="ri-shield-keyhole-line text-lg flex-shrink-0"></i>
        <span class="flex-1">You are still using the default admin password from the installation guide. Anyone who reads it can sign in.</span>
        <a href="<?= e($basePath) ?>/admin/profile.php?tab=password" class="px-3 py-1.5 rounded-lg bg-red-500 text-white text-xs font-bold hover:bg-red-400">Change password</a>
    </div>
    <?php endif; ?>

    <?php if ($_hdrPendingMigrations): ?>
    <div class="mx-4 sm:mx-6 mt-4 p-3.5 rounded-xl text-sm flex flex-wrap items-center gap-3 bg-yellow-500/10 border border-yellow-500/20 text-yellow-300">
        <i class="ri-database-2-line text-lg flex-shrink-0"></i>
        <span class="flex-1"><?= $_hdrPendingMigrations ?> database update(s) are waiting. Some features will not work until they are applied.</span>
        <a href="<?= e($basePath) ?>/admin/settings.php?tab=system" class="px-3 py-1.5 rounded-lg bg-yellow-400 text-dark-950 text-xs font-bold hover:opacity-90">Review updates</a>
    </div>
    <?php endif; ?>

    <!-- Page content -->
    <main class="flex-1 p-4 sm:p-6">

<!-- Search modal (keyboard shortcut ⌘K) -->
<div id="searchModal" class="hidden fixed inset-0 z-[60] bg-black/60 backdrop-blur-sm flex items-start justify-center pt-[15vh]">
    <div class="bg-dark-900 border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden mx-4" onclick="event.stopPropagation()">
        <div class="flex items-center gap-3 px-4 py-3 border-b border-white/[.06]">
            <i class="ri-search-line text-gray-500"></i>
            <input id="searchInput" type="text" placeholder="Search orders, products, customers…"
                   class="flex-1 bg-transparent text-sm text-white placeholder-gray-500 outline-none" autofocus>
            <kbd class="text-[10px] bg-white/[.06] px-1.5 py-0.5 rounded text-gray-600 cursor-pointer" onclick="document.getElementById('searchModal').classList.add('hidden')">ESC</kbd>
        </div>
        <div id="searchResults" class="hidden p-3 text-[12px] text-gray-300 space-y-1 border-b border-white/[.06]">
            <a data-target="orders"    class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-white/[.04] bg-white/[.03] transition-colors"><i class="ri-shopping-bag-3-line text-sm text-neon-blue"></i><span>Search orders for “<b></b>”</span><kbd class="ml-auto text-[10px] text-gray-600">Enter</kbd></a>
            <a data-target="products"  class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-white/[.04] transition-colors"><i class="ri-headphone-line text-sm text-neon-purple"></i><span>Search products for “<b></b>”</span></a>
            <a data-target="customers" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-white/[.04] transition-colors"><i class="ri-group-line text-sm text-neon-pink"></i><span>Search customers for “<b></b>”</span></a>
        </div>
        <div class="p-3 text-[12px] text-gray-500 space-y-1">
            <a href="<?= e($basePath) ?>/admin/orders.php"    class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-white/[.04] transition-colors"><i class="ri-shopping-bag-3-line text-sm"></i>Go to Orders</a>
            <a href="<?= e($basePath) ?>/admin/products.php"  class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-white/[.04] transition-colors"><i class="ri-headphone-line text-sm"></i>Go to Products</a>
            <a href="<?= e($basePath) ?>/admin/customers.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-white/[.04] transition-colors"><i class="ri-group-line text-sm"></i>Go to Customers</a>
            <a href="<?= e($basePath) ?>/admin/payments.php"  class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-white/[.04] transition-colors"><i class="ri-bank-card-line text-sm"></i>Go to Payments</a>
            <a href="<?= e($basePath) ?>/admin/settings.php"  class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-white/[.04] transition-colors"><i class="ri-settings-3-line text-sm"></i>Go to Settings</a>
        </div>
    </div>
</div>

<script>
/* Sidebar */
function openSidebar(){document.getElementById('sidebar').classList.remove('-translate-x-full');document.getElementById('sidebar-overlay').classList.remove('hidden')}
function closeSidebar(){document.getElementById('sidebar').classList.add('-translate-x-full');document.getElementById('sidebar-overlay').classList.add('hidden')}

/* Dropdowns */
function toggleDD(name){
    const panel=document.getElementById(name+'-dd');
    const wasOpen=panel.classList.contains('open');
    closeAllDD();
    if(!wasOpen) panel.classList.add('open');
}
function closeAllDD(){document.querySelectorAll('.dd-panel').forEach(p=>p.classList.remove('open'))}
document.addEventListener('click',e=>{
    if(!e.target.closest('#notif-wrap')&&!e.target.closest('#profile-wrap')) closeAllDD();
});

/* Search modal */
const sm=document.getElementById('searchModal');
sm.addEventListener('click',()=>sm.classList.add('hidden'));
document.addEventListener('keydown',e=>{
    if((e.metaKey||e.ctrlKey)&&e.key==='k'){e.preventDefault();sm.classList.toggle('hidden');setTimeout(()=>document.getElementById('searchInput')?.focus(),50)}
    if(e.key==='Escape') sm.classList.add('hidden');
});
/* Search: each result opens that list page filtered by ?q= */
const searchInput=document.getElementById('searchInput');
const searchResults=document.getElementById('searchResults');
const adminBase=<?= jsonForScript($basePath . '/admin/') ?>;
searchInput?.addEventListener('input',()=>{
    const q=searchInput.value.trim();
    searchResults.classList.toggle('hidden',!q);
    searchResults.querySelectorAll('a').forEach(a=>{
        a.querySelector('b').textContent=q;
        a.href=adminBase+a.dataset.target+'.php?q='+encodeURIComponent(q);
    });
});
searchInput?.addEventListener('keydown',e=>{
    if(e.key==='Enter'&&searchInput.value.trim()) window.location.href=searchResults.querySelector('a').href;
});
</script>
