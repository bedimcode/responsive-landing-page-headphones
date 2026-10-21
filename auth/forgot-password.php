<?php
/**
 * Forgot Password Page — emails a one-time reset link
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// All redirects must happen before any HTML output
if (isLoggedIn()) {
    redirect(APP_URL . '/');
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = cleanText($_POST['email'] ?? '', 255);
        if (!isValidEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                $db = getDB();
                if (!tooManyAttempts($db, 'reset', $email, 3, 30)) {
                    recordAttempt($db, 'reset', $email);

                    $stmt = $db->prepare('SELECT id, name, email FROM users WHERE email = ? AND is_active = 1');
                    $stmt->execute([$email]);
                    if ($user = $stmt->fetch()) {
                        // Only a hash of the token is stored; the email carries the token itself
                        $token = bin2hex(random_bytes(32));
                        $db->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?')
                           ->execute([hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600), $user['id']]);
                        sendPasswordReset($user['email'], $user['name'], absoluteUrl('/auth/reset-password.php?token=' . $token . '&id=' . $user['id']));
                    }
                }
                // Same answer whether or not the account exists (prevents email enumeration)
                $success = true;
            } catch (Exception $e) {
                error_log('Password reset request failed: ' . $e->getMessage());
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}

$pageTitle = 'Reset Password — ' . storeName();
$pageDescription = 'Reset your ' . storeName() . ' account password.';
$pageType = 'page';
$noIndex = true;

require_once __DIR__ . '/../includes/header.php';
?>

    <main class="pt-32 pb-24 min-h-[70vh] flex items-center">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="text-center mb-10">
                <h1 class="font-display text-3xl lg:text-4xl font-bold text-white mb-2">Reset Password</h1>
                <p class="text-gray-500">Enter your email and we&rsquo;ll send you a reset link</p>
            </div>

            <?php if ($success): ?>
            <div class="p-6 rounded-2xl bg-green-500/10 border border-green-500/20 text-center">
                <i class="ri-mail-check-line text-4xl text-green-400 mb-3 block"></i>
                <h2 class="text-white font-semibold text-lg mb-2">Check Your Email</h2>
                <p class="text-gray-400 text-sm">If an account exists with that email, you&rsquo;ll receive a password reset link shortly. The link expires in 60 minutes.</p>
                <a href="<?= e($basePath) ?>/auth/login.php" class="inline-flex items-center gap-2 mt-6 text-neon-blue hover:underline text-sm font-medium">
                    <i class="ri-arrow-left-line"></i> Back to Sign In
                </a>
            </div>
            <?php else: ?>

            <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-center gap-3">
                <i class="ri-error-warning-line text-lg"></i> <?= e($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <?= csrfField() ?>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-400 mb-2">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                        class="w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                        placeholder="you@example.com">
                </div>
                <button type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl font-bold text-dark-950 text-lg hover:scale-[1.02] transition-transform">
                    Send Reset Link
                </button>
            </form>

            <p class="text-center text-gray-500 text-sm mt-8">
                Remember your password? <a href="<?= e($basePath) ?>/auth/login.php" class="text-neon-blue hover:underline font-medium">Sign in</a>
            </p>
            <?php endif; ?>
        </div>
    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
