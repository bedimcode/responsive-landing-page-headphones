<?php
/**
 * Admin — Order Detail & Status Update
 */
$adminPageTitle = 'Order Detail';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$orderId = (int)($_GET['id'] ?? 0);

if (!$orderId) {
    setFlash('error', 'Order not found.');
    header('Location: ' . $basePath . '/admin/orders.php'); exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $current = $db->prepare('SELECT status, payment_status, tracking_number FROM orders WHERE id = ?');
    $current->execute([$orderId]);
    $before = $current->fetch();

    $newStatus   = $_POST['status']         ?? '';
    $newPayment  = $_POST['payment_status'] ?? '';
    $tracking    = cleanText($_POST['tracking_number'] ?? '', 100);
    $note        = cleanText($_POST['note'] ?? '', 500);
    $notify      = isset($_POST['notify_customer']);

    $validStatuses  = ['pending','processing','shipped','delivered','cancelled'];
    $validPayments  = ['pending','paid','failed','refunded'];

    if ($before && in_array($newStatus, $validStatuses, true) && in_array($newPayment, $validPayments, true)) {
        $db->beginTransaction();
        $db->prepare('UPDATE orders SET status = ?, payment_status = ?, tracking_number = ? WHERE id = ?')
           ->execute([$newStatus, $newPayment, $tracking ?: null, $orderId]);

        // Cancelling puts the items back in stock (once)
        if ($newStatus === 'cancelled' && $before['status'] !== 'cancelled') {
            $db->prepare('UPDATE products p JOIN order_items oi ON oi.product_id = p.id SET p.stock = p.stock + oi.quantity WHERE oi.order_id = ?')
               ->execute([$orderId]);
        } elseif ($before['status'] === 'cancelled' && $newStatus !== 'cancelled') {
            $db->prepare('UPDATE products p JOIN order_items oi ON oi.product_id = p.id SET p.stock = GREATEST(0, CAST(p.stock AS SIGNED) - oi.quantity) WHERE oi.order_id = ?')
               ->execute([$orderId]);
        }

        $changes = [];
        if ($newPayment !== $before['payment_status']) $changes[] = 'Payment ' . $newPayment;
        if ($tracking !== (string) $before['tracking_number'] && $tracking !== '') $changes[] = 'Tracking ' . $tracking;
        if ($note !== '') $changes[] = $note;
        if ($newStatus !== $before['status'] || $changes) {
            recordOrderStatus($db, $orderId, $before['status'], $newStatus, currentUserId(), $changes ? implode(' · ', $changes) : null);
        }
        $db->commit();

        if ($notify && $newStatus !== $before['status']) {
            sendOrderStatusUpdate($db, $orderId);
        }
        setFlash('success', 'Order updated' . ($notify && $newStatus !== $before['status'] ? ' and customer emailed.' : '.'));
    }

    header('Location: ' . $basePath . '/admin/order-view.php?id=' . $orderId); exit;
}

// Fetch order
$stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    header('Location: ' . $basePath . '/admin/orders.php'); exit;
}

$adminPageTitle = 'Order #' . e($order['order_number']);

// Fetch items
$itemsStmt = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

$statusColors = [
    'pending'    => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
    'processing' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    'shipped'    => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
    'delivered'  => 'bg-green-500/10 text-green-400 border-green-500/20',
    'cancelled'  => 'bg-red-500/10 text-red-400 border-red-500/20',
];
?>

