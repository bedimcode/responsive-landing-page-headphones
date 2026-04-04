<?php
/**
 * User Dashboard — Address Book
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAuth();

$db   = getDB();
$uid  = currentUserId();
$errors = [];

// Handle POST actions BEFORE any HTML output to allow redirects
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_address') {
    if (!validateCsrf()) {
        $errors[] = 'Invalid security token.';
    } else {
        $label   = trim($_POST['label'] ?? 'Home');
        $address = trim($_POST['address'] ?? '');
        $city    = trim($_POST['city'] ?? '');
        $state   = trim($_POST['state'] ?? '');
        $zip     = trim($_POST['zip'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $isDefault = !empty($_POST['is_default']) ? 1 : 0;

        if (strlen($address) < 5) $errors[] = 'Please enter a valid address.';
        if (strlen($city) < 2) $errors[] = 'Please enter a valid city.';
        if (strlen($zip) < 3) $errors[] = 'Please enter a valid ZIP code.';
        if (strlen($country) < 2) $errors[] = 'Please select a country.';

        if (empty($errors)) {
            if ($isDefault) {
                $db->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?')->execute([$uid]);
            }
            $stmt = $db->prepare('INSERT INTO user_addresses (user_id, label, address, city, state, zip, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$uid, $label, $address, $city, $state ?: null, $zip, $country, $isDefault]);
            setFlash('success', 'Address added successfully.');
            redirect(APP_URL . '/account/addresses.php');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_address') {
    if (validateCsrf()) {
        $addrId = (int) ($_POST['address_id'] ?? 0);
        $db->prepare('DELETE FROM user_addresses WHERE id = ? AND user_id = ?')->execute([$addrId, $uid]);
        setFlash('success', 'Address removed.');
        redirect(APP_URL . '/account/addresses.php');
    }
}

// Handle set default
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_default') {
    if (validateCsrf()) {
        $addrId = (int) ($_POST['address_id'] ?? 0);
        $db->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?')->execute([$uid]);
        $db->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?')->execute([$addrId, $uid]);
        setFlash('success', 'Default address updated.');
        redirect(APP_URL . '/account/addresses.php');
    }
}

$addresses = getUserAddresses($db, $uid);
$showForm  = isset($_GET['add']) || !empty($errors);

// Now safe to output HTML
$accountPageTitle = 'My Addresses';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Errors -->
<?php if (!empty($errors)): ?>
<div class="mb-6 p-3.5 rounded-xl bg-red-500/10 border border-red-500/20 text-sm text-red-400">
    <ul class="list-disc list-inside space-y-1">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Header with Add Button -->
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500"><?= count($addresses) ?> address<?= count($addresses) !== 1 ? 'es' : '' ?> saved</p>
    <?php if (!$showForm): ?>
    <a href="?add=1" class="inline-flex items-center gap-2 px-4 py-2 bg-neon-blue text-dark-950 text-sm font-semibold rounded-xl hover:bg-neon-blue/90 transition-colors">
        <i class="ri-add-line"></i> Add Address
    </a>
    <?php endif; ?>
</div>

<!-- Add Address Form -->
<?php if ($showForm): ?>
<div class="bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden mb-6">
    <div class="flex items-center justify-between px-4 sm:px-5 py-4 border-b border-white/[.06]">
        <h2 class="font-display font-semibold text-white text-sm">Add New Address</h2>
        <a href="<?= e($basePath) ?>/account/addresses.php" class="text-xs text-gray-500 hover:text-white transition-colors">Cancel</a>
    </div>
    <form method="post" class="px-4 sm:px-5 py-5 space-y-4">
        <input type="hidden" name="action" value="add_address">
        <?= csrfField() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Label</label>
                <select name="label" class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white focus:outline-none focus:border-neon-blue/30 transition-colors">
                    <option value="Home">Home</option>
                    <option value="Work">Work</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Country</label>
                <select name="country" required class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white focus:outline-none focus:border-neon-blue/30 transition-colors">
                    <option value="">Select country</option>
                    <option value="United States" selected>United States</option>
                    <option value="Canada">Canada</option>
                    <option value="United Kingdom">United Kingdom</option>
                    <option value="Australia">Australia</option>
                    <option value="Germany">Germany</option>
                    <option value="France">France</option>
                    <option value="Japan">Japan</option>
                    <option value="South Korea">South Korea</option>
                    <option value="Kenya">Kenya</option>
                    <option value="Nigeria">Nigeria</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-400 mb-1.5">Street Address</label>
            <input type="text" name="address" required placeholder="123 Main Street, Apt 4B"
                   class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:border-neon-blue/30 transition-colors">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">City</label>
                <input type="text" name="city" required
                       class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:border-neon-blue/30 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">State <span class="text-gray-600">(optional)</span></label>
                <input type="text" name="state"
                       class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:border-neon-blue/30 transition-colors">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">ZIP Code</label>
                <input type="text" name="zip" required
                       class="w-full px-4 py-2.5 bg-dark-950 border border-white/[.08] rounded-xl text-sm text-white placeholder-gray-600 focus:outline-none focus:border-neon-blue/30 transition-colors">
            </div>
        </div>

        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_default" value="1"
                   class="w-4 h-4 rounded border-white/20 bg-dark-950 text-neon-blue focus:ring-neon-blue/30">
            <span class="text-sm text-gray-400">Set as default address</span>
        </label>

        <div class="pt-2">
            <button type="submit" class="px-6 py-2.5 bg-neon-blue text-dark-950 text-sm font-semibold rounded-xl hover:bg-neon-blue/90 transition-colors">
                Save Address
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Address Cards -->
<?php if (empty($addresses) && !$showForm): ?>
<div class="bg-dark-900/60 border border-white/[.06] rounded-2xl px-5 py-16 text-center">
    <i class="ri-map-pin-line text-5xl text-gray-700 mb-4 block"></i>
    <p class="text-gray-400 mb-2">No saved addresses.</p>
    <p class="text-sm text-gray-600 mb-6">Add a shipping address for faster checkout.</p>
    <a href="?add=1" class="inline-flex items-center gap-2 px-5 py-2.5 bg-neon-blue text-dark-950 text-sm font-semibold rounded-xl hover:bg-neon-blue/90 transition-colors">
        <i class="ri-add-line"></i> Add Address
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($addresses as $addr): ?>
    <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl p-4 sm:p-5 relative <?= $addr['is_default'] ? 'ring-1 ring-neon-blue/20' : '' ?>">
        <!-- Label -->
        <div class="flex items-center gap-2 mb-3">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold
                         <?= $addr['is_default'] ? 'bg-neon-blue/10 text-neon-blue' : 'bg-white/[.04] text-gray-500' ?>">
                <i class="<?= $addr['label'] === 'Home' ? 'ri-home-4-line' : ($addr['label'] === 'Work' ? 'ri-building-line' : 'ri-map-pin-line') ?> text-[10px]"></i>
                <?= e($addr['label']) ?>
            </span>
            <?php if ($addr['is_default']): ?>
            <span class="text-[10px] text-neon-blue font-medium">Default</span>
            <?php endif; ?>
        </div>

        <!-- Address -->
        <div class="text-sm text-gray-300 space-y-0.5 mb-4">
            <div><?= e($addr['address']) ?></div>
            <div><?= e($addr['city']) ?><?= $addr['state'] ? ', ' . e($addr['state']) : '' ?> <?= e($addr['zip']) ?></div>
            <div class="text-gray-500"><?= e($addr['country']) ?></div>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
            <?php if (!$addr['is_default']): ?>
            <form method="post" class="inline">
                <input type="hidden" name="action" value="set_default">
                <input type="hidden" name="address_id" value="<?= (int) $addr['id'] ?>">
                <?= csrfField() ?>
                <button type="submit" class="px-3 py-1.5 text-[11px] font-medium text-gray-400 bg-white/[.04] border border-white/[.06] rounded-lg hover:text-white hover:border-white/10 transition-all">
                    Set Default
                </button>
            </form>
            <?php endif; ?>
            <form method="post" class="inline" onsubmit="return confirm('Remove this address?')">
                <input type="hidden" name="action" value="delete_address">
                <input type="hidden" name="address_id" value="<?= (int) $addr['id'] ?>">
                <?= csrfField() ?>
                <button type="submit" class="px-3 py-1.5 text-[11px] font-medium text-red-400/70 bg-white/[.04] border border-white/[.06] rounded-lg hover:text-red-400 hover:border-red-500/20 transition-all">
                    Remove
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
