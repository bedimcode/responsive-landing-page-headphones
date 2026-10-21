<?php
/**
 * User Dashboard — Overview
 */
$accountPageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$db  = getDB();
$uid = currentUserId();

// KPI data
$totalOrders   = getUserOrderCount($db, $uid);
$totalSpent    = getUserTotalSpent($db, $uid);
$wishlistItems = getWishlistCount($db, $uid);
$savedAddresses = $addressCount;

// Recent orders
$recentOrders = getUserOrders($db, $uid, 5);

// Wishlist preview
$wishlistPreview = [];
try {
    $stmt = $db->prepare('SELECT w.id AS wishlist_id, p.id, p.name, p.slug, p.price, p.sale_price, p.image, p.color, p.stock
                          FROM wishlists w JOIN products p ON w.product_id = p.id
                          WHERE w.user_id = ? ORDER BY w.created_at DESC LIMIT 4');
    $stmt->execute([$uid]);
    $wishlistPreview = $stmt->fetchAll();
} catch (Exception $e) {}
?>

<!-- KPI Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <!-- Total Orders -->
    <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl p-4 sm:p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-neon-blue/10 flex items-center justify-center">
                <i class="ri-shopping-bag-3-line text-neon-blue text-lg"></i>
            </div>
        </div>
        <div class="text-xl sm:text-2xl font-bold text-white font-display"><?= $totalOrders ?></div>
        <div class="text-[11px] sm:text-xs text-gray-500 mt-1">Total Orders</div>
    </div>

    <!-- Total Spent -->
    <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl p-4 sm:p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center">
                <i class="ri-money-dollar-circle-line text-green-400 text-lg"></i>
            </div>
        </div>
        <div class="text-xl sm:text-2xl font-bold text-white font-display"><?= formatPrice($totalSpent) ?></div>
        <div class="text-[11px] sm:text-xs text-gray-500 mt-1">Total Spent</div>
    </div>

    <!-- Wishlist -->
    <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl p-4 sm:p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-neon-pink/10 flex items-center justify-center">
                <i class="ri-heart-3-line text-neon-pink text-lg"></i>
            </div>
        </div>
        <div class="text-xl sm:text-2xl font-bold text-white font-display"><?= $wishlistItems ?></div>
        <div class="text-[11px] sm:text-xs text-gray-500 mt-1">Wishlist Items</div>
    </div>

    <!-- Addresses -->
    <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl p-4 sm:p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-neon-purple/10 flex items-center justify-center">
                <i class="ri-map-pin-line text-neon-purple text-lg"></i>
            </div>
        </div>
        <div class="text-xl sm:text-2xl font-bold text-white font-display"><?= $savedAddresses ?></div>
        <div class="text-[11px] sm:text-xs text-gray-500 mt-1">Saved Addresses</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <a href="<?= e($basePath) ?>/shop.php" class="flex items-center gap-3 p-3 sm:p-4 bg-dark-900/60 border border-white/[.06] rounded-xl hover:border-neon-blue/20 hover:bg-neon-blue/[.02] transition-all group">
        <i class="ri-store-2-line text-gray-500 group-hover:text-neon-blue transition-colors"></i>
        <span class="text-xs sm:text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Browse Shop</span>
    </a>
    <a href="<?= e($basePath) ?>/account/orders.php" class="flex items-center gap-3 p-3 sm:p-4 bg-dark-900/60 border border-white/[.06] rounded-xl hover:border-neon-blue/20 hover:bg-neon-blue/[.02] transition-all group">
        <i class="ri-file-list-3-line text-gray-500 group-hover:text-neon-blue transition-colors"></i>
        <span class="text-xs sm:text-sm font-medium text-gray-400 group-hover:text-white transition-colors">View Orders</span>
    </a>
    <a href="<?= e($basePath) ?>/account/wishlist.php" class="flex items-center gap-3 p-3 sm:p-4 bg-dark-900/60 border border-white/[.06] rounded-xl hover:border-neon-blue/20 hover:bg-neon-blue/[.02] transition-all group">
        <i class="ri-heart-3-line text-gray-500 group-hover:text-neon-pink transition-colors"></i>
        <span class="text-xs sm:text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Wishlist</span>
    </a>
    <a href="<?= e($basePath) ?>/account/profile.php" class="flex items-center gap-3 p-3 sm:p-4 bg-dark-900/60 border border-white/[.06] rounded-xl hover:border-neon-blue/20 hover:bg-neon-blue/[.02] transition-all group">
        <i class="ri-user-settings-line text-gray-500 group-hover:text-neon-purple transition-colors"></i>
        <span class="text-xs sm:text-sm font-medium text-gray-400 group-hover:text-white transition-colors">Edit Profile</span>
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-4 sm:gap-6">

    <!-- Recent Orders (takes 2 cols on lg) -->
    <div class="lg:col-span-2 bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-4 sm:px-5 py-4 border-b border-white/[.06]">
            <h2 class="font-display font-semibold text-white text-sm">Recent Orders</h2>
            <?php if ($totalOrders > 0): ?>
            <a href="<?= e($basePath) ?>/account/orders.php" class="text-[11px] text-neon-blue hover:text-white font-medium transition-colors">View All →</a>
            <?php endif; ?>
        </div>

        <?php if (empty($recentOrders)): ?>
        <div class="px-5 py-12 text-center">
            <i class="ri-shopping-bag-3-line text-4xl text-gray-700 mb-3 block"></i>
            <p class="text-sm text-gray-500 mb-4">You haven't placed any orders yet.</p>
            <a href="<?= e($basePath) ?>/shop.php" class="inline-flex items-center gap-2 px-4 py-2 bg-neon-blue/10 text-neon-blue text-sm font-medium rounded-xl hover:bg-neon-blue/20 transition-colors">
                <i class="ri-store-2-line"></i> Start Shopping
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/[.04]">
                        <th class="text-left px-4 sm:px-5 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="text-left px-3 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Date</th>
                        <th class="text-left px-3 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right px-4 sm:px-5 py-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[.04]">
                    <?php foreach ($recentOrders as $order): ?>
                    <tr class="hover:bg-white/[.02] transition-colors">
                        <td class="px-4 sm:px-5 py-3">
                            <a href="<?= e($basePath) ?>/account/order-view.php?id=<?= (int) $order['id'] ?>" class="font-medium text-white hover:text-neon-blue transition-colors text-xs sm:text-sm">
                                #<?= e($order['order_number']) ?>
                            </a>
                            <div class="text-[10px] text-gray-600 mt-0.5 sm:hidden"><?= date('M j, Y', strtotime($order['created_at'])) ?></div>
                        </td>
                        <td class="px-3 py-3 text-gray-400 text-xs hidden sm:table-cell"><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                        <td class="px-3 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold border <?= orderStatusBadge($order['status']) ?>">
                                <?= ucfirst(e($order['status'])) ?>
                            </span>
                        </td>
                        <td class="px-4 sm:px-5 py-3 text-right font-semibold text-white text-xs sm:text-sm"><?= formatPrice($order['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Wishlist Preview -->
    <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-4 sm:px-5 py-4 border-b border-white/[.06]">
            <h2 class="font-display font-semibold text-white text-sm">Wishlist</h2>
            <?php if (!empty($wishlistPreview)): ?>
            <a href="<?= e($basePath) ?>/account/wishlist.php" class="text-[11px] text-neon-blue hover:text-white font-medium transition-colors">View All →</a>
            <?php endif; ?>
        </div>

        <?php if (empty($wishlistPreview)): ?>
        <div class="px-5 py-12 text-center">
            <i class="ri-heart-3-line text-4xl text-gray-700 mb-3 block"></i>
            <p class="text-sm text-gray-500 mb-4">Your wishlist is empty.</p>
            <a href="<?= e($basePath) ?>/shop.php" class="inline-flex items-center gap-2 px-4 py-2 bg-neon-pink/10 text-neon-pink text-sm font-medium rounded-xl hover:bg-neon-pink/20 transition-colors">
                <i class="ri-heart-add-line"></i> Discover Products
            </a>
        </div>
        <?php else: ?>
        <div class="divide-y divide-white/[.04]">
            <?php foreach ($wishlistPreview as $item): ?>
            <a href="<?= e($basePath) ?>/product.php?slug=<?= e($item['slug']) ?>" class="flex items-center gap-3 px-4 sm:px-5 py-3 hover:bg-white/[.02] transition-colors">
                <img src="<?= e($basePath) ?>/<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>" class="w-10 h-10 object-contain rounded-lg bg-dark-800 flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <div class="text-[12px] sm:text-sm font-medium text-white truncate"><?= e($item['name']) ?></div>
                    <div class="text-[11px] text-gray-500"><?= e($item['color'] ?? '') ?></div>
                </div>
                <div class="text-sm font-semibold text-white flex-shrink-0">
                    <?= formatPrice($item['sale_price'] ?? $item['price']) ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
