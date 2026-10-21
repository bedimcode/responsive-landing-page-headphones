<?php
/**
 * Local configuration — copy this file to includes/config.local.php
 * and fill in the values for this server. config.local.php is not
 * committed to git, so passwords never end up in the repository.
 *
 * Anything you leave out falls back to the defaults in config.php.
 */

// ---- Site ----
define('APP_URL', 'https://www.example.com');   // No trailing slash
define('APP_ENV', 'production');                // 'production' hides error details
define('DEMO_MODE', false);                     // true shows the "demo store" notice
define('APP_TIMEZONE', 'Africa/Nairobi');

// ---- Database ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'headphones1_duka');
define('DB_USER', 'shop_user');
define('DB_PASS', 'change-me');

// ---- Email ----
// 'log' writes emails to logs/mail.log, 'mail' uses PHP mail(), 'smtp' uses the server below.
define('MAIL_TRANSPORT', 'smtp');
define('MAIL_FROM', 'orders@example.com');      // Must be an address your SMTP account may send from
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');                   // 'tls' for port 587, 'ssl' for port 465
define('SMTP_USER', 'orders@example.com');
define('SMTP_PASS', 'app-password-here');       // Gmail: create an App Password, not your login password
