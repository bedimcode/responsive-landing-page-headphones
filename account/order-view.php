<?php
/**
 * User Dashboard — Order Detail View
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAuth();

$db  = getDB();
$uid = currentUserId();

$orderId = (int) ($_GET['id'] ?? 0);
if (!$orderId) {
    setFlash('error', 'Invalid order.');
    redirect(APP_URL . '/account/orders.php');
}

// Fetch order (must belong to the current user)
$stmt = $db->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$orderId, $uid]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect(APP_URL . '/account/orders.php');
}

// Fetch order items
$stmt = $db->prepare('SELECT oi.*, p.slug AS product_slug FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Fetch status history
$history = getOrderStatusHistory($db, $orderId);

$accountPageTitle = 'Order #' . $order['order_number'];
require_once __DIR__ . '/includes/header.php';
?>

<!-- Back link -->
<div class="mb-4 sm:mb-6">
    <a href="<?= e($basePath) ?>/account/orders.php" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-white transition-colors">
        <i class="ri-arrow-left-line"></i> Back to Orders
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-4 sm:gap-6">

    <!-- Left: Order Items (2 cols) -->
    <div class="lg:col-span-2 space-y-4 sm:space-y-6">

        <!-- Order Items -->
        <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-white/[.06]">
                <h2 class="font-display font-semibold text-white text-sm">Order Items</h2>
            </div>
            <div class="divide-y divide-white/[.04]">
                <?php foreach ($items as $item): ?>
                <div class="flex items-center gap-3 sm:gap-4 px-4 sm:px-5 py-3 sm:py-4">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl bg-dark-800 flex items-center justify-center flex-shrink-0 overflow-hidden">
                        <?php if (!empty($item['product_image'])): ?>
                        <img src="<?= e($basePath) ?>/<?= e($item['product_image']) ?>" alt="<?= e($item['product_name'] ?? 'Product') ?>" class="w-full h-full object-contain p-1">
                        <?php else: ?>
                        <i class="ri-headphone-line text-2xl text-gray-600"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <?php if (!empty($item['product_slug'])): ?>
                        <a href="<?= e($basePath) ?>/product.php?slug=<?= e($item['product_slug']) ?>" class="text-sm font-medium text-white hover:text-neon-blue transition-colors"><?= e($item['product_name'] ?? 'Product') ?></a>
                        <?php else: ?>
                        <div class="text-sm font-medium text-white"><?= e($item['product_name'] ?? 'Product') ?></div>
                        <?php endif; ?>
                        <div class="text-xs text-gray-500 mt-0.5">Qty: <?= (int) $item['quantity'] ?> × <?= formatPrice($item['price']) ?></div>
                    </div>
                    <div class="text-sm font-semibold text-white flex-shrink-0">
                        <?= formatPrice($item['price'] * $item['quantity']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Totals -->
            <div class="border-t border-white/[.06] px-4 sm:px-5 py-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Subtotal</span>
                    <span class="text-gray-300"><?= formatPrice($order['subtotal']) ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Shipping</span>
                    <span class="text-gray-300"><?= $order['shipping_cost'] > 0 ? formatPrice($order['shipping_cost']) : 'Free' ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Tax</span>
                    <span class="text-gray-300"><?= formatPrice($order['tax']) ?></span>
                </div>
                <div class="flex justify-between text-sm font-semibold pt-2 border-t border-white/[.06]">
                    <span class="text-white">Total</span>
                    <span class="text-white text-base"><?= formatPrice($order['total']) ?></span>
                </div>
            </div>
        </div>

        <!-- Status Timeline -->
        <?php if (!empty($history)): ?>
        <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-white/[.06]">
                <h2 class="font-display font-semibold text-white text-sm">Order Timeline</h2>
            </div>
            <div class="px-4 sm:px-5 py-4">
                <div class="space-y-4">
                    <?php foreach ($history as $i => $h):
                        $isLast = $i === count($history) - 1;
                    ?>
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="w-3 h-3 rounded-full <?= $isLast ? 'bg-neon-blue' : 'bg-gray-600' ?> flex-shrink-0 mt-1"></div>
                            <?php if (!$isLast): ?>
                            <div class="w-0.5 flex-1 bg-white/[.06] mt-1"></div>
                            <?php endif; ?>
                        </div>
                        <div class="pb-4 min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border <?= orderStatusBadge($h['new_status']) ?>">
                                    <?= ucfirst(e($h['new_status'])) ?>
                                </span>
                                <span class="text-[11px] text-gray-600"><?= date('M j, Y g:ia', strtotime($h['created_at'])) ?></span>
                            </div>
                            <?php if ($h['note']): ?>
                            <p class="text-xs text-gray-500 mt-1"><?= e($h['note']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Order Info -->
    <div class="space-y-4 sm:space-y-6">

        <!-- Order Info -->
        <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-white/[.06]">
                <h2 class="font-display font-semibold text-white text-sm">Order Info</h2>
            </div>
            <div class="px-4 sm:px-5 py-4 space-y-3">
                <div>
                    <div class="text-[10px] text-gray-600 uppercase tracking-wider mb-1">Order Number</div>
                    <div class="text-sm text-white font-medium">#<?= e($order['order_number']) ?></div>
                </div>
                <div>
                    <div class="text-[10px] text-gray-600 uppercase tracking-wider mb-1">Date</div>
                    <div class="text-sm text-gray-300"><?= date('F j, Y \a\t g:ia', strtotime($order['created_at'])) ?></div>
                </div>
                <div>
                    <div class="text-[10px] text-gray-600 uppercase tracking-wider mb-1">Status</div>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold border <?= orderStatusBadge($order['status']) ?>">
                        <?= ucfirst(e($order['status'])) ?>
                    </span>
                </div>
                <div>
                    <div class="text-[10px] text-gray-600 uppercase tracking-wider mb-1">Payment</div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-300 capitalize"><?= e($order['payment_method']) ?></span>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium <?= paymentStatusBadge($order['payment_status']) ?>">
                            <?= ucfirst(e($order['payment_status'])) ?>
                        </span>
                    </div>
                </div>
                <?php if ($order['tracking_number']): ?>
                <div>
                    <div class="text-[10px] text-gray-600 uppercase tracking-wider mb-1">Tracking Number</div>
                    <div class="text-sm text-neon-blue font-medium"><?= e($order['tracking_number']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shipping Address -->
        <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-white/[.06]">
                <h2 class="font-display font-semibold text-white text-sm">Shipping Address</h2>
            </div>
            <div class="px-4 sm:px-5 py-4 text-sm text-gray-300 space-y-1">
                <div class="font-medium text-white"><?= e($order['shipping_name']) ?></div>
                <div><?= e($order['shipping_address']) ?></div>
                <div><?= e($order['shipping_city']) ?><?= $order['shipping_state'] ? ', ' . e($order['shipping_state']) : '' ?> <?= e($order['shipping_zip']) ?></div>
                <div><?= e($order['shipping_country']) ?></div>
                <?php if ($order['shipping_phone']): ?>
                <div class="text-gray-500 pt-1"><?= e($order['shipping_phone']) ?></div>
                <?php endif; ?>
                <div class="text-gray-500"><?= e($order['shipping_email']) ?></div>
            </div>
        </div>

        <!-- Need Help -->
        <div class="bg-dark-900/60 border border-white/[.06] rounded-2xl p-4 sm:p-5">
            <h3 class="font-semibold text-white text-sm mb-2">Need Help?</h3>
            <p class="text-xs text-gray-500 mb-3">If you have questions about this order, contact our support team.</p>
            <a href="<?= e($basePath) ?>/contact.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white/[.04] border border-white/[.06] text-sm font-medium text-gray-300 rounded-xl hover:text-white hover:border-white/10 transition-all">
                <i class="ri-customer-service-2-line text-sm"></i> Contact Support
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
