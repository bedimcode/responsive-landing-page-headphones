<?php
/**
 * Register Page
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// All redirects and session changes must happen before any HTML output
if (isLoggedIn()) {
    redirect(APP_URL . '/');
}

$error = '';
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = cleanText($_POST['name'] ?? '', 100);
        $email = cleanText($_POST['email'] ?? '', 255);
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        $errors = [];
        if (mb_strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
        if (!isValidEmail($email)) $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must include an uppercase letter and a number.';
        }
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            try {
                $db = getDB();

                // Check duplicate email
                $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'An account with this email already exists. <a href="' . e(APP_URL) . '/auth/login.php" class="text-neon-blue hover:underline">Sign in instead</a>';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $db->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)')->execute([$name, $email, $hash]);

                    $userId = (int) $db->lastInsertId();

                    // Save old session ID for cart migration BEFORE regeneration
                    $oldSessionId = session_id();

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = 'customer';

                    // Migrate guest cart to new user account
                    mergeGuestCartToUser($db, $oldSessionId, $userId);

                    setFlash('success', 'Welcome to ' . storeName() . ', ' . $name . '! Your account has been created.');
                    $redirect = $_SESSION['redirect_after_login'] ?? APP_URL . '/';
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirect);
                }
            } catch (Exception $e) {
                $error = 'Something went wrong. Please try again.';
            }
        } else {
            $error = implode('<br>', array_map('htmlspecialchars', $errors));
        }
    }
}

// HTML output starts here
$pageTitle = 'Create Account — ' . storeName();
$pageDescription = 'Create your ' . storeName() . ' account for faster checkout and order tracking.';
$pageType = 'page';
$noIndex = true;

require_once __DIR__ . '/../includes/header.php';
?>

    <main class="pt-32 pb-24 min-h-[70vh] flex items-center">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="text-center mb-10">
                <h1 class="font-display text-3xl lg:text-4xl font-bold text-white mb-2">Create Account</h1>
                <p class="text-gray-500">Track orders, save addresses and check out faster</p>
            </div>

            <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-center gap-3">
                <i class="ri-error-warning-line text-lg flex-shrink-0"></i> <span><?= $error ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <?= csrfField() ?>
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-400 mb-2">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= e($name) ?>" required autocomplete="name"
                        class="w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                        placeholder="John Doe">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-400 mb-2">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email"
                        class="w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                        placeholder="you@example.com">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-400 mb-2">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password"
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
                    Create Account
                </button>
            </form>

            <p class="text-center text-gray-500 text-sm mt-8">
                Already have an account? <a href="<?= e($basePath) ?>/auth/login.php" class="text-neon-blue hover:underline font-medium">Sign in</a>
            </p>
        </div>
    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
