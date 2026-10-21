<?php
/**
 * Admin — Settings
 * Store configuration saved in the site_settings table (see settingDefaults()).
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/migrations.php';
requireAdmin();

$basePath = rtrim(APP_URL, '/');
$db       = getDB();
$errors   = [];
$tabs = [
    'general'       => ['icon' => 'ri-store-2-line',             'label' => 'General'],
    'commerce'      => ['icon' => 'ri-money-dollar-circle-line', 'label' => 'Commerce'],
    'payments'      => ['icon' => 'ri-wallet-3-line',            'label' => 'Payments'],
    'marketing'     => ['icon' => 'ri-megaphone-line',           'label' => 'Marketing'],
    'notifications' => ['icon' => 'ri-notification-3-line',      'label' => 'Notifications'],
    'system'        => ['icon' => 'ri-server-line',              'label' => 'System'],
];

$activeTab = $_GET['tab'] ?? 'general';
if (!isset($tabs[$activeTab])) $activeTab = 'general';

/** Posted text value, trimmed and capped. */
function posted(string $key, int $max = 255): string {
    return cleanText($_POST[$key] ?? '', $max);
}

/** Posted number clamped to a range, as a string for storage. */
function postedNumber(string $key, float $min, float $max): string {
    $value = (float) str_replace(',', '', (string) ($_POST[$key] ?? 0));
    return (string) max($min, min($max, round($value, 2)));
}

