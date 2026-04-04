<?php
/**
 * Account API — Profile updates, password change, address management
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
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please log in first.', 'redirect' => APP_URL . '/auth/login.php']);
        exit;
    }

    $userId = (int) $_SESSION['user_id'];

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';

    // CSRF check
    $token = $input['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!validateCsrf($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }

    switch ($action) {

        // ========== UPDATE PROFILE ==========
        case 'update_profile':
            $name  = trim($input['name'] ?? '');
            $email = trim($input['email'] ?? '');
            $phone = trim($input['phone'] ?? '');

            if (!$name || !$email) {
                echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
                exit;
            }

            // Check email uniqueness
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'This email is already in use.']);
                exit;
            }

            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $email, $phone ?: null, $userId]);

            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;

            echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
            break;

        // ========== UPDATE PASSWORD ==========
        case 'update_password':
            $current  = $input['current_password'] ?? '';
            $new      = $input['new_password'] ?? '';
            $confirm  = $input['confirm_password'] ?? '';

            if (!$current || !$new || !$confirm) {
                echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
                exit;
            }

            if ($new !== $confirm) {
                echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
                exit;
            }

            if (strlen($new) < 8) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
                exit;
            }

            // Verify current password
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
                exit;
            }

            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hash, $userId]);

            echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
            break;

        // ========== ADD ADDRESS ==========
        case 'add_address':
            $label   = trim($input['label'] ?? '');
            $address = trim($input['address'] ?? '');
            $city    = trim($input['city'] ?? '');
            $state   = trim($input['state'] ?? '');
            $zip     = trim($input['zip'] ?? '');
            $country = trim($input['country'] ?? '');

            if (!$label || !$address || !$city || !$zip || !$country) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
                exit;
            }

            // Check address count (limit 5)
            $stmt = $db->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() >= 5) {
                echo json_encode(['success' => false, 'message' => 'Maximum 5 addresses allowed. Please delete one first.']);
                exit;
            }

            // If first address or set as default
            $isDefault = (int) ($input['is_default'] ?? 0);
            $stmt = $db->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() == 0) $isDefault = 1;

            if ($isDefault) {
                $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            }

            $stmt = $db->prepare("INSERT INTO user_addresses (user_id, label, address, city, state, zip, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $label, $address, $city, $state ?: null, $zip, $country, $isDefault]);

            echo json_encode(['success' => true, 'message' => 'Address added successfully.']);
            break;

        // ========== DELETE ADDRESS ==========
        case 'delete_address':
            $addressId = (int) ($input['address_id'] ?? 0);
            if (!$addressId) {
                echo json_encode(['success' => false, 'message' => 'Address ID required.']);
                exit;
            }

            // Verify ownership
            $stmt = $db->prepare("SELECT id, is_default FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$addressId, $userId]);
            $addr = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$addr) {
                echo json_encode(['success' => false, 'message' => 'Address not found.']);
                exit;
            }

            $db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?")->execute([$addressId, $userId]);

            // If deleted address was default, set first remaining as default
            if ($addr['is_default']) {
                $stmt = $db->prepare("UPDATE user_addresses SET is_default = 1 WHERE user_id = ? ORDER BY id LIMIT 1");
                $stmt->execute([$userId]);
            }

            echo json_encode(['success' => true, 'message' => 'Address deleted.']);
            break;

        // ========== SET DEFAULT ADDRESS ==========
        case 'set_default_address':
            $addressId = (int) ($input['address_id'] ?? 0);
            if (!$addressId) {
                echo json_encode(['success' => false, 'message' => 'Address ID required.']);
                exit;
            }

            // Verify ownership
            $stmt = $db->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$addressId, $userId]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Address not found.']);
                exit;
            }

            $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            $db->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$addressId, $userId]);

            echo json_encode(['success' => true, 'message' => 'Default address updated.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }

} catch (Exception $e) {
    error_log('Account API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
