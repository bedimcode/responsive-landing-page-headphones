<?php
/**
 * User Dashboard — Shared Header, Sidebar & Layout
 * Included at the top of every account page.
 * Set $accountPageTitle before including.
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__DIR__)));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/functions.php';

requireAuth();

$accountPageTitle = $accountPageTitle ?? 'My Account';
$basePath         = rtrim(APP_URL, '/');
$currentPage      = basename($_SERVER['PHP_SELF'], '.php');

/* ------------------------------------------------------------------ */
/*  Dashboard counts                                                   */
/* ------------------------------------------------------------------ */
$_acctOrderCount = $wishlistCount = $addressCount = 0;
try {
    $db = getDB();
    $uid = currentUserId();
    $_acctOrderCount = getUserOrderCount($db, $uid);
    $wishlistCount   = getWishlistCount($db, $uid);
    $stmt = $db->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id = ?');
    $stmt->execute([$uid]);
    $addressCount = (int) $stmt->fetchColumn();
} catch (Exception $e) {}

/* ------------------------------------------------------------------ */
/*  Sidebar navigation config                                         */
/* ------------------------------------------------------------------ */
$accountNav = [
    'OVERVIEW' => [
        ['href' => $basePath . '/account/',              'icon' => 'ri-dashboard-3-line',   'label' => 'Dashboard',   'page' => 'index'],
    ],
    'SHOPPING' => [
        ['href' => $basePath . '/shop.php',              'icon' => 'ri-store-2-line',       'label' => 'Shop',        'page' => 'shop'],
        ['href' => $basePath . '/account/orders.php',    'icon' => 'ri-shopping-bag-3-line','label' => 'My Orders',   'page' => 'orders',    'badge' => $_acctOrderCount],
        ['href' => $basePath . '/account/wishlist.php',  'icon' => 'ri-heart-3-line',       'label' => 'Wishlist',    'page' => 'wishlist',  'badge' => $wishlistCount],
    ],
    'ACCOUNT' => [
        ['href' => $basePath . '/account/addresses.php', 'icon' => 'ri-map-pin-line',       'label' => 'Addresses',   'page' => 'addresses'],
        ['href' => $basePath . '/account/profile.php',   'icon' => 'ri-user-settings-line', 'label' => 'Profile',     'page' => 'profile'],
    ],
];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($accountPageTitle) ?> — My Account · <?= e(APP_NAME) ?></title>
    <meta name="robots" content="noindex, nofollow">

    <!-- CSRF Token & Cart API for JS -->
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <meta name="cart-api" content="<?= e($basePath) ?>/api/cart.php">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    dark:{50:'#f5f5f6',100:'#e5e5e8',200:'#cdcdd2',300:'#aaabb3',400:'#81828e',
                          500:'#666773',600:'#565762',700:'#494a53',800:'#141418',900:'#0a0a0d',950:'#050507'},
                    neon:{blue:'#00d4ff',purple:'#a855f7',pink:'#ec4899',cyan:'#06b6d4'}
                },
                fontFamily:{sans:['Inter','system-ui','sans-serif'],display:['Space Grotesk','sans-serif']}
            }
        }
    }
    </script>

    <!-- Icons + Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        ::-webkit-scrollbar{width:5px;height:5px}
        ::-webkit-scrollbar-track{background:transparent}
        ::-webkit-scrollbar-thumb{background:rgba(0,212,255,.15);border-radius:99px}
        ::-webkit-scrollbar-thumb:hover{background:rgba(0,212,255,.3)}
        .nav-active{background:rgba(0,212,255,.08);color:#00d4ff}
        .nav-active .nav-icon{color:#00d4ff}
        .dd-panel{opacity:0;transform:translateY(-6px);pointer-events:none;transition:opacity .15s ease,transform .15s ease}
        .dd-panel.open{opacity:1;transform:translateY(0);pointer-events:auto}
        @media print{aside,header,.no-print{display:none!important}.flex-1{margin-left:0!important}}
        /* Cart Drawer */
        #cart-drawer.open{transform:translateX(0)}
        #cart-overlay.open{opacity:1;pointer-events:all}
        .cart-item{padding:1rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05);border-radius:1rem;display:flex;gap:1rem;align-items:center;animation:cartItemIn .4s cubic-bezier(.25,1,.5,1)}
        .cart-item img{width:60px;height:60px;object-fit:contain}
        .cart-item-info{flex:1}
        .cart-item-name{color:#fff;font-weight:500;font-size:.875rem;margin-bottom:.25rem}
        .cart-item-price{color:#00d4ff;font-weight:700;font-size:.875rem}
        .cart-item-qty{display:flex;align-items:center;gap:.5rem;margin-top:.5rem}
        .cart-item-qty button{width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#fff;font-size:.875rem;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s}
        .cart-item-qty button:hover{background:rgba(0,212,255,.15);border-color:rgba(0,212,255,.3)}
        .cart-item-qty span{color:#fff;font-weight:600;font-size:.875rem;min-width:1.5rem;text-align:center}
        .cart-item-remove{color:rgba(255,255,255,.3);cursor:pointer;transition:color .2s;font-size:1.25rem;background:none;border:none;padding:.25rem}
        .cart-item-remove:hover{color:#ef4444}
        @keyframes cartItemIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
        .toast{position:fixed;bottom:2rem;right:2rem;padding:1rem 1.5rem;background:rgba(10,10,13,.95);backdrop-filter:blur(24px);border:1px solid rgba(0,212,255,.2);border-radius:1rem;color:#fff;font-size:.875rem;font-weight:500;z-index:999;display:flex;align-items:center;gap:.75rem;transform:translateX(120%);transition:transform .5s cubic-bezier(.25,1,.5,1);box-shadow:0 10px 40px rgba(0,0,0,.4)}
        .toast.show{transform:translateX(0)}
        .toast i{color:#00d4ff;font-size:1.25rem}
    </style>
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
        <a href="<?= e($basePath) ?>/" class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-neon-blue/20 to-neon-purple/20
                        border border-neon-blue/20 flex items-center justify-center">
                <i class="ri-headphone-fill text-neon-blue text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-display font-bold text-white text-[15px] leading-tight tracking-tight">Beats Audio</div>
                <div class="text-[10px] text-gray-500 font-medium uppercase tracking-[.18em]">My Account</div>
            </div>
        </a>
        <button onclick="closeSidebar()" class="lg:hidden w-7 h-7 rounded-lg flex items-center justify-center
                       text-gray-500 hover:text-white hover:bg-white/5 transition-colors">
            <i class="ri-close-line text-lg"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        <?php foreach ($accountNav as $groupLabel => $items): ?>
        <div>
            <div class="px-3 mb-2 text-[10px] font-bold text-gray-600 uppercase tracking-[.2em]"><?= $groupLabel ?></div>
            <div class="space-y-0.5">
                <?php foreach ($items as $item):
                    $isActive = $currentPage === $item['page']
                        || ($item['page'] === 'orders' && $currentPage === 'order-view');
                ?>
                <a href="<?= e($item['href']) ?>"
                   class="group flex items-center gap-3 px-3 py-[9px] rounded-xl text-[13px] font-medium transition-all duration-150
                          <?= $isActive ? 'nav-active' : 'text-gray-400 hover:text-white hover:bg-white/[.04]' ?>">
                    <i class="nav-icon <?= $item['icon'] ?> text-[17px] w-5 text-center flex-shrink-0
                              <?= $isActive ? '' : 'text-gray-500 group-hover:text-gray-300' ?> transition-colors"></i>
                    <span class="flex-1 truncate"><?= e($item['label']) ?></span>
                    <?php if (!empty($item['badge'])): ?>
                    <span class="min-w-[20px] h-5 bg-white/[.06] text-gray-400 text-[10px] font-bold rounded-full
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
        <a href="<?= e($basePath) ?>/account/profile.php"
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/[.04] transition-all group">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-neon-blue/25 to-neon-purple/25
                        border border-white/10 flex items-center justify-center
                        text-neon-blue text-[13px] font-bold flex-shrink-0">
                <?= strtoupper(substr(currentUserName(), 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[13px] font-semibold text-white truncate"><?= e(currentUserName()) ?></div>
                <div class="text-[10px] text-gray-500 font-medium">Customer</div>
            </div>
            <i class="ri-more-2-fill text-gray-600 group-hover:text-gray-400 transition-colors text-sm rotate-90"></i>
        </a>
        <div class="flex gap-1.5 mt-2">
            <a href="<?= e($basePath) ?>/"
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

        <!-- Page title -->
        <div class="flex items-center gap-2 min-w-0">
            <h1 class="font-display font-semibold text-white text-sm sm:text-base truncate"><?= e($accountPageTitle) ?></h1>
        </div>

        <div class="flex-1"></div>

        <!-- Right actions -->
        <div class="flex items-center gap-1.5">
            <!-- Visit store -->
            <a href="<?= e($basePath) ?>/"
               class="hidden sm:flex w-9 h-9 rounded-xl bg-white/[.04] border border-white/[.06]
                      items-center justify-center text-gray-400 hover:text-white transition-colors"
               title="Visit Store">
                <i class="ri-store-2-line text-[15px]"></i>
            </a>

            <!-- Cart toggle (opens drawer) -->
            <button id="cart-toggle"
               class="relative w-9 h-9 rounded-xl bg-white/[.04] border border-white/[.06]
                      flex items-center justify-center text-gray-400 hover:text-white transition-colors"
               title="Cart" aria-label="Open cart">
                <i class="ri-shopping-cart-2-line text-[15px]"></i>
                <span id="cart-count" class="absolute -top-1 -right-1 w-4 h-4 bg-neon-blue text-dark-950 text-[9px] font-bold rounded-full flex items-center justify-center opacity-0 scale-0 transition-all">0</span>
            </button>

            <!-- Profile dropdown -->
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
                        <div class="text-[11px] text-gray-500 mt-0.5">Customer Account</div>
                    </div>
                    <div class="p-1.5 space-y-0.5">
                        <a href="<?= e($basePath) ?>/account/"            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[12px] font-medium text-gray-300 hover:text-white hover:bg-white/[.04] transition-all"><i class="ri-dashboard-3-line text-sm w-4 text-center"></i>Dashboard</a>
                        <a href="<?= e($basePath) ?>/account/orders.php"  class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[12px] font-medium text-gray-300 hover:text-white hover:bg-white/[.04] transition-all"><i class="ri-shopping-bag-3-line text-sm w-4 text-center"></i>My Orders</a>
                        <a href="<?= e($basePath) ?>/account/wishlist.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[12px] font-medium text-gray-300 hover:text-white hover:bg-white/[.04] transition-all"><i class="ri-heart-3-line text-sm w-4 text-center"></i>Wishlist</a>
                        <a href="<?= e($basePath) ?>/account/profile.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[12px] font-medium text-gray-300 hover:text-white hover:bg-white/[.04] transition-all"><i class="ri-user-settings-line text-sm w-4 text-center"></i>Profile</a>
                        <a href="<?= e($basePath) ?>/"                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[12px] font-medium text-gray-300 hover:text-white hover:bg-white/[.04] transition-all"><i class="ri-store-2-line text-sm w-4 text-center"></i>Visit Store</a>
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

    <!-- Cart Drawer -->
    <div id="cart-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[160] opacity-0 pointer-events-none transition-opacity duration-300"></div>
    <aside id="cart-drawer" class="fixed top-0 right-0 w-full max-w-md h-full bg-dark-900/95 backdrop-blur-2xl border-l border-white/5 z-[170] translate-x-full transition-transform duration-500 ease-out flex flex-col" role="dialog" aria-label="Shopping cart">
        <div class="flex items-center justify-between p-6 border-b border-white/5">
            <h3 class="text-xl font-display font-bold text-white">Your Cart</h3>
            <button id="cart-close" class="text-2xl text-gray-400 hover:text-white transition-colors"><i class="ri-close-line"></i></button>
        </div>
        <div id="cart-items" class="flex-1 overflow-y-auto p-6 space-y-4">
            <p id="cart-empty" class="text-center text-gray-500 py-12">Your cart is empty</p>
        </div>
        <div id="cart-footer" class="p-6 border-t border-white/5 hidden">
            <div class="flex justify-between mb-4">
                <span class="text-gray-400">Subtotal</span>
                <span id="cart-total" class="text-xl font-bold text-white">$0</span>
            </div>
            <a href="<?= e($basePath) ?>/checkout.php" class="block w-full py-4 bg-gradient-to-r from-neon-blue to-neon-purple text-dark-950 font-bold rounded-2xl hover:scale-[1.02] transition-transform text-center">
                Checkout — <span id="cart-total-btn">$0</span>
            </a>
            <p class="text-center text-xs text-gray-500 mt-3">Free shipping on orders over $199</p>
        </div>
    </aside>

    <!-- Page content -->
    <main class="flex-1 p-4 sm:p-6">

<script>
function openSidebar(){document.getElementById('sidebar').classList.remove('-translate-x-full');document.getElementById('sidebar-overlay').classList.remove('hidden')}
function closeSidebar(){document.getElementById('sidebar').classList.add('-translate-x-full');document.getElementById('sidebar-overlay').classList.add('hidden')}
function toggleDD(name){
    const panel=document.getElementById(name+'-dd');
    const wasOpen=panel.classList.contains('open');
    closeAllDD();
    if(!wasOpen) panel.classList.add('open');
}
function closeAllDD(){document.querySelectorAll('.dd-panel').forEach(p=>p.classList.remove('open'))}
document.addEventListener('click',e=>{
    if(!e.target.closest('#profile-wrap')) closeAllDD();
});
document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){
        closeSidebar();
        closeAllDD();
        if(typeof Cart!=='undefined') Cart.closeDrawer();
    }
});
window.addEventListener('resize',()=>{if(window.innerWidth>=1024)closeSidebar()});
</script>
