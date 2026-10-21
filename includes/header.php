<?php
/**
 * Headphones1.Duka — Shared Header
 * Includes <head>, navbar, cart drawer, quick-view modal
 * 
 * Variables available before include:
 *   $pageTitle       — (required) Page <title>
 *   $pageDescription — (optional) Meta description
 *   $pageType        — (optional) 'home' | 'page' | 'product' (default: 'page')
 *   $bodyClass       — (optional) Extra classes on <body>
 *   $ogImage         — (optional) OG image path
 *   $canonical       — (optional) Canonical URL
 *   $noIndex         — (optional) true to add noindex
 *   $includeAnimations — (optional) true to load GSAP/Lenis (default: true on home)
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/functions.php';

// Current page for nav highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Visitors see the maintenance page (admins and the sign-in page stay open)
if (!str_ends_with($_SERVER['PHP_SELF'], '/auth/login.php')) {
    maintenanceGate();
}

// Defaults
$pageTitle = $pageTitle ?? storeName();
$pageDescription = $pageDescription ?? setting('store_tagline');
$pageType = $pageType ?? 'page';
$bodyClass = $bodyClass ?? '';
$ogImage = $ogImage ?? 'assets/img/home.png';
$noIndex = $noIndex ?? false;
$includeAnimations = $includeAnimations ?? ($pageType === 'home');

// Determine base URL for assets (handles subdirectory depth)
$basePath = rtrim(APP_URL, '/');

// Canonical URL: explicit, or this page without its query string
$canonical = $canonical ?? absoluteUrl(substr(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), strlen(parse_url(APP_URL, PHP_URL_PATH) ?? '')));

// Get cart count for badge
try {
    $db = getDB();
    $cartCount = getCartCount($db);
} catch (Exception $e) {
    $cartCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <?php if ($noIndex): ?>
    <meta name="robots" content="noindex">
    <?php endif; ?>
    <meta name="theme-color" content="#050507">

    <!-- Open Graph -->
    <meta property="og:site_name" content="<?= e(storeName()) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:image" content="<?= e(absoluteUrl($ogImage)) ?>">
    <meta property="og:type" content="<?= $pageType === 'product' ? 'product' : 'website' ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e(absoluteUrl($ogImage)) ?>">

    <?php if (!$noIndex): ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= e($basePath) ?>/assets/img/favicon.png" type="image/x-icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= e(assetUrl('assets/css/styles.css')) ?>">

    <!-- CSRF Token + store config for JS -->
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <meta name="cart-api" content="<?= e($basePath) ?>/api/cart.php">
    <script>window.STORE_CURRENCY = <?= jsonForScript(currencyConfig()) ?>;</script>

    <!-- Compiled Tailwind (npm run build:css) — loaded last, like the old CDN build -->
    <link rel="stylesheet" href="<?= e(assetUrl('assets/css/tailwind.css')) ?>">

    <title><?= e($pageTitle) ?></title>
</head>
<body class="bg-dark-950 text-gray-300 font-sans overflow-x-hidden <?= e($bodyClass) ?>">

    <?php
    // Flash message display
    $flash = getFlash();
    if ($flash): ?>
    <div id="flash-message" class="fixed top-20 left-1/2 -translate-x-1/2 z-[200] px-6 py-3 rounded-2xl text-sm font-medium backdrop-blur-xl border transition-all duration-500
        <?= $flash['type'] === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-400' : '' ?>
        <?= $flash['type'] === 'error' ? 'bg-red-500/10 border-red-500/20 text-red-400' : '' ?>
        <?= $flash['type'] === 'info' ? 'bg-neon-blue/10 border-neon-blue/20 text-neon-blue' : '' ?>">
        <?= e($flash['message']) ?>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('flash-message');
            if (el) { el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }
        }, 4000);
    </script>
    <?php endif; ?>

    <!-- Scroll Progress Bar -->
    <div id="scroll-progress" class="fixed top-0 left-0 h-[3px] bg-gradient-to-r from-neon-blue via-neon-purple to-neon-pink z-[200] transition-all" style="width: 0%"></div>

    <!-- ============ HEADER / NAVBAR ============ -->
    <header id="header" class="fixed top-0 left-0 w-full z-[150] transition-all duration-500">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16 lg:h-20 transition-all duration-500">
            <!-- Logo -->
            <a href="<?= e($basePath) ?>/" class="flex items-center gap-2 z-50">
                <img src="<?= e($basePath) ?>/assets/img/logo.png" alt="<?= e(storeName()) ?>" class="h-8 lg:h-10" width="40" height="40">
            </a>

            <!-- Desktop Nav -->
            <div id="nav-menu" class="nav-menu fixed top-0 left-0 w-full h-screen bg-dark-950/98 backdrop-blur-xl flex flex-col items-center justify-center gap-8 z-40 transition-all duration-500 opacity-0 pointer-events-none lg:static lg:w-auto lg:h-auto lg:bg-transparent lg:flex-row lg:opacity-100 lg:pointer-events-auto lg:backdrop-blur-none">
                <ul class="flex flex-col lg:flex-row items-center gap-6 lg:gap-8">
                    <?php if ($pageType === 'home'): ?>
                    <li><a href="#home" class="nav-link text-lg lg:text-sm font-medium uppercase tracking-widest text-gray-400 hover:text-white transition-colors relative" data-section="home">Home</a></li>
                    <li><a href="#specs" class="nav-link text-lg lg:text-sm font-medium uppercase tracking-widest text-gray-400 hover:text-white transition-colors relative" data-section="specs">Specs</a></li>
                    <li><a href="#case" class="nav-link text-lg lg:text-sm font-medium uppercase tracking-widest text-gray-400 hover:text-white transition-colors relative" data-section="case">Case</a></li>
                    <li><a href="#products" class="nav-link text-lg lg:text-sm font-medium uppercase tracking-widest text-gray-400 hover:text-white transition-colors relative" data-section="products">Products</a></li>
                    <li><a href="#testimonials" class="nav-link text-lg lg:text-sm font-medium uppercase tracking-widest text-gray-400 hover:text-white transition-colors relative" data-section="testimonials">Reviews</a></li>
                    <li><a href="#faq" class="nav-link text-lg lg:text-sm font-medium uppercase tracking-widest text-gray-400 hover:text-white transition-colors relative" data-section="faq">FAQ</a></li>
                    <?php else: ?>
                    <li><a href="<?= e($basePath) ?>/" class="nav-link text-lg lg:text-sm font-medium uppercase tracking-widest text-gray-400 hover:text-white transition-colors relative <?= $currentPage === 'index' ? 'active' : '' ?>">Home</a></li>
                    <li><a href="<?= e($basePath) ?>/shop.php" class="nav-link text-lg lg:text-sm font-medium uppercase tracking-widest text-gray-400 hover:text-white transition-colors relative <?= $currentPage === 'shop' ? 'active' : '' ?>">Shop</a></li>
                    <li><a href="<?= e($basePath) ?>/contact.php" class="nav-link text-lg lg:text-sm font-medium uppercase tracking-widest text-gray-400 hover:text-white transition-colors relative <?= $currentPage === 'contact' ? 'active' : '' ?>">Contact</a></li>
                    <li><a href="<?= e($basePath) ?>/#faq" class="nav-link text-lg lg:text-sm font-medium uppercase tracking-widest text-gray-400 hover:text-white transition-colors relative">FAQ</a></li>
                    <?php endif; ?>
                </ul>
                <!-- Mobile close -->
                <button id="nav-close" class="absolute top-6 right-6 text-3xl text-white lg:hidden"><i class="ri-close-line"></i></button>
            </div>

            <!-- Right side: Cart + Auth + Mobile toggle -->
            <div class="flex items-center gap-4 z-50">
                <?php if (isLoggedIn()): ?>
                <div class="hidden sm:block relative" id="user-dropdown-wrap">
                    <button id="user-dropdown-btn" class="flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
                        <i class="ri-user-line"></i> <?= e(currentUserName()) ?> <i class="ri-arrow-down-s-line text-xs"></i>
                    </button>
                    <div id="user-dropdown" class="absolute right-0 top-full mt-2 w-48 bg-dark-900/95 backdrop-blur-xl border border-white/[.06] rounded-xl py-2 shadow-2xl opacity-0 pointer-events-none transition-all duration-200 translate-y-1">
                        <a href="<?= e($basePath) ?>/account/" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-white/[.04] transition-colors"><i class="ri-dashboard-line"></i> Dashboard</a>
                        <a href="<?= e($basePath) ?>/account/orders.php" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-white/[.04] transition-colors"><i class="ri-shopping-bag-line"></i> My Orders</a>
                        <a href="<?= e($basePath) ?>/account/wishlist.php" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-white/[.04] transition-colors"><i class="ri-heart-line"></i> Wishlist</a>
                        <a href="<?= e($basePath) ?>/account/profile.php" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-white/[.04] transition-colors"><i class="ri-settings-3-line"></i> Settings</a>
                        <div class="border-t border-white/[.06] my-1"></div>
                        <a href="<?= e($basePath) ?>/auth/logout.php" class="flex items-center gap-2.5 px-4 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-white/[.04] transition-colors"><i class="ri-logout-box-r-line"></i> Sign Out</a>
                    </div>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const btn = document.getElementById('user-dropdown-btn');
                    const dd = document.getElementById('user-dropdown');
                    if (btn && dd) {
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const open = dd.classList.contains('pointer-events-none');
                            dd.classList.toggle('opacity-0', !open);
                            dd.classList.toggle('pointer-events-none', !open);
                            dd.classList.toggle('translate-y-1', !open);
                            dd.classList.toggle('translate-y-0', open);
                        });
                        document.addEventListener('click', function() {
                            dd.classList.add('opacity-0', 'pointer-events-none', 'translate-y-1');
                            dd.classList.remove('translate-y-0');
                        });
                    }
                });
                </script>
                <?php else: ?>
                <a href="<?= e($basePath) ?>/auth/login.php" class="hidden sm:flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
                    <i class="ri-user-line"></i> Sign In
                </a>
                <?php endif; ?>

                <!-- Cart Button -->
                <button id="cart-toggle" class="relative group" aria-label="Open cart">
                    <i class="ri-shopping-bag-3-line text-xl text-gray-300 group-hover:text-neon-blue transition-colors"></i>
                    <span id="cart-count" class="absolute -top-2 -right-2 w-5 h-5 bg-neon-blue text-dark-950 text-xs font-bold rounded-full flex items-center justify-center <?= $cartCount > 0 ? 'opacity-100 scale-100' : 'opacity-0 scale-0' ?> transition-all"><?= $cartCount ?></span>
                </button>
                <!-- Mobile Toggle -->
                <button id="nav-toggle" class="text-2xl text-white lg:hidden" aria-label="Open menu"><i class="ri-menu-4-line"></i></button>
            </div>
        </nav>
    </header>

    <!-- ============ CART DRAWER ============ -->
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
            <p class="text-center text-xs text-gray-500 mt-3"><?= e(shippingSummary()) ?></p>
        </div>
    </aside>

    <!-- ============ QUICK VIEW MODAL ============ -->
    <div id="quickview-overlay" class="fixed inset-0 bg-black/70 backdrop-blur-md z-[180] opacity-0 pointer-events-none transition-opacity duration-300"></div>
    <div id="quickview-modal" class="fixed inset-0 z-[190] flex items-center justify-center p-4 opacity-0 pointer-events-none transition-all duration-500 scale-95" role="dialog" aria-label="Product quick view">
        <div class="bg-dark-800/95 backdrop-blur-2xl rounded-3xl border border-white/10 max-w-3xl w-full max-h-[90vh] overflow-y-auto p-8 relative">
            <button id="quickview-close" class="absolute top-4 right-4 text-2xl text-gray-400 hover:text-white transition-colors z-10"><i class="ri-close-line"></i></button>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="flex items-center justify-center bg-gradient-to-br from-dark-900 to-dark-800 rounded-2xl p-8">
                    <img id="qv-image" src="" alt="Product" class="w-full max-w-[280px] drop-shadow-2xl">
                </div>
                <div class="flex flex-col justify-center">
                    <span class="text-neon-blue text-sm font-medium uppercase tracking-widest mb-2">Quick View</span>
                    <h3 id="qv-title" class="text-3xl font-display font-bold text-white mb-2"></h3>
                    <p id="qv-price" class="text-2xl font-bold text-white mb-4"></p>
                    <div class="space-y-3 mb-6 text-sm text-gray-400">
                        <div class="flex items-center gap-3"><i class="ri-bluetooth-line text-neon-blue"></i> Bluetooth 5.2</div>
                        <div class="flex items-center gap-3"><i class="ri-battery-charge-line text-neon-blue"></i> 40h Battery Life</div>
                        <div class="flex items-center gap-3"><i class="ri-mic-line text-neon-blue"></i> Built-in Microphone</div>
                        <?php if (warrantyText()): ?>
                        <div class="flex items-center gap-3"><i class="ri-shield-check-line text-neon-blue"></i> <?= e(ucfirst(warrantyText())) ?> Warranty</div>
                        <?php endif; ?>
                        <div class="flex items-center gap-3"><i class="ri-truck-line text-neon-blue"></i> <?= e(shippingSummary()) ?></div>
                    </div>
                    <!-- Color swatches in modal -->
                    <div class="mb-6">
                        <p class="text-sm text-gray-400 mb-3">Color: <span id="qv-color-name" class="text-white"></span></p>
                        <div id="qv-colors" class="flex gap-3"></div>
                    </div>
                    <button id="qv-add-btn" class="w-full py-4 bg-gradient-to-r from-neon-blue to-neon-purple text-dark-950 font-bold rounded-2xl hover:scale-[1.02] transition-transform flex items-center justify-center gap-2" data-id="" data-name="" data-price="" data-img="">
                        <i class="ri-shopping-bag-line"></i> Add to Cart
                    </button>
                    <a id="qv-link" href="<?= e($basePath) ?>/shop.php" class="mt-3 text-center text-sm text-gray-400 hover:text-neon-blue transition-colors">View full details <i class="ri-arrow-right-line"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Live region for accessibility announcements -->
    <div id="aria-live-region" class="sr-only" aria-live="polite" aria-atomic="true"></div>

    <main>
