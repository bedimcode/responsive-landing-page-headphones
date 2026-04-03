<?php
/**
 * Newsletter API — Subscribe email
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

    $email = trim($input['email'] ?? '');

    if (!isValidEmail($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    $db = getDB();

    // Check for existing subscription
    $stmt = $db->prepare('SELECT id, is_active FROM newsletter WHERE email = ?');
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['is_active']) {
            echo json_encode(['success' => true, 'message' => 'You\'re already subscribed! Check your inbox for updates.']);
        } else {
            $db->prepare('UPDATE newsletter SET is_active = 1, updated_at = NOW() WHERE id = ?')->execute([$existing['id']]);
            echo json_encode(['success' => true, 'message' => 'Welcome back! Your subscription has been reactivated.']);
        }
    } else {
        $db->prepare('INSERT INTO newsletter (email) VALUES (?)')->execute([$email]);
        echo json_encode(['success' => true, 'message' => 'Thanks for subscribing! You\'ll get 10% off your first order.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    $msg = APP_ENV === 'development' ? $e->getMessage() : 'An error occurred. Please try again.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
