<?php
/**
 * Admin — Settings
 * Store configuration and preferences.
 * Note: Since there's no settings table, this page saves to a JSON file.
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/functions.php';
requireAdmin();

$basePath     = rtrim(APP_URL, '/');
$settingsFile = BASE_PATH . '/admin/settings.json';
$errors       = [];

// Default settings
$defaults = [
    'store_name'         => APP_NAME,
    'store_email'        => 'admin@beats3.store',
    'store_phone'        => '',
    'store_address'      => '',
    'currency_symbol'    => '$',
    'currency_code'      => 'USD',
    'tax_rate'           => 0,
    'free_shipping_min'  => 0,
    'shipping_flat_rate' => 0,
    'low_stock_threshold'=> 10,
    'orders_per_page'    => 20,
    'maintenance_mode'   => false,
    'order_email_notify' => true,
    'stock_email_notify' => true,
];

// Load existing
$settings = $defaults;
if (file_exists($settingsFile)) {
    $loaded = json_decode(file_get_contents($settingsFile), true);
    if (is_array($loaded)) {
        $settings = array_merge($defaults, $loaded);
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $action = $_POST['_action'] ?? 'general';

    if ($action === 'general') {
        $settings['store_name']    = trim($_POST['store_name'] ?? $settings['store_name']);
        $settings['store_email']   = trim($_POST['store_email'] ?? $settings['store_email']);
        $settings['store_phone']   = trim($_POST['store_phone'] ?? '');
        $settings['store_address'] = trim($_POST['store_address'] ?? '');

        if (strlen($settings['store_name']) < 2) $errors[] = 'Store name is required.';
        if (!filter_var($settings['store_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    }

    if ($action === 'commerce') {
        $settings['currency_symbol']     = trim($_POST['currency_symbol'] ?? '$');
        $settings['currency_code']       = strtoupper(trim($_POST['currency_code'] ?? 'USD'));
        $settings['tax_rate']            = max(0, (float)($_POST['tax_rate'] ?? 0));
        $settings['free_shipping_min']   = max(0, (float)($_POST['free_shipping_min'] ?? 0));
        $settings['shipping_flat_rate']  = max(0, (float)($_POST['shipping_flat_rate'] ?? 0));
        $settings['low_stock_threshold'] = max(1, (int)($_POST['low_stock_threshold'] ?? 10));
        $settings['orders_per_page']     = max(5, min(100, (int)($_POST['orders_per_page'] ?? 20)));
    }

    if ($action === 'notifications') {
        $settings['order_email_notify'] = isset($_POST['order_email_notify']);
        $settings['stock_email_notify'] = isset($_POST['stock_email_notify']);
        $settings['maintenance_mode']   = isset($_POST['maintenance_mode']);
    }

    if (empty($errors)) {
        $dir = dirname($settingsFile);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        setFlash('success', 'Settings saved successfully.');
        redirect($basePath . '/admin/settings.php?tab=' . urlencode($action));
    }
}

$activeTab = $_GET['tab'] ?? 'general';
if (!in_array($activeTab, ['general','commerce','notifications','system'])) $activeTab = 'general';

$adminPageTitle = 'Settings';
require_once __DIR__ . '/includes/header.php';

// Input helper
$inp = 'w-full px-4 py-3 bg-dark-800 border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors';
?>

<div class="max-w-4xl mx-auto">

    <!-- Error block -->
    <?php if (!empty($errors)): ?>
    <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/20">
        <?php foreach ($errors as $err): ?>
        <div class="flex items-center gap-2 text-sm text-red-400">
            <i class="ri-error-warning-line flex-shrink-0"></i> <?= e($err) ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 bg-dark-900 border border-white/[.06] rounded-xl p-1 w-fit flex-wrap">
        <?php
        $tabs = [
            'general'       => ['icon' => 'ri-store-2-line',        'label' => 'General'],
            'commerce'      => ['icon' => 'ri-money-dollar-circle-line', 'label' => 'Commerce'],
            'notifications' => ['icon' => 'ri-notification-3-line', 'label' => 'Notifications'],
            'system'        => ['icon' => 'ri-server-line',         'label' => 'System'],
        ];
        foreach ($tabs as $key => $tab):
        ?>
        <a href="?tab=<?= $key ?>"
           class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-all
                  <?= $activeTab === $key ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-white' ?>">
            <i class="<?= $tab['icon'] ?> text-sm"></i>
            <?= $tab['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ===== GENERAL TAB ===== -->
    <?php if ($activeTab === 'general'): ?>
    <form method="POST" class="space-y-6">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="general">

        <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-6 space-y-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-neon-blue/10 flex items-center justify-center">
                    <i class="ri-store-2-line text-neon-blue text-lg"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-white">Store Information</h2>
                    <p class="text-[12px] text-gray-500">Basic details about your store</p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Store Name</label>
                    <input type="text" name="store_name" value="<?= e($settings['store_name']) ?>" required class="<?= $inp ?>">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Contact Email</label>
                    <input type="email" name="store_email" value="<?= e($settings['store_email']) ?>" required class="<?= $inp ?>">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Phone Number</label>
                    <input type="tel" name="store_phone" value="<?= e($settings['store_phone']) ?>" placeholder="+1 555 000 0000" class="<?= $inp ?>">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Address</label>
                    <input type="text" name="store_address" value="<?= e($settings['store_address']) ?>" placeholder="123 Main St, City, State" class="<?= $inp ?>">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl text-sm font-bold text-dark-950 hover:opacity-90 transition-opacity">
                <i class="ri-save-line mr-1.5"></i> Save Changes
            </button>
        </div>
    </form>

    <!-- ===== COMMERCE TAB ===== -->
    <?php elseif ($activeTab === 'commerce'): ?>
    <form method="POST" class="space-y-6">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="commerce">

        <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-6 space-y-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center">
                    <i class="ri-money-dollar-circle-line text-green-400 text-lg"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-white">Currency & Pricing</h2>
                    <p class="text-[12px] text-gray-500">Set your store's currency and tax rates</p>
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="<?= e($settings['currency_symbol']) ?>" maxlength="5" class="<?= $inp ?>">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Currency Code</label>
                    <input type="text" name="currency_code" value="<?= e($settings['currency_code']) ?>" maxlength="3" placeholder="USD" class="<?= $inp ?>">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Tax Rate (%)</label>
                    <input type="number" name="tax_rate" value="<?= e((string)$settings['tax_rate']) ?>" min="0" max="100" step="0.01" class="<?= $inp ?>">
                </div>
            </div>
        </div>

        <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-6 space-y-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-neon-purple/10 flex items-center justify-center">
                    <i class="ri-truck-line text-neon-purple text-lg"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-white">Shipping</h2>
                    <p class="text-[12px] text-gray-500">Configure shipping costs and thresholds</p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Flat Shipping Rate ($)</label>
                    <input type="number" name="shipping_flat_rate" value="<?= e((string)$settings['shipping_flat_rate']) ?>" min="0" step="0.01" class="<?= $inp ?>">
                    <p class="text-[11px] text-gray-600 mt-1.5">Set to 0 for free shipping on all orders</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Free Shipping Minimum ($)</label>
                    <input type="number" name="free_shipping_min" value="<?= e((string)$settings['free_shipping_min']) ?>" min="0" step="0.01" class="<?= $inp ?>">
                    <p class="text-[11px] text-gray-600 mt-1.5">Orders above this amount get free shipping</p>
                </div>
            </div>
        </div>

        <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-6 space-y-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-yellow-500/10 flex items-center justify-center">
                    <i class="ri-settings-3-line text-yellow-400 text-lg"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-white">Inventory & Display</h2>
                    <p class="text-[12px] text-gray-500">Stock alerts and pagination</p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Low Stock Threshold</label>
                    <input type="number" name="low_stock_threshold" value="<?= (int)$settings['low_stock_threshold'] ?>" min="1" class="<?= $inp ?>">
                    <p class="text-[11px] text-gray-600 mt-1.5">Products below this quantity trigger alerts</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-2">Orders Per Page</label>
                    <input type="number" name="orders_per_page" value="<?= (int)$settings['orders_per_page'] ?>" min="5" max="100" class="<?= $inp ?>">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl text-sm font-bold text-dark-950 hover:opacity-90 transition-opacity">
                <i class="ri-save-line mr-1.5"></i> Save Changes
            </button>
        </div>
    </form>

    <!-- ===== NOTIFICATIONS TAB ===== -->
    <?php elseif ($activeTab === 'notifications'): ?>
    <form method="POST" class="space-y-6">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="notifications">

        <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-6 space-y-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-neon-cyan/10 flex items-center justify-center">
                    <i class="ri-notification-3-line text-neon-cyan text-lg"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-white">Email Notifications</h2>
                    <p class="text-[12px] text-gray-500">Configure when to receive email alerts</p>
                </div>
            </div>

            <div class="space-y-4">
                <label class="flex items-center justify-between gap-4 p-4 bg-white/[.02] border border-white/[.04] rounded-xl cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-neon-blue/10 flex items-center justify-center flex-shrink-0">
                            <i class="ri-shopping-bag-3-line text-neon-blue text-sm"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-white">New Order Notifications</div>
                            <div class="text-[12px] text-gray-500">Receive an email when a new order is placed</div>
                        </div>
                    </div>
                    <input type="checkbox" name="order_email_notify" value="1"
                           <?= $settings['order_email_notify'] ? 'checked' : '' ?>
                           class="w-5 h-5 rounded border-white/20 bg-dark-800 text-neon-blue focus:ring-neon-blue/50">
                </label>

                <label class="flex items-center justify-between gap-4 p-4 bg-white/[.02] border border-white/[.04] rounded-xl cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-yellow-500/10 flex items-center justify-center flex-shrink-0">
                            <i class="ri-error-warning-line text-yellow-400 text-sm"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-white">Low Stock Alerts</div>
                            <div class="text-[12px] text-gray-500">Get notified when products fall below threshold</div>
                        </div>
                    </div>
                    <input type="checkbox" name="stock_email_notify" value="1"
                           <?= $settings['stock_email_notify'] ? 'checked' : '' ?>
                           class="w-5 h-5 rounded border-white/20 bg-dark-800 text-neon-blue focus:ring-neon-blue/50">
                </label>
            </div>
        </div>

        <div class="bg-dark-900 border border-red-500/10 rounded-2xl p-6 space-y-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center">
                    <i class="ri-tools-line text-red-400 text-lg"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-white">Maintenance Mode</h2>
                    <p class="text-[12px] text-gray-500">Temporarily disable the storefront for visitors</p>
                </div>
            </div>

            <label class="flex items-center justify-between gap-4 p-4 bg-red-500/[.03] border border-red-500/10 rounded-xl cursor-pointer">
                <div>
                    <div class="text-sm font-medium text-white">Enable Maintenance Mode</div>
                    <div class="text-[12px] text-gray-500">Visitors will see a "Coming Soon" page. Admin panel remains accessible.</div>
                </div>
                <input type="checkbox" name="maintenance_mode" value="1"
                       <?= $settings['maintenance_mode'] ? 'checked' : '' ?>
                       class="w-5 h-5 rounded border-white/20 bg-dark-800 text-red-400 focus:ring-red-400/50">
            </label>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl text-sm font-bold text-dark-950 hover:opacity-90 transition-opacity">
                <i class="ri-save-line mr-1.5"></i> Save Changes
            </button>
        </div>
    </form>

    <!-- ===== SYSTEM TAB ===== -->
    <?php elseif ($activeTab === 'system'): ?>
    <?php
    // Gather system info
    $dbVersion  = '';
    try { $dbVersion = getDB()->query("SELECT VERSION()")->fetchColumn(); } catch (Exception $ex) {}
    $phpVersion = phpversion();
    $serverSW   = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    $dbSize     = 0;
    try {
        $row = getDB()->query("SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = '" . DB_NAME . "'")->fetch();
        $dbSize = (float)($row['size'] ?? 0);
    } catch (Exception $ex) {}

    $tableCounts = [];
    try {
        $db = getDB();
        foreach (['products','orders','users','contact_messages','newsletter','categories'] as $t) {
            $tableCounts[$t] = (int) $db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        }
    } catch (Exception $ex) {}
    ?>

    <div class="space-y-6">

        <!-- Environment info -->
        <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-neon-cyan/10 flex items-center justify-center">
                    <i class="ri-server-line text-neon-cyan text-lg"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-white">System Information</h2>
                    <p class="text-[12px] text-gray-500">Server and application environment details</p>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <?php
                $sysInfo = [
                    ['PHP Version',    $phpVersion,            'ri-code-s-slash-line', 'text-neon-blue bg-neon-blue/10'],
                    ['MySQL Version',  $dbVersion ?: 'N/A',    'ri-database-2-line',   'text-neon-purple bg-neon-purple/10'],
                    ['Web Server',     $serverSW,              'ri-global-line',       'text-neon-cyan bg-neon-cyan/10'],
                    ['Database Size',  $dbSize > 0 ? number_format($dbSize / 1048576, 2) . ' MB' : 'N/A', 'ri-hard-drive-2-line', 'text-yellow-400 bg-yellow-500/10'],
                    ['App Environment', strtoupper(APP_ENV),   'ri-flask-line',        'text-green-400 bg-green-500/10'],
                    ['App URL',        APP_URL,                'ri-link',              'text-gray-400 bg-white/5'],
                ];
                foreach ($sysInfo as [$label, $value, $icon, $cls]):
                ?>
                <div class="flex items-center gap-3 bg-white/[.02] border border-white/[.04] rounded-xl p-3.5">
                    <span class="w-8 h-8 rounded-lg <?= $cls ?> flex items-center justify-center flex-shrink-0 text-sm">
                        <i class="<?= $icon ?>"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="text-[11px] text-gray-500"><?= $label ?></div>
                        <div class="text-sm text-white font-medium truncate"><?= e($value) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Database records -->
        <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-neon-purple/10 flex items-center justify-center">
                    <i class="ri-database-2-line text-neon-purple text-lg"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-white">Database Overview</h2>
                    <p class="text-[12px] text-gray-500">Record counts for each table</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <?php
                $tableInfo = [
                    'products'         => ['Products',   'ri-headphone-line',        'neon-blue'],
                    'orders'           => ['Orders',     'ri-shopping-bag-3-line',   'neon-purple'],
                    'users'            => ['Users',      'ri-group-line',            'neon-pink'],
                    'contact_messages' => ['Messages',   'ri-message-3-line',        'neon-cyan'],
                    'newsletter'       => ['Newsletter', 'ri-mail-send-line',        'yellow-400'],
                    'categories'       => ['Categories', 'ri-folders-line',          'green-400'],
                ];
                foreach ($tableInfo as $table => [$label, $icon, $color]):
                ?>
                <div class="bg-white/[.02] border border-white/[.04] rounded-xl p-4 text-center">
                    <i class="<?= $icon ?> text-<?= $color ?> text-lg block mb-2"></i>
                    <div class="font-display font-bold text-white text-xl"><?= $tableCounts[$table] ?? 0 ?></div>
                    <div class="text-[11px] text-gray-500 mt-0.5"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick links -->
        <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-6">
            <h2 class="font-semibold text-white mb-4">Quick Links</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <a href="<?= e($basePath) ?>/admin/profile.php"
                   class="flex items-center gap-3 p-3.5 bg-white/[.02] border border-white/[.04] rounded-xl hover:border-white/10 transition-all group">
                    <i class="ri-user-settings-line text-neon-blue group-hover:text-white transition-colors"></i>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Edit Profile</span>
                </a>
                <a href="<?= e($basePath) ?>/admin/analytics.php"
                   class="flex items-center gap-3 p-3.5 bg-white/[.02] border border-white/[.04] rounded-xl hover:border-white/10 transition-all group">
                    <i class="ri-line-chart-line text-neon-purple group-hover:text-white transition-colors"></i>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">View Analytics</span>
                </a>
                <a href="<?= e($basePath) ?>/" target="_blank"
                   class="flex items-center gap-3 p-3.5 bg-white/[.02] border border-white/[.04] rounded-xl hover:border-white/10 transition-all group">
                    <i class="ri-store-2-line text-green-400 group-hover:text-white transition-colors"></i>
                    <span class="text-sm text-gray-300 group-hover:text-white transition-colors">Visit Store</span>
                </a>
                <a href="<?= e($basePath) ?>/auth/logout.php"
                   class="flex items-center gap-3 p-3.5 bg-white/[.02] border border-red-500/10 rounded-xl hover:border-red-500/20 transition-all group">
                    <i class="ri-logout-box-r-line text-red-400"></i>
                    <span class="text-sm text-gray-300 group-hover:text-red-300 transition-colors">Sign Out</span>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
