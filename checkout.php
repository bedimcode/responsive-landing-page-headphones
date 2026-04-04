<?php
/**
 * Checkout Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Guests must register/login before checkout — cart persists via session
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = APP_URL . '/checkout.php';
    setFlash('success', 'Please create an account or sign in to complete your purchase. Your cart items are saved!');
    redirect(APP_URL . '/auth/register.php');
}

$pageTitle = 'Checkout — Beats Audio';
$pageDescription = 'Complete your Beats Audio purchase securely.';
$pageType = 'page';
$noIndex = true;

require_once __DIR__ . '/includes/header.php';

// Get cart for server-side display
try {
    $db = getDB();
    $cart = getOrCreateCart($db);
    $stmt = $db->prepare('
        SELECT ci.id, ci.quantity, p.name, p.color, p.price, p.sale_price, p.image, p.stock
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.cart_id = ?
        ORDER BY ci.created_at
    ');
    $stmt->execute([$cart['id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cartItems = [];
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $price = $item['sale_price'] ?: $item['price'];
    $subtotal += $price * $item['quantity'];
}
$shipping = $subtotal >= 100 ? 0 : 9.99;
$tax = round($subtotal * 0.08, 2);
$total = $subtotal + $shipping + $tax;
?>

    <main class="pt-32 pb-24 min-h-[70vh]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="mb-12">
                <a href="<?= e($basePath) ?>/" class="inline-flex items-center gap-2 text-gray-500 hover:text-neon-blue text-sm mb-6 transition-colors"><i class="ri-arrow-left-line"></i> Continue Shopping</a>
                <h1 class="font-display text-3xl lg:text-4xl font-bold text-white">Checkout</h1>
            </div>

            <?php if (empty($cartItems)): ?>
            <!-- Empty Cart State -->
            <div class="text-center py-20">
                <i class="ri-shopping-bag-line text-6xl text-gray-700 mb-6 block"></i>
                <h2 class="text-xl font-semibold text-white mb-3">Your cart is empty</h2>
                <p class="text-gray-500 mb-8">Add some products before checking out.</p>
                <a href="<?= e($basePath) ?>/#products" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-2xl font-bold text-dark-950 hover:scale-105 transition-transform">
                    <i class="ri-shopping-bag-line"></i> Browse Products
                </a>
            </div>

            <?php else: ?>
            <div class="grid lg:grid-cols-3 gap-12">

                <!-- Shipping Form -->
                <div class="lg:col-span-2">
                    <form id="checkout-form" class="space-y-8">
                        <?= csrfField() ?>

                        <!-- Contact -->
                        <div>
                            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2"><i class="ri-user-line text-neon-blue"></i> Contact Information</h2>
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm text-gray-400 mb-1.5">First Name</label>
                                    <input type="text" id="first_name" name="first_name" required
                                        class="w-full px-4 py-3 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                                        placeholder="John" value="<?= e(isLoggedIn() ? explode(' ', currentUserName())[0] : '') ?>">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm text-gray-400 mb-1.5">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" required
                                        class="w-full px-4 py-3 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                                        placeholder="Doe">
                                </div>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="checkout-email" class="block text-sm text-gray-400 mb-1.5">Email</label>
                                    <input type="email" id="checkout-email" name="email" required
                                        class="w-full px-4 py-3 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                                        placeholder="you@example.com">
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm text-gray-400 mb-1.5">Phone (optional)</label>
                                    <input type="tel" id="phone" name="phone"
                                        class="w-full px-4 py-3 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                                        placeholder="+1 (555) 123-4567">
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div>
                            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2"><i class="ri-truck-line text-neon-blue"></i> Shipping Address</h2>
                            <div class="space-y-4">
                                <div>
                                    <label for="address" class="block text-sm text-gray-400 mb-1.5">Street Address</label>
                                    <input type="text" id="address" name="address" required
                                        class="w-full px-4 py-3 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                                        placeholder="123 Main Street, Apt 4B">
                                </div>
                                <div class="grid sm:grid-cols-3 gap-4">
                                    <div>
                                        <label for="city" class="block text-sm text-gray-400 mb-1.5">City</label>
                                        <input type="text" id="city" name="city" required
                                            class="w-full px-4 py-3 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                                            placeholder="Los Angeles">
                                    </div>
                                    <div>
                                        <label for="state" class="block text-sm text-gray-400 mb-1.5">State / Province</label>
                                        <input type="text" id="state" name="state"
                                            class="w-full px-4 py-3 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                                            placeholder="CA">
                                    </div>
                                    <div>
                                        <label for="zip" class="block text-sm text-gray-400 mb-1.5">ZIP / Postal Code</label>
                                        <input type="text" id="zip" name="zip" required
                                            class="w-full px-4 py-3 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                                            placeholder="90001">
                                    </div>
                                </div>
                                <div>
                                    <label for="country" class="block text-sm text-gray-400 mb-1.5">Country</label>
                                    <select id="country" name="country" required
                                        class="w-full px-4 py-3 bg-dark-900/60 border border-white/10 rounded-xl text-white focus:border-neon-blue/50 focus:outline-none transition-colors">
                                        <option value="" class="bg-dark-900">Select country</option>
                                        <option value="US" selected class="bg-dark-900">United States</option>
                                        <option value="CA" class="bg-dark-900">Canada</option>
                                        <option value="GB" class="bg-dark-900">United Kingdom</option>
                                        <option value="AU" class="bg-dark-900">Australia</option>
                                        <option value="DE" class="bg-dark-900">Germany</option>
                                        <option value="FR" class="bg-dark-900">France</option>
                                        <option value="JP" class="bg-dark-900">Japan</option>
                                        <option value="KE" class="bg-dark-900">Kenya</option>
                                        <option value="NG" class="bg-dark-900">Nigeria</option>
                                        <option value="ZA" class="bg-dark-900">South Africa</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Result Message -->
                        <div id="checkout-result" class="hidden p-4 rounded-2xl text-sm"></div>

                        <!-- Payment Method -->
                        <div>
                            <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2"><i class="ri-bank-card-line text-neon-blue"></i> Payment Method</h2>
                            <div class="grid sm:grid-cols-2 gap-3" id="payment-methods">
                                <!-- Cash on Delivery -->
                                <label class="payment-option relative flex items-center gap-3 p-4 bg-dark-900/60 border-2 border-white/[.06] rounded-xl cursor-pointer hover:border-white/10 transition-all">
                                    <input type="radio" name="payment_method" value="cod" class="sr-only peer" required>
                                    <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center flex-shrink-0">
                                        <i class="ri-hand-coin-line text-green-400 text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-white">Cash on Delivery</div>
                                        <div class="text-[11px] text-gray-500">Pay when you receive your order</div>
                                    </div>
                                    <div class="w-5 h-5 rounded-full border-2 border-gray-600 flex items-center justify-center peer-checked:border-neon-blue peer-checked:bg-neon-blue/20 transition-all">
                                        <div class="w-2 h-2 rounded-full bg-neon-blue scale-0 peer-checked:scale-100 transition-transform"></div>
                                    </div>
                                </label>

                                <!-- M-Pesa -->
                                <label class="payment-option relative flex items-center gap-3 p-4 bg-dark-900/60 border-2 border-white/[.06] rounded-xl cursor-pointer hover:border-white/10 transition-all">
                                    <input type="radio" name="payment_method" value="mpesa" class="sr-only peer">
                                    <div class="w-10 h-10 rounded-xl bg-green-600/10 flex items-center justify-center flex-shrink-0">
                                        <i class="ri-smartphone-line text-green-500 text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-white">M-Pesa</div>
                                        <div class="text-[11px] text-gray-500">Send Money to <span class="text-green-400 font-semibold">0715674828</span></div>
                                    </div>
                                    <div class="w-5 h-5 rounded-full border-2 border-gray-600 flex items-center justify-center peer-checked:border-neon-blue peer-checked:bg-neon-blue/20 transition-all">
                                        <div class="w-2 h-2 rounded-full bg-neon-blue scale-0 peer-checked:scale-100 transition-transform"></div>
                                    </div>
                                </label>

                                <!-- Bank Transfer -->
                                <label class="payment-option relative flex items-center gap-3 p-4 bg-dark-900/60 border-2 border-white/[.06] rounded-xl cursor-pointer hover:border-white/10 transition-all">
                                    <input type="radio" name="payment_method" value="bank" class="sr-only peer">
                                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                                        <i class="ri-bank-line text-blue-400 text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-white">Bank Transfer</div>
                                        <div class="text-[11px] text-gray-500">Direct bank payment</div>
                                    </div>
                                    <div class="w-5 h-5 rounded-full border-2 border-gray-600 flex items-center justify-center peer-checked:border-neon-blue peer-checked:bg-neon-blue/20 transition-all">
                                        <div class="w-2 h-2 rounded-full bg-neon-blue scale-0 peer-checked:scale-100 transition-transform"></div>
                                    </div>
                                </label>

                                <!-- PayPal -->
                                <label class="payment-option relative flex items-center gap-3 p-4 bg-dark-900/60 border-2 border-white/[.06] rounded-xl cursor-pointer hover:border-white/10 transition-all">
                                    <input type="radio" name="payment_method" value="paypal" class="sr-only peer">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center flex-shrink-0">
                                        <i class="ri-paypal-line text-indigo-400 text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-white">PayPal</div>
                                        <div class="text-[11px] text-gray-500">Pay with your PayPal account</div>
                                    </div>
                                    <div class="w-5 h-5 rounded-full border-2 border-gray-600 flex items-center justify-center peer-checked:border-neon-blue peer-checked:bg-neon-blue/20 transition-all">
                                        <div class="w-2 h-2 rounded-full bg-neon-blue scale-0 peer-checked:scale-100 transition-transform"></div>
                                    </div>
                                </label>
                            </div>

                            <!-- M-Pesa instructions (shown when M-Pesa selected) -->
                            <div id="mpesa-instructions" class="hidden mt-3 p-4 bg-green-500/5 border border-green-500/10 rounded-xl text-sm">
                                <div class="flex items-center gap-2 text-green-400 font-semibold mb-2">
                                    <i class="ri-information-line"></i> M-Pesa Payment Instructions
                                </div>
                                <ol class="text-gray-400 space-y-1 list-decimal list-inside text-xs">
                                    <li>Go to M-Pesa on your phone</li>
                                    <li>Select <strong class="text-white">Send Money</strong></li>
                                    <li>Enter number: <strong class="text-green-400">0715674828</strong></li>
                                    <li>Enter amount: <strong class="text-white"><?= formatPrice($total) ?></strong></li>
                                    <li>Complete the transaction and place your order</li>
                                </ol>
                                <p class="text-[11px] text-gray-500 mt-2">Your order will be confirmed once payment is verified.</p>
                            </div>

                            <!-- Bank instructions (shown when Bank selected) -->
                            <div id="bank-instructions" class="hidden mt-3 p-4 bg-blue-500/5 border border-blue-500/10 rounded-xl text-sm">
                                <div class="flex items-center gap-2 text-blue-400 font-semibold mb-2">
                                    <i class="ri-information-line"></i> Bank Transfer Details
                                </div>
                                <div class="text-gray-400 text-xs space-y-1">
                                    <p>Transfer <strong class="text-white"><?= formatPrice($total) ?></strong> to the following account:</p>
                                    <p>Bank: <strong class="text-white">Beats Audio Ltd</strong></p>
                                    <p>Use your order number as the payment reference.</p>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-2">Your order will be confirmed once payment is verified.</p>
                            </div>
                        </div>

                        <button type="submit" id="checkout-submit" class="w-full px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl font-bold text-dark-950 text-lg hover:scale-[1.02] transition-transform flex items-center justify-center gap-3">
                            <i class="ri-lock-line"></i> Place Order &mdash; <?= formatPrice($total) ?>
                        </button>

                        <p class="text-center text-gray-600 text-xs">
                            <i class="ri-shield-check-line"></i> Your payment is secure. By placing this order you agree to our
                            <a href="<?= e($basePath) ?>/legal/terms-of-service.php" class="text-gray-500 hover:text-neon-blue">Terms</a>.
                        </p>
                    </form>
                </div>

                <!-- Order Summary -->
                <div>
                    <div class="sticky top-28 p-6 rounded-2xl border border-white/5 bg-dark-900/40">
                        <h2 class="text-lg font-semibold text-white mb-6">Order Summary</h2>

                        <div class="space-y-4 mb-6 max-h-[300px] overflow-y-auto">
                            <?php foreach ($cartItems as $item): ?>
                            <?php $effectivePrice = $item['sale_price'] ?: $item['price']; ?>
                            <div class="flex items-center gap-3">
                                <img src="<?= e($basePath . '/' . $item['image']) ?>" alt="<?= e($item['color']) ?>" class="w-14 h-14 object-contain bg-dark-800 rounded-xl p-1" loading="lazy">
                                <div class="flex-1 min-w-0">
                                    <p class="text-white text-sm font-medium truncate"><?= e($item['name']) ?></p>
                                    <p class="text-gray-500 text-xs"><?= e($item['color']) ?> &times; <?= (int) $item['quantity'] ?></p>
                                </div>
                                <span class="text-white text-sm font-medium"><?= formatPrice($effectivePrice * $item['quantity']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="space-y-3 pt-4 border-t border-white/5 text-sm">
                            <div class="flex justify-between text-gray-400">
                                <span>Subtotal</span>
                                <span><?= formatPrice($subtotal) ?></span>
                            </div>
                            <div class="flex justify-between text-gray-400">
                                <span>Shipping</span>
                                <span><?= $shipping === 0 ? '<span class="text-green-400">Free</span>' : formatPrice($shipping) ?></span>
                            </div>
                            <div class="flex justify-between text-gray-400">
                                <span>Tax (8%)</span>
                                <span><?= formatPrice($tax) ?></span>
                            </div>
                            <div class="flex justify-between text-white font-bold text-lg pt-3 border-t border-white/5">
                                <span>Total</span>
                                <span class="bg-gradient-to-r from-neon-blue to-neon-purple bg-clip-text text-transparent"><?= formatPrice($total) ?></span>
                            </div>
                        </div>

                        <?php if ($subtotal < 100): ?>
                        <p class="text-xs text-orange-400 mt-4 flex items-center gap-1">
                            <i class="ri-truck-line"></i> Add <?= formatPrice(100 - $subtotal) ?> more for free shipping!
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php if (!empty($cartItems)): ?>
    <script>
    // Payment method selection - toggle instructions & highlight
    (function() {
        const radios = document.querySelectorAll('input[name="payment_method"]');
        const mpesaBox = document.getElementById('mpesa-instructions');
        const bankBox = document.getElementById('bank-instructions');
        const labels = document.querySelectorAll('.payment-option');

        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Hide all instruction boxes
                mpesaBox?.classList.add('hidden');
                bankBox?.classList.add('hidden');

                // Reset all label borders
                labels.forEach(l => {
                    l.classList.remove('border-neon-blue/50', 'bg-neon-blue/[.03]');
                    l.classList.add('border-white/[.06]');
                });

                // Highlight selected
                const parent = this.closest('.payment-option');
                if (parent) {
                    parent.classList.remove('border-white/[.06]');
                    parent.classList.add('border-neon-blue/50', 'bg-neon-blue/[.03]');
                }

                // Show relevant instructions
                if (this.value === 'mpesa') mpesaBox?.classList.remove('hidden');
                if (this.value === 'bank') bankBox?.classList.remove('hidden');

                // Update radio indicator dots
                document.querySelectorAll('.payment-option .w-5 .w-2').forEach(dot => dot.style.transform = 'scale(0)');
                const dot = parent?.querySelector('.w-5 .w-2');
                if (dot) dot.style.transform = 'scale(1)';
            });
        });
    })();

    document.getElementById('checkout-form')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('checkout-submit');
        const result = document.getElementById('checkout-result');
        const form = this;

        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Processing...';

        try {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const res = await fetch('<?= e($basePath) ?>/api/checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
                body: JSON.stringify(data)
            });
            const json = await res.json();

            result.classList.remove('hidden', 'bg-red-500/10', 'border-red-500/20', 'text-red-400', 'bg-green-500/10', 'border-green-500/20', 'text-green-400');

            if (json.success) {
                result.classList.add('bg-green-500/10', 'border', 'border-green-500/20', 'text-green-400');
                result.innerHTML = '<div class="text-center"><i class="ri-check-double-line text-3xl block mb-2"></i><strong>' + json.message + '</strong><br>Order #' + json.order_number + '<br>Total: ' + json.total + '</div>';
                form.style.display = 'none';

                // Clear local Cart
                if (typeof Cart !== 'undefined') Cart.clear();
                // Update cart badge
                const badge = document.querySelector('.cart-count');
                if (badge) { badge.textContent = '0'; badge.classList.add('hidden'); }
            } else {
                result.classList.add('bg-red-500/10', 'border', 'border-red-500/20', 'text-red-400');
                result.innerHTML = '<i class="ri-error-warning-line"></i> ' + json.message;
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-lock-line"></i> Place Order';
            }
        } catch (err) {
            result.classList.remove('hidden');
            result.classList.add('bg-red-500/10', 'border', 'border-red-500/20', 'text-red-400');
            result.textContent = 'Network error. Please try again.';
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-lock-line"></i> Place Order';
        }
    });
    </script>
    <?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
