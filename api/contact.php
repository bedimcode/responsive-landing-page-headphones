<?php
/**
 * Contact Form API
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

    // Spam trap: real visitors never see or fill the "website" field
    if (!empty($input['website'])) {
        echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been sent.']);
        exit;
    }

    $name    = cleanText($input['name'] ?? '', 100);
    $email   = cleanText($input['email'] ?? '', 255);
    $subject = cleanText($input['subject'] ?? '', 200);
    $message = cleanText($input['message'] ?? '', 5000);

    // Validation
    $errors = [];
    if (mb_strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
    if (!isValidEmail($email)) $errors[] = 'Please enter a valid email address.';
    if (mb_strlen($subject) < 3) $errors[] = 'Subject must be at least 3 characters.';
    if (mb_strlen($message) < 10) $errors[] = 'Message must be at least 10 characters.';

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors), 'errors' => $errors]);
        exit;
    }

    $db = getDB();
    if (tooManyAttempts($db, 'contact', $email, 3, 30)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'You have sent several messages already. We will reply soon.']);
        exit;
    }
    recordAttempt($db, 'contact', $email);

    $stmt = $db->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, $subject, $message]);

    sendContactNotification($name, $email, $subject, $message);

    echo json_encode(['success' => true, 'message' => 'Thank you! Your message has been sent. We\'ll get back to you within 24-48 hours.']);

} catch (Exception $e) {
    error_log('Contact API error: ' . $e->getMessage());
    http_response_code(500);
    $msg = APP_ENV === 'development' ? $e->getMessage() : 'An error occurred. Please try again.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
