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
 * Sanitize and trim a string input.
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format.
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
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
 * Format a price for display.
 */
function formatPrice(float|null $price): string {
    return '$' . number_format($price ?? 0, 2);
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

/**
 * Get a site setting value.
 */
function getSiteSetting(PDO $db, string $key, string $default = ''): string {
    try {
        $stmt = $db->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
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
