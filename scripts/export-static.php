<?php
/**
 * Static Export — Renders the public storefront into static-site/
 * for the display-only Vercel deployment (Vercel runs no PHP or MySQL).
 *
 * Requires the local XAMPP site (Apache + MySQL) to be running.
 * Usage: C:\xampp\php\php.exe scripts/export-static.php [base-url]
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/includes/config.php';

$baseUrl = rtrim($argv[1] ?? APP_URL, '/');
$outDir  = BASE_PATH . '/static-site';

// ============ CATALOG ============
$db         = getDB();
$products   = $db->query('SELECT id, name, slug, color, price, sale_price, image, stock FROM products WHERE is_active = 1 ORDER BY id')->fetchAll();
$categories = $db->query('SELECT slug FROM categories ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

// ============ PAGES ============
// Pages with no dynamic query string; links to them become /path
$staticRoutes = [
    'contact',
    'legal/privacy-policy',
    'legal/terms-of-service',
    'legal/cookie-policy',
    'auth/login',
    'auth/register',
    'auth/forgot-password',
];

// source URL (relative to base) => [output file, expected HTTP status]
$pages = [
    ''         => ['index.html', 200],
    'shop.php' => ['shop.html', 200],
    '404.php'  => ['404.html', 404],
];
foreach ($staticRoutes as $route) {
    $pages[$route . '.php'] = [$route . '.html', 200];
}
foreach ($categories as $slug) {
    $pages['shop.php?category=' . rawurlencode($slug)] = ["shop/$slug.html", 200];
}
foreach ($products as $p) {
    $pages['product.php?slug=' . rawurlencode($p['slug'])] = ["product/{$p['slug']}.html", 200];
}

// ============ HELPERS ============
function fetchPage(string $url, int $expectedStatus): string {
    $context = stream_context_create(['http' => [
        'follow_location' => 0,
        'ignore_errors'   => true,
        'timeout'         => 30,
    ]]);
    $html   = @file_get_contents($url, false, $context);
    $status = preg_match('#^HTTP/\S+\s(\d{3})#', $http_response_header[0] ?? '', $m) ? (int) $m[1] : 0;

    if ($html === false || $status !== $expectedStatus) {
        fwrite(STDERR, "Failed: $url (HTTP $status, expected $expectedStatus). Is XAMPP running?\n");
        exit(1);
    }
    return $html;
}

function rewriteHtml(string $html, string $baseUrl, array $staticRoutes): string {
    // Absolute and base-path links → site root (plain and JSON-escaped forms)
    $basePath = rtrim(parse_url($baseUrl, PHP_URL_PATH) ?? '', '/');
    foreach (array_filter([$baseUrl, $basePath]) as $prefix) {
        $html = str_replace([$prefix, str_replace('/', '\/', $prefix)], '', $html);
    }

    // .php links → clean static URLs (api/ calls are left for demo-mode.js)
    $html = preg_replace_callback(
        '#(?<=["\'(=\s])(\\\\?/)([a-z0-9\-]+(?:\\\\?/[a-z0-9\-]+)*)\.php(\?[^"\'\s<>\#]*)?#i',
        function (array $m) use ($staticRoutes): string {
            $path = str_replace('\/', '/', $m[2]);
            parse_str(html_entity_decode(ltrim($m[3] ?? '', '?')), $query);

            $target = match (true) {
                $path === 'index'                              => '/',
                $path === '404'                                => '/404',
                $path === 'product' && isset($query['slug'])   => '/product/' . rawurlencode($query['slug']),
                $path === 'shop' && !empty($query['category']) => '/shop/' . rawurlencode($query['category']),
                $path === 'shop'                               => '/shop',
                $path === 'checkout'                           => '/auth/register',
                in_array($path, $staticRoutes, true)           => '/' . $path,
                (bool) preg_match('#^(account|admin)/|^auth/logout$#', $path) => '/auth/login',
                default                                        => null,
            };
            if ($target === null) return $m[0];
            return $m[1] === '\/' ? str_replace('/', '\/', $target) : $target;
        },
        $html
    );

    // Session CSRF tokens mean nothing on a static host
    $html = preg_replace('#(<meta name="csrf-token" content=")[^"]*#', '$1', $html);
    $html = preg_replace('#(name="csrf_token" value=")[^"]*#', '$1', $html);

    // Demo shim must load before cart.js
    return preg_replace('#<head\b[^>]*>#i', "$0\n    <script src=\"/assets/js/demo-mode.js\"></script>", $html, 1);
}

function writeFile(string $path, string $contents): void {
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    file_put_contents($path, $contents);
}

function removeDir(string $dir): void {
    if (!is_dir($dir)) return;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

function copyDir(string $src, string $dst): void {
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        $target = $dst . '/' . $items->getSubPathname();
        $item->isDir() ? @mkdir($target, 0755, true) : writeFile($target, file_get_contents($item->getPathname()));
    }
}

// ============ EXPORT ============
removeDir($outDir);
mkdir($outDir, 0755, true);

foreach ($pages as $source => [$file, $status]) {
    $html = fetchPage($baseUrl . '/' . $source, $status);
    writeFile("$outDir/$file", rewriteHtml($html, $baseUrl, $staticRoutes));
    echo "  $file\n";
}

copyDir(BASE_PATH . '/assets', "$outDir/assets");

$demoProducts = array_map(fn($p) => [
    'id'         => (int) $p['id'],
    'name'       => $p['name'],
    'slug'       => $p['slug'],
    'color'      => $p['color'],
    'price'      => (float) $p['price'],
    'sale_price' => $p['sale_price'] !== null ? (float) $p['sale_price'] : null,
    'image'      => $p['image'],
    'stock'      => (int) $p['stock'],
], $products);
$shim = file_get_contents(__DIR__ . '/static/demo-mode.js');
$shim = str_replace('/*__DEMO_PRODUCTS__*/[]', json_encode($demoProducts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $shim);
writeFile("$outDir/assets/js/demo-mode.js", $shim);

$robots = preg_replace('#^Sitemap:.*\R?#mi', '', file_get_contents(BASE_PATH . '/robots.txt'));
writeFile("$outDir/robots.txt", $robots);

// ============ LEFTOVER CHECK ============
$leftovers = [];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($outDir, FilesystemIterator::SKIP_DOTS));
foreach ($files as $f) {
    if ($f->getExtension() !== 'html') continue;
    preg_match_all('#localhost|headphones1\.duka|[\w\-/\\\\]*\.php[^"\'\s<>]*#', file_get_contents($f->getPathname()), $m);
    foreach ($m[0] as $hit) {
        if (!preg_match('#(^|/)api/#', $hit)) $leftovers[$hit] = true;
    }
}

echo "\nExported " . count($pages) . " pages to static-site/\n";
if ($leftovers) {
    echo "Warning: unconverted links remain:\n  " . implode("\n  ", array_keys($leftovers)) . "\n";
}
