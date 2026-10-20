<?php
/**
 * Reset Password Page — opened from the emailed one-time link
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$token  = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$userId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$errors = [];

/**
 * The user whose unexpired reset token matches, or null.
 */
function findResetUser(PDO $db, int $userId, string $token): ?array {
    if (!$userId || !preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    $stmt = $db->prepare('SELECT id, name, email, reset_token, reset_expires FROM users WHERE id = ? AND is_active = 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || !$user['reset_token'] || strtotime($user['reset_expires']) < time()) return null;
    return hash_equals($user['reset_token'], hash('sha256', $token)) ? $user : null;
}

$db   = getDB();
$user = findResetUser($db, $userId, $token);

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must include an uppercase letter and a number.';
        }
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        if (!$errors) {
            $db->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?')
               ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            clearAttempts($db, 'login', $user['email']);
            setFlash('success', 'Your password has been changed. Please sign in.');
            redirect(APP_URL . '/auth/login.php');
        }
    }
}

$pageTitle = 'Choose a New Password — ' . storeName();
$pageType = 'page';
$noIndex = true;

require_once __DIR__ . '/../includes/header.php';
?>

    <main class="pt-32 pb-24 min-h-[70vh] flex items-center">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <?php if (!$user): ?>
            <div class="p-6 rounded-2xl bg-red-500/10 border border-red-500/20 text-center">
                <i class="ri-link-unlink text-4xl text-red-400 mb-3 block"></i>
                <h1 class="text-white font-semibold text-lg mb-2">This reset link is invalid or has expired</h1>
                <p class="text-gray-400 text-sm">Reset links work once and expire after 60 minutes.</p>
                <a href="<?= e($basePath) ?>/auth/forgot-password.php" class="inline-flex items-center gap-2 mt-6 text-neon-blue hover:underline text-sm font-medium">
                    <i class="ri-refresh-line"></i> Request a new link
                </a>
            </div>
            <?php else: ?>
            <div class="text-center mb-10">
                <h1 class="font-display text-3xl lg:text-4xl font-bold text-white mb-2">New Password</h1>
                <p class="text-gray-500">for <?= e($user['email']) ?></p>
            </div>

            <?php if ($errors): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm space-y-1">
                <?php foreach ($errors as $err): ?>
                <div class="flex items-center gap-2"><i class="ri-error-warning-line flex-shrink-0"></i> <?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-400 mb-2">New Password</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"
                        class="w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                        placeholder="Min 8 chars, 1 uppercase, 1 number">
                </div>
                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-400 mb-2">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"
                        class="w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                        placeholder="Re-enter your password">
                </div>
                <button type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl font-bold text-dark-950 text-lg hover:scale-[1.02] transition-transform">
                    Save New Password
                </button>
            </form>
            <?php endif; ?>
        </div>
    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
