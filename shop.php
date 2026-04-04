<?php
/**
 * Shop — Product Catalog
 */
$pageTitle       = 'Shop';
$pageDescription = 'Browse our collection of premium Beats 3 wireless headphones. Studio-grade sound, 40-hour battery, and Bluetooth 5.2 in 5 stunning colors.';
$pageType        = 'page';
$canonical       = APP_URL . '/shop.php';

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

// Filters
$categorySlug = $_GET['category'] ?? '';
$sort         = $_GET['sort'] ?? 'newest';
$search       = trim($_GET['q'] ?? '');

// Get data
$categories = getCategoriesWithCount($db);
$products   = getFilteredProducts($db, $categorySlug ?: null, $sort, $search ?: null);

// Active category name for title
$activeCategoryName = '';
if ($categorySlug) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $categorySlug) {
            $activeCategoryName = $cat['name'];
            $pageTitle = $cat['name'] . ' — Shop';
            break;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <?php
    $crumbs = [['label' => 'Home', 'url' => APP_URL . '/']];
    $crumbs[] = $activeCategoryName
        ? ['label' => 'Shop', 'url' => APP_URL . '/shop.php']
        : ['label' => 'Shop'];
    if ($activeCategoryName) $crumbs[] = ['label' => $activeCategoryName];
    echo breadcrumb($crumbs);
    ?>

    <!-- Page Header -->
    <div class="mb-6 sm:mb-8">
        <h1 class="font-display text-2xl sm:text-3xl lg:text-4xl font-bold text-white">
            <?= $activeCategoryName ? e($activeCategoryName) : 'All Products' ?>
        </h1>
        <p class="text-sm sm:text-base text-gray-400 mt-2">
            <?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> available
        </p>
    </div>

    <!-- Filters Bar -->
    <div class="flex flex-col sm:flex-row gap-3 mb-6 sm:mb-8">
        <!-- Search -->
        <form method="get" class="flex-1">
            <?php if ($categorySlug): ?><input type="hidden" name="category" value="<?= e($categorySlug) ?>"><?php endif; ?>
            <?php if ($sort !== 'newest'): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
            <div class="relative">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search products…"
                       class="w-full pl-9 pr-4 py-2.5 bg-dark-900/60 border border-white/[.06] rounded-xl text-sm text-white placeholder-gray-500
                              focus:outline-none focus:border-neon-blue/30 transition-colors">
            </div>
        </form>

        <!-- Category Filter -->
        <div class="flex flex-wrap gap-1.5">
            <a href="<?= APP_URL ?>/shop.php<?= $sort !== 'newest' ? '?sort=' . e($sort) : '' ?><?= $search ? ($sort !== 'newest' ? '&' : '?') . 'q=' . urlencode($search) : '' ?>"
               class="px-3 py-2 rounded-xl text-[11px] sm:text-xs font-medium border transition-all
                      <?= !$categorySlug ? 'bg-neon-blue/10 text-neon-blue border-neon-blue/20' : 'bg-dark-900/60 text-gray-400 border-white/[.06] hover:text-white hover:border-white/10' ?>">
                All
            </a>
            <?php foreach ($categories as $cat): if ($cat['product_count'] == 0) continue; ?>
            <a href="<?= APP_URL ?>/shop.php?category=<?= e($cat['slug']) ?><?= $sort !== 'newest' ? '&sort=' . e($sort) : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
               class="px-3 py-2 rounded-xl text-[11px] sm:text-xs font-medium border transition-all
                      <?= $categorySlug === $cat['slug'] ? 'bg-neon-blue/10 text-neon-blue border-neon-blue/20' : 'bg-dark-900/60 text-gray-400 border-white/[.06] hover:text-white hover:border-white/10' ?>">
                <?= e($cat['name']) ?> <span class="text-gray-600 ml-0.5"><?= $cat['product_count'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Sort -->
        <div class="relative">
            <select onchange="window.location.href=this.value"
                    class="appearance-none pl-3 pr-8 py-2.5 bg-dark-900/60 border border-white/[.06] rounded-xl text-xs text-gray-300 focus:outline-none focus:border-neon-blue/30 cursor-pointer transition-colors">
                <?php
                $sortOptions = ['newest' => 'Newest', 'price_asc' => 'Price: Low → High', 'price_desc' => 'Price: High → Low', 'name_asc' => 'Name: A–Z', 'name_desc' => 'Name: Z–A'];
                foreach ($sortOptions as $val => $label):
                    $url = APP_URL . '/shop.php?' . http_build_query(array_filter(['category' => $categorySlug, 'sort' => $val, 'q' => $search]));
                ?>
                <option value="<?= e($url) ?>" <?= $sort === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <i class="ri-arrow-down-s-line absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-500 text-sm pointer-events-none"></i>
        </div>
    </div>

    <!-- Product Grid -->
    <?php if (empty($products)): ?>
    <div class="text-center py-20">
        <i class="ri-headphone-line text-5xl text-gray-700 mb-4 block"></i>
        <p class="text-gray-400 mb-2">No products found.</p>
        <p class="text-sm text-gray-600 mb-6">Try adjusting your search or filters.</p>
        <a href="<?= APP_URL ?>/shop.php" class="text-sm text-neon-blue hover:text-white transition-colors">Clear Filters</a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5">
        <?php foreach ($products as $product):
            $effectivePrice = $product['sale_price'] ?? $product['price'];
            $inStock = $product['stock'] > 0;
        ?>
        <div class="group bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden hover:border-white/10 transition-all">
            <!-- Image -->
            <a href="<?= APP_URL ?>/product.php?slug=<?= e($product['slug']) ?>" class="block relative">
                <div class="aspect-square bg-dark-800 flex items-center justify-center p-6 sm:p-8">
                    <img src="<?= e(APP_URL) ?>/<?= e($product['image']) ?>" alt="<?= e($product['name']) ?> headphones"
                         class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300" loading="lazy">
                </div>
                <?php if ($product['sale_price']): ?>
                <div class="absolute top-3 left-3 px-2 py-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full">
                    -<?= round((1 - $product['sale_price'] / $product['price']) * 100) ?>%
                </div>
                <?php endif; ?>
                <?php if ($product['featured']): ?>
                <div class="absolute top-3 right-3 px-2 py-0.5 bg-neon-blue/20 text-neon-blue text-[10px] font-bold rounded-full border border-neon-blue/20">
                    Featured
                </div>
                <?php endif; ?>
                <?php if (!$inStock): ?>
                <div class="absolute inset-0 bg-dark-950/60 flex items-center justify-center">
                    <span class="px-3 py-1 bg-dark-950/80 text-red-400 text-xs font-semibold rounded-full">Out of Stock</span>
                </div>
                <?php endif; ?>
            </a>

            <!-- Info -->
            <div class="p-4">
                <a href="<?= APP_URL ?>/product.php?slug=<?= e($product['slug']) ?>" class="block">
                    <h3 class="font-semibold text-white text-sm truncate group-hover:text-neon-blue transition-colors"><?= e($product['name']) ?></h3>
                    <p class="text-xs text-gray-500 mt-0.5"><?= e($product['color']) ?><?= !empty($product['category_name']) ? ' · ' . e($product['category_name']) : '' ?></p>
                </a>

                <div class="flex items-center justify-between mt-3">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-white"><?= formatPrice($effectivePrice) ?></span>
                        <?php if ($product['sale_price']): ?>
                        <span class="text-xs text-gray-600 line-through"><?= formatPrice($product['price']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($inStock && $product['stock'] <= 10): ?>
                    <span class="text-[10px] text-yellow-400">Only <?= $product['stock'] ?> left</span>
                    <?php endif; ?>
                </div>

                <?php if ($inStock): ?>
                <button class="add-to-cart-btn w-full mt-3 flex items-center justify-center gap-1.5 px-3 py-2.5 bg-white/[.04] border border-white/[.06] text-sm font-medium text-gray-300 rounded-xl hover:bg-neon-blue hover:text-dark-950 hover:border-neon-blue transition-all"
                        data-id="<?= (int) $product['id'] ?>" data-name="<?= e($product['name']) ?>"
                        data-price="<?= $effectivePrice ?>" data-img="<?= e(APP_URL) ?>/<?= e($product['image']) ?>">
                    <i class="ri-shopping-cart-2-line text-sm"></i> Add to Cart
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- JSON-LD: ItemList for SEO -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ItemList",
    "name": "<?= e($activeCategoryName ?: 'All Products') ?>",
    "numberOfItems": <?= count($products) ?>,
    "itemListElement": [
        <?php foreach ($products as $i => $p): if ($i > 0) echo ','; ?>
        {
            "@type": "ListItem",
            "position": <?= $i + 1 ?>,
            "item": {
                "@type": "Product",
                "name": <?= json_encode($p['name']) ?>,
                "url": <?= json_encode(APP_URL . '/product.php?slug=' . $p['slug']) ?>,
                "image": <?= json_encode(APP_URL . '/' . $p['image']) ?>,
                "offers": {
                    "@type": "Offer",
                    "price": "<?= number_format($p['sale_price'] ?? $p['price'], 2, '.', '') ?>",
                    "priceCurrency": "USD",
                    "availability": "<?= $p['stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' ?>"
                }
            }
        }
        <?php endforeach; ?>
    ]
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