<!-- Back link -->
<div class="mb-6">
    <a href="<?= e($basePath) ?>/admin/orders.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-neon-blue text-sm transition-colors">
        <i class="ri-arrow-left-line"></i> Back to Orders
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    <!-- Left: Items + Update Form -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Order Items -->
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/5">
                <h2 class="font-semibold text-white">Order Items</h2>
            </div>
            <div class="divide-y divide-white/5">
                <?php foreach ($items as $item): ?>
                <div class="flex items-center gap-4 px-6 py-4">
                    <?php if ($item['product_image']): ?>
                    <img src="<?= e($basePath) ?>/<?= e($item['product_image']) ?>" alt="<?= e($item['product_name']) ?>" class="w-12 h-12 object-contain rounded-xl bg-dark-800 flex-shrink-0">
                    <?php else: ?>
                    <div class="w-12 h-12 rounded-xl bg-dark-800 flex items-center justify-center flex-shrink-0"><i class="ri-headphone-line text-gray-600 text-xl"></i></div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="text-white font-medium text-sm"><?= e($item['product_name']) ?></div>
                        <div class="text-gray-500 text-xs">Qty: <?= (int)$item['quantity'] ?></div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-white font-semibold text-sm"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                        <div class="text-gray-500 text-xs"><?= formatPrice($item['price']) ?> each</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Totals -->
            <div class="px-6 py-4 border-t border-white/5 space-y-2">
                <div class="flex justify-between text-sm text-gray-400"><span>Subtotal</span><span><?= formatPrice($order['subtotal']) ?></span></div>
                <?php if ($order['discount'] > 0): ?>
                <div class="flex justify-between text-sm text-green-400"><span>Discount<?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></span><span><?= formatPrice(-$order['discount']) ?></span></div>
                <?php endif; ?>
                <div class="flex justify-between text-sm text-gray-400"><span>Shipping</span><span><?= $order['shipping_cost'] > 0 ? formatPrice($order['shipping_cost']) : 'Free' ?></span></div>
                <div class="flex justify-between text-sm text-gray-400"><span><?= e(setting('tax_label') ?: 'Tax') ?></span><span><?= formatPrice($order['tax']) ?></span></div>
                <div class="flex justify-between text-base text-white font-bold pt-2 border-t border-white/5"><span>Total</span><span><?= formatPrice($order['total']) ?></span></div>
            </div>
        </div>

        <!-- Update Status -->
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl p-6">
            <h2 class="font-semibold text-white mb-4">Update Order</h2>
            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label for="status" class="block text-xs text-gray-500 mb-1.5">Order Status</label>
                        <select id="status" name="status" class="w-full px-4 py-3 bg-dark-800 border border-white/10 rounded-xl text-sm text-white focus:border-neon-blue/50 focus:outline-none transition-colors">
                            <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?> class="bg-dark-800"><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="payment_status" class="block text-xs text-gray-500 mb-1.5">Payment Status</label>
                        <select id="payment_status" name="payment_status" class="w-full px-4 py-3 bg-dark-800 border border-white/10 rounded-xl text-sm text-white focus:border-neon-blue/50 focus:outline-none transition-colors">
                            <?php foreach (['pending','paid','failed','refunded'] as $s): ?>
                            <option value="<?= $s ?>" <?= $order['payment_status'] === $s ? 'selected' : '' ?> class="bg-dark-800"><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="tracking_number" class="block text-xs text-gray-500 mb-1.5">Tracking Number</label>
                        <input id="tracking_number" name="tracking_number" maxlength="100" value="<?= e($order['tracking_number'] ?? '') ?>" placeholder="Optional"
                               class="w-full px-4 py-3 bg-dark-800 border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors">
                    </div>
                </div>
                <div>
                    <label for="note" class="block text-xs text-gray-500 mb-1.5">Note for the timeline <span class="text-gray-600">(visible to the customer)</span></label>
                    <input id="note" name="note" maxlength="500" placeholder="e.g. Dispatched with G4S courier"
                           class="w-full px-4 py-3 bg-dark-800 border border-white/10 rounded-xl text-sm text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors">
                </div>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
                        <input type="checkbox" name="notify_customer" value="1" checked class="w-4 h-4 rounded border-white/20 bg-dark-800 text-neon-blue focus:ring-neon-blue/50">
                        Email the customer when the status changes
                    </label>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl text-sm font-bold text-dark-950 hover:opacity-90 transition-opacity">
                        Save Changes
                    </button>
                </div>
                <p class="text-[11px] text-gray-600">Cancelling an order puts its items back in stock.</p>
            </form>
        </div>

        <?php $history = getOrderStatusHistory($db, $orderId); if ($history): ?>
        <!-- Timeline -->
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl p-6">
            <h2 class="font-semibold text-white mb-4">Timeline</h2>
            <ol class="space-y-3 text-sm">
                <?php foreach (array_reverse($history) as $h): ?>
                <li class="flex gap-3">
                    <span class="mt-1.5 w-2 h-2 rounded-full bg-neon-blue/60 flex-shrink-0"></span>
                    <div>
                        <span class="text-white"><?= e(ucfirst($h['new_status'])) ?></span>
                        <?php if ($h['note']): ?><span class="text-gray-400"> — <?= e($h['note']) ?></span><?php endif; ?>
                        <div class="text-[11px] text-gray-600"><?= date('M j, Y H:i', strtotime($h['created_at'])) ?><?= $h['changed_by_name'] ? ' · ' . e($h['changed_by_name']) : '' ?></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>

    </div><!-- end left col -->

    <!-- Right: Order Info + Customer -->
    <div class="space-y-6">

        <!-- Order Summary -->
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl p-6">
            <h2 class="font-semibold text-white mb-4">Order Info</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Order #</dt>
                    <dd class="text-white font-mono"><?= e($order['order_number']) ?></dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Date</dt>
                    <dd class="text-white"><?= date('M j, Y H:i', strtotime($order['created_at'])) ?></dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Status</dt>
                    <dd><span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-semibold border <?= $statusColors[$order['status']] ?? 'text-gray-400 border-white/10' ?>"><?= ucfirst(e($order['status'])) ?></span></dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Payment</dt>
                    <dd class="text-white"><?= e(paymentMethodLabel($order['payment_method'])) ?> · <span class="<?= paymentStatusBadge($order['payment_status']) ?> px-2 py-0.5 rounded-full text-[10px]"><?= e(ucfirst($order['payment_status'])) ?></span></dd>
                </div>
                <?php if ($order['payment_reference']): ?>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Customer&rsquo;s reference</dt>
                    <dd class="text-neon-blue font-mono"><?= e($order['payment_reference']) ?></dd>
                </div>
                <?php elseif ($order['payment_status'] === 'pending' && in_array($order['payment_method'], ['mpesa', 'bank'], true)): ?>
                <div class="text-[11px] text-yellow-400/80">Waiting for the customer to pay and send a transaction code.</div>
                <?php endif; ?>
                <?php if ($order['notes']): ?>
                <div>
                    <dt class="text-gray-500 mb-1">Notes</dt>
                    <dd class="text-gray-300 text-xs bg-dark-800 rounded-lg p-3"><?= e($order['notes']) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Shipping Info -->
        <div class="bg-dark-900/60 border border-white/5 rounded-2xl p-6">
            <h2 class="font-semibold text-white mb-4">Shipping Address</h2>
            <address class="not-italic text-sm text-gray-300 space-y-1">
                <div class="font-medium text-white"><?= e($order['shipping_name']) ?></div>
                <div><?= e($order['shipping_email']) ?></div>
                <?php if ($order['shipping_phone']): ?>
                <div><?= e($order['shipping_phone']) ?></div>
                <?php endif; ?>
                <div class="pt-1"><?= e($order['shipping_address']) ?></div>
                <div><?= e($order['shipping_city']) ?><?= $order['shipping_state'] ? ', ' . e($order['shipping_state']) : '' ?> <?= e($order['shipping_zip']) ?></div>
                <div><?= e($order['shipping_country']) ?></div>
            </address>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