// ============ SAVE ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? '';
    $new    = [];

    if (!validateCsrf()) {
        $errors[] = 'Invalid security token. Please try again.';
    } elseif ($action === 'general') {
        $new = [
            'store_name'       => posted('store_name', 100),
            'store_tagline'    => posted('store_tagline', 300),
            'store_email'      => posted('store_email'),
            'store_phone'      => posted('store_phone', 40),
            'store_address'    => posted('store_address'),
            'support_hours'    => posted('support_hours', 100),
            'whatsapp_number'  => preg_replace('/[^0-9+]/', '', posted('whatsapp_number', 20)),
            'social_facebook'  => posted('social_facebook'),
            'social_instagram' => posted('social_instagram'),
            'social_x'         => posted('social_x'),
            'social_tiktok'    => posted('social_tiktok'),
            'social_youtube'   => posted('social_youtube'),
        ];
        if (mb_strlen($new['store_name']) < 2) $errors[] = 'Store name is required.';
        if (!isValidEmail($new['store_email'])) $errors[] = 'A valid contact email is required.';
        foreach (['social_facebook', 'social_instagram', 'social_x', 'social_tiktok', 'social_youtube'] as $key) {
            if ($new[$key] !== '' && !preg_match('#^https://#i', $new[$key])) $errors[] = 'Social links must start with https://';
        }
    } elseif ($action === 'commerce') {
        $new = [
            'currency_symbol'       => posted('currency_symbol', 5),
            'currency_code'         => strtoupper(posted('currency_code', 3)),
            'currency_decimals'     => (string) max(0, min(3, (int) ($_POST['currency_decimals'] ?? 2))),
            'tax_label'             => posted('tax_label', 20) ?: 'Tax',
            'tax_rate'              => postedNumber('tax_rate', 0, 100),
            'tax_inclusive'         => isset($_POST['tax_inclusive']) ? '1' : '0',
            'shipping_flat_rate'    => postedNumber('shipping_flat_rate', 0, 1000000),
            'free_shipping_minimum' => postedNumber('free_shipping_minimum', 0, 100000000),
            'delivery_estimate'     => posted('delivery_estimate', 60),
            'default_country'       => posted('default_country', 100),
            'return_days'           => (string) max(0, min(365, (int) ($_POST['return_days'] ?? 0))),
            'warranty_months'       => (string) max(0, min(120, (int) ($_POST['warranty_months'] ?? 0))),
            'governing_law'         => posted('governing_law', 100),
            'low_stock_threshold'   => (string) max(1, (int) ($_POST['low_stock_threshold'] ?? 10)),
            'orders_per_page'       => (string) max(5, min(100, (int) ($_POST['orders_per_page'] ?? 20))),
        ];
        if ($new['currency_symbol'] === '') $errors[] = 'Currency symbol is required.';
        if (!preg_match('/^[A-Z]{3}$/', $new['currency_code'])) $errors[] = 'Currency code must be 3 letters (e.g. KES, USD).';
        if ($new['delivery_estimate'] === '') $errors[] = 'Delivery estimate is required (e.g. "1–3 business days").';
        if ($new['default_country'] === '') $errors[] = 'Default country is required.';
        if (settingOn('pay_mpesa_enabled') && $new['currency_code'] !== 'KES') {
            $errors[] = 'M-Pesa is switched on, and it only accepts KES. Switch M-Pesa off first (Payments tab) or keep KES.';
        }
    } elseif ($action === 'payments') {
        $new = [
            'pay_cod_enabled'     => isset($_POST['pay_cod_enabled']) ? '1' : '0',
            'pay_mpesa_enabled'   => isset($_POST['pay_mpesa_enabled']) ? '1' : '0',
            'mpesa_type'          => in_array($_POST['mpesa_type'] ?? '', ['paybill', 'till', 'send_money'], true) ? $_POST['mpesa_type'] : 'paybill',
            'mpesa_number'        => preg_replace('/[^0-9]/', '', posted('mpesa_number', 20)),
            'mpesa_account_name'  => posted('mpesa_account_name', 100),
            'pay_bank_enabled'    => isset($_POST['pay_bank_enabled']) ? '1' : '0',
            'bank_name'           => posted('bank_name', 100),
            'bank_account_name'   => posted('bank_account_name', 100),
            'bank_account_number' => posted('bank_account_number', 40),
            'bank_branch'         => posted('bank_branch', 100),
            'bank_swift'          => strtoupper(posted('bank_swift', 11)),
        ];
        if ($new['pay_mpesa_enabled'] === '1') {
            if (strlen($new['mpesa_number']) < 5) $errors[] = 'Enter your M-Pesa Paybill, Till or phone number.';
            if (setting('currency_code') !== 'KES') $errors[] = 'M-Pesa only accepts KES. Set the store currency to KES (Commerce tab) and update product prices first.';
        }
        if ($new['pay_bank_enabled'] === '1' && ($new['bank_name'] === '' || $new['bank_account_name'] === '' || $new['bank_account_number'] === '')) {
            $errors[] = 'Bank transfer needs the bank name, account name and account number.';
        }
        if ($new['pay_cod_enabled'] !== '1' && $new['pay_mpesa_enabled'] !== '1' && $new['pay_bank_enabled'] !== '1') {
            $errors[] = 'Keep at least one payment method switched on, or customers cannot check out.';
        }
    } elseif ($action === 'marketing') {
        $endsAt = posted('promo_ends_at', 20);
        $new = [
            'promo_enabled'     => isset($_POST['promo_enabled']) ? '1' : '0',
            'promo_title'       => posted('promo_title', 80),
            'promo_text'        => posted('promo_text', 300),
            'promo_code'        => strtoupper(posted('promo_code', 40)),
            'promo_ends_at'     => $endsAt !== '' ? str_replace('T', ' ', $endsAt) : '',
            'newsletter_coupon' => strtoupper(posted('newsletter_coupon', 40)),
            'show_testimonials' => isset($_POST['show_testimonials']) ? '1' : '0',
        ];
        if ($new['promo_enabled'] === '1' && $new['promo_title'] === '') $errors[] = 'Give the promotion a title.';
        if ($new['promo_ends_at'] !== '' && !strtotime($new['promo_ends_at'])) $errors[] = 'Promotion end date is not valid.';
        foreach (['promo_code' => 'Promotion', 'newsletter_coupon' => 'Newsletter'] as $key => $label) {
            if ($new[$key] === '') continue;
            $check = $db->prepare('SELECT is_active FROM coupons WHERE code = ?');
            $check->execute([$new[$key]]);
            $found = $check->fetch();
            if (!$found) $errors[] = "$label coupon \"{$new[$key]}\" does not exist yet — create it in Coupons first.";
            elseif (!$found['is_active']) $errors[] = "$label coupon \"{$new[$key]}\" is switched off in Coupons.";
        }
    } elseif ($action === 'notifications') {
        $new = [
            'order_email_notify' => isset($_POST['order_email_notify']) ? '1' : '0',
            'stock_email_notify' => isset($_POST['stock_email_notify']) ? '1' : '0',
            'admin_notify_email' => posted('admin_notify_email'),
            'maintenance_mode'   => isset($_POST['maintenance_mode']) ? '1' : '0',
        ];
        if ($new['admin_notify_email'] !== '' && !isValidEmail($new['admin_notify_email'])) $errors[] = 'Notification email is not valid.';
    } elseif ($action === 'migrate') {
        try {
            $applied = runMigrations($db);
            setFlash('success', $applied ? 'Database updated: ' . implode('; ', $applied) . '.' : 'Database is already up to date.');
        } catch (Exception $e) {
            setFlash('error', 'Database update failed: ' . $e->getMessage());
        }
        redirect($basePath . '/admin/settings.php?tab=system');
    } elseif ($action === 'test_email') {
        $to = adminNotifyEmail();
        $sent = sendMail($to, 'Test email from ' . storeName(), emailLayout('Email is working', '<p style="font-size:14px">This test was sent from Admin → Settings at ' . e(date('Y-m-d H:i')) . '.</p>'));
        setFlash($sent ? 'success' : 'error', $sent
            ? (MAIL_TRANSPORT === 'log' ? "Test email written to logs/mail.log (MAIL_TRANSPORT is 'log')." : "Test email sent to $to.")
            : 'Sending failed — check the SMTP settings in includes/config.local.php and logs/error.log.');
        redirect($basePath . '/admin/settings.php?tab=notifications');
    }

    if ($new && !$errors) {
        saveSettings($db, $new);
        setFlash('success', 'Settings saved.');
        redirect($basePath . '/admin/settings.php?tab=' . urlencode($action));
    }
    if ($new) $activeTab = $action;
}

