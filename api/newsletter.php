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
    if (!validateCsrf()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }

    $email = strtolower(cleanText($input['email'] ?? '', 255));

    if (!isValidEmail($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    $db = getDB();
    if (tooManyAttempts($db, 'newsletter', $email, 3, 60)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Please wait a little before trying again.']);
        exit;
    }
    recordAttempt($db, 'newsletter', $email);

    // Check for existing subscription
    $stmt = $db->prepare('SELECT id, is_active, token FROM newsletter WHERE email = ?');
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $hasOffer = setting('newsletter_coupon') !== '';
    if ($existing && $existing['is_active']) {
        echo json_encode(['success' => true, 'message' => 'You\'re already subscribed — thanks for being here!']);
        exit;
    }

    $token = ($existing['token'] ?? '') ?: bin2hex(random_bytes(32));
    if ($existing) {
        $db->prepare('UPDATE newsletter SET is_active = 1, token = ? WHERE id = ?')->execute([$token, $existing['id']]);
    } else {
        $db->prepare('INSERT INTO newsletter (email, token) VALUES (?, ?)')->execute([$email, $token]);
    }

    sendNewsletterWelcome($email, $token);

    echo json_encode([
        'success' => true,
        'message' => $hasOffer ? 'Thanks for subscribing! Check your inbox for your welcome code.' : 'Thanks for subscribing!',
    ]);

} catch (Exception $e) {
    error_log('Newsletter API error: ' . $e->getMessage());
    http_response_code(500);
    $msg = APP_ENV === 'development' ? $e->getMessage() : 'An error occurred. Please try again.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
