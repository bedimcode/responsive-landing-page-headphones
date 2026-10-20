<?php
/**
 * XML Sitemap — served at /sitemap.xml (see .htaccess)
 * Built from the database so new products appear automatically.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = [
    ['loc' => absoluteUrl('/'),         'changefreq' => 'weekly', 'priority' => '1.0'],
    ['loc' => absoluteUrl('/shop.php'), 'changefreq' => 'daily',  'priority' => '0.9'],
];

try {
    $db = getDB();
    foreach (getCategoriesWithCount($db) as $cat) {
        if (!$cat['product_count']) continue;
        $urls[] = ['loc' => absoluteUrl('/shop.php?category=' . rawurlencode($cat['slug'])), 'changefreq' => 'weekly', 'priority' => '0.8'];
    }
    foreach ($db->query('SELECT slug, updated_at FROM products WHERE is_active = 1 ORDER BY id')->fetchAll() as $p) {
        $urls[] = [
            'loc'        => absoluteUrl('/product.php?slug=' . rawurlencode($p['slug'])),
            'lastmod'    => date('Y-m-d', strtotime($p['updated_at'])),
            'changefreq' => 'weekly',
            'priority'   => '0.8',
        ];
    }
} catch (Exception $e) {
    error_log('Sitemap: ' . $e->getMessage());
}

foreach (['contact.php' => '0.5', 'legal/terms-of-service.php' => '0.3', 'legal/privacy-policy.php' => '0.3', 'legal/cookie-policy.php' => '0.3'] as $path => $priority) {
    $urls[] = ['loc' => absoluteUrl($path), 'changefreq' => 'yearly', 'priority' => $priority];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    echo "    <url>\n";
    foreach ($url as $tag => $value) {
        echo "        <$tag>" . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</$tag>\n";
    }
    echo "    </url>\n";
}
echo "</urlset>\n";
