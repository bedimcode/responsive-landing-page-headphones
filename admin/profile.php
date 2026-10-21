<?php
/**
 * Admin Profile Manager
 * All POST processing must happen before any HTML output.
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/functions.php';
requireAdmin();

$db     = getDB();
$userId = (int) currentUserId();
$user   = $db->prepare("SELECT id,name,email,phone,role,created_at FROM users WHERE id=?");
$user->execute([$userId]);
$user   = $user->fetch();
$errors = [];
$profileSuccess = false;
$passwordSuccess = false;
$activeTab = $_GET['tab'] ?? 'profile'; // 'profile' | 'password'
$basePath  = rtrim(APP_URL, '/');

// ---- Handle Profile Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'profile') {
    if (!validateCsrf()) $errors[] = 'Invalid security token. Please try again.';
    $newName  = trim($_POST['name']  ?? '');
    $newEmail = strtolower(trim($_POST['email'] ?? ''));
    $newPhone = trim($_POST['phone'] ?? '');

    if (strlen($newName) < 2)          $errors[] = 'Name must be at least 2 characters.';
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    if (empty($errors)) {
        // Check email uniqueness (exclude self)
        $dupe = $db->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $dupe->execute([$newEmail, $userId]);
        if ($dupe->fetch()) {
            $errors[] = 'That email is already in use by another account.';
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
        $stmt->execute([$newName, $newEmail, $newPhone ?: null, $userId]);
        $_SESSION['user_name'] = $newName;
        setFlash('success', 'Profile updated successfully.');
        redirect($basePath . '/admin/profile.php?tab=profile');
    }
    $activeTab = 'profile';
}

// ---- Handle Password Change ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'password') {
    if (!validateCsrf()) $errors[] = 'Invalid security token. Please try again.';
    $current  = $_POST['current_password']  ?? '';
    $newPass  = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    if (empty($current))                    $errors[] = 'Current password is required.';
    if (strlen($newPass) < 8)               $errors[] = 'New password must be at least 8 characters.';
    if (in_array($newPass, ['Admin@123', 'password'], true)) $errors[] = 'Choose a password that is not published in the installation guide.';
    if ($newPass !== $confirm)              $errors[] = 'New passwords do not match.';

    if (empty($errors)) {
        $row = $db->prepare("SELECT password_hash FROM users WHERE id=?");
        $row->execute([$userId]);
        $hash = $row->fetchColumn();
        if (!password_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $stmt->execute([password_hash($newPass, PASSWORD_DEFAULT), $userId]);
        unset($_SESSION['admin_default_pw']);
        setFlash('success', 'Password changed successfully. Please log in with your new password next time.');
        redirect($basePath . '/admin/profile.php?tab=password');
    }
    $activeTab = 'password';
}

// Reload fresh user data in case of update
$refetch = $db->prepare("SELECT id,name,email,phone,role,created_at FROM users WHERE id=?");
$refetch->execute([$userId]);
$user = $refetch->fetch();

// Account stats
$orderCount     = (int) $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$productCount   = (int) $db->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
$messageCount   = (int) $db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$adminPageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto">

    <!-- Page header -->
    <div class="flex items-center gap-4 mb-6">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-neon-blue/30 to-neon-purple/30
                    border border-neon-blue/20 flex items-center justify-center
                    text-neon-blue text-2xl font-bold font-display flex-shrink-0">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
        </div>
        <div>
            <h2 class="font-display font-bold text-white text-xl"><?= e($user['name']) ?></h2>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-[11px] bg-neon-blue/10 text-neon-blue border border-neon-blue/20 px-2 py-0.5 rounded-full font-medium uppercase tracking-wide">
                    <?= ucfirst(e($user['role'])) ?>
                </span>
                <span class="text-xs text-gray-500">Member since <?= date('M Y', strtotime($user['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <!-- Quick stats strip -->
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-dark-900 border border-white/5 rounded-xl p-4 text-center">
            <div class="font-display font-bold text-white text-xl"><?= $orderCount ?></div>
            <div class="text-[11px] text-gray-500 mt-0.5">Total Orders</div>
        </div>
        <div class="bg-dark-900 border border-white/5 rounded-xl p-4 text-center">
            <div class="font-display font-bold text-white text-xl"><?= $productCount ?></div>
            <div class="text-[11px] text-gray-500 mt-0.5">Active Products</div>
        </div>
        <div class="bg-dark-900 border border-white/5 rounded-xl p-4 text-center">
            <div class="font-display font-bold text-white text-xl"><?= $messageCount ?></div>
            <div class="text-[11px] text-gray-500 mt-0.5">Messages</div>
        </div>
    </div>

    <!-- Error block (shown before tabs) -->
    <?php if (!empty($errors)): ?>
    <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/20">
        <?php foreach ($errors as $e): ?>
        <div class="flex items-center gap-2 text-sm text-red-400">
            <i class="ri-error-warning-line flex-shrink-0"></i> <?= htmlspecialchars($e) ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="flex gap-1 mb-5 bg-dark-900 border border-white/5 rounded-xl p-1 w-fit">
        <a href="?tab=profile"
           class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                  <?= $activeTab==='profile' ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-white' ?>">
            <i class="ri-user-3-line mr-1.5"></i>Profile
        </a>
        <a href="?tab=password"
           class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                  <?= $activeTab==='password' ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-white' ?>">
            <i class="ri-lock-password-line mr-1.5"></i>Password
        </a>
    </div>

    <?php if ($activeTab === 'profile'): ?>
    <!-- ---- PROFILE FORM ---- -->
    <form method="POST" action="?tab=profile" class="bg-dark-900 border border-white/5 rounded-2xl p-6 space-y-5">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="profile">

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-2">Full Name</label>
                <input type="text" name="name" required
                       value="<?= e($user['name']) ?>"
                       class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-3 text-sm text-white
                              placeholder-gray-600 focus:outline-none focus:border-neon-blue/50 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-2">Email Address</label>
                <input type="email" name="email" required
                       value="<?= e($user['email']) ?>"
                       class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-3 text-sm text-white
                              placeholder-gray-600 focus:outline-none focus:border-neon-blue/50 transition-colors">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-400 mb-2">Phone Number <span class="text-gray-600">(optional)</span></label>
            <input type="tel" name="phone"
                   value="<?= e($user['phone'] ?? '') ?>"
                   placeholder="+1 555 000 0000"
                   class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-3 text-sm text-white
                          placeholder-gray-600 focus:outline-none focus:border-neon-blue/50 transition-colors">
        </div>

        <div class="pt-2">
            <label class="block text-xs font-medium text-gray-400 mb-2">Role</label>
            <div class="flex items-center gap-2 px-4 py-3 bg-dark-800 border border-white/5 rounded-xl">
                <i class="ri-shield-check-line text-neon-blue text-sm"></i>
                <span class="text-sm text-white"><?= ucfirst(e($user['role'])) ?></span>
                <span class="ml-auto text-[11px] text-gray-500">Cannot be changed here</span>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit"
                    class="px-6 py-2.5 bg-neon-blue/10 border border-neon-blue/20 rounded-xl text-neon-blue
                           text-sm font-medium hover:bg-neon-blue/20 transition-all">
                <i class="ri-save-line mr-1.5"></i>Save Changes
            </button>
        </div>
    </form>

    <?php else: ?>
    <!-- ---- PASSWORD FORM ---- -->
    <form method="POST" action="?tab=password" class="bg-dark-900 border border-white/5 rounded-2xl p-6 space-y-5">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="password">

        <div>
            <label class="block text-xs font-medium text-gray-400 mb-2">Current Password</label>
            <input type="password" name="current_password" required autocomplete="current-password"
                   class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-3 text-sm text-white
                          placeholder-gray-600 focus:outline-none focus:border-neon-blue/50 transition-colors">
        </div>

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-2">New Password</label>
                <input type="password" name="new_password" required autocomplete="new-password" minlength="8"
                       placeholder="Min 8 characters"
                       class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-3 text-sm text-white
                              placeholder-gray-600 focus:outline-none focus:border-neon-blue/50 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-2">Confirm New Password</label>
                <input type="password" name="confirm_password" required autocomplete="new-password" minlength="8"
                       class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-3 text-sm text-white
                              placeholder-gray-600 focus:outline-none focus:border-neon-blue/50 transition-colors">
            </div>
        </div>

        <div class="p-4 rounded-xl bg-white/3 border border-white/5 text-xs text-gray-500 space-y-1.5">
            <p class="font-medium text-gray-400 mb-1">Password requirements</p>
            <div class="flex items-center gap-2"><i class="ri-check-line text-neon-blue"></i>At least 8 characters</div>
            <div class="flex items-center gap-2"><i class="ri-check-line text-neon-blue"></i>Mix of letters and numbers recommended</div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit"
                    class="px-6 py-2.5 bg-neon-blue/10 border border-neon-blue/20 rounded-xl text-neon-blue
                           text-sm font-medium hover:bg-neon-blue/20 transition-all">
                <i class="ri-lock-password-line mr-1.5"></i>Change Password
            </button>
        </div>
    </form>
    <?php endif; ?>

    <!-- Activity info -->
    <div class="mt-5 p-4 bg-dark-900 border border-white/5 rounded-2xl">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Account Info</h3>
        <div class="space-y-2.5">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Account created</span>
                <span class="text-white font-medium"><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">User ID</span>
                <span class="font-mono text-gray-400 text-xs">#<?= $user['id'] ?></span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Role</span>
                <span class="text-neon-blue font-medium text-xs uppercase"><?= e($user['role']) ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
