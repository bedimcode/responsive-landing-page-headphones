<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

// Show the function source
$ref = new ReflectionFunction('getWishlistItems');
echo "<h3>getWishlistItems defined at: " . $ref->getFileName() . " line " . $ref->getStartLine() . "-" . $ref->getEndLine() . "</h3>";

// Show file last modified
$funcFile = $ref->getFileName();
echo "<p>File mtime: " . date('Y-m-d H:i:s', filemtime($funcFile)) . "</p>";
echo "<p>Wishlist.php mtime: " . date('Y-m-d H:i:s', filemtime(__DIR__ . '/account/wishlist.php')) . "</p>";

// Manually run the query for user_id 2
$uid = 2;
echo "<h3>Results for user_id=$uid:</h3>";
$items = getWishlistItems($db, $uid);
echo "<pre>";
print_r($items);
echo "</pre>";

// Also try session user
if (!empty($_SESSION['user_id'])) {
    $suid = (int)$_SESSION['user_id'];
    echo "<h3>Session user_id=$suid:</h3>";
    $sitems = getWishlistItems($db, $suid);
    echo "<pre>";
    print_r($sitems);
    echo "</pre>";
}

// OPcache info
if (function_exists('opcache_get_status')) {
    $s = opcache_get_status(false);
    echo "<p>OPcache enabled: " . ($s['opcache_enabled'] ? 'YES' : 'NO') . "</p>";
} else {
    echo "<p>OPcache: not loaded</p>";
}
