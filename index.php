<?php
/**
 * Headphones1.Duka — Homepage
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Beats 3 — Studio-Grade Wireless Headphones | ' . storeName();
$pageDescription = 'Beats 3 studio-grade wireless headphones with 40h battery, Bluetooth 5.2 and premium sound. ' . shippingSummary() . '.';
$pageType = 'home';
$includeAnimations = true;
$canonical = absoluteUrl('/');

require_once __DIR__ . '/includes/header.php';

// Fetch products and homepage content from the database
try {
    $db = getDB();
    $products = getActiveProducts($db);
    $testimonials = settingOn('show_testimonials')
        ? $db->query('SELECT name, role, quote, rating FROM testimonials WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll()
        : [];
} catch (Exception $e) {
    $products = [];
    $testimonials = [];
}

// Get hero product (first featured or first product)
$heroProduct = null;
foreach ($products as $p) {
    if ($p['featured']) { $heroProduct = $p; break; }
}
if (!$heroProduct && !empty($products)) {
    $heroProduct = $products[0];
}

$lowStockAt = (int) setting('low_stock_threshold');
$promo      = activePromo();
$returnDays = (int) setting('return_days');
$warranty   = warrantyText();
?>

        <!-- ============ HERO SECTION ============ -->
        <section id="home" class="relative min-h-screen flex items-center overflow-hidden pt-20">
            <!-- Ambient bg effects -->
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute top-1/4 left-1/4 w-[600px] h-[600px] bg-neon-blue/5 rounded-full blur-[150px]"></div>
                <div class="absolute bottom-1/4 right-1/4 w-[500px] h-[500px] bg-neon-purple/5 rounded-full blur-[150px]"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-12 items-center relative z-10 w-full py-12">
                <!-- Text -->
                <div class="order-2 lg:order-1 text-center lg:text-left">
                    <div class="hero-text">
                        <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-neon-blue/10 border border-neon-blue/20 rounded-full text-neon-blue text-xs font-semibold uppercase tracking-widest mb-6">
                            <span class="w-2 h-2 bg-neon-blue rounded-full animate-pulse"></span> Now Available
                        </span>
                        <h1 class="font-display text-5xl sm:text-6xl lg:text-7xl xl:text-8xl font-black text-white leading-[0.95] mb-4">
                            <span class="block hero-line">On Ear</span>
                            <span class="block bg-gradient-to-r from-neon-blue via-neon-purple to-neon-pink bg-clip-text text-transparent hero-line">Beats 3</span>
                        </h1>
                        <p class="text-lg sm:text-xl text-gray-400 max-w-lg mx-auto lg:mx-0 mb-8 leading-relaxed hero-desc">
                            Studio-grade sound that disappears into your world. 40 hours of wireless freedom, wrapped in premium comfort.
                        </p>

                        <!-- Color swatches -->
                        <div class="flex items-center gap-4 justify-center lg:justify-start mb-8 hero-colors">
                            <span class="text-sm text-gray-500 uppercase tracking-wide">Color:</span>
                            <div class="flex gap-3" id="hero-colors">
                                <?php foreach ($products as $p): ?>
                                <button type="button" class="color-swatch w-8 h-8 rounded-full border-2 <?= $heroProduct && $p['id'] === $heroProduct['id'] ? 'border-white/30 active' : 'border-transparent' ?> transition-all hover:scale-110"
                                    data-id="<?= (int) $p['id'] ?>"
                                    data-name="<?= e($p['name']) ?>"
                                    data-price="<?= e((string) effectivePrice($p)) ?>"
                                    data-price-label="<?= e(formatPrice(effectivePrice($p))) ?>"
                                    data-img="<?= e($p['image']) ?>"
                                    style="background: <?= e($p['color_hex'] ?: '#1a1a1a') ?>"
                                    title="<?= e($p['color']) ?>" aria-label="<?= e($p['color']) ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Price + CTA -->
                        <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start hero-cta">
                            <?php if ($heroProduct): ?>
                            <button id="hero-add-to-cart" class="group relative px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-2xl font-bold text-dark-950 text-lg flex items-center gap-3 hover:scale-105 transition-all duration-300 overflow-hidden add-to-cart-trigger"
                                data-id="<?= (int) $heroProduct['id'] ?>"
                                data-name="<?= e($heroProduct['name']) ?>"
                                data-price="<?= e((string) effectivePrice($heroProduct)) ?>"
                                data-img="<?= e($heroProduct['image']) ?>">
                                <span class="relative z-10 flex items-center gap-3">
                                    <i class="ri-shopping-bag-line text-xl"></i> Add to Bag
                                </span>
                                <span id="hero-price" class="relative z-10 px-3 py-1 bg-dark-950/20 rounded-xl text-sm"><?= formatPrice(effectivePrice($heroProduct)) ?></span>
                                <div class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 skew-x-12"></div>
                            </button>
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <i class="ri-truck-line text-neon-blue"></i>
                                <span><?= e(shippingSummary()) ?><?php if ($heroProduct['stock'] > 0 && $heroProduct['stock'] <= $lowStockAt): ?> · <strong class="text-orange-400">Only <?= (int) $heroProduct['stock'] ?> left</strong><?php endif; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Hero Image -->
                <div class="order-1 lg:order-2 flex justify-center relative">
                    <div class="relative" id="hero-image-wrapper">
                        <div class="absolute inset-0 bg-gradient-to-br from-neon-blue/20 to-neon-purple/20 rounded-full blur-[100px] scale-75"></div>
                        <img id="hero-product-img" 
                             src="<?= e($heroProduct['image'] ?? 'assets/img/home.png') ?>" 
                             alt="<?= e($heroProduct['name'] ?? 'Beats 3 wireless headphones') ?>"
                             width="375" height="500" fetchpriority="high"
                             class="relative z-10 w-full max-w-[400px] lg:max-w-[500px] drop-shadow-2xl">
                    </div>
                </div>
            </div>

            <!-- Scroll indicator -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-gray-500 z-10 scroll-hint">
                <span class="text-xs uppercase tracking-widest">Scroll</span>
                <div class="w-5 h-8 border-2 border-gray-600 rounded-full flex justify-center">
                    <div class="w-1 h-2 bg-gray-400 rounded-full mt-1 animate-bounce"></div>
                </div>
            </div>
        </section>

        <!-- ============ SPECS / FEATURES ============ -->
        <section id="specs" class="py-24 lg:py-32 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-neon-purple/5 rounded-full blur-[200px] pointer-events-none"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-16">
                    <span class="text-neon-blue text-sm font-semibold uppercase tracking-widest">Technical Excellence</span>
                    <h2 class="font-display text-4xl lg:text-6xl font-bold text-white mt-3">Built for <span class="bg-gradient-to-r from-neon-blue to-neon-purple bg-clip-text text-transparent">Perfection</span></h2>
                </div>

                <div class="grid lg:grid-cols-3 gap-12 items-center">
                    <!-- Left specs -->
                    <div class="space-y-8">
                        <div class="spec-card group p-6 rounded-2xl border border-white/5 bg-dark-900/50 backdrop-blur-sm hover:border-neon-blue/30 transition-all duration-500">
                            <div class="w-12 h-12 bg-neon-blue/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-neon-blue/20 transition-colors">
                                <i class="ri-bluetooth-line text-2xl text-neon-blue"></i>
                            </div>
                            <h3 class="text-white font-semibold text-lg mb-1">Bluetooth 5.2</h3>
                            <p class="text-gray-500 text-sm">Ultra-fast, stable connection up to 30ft. Multipoint pairing for seamless device switching.</p>
                        </div>
                        <div class="spec-card group p-6 rounded-2xl border border-white/5 bg-dark-900/50 backdrop-blur-sm hover:border-neon-blue/30 transition-all duration-500">
                            <div class="w-12 h-12 bg-neon-purple/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-neon-purple/20 transition-colors">
                                <i class="ri-battery-charge-line text-2xl text-neon-purple"></i>
                            </div>
                            <h3 class="text-white font-semibold text-lg mb-1">40h Battery</h3>
                            <p class="text-gray-500 text-sm">Marathon listening sessions. 10 minutes of Fast Fuel charging gives you 3 hours of playback.</p>
                        </div>
                    </div>

                    <!-- Center image -->
                    <div class="flex justify-center" id="specs-image-wrap">
                        <img src="assets/img/specs.png" alt="Beats 3 specifications view" class="w-full max-w-[350px] drop-shadow-2xl" loading="lazy">
                    </div>

                    <!-- Right specs -->
                    <div class="space-y-8">
                        <div class="spec-card group p-6 rounded-2xl border border-white/5 bg-dark-900/50 backdrop-blur-sm hover:border-neon-blue/30 transition-all duration-500">
                            <div class="w-12 h-12 bg-neon-pink/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-neon-pink/20 transition-colors">
                                <i class="ri-plug-line text-2xl text-neon-pink"></i>
                            </div>
                            <h3 class="text-white font-semibold text-lg mb-1">Fast Charge 4.2</h3>
                            <p class="text-gray-500 text-sm">USB-C fast charging with AAC codec support. Full charge in under 90 minutes.</p>
                        </div>
                        <div class="spec-card group p-6 rounded-2xl border border-white/5 bg-dark-900/50 backdrop-blur-sm hover:border-neon-blue/30 transition-all duration-500">
                            <div class="w-12 h-12 bg-neon-cyan/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-neon-cyan/20 transition-colors">
                                <i class="ri-mic-line text-2xl text-neon-cyan"></i>
                            </div>
                            <h3 class="text-white font-semibold text-lg mb-1">Smart Assistant</h3>
                            <p class="text-gray-500 text-sm">Built-in mic with support for Apple Siri, Google Assistant, and Alexa voice control.</p>
                        </div>
                    </div>
                </div>

                <!-- Stats counter -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-20">
                    <div class="text-center p-6 rounded-2xl border border-white/5 bg-dark-900/30">
                        <span class="counter block text-3xl lg:text-4xl font-display font-bold text-white" data-target="40">0</span>
                        <span class="text-gray-500 text-sm">Hours Battery</span>
                    </div>
                    <div class="text-center p-6 rounded-2xl border border-white/5 bg-dark-900/30">
                        <span class="counter block text-3xl lg:text-4xl font-display font-bold text-white" data-target="<?= count($products) ?>">0</span>
                        <span class="text-gray-500 text-sm">Premium Colors</span>
                    </div>
                    <div class="text-center p-6 rounded-2xl border border-white/5 bg-dark-900/30">
                        <span class="counter block text-3xl lg:text-4xl font-display font-bold text-neon-blue" data-target="30">0</span>
                        <span class="text-gray-500 text-sm">Ft Wireless Range</span>
                    </div>
                    <div class="text-center p-6 rounded-2xl border border-white/5 bg-dark-900/30">
                        <span class="counter block text-3xl lg:text-4xl font-display font-bold text-white" data-target="3">0</span>
                        <span class="text-gray-500 text-sm">Hours Play from 10 Min Charge</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ CASE SECTION ============ -->
        <section id="case" class="py-24 lg:py-32 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-[600px] h-[600px] bg-neon-blue/5 rounded-full blur-[200px] pointer-events-none"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                    <div class="case-image relative flex justify-center">
                        <div class="absolute inset-0 bg-gradient-to-br from-neon-blue/10 to-neon-purple/10 rounded-3xl blur-[80px] scale-90"></div>
                        <img src="assets/img/case.png" alt="Beats 3 premium carrying case" class="relative z-10 w-full max-w-[450px] drop-shadow-2xl rounded-2xl" loading="lazy">
                    </div>
                    <div class="case-text">
                        <span class="text-neon-blue text-sm font-semibold uppercase tracking-widest">Premium Protection</span>
                        <h2 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3 mb-6">Travel in <span class="bg-gradient-to-r from-neon-blue to-neon-purple bg-clip-text text-transparent">Style</span></h2>
                        <p class="text-gray-400 text-lg leading-relaxed mb-6">
                            A sleek, hard-shell case designed to protect your Beats 3 wherever life takes you. Ultra-lightweight, water-resistant, and magnetically sealed for maximum durability.
                        </p>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-center gap-3 text-gray-400"><i class="ri-check-line text-neon-blue text-lg"></i> Impact-resistant hard shell</li>
                            <li class="flex items-center gap-3 text-gray-400"><i class="ri-check-line text-neon-blue text-lg"></i> Magnetic snap closure</li>
                            <li class="flex items-center gap-3 text-gray-400"><i class="ri-check-line text-neon-blue text-lg"></i> Included free with every order</li>
                        </ul>
                        <a href="#products" class="inline-flex items-center gap-2 px-6 py-3 border border-neon-blue/30 text-neon-blue rounded-xl hover:bg-neon-blue/10 transition-all duration-300 font-medium">
                            <i class="ri-information-line"></i> Explore Products
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ WHY CHOOSE US ============ -->
        <section class="py-24 lg:py-32 relative overflow-hidden border-y border-white/5">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="text-neon-purple text-sm font-semibold uppercase tracking-widest">Why Shop With Us</span>
                    <h2 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3">Shop with <span class="bg-gradient-to-r from-neon-purple to-neon-pink bg-clip-text text-transparent">Confidence</span></h2>
                </div>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="trust-card group text-center p-8 rounded-3xl border border-white/5 bg-dark-900/30 hover:bg-dark-900/60 hover:border-neon-blue/20 transition-all duration-500">
                        <div class="w-16 h-16 mx-auto bg-gradient-to-br from-neon-blue/20 to-neon-blue/5 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i class="ri-truck-line text-3xl text-neon-blue"></i>
                        </div>
                        <h3 class="text-white font-semibold text-lg mb-2">Delivered in <?= e(setting('delivery_estimate')) ?></h3>
                        <p class="text-gray-500 text-sm"><?= e(shippingSummary()) ?>. Track every order from your account.</p>
                    </div>
                    <div class="trust-card group text-center p-8 rounded-3xl border border-white/5 bg-dark-900/30 hover:bg-dark-900/60 hover:border-neon-purple/20 transition-all duration-500">
                        <div class="w-16 h-16 mx-auto bg-gradient-to-br from-neon-purple/20 to-neon-purple/5 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i class="<?= $warranty ? 'ri-shield-check-line' : 'ri-customer-service-2-line' ?> text-3xl text-neon-purple"></i>
                        </div>
                        <?php if ($warranty): ?>
                        <h3 class="text-white font-semibold text-lg mb-2"><?= e(ucfirst($warranty)) ?> Warranty</h3>
                        <p class="text-gray-500 text-sm">Covers manufacturing defects under normal use. We repair or replace faulty units.</p>
                        <?php else: ?>
                        <h3 class="text-white font-semibold text-lg mb-2">Real Human Support</h3>
                        <p class="text-gray-500 text-sm">Questions before or after you buy? Message us and a real person will help.</p>
                        <?php endif; ?>
                    </div>
                    <div class="trust-card group text-center p-8 rounded-3xl border border-white/5 bg-dark-900/30 hover:bg-dark-900/60 hover:border-neon-pink/20 transition-all duration-500">
                        <div class="w-16 h-16 mx-auto bg-gradient-to-br from-neon-pink/20 to-neon-pink/5 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i class="<?= $returnDays > 0 ? 'ri-refresh-line' : 'ri-secure-payment-line' ?> text-3xl text-neon-pink"></i>
                        </div>
                        <?php if ($returnDays > 0): ?>
                        <h3 class="text-white font-semibold text-lg mb-2"><?= $returnDays ?>-Day Returns</h3>
                        <p class="text-gray-500 text-sm">Not in love? Return unused items in their original packaging within <?= $returnDays ?> days of delivery.</p>
                        <?php else: ?>
                        <h3 class="text-white font-semibold text-lg mb-2">Pay Your Way</h3>
                        <p class="text-gray-500 text-sm"><?= e(implode(', ', array_column(paymentMethods(), 'label'))) ?>.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ PRODUCTS (Dynamic from DB) ============ -->
        <section id="products" class="py-24 lg:py-32 relative overflow-hidden">
            <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-neon-blue/5 rounded-full blur-[200px] pointer-events-none"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-16">
                    <span class="text-neon-blue text-sm font-semibold uppercase tracking-widest">Collection</span>
                    <h2 class="font-display text-4xl lg:text-6xl font-bold text-white mt-3">Choose Your <span class="bg-gradient-to-r from-neon-blue to-neon-purple bg-clip-text text-transparent">Style</span></h2>
                    <p class="text-gray-500 mt-4 max-w-md mx-auto"><?= count($products) ?> colorways, one iconic sound. Tap a pair for a quick look.</p>
                </div>

                <div class="products-scroll flex lg:grid lg:grid-cols-<?= min(count($products), 5) ?> gap-6 overflow-x-auto pb-4 snap-x snap-mandatory lg:overflow-visible scrollbar-hide">
                    <?php foreach ($products as $product): ?>
                    <article class="product-card group min-w-[260px] lg:min-w-0 snap-center flex-shrink-0 cursor-pointer"
                        data-id="<?= (int) $product['id'] ?>"
                        data-name="<?= e($product['name']) ?>"
                        data-price="<?= e((string) effectivePrice($product)) ?>"
                        data-img="<?= e($product['image']) ?>"
                        data-url="<?= e($basePath) ?>/product.php?slug=<?= e($product['slug']) ?>"
                        data-color="<?= e($product['color']) ?>"
                        data-color-hex="<?= e($product['color_hex'] ?: '#1a1a1a') ?>">
                        <div class="relative p-6 rounded-3xl border border-white/5 bg-dark-900/40 backdrop-blur-sm hover:border-neon-blue/30 transition-all duration-500 overflow-hidden">
                            <?php if ($product['featured']): ?>
                            <div class="absolute top-3 right-3 text-xs px-2 py-1 bg-neon-blue/10 text-neon-blue rounded-full font-medium">Featured</div>
                            <?php endif; ?>
                            <?php if (isOnSale($product)): ?>
                            <div class="absolute top-3 left-3 text-xs px-2 py-1 bg-red-500/10 text-red-400 rounded-full font-medium">Sale</div>
                            <?php endif; ?>
                            <div class="flex justify-center py-6 mb-4">
                                <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" width="160" height="160" class="w-40 h-40 object-contain drop-shadow-xl group-hover:scale-110 transition-transform duration-500" loading="lazy">
                            </div>
                            <h3 class="text-white font-semibold text-lg"><?= e($product['color'] ?: $product['name']) ?></h3>
                            <div class="flex items-center justify-between mt-2">
                                <?php if (isOnSale($product)): ?>
                                <div>
                                    <span class="text-xl font-bold text-white"><?= formatPrice(effectivePrice($product)) ?></span>
                                    <span class="text-sm text-gray-500 line-through ml-2"><?= formatPrice($product['price']) ?></span>
                                </div>
                                <?php else: ?>
                                <span class="text-xl font-bold text-white"><?= formatPrice($product['price']) ?></span>
                                <?php endif; ?>
                                <?php if ((int) $product['stock'] > 0): ?>
                                <button class="add-to-cart-btn w-10 h-10 bg-neon-blue/10 hover:bg-neon-blue text-neon-blue hover:text-dark-950 rounded-xl flex items-center justify-center transition-all duration-300" aria-label="Add <?= e($product['color'] ?: $product['name']) ?> to cart">
                                    <i class="ri-shopping-bag-line"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php if ((int) $product['stock'] === 0): ?>
                            <p class="text-xs text-red-400 mt-2">Out of stock</p>
                            <?php elseif ($product['stock'] <= $lowStockAt): ?>
                            <p class="text-xs text-orange-400 mt-2">Only <?= (int) $product['stock'] ?> left</p>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($testimonials): ?>
        <!-- ============ TESTIMONIALS ============ -->
        <section id="testimonials" class="py-24 lg:py-32 relative overflow-hidden border-t border-white/5">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="text-neon-pink text-sm font-semibold uppercase tracking-widest">Reviews</span>
                    <h2 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3">What Customers <span class="bg-gradient-to-r from-neon-pink to-neon-purple bg-clip-text text-transparent">Say</span></h2>
                </div>

                <div class="testimonial-track relative overflow-hidden">
                    <div id="testimonial-slider" class="flex transition-transform duration-700 ease-out">
                        <?php
                        $avatarGradients = ['from-neon-blue to-neon-purple', 'from-neon-pink to-neon-purple', 'from-neon-blue to-neon-cyan', 'from-yellow-400 to-orange-500', 'from-green-400 to-teal-500', 'from-red-500 to-pink-500'];
                        foreach ($testimonials as $i => $t):
                            $initials = strtoupper(implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($t['name'])), 0, 2))));
                            $rating = max(1, min(5, (int) $t['rating']));
                        ?>
                        <div class="testimonial-slide min-w-full md:min-w-[50%] lg:min-w-[33.333%] px-3">
                            <div class="p-8 rounded-3xl border border-white/5 bg-dark-900/40 backdrop-blur-sm h-full">
                                <div class="flex text-yellow-400 text-sm mb-4" aria-label="<?= $rating ?> out of 5 stars">
                                    <?php for ($s = 1; $s <= 5; $s++): ?><i class="<?= $s <= $rating ? 'ri-star-fill' : 'ri-star-line text-gray-600' ?>"></i><?php endfor; ?>
                                </div>
                                <p class="text-gray-300 mb-6 leading-relaxed">&ldquo;<?= e($t['quote']) ?>&rdquo;</p>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br <?= $avatarGradients[$i % count($avatarGradients)] ?> rounded-full flex items-center justify-center text-white font-bold text-sm"><?= e($initials) ?></div>
                                    <div><p class="text-white font-medium text-sm"><?= e($t['name']) ?></p><?php if ($t['role']): ?><p class="text-gray-500 text-xs"><?= e($t['role']) ?></p><?php endif; ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Testimonial controls -->
                <div class="flex items-center justify-center gap-4 mt-10">
                    <button id="test-prev" class="w-12 h-12 rounded-full border border-white/10 flex items-center justify-center text-gray-400 hover:text-white hover:border-neon-blue/30 transition-all" aria-label="Previous review">
                        <i class="ri-arrow-left-s-line text-xl"></i>
                    </button>
                    <div id="test-dots" class="flex gap-2"></div>
                    <button id="test-next" class="w-12 h-12 rounded-full border border-white/10 flex items-center justify-center text-gray-400 hover:text-white hover:border-neon-blue/30 transition-all" aria-label="Next review">
                        <i class="ri-arrow-right-s-line text-xl"></i>
                    </button>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($promo && $heroProduct): ?>
        <!-- ============ PROMOTION (Admin → Settings → Marketing) ============ -->
        <section id="offer" class="py-24 lg:py-32 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-neon-blue/5 via-neon-purple/10 to-neon-pink/5 pointer-events-none"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="discount-text">
                        <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-red-500/10 border border-red-500/20 rounded-full text-red-400 text-xs font-semibold uppercase tracking-widest mb-6">
                            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span> <?= $promo['ends_at'] ? 'Limited Time Offer' : 'Special Offer' ?>
                        </span>
                        <h2 class="font-display text-4xl lg:text-6xl font-bold text-white mb-4"><?= e($promo['title']) ?></h2>
                        <p class="text-gray-400 text-lg mb-8"><?= e($promo['text']) ?></p>

                        <?php if ($promo['ends_at']): ?>
                        <!-- Countdown to the real end date set in Admin → Settings -->
                        <div class="flex gap-4 mb-8" id="countdown" data-ends="<?= (int) $promo['ends_at'] * 1000 ?>">
                            <div class="text-center">
                                <span id="countdown-days" class="block text-3xl lg:text-4xl font-display font-bold text-white bg-dark-900/60 border border-white/5 rounded-2xl px-4 py-3 min-w-[80px]">00</span>
                                <span class="text-xs text-gray-500 mt-2 block">Days</span>
                            </div>
                            <div class="text-center">
                                <span id="countdown-hours" class="block text-3xl lg:text-4xl font-display font-bold text-white bg-dark-900/60 border border-white/5 rounded-2xl px-4 py-3 min-w-[80px]">00</span>
                                <span class="text-xs text-gray-500 mt-2 block">Hours</span>
                            </div>
                            <div class="text-center">
                                <span id="countdown-mins" class="block text-3xl lg:text-4xl font-display font-bold text-white bg-dark-900/60 border border-white/5 rounded-2xl px-4 py-3 min-w-[80px]">00</span>
                                <span class="text-xs text-gray-500 mt-2 block">Minutes</span>
                            </div>
                            <div class="text-center">
                                <span id="countdown-secs" class="block text-3xl lg:text-4xl font-display font-bold text-neon-blue bg-dark-900/60 border border-neon-blue/10 rounded-2xl px-4 py-3 min-w-[80px]">00</span>
                                <span class="text-xs text-gray-500 mt-2 block">Seconds</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 -mt-4 mb-8">Offer ends <?= e(date('F j, Y', $promo['ends_at'])) ?>.</p>
                        <?php endif; ?>

                        <div class="flex flex-wrap items-center gap-4">
                            <a href="<?= e($basePath) ?>/shop.php" class="group relative px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-2xl font-bold text-dark-950 text-lg flex items-center gap-3 hover:scale-105 transition-all duration-300 overflow-hidden">
                                <i class="ri-shopping-bag-line text-xl"></i> Shop Now
                                <div class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 skew-x-12"></div>
                            </a>
                            <?php if ($promo['code']): ?>
                            <button type="button" class="promo-code inline-flex items-center gap-2 px-5 py-4 rounded-2xl border border-dashed border-neon-blue/40 text-neon-blue font-mono font-bold tracking-widest hover:bg-neon-blue/10 transition-colors" data-code="<?= e($promo['code']) ?>" aria-label="Copy code <?= e($promo['code']) ?>">
                                <?= e($promo['code']) ?> <i class="ri-file-copy-line text-base"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="discount-image flex justify-center relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-neon-blue/20 to-neon-purple/20 rounded-full blur-[100px] scale-75"></div>
                        <img src="assets/img/discount.png" alt="<?= e($promo['title']) ?>" width="525" height="361" class="relative z-10 w-full max-w-[400px] drop-shadow-2xl" loading="lazy">
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ============ FAQ ============ -->
        <section id="faq" class="py-24 lg:py-32 relative overflow-hidden border-t border-white/5">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="text-neon-cyan text-sm font-semibold uppercase tracking-widest">Support</span>
                    <h2 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3">Frequently Asked <span class="bg-gradient-to-r from-neon-cyan to-neon-blue bg-clip-text text-transparent">Questions</span></h2>
                </div>

                <div class="space-y-4" id="faq-accordion">
                    <div class="faq-item border border-white/5 rounded-2xl overflow-hidden bg-dark-900/30">
                        <button class="faq-trigger w-full flex items-center justify-between p-6 text-left text-white font-medium hover:text-neon-blue transition-colors" aria-expanded="false">
                            <span>What&rsquo;s the battery life of Beats 3?</span>
                            <i class="ri-add-line text-xl faq-icon transition-transform duration-300"></i>
                        </button>
                        <div class="faq-content px-6 overflow-hidden" style="height: 0">
                            <p class="text-gray-400 pb-6 leading-relaxed">Beats 3 delivers up to 40 hours of listening time on a single charge. With Fast Fuel, a 10-minute charge gives you 3 hours of playback &mdash; perfect for when you&rsquo;re on the go.</p>
                        </div>
                    </div>
                    <div class="faq-item border border-white/5 rounded-2xl overflow-hidden bg-dark-900/30">
                        <button class="faq-trigger w-full flex items-center justify-between p-6 text-left text-white font-medium hover:text-neon-blue transition-colors" aria-expanded="false">
                            <span>Are Beats 3 good for working out?</span>
                            <i class="ri-add-line text-xl faq-icon transition-transform duration-300"></i>
                        </button>
                        <div class="faq-content px-6 overflow-hidden" style="height: 0">
                            <p class="text-gray-400 pb-6 leading-relaxed">While Beats 3 are on-ear headphones designed for premium listening, they feature a secure fit and sweat-resistant coating. For intense workouts, we recommend pairing them with the sport ear hooks (sold separately).</p>
                        </div>
                    </div>
                    <div class="faq-item border border-white/5 rounded-2xl overflow-hidden bg-dark-900/30">
                        <button class="faq-trigger w-full flex items-center justify-between p-6 text-left text-white font-medium hover:text-neon-blue transition-colors" aria-expanded="false">
                            <span>What&rsquo;s included in the box?</span>
                            <i class="ri-add-line text-xl faq-icon transition-transform duration-300"></i>
                        </button>
                        <div class="faq-content px-6 overflow-hidden" style="height: 0">
                            <p class="text-gray-400 pb-6 leading-relaxed">Every Beats 3 comes with: the headphones, a premium hard-shell carrying case, a USB-C charging cable, a 3.5mm audio cable for wired use, a Quick Start guide, and a warranty card. Everything you need, right out of the box.</p>
                        </div>
                    </div>
                    <div class="faq-item border border-white/5 rounded-2xl overflow-hidden bg-dark-900/30">
                        <button class="faq-trigger w-full flex items-center justify-between p-6 text-left text-white font-medium hover:text-neon-blue transition-colors" aria-expanded="false">
                            <span>Can I connect to multiple devices?</span>
                            <i class="ri-add-line text-xl faq-icon transition-transform duration-300"></i>
                        </button>
                        <div class="faq-content px-6 overflow-hidden" style="height: 0">
                            <p class="text-gray-400 pb-6 leading-relaxed">Yes! Beats 3 features Bluetooth 5.2 with multipoint pairing. You can stay connected to your phone and laptop simultaneously, seamlessly switching audio between devices without the need to disconnect and reconnect.</p>
                        </div>
                    </div>
                    <div class="faq-item border border-white/5 rounded-2xl overflow-hidden bg-dark-900/30">
                        <button class="faq-trigger w-full flex items-center justify-between p-6 text-left text-white font-medium hover:text-neon-blue transition-colors" aria-expanded="false">
                            <span>What&rsquo;s the return policy?</span>
                            <i class="ri-add-line text-xl faq-icon transition-transform duration-300"></i>
                        </button>
                        <div class="faq-content px-6 overflow-hidden" style="height: 0">
                            <?php if ($returnDays > 0): ?>
                            <p class="text-gray-400 pb-6 leading-relaxed">You can return unused items in their original packaging within <?= $returnDays ?> days of delivery for a full refund. <a href="<?= e($basePath) ?>/contact.php" class="text-neon-blue hover:underline">Contact us</a> to start a return.<?= $warranty ? ' Faulty units are covered by our ' . e($warranty) . ' warranty.' : '' ?></p>
                            <?php else: ?>
                            <p class="text-gray-400 pb-6 leading-relaxed">Please <a href="<?= e($basePath) ?>/contact.php" class="text-neon-blue hover:underline">contact us</a> about returns. Read our <a href="<?= e($basePath) ?>/legal/terms-of-service.php#returns" class="text-neon-blue hover:underline">terms</a> for details.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ FINAL CTA + NEWSLETTER ============ -->
        <section id="newsletter" class="py-24 lg:py-32 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-neon-blue/5 to-transparent pointer-events-none"></div>
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <h2 class="font-display text-4xl lg:text-6xl font-bold text-white mb-6">Ready to Experience<br><span class="bg-gradient-to-r from-neon-blue via-neon-purple to-neon-pink bg-clip-text text-transparent">Pure Sound?</span></h2>
                <p class="text-gray-400 text-lg max-w-xl mx-auto mb-10">Get new releases and subscriber-only offers in your inbox.<?= setting('newsletter_coupon') !== '' ? ' Subscribe now and we&rsquo;ll email you a welcome code for your first order.' : '' ?></p>

                <form id="newsletter-form" class="flex flex-col sm:flex-row gap-4 max-w-lg mx-auto mb-6" action="api/newsletter.php" method="POST">
                    <?= csrfField() ?>
                    <label for="newsletter-email" class="sr-only">Email address</label>
                    <input type="email" id="newsletter-email" name="email" placeholder="Enter your email" required class="flex-1 px-6 py-4 bg-dark-900/60 border border-white/10 rounded-2xl text-white placeholder-gray-500 focus:border-neon-blue/50 focus:outline-none transition-colors" autocomplete="email">
                    <button type="submit" class="px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple text-dark-950 font-bold rounded-2xl hover:scale-105 transition-transform flex items-center justify-center gap-2 whitespace-nowrap">
                        <i class="ri-send-plane-line"></i> Subscribe
                    </button>
                </form>
                <p id="newsletter-note" class="text-xs text-gray-600">No spam, ever. Every email has a one-click unsubscribe link.</p>
            </div>
        </section>

    <!-- Floating Add to Cart (dynamic from hero product) -->
    <?php if ($heroProduct && $heroProduct['stock'] > 0): ?>
    <div id="floating-cta" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[140] opacity-0 translate-y-10 pointer-events-none transition-all duration-500">
        <button class="flex items-center gap-3 px-6 py-3 bg-dark-900/90 backdrop-blur-xl border border-neon-blue/30 rounded-full shadow-2xl shadow-neon-blue/10 hover:border-neon-blue/60 transition-all add-to-cart-trigger"
            data-id="<?= (int) $heroProduct['id'] ?>"
            data-name="<?= e($heroProduct['name']) ?>"
            data-price="<?= e((string) effectivePrice($heroProduct)) ?>"
            data-img="<?= e($heroProduct['image']) ?>">
            <i class="ri-shopping-bag-line text-neon-blue"></i>
            <span class="text-white font-medium text-sm">Add <?= e($heroProduct['color'] ?: $heroProduct['name']) ?> to Cart</span>
            <span class="text-neon-blue font-bold"><?= formatPrice(effectivePrice($heroProduct)) ?></span>
        </button>
    </div>
    <?php endif; ?>

<!-- JSON-LD: Organization + WebSite -->
<script type="application/ld+json">
<?= jsonForScript([
    '@context' => 'https://schema.org',
    '@graph'   => [
        array_filter([
            '@type'  => 'Organization',
            'name'   => storeName(),
            'url'    => absoluteUrl('/'),
            'logo'   => absoluteUrl('assets/img/logo.png'),
            'email'  => setting('store_email') ?: null,
            'telephone' => setting('store_phone') ?: null,
            'sameAs' => array_values(array_column(socialLinks(), 0)) ?: null,
        ]),
        [
            '@type' => 'WebSite',
            'name'  => storeName(),
            'url'   => absoluteUrl('/'),
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => absoluteUrl('/shop.php') . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ],
]) ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
