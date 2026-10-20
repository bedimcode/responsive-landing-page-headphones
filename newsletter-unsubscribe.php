<?php
/**
 * Newsletter Unsubscribe — one-click link from newsletter emails
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? '';
$done  = false;

if (is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM newsletter WHERE token = ?');
    $stmt->execute([$token]);
    $subscriberId = $stmt->fetchColumn();
    if ($subscriberId) {
        $db->prepare('UPDATE newsletter SET is_active = 0 WHERE id = ?')->execute([$subscriberId]);
        $done = true;
    }
}

$pageTitle = 'Unsubscribe — ' . storeName();
$pageType  = 'page';
$noIndex   = true;
require_once __DIR__ . '/includes/header.php';
?>

    <main class="pt-32 pb-24 min-h-[70vh] flex items-center">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 w-full text-center">
            <?php if ($done): ?>
            <i class="ri-mail-close-line text-5xl text-neon-blue mb-4 block"></i>
            <h1 class="font-display text-3xl font-bold text-white mb-3">You&rsquo;re unsubscribed</h1>
            <p class="text-gray-400">You won&rsquo;t receive any more newsletter emails from <?= e(storeName()) ?>. Order emails are not affected.</p>
            <?php else: ?>
            <i class="ri-link-unlink text-5xl text-gray-600 mb-4 block"></i>
            <h1 class="font-display text-3xl font-bold text-white mb-3">Link not valid</h1>
            <p class="text-gray-400">This unsubscribe link is incomplete or expired. Contact us and we&rsquo;ll remove you right away.</p>
            <?php endif; ?>
            <a href="<?= e($basePath) ?>/" class="inline-flex items-center gap-2 mt-8 text-neon-blue hover:underline text-sm font-medium">
                <i class="ri-arrow-left-line"></i> Back to the store
            </a>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
