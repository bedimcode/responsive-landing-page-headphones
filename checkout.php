<?php
/**
 * Checkout Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Guests must register/login before checkout — cart persists via session
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = APP_URL . '/checkout.php';
    setFlash('info', 'Please create an account or sign in to complete your purchase. Your cart items are saved!');
    redirect(APP_URL . '/auth/register.php');
}

$pageTitle = 'Checkout — ' . storeName();
$pageDescription = 'Complete your ' . storeName() . ' purchase.';
$pageType = 'page';
$noIndex = true;

require_once __DIR__ . '/includes/header.php';

// Cart, customer profile and default address for pre-filling the form
try {
    $db = getDB();
    $cart = getOrCreateCart($db);
    $stmt = $db->prepare('
        SELECT ci.id, ci.quantity, p.name, p.color, p.price, p.sale_price, p.image, p.stock, p.is_active
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.cart_id = ?
        ORDER BY ci.created_at
    ');
    $stmt->execute([$cart['id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare('SELECT name, email, phone FROM users WHERE id = ?');
    $stmt->execute([currentUserId()]);
    $customer = $stmt->fetch() ?: [];
    $address = getUserAddresses($db, currentUserId())[0] ?? [];
} catch (Exception $e) {
    $cartItems = [];
    $customer = $address = [];
}

$subtotal = 0;
$problems = [];
foreach ($cartItems as $item) {
    $subtotal += effectivePrice($item) * $item['quantity'];
    if (!$item['is_active']) $problems[] = $item['name'] . ' is no longer available.';
    elseif ($item['quantity'] > $item['stock']) $problems[] = $item['name'] . ($item['stock'] > 0 ? ' only has ' . $item['stock'] . ' left.' : ' is sold out.');
}
$totals  = calculateTotals($subtotal);
$methods = paymentMethods();

[$firstName, $lastName] = array_pad(explode(' ', $customer['name'] ?? '', 2), 2, '');
$country   = $address['country'] ?? setting('default_country');
$countries = array_unique(array_merge([setting('default_country')], [
    'Kenya', 'Uganda', 'Tanzania', 'Rwanda', 'Ethiopia', 'Nigeria', 'Ghana', 'South Africa',
    'United States', 'Canada', 'United Kingdom', 'Ireland', 'Germany', 'France', 'Netherlands',
    'United Arab Emirates', 'India', 'Australia', 'Japan',
]));
$field = 'w-full px-4 py-3 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors';
?>

    <main class="pt-32 pb-24 min-h-[70vh]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="mb-12">
                <a href="<?= e($basePath) ?>/shop.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-neon-blue text-sm mb-6 transition-colors"><i class="ri-arrow-left-line"></i> Continue Shopping</a>
                <h1 class="font-display text-3xl lg:text-4xl font-bold text-white">Checkout</h1>
            </div>

            <?php if (empty($cartItems)): ?>
            <!-- Empty Cart State -->
            <div class="text-center py-20">
                <i class="ri-shopping-bag-line text-6xl text-gray-700 mb-6 block"></i>
                <h2 class="text-xl font-semibold text-white mb-3">Your cart is empty</h2>
                <p class="text-gray-500 mb-8">Add some products before checking out.</p>
                <a href="<?= e($basePath) ?>/shop.php" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-2xl font-bold text-dark-950 hover:scale-105 transition-transform">
                    <i class="ri-shopping-bag-line"></i> Browse Products
                </a>
            </div>

            <?php elseif (!$methods): ?>
            <!-- Store not configured for payments -->
            <div class="max-w-lg mx-auto text-center py-16 px-6 rounded-2xl border border-yellow-500/20 bg-yellow-500/5">
                <i class="ri-tools-line text-5xl text-yellow-400 mb-4 block"></i>
                <h2 class="text-xl font-semibold text-white mb-2">Checkout is temporarily unavailable</h2>
                <p class="text-gray-400 text-sm">We&rsquo;re updating our payment options. Please <a href="<?= e($basePath) ?>/contact.php" class="text-neon-blue hover:underline">contact us</a> to order.</p>
            </div>

            <?php else: ?>
            <div class="grid lg:grid-cols-3 gap-12">

                <!-- Shipping Form -->
                <div class="lg:col-span-2">
                    <?php if ($problems): ?>
                    <div class="mb-8 p-4 rounded-2xl bg-yellow-500/10 border border-yellow-500/20 text-yellow-300 text-sm">
                        <p class="font-semibold mb-1"><i class="ri-error-warning-line"></i> Please update your cart before ordering:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <?php foreach ($problems as $problem): ?><li><?= e($problem) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form id="checkout-form" class="space-y-8" novalidate>
                        <?= csrfField() ?>

                        <!-- Contact -->
                        <div>
                            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2"><i class="ri-user-line text-neon-blue"></i> Contact Information</h2>
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm text-gray-400 mb-1.5">First Name</label>
                                    <input type="text" id="first_name" name="first_name" required maxlength="50" autocomplete="given-name" class="<?= $field ?>" value="<?= e($firstName) ?>">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm text-gray-400 mb-1.5">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" required maxlength="49" autocomplete="family-name" class="<?= $field ?>" value="<?= e($lastName) ?>">
                                </div>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="checkout-email" class="block text-sm text-gray-400 mb-1.5">Email</label>
                                    <input type="email" id="checkout-email" name="email" required maxlength="255" autocomplete="email" class="<?= $field ?>" value="<?= e($customer['email'] ?? '') ?>">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm text-gray-400 mb-1.5">Phone <span class="text-gray-600">(for delivery)</span></label>
                                    <input type="tel" id="phone" name="phone" required maxlength="20" autocomplete="tel" class="<?= $field ?>" placeholder="+254 712 345 678" value="<?= e($customer['phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div>
                            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2"><i class="ri-truck-line text-neon-blue"></i> Shipping Address</h2>
                            <div class="space-y-4">
                                <div>
                                    <label for="address" class="block text-sm text-gray-400 mb-1.5">Street Address</label>
                                    <input type="text" id="address" name="address" required maxlength="255" autocomplete="street-address" class="<?= $field ?>" placeholder="Street, building, apartment" value="<?= e($address['address'] ?? '') ?>">
                                </div>
                                <div class="grid sm:grid-cols-3 gap-4">
                                    <div>
                                        <label for="city" class="block text-sm text-gray-400 mb-1.5">City / Town</label>
                                        <input type="text" id="city" name="city" required maxlength="100" autocomplete="address-level2" class="<?= $field ?>" value="<?= e($address['city'] ?? '') ?>">
                                    </div>
                                    <div>
                                        <label for="state" class="block text-sm text-gray-400 mb-1.5">County / State</label>
                                        <input type="text" id="state" name="state" maxlength="100" autocomplete="address-level1" class="<?= $field ?>" value="<?= e($address['state'] ?? '') ?>">
                                    </div>
                                    <div>
                                        <label for="zip" class="block text-sm text-gray-400 mb-1.5">Postal Code</label>
                                        <input type="text" id="zip" name="zip" maxlength="20" autocomplete="postal-code" class="<?= $field ?>" value="<?= e($address['zip'] ?? '') ?>">
                                    </div>
                                </div>
                                <div>
                                    <label for="country" class="block text-sm text-gray-400 mb-1.5">Country</label>
                                    <select id="country" name="country" required autocomplete="country-name" class="<?= $field ?>">
                                        <?php foreach ($countries as $c): ?>
                                        <option value="<?= e($c) ?>" class="bg-dark-900" <?= $c === $country ? 'selected' : '' ?>><?= e($c) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2"><i class="ri-wallet-3-line text-neon-blue"></i> Payment Method</h2>
                            <div class="grid sm:grid-cols-2 gap-3" id="payment-methods">
                                <?php $first = true; foreach ($methods as $code => $m): ?>
                                <label class="payment-option relative flex items-center gap-3 p-4 bg-dark-900/60 border-2 <?= $first ? 'border-neon-blue/50' : 'border-white/[.06]' ?> rounded-xl cursor-pointer hover:border-white/10 transition-all">
                                    <input type="radio" name="payment_method" value="<?= e($code) ?>" class="sr-only" <?= $first ? 'checked' : '' ?>>
                                    <div class="w-10 h-10 rounded-xl <?= e($m['color']) ?> flex items-center justify-center flex-shrink-0">
                                        <i class="<?= e($m['icon']) ?> text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-white"><?= e($m['label']) ?></div>
                                        <div class="text-[11px] text-gray-500 truncate"><?= e($m['hint']) ?></div>
                                    </div>
                                    <i class="payment-check ri-checkbox-circle-fill text-neon-blue text-xl <?= $first ? '' : 'invisible' ?>"></i>
                                </label>
                                <?php $first = false; endforeach; ?>
                            </div>

                            <!-- What happens next, per method -->
                            <?php foreach ($methods as $code => $m):
                                $how = paymentInstructions($code, 'your order number', $totals['total']); ?>
                            <div class="payment-info <?= $code === array_key_first($methods) ? '' : 'hidden' ?> mt-3 p-4 bg-white/[.02] border border-white/[.06] rounded-xl text-sm" data-method="<?= e($code) ?>">
                                <?php if ($how): ?>
                                <div class="text-white font-semibold mb-2 flex items-center gap-2"><i class="ri-information-line text-neon-blue"></i> <?= e($how['title']) ?> (after you place the order)</div>
                                <ol class="text-gray-400 space-y-1 list-decimal list-inside text-xs">
                                    <?php foreach ($how['steps'] as $step): ?>
                                    <?php if (str_starts_with($step, 'Amount: ')): // kept in sync with the coupon total by JS ?>
                                    <li>Amount: <span class="js-pay-amount"><?= formatPrice($totals['total']) ?></span></li>
                                    <?php else: ?>
                                    <li><?= e($step) ?></li>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </ol>
                                <p class="text-[11px] text-gray-500 mt-2"><?= e($how['note']) ?></p>
                                <?php else: ?>
                                <p class="text-gray-400 text-xs"><i class="ri-information-line text-neon-blue"></i> Pay the courier in cash or mobile money when your order arrives. Please have the exact amount ready.</p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Result Message -->
                        <div id="checkout-result" class="hidden p-4 rounded-2xl text-sm" role="alert"></div>

                        <button type="submit" id="checkout-submit" <?= $problems ? 'disabled' : '' ?> class="w-full px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl font-bold text-dark-950 text-lg hover:scale-[1.02] transition-transform flex items-center justify-center gap-3 disabled:opacity-40 disabled:hover:scale-100">
                            <i class="ri-check-double-line"></i> Place Order &mdash; <span id="submit-total"><?= formatPrice($totals['total']) ?></span>
                        </button>

                        <p class="text-center text-gray-600 text-xs">
                            By placing this order you agree to our
                            <a href="<?= e($basePath) ?>/legal/terms-of-service.php" class="text-gray-500 hover:text-neon-blue">Terms of Service</a>
                            and <a href="<?= e($basePath) ?>/legal/privacy-policy.php" class="text-gray-500 hover:text-neon-blue">Privacy Policy</a>.
                        </p>
                    </form>
                </div>

                <!-- Order Summary -->
                <div>
                    <div class="sticky top-28 p-6 rounded-2xl border border-white/5 bg-dark-900/40">
                        <h2 class="text-lg font-semibold text-white mb-6">Order Summary</h2>

                        <div class="space-y-4 mb-6 max-h-[300px] overflow-y-auto">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="flex items-center gap-3">
                                <img src="<?= e($basePath . '/' . $item['image']) ?>" alt="<?= e($item['name']) ?>" class="w-14 h-14 object-contain bg-dark-800 rounded-xl p-1" loading="lazy">
                                <div class="flex-1 min-w-0">
                                    <p class="text-white text-sm font-medium truncate"><?= e($item['name']) ?></p>
                                    <p class="text-gray-500 text-xs"><?= e($item['color'] ?? '') ?> &times; <?= (int) $item['quantity'] ?></p>
                                </div>
                                <span class="text-white text-sm font-medium"><?= formatPrice(effectivePrice($item) * $item['quantity']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Coupon -->
                        <div class="mb-5">
                            <label for="coupon-code" class="block text-xs text-gray-500 mb-1.5">Coupon code</label>
                            <div class="flex gap-2">
                                <input type="text" id="coupon-code" maxlength="40" autocomplete="off" class="flex-1 min-w-0 px-3 py-2.5 bg-dark-900/60 border border-white/10 rounded-xl text-sm text-white uppercase placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none" placeholder="e.g. WELCOME10">
                                <button type="button" id="coupon-apply" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-neon-blue/30 text-neon-blue hover:bg-neon-blue/10 transition-colors">Apply</button>
                            </div>
                            <p id="coupon-message" class="text-xs mt-1.5 hidden"></p>
                        </div>

                        <div class="space-y-3 pt-4 border-t border-white/5 text-sm">
                            <div class="flex justify-between text-gray-400">
                                <span>Subtotal</span>
                                <span id="sum-subtotal"><?= formatPrice($totals['subtotal']) ?></span>
                            </div>
                            <div id="row-discount" class="flex justify-between text-green-400 hidden">
                                <span>Discount <span id="sum-coupon" class="font-mono text-xs"></span></span>
                                <span id="sum-discount"></span>
                            </div>
                            <div class="flex justify-between text-gray-400">
                                <span>Shipping</span>
                                <span id="sum-shipping"><?= $totals['shipping'] > 0 ? formatPrice($totals['shipping']) : '<span class="text-green-400">Free</span>' ?></span>
                            </div>
                            <div class="flex justify-between text-gray-400">
                                <span><?= e(taxLabel()) ?></span>
                                <span id="sum-tax"><?= formatPrice($totals['tax']) ?></span>
                            </div>
                            <div class="flex justify-between text-white font-bold text-lg pt-3 border-t border-white/5">
                                <span>Total</span>
                                <span id="sum-total" class="bg-gradient-to-r from-neon-blue to-neon-purple bg-clip-text text-transparent"><?= formatPrice($totals['total']) ?></span>
                            </div>
                        </div>

                        <?php if ($totals['free_remaining'] > 0): ?>
                        <p class="text-xs text-orange-400 mt-4 flex items-center gap-1">
                            <i class="ri-truck-line"></i> Add <?= formatPrice($totals['free_remaining']) ?> more for free shipping!
                        </p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-500 mt-3 flex items-center gap-1"><i class="ri-time-line"></i> Delivery in <?= e(setting('delivery_estimate')) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php if (!empty($cartItems) && $methods): ?>
    <script>
    (function() {
        const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
        const form = document.getElementById('checkout-form');
        const result = document.getElementById('checkout-result');
        let appliedCoupon = '';

        // ---- Payment method selection: highlight + show what happens next ----
        form.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', () => {
                form.querySelectorAll('.payment-option').forEach(label => {
                    const on = label.contains(radio);
                    label.classList.toggle('border-neon-blue/50', on);
                    label.classList.toggle('border-white/[.06]', !on);
                    label.querySelector('.payment-check').classList.toggle('invisible', !on);
                });
                form.querySelectorAll('.payment-info').forEach(box => box.classList.toggle('hidden', box.dataset.method !== radio.value));
            });
        });

        // ---- Coupon ----
        const couponInput = document.getElementById('coupon-code');
        const couponMsg = document.getElementById('coupon-message');
        const sumIds = ['sum-shipping', 'sum-tax', 'sum-total', 'submit-total'];
        const original = Object.fromEntries(sumIds.map(id => [id, document.getElementById(id).innerHTML]));
        function showCouponMessage(text, ok) {
            couponMsg.textContent = text;
            couponMsg.className = 'text-xs mt-1.5 ' + (ok ? 'text-green-400' : 'text-red-400');
        }
        // Back to the no-coupon totals rendered by the server
        function clearCoupon() {
            appliedCoupon = '';
            document.getElementById('row-discount').classList.add('hidden');
            sumIds.forEach(id => document.getElementById(id).innerHTML = original[id]);
            document.querySelectorAll('.js-pay-amount').forEach(el => el.innerHTML = original['sum-total']);
        }
        async function applyCoupon() {
            const code = couponInput.value.trim().toUpperCase();
            if (!code) return;
            try {
                const res = await fetch('<?= e($basePath) ?>/api/coupon.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ code, email: document.getElementById('checkout-email').value })
                });
                const data = await res.json();
                if (!data.success) {
                    clearCoupon();
                    showCouponMessage(data.message, false);
                    return;
                }
                appliedCoupon = data.code;
                showCouponMessage(data.message, true);
                document.getElementById('row-discount').classList.remove('hidden');
                document.getElementById('sum-coupon').textContent = '(' + data.code + ')';
                document.getElementById('sum-discount').textContent = data.totals.discount;
                document.getElementById('sum-shipping').textContent = data.shipping_free ? 'Free' : data.totals.shipping;
                document.getElementById('sum-tax').textContent = data.totals.tax;
                document.getElementById('sum-total').textContent = data.totals.total;
                document.getElementById('submit-total').textContent = data.totals.total;
                document.querySelectorAll('.js-pay-amount').forEach(el => el.textContent = data.totals.total);
            } catch (e) {
                showCouponMessage('Could not check the coupon. Please try again.', false);
            }
        }
        document.getElementById('coupon-apply').addEventListener('click', applyCoupon);
        couponInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); } });

        // ---- Place order ----
        function showResult(message, ok) {
            result.textContent = message;
            result.className = 'p-4 rounded-2xl text-sm border ' + (ok ? 'bg-green-500/10 border-green-500/20 text-green-400' : 'bg-red-500/10 border-red-500/20 text-red-400');
            result.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!form.reportValidity()) return;
            const btn = document.getElementById('checkout-submit');
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Placing your order…';

            try {
                const data = Object.fromEntries(new FormData(form));
                data.coupon_code = appliedCoupon;
                const res = await fetch('<?= e($basePath) ?>/api/checkout.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.success) {
                    showResult(json.message + ' Order #' + json.order_number + ' — taking you to your order…', true);
                    window.location.href = json.redirect;
                    return;
                }
                if (json.field === 'coupon') {
                    clearCoupon();
                    showCouponMessage(json.message, false);
                }
                showResult(json.message || 'We could not place your order. Please try again.', false);
                if (json.redirect && res.status === 401) window.location.href = json.redirect;
            } catch (err) {
                showResult('Network error. Please check your connection and try again.', false);
            }
            btn.disabled = false;
            btn.innerHTML = original;
        });
    })();
    </script>
    <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
