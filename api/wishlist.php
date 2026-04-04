<?php
/**
 * Wishlist API — Toggle add/remove products from wishlist
 * Requires authentication. All responses are JSON.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();

    // Auth check
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please log in first.', 'redirect' => APP_URL . '/auth/login.php']);
        exit;
    }

    $userId = (int) $_SESSION['user_id'];

    // ---------- GET: fetch wishlist ----------
    if ($method === 'GET') {
        $items = getWishlistItems($db, $userId);
        echo json_encode(['success' => true, 'items' => $items, 'count' => count($items)]);
        exit;
    }

    // ---------- POST: toggle wishlist item ----------
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $productId = (int) ($input['product_id'] ?? 0);

        if (!$productId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product ID is required.']);
            exit;
        }

        // Verify product exists
        $stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }

        // Toggle: if in wishlist, remove; otherwise, add
        if (isInWishlist($db, $userId, $productId)) {
            $stmt = $db->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            $action = 'removed';
        } else {
            $stmt = $db->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$userId, $productId]);
            $action = 'added';
        }

        $count = getWishlistCount($db, $userId);
        echo json_encode(['success' => true, 'action' => $action, 'count' => $count, 'message' => $action === 'added' ? 'Added to wishlist!' : 'Removed from wishlist.']);
        exit;
    }

    // ---------- DELETE: remove specific item ----------
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = (int) ($input['product_id'] ?? 0);

        if (!$productId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product ID is required.']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);

        $count = getWishlistCount($db, $userId);
        echo json_encode(['success' => true, 'count' => $count, 'message' => 'Removed from wishlist.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);

} catch (Exception $e) {
    error_log('Wishlist API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
