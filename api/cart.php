<?php
/**
 * Cart API — Add / Update / Remove / Get cart items
 * All responses are JSON.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();

    // ---------- GET: fetch cart ----------
    if ($method === 'GET') {
        $cart = getOrCreateCart($db);
        $stmt = $db->prepare('
            SELECT ci.id, ci.quantity, p.id AS product_id, p.name, p.slug, p.color, p.price, p.sale_price, p.image, p.stock, p.is_active
            FROM cart_items ci
            JOIN products p ON p.id = ci.product_id
            WHERE ci.cart_id = ?
            ORDER BY ci.created_at
        ');
        $stmt->execute([$cart['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = 0;
        foreach ($items as &$item) {
            $item['effective_price'] = effectivePrice($item);
            $item['line_total'] = $item['effective_price'] * $item['quantity'];
            $total += $item['line_total'];
        }
        unset($item);

        echo json_encode(['success' => true, 'items' => $items, 'total' => $total, 'count' => array_sum(array_column($items, 'quantity'))]);
        exit;
    }

    // ---------- POST methods require CSRF ----------
    if ($method === 'POST') {
        maintenanceGate(true);
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $input['action'] ?? '';

        // CSRF check (X-CSRF-TOKEN header)
        if (!validateCsrf()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
            exit;
        }

        $cart = getOrCreateCart($db);

        // ---- ADD TO CART ----
        if ($action === 'add') {
            $productId = (int) ($input['product_id'] ?? 0);
            $qty = max(1, (int) ($input['quantity'] ?? 1));

            $product = getProductById($db, $productId);
            if (!$product || !$product['is_active']) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Product not found.']);
                exit;
            }
            if ($product['stock'] < $qty) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
                exit;
            }

            // Check if already in cart
            $existing = $db->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?');
            $existing->execute([$cart['id'], $productId]);
            $row = $existing->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $newQty = $row['quantity'] + $qty;
                if ($newQty > $product['stock']) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Cannot exceed available stock.']);
                    exit;
                }
                $db->prepare('UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?')->execute([$newQty, $row['id']]);
            } else {
                $db->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)')->execute([$cart['id'], $productId, $qty]);
            }

            echo json_encode(['success' => true, 'message' => 'Added to cart.', 'count' => getCartCount($db)]);
            exit;
        }

        // ---- UPDATE QUANTITY ----
        if ($action === 'update') {
            $itemId = (int) ($input['item_id'] ?? 0);
            $qty = max(1, (int) ($input['quantity'] ?? 1));

            $stmt = $db->prepare('SELECT ci.id, p.stock FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.id = ? AND ci.cart_id = ?');
            $stmt->execute([$itemId, $cart['id']]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Cart item not found.']);
                exit;
            }
            if ($qty > $item['stock']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot exceed available stock.']);
                exit;
            }

            $db->prepare('UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?')->execute([$qty, $itemId]);
            echo json_encode(['success' => true, 'message' => 'Cart updated.', 'count' => getCartCount($db)]);
            exit;
        }

        // ---- REMOVE ITEM ----
        if ($action === 'remove') {
            $itemId = (int) ($input['item_id'] ?? 0);
            $db->prepare('DELETE FROM cart_items WHERE id = ? AND cart_id = ?')->execute([$itemId, $cart['id']]);
            echo json_encode(['success' => true, 'message' => 'Item removed.', 'count' => getCartCount($db)]);
            exit;
        }

        // ---- CLEAR CART ----
        if ($action === 'clear') {
            $db->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cart['id']]);
            echo json_encode(['success' => true, 'message' => 'Cart cleared.', 'count' => 0]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);

} catch (Exception $e) {
    http_response_code(500);
    $msg = APP_ENV === 'development' ? $e->getMessage() : 'An error occurred.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
