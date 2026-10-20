<?php
/**
 * Headphones1.Duka — Utility Functions
 * CSRF, sanitization, formatting, auth helpers
 */

// ============ CSRF PROTECTION ============

/**
 * Generate or retrieve the current CSRF token.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Validate the submitted CSRF token.
 */
function validateCsrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        return false;
    }
    return true;
}

// ============ INPUT SANITIZATION ============

/**
 * Escape output for safe HTML display.
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Read a text input: trimmed, control characters removed, length capped.
 * Values are stored as typed and escaped with e() when displayed.
 */
function cleanText(mixed $input, int $maxLength = 255): string {
    if (!is_scalar($input)) return '';
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', trim((string) $input)) ?? '';
    return mb_substr($text, 0, $maxLength);
}

/**
 * Validate email format.
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Encode data for a <script type="application/ld+json"> or inline JS value.
 */
function jsonForScript(mixed $data): string {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/**
 * Absolute URL for a site path ('assets/img/a.png' or '/shop.php').
 */
function absoluteUrl(string $path = ''): string {
    if (preg_match('#^https?://#i', $path)) return $path;
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * URL for a local asset with a cache-busting version (file modified time).
 */
function assetUrl(string $path): string {
    $file = BASE_PATH . '/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/') . $version;
}

/**
 * Client IP address (REMOTE_ADDR only — proxy headers are spoofable).
 */
function clientIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ============ STORE SETTINGS ============

/**
 * Default values for every store setting. Admin → Settings saves overrides
 * into the site_settings table; anything not saved falls back to these.
 */
function settingDefaults(): array {
    return [
        // Store
        'store_name'            => APP_NAME,
        'store_tagline'         => 'Studio-grade wireless headphones engineered for those who demand the best in sound, comfort, and style.',
        'store_email'           => 'support@beats3.store',
        'store_phone'           => '+1 (555) 123-4567',
        'store_address'         => '123 Audio Street, Los Angeles, CA 90001',
        'support_hours'         => 'Mon–Fri, 9 AM – 6 PM',
        'whatsapp_number'       => '',
        'social_facebook'       => '',
        'social_instagram'      => '',
        'social_x'              => '',
        'social_tiktok'         => '',
        'social_youtube'        => '',
        // Commerce
        'currency_symbol'       => '$',
        'currency_code'         => 'USD',
        'currency_decimals'     => '2',
        'tax_label'             => 'Tax',
        'tax_rate'              => '8',
        'tax_inclusive'         => '0',
        'shipping_flat_rate'    => '9.99',
        'free_shipping_minimum' => '100',
        'delivery_estimate'     => '2–5 business days',
        'default_country'       => 'United States',
        'low_stock_threshold'   => '10',
        'orders_per_page'       => '20',
        // Policies
        'return_days'           => '30',
        'warranty_months'       => '12',
        'governing_law'         => '',
        // Payments
        'pay_cod_enabled'       => '1',
        'pay_mpesa_enabled'     => '0',
        'mpesa_type'            => 'paybill',
        'mpesa_number'          => '',
        'mpesa_account_name'    => '',
        'pay_bank_enabled'      => DEMO_MODE ? '1' : '0',
        'bank_name'             => DEMO_MODE ? 'Demo Bank' : '',
        'bank_account_name'     => DEMO_MODE ? 'Beats 3 Store Ltd' : '',
        'bank_account_number'   => DEMO_MODE ? '0123456789' : '',
        'bank_branch'           => DEMO_MODE ? 'Main Branch' : '',
        'bank_swift'            => '',
        // Marketing
        'promo_enabled'         => '1',
        'promo_title'           => 'Holiday Sale',
        'promo_text'            => 'Take 10% off any order with the code below. Ends when the timer hits zero.',
        'promo_code'            => 'HOLIDAY10',
        'promo_ends_at'         => '2026-12-31 23:59',
        'newsletter_coupon'     => 'WELCOME10',
        'show_testimonials'     => '1',
        // Notifications
        'order_email_notify'    => '1',
        'stock_email_notify'    => '1',
        'admin_notify_email'    => '',
        // System
        'maintenance_mode'      => '0',
    ];
}

/**
 * All store settings (saved values merged over defaults), cached per request.
 */
function settings(bool $refresh = false): array {
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = settingDefaults();
        try {
            foreach (getDB()->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll() as $row) {
                if (array_key_exists($row['setting_key'], $cache) && $row['setting_value'] !== null) {
                    $cache[$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (Exception $e) {
            error_log('Settings load failed: ' . $e->getMessage());
        }
    }
    return $cache;
}

/**
 * A single store setting.
 */
function setting(string $key): string {
    return (string) (settings()[$key] ?? '');
}

/**
 * Boolean store setting ('1' / '0').
 */
function settingOn(string $key): bool {
    return setting($key) === '1';
}

/**
 * Save store settings (only keys that exist in settingDefaults()).
 */
function saveSettings(PDO $db, array $values): void {
    $known = settingDefaults();
    $stmt = $db->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
                          ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($values as $key => $value) {
        if (!array_key_exists($key, $known)) continue;
        $stmt->execute([$key, is_bool($value) ? ($value ? '1' : '0') : (string) $value]);
    }
    settings(true);
}

/**
 * Store name for titles, emails and legal pages.
 */
function storeName(): string {
    return setting('store_name') ?: APP_NAME;
}

/**
 * Email address that receives admin notifications.
 */
function adminNotifyEmail(): string {
    return setting('admin_notify_email') ?: setting('store_email');
}

/**
 * Social profile links that have been filled in: key => [url, icon, label].
 */
function socialLinks(): array {
    $networks = [
        'social_facebook'  => ['ri-facebook-fill', 'Facebook'],
        'social_instagram' => ['ri-instagram-line', 'Instagram'],
        'social_x'         => ['ri-twitter-x-line', 'X'],
        'social_tiktok'    => ['ri-tiktok-fill', 'TikTok'],
        'social_youtube'   => ['ri-youtube-line', 'YouTube'],
    ];
    $links = [];
    foreach ($networks as $key => [$icon, $label]) {
        $url = setting($key);
        if (preg_match('#^https?://#i', $url)) $links[$key] = [$url, $icon, $label];
    }
    return $links;
}

/**
 * WhatsApp chat link for the store number, or '' when not set.
 */
function whatsappLink(string $message = ''): string {
    $digits = preg_replace('/\D+/', '', setting('whatsapp_number'));
    if (strlen($digits) < 8) return '';
    return 'https://wa.me/' . $digits . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

/**
 * Show a maintenance page to visitors (admins and the login page stay reachable).
 */
function maintenanceGate(bool $json = false): void {
    if (!settingOn('maintenance_mode') || isAdmin()) return;
    http_response_code(503);
    header('Retry-After: 3600');
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'The store is closed for maintenance. Please try again soon.']);
        exit;
    }
    require BASE_PATH . '/includes/maintenance.php';
    exit;
}

// ============ AUTH HELPERS ============

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Check if the current user is an admin.
 */
function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Get the current user's ID or null.
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current user's name or 'Guest'.
 */
function currentUserName(): string {
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Redirect to a URL and exit.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Require authentication — redirects to login if not logged in.
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect(APP_URL . '/auth/login.php');
    }
}

/**
 * Require admin role — redirects to home if not admin.
 */
function requireAdmin(): void {
    requireAuth();
    if (!isAdmin()) {
        redirect(APP_URL . '/');
    }
}

// ============ FLASH MESSAGES ============

/**
 * Set a flash message.
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear the flash message.
 */
function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ============ FORMATTING ============

/**
 * Format an amount in the store currency ("$249.00", "KSh 34,999").
 */
function formatPrice(float|int|string|null $price): string {
    $amount   = (float) ($price ?? 0);
    $decimals = max(0, min(3, (int) setting('currency_decimals')));
    return ($amount < 0 ? '−' : '') . currencyPrefix() . number_format(abs($amount), $decimals);
}

/**
 * Currency symbol as written before an amount. Letter symbols get a
 * space ("KSh 1,000"), sign symbols do not ("$1,000").
 */
function currencyPrefix(): string {
    $symbol = setting('currency_symbol');
    return preg_match('/\p{L}$/u', $symbol) ? $symbol . ' ' : $symbol;
}

/**
 * Currency details for JavaScript price formatting.
 */
function currencyConfig(): array {
    return [
        'symbol'   => setting('currency_symbol'),
        'code'     => setting('currency_code'),
        'decimals' => max(0, min(3, (int) setting('currency_decimals'))),
    ];
}

/**
 * Shipping, tax and total for a cart subtotal, using the store settings.
 * Free shipping applies when the discounted subtotal reaches the minimum
 * (a minimum of 0 turns the free-shipping threshold off).
 */
function calculateTotals(float $subtotal, float $discount = 0): array {
    $discount = round(min(max(0, $discount), $subtotal), 2);
    $taxable  = round($subtotal - $discount, 2);

    $freeMin  = (float) setting('free_shipping_minimum');
    $flatRate = (float) setting('shipping_flat_rate');
    $shipping = ($subtotal <= 0 || ($freeMin > 0 && $taxable >= $freeMin)) ? 0.0 : $flatRate;

    $rate      = max(0, (float) setting('tax_rate')) / 100;
    $inclusive = settingOn('tax_inclusive');
    if ($inclusive) {
        $tax   = round($taxable - $taxable / (1 + $rate), 2);
        $total = $taxable + $shipping;
    } else {
        $tax   = round($taxable * $rate, 2);
        $total = $taxable + $tax + $shipping;
    }

    return [
        'subtotal'       => round($subtotal, 2),
        'discount'       => $discount,
        'shipping'       => round($shipping, 2),
        'tax'            => $tax,
        'total'          => round($total, 2),
        'tax_inclusive'  => $inclusive,
        'free_min'       => $freeMin,
        'free_remaining' => ($freeMin > 0 && $flatRate > 0 && $taxable < $freeMin) ? round($freeMin - $taxable, 2) : 0.0,
    ];
}

/**
 * Tax line label, e.g. "Tax (8%)" or "VAT (16%, included)".
 */
function taxLabel(): string {
    $rate = rtrim(rtrim(number_format((float) setting('tax_rate'), 2, '.', ''), '0'), '.');
    return (setting('tax_label') ?: 'Tax') . ' (' . $rate . '%' . (settingOn('tax_inclusive') ? ', included' : '') . ')';
}

/**
 * One-line shipping promise for banners and product pages.
 */
function shippingSummary(): string {
    $freeMin  = (float) setting('free_shipping_minimum');
    $flatRate = (float) setting('shipping_flat_rate');
    if ($flatRate <= 0) return 'Free shipping on every order';
    if ($freeMin > 0)   return 'Free shipping on orders over ' . formatPrice($freeMin);
    return 'Shipping ' . formatPrice($flatRate) . ' per order';
}

/**
 * Warranty length as text ("1-year", "6-month"), or '' when none is offered.
 */
function warrantyText(): string {
    $months = (int) setting('warranty_months');
    if ($months <= 0) return '';
    return $months % 12 === 0 ? ($months / 12) . '-year' : $months . '-month';
}

/**
 * Generate a unique order number.
 */
function generateOrderNumber(): string {
    return 'BT-' . strtoupper(date('Ymd')) . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Create a URL-safe slug from a string.
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    return preg_replace('~-+~', '-', $text);
}

// ============ CART HELPERS ============

/**
 * Get or create a cart for the current user/session.
 */
function getOrCreateCart(PDO $db): array {
    $userId = currentUserId();
    $sessionId = session_id();

    if ($userId) {
        $stmt = $db->prepare('SELECT id FROM cart WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare('SELECT id FROM cart WHERE session_id = ? AND user_id IS NULL LIMIT 1');
        $stmt->execute([$sessionId]);
    }

    $cart = $stmt->fetch();
    if ($cart) {
        return ['id' => (int) $cart['id']];
    }

    $stmt = $db->prepare('INSERT INTO cart (session_id, user_id) VALUES (?, ?)');
    $stmt->execute([$sessionId, $userId]);
    return ['id' => (int) $db->lastInsertId()];
}

/**
 * Merge a guest (session-based) cart into a user's account cart.
 * If the user already has a cart, move items from guest cart into it (summing quantities).
 * If the user has no cart, simply reassign the guest cart.
 */
function mergeGuestCartToUser(PDO $db, string $oldSessionId, int $userId): void {
    try {
        // Find guest cart
        $stmt = $db->prepare('SELECT id FROM cart WHERE session_id = ? AND user_id IS NULL LIMIT 1');
        $stmt->execute([$oldSessionId]);
        $guestCart = $stmt->fetch();
        if (!$guestCart) return;

        $guestCartId = (int) $guestCart['id'];

        // Find existing user cart
        $stmt = $db->prepare('SELECT id FROM cart WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $userCart = $stmt->fetch();

        if (!$userCart) {
            // No existing user cart — just reassign the guest cart
            $db->prepare('UPDATE cart SET user_id = ?, session_id = NULL WHERE id = ?')
               ->execute([$userId, $guestCartId]);
            return;
        }

        $userCartId = (int) $userCart['id'];

        // Merge items from guest cart into user cart
        $guestItems = $db->prepare('SELECT product_id, quantity FROM cart_items WHERE cart_id = ?');
        $guestItems->execute([$guestCartId]);

        foreach ($guestItems->fetchAll() as $item) {
            $existing = $db->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?');
            $existing->execute([$userCartId, $item['product_id']]);
            $row = $existing->fetch();

            if ($row) {
                $db->prepare('UPDATE cart_items SET quantity = quantity + ?, updated_at = NOW() WHERE id = ?')
                   ->execute([$item['quantity'], $row['id']]);
            } else {
                $db->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)')
                   ->execute([$userCartId, $item['product_id'], $item['quantity']]);
            }
        }

        // Remove guest cart and its items
        $db->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$guestCartId]);
        $db->prepare('DELETE FROM cart WHERE id = ?')->execute([$guestCartId]);
    } catch (Exception $e) {
        // Non-critical — log but don't break login/register
        error_log('Cart merge failed: ' . $e->getMessage());
    }
}

