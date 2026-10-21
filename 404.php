<?php
/**
 * 404 / Error Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$code = isset($_GET['code']) ? (int) $_GET['code'] : 404;
$codes = [
    403 => ['Forbidden', 'You don\'t have permission to access this resource.'],
    404 => ['Page Not Found', 'The page you\'re looking for doesn\'t exist or has been moved.'],
    500 => ['Server Error', 'Something went wrong on our end. Please try again later.'],
];
if (!isset($codes[$code])) $code = 404;
$error = $codes[$code];

http_response_code($code);

$pageTitle = $error[0] . ' — ' . storeName();
$pageDescription = $error[1];
$pageType = 'page';
$noIndex = true;

require_once __DIR__ . '/includes/header.php';
?>

    <main class="pt-32 pb-24 min-h-[70vh] flex items-center">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-8">
                <span class="font-display text-8xl lg:text-[10rem] font-black bg-gradient-to-r from-neon-blue via-neon-purple to-neon-pink bg-clip-text text-transparent"><?= $code ?></span>
            </div>
            <h1 class="font-display text-3xl lg:text-4xl font-bold text-white mb-4"><?= e($error[0]) ?></h1>
            <p class="text-gray-400 text-lg mb-10 max-w-md mx-auto"><?= e($error[1]) ?></p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="<?= e($basePath) ?>/" class="px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-2xl font-bold text-dark-950 text-lg hover:scale-105 transition-transform flex items-center gap-3">
                    <i class="ri-home-line"></i> Back to Home
                </a>
                <a href="<?= e($basePath) ?>/shop.php" class="px-8 py-4 border border-white/10 rounded-2xl font-medium text-gray-300 hover:border-neon-blue/30 hover:text-white transition-all flex items-center gap-3">
                    <i class="ri-store-2-line"></i> Browse the Shop
                </a>
                <a href="<?= e($basePath) ?>/contact.php" class="px-8 py-4 border border-white/10 rounded-2xl font-medium text-gray-300 hover:border-neon-blue/30 hover:text-white transition-all flex items-center gap-3">
                    <i class="ri-customer-service-line"></i> Contact Support
                </a>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