// Values for the form: what was just posted (on error) or what is saved
$s = array_merge(settings(), $errors && !empty($new) ? $new : []);

$adminPageTitle = 'Settings';
require_once __DIR__ . '/includes/header.php';

// Input helpers
$inp = 'w-full px-4 py-3 bg-dark-800 border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors';
$card = 'bg-dark-900 border border-white/[.06] rounded-2xl p-6 space-y-5';

function cardHeader(string $icon, string $tone, string $title, string $subtitle): string {
    return '<div class="flex items-center gap-3 mb-2"><div class="w-10 h-10 rounded-xl ' . $tone . ' flex items-center justify-center"><i class="' . $icon . ' text-lg"></i></div>'
         . '<div><h2 class="font-semibold text-white">' . $title . '</h2><p class="text-[12px] text-gray-500">' . $subtitle . '</p></div></div>';
}

function textField(string $name, string $label, array $s, string $inp, string $attrs = '', string $hint = ''): string {
    return '<div><label for="f-' . $name . '" class="block text-xs font-medium text-gray-400 mb-2">' . $label . '</label>'
         . '<input id="f-' . $name . '" name="' . $name . '" value="' . e((string) $s[$name]) . '" class="' . $inp . '" ' . $attrs . '>'
         . ($hint ? '<p class="text-[11px] text-gray-600 mt-1.5">' . $hint . '</p>' : '') . '</div>';
}

function toggleRow(string $name, string $title, string $text, array $s, string $icon = '', string $tone = ''): string {
    return '<label class="flex items-center justify-between gap-4 p-4 bg-white/[.02] border border-white/[.04] rounded-xl cursor-pointer">'
         . '<div class="flex items-center gap-3">' . ($icon ? '<div class="w-9 h-9 rounded-lg ' . $tone . ' flex items-center justify-center flex-shrink-0"><i class="' . $icon . ' text-sm"></i></div>' : '')
         . '<div><div class="text-sm font-medium text-white">' . $title . '</div><div class="text-[12px] text-gray-500">' . $text . '</div></div></div>'
         . '<input type="checkbox" name="' . $name . '" value="1" ' . ($s[$name] === '1' ? 'checked' : '') . ' class="w-5 h-5 rounded border-white/20 bg-dark-800 text-neon-blue focus:ring-neon-blue/50"></label>';
}