/**
 * Get the total number of items in the cart.
 */
function getCartCount(PDO $db): int {
    try {
        $userId = currentUserId();
        $sessionId = session_id();

        if ($userId) {
            $stmt = $db->prepare('SELECT SUM(ci.quantity) FROM cart_items ci JOIN cart c ON ci.cart_id = c.id WHERE c.user_id = ?');
            $stmt->execute([$userId]);
        } else {
            $stmt = $db->prepare('SELECT SUM(ci.quantity) FROM cart_items ci JOIN cart c ON ci.cart_id = c.id WHERE c.session_id = ? AND c.user_id IS NULL');
            $stmt->execute([$sessionId]);
        }
        return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// ============ PRODUCT HELPERS ============

/**
 * Price a customer pays: the sale price when it is a real discount.
 */
function effectivePrice(array $product): float {
    $price = (float) $product['price'];
    $sale  = (float) ($product['sale_price'] ?? 0);
    return ($sale > 0 && $sale < $price) ? $sale : $price;
}

/**
 * True when the product shows a sale price.
 */
function isOnSale(array $product): bool {
    return effectivePrice($product) < (float) $product['price'];
}

/**
 * Get all active products.
 */
function getActiveProducts(PDO $db, ?int $limit = null): array {
    $sql = 'SELECT * FROM products WHERE is_active = 1 ORDER BY featured DESC, created_at DESC';
    if ($limit) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

/**
 * Get a single product by slug.
 */
function getProductBySlug(PDO $db, string $slug): ?array {
    $stmt = $db->prepare('SELECT * FROM products WHERE slug = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$slug]);
    $product = $stmt->fetch();
    return $product ?: null;
}

/**
 * Get a single product by ID.
 */
function getProductById(PDO $db, int $id): ?array {
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    return $product ?: null;
}

// ============ USER DASHBOARD HELPERS ============

/**
 * Get orders for a specific user.
 */
function getUserOrders(PDO $db, int $userId, ?int $limit = null, int $offset = 0): array {
    $sql = 'SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
            FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC';
    if ($limit) {
        $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Count total orders for a user.
 */
function getUserOrderCount(PDO $db, int $userId): int {
    $stmt = $db->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Get total amount spent by a user.
 */
function getUserTotalSpent(PDO $db, int $userId): float {
    $stmt = $db->prepare('SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = ? AND status != ?');
    $stmt->execute([$userId, 'cancelled']);
    return (float) $stmt->fetchColumn();
}

/**
 * Get saved addresses for a user.
 */
function getUserAddresses(PDO $db, int $userId): array {
    $stmt = $db->prepare('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Get wishlist count for a user.
 */
function getWishlistCount(PDO $db, int $userId): int {
    $stmt = $db->prepare('SELECT COUNT(*) FROM wishlists WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Check if a product is in the user's wishlist.
 */
function isInWishlist(PDO $db, int $userId, int $productId): bool {
    $stmt = $db->prepare('SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$userId, $productId]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Get wishlist items with product details.
 */
function getWishlistItems(PDO $db, int $userId): array {
    $stmt = $db->prepare('SELECT w.id AS wishlist_id, w.product_id, w.created_at AS wishlisted_at,
                                 p.id, p.name, p.slug, p.price, p.sale_price, p.image, p.color, p.stock
                          FROM wishlists w JOIN products p ON w.product_id = p.id
                          WHERE w.user_id = ? AND p.is_active = 1 ORDER BY w.created_at DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// ============ PAYMENT METHODS ============

/**
 * Payment methods the store offers. Enabled methods only, unless $all.
 * Card / PayPal / Apple Pay are not listed: they need a payment gateway.
 */
function paymentMethods(bool $all = false): array {
    $mpesaHint = match (setting('mpesa_type')) {
        'till'       => 'Buy Goods · Till ' . setting('mpesa_number'),
        'send_money' => 'Send Money to ' . setting('mpesa_number'),
        default      => 'Paybill ' . setting('mpesa_number'),
    };
    $methods = [
        'cod'   => ['label' => 'Cash on Delivery', 'hint' => 'Pay when your order arrives', 'icon' => 'ri-hand-coin-line',  'color' => 'text-green-400 bg-green-500/10', 'enabled' => settingOn('pay_cod_enabled')],
        'mpesa' => ['label' => 'M-Pesa',           'hint' => $mpesaHint,                    'icon' => 'ri-smartphone-line', 'color' => 'text-green-500 bg-green-600/10', 'enabled' => settingOn('pay_mpesa_enabled') && setting('mpesa_number') !== ''],
        'bank'  => ['label' => 'Bank Transfer',    'hint' => 'Pay into our bank account',   'icon' => 'ri-bank-line',       'color' => 'text-blue-400 bg-blue-500/10',   'enabled' => settingOn('pay_bank_enabled') && setting('bank_account_number') !== ''],
    ];
    return $all ? $methods : array_filter($methods, fn($m) => $m['enabled']);
}

/**
 * Human label for a stored payment method code.
 */
function paymentMethodLabel(string $code): string {
    return paymentMethods(true)[$code]['label'] ?? ucfirst($code);
}

/**
 * How to pay for an order with a manual method: ['title' => …, 'steps' => [...], 'note' => …].
 * Returns null for methods that need no instructions (cash on delivery).
 */
function paymentInstructions(string $method, string $orderNumber, float $total): ?array {
    $amount = formatPrice($total);
    if ($method === 'mpesa') {
        $number = setting('mpesa_number');
        $name   = setting('mpesa_account_name');
        $steps  = match (setting('mpesa_type')) {
            'till' => [
                'Open M-PESA and choose Lipa na M-PESA → Buy Goods and Services',
                "Till number: $number" . ($name ? " ($name)" : ''),
                "Amount: $amount",
                'Enter your M-PESA PIN and confirm',
            ],
            'send_money' => [
                'Open M-PESA and choose Send Money',
                "Phone number: $number" . ($name ? " ($name)" : ''),
                "Amount: $amount",
                'Enter your M-PESA PIN and confirm',
            ],
            default => [
                'Open M-PESA and choose Lipa na M-PESA → Pay Bill',
                "Business number: $number" . ($name ? " ($name)" : ''),
                "Account number: $orderNumber",
                "Amount: $amount",
                'Enter your M-PESA PIN and confirm',
            ],
        };
        return [
            'title' => 'Pay with M-Pesa',
            'steps' => $steps,
            'note'  => 'Then enter the M-PESA confirmation code (e.g. QHX1ABC2DE) on your order page so we can match your payment.',
        ];
    }
    if ($method === 'bank') {
        $steps = array_filter([
            "Amount: $amount",
            'Bank: ' . setting('bank_name'),
            'Account name: ' . setting('bank_account_name'),
            'Account number: ' . setting('bank_account_number'),
            setting('bank_branch') ? 'Branch: ' . setting('bank_branch') : null,
            setting('bank_swift') ? 'SWIFT / BIC: ' . setting('bank_swift') : null,
            "Payment reference: $orderNumber",
        ]);
        return [
            'title' => 'Pay by bank transfer',
            'steps' => array_values($steps),
            'note'  => 'After paying, enter the bank transaction reference on your order page. We ship once the payment clears.',
        ];
    }
    return null;
}

// ============ COUPONS ============

/**
 * Check a coupon code against a subtotal. Returns
 * ['coupon' => row|null, 'discount' => float, 'error' => string|null].
 */
function applyCoupon(PDO $db, string $code, float $subtotal, ?int $userId = null, string $email = ''): array {
    $code = strtoupper(trim($code));
    $fail = fn(string $msg) => ['coupon' => null, 'discount' => 0.0, 'error' => $msg];
    if ($code === '') return $fail('Enter a coupon code.');

    $stmt = $db->prepare('SELECT * FROM coupons WHERE code = ? LIMIT 1');
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon || !$coupon['is_active'])                               return $fail('That coupon code is not valid.');
    if ($coupon['starts_at'] && strtotime($coupon['starts_at']) > time()) return $fail('That coupon is not active yet.');
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) return $fail('That coupon has expired.');
    if ($coupon['max_uses'] !== null && $coupon['used_count'] >= $coupon['max_uses']) return $fail('That coupon has reached its usage limit.');
    if ($subtotal < (float) $coupon['min_subtotal']) {
        return $fail('Spend at least ' . formatPrice($coupon['min_subtotal']) . ' to use this coupon.');
    }
    if ($coupon['once_per_customer'] && ($userId || $email !== '')) {
        $used = $db->prepare("SELECT COUNT(*) FROM orders WHERE coupon_code = ? AND status != 'cancelled' AND (user_id = ? OR shipping_email = ?)");
        $used->execute([$code, $userId ?? 0, $email]);
        if ($used->fetchColumn() > 0) return $fail('You have already used this coupon.');
    }

    $discount = $coupon['type'] === 'percent'
        ? round($subtotal * min(100, (float) $coupon['value']) / 100, 2)
        : min($subtotal, (float) $coupon['value']);

    return ['coupon' => $coupon, 'discount' => $discount, 'error' => null];
}

/**
 * The active storefront promotion, or null when switched off or expired.
 */
function activePromo(): ?array {
    if (!settingOn('promo_enabled') || setting('promo_title') === '') return null;
    $ends = strtotime(setting('promo_ends_at'));
    if ($ends && $ends < time()) return null;
    return [
        'title'   => setting('promo_title'),
        'text'    => setting('promo_text'),
        'code'    => strtoupper(setting('promo_code')),
        'ends_at' => $ends ?: null,
    ];
}

// ============ ATTEMPT THROTTLING ============

/**
 * True when an action (login, password reset…) has been tried too often
 * for this key (usually an email) or from this IP address.
 */
function tooManyAttempts(PDO $db, string $action, string $key, int $max = 5, int $minutes = 15): bool {
    try {
        $since = date('Y-m-d H:i:s', time() - $minutes * 60);
        $stmt = $db->prepare('SELECT
                (SELECT COUNT(*) FROM auth_attempts WHERE action = ? AND attempt_key = ? AND created_at > ?) AS by_key,
                (SELECT COUNT(*) FROM auth_attempts WHERE action = ? AND ip = ? AND created_at > ?) AS by_ip');
        $stmt->execute([$action, strtolower($key), $since, $action, clientIp(), $since]);
        $row = $stmt->fetch();
        return $row['by_key'] >= $max || $row['by_ip'] >= $max * 4;
    } catch (Exception $e) {
        return false;
    }
}

function recordAttempt(PDO $db, string $action, string $key): void {
    try {
        $db->prepare('INSERT INTO auth_attempts (action, attempt_key, ip, created_at) VALUES (?, ?, ?, ?)')
           ->execute([$action, strtolower($key), clientIp(), date('Y-m-d H:i:s')]);
        // Keep the table small
        if (random_int(1, 50) === 1) {
            $db->prepare('DELETE FROM auth_attempts WHERE created_at < ?')->execute([date('Y-m-d H:i:s', time() - 86400)]);
        }
    } catch (Exception $e) {
        error_log('Attempt log failed: ' . $e->getMessage());
    }
}

function clearAttempts(PDO $db, string $action, string $key): void {
    try {
        $db->prepare('DELETE FROM auth_attempts WHERE action = ? AND attempt_key = ?')->execute([$action, strtolower($key)]);
    } catch (Exception $e) {}
}

// ============ ORDER HELPERS ============

/**
 * Record an order status change in the timeline.
 */
function recordOrderStatus(PDO $db, int $orderId, ?string $oldStatus, string $newStatus, ?int $changedBy = null, ?string $note = null): void {
    $db->prepare('INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note) VALUES (?, ?, ?, ?, ?)')
       ->execute([$orderId, $oldStatus, $newStatus, $changedBy, $note]);
}

/**
 * Render breadcrumb HTML.
 * $items = [ ['label' => 'Home', 'url' => '/'], ['label' => 'Shop', 'url' => '/shop.php'], ['label' => 'Product'] ]
 */
function breadcrumb(array $items): string {
    $html = '<nav aria-label="Breadcrumb" class="mb-4 sm:mb-6"><ol class="flex flex-wrap items-center gap-1.5 text-xs sm:text-sm text-gray-500" itemscope itemtype="https://schema.org/BreadcrumbList">';
    foreach ($items as $i => $item) {
        $pos = $i + 1;
        $isLast = $i === count($items) - 1;
        $html .= '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        if (!$isLast && !empty($item['url'])) {
            $html .= '<a href="' . e($item['url']) . '" itemprop="item" class="hover:text-neon-blue transition-colors"><span itemprop="name">' . e($item['label']) . '</span></a>';
        } else {
            $html .= '<span itemprop="name" class="text-gray-300 font-medium">' . e($item['label']) . '</span>';
        }
        $html .= '<meta itemprop="position" content="' . $pos . '">';
        $html .= '</li>';
        if (!$isLast) {
            $html .= '<li class="text-gray-700"><i class="ri-arrow-right-s-line text-xs"></i></li>';
        }
    }
    $html .= '</ol></nav>';
    return $html;
}

/**
 * Get status badge classes for order status.
 */
function orderStatusBadge(string $status): string {
    return match($status) {
        'pending'    => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        'processing' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'shipped'    => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        'delivered'  => 'bg-green-500/10 text-green-400 border-green-500/20',
        'cancelled'  => 'bg-red-500/10 text-red-400 border-red-500/20',
        default      => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
    };
}

/**
 * Get status badge classes for payment status.
 */
function paymentStatusBadge(string $status): string {
    return match($status) {
        'paid'     => 'bg-green-500/10 text-green-400',
        'pending'  => 'bg-yellow-500/10 text-yellow-400',
        'failed'   => 'bg-red-500/10 text-red-400',
        'refunded' => 'bg-purple-500/10 text-purple-400',
        default    => 'bg-gray-500/10 text-gray-400',
    };
}

/**
 * Get order status history.
 */
function getOrderStatusHistory(PDO $db, int $orderId): array {
    $stmt = $db->prepare('SELECT osh.*, u.name AS changed_by_name
                          FROM order_status_history osh
                          LEFT JOIN users u ON osh.changed_by = u.id
                          WHERE osh.order_id = ? ORDER BY osh.created_at ASC');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

/**
 * Get all active products with optional category filter and sorting.
 */
function getFilteredProducts(PDO $db, ?string $category = null, string $sort = 'newest', ?string $search = null): array {
    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_active = 1';
    $params = [];

    if ($category) {
        $sql .= ' AND c.slug = ?';
        $params[] = $category;
    }
    if ($search) {
        $sql .= ' AND (p.name LIKE ? OR p.description LIKE ? OR p.color LIKE ?)';
        $term = '%' . $search . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $sql .= match($sort) {
        'price_asc'  => ' ORDER BY COALESCE(p.sale_price, p.price) ASC',
        'price_desc' => ' ORDER BY COALESCE(p.sale_price, p.price) DESC',
        'name_asc'   => ' ORDER BY p.name ASC',
        'name_desc'  => ' ORDER BY p.name DESC',
        default      => ' ORDER BY p.featured DESC, p.created_at DESC',
    };

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get all categories with product counts.
 */
function getCategoriesWithCount(PDO $db): array {
    $stmt = $db->query('SELECT c.*, COUNT(p.id) AS product_count
                        FROM categories c
                        LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                        GROUP BY c.id ORDER BY c.name ASC');
    return $stmt->fetchAll();
}

/**
 * Time ago helper for user-facing pages.
 */
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

// ============ EMAIL ============
require_once __DIR__ . '/mailer.php';
