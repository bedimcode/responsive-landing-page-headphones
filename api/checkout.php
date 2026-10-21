<?php
/**
 * Checkout API — Place order
 * Validates the cart, locks stock, applies a coupon, records the order,
 * then emails the customer and the store.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

function fail(string $message, int $status = 400, array $extra = []): never {
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message] + $extra);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Method not allowed.', 405);
}

maintenanceGate(true);

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    if (!validateCsrf()) {
        fail('Invalid security token. Please refresh the page.', 403);
    }
    if (!isLoggedIn()) {
        fail('Please sign in to place your order.', 401, ['redirect' => APP_URL . '/auth/login.php']);
    }

    $db = getDB();
    $cart = getOrCreateCart($db);
    $userId = currentUserId();

    // ---- Shipping details ----
    $firstName     = cleanText($input['first_name'] ?? '', 50);
    $lastName      = cleanText($input['last_name'] ?? '', 49);
    $email         = cleanText($input['email'] ?? '', 255);
    $phone         = cleanText($input['phone'] ?? '', 20);
    $address       = cleanText($input['address'] ?? '', 255);
    $city          = cleanText($input['city'] ?? '', 100);
    $state         = cleanText($input['state'] ?? '', 100);
    $zip           = cleanText($input['zip'] ?? '', 20);
    $country       = cleanText($input['country'] ?? '', 100);
    $paymentMethod = cleanText($input['payment_method'] ?? '', 20);
    $couponCode    = strtoupper(cleanText($input['coupon_code'] ?? '', 40));

    $errors = [];
    if (mb_strlen($firstName) < 2) $errors[] = 'First name is required.';
    if (mb_strlen($lastName) < 2)  $errors[] = 'Last name is required.';
    if (!isValidEmail($email))     $errors[] = 'A valid email is required.';
    if (!preg_match('/^\+?[0-9 ()-]{7,20}$/', $phone)) $errors[] = 'A valid phone number is required for delivery.';
    if (mb_strlen($address) < 5)   $errors[] = 'Street address is required.';
    if (mb_strlen($city) < 2)      $errors[] = 'City is required.';
    if (mb_strlen($country) < 2)   $errors[] = 'Country is required.';
    if (!array_key_exists($paymentMethod, paymentMethods())) {
        $errors[] = 'Please select a payment method.';
    }
    if ($errors) {
        fail(implode(' ', $errors), 400, ['errors' => $errors]);
    }

    $db->beginTransaction();

    // ---- Cart, with product rows locked so stock cannot be oversold ----
    $stmt = $db->prepare('
        SELECT ci.quantity, p.id AS product_id, p.name, p.image, p.price, p.sale_price, p.stock, p.is_active
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.cart_id = ?
        FOR UPDATE
    ');
    $stmt->execute([$cart['id']]);
    $items = $stmt->fetchAll();

    if (!$items) {
        $db->rollBack();
        fail('Your cart is empty.');
    }

    $subtotal = 0.0;
    foreach ($items as &$item) {
        if (!$item['is_active']) {
            $db->rollBack();
            fail($item['name'] . ' is no longer available. Please remove it from your cart.');
        }
        if ($item['quantity'] > $item['stock']) {
            $db->rollBack();
            fail($item['stock'] > 0
                ? $item['name'] . ' only has ' . $item['stock'] . ' left. Please lower the quantity.'
                : $item['name'] . ' just sold out. Please remove it from your cart.');
        }
        $item['unit_price'] = effectivePrice($item);
        $subtotal += $item['unit_price'] * $item['quantity'];
    }
    unset($item);

    // ---- Coupon ----
    $discount = 0.0;
    $coupon = null;
    if ($couponCode !== '') {
        $result = applyCoupon($db, $couponCode, $subtotal, $userId, $email);
        if ($result['error']) {
            $db->rollBack();
            fail($result['error'], 400, ['field' => 'coupon']);
        }
        $coupon = $result['coupon'];
        $discount = $result['discount'];
    }

    $totals = calculateTotals($subtotal, $discount);

    // ---- Order ----
    $orderNumber = generateOrderNumber();
    $db->prepare('
        INSERT INTO orders (
            order_number, user_id,
            shipping_name, shipping_email, shipping_phone,
            shipping_address, shipping_city, shipping_state, shipping_zip, shipping_country,
            subtotal, discount, coupon_code, shipping_cost, tax, total,
            payment_method, payment_status, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ')->execute([
        $orderNumber, $userId,
        $firstName . ' ' . $lastName, $email, $phone,
        $address, $city, $state ?: null, $zip, $country,
        $totals['subtotal'], $totals['discount'], $coupon ? $coupon['code'] : null,
        $totals['shipping'], $totals['tax'], $totals['total'],
        $paymentMethod, 'pending', 'pending',
    ]);
    $orderId = (int) $db->lastInsertId();

    $itemStmt  = $db->prepare('INSERT INTO order_items (order_id, product_id, product_name, product_image, quantity, price) VALUES (?, ?, ?, ?, ?, ?)');
    $stockStmt = $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
    $lowStock  = [];
    $threshold = (int) setting('low_stock_threshold');

    foreach ($items as $item) {
        $itemStmt->execute([$orderId, $item['product_id'], $item['name'], $item['image'] ?? null, $item['quantity'], $item['unit_price']]);
        $stockStmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
        if ($stockStmt->rowCount() !== 1) {
            throw new RuntimeException('Stock changed for product ' . $item['product_id']);
        }
        $left = $item['stock'] - $item['quantity'];
        if ($item['stock'] > $threshold && $left <= $threshold) {
            $lowStock[] = ['name' => $item['name'], 'stock' => $left];
        }
    }

    if ($coupon) {
        $use = $db->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ? AND (max_uses IS NULL OR used_count < max_uses)');
        $use->execute([$coupon['id']]);
        if ($use->rowCount() !== 1) {
            $db->rollBack();
            fail('That coupon has reached its usage limit.', 400, ['field' => 'coupon']);
        }
    }

    recordOrderStatus($db, $orderId, null, 'pending', $userId, 'Order placed · ' . paymentMethodLabel($paymentMethod));
    $db->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cart['id']]);
    $db->commit();

    // ---- Notifications (failures are logged, never shown to the customer) ----
    sendOrderConfirmation($db, $orderId);
    sendAdminNewOrder($db, $orderId);
    sendLowStockAlert($lowStock);

    echo json_encode([
        'success'      => true,
        'message'      => 'Order placed successfully!',
        'order_number' => $orderNumber,
        'total'        => formatPrice($totals['total']),
        'redirect'     => APP_URL . '/account/order-view.php?id=' . $orderId . '&placed=1',
    ]);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Checkout failed: ' . $e->getMessage());
    http_response_code(500);
    $msg = APP_ENV === 'development' ? $e->getMessage() : 'We could not place your order. Please try again.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
