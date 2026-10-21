<?php
/**
 * Maintenance page — shown by maintenanceGate() while maintenance mode is on.
 */
$basePath = rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Back soon — <?= e(storeName()) ?></title>
    <link rel="shortcut icon" href="<?= e($basePath) ?>/assets/img/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?= e(assetUrl('assets/css/tailwind.css')) ?>">
</head>
<body class="bg-dark-950 text-gray-300 font-sans min-h-screen flex items-center justify-center p-6">
    <main class="max-w-md text-center">
        <img src="<?= e($basePath) ?>/assets/img/logo.png" alt="<?= e(storeName()) ?>" class="h-12 mx-auto mb-8">
        <h1 class="font-display text-3xl font-bold text-white mb-3">We&rsquo;ll be right back</h1>
        <p class="text-gray-400 mb-8">The store is closed for a short update. Please check back soon.</p>
        <?php if (setting('store_email')): ?>
        <a href="mailto:<?= e(setting('store_email')) ?>" class="text-neon-blue hover:underline text-sm"><?= e(setting('store_email')) ?></a>
        <?php endif; ?>
    </main>
</body>
</html>
