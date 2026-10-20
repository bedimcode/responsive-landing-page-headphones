<?php
/**
 * Coupon API — Preview a coupon against the current cart
 * POST { code } → discount + recalculated totals (nothing is saved).
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

    if (!validateCsrf()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }

    $db = getDB();
    if (tooManyAttempts($db, 'coupon', session_id(), 15, 10)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many tries. Please wait a few minutes.']);
        exit;
    }

    $cart = getOrCreateCart($db);
    $stmt = $db->prepare('SELECT ci.quantity, p.price, p.sale_price FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.cart_id = ?');
    $stmt->execute([$cart['id']]);
    $subtotal = 0.0;
    foreach ($stmt->fetchAll() as $item) {
        $subtotal += effectivePrice($item) * $item['quantity'];
    }

    $email  = cleanText($input['email'] ?? '', 255);
    $result = applyCoupon($db, cleanText($input['code'] ?? '', 40), $subtotal, currentUserId(), isValidEmail($email) ? $email : '');

    if ($result['error']) {
        recordAttempt($db, 'coupon', session_id());
        echo json_encode(['success' => false, 'message' => $result['error']]);
        exit;
    }

    $totals = calculateTotals($subtotal, $result['discount']);
    echo json_encode([
        'success'  => true,
        'message'  => 'Coupon applied: ' . ($result['coupon']['description'] ?: $result['coupon']['code']),
        'code'     => $result['coupon']['code'],
        'totals'   => array_map(fn($v) => is_float($v) ? formatPrice($v) : $v, [
            'subtotal' => $totals['subtotal'],
            'discount' => -$totals['discount'],
            'shipping' => $totals['shipping'],
            'tax'      => $totals['tax'],
            'total'    => $totals['total'],
        ]),
        'shipping_free' => $totals['shipping'] <= 0,
    ]);

} catch (Exception $e) {
    error_log('Coupon API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
