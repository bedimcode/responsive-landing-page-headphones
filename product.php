<?php
/**
 * Product Detail Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$db   = getDB();
$slug = $_GET['slug'] ?? '';

if (!$slug) { header('Location: ' . APP_URL . '/shop.php'); exit; }

$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ? AND p.is_active = 1
    LIMIT 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

// Get color variants (other products in same category)
$stmtVariants = $db->prepare("
    SELECT id, name, slug, color, image, price, sale_price, stock
    FROM products
    WHERE category_id = ? AND id != ? AND is_active = 1
    ORDER BY name
");
$stmtVariants->execute([$product['category_id'], $product['id']]);
$variants = $stmtVariants->fetchAll(PDO::FETCH_ASSOC);

// Related products (in stock, random)
$stmtRelated = $db->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id != ? AND p.stock > 0 AND p.is_active = 1
    ORDER BY RAND()
    LIMIT 4
");
$stmtRelated->execute([$product['id']]);
$related = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);

// Wishlist check
$isWished = false;
if (isLoggedIn()) {
    $isWished = isInWishlist($db, $_SESSION['user_id'], $product['id']);
}

$effectivePrice = effectivePrice($product);
$inStock        = $product['stock'] > 0;
$discount       = isOnSale($product) ? (int) round((1 - $effectivePrice / $product['price']) * 100) : 0;
$lowStockAt     = (int) setting('low_stock_threshold');
$sku            = $product['sku'] ?: 'P-' . str_pad((string) $product['id'], 4, '0', STR_PAD_LEFT);

$pageTitle       = $product['name'] . ' — ' . storeName();
$pageDescription = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($product['short_description'] ?: $product['description']))), 0, 155);
$pageType        = 'product';
$ogImage         = $product['image'];
$canonical       = absoluteUrl('/product.php?slug=' . rawurlencode($product['slug']));

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-24">
    <!-- Breadcrumb -->
    <?php echo breadcrumb(array_values(array_filter([
        ['label' => 'Home', 'url' => APP_URL . '/'],
        ['label' => 'Shop', 'url' => APP_URL . '/shop.php'],
        $product['category_name'] ? ['label' => $product['category_name'], 'url' => APP_URL . '/shop.php?category=' . $product['category_slug']] : null,
        ['label' => $product['name']]
    ]))); ?>

    <!-- Product Main -->
    <div class="grid lg:grid-cols-2 gap-6 lg:gap-10 mb-12 sm:mb-16">
        <!-- Image -->
        <div class="relative bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden">
            <div class="aspect-square flex items-center justify-center p-8 sm:p-12 lg:p-16">
                <img src="<?= e(APP_URL) ?>/<?= e($product['image']) ?>" alt="<?= e($product['name']) ?> headphones"
                     class="w-full h-full object-contain" id="productImage">
            </div>
            <?php if ($discount): ?>
            <div class="absolute top-4 left-4 px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                -<?= $discount ?>%
            </div>
            <?php endif; ?>
            <?php if ($product['featured']): ?>
            <div class="absolute top-4 right-4 px-3 py-1 bg-neon-blue/20 text-neon-blue text-xs font-bold rounded-full border border-neon-blue/20">
                <i class="ri-star-fill mr-1"></i>Featured
            </div>
            <?php endif; ?>
        </div>

        <!-- Details -->
        <div class="flex flex-col">
            <!-- Category -->
            <?php if ($product['category_name']): ?>
            <a href="<?= APP_URL ?>/shop.php?category=<?= e($product['category_slug']) ?>"
               class="text-xs text-neon-blue font-medium uppercase tracking-wider mb-2 hover:text-white transition-colors">
                <?= e($product['category_name']) ?>
            </a>
            <?php endif; ?>

            <h1 class="font-display text-2xl sm:text-3xl lg:text-4xl font-bold text-white leading-tight"><?= e($product['name']) ?></h1>

            <!-- Color -->
            <?php if ($product['color']): ?>
            <p class="text-sm text-gray-400 mt-2">Color: <span class="text-gray-300 font-medium"><?= e($product['color']) ?></span></p>
            <?php endif; ?>

            <!-- Price -->
            <div class="flex items-center gap-3 mt-4">
                <span class="font-display text-3xl font-bold text-white"><?= formatPrice($effectivePrice) ?></span>
                <?php if ($discount): ?>
                <span class="text-lg text-gray-600 line-through"><?= formatPrice($product['price']) ?></span>
                <span class="px-2.5 py-0.5 bg-red-500/10 text-red-400 text-sm font-semibold rounded-full">Save <?= formatPrice($product['price'] - $effectivePrice) ?></span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="mt-5 text-gray-400 text-sm leading-relaxed"><?= nl2br(e($product['description'])) ?></div>

            <!-- Stock -->
            <div class="flex items-center gap-2 mt-5">
                <?php if ($inStock): ?>
                    <span class="w-2 h-2 rounded-full bg-green-400"></span>
                    <span class="text-sm text-green-400 font-medium">In Stock</span>
                    <?php if ($product['stock'] <= $lowStockAt): ?>
                    <span class="text-xs text-yellow-400 ml-1">(Only <?= (int) $product['stock'] ?> left)</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="w-2 h-2 rounded-full bg-red-400"></span>
                    <span class="text-sm text-red-400 font-medium">Out of Stock</span>
                <?php endif; ?>
            </div>

            <!-- Color Variants -->
            <?php if (!empty($variants)): ?>
            <div class="mt-6">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-3">Other Colors</p>
                <div class="flex flex-wrap gap-2">
                    <!-- Current -->
                    <div class="px-3 py-2 bg-neon-blue/10 border border-neon-blue/30 text-neon-blue text-xs font-medium rounded-xl cursor-default">
                        <?= e($product['color']) ?>
                    </div>
                    <?php foreach ($variants as $v): ?>
                    <a href="<?= APP_URL ?>/product.php?slug=<?= e($v['slug']) ?>"
                       class="px-3 py-2 bg-dark-900/60 border border-white/[.06] text-gray-400 text-xs font-medium rounded-xl hover:text-white hover:border-white/10 transition-colors">
                        <?= e($v['color']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="mt-auto pt-6">
                <?php if ($inStock): ?>
                <div class="flex flex-col sm:flex-row gap-3">
                    <!-- Quantity -->
                    <div class="flex items-center border border-white/[.06] rounded-xl overflow-hidden">
                        <button type="button" onclick="changeQty(-1)"
                                class="px-3 py-3 text-gray-400 hover:text-white hover:bg-white/[.04] transition-colors">
                            <i class="ri-subtract-line"></i>
                        </button>
                        <label for="qty" class="sr-only">Quantity</label>
                        <input type="number" id="qty" value="1" min="1" max="<?= (int) $product['stock'] ?>"
                               class="w-12 text-center bg-transparent text-white text-sm font-medium focus:outline-none
                                      [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                        <button type="button" onclick="changeQty(1)"
                                class="px-3 py-3 text-gray-400 hover:text-white hover:bg-white/[.04] transition-colors">
                            <i class="ri-add-line"></i>
                        </button>
                    </div>

                    <!-- Add to Cart -->
                    <button id="addToCartBtn"
                            class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-neon-blue text-dark-950 font-semibold rounded-xl hover:bg-white transition-colors"
                            data-id="<?= (int) $product['id'] ?>" data-name="<?= e($product['name']) ?>"
                            data-price="<?= $effectivePrice ?>" data-img="<?= e(APP_URL) ?>/<?= e($product['image']) ?>">
                        <i class="ri-shopping-cart-2-line"></i> Add to Cart
                    </button>

                    <!-- Wishlist -->
                    <button id="wishlistBtn"
                            class="flex items-center justify-center gap-2 px-4 py-3 border rounded-xl transition-all
                                   <?= $isWished ? 'bg-red-500/10 text-red-400 border-red-500/20' : 'bg-dark-900/60 text-gray-400 border-white/[.06] hover:text-red-400 hover:border-red-400/20' ?>"
                            data-product-id="<?= (int) $product['id'] ?>">
                        <i class="<?= $isWished ? 'ri-heart-fill' : 'ri-heart-line' ?> text-lg"></i>
                        <span class="sm:hidden"><?= $isWished ? 'Saved' : 'Save' ?></span>
                    </button>
                </div>
                <?php else: ?>
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-dark-800 text-gray-600 font-semibold rounded-xl cursor-not-allowed">
                        Out of Stock
                    </div>
                    <button id="wishlistBtn"
                            class="flex items-center justify-center gap-2 px-4 py-3 border rounded-xl transition-all
                                   <?= $isWished ? 'bg-red-500/10 text-red-400 border-red-500/20' : 'bg-dark-900/60 text-gray-400 border-white/[.06] hover:text-red-400 hover:border-red-400/20' ?>"
                            data-product-id="<?= (int) $product['id'] ?>">
                        <i class="<?= $isWished ? 'ri-heart-fill' : 'ri-heart-line' ?> text-lg"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- SKU / Features -->
            <div class="flex flex-wrap gap-x-6 gap-y-1 mt-5 text-xs text-gray-500">
                <span>SKU: <?= e($sku) ?></span>
                <span><i class="ri-truck-line mr-1"></i><?= e(shippingSummary()) ?></span>
                <span><i class="ri-time-line mr-1"></i>Delivery in <?= e(setting('delivery_estimate')) ?></span>
                <?php if (warrantyText()): ?>
                <span><i class="ri-shield-check-line mr-1"></i><?= e(ucfirst(warrantyText())) ?> warranty</span>
                <?php endif; ?>
                <?php if ((int) setting('return_days') > 0): ?>
                <span><i class="ri-refresh-line mr-1"></i><?= (int) setting('return_days') ?>-day returns</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related)): ?>
    <section class="pb-8">
        <div class="flex items-center justify-between mb-5 sm:mb-6">
            <h2 class="font-display text-xl sm:text-2xl font-bold text-white">You May Also Like</h2>
            <a href="<?= APP_URL ?>/shop.php" class="text-xs text-neon-blue hover:text-white transition-colors">View All →</a>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
            <?php foreach ($related as $rp):
                $rPrice = effectivePrice($rp);
            ?>
            <div class="group bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden hover:border-white/10 transition-all">
                <a href="<?= APP_URL ?>/product.php?slug=<?= e($rp['slug']) ?>" class="block relative">
                    <div class="aspect-square bg-dark-800 flex items-center justify-center p-4 sm:p-6">
                        <img src="<?= e(APP_URL) ?>/<?= e($rp['image']) ?>" alt="<?= e($rp['name']) ?>"
                             class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300" loading="lazy">
                    </div>
                    <?php if (isOnSale($rp)): ?>
                    <div class="absolute top-2 left-2 px-2 py-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full">Sale</div>
                    <?php endif; ?>
                </a>
                <div class="p-3 sm:p-4">
                    <a href="<?= APP_URL ?>/product.php?slug=<?= e($rp['slug']) ?>" class="block">
                        <h3 class="font-semibold text-white text-xs sm:text-sm truncate group-hover:text-neon-blue transition-colors"><?= e($rp['name']) ?></h3>
                    </a>
                    <div class="flex items-center gap-2 mt-1.5">
                        <span class="font-bold text-white text-sm"><?= formatPrice($rPrice) ?></span>
                        <?php if (isOnSale($rp)): ?>
                        <span class="text-[10px] text-gray-600 line-through"><?= formatPrice($rp['price']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<!-- Product Quantity Script -->
<script>
function changeQty(delta) {
    const input = document.getElementById('qty');
    let val = parseInt(input.value) || 1;
    val = Math.max(1, Math.min(<?= (int) $product['stock'] ?>, val + delta));
    input.value = val;
}

// Add to cart with quantity
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('addToCartBtn');
    if (btn) {
        btn.addEventListener('click', function() {
            const qty = parseInt(document.getElementById('qty').value) || 1;
            if (typeof Cart !== 'undefined') {
                Cart.addItem(this.dataset.id, this.dataset.name, parseFloat(this.dataset.price), this.dataset.img, qty);
                // Feedback
                const orig = this.innerHTML;
                this.innerHTML = '<i class="ri-check-line"></i> Added!';
                this.classList.add('bg-green-500');
                this.classList.remove('bg-neon-blue');
                setTimeout(() => {
                    this.innerHTML = orig;
                    this.classList.remove('bg-green-500');
                    this.classList.add('bg-neon-blue');
                }, 1500);
            }
        });
    }

    // Wishlist toggle
    const wbtn = document.getElementById('wishlistBtn');
    if (wbtn) {
        wbtn.addEventListener('click', async function() {
            try {
                const res = await fetch('<?= e(APP_URL) ?>/api/wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ product_id: this.dataset.productId })
                });
                const data = await res.json();
                if (data.success) {
                    const icon = this.querySelector('i');
                    const label = this.querySelector('span');
                    if (data.action === 'added') {
                        icon.className = 'ri-heart-fill text-lg';
                        this.classList.add('bg-red-500/10', 'text-red-400', 'border-red-500/20');
                        this.classList.remove('bg-dark-900/60', 'text-gray-400', 'border-white/[.06]');
                        if (label) label.textContent = 'Saved';
                    } else {
                        icon.className = 'ri-heart-line text-lg';
                        this.classList.remove('bg-red-500/10', 'text-red-400', 'border-red-500/20');
                        this.classList.add('bg-dark-900/60', 'text-gray-400', 'border-white/[.06]');
                        if (label) label.textContent = 'Save';
                    }
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } catch (e) { console.error('Wishlist error', e); }
        });
    }
});
</script>

<!-- JSON-LD: Product -->
<script type="application/ld+json">
<?= jsonForScript(array_filter([
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => $product['name'],
    'description' => mb_substr(trim(strip_tags($product['description'] ?? '')), 0, 300),
    'image'       => absoluteUrl($product['image']),
    'sku'         => $sku,
    'color'       => $product['color'] ?: null,
    'category'    => $product['category_name'] ?: null,
    'offers'      => [
        '@type'         => 'Offer',
        'url'           => $canonical,
        'price'         => number_format($effectivePrice, 2, '.', ''),
        'priceCurrency' => setting('currency_code'),
        'availability'  => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'itemCondition' => 'https://schema.org/NewCondition',
        'seller'        => ['@type' => 'Organization', 'name' => storeName()],
    ],
])) ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
