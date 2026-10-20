<?php
/**
 * Database Migrations — command-line runner
 * Usage: php scripts/migrate.php
 * (The same updates can be applied from Admin → Settings → System.)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/migrations.php';

$applied = runMigrations(getDB());

if (!$applied) {
    echo "Database is up to date.\n";
    exit;
}
foreach ($applied as $label) {
    echo "  applied: $label\n";
}
echo "\nApplied " . count($applied) . " migration(s).\n";
