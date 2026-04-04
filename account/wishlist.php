<?php
/**
 * User Dashboard — Wishlist
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAuth();

$db  = getDB();
$uid = currentUserId();

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    if (validateCsrf()) {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $db->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')->execute([$uid, $productId]);
        setFlash('success', 'Item removed from wishlist.');
        redirect(APP_URL . '/account/wishlist.php');
    }
}

$items = getWishlistItems($db, $uid);

$accountPageTitle = 'My Wishlist';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500"><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?> in your wishlist</p>
    <a href="<?= e($basePath) ?>/shop.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white/[.04] border border-white/[.06] text-sm font-medium text-gray-300 rounded-xl hover:text-white hover:border-white/10 transition-all">
        <i class="ri-store-2-line text-sm"></i> Browse Shop
    </a>
</div>

<?php if (empty($items)): ?>
<div class="bg-dark-900/60 border border-white/[.06] rounded-2xl px-5 py-16 text-center">
    <i class="ri-heart-3-line text-5xl text-gray-700 mb-4 block"></i>
    <p class="text-gray-400 mb-2">Your wishlist is empty.</p>
    <p class="text-sm text-gray-600 mb-6">Browse our products and save your favorites here.</p>
    <a href="<?= e($basePath) ?>/shop.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-neon-blue text-dark-950 text-sm font-semibold rounded-xl hover:bg-neon-blue/90 transition-colors">
        <i class="ri-heart-add-line"></i> Discover Products
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($items as $item):
        $effectivePrice = $item['sale_price'] ?? $item['price'] ?? 0;
        $inStock = ($item['stock'] ?? 0) > 0;
    ?>
    <div class="group bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden hover:border-white/10 transition-all">
        <!-- Image -->
        <a href="<?= e($basePath) ?>/product.php?slug=<?= e($item['slug'] ?? '') ?>" class="block relative">
            <div class="aspect-square bg-dark-800 flex items-center justify-center p-4 sm:p-6">
                <img src="<?= e($basePath) ?>/<?= e($item['image'] ?? '') ?>" alt="<?= e($item['name'] ?? 'Product') ?>"
                     class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300">
            </div>
            <?php if ($item['sale_price']): ?>
            <div class="absolute top-3 left-3 px-2 py-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full">SALE</div>
            <?php endif; ?>
            <?php if (!$inStock): ?>
            <div class="absolute inset-0 bg-dark-950/60 flex items-center justify-center">
                <span class="px-3 py-1 bg-dark-950/80 text-red-400 text-xs font-semibold rounded-full">Out of Stock</span>
            </div>
            <?php endif; ?>
        </a>

        <!-- Info -->
        <div class="p-4">
            <a href="<?= e($basePath) ?>/product.php?slug=<?= e($item['slug'] ?? '') ?>" class="block">
                <h3 class="font-semibold text-white text-sm truncate group-hover:text-neon-blue transition-colors"><?= e($item['name'] ?? 'Product') ?></h3>
                <p class="text-xs text-gray-500 mt-0.5"><?= e($item['color'] ?? '') ?></p>
            </a>
            <div class="flex items-center gap-2 mt-2">
                <span class="font-bold text-white text-sm"><?= formatPrice($effectivePrice) ?></span>
                <?php if (!empty($item['sale_price'])): ?>
                <span class="text-xs text-gray-600 line-through"><?= formatPrice($item['price'] ?? 0) ?></span>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 mt-3">
                <?php if ($inStock): ?>
                <button class="flex-1 add-to-cart-btn flex items-center justify-center gap-1.5 px-3 py-2 bg-neon-blue text-dark-950 text-xs font-semibold rounded-xl hover:bg-neon-blue/90 transition-colors"
                        data-id="<?= (int) $item['id'] ?>" data-name="<?= e($item['name'] ?? 'Product') ?>" data-price="<?= $effectivePrice ?>" data-img="<?= e($basePath) ?>/<?= e($item['image'] ?? '') ?>">
                    <i class="ri-shopping-cart-2-line text-sm"></i> Add to Cart
                </button>
                <?php else: ?>
                <span class="flex-1 flex items-center justify-center px-3 py-2 bg-gray-800/50 text-gray-500 text-xs font-medium rounded-xl cursor-not-allowed">
                    Unavailable
                </span>
                <?php endif; ?>
                <form method="post" class="inline">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                    <?= csrfField() ?>
                    <button type="submit" title="Remove from Wishlist"
                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-white/[.04] border border-white/[.06] text-red-400/70 hover:text-red-400 hover:border-red-500/20 transition-all">
                        <i class="ri-heart-fill text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
