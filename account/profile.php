<?php
/**
 * User Dashboard — Profile Settings
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAuth();

$db   = getDB();
$uid  = currentUserId();
$errors = [];

// Fetch user data
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
        if (!isValidEmail($email)) $errors[] = 'Please enter a valid email address.';

        // Check email uniqueness
        if (empty($errors) && $email !== $user['email']) {
            $check = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $check->execute([$email, $uid]);
            if ($check->fetch()) $errors[] = 'This email is already in use.';
        }

        if (empty($errors)) {
            $stmt = $db->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
            $stmt->execute([$name, $email, $phone ?: null, $uid]);
            $_SESSION['user_name'] = $name;
            setFlash('success', 'Profile updated successfully.');
            redirect(APP_URL . '/account/profile.php');
        }
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPass, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }
        if (strlen($newPass) < 8) $errors[] = 'New password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $newPass)) $errors[] = 'New password must contain an uppercase letter.';
        if (!preg_match('/[0-9]/', $newPass)) $errors[] = 'New password must contain a number.';
        if ($newPass !== $confirmPass) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $uid]);
            setFlash('success', 'Password changed successfully.');
            redirect(APP_URL . '/account/profile.php');
        }
    }
}

$activeTab = $_GET['tab'] ?? 'profile';
// Refresh user data after potential updates
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();
$totalOrders = getUserOrderCount($db, $uid);

$accountPageTitle = 'Profile Settings';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Errors -->
<?php if (!empty($errors)): ?>
<div class="mb-6 p-3.5 rounded-xl bg-red-500/10 border border-red-500/20 text-sm text-red-400">
    <ul class="list-disc list-inside space-y-1">
        <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-4 sm:gap-6">

    <!-- Left: Profile Card -->
    <div class="space-y-4 sm:space-y-6">
        <!-- Avatar Card -->
        <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl p-5 sm:p-6 text-center">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-neon-blue/25 to-neon-purple/25
                        border-2 border-white/10 flex items-center justify-center
                        text-neon-blue text-2xl font-bold mx-auto mb-4">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <h2 class="font-display font-bold text-white text-lg"><?= e($user['name']) ?></h2>
            <p class="text-sm text-gray-500 mt-1"><?= e($user['email']) ?></p>
            <span class="inline-flex mt-3 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-neon-blue/10 text-neon-blue border border-neon-blue/20">
                Customer
            </span>
        </div>

        <!-- Quick Stats -->
        <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-white/[.06]">
                <h3 class="font-semibold text-white text-sm">Account Info</h3>
            </div>
            <div class="px-5 py-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Member Since</span>
                    <span class="text-gray-300"><?= date('M j, Y', strtotime($user['created_at'])) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Orders</span>
                    <span class="text-gray-300"><?= $totalOrders ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Spent</span>
                    <span class="text-gray-300"><?= formatPrice(getUserTotalSpent($db, $uid)) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Tabs -->
    <div class="lg:col-span-2">
        <!-- Tab Toggle -->
        <div class="flex gap-1.5 mb-4">
            <a href="?tab=profile"
               class="px-4 py-2.5 rounded-xl text-sm font-medium transition-all
                      <?= $activeTab === 'profile' ? 'bg-neon-blue/10 text-neon-blue border border-neon-blue/20' : 'bg-dark-900/60 text-gray-400 border border-white/[.06] hover:text-white' ?>">
                <i class="ri-user-line mr-1.5"></i>Profile
            </a>
            <a href="?tab=password"
               class="px-4 py-2.5 rounded-xl text-sm font-medium transition-all
                      <?= $activeTab === 'password' ? 'bg-neon-blue/10 text-neon-blue border border-neon-blue/20' : 'bg-dark-900/60 text-gray-400 border border-white/[.06] hover:text-white' ?>">
                <i class="ri-lock-line mr-1.5"></i>Password
            </a>
        </div>

        <!-- Profile Tab -->
        <?php if ($activeTab === 'profile'): ?>
        <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-white/[.06]">
                <h2 class="font-display font-semibold text-white text-sm">Edit Profile</h2>
            </div>
            <form method="post" class="px-4 sm:px-5 py-5 space-y-4">
                <input type="hidden" name="action" value="update_profile">
                <?= csrfField() ?>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Full Name</label>
                    <input type="text" name="name" value="<?= e($user['name']) ?>" required
                           class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white placeholder-gray-600
                                  focus:outline-none focus:border-neon-blue/30 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Email Address</label>
                    <input type="email" name="email" value="<?= e($user['email']) ?>" required
                           class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white placeholder-gray-600
                                  focus:outline-none focus:border-neon-blue/30 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Phone <span class="text-gray-600">(optional)</span></label>
                    <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>"
                           class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white placeholder-gray-600
                                  focus:outline-none focus:border-neon-blue/30 transition-colors">
                </div>

                <div class="pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-neon-blue text-dark-950 text-sm font-semibold rounded-xl hover:bg-neon-blue/90 transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Password Tab -->
        <?php if ($activeTab === 'password'): ?>
        <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-white/[.06]">
                <h2 class="font-display font-semibold text-white text-sm">Change Password</h2>
            </div>
            <form method="post" class="px-4 sm:px-5 py-5 space-y-4">
                <input type="hidden" name="action" value="update_password">
                <?= csrfField() ?>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Current Password</label>
                    <input type="password" name="current_password" required
                           class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white placeholder-gray-600
                                  focus:outline-none focus:border-neon-blue/30 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">New Password</label>
                    <input type="password" name="new_password" required minlength="8"
                           class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white placeholder-gray-600
                                  focus:outline-none focus:border-neon-blue/30 transition-colors">
                    <p class="text-[10px] text-gray-600 mt-1">Minimum 8 characters, 1 uppercase, 1 number</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Confirm New Password</label>
                    <input type="password" name="confirm_password" required
                           class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white placeholder-gray-600
                                  focus:outline-none focus:border-neon-blue/30 transition-colors">
                </div>

                <div class="pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-neon-blue text-dark-950 text-sm font-semibold rounded-xl hover:bg-neon-blue/90 transition-colors">
                        Change Password
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
