<?php
/**
 * Headphones1.Duka — Application Configuration
 * Database connection, constants, session setup
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// ============ ENVIRONMENT ============
define('APP_NAME', 'Beats 3 Store');
define('APP_URL', 'http://localhost/headphones1.duka');
define('APP_ENV', 'development'); // 'development' or 'production'

// ============ DATABASE ============
define('DB_HOST', 'localhost');
define('DB_NAME', 'headphones1_duka');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP has no password

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
            die('Service temporarily unavailable. Please try again later.');
        }
    }
    return $pdo;
}

// ============ SESSION CONFIG ============
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// ============ ERROR DISPLAY ============
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}

// ============ TIMEZONE ============
date_default_timezone_set('UTC');
