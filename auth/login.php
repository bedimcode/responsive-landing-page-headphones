<?php
/**
 * Login Page
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// All redirects and session changes must happen before any HTML output
if (isLoggedIn()) {
    redirect(APP_URL . '/');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!isValidEmail($email) || empty($password)) {
            $error = 'Please enter a valid email and password.';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? AND is_active = 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];

                    // Migrate guest cart to user
                    if (!empty($_SESSION['cart_session_id'])) {
                        $guestCart = $db->prepare('SELECT id FROM cart WHERE session_id = ? AND user_id IS NULL');
                        $guestCart->execute([$_SESSION['cart_session_id']]);
                        $gc = $guestCart->fetch(PDO::FETCH_ASSOC);
                        if ($gc) {
                            $db->prepare('UPDATE cart SET user_id = ?, session_id = NULL WHERE id = ?')->execute([$user['id'], $gc['id']]);
                        }
                    }

                    $defaultRedirect = $user['role'] === 'admin' ? APP_URL . '/admin/' : APP_URL . '/';
                    $redirect = $_SESSION['redirect_after_login'] ?? $defaultRedirect;
                    unset($_SESSION['redirect_after_login']);
                    setFlash('success', 'Welcome back, ' . e($user['name']) . '!');
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $error = 'Invalid email or password.';
                }
            } catch (Exception $e) {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}

// HTML output starts here
$pageTitle = 'Sign In — Beats Audio';
$pageDescription = 'Sign in to your Beats Audio account.';
$pageType = 'page';
$noIndex = true;

require_once __DIR__ . '/../includes/header.php';
?>

    <main class="pt-32 pb-24 min-h-[70vh] flex items-center">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="text-center mb-10">
                <h1 class="font-display text-3xl lg:text-4xl font-bold text-white mb-2">Welcome Back</h1>
                <p class="text-gray-500">Sign in to your Beats Audio account</p>
            </div>

            <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-center gap-3">
                <i class="ri-error-warning-line text-lg"></i> <?= e($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <?= csrfField() ?>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-400 mb-2">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email"
                        class="w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                        placeholder="you@example.com">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-400 mb-2">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        class="w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                        placeholder="Enter your password">
                </div>
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-gray-400 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-white/20 bg-dark-900 text-neon-blue focus:ring-neon-blue/50">
                        Remember me
                    </label>
                    <a href="<?= e($basePath) ?>/auth/forgot-password.php" class="text-neon-blue hover:underline">Forgot password?</a>
                </div>
                <button type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl font-bold text-dark-950 text-lg hover:scale-[1.02] transition-transform">
                    Sign In
                </button>
            </form>

            <p class="text-center text-gray-500 text-sm mt-8">
                Don&rsquo;t have an account? <a href="<?= e($basePath) ?>/auth/register.php" class="text-neon-blue hover:underline font-medium">Create one</a>
            </p>
        </div>
    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
