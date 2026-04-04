<?php
/**
 * Checkout API — Place order
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // CSRF check
    $token = $input['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validateCsrf($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }

    $db = getDB();
    $cart = getOrCreateCart($db);

    // Get cart items
    $stmt = $db->prepare('
        SELECT ci.quantity, p.id AS product_id, p.name, p.image, p.price, p.sale_price, p.stock
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.cart_id = ?
    ');
    $stmt->execute([$cart['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
        exit;
    }

    // Validate shipping info
    $firstName = sanitize($input['first_name'] ?? '');
    $lastName = sanitize($input['last_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = sanitize($input['phone'] ?? '');
    $address = sanitize($input['address'] ?? '');
    $city = sanitize($input['city'] ?? '');
    $state = sanitize($input['state'] ?? '');
    $zip = sanitize($input['zip'] ?? '');
    $country = sanitize($input['country'] ?? '');
    $paymentMethod = sanitize($input['payment_method'] ?? '');

    $errors = [];
    if (strlen($firstName) < 2) $errors[] = 'First name is required.';
    if (strlen($lastName) < 2) $errors[] = 'Last name is required.';
    if (!isValidEmail($email)) $errors[] = 'Valid email is required.';
    if (strlen($address) < 5) $errors[] = 'Address is required.';
    if (strlen($city) < 2) $errors[] = 'City is required.';
    if (strlen($zip) < 3) $errors[] = 'ZIP/postal code is required.';
    if (strlen($country) < 2) $errors[] = 'Country is required.';

    $allowedMethods = ['cod', 'mpesa', 'bank', 'paypal'];
    if (!in_array($paymentMethod, $allowedMethods, true)) {
        $errors[] = 'Please select a valid payment method.';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors), 'errors' => $errors]);
        exit;
    }

    // Calculate total and check stock
    $subtotal = 0;
    foreach ($items as $item) {
        if ($item['quantity'] > $item['stock']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => e($item['name']) . ' only has ' . $item['stock'] . ' in stock.']);
            exit;
        }
        $price = $item['sale_price'] ?: $item['price'];
        $subtotal += $price * $item['quantity'];
    }

    $shipping = $subtotal >= 100 ? 0 : 9.99;
    $tax = round($subtotal * 0.08, 2); // 8% tax
    $total = $subtotal + $shipping + $tax;

    $db->beginTransaction();

    $orderNumber = generateOrderNumber();
    $userId = isLoggedIn() ? currentUserId() : null;

    $stmt = $db->prepare('
        INSERT INTO orders (
            order_number, user_id,
            shipping_name, shipping_email, shipping_phone,
            shipping_address, shipping_city, shipping_state, shipping_zip, shipping_country,
            subtotal, shipping_cost, tax, total,
            payment_method, payment_status, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $orderNumber, $userId,
        $firstName . ' ' . $lastName, $email, $phone,
        $address, $city, $state, $zip, $country ?: 'United States',
        $subtotal, $shipping, $tax, $total,
        $paymentMethod, 'pending', 'pending'
    ]);
    $orderId = $db->lastInsertId();

    // Insert order items and decrease stock
    $itemStmt = $db->prepare('INSERT INTO order_items (order_id, product_id, product_name, product_image, quantity, price) VALUES (?, ?, ?, ?, ?, ?)');
    $stockStmt = $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

    foreach ($items as $item) {
        $price = $item['sale_price'] ?: $item['price'];
        $itemStmt->execute([$orderId, $item['product_id'], $item['name'], $item['image'] ?? null, $item['quantity'], $price]);
        $stockStmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
    }

    // Clear cart
    $db->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cart['id']]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_number' => $orderNumber,
        'total' => formatPrice($total),
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    $msg = APP_ENV === 'development' ? $e->getMessage() : 'An error occurred while placing your order. Please try again.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
