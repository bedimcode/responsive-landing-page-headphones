<?php
/**
 * robots.txt — served at /robots.txt (see .htaccess) so the sitemap URL
 * always matches APP_URL.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/plain; charset=utf-8');
?>
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /account/
Disallow: /api/
Disallow: /auth/
Disallow: /checkout.php
Disallow: /includes/
Disallow: /sql/
Disallow: /*?q=

Sitemap: <?= absoluteUrl('/sitemap.xml') ?>