$saveButton = '<div class="flex justify-end"><button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl text-sm font-bold text-dark-950 hover:opacity-90 transition-opacity"><i class="ri-save-line mr-1.5"></i> Save Changes</button></div>';
?>

<div class="max-w-4xl mx-auto">

    <!-- Error block -->
    <?php if (!empty($errors)): ?>
    <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/20 space-y-1">
        <?php foreach ($errors as $err): ?>
        <div class="flex items-center gap-2 text-sm text-red-400">
            <i class="ri-error-warning-line flex-shrink-0"></i> <?= e($err) ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 bg-dark-900 border border-white/[.06] rounded-xl p-1 w-fit flex-wrap">
        <?php foreach ($tabs as $key => $tab): ?>
        <a href="?tab=<?= $key ?>"
           class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-all
                  <?= $activeTab === $key ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-white' ?>">
            <i class="<?= $tab['icon'] ?> text-sm"></i>
            <?= $tab['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($activeTab === 'general'): ?>
    <!-- ===== GENERAL ===== -->
    <form method="POST" class="space-y-6">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="general">

        <div class="<?= $card ?>">
            <?= cardHeader('ri-store-2-line', 'bg-neon-blue/10 text-neon-blue', 'Store Information', 'Shown in the header, footer, emails and legal pages') ?>
            <div class="grid sm:grid-cols-2 gap-5">
                <?= textField('store_name', 'Store Name', $s, $inp, 'required maxlength="100"') ?>
                <?= textField('store_email', 'Contact Email', $s, $inp, 'type="email" required') ?>
            </div>
            <?= textField('store_tagline', 'Tagline', $s, $inp, 'maxlength="300"', 'Footer blurb and default search-engine description.') ?>
            <div class="grid sm:grid-cols-2 gap-5">
                <?= textField('store_phone', 'Phone Number', $s, $inp, 'type="tel" placeholder="+254 712 345 678"') ?>
                <?= textField('support_hours', 'Support Hours', $s, $inp, 'placeholder="Mon–Sat, 8 AM – 6 PM"') ?>
            </div>
            <?= textField('store_address', 'Address', $s, $inp, 'placeholder="Building, street, town"') ?>
        </div>

        <div class="<?= $card ?>">
            <?= cardHeader('ri-share-line', 'bg-neon-pink/10 text-neon-pink', 'Social & Chat', 'Leave a field empty to hide that icon') ?>
            <?= textField('whatsapp_number', 'WhatsApp Number', $s, $inp, 'placeholder="254712345678"', 'International format, digits only. Adds a WhatsApp chat button to the store.') ?>
            <div class="grid sm:grid-cols-2 gap-5">
                <?= textField('social_facebook', 'Facebook URL', $s, $inp, 'type="url" placeholder="https://facebook.com/yourpage"') ?>
                <?= textField('social_instagram', 'Instagram URL', $s, $inp, 'type="url" placeholder="https://instagram.com/yourhandle"') ?>
                <?= textField('social_x', 'X (Twitter) URL', $s, $inp, 'type="url" placeholder="https://x.com/yourhandle"') ?>
                <?= textField('social_tiktok', 'TikTok URL', $s, $inp, 'type="url" placeholder="https://tiktok.com/@yourhandle"') ?>
                <?= textField('social_youtube', 'YouTube URL', $s, $inp, 'type="url" placeholder="https://youtube.com/@yourchannel"') ?>
            </div>
        </div>
        <?= $saveButton ?>
    </form>

    <?php elseif ($activeTab === 'commerce'): ?>
    <!-- ===== COMMERCE ===== -->
    <form method="POST" class="space-y-6">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="commerce">

        <div class="<?= $card ?>">
            <?= cardHeader('ri-money-dollar-circle-line', 'bg-green-500/10 text-green-400', 'Currency & Tax', 'Kenya example: KSh · KES · 0 decimals · VAT 16% included') ?>
            <div class="grid sm:grid-cols-3 gap-5">
                <?= textField('currency_symbol', 'Currency Symbol', $s, $inp, 'required maxlength="5" placeholder="KSh"') ?>
                <?= textField('currency_code', 'Currency Code', $s, $inp, 'required maxlength="3" placeholder="KES"') ?>
                <?= textField('currency_decimals', 'Decimals', $s, $inp, 'type="number" min="0" max="3"') ?>
            </div>
            <div class="grid sm:grid-cols-3 gap-5">
                <?= textField('tax_label', 'Tax Name', $s, $inp, 'maxlength="20" placeholder="VAT"') ?>
                <?= textField('tax_rate', 'Tax Rate (%)', $s, $inp, 'type="number" min="0" max="100" step="0.01"') ?>
                <div class="flex items-end">
                    <label class="flex items-center gap-3 p-3 w-full bg-white/[.02] border border-white/[.04] rounded-xl cursor-pointer">
                        <input type="checkbox" name="tax_inclusive" value="1" <?= $s['tax_inclusive'] === '1' ? 'checked' : '' ?> class="w-5 h-5 rounded border-white/20 bg-dark-800 text-neon-blue focus:ring-neon-blue/50">
                        <span class="text-sm text-gray-300">Prices already include tax</span>
                    </label>
                </div>
            </div>
            <p class="text-[11px] text-gray-600">Changing the currency does not convert prices — update product prices in Products afterwards.</p>
        </div>

        <div class="<?= $card ?>">
            <?= cardHeader('ri-truck-line', 'bg-neon-purple/10 text-neon-purple', 'Shipping & Delivery', 'Used at checkout and in the store banners') ?>
            <div class="grid sm:grid-cols-2 gap-5">
                <?= textField('shipping_flat_rate', 'Flat Shipping Rate', $s, $inp, 'type="number" min="0" step="0.01"', 'Set to 0 for free shipping on every order.') ?>
                <?= textField('free_shipping_minimum', 'Free Shipping From', $s, $inp, 'type="number" min="0" step="0.01"', 'Order value that ships free. 0 turns this off.') ?>
                <?= textField('delivery_estimate', 'Delivery Estimate', $s, $inp, 'required placeholder="1–3 business days"') ?>
                <?= textField('default_country', 'Default Country', $s, $inp, 'required placeholder="Kenya"') ?>
            </div>
        </div>

        <div class="<?= $card ?>">
            <?= cardHeader('ri-shield-check-line', 'bg-neon-cyan/10 text-neon-cyan', 'Policies', 'Shown on the homepage, product pages and Terms of Service') ?>
            <div class="grid sm:grid-cols-3 gap-5">
                <?= textField('return_days', 'Return Window (days)', $s, $inp, 'type="number" min="0" max="365"', '0 hides the returns promise.') ?>
                <?= textField('warranty_months', 'Warranty (months)', $s, $inp, 'type="number" min="0" max="120"', '0 hides the warranty promise.') ?>
                <?= textField('governing_law', 'Governing Law', $s, $inp, 'placeholder="the Republic of Kenya"', 'Used in the Terms of Service.') ?>
            </div>
        </div>

        <div class="<?= $card ?>">
            <?= cardHeader('ri-settings-3-line', 'bg-yellow-500/10 text-yellow-400', 'Inventory & Display', 'Stock alerts and pagination') ?>
            <div class="grid sm:grid-cols-2 gap-5">
                <?= textField('low_stock_threshold', 'Low Stock Threshold', $s, $inp, 'type="number" min="1"', 'At or below this, products show "Only N left" and trigger alerts.') ?>
                <?= textField('orders_per_page', 'Orders Per Page', $s, $inp, 'type="number" min="5" max="100"') ?>
            </div>
        </div>
        <?= $saveButton ?>
    </form>

    <?php elseif ($activeTab === 'payments'): ?>
    <!-- ===== PAYMENTS ===== -->
    <form method="POST" class="space-y-6">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="payments">

        <div class="p-4 rounded-xl bg-neon-blue/[.04] border border-neon-blue/10 text-[12px] text-gray-400">
            <i class="ri-information-line text-neon-blue"></i>
            These are manual payment methods: the customer pays, sends you the transaction code, and you mark the order as paid in Orders.
            Card payments need a payment gateway (e.g. Pesapal, Flutterwave, Stripe) and are not built in.
        </div>

        <div class="<?= $card ?>">
            <?= cardHeader('ri-hand-coin-line', 'bg-green-500/10 text-green-400', 'Cash on Delivery', 'Customer pays the courier') ?>
            <?= toggleRow('pay_cod_enabled', 'Offer Cash on Delivery', 'Shown as a payment option at checkout', $s) ?>
        </div>

        <div class="<?= $card ?>">
            <?= cardHeader('ri-smartphone-line', 'bg-green-600/10 text-green-500', 'M-Pesa', 'Requires the store currency to be KES') ?>
            <?= toggleRow('pay_mpesa_enabled', 'Offer M-Pesa', 'Customers get step-by-step payment instructions with your number', $s) ?>
            <div class="grid sm:grid-cols-3 gap-5">
                <div>
                    <label for="f-mpesa_type" class="block text-xs font-medium text-gray-400 mb-2">Type</label>
                    <select id="f-mpesa_type" name="mpesa_type" class="<?= $inp ?>">
                        <?php foreach (['paybill' => 'Paybill (order no. as account)', 'till' => 'Buy Goods (Till number)', 'send_money' => 'Send Money (phone number)'] as $v => $l): ?>
                        <option value="<?= $v ?>" class="bg-dark-800" <?= $s['mpesa_type'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?= textField('mpesa_number', 'Paybill / Till / Phone No.', $s, $inp, 'inputmode="numeric" placeholder="123456"') ?>
                <?= textField('mpesa_account_name', 'Name Customers See', $s, $inp, 'placeholder="My Shop Ltd"') ?>
            </div>
        </div>

        <div class="<?= $card ?>">
            <?= cardHeader('ri-bank-line', 'bg-blue-500/10 text-blue-400', 'Bank Transfer', 'Customers use their order number as the reference') ?>
            <?= toggleRow('pay_bank_enabled', 'Offer Bank Transfer', 'Your account details are shown after the order is placed', $s) ?>
            <div class="grid sm:grid-cols-2 gap-5">
                <?= textField('bank_name', 'Bank Name', $s, $inp) ?>
                <?= textField('bank_account_name', 'Account Name', $s, $inp) ?>
                <?= textField('bank_account_number', 'Account Number', $s, $inp) ?>
                <?= textField('bank_branch', 'Branch', $s, $inp) ?>
                <?= textField('bank_swift', 'SWIFT / BIC (optional)', $s, $inp, 'maxlength="11"') ?>
            </div>
        </div>
        <?= $saveButton ?>
    </form>

    <?php elseif ($activeTab === 'marketing'): ?>
    <!-- ===== MARKETING ===== -->
    <form method="POST" class="space-y-6">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="marketing">

        <div class="<?= $card ?>">
            <?= cardHeader('ri-megaphone-line', 'bg-red-500/10 text-red-400', 'Homepage Promotion', 'A banner with a real countdown — it hides itself when the end date passes') ?>
            <?= toggleRow('promo_enabled', 'Show the promotion banner', 'Only switch on when the offer is real', $s) ?>
            <div class="grid sm:grid-cols-2 gap-5">
                <?= textField('promo_title', 'Title', $s, $inp, 'maxlength="80" placeholder="Holiday Sale"') ?>
                <?= textField('promo_code', 'Coupon Code', $s, $inp, 'maxlength="40" placeholder="HOLIDAY10"', 'Must exist in Coupons. Leave empty for no code.') ?>
            </div>
            <?= textField('promo_text', 'Text', $s, $inp, 'maxlength="300"') ?>
            <?= textField('promo_ends_at', 'Ends At', array_merge($s, ['promo_ends_at' => $s['promo_ends_at'] ? date('Y-m-d\TH:i', strtotime($s['promo_ends_at'])) : '']), $inp, 'type="datetime-local"', 'Leave empty for an offer without a deadline (no countdown).') ?>
        </div>

        <div class="<?= $card ?>">
            <?= cardHeader('ri-mail-star-line', 'bg-yellow-500/10 text-yellow-400', 'Newsletter & Reviews', 'Welcome offer and homepage testimonials') ?>
            <?= textField('newsletter_coupon', 'Welcome Coupon', $s, $inp, 'maxlength="40" placeholder="WELCOME10"', 'Emailed to new subscribers. Leave empty to send no code.') ?>
            <?= toggleRow('show_testimonials', 'Show testimonials on the homepage', 'Manage them in Testimonials — publish genuine customer reviews only', $s) ?>
        </div>
        <?= $saveButton ?>
    </form>

    <?php elseif ($activeTab === 'notifications'): ?>
    <!-- ===== NOTIFICATIONS ===== -->
    <form method="POST" class="space-y-6">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="notifications">

        <div class="<?= $card ?>">
            <?= cardHeader('ri-notification-3-line', 'bg-neon-cyan/10 text-neon-cyan', 'Email Notifications', 'Customers always get order confirmations and status updates') ?>
            <?= textField('admin_notify_email', 'Send store alerts to', $s, $inp, 'type="email" placeholder="' . e(setting('store_email')) . '"', 'Leave empty to use the store contact email.') ?>
            <div class="space-y-4">
                <?= toggleRow('order_email_notify', 'New Order Notifications', 'Receive an email when a new order is placed', $s, 'ri-shopping-bag-3-line', 'bg-neon-blue/10 text-neon-blue') ?>
                <?= toggleRow('stock_email_notify', 'Low Stock Alerts', 'Get notified when a sale drops a product to the low-stock threshold', $s, 'ri-error-warning-line', 'bg-yellow-500/10 text-yellow-400') ?>
            </div>
        </div>

        <div class="bg-dark-900 border border-red-500/10 rounded-2xl p-6 space-y-5">
            <?= cardHeader('ri-tools-line', 'bg-red-500/10 text-red-400', 'Maintenance Mode', 'Temporarily close the storefront') ?>
            <?= toggleRow('maintenance_mode', 'Enable Maintenance Mode', 'Visitors see a "We\'ll be right back" page. Admins can still browse and sign in.', $s) ?>
        </div>
        <?= $saveButton ?>
    </form>

    <form method="POST" class="mt-6 <?= $card ?>">
        <?= csrfField() ?>
        <input type="hidden" name="_action" value="test_email">
        <?= cardHeader('ri-mail-send-line', 'bg-neon-purple/10 text-neon-purple', 'Email Delivery', 'Transport: ' . e(MAIL_TRANSPORT) . (MAIL_TRANSPORT === 'smtp' ? ' · ' . e(SMTP_HOST) . ':' . (int) SMTP_PORT : '')) ?>
        <?php if (MAIL_TRANSPORT === 'log'): ?>
        <p class="text-[12px] text-yellow-400/90"><i class="ri-information-line"></i> Emails are being written to <code>logs/mail.log</code>, not sent. Set MAIL_TRANSPORT and SMTP details in <code>includes/config.local.php</code> before going live.</p>
        <?php endif; ?>
        <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold border border-neon-purple/30 text-neon-purple hover:bg-neon-purple/10 transition-colors">
            <i class="ri-send-plane-line mr-1"></i> Send a test email to <?= e(adminNotifyEmail()) ?>
        </button>
    </form>

    <?php elseif ($activeTab === 'system'): ?>
    <!-- ===== SYSTEM ===== -->
    <?php
    $dbVersion = '';
    try { $dbVersion = $db->query("SELECT VERSION()")->fetchColumn(); } catch (Exception $ex) {}
    $dbSize = 0;
    try {
        $row = $db->query("SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = DATABASE()")->fetch();
        $dbSize = (float) ($row['size'] ?? 0);
    } catch (Exception $ex) {}
    $tableCounts = [];
    try {
        foreach (['products', 'orders', 'users', 'contact_messages', 'newsletter', 'coupons'] as $t) {
            $tableCounts[$t] = (int) $db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        }
    } catch (Exception $ex) {}
    $pending = [];
    try { $pending = pendingMigrations($db); } catch (Exception $ex) {}
    ?>

    <div class="space-y-6">
        <!-- Database updates -->
        <form method="POST" class="<?= $card ?> <?= $pending ? 'border-yellow-500/20' : '' ?>">
            <?= csrfField() ?>
            <input type="hidden" name="_action" value="migrate">
            <?= cardHeader('ri-database-2-line', $pending ? 'bg-yellow-500/10 text-yellow-400' : 'bg-green-500/10 text-green-400', 'Database Updates', $pending ? count($pending) . ' update(s) waiting' : 'Your database is up to date') ?>
            <?php if ($pending): ?>
            <ul class="text-sm text-gray-400 list-disc list-inside"><?php foreach ($pending as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
            <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-yellow-400 text-dark-950 hover:opacity-90"><i class="ri-play-line mr-1"></i> Apply updates</button>
            <p class="text-[11px] text-gray-600">Back up your database first (phpMyAdmin → Export).</p>
            <?php endif; ?>
        </form>

        <!-- Environment info -->
        <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-6">
            <?= cardHeader('ri-server-line', 'bg-neon-cyan/10 text-neon-cyan', 'System Information', 'Server and application environment details') ?>
            <div class="grid sm:grid-cols-2 gap-4 mt-4">
                <?php
                $sysInfo = [
                    ['PHP Version',     phpversion(),            'ri-code-s-slash-line', 'text-neon-blue bg-neon-blue/10'],
                    ['MySQL Version',   $dbVersion ?: 'N/A',     'ri-database-2-line',   'text-neon-purple bg-neon-purple/10'],
                    ['Web Server',      $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown', 'ri-global-line', 'text-neon-cyan bg-neon-cyan/10'],
                    ['Database Size',   $dbSize > 0 ? number_format($dbSize / 1048576, 2) . ' MB' : 'N/A', 'ri-hard-drive-2-line', 'text-yellow-400 bg-yellow-500/10'],
                    ['App Environment', strtoupper(APP_ENV) . (DEMO_MODE ? ' · DEMO' : ''), 'ri-flask-line', 'text-green-400 bg-green-500/10'],
                    ['App URL',         APP_URL,                 'ri-link',              'text-gray-400 bg-white/5'],
                ];
                foreach ($sysInfo as [$label, $value, $icon, $cls]):
                ?>
                <div class="flex items-center gap-3 bg-white/[.02] border border-white/[.04] rounded-xl p-3.5">
                    <span class="w-8 h-8 rounded-lg <?= $cls ?> flex items-center justify-center flex-shrink-0 text-sm"><i class="<?= $icon ?>"></i></span>
                    <div class="min-w-0">
                        <div class="text-[11px] text-gray-500"><?= $label ?></div>
                        <div class="text-sm text-white font-medium truncate"><?= e($value) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (APP_ENV !== 'production' && !DEMO_MODE): ?>
            <p class="mt-4 text-[12px] text-yellow-400/90"><i class="ri-error-warning-line"></i> APP_ENV is not "production" — visitors may see detailed error messages. Set it in includes/config.local.php.</p>
            <?php endif; ?>
        </div>

        <!-- Database records -->
        <div class="bg-dark-900 border border-white/[.06] rounded-2xl p-6">
            <?= cardHeader('ri-database-2-line', 'bg-neon-purple/10 text-neon-purple', 'Database Overview', 'Record counts for each table') ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
                <?php
                $tableInfo = [
                    'products'         => ['Products',   'ri-headphone-line',      'text-neon-blue'],
                    'orders'           => ['Orders',     'ri-shopping-bag-3-line', 'text-neon-purple'],
                    'users'            => ['Users',      'ri-group-line',          'text-neon-pink'],
                    'contact_messages' => ['Messages',   'ri-message-3-line',      'text-neon-cyan'],
                    'newsletter'       => ['Newsletter', 'ri-mail-send-line',      'text-yellow-400'],
                    'coupons'          => ['Coupons',    'ri-coupon-3-line',       'text-green-400'],
                ];
                foreach ($tableInfo as $table => [$label, $icon, $color]):
                ?>
                <div class="bg-white/[.02] border border-white/[.04] rounded-xl p-4 text-center">
                    <i class="<?= $icon ?> <?= $color ?> text-lg block mb-2"></i>
                    <div class="font-display font-bold text-white text-xl"><?= $tableCounts[$table] ?? 0 ?></div>
                    <div class="text-[11px] text-gray-500 mt-0.5"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
