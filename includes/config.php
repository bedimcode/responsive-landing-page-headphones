<?php
/**
 * Headphones1.Duka — Application Configuration
 * Database connection, constants, session setup
 *
 * Server-specific values (database, site URL, email) belong in
 * includes/config.local.php — copy config.local.sample.php to start.
 * Store details (name, currency, payments…) are edited in Admin → Settings.
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// ============ LOCAL OVERRIDES ============
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// ============ ENVIRONMENT ============
defined('APP_NAME')     || define('APP_NAME', 'Beats 3 Store'); // Fallback when the store name setting is empty
defined('APP_URL')      || define('APP_URL', 'http://localhost/headphones1.duka');
defined('APP_ENV')      || define('APP_ENV', 'development'); // 'development' or 'production'
defined('DEMO_MODE')    || define('DEMO_MODE', true);        // Shows a "demo store" notice on the storefront
defined('APP_TIMEZONE') || define('APP_TIMEZONE', 'UTC');

// ============ DATABASE ============
defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_NAME') || define('DB_NAME', 'headphones1_duka');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', ''); // Default XAMPP has no password

// ============ EMAIL ============
// 'log'  — write emails to logs/mail.log (development, demos)
// 'mail' — PHP mail() (most shared hosting)
// 'smtp' — SMTP server below (Gmail, Zoho, Mailgun, your host…)
defined('MAIL_TRANSPORT') || define('MAIL_TRANSPORT', 'log');
defined('MAIL_FROM')      || define('MAIL_FROM', '');      // Empty: uses the store email setting
defined('SMTP_HOST')      || define('SMTP_HOST', '');
defined('SMTP_PORT')      || define('SMTP_PORT', 587);
defined('SMTP_SECURE')    || define('SMTP_SECURE', 'tls'); // 'tls' (STARTTLS), 'ssl' or 'none'
defined('SMTP_USER')      || define('SMTP_USER', '');
defined('SMTP_PASS')      || define('SMTP_PASS', '');

// ============ PDO CONNECTION ============
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
            }
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(503);
            die('Service temporarily unavailable. Please try again later.');
        }
    }
    return $pdo;
}

// ============ OUTPUT BUFFERING ============
// Lets pages send redirects after the layout has started rendering. Always
// started (even if php.ini already buffers): a 4 KB php.ini buffer flushes
// before the admin layout finishes, which breaks redirects.
if (PHP_SAPI !== 'cli') {
    ob_start();
}

// ============ SESSION CONFIG ============
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || str_starts_with(APP_URL, 'https://')) {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// ============ ERROR DISPLAY ============
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}

// ============ TIMEZONE ============
date_default_timezone_set(APP_TIMEZONE);
