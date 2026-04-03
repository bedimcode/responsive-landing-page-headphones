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
function formatPrice(float $price): string {
    return '$' . number_format($price, 2);
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
