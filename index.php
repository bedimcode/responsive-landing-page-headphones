<?php
/**
 * Headphones1.Duka — Homepage
 */
$pageTitle = 'Beats 3 — Studio-Grade Wireless Headphones | Premium Sound';
$pageDescription = 'Beats 3 — Studio-grade wireless headphones with 40h battery, Bluetooth 5.2, and premium sound. Free shipping & 30-day returns.';
$pageType = 'home';
$includeAnimations = true;

require_once __DIR__ . '/includes/header.php';

// Fetch products from database
try {
    $db = getDB();
    $products = getActiveProducts($db);
} catch (Exception $e) {
    $products = [];
}

// Get hero product (first featured or first product)
$heroProduct = null;
foreach ($products as $p) {
    if ($p['featured']) { $heroProduct = $p; break; }
}
if (!$heroProduct && !empty($products)) {
    $heroProduct = $products[0];
}
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
                            <span class="w-2 h-2 bg-neon-blue rounded-full animate-pulse"></span> New Release <?= date('Y') ?>
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
                                <?php foreach ($products as $i => $p): ?>
                                <button class="color-swatch w-8 h-8 rounded-full border-2 <?= $i === 0 ? 'border-white/30 active' : 'border-transparent' ?> transition-all hover:scale-110" 
                                    data-color="<?= e($p['slug']) ?>" 
                                    data-img="<?= e($p['image']) ?>" 
                                    style="background: <?= e($p['color_hex']) ?>" 
                                    title="<?= e($p['color']) ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Price + CTA -->
                        <div class="flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start hero-cta">
                            <?php if ($heroProduct): ?>
                            <button id="hero-add-to-cart" class="group relative px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-2xl font-bold text-dark-950 text-lg flex items-center gap-3 hover:scale-105 transition-all duration-300 overflow-hidden add-to-cart-trigger" 
                                data-id="<?= (int) $heroProduct['id'] ?>" 
                                data-name="<?= e($heroProduct['name']) ?>" 
                                data-price="<?= e($heroProduct['sale_price'] ?? $heroProduct['price']) ?>" 
                                data-img="<?= e($heroProduct['image']) ?>">
                                <span class="relative z-10 flex items-center gap-3">
                                    <i class="ri-shopping-bag-line text-xl"></i> Add to Bag
                                </span>
                                <span class="relative z-10 px-3 py-1 bg-dark-950/20 rounded-xl text-sm"><?= formatPrice($heroProduct['sale_price'] ?? $heroProduct['price']) ?></span>
                                <div class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 skew-x-12"></div>
                            </button>
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <i class="ri-truck-line text-neon-blue"></i>
                                <span>Free shipping · <strong class="text-orange-400">Only <?= (int) $heroProduct['stock'] ?> left</strong></span>
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
                             alt="Beats 3 Wireless Headphones in <?= e($heroProduct['color'] ?? 'Black') ?>" 
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

        <!-- ============ TRUSTED BY / SPONSOR ============ -->
        <section class="py-16 border-y border-white/5 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-center text-xs uppercase tracking-[0.3em] text-gray-500 mb-10">Trusted by the world's best</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 items-center" id="sponsors">
                    <div class="sponsor-item flex justify-center opacity-40 hover:opacity-80 transition-opacity duration-500">
                        <img src="assets/img/sponsor1.png" alt="Sponsor partner" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all duration-500" loading="lazy">
                    </div>
                    <div class="sponsor-item flex justify-center opacity-40 hover:opacity-80 transition-opacity duration-500">
                        <img src="assets/img/sponsor2.png" alt="Sponsor partner" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all duration-500" loading="lazy">
                    </div>
                    <div class="sponsor-item flex justify-center opacity-40 hover:opacity-80 transition-opacity duration-500">
                        <img src="assets/img/sponsor3.png" alt="Sponsor partner" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all duration-500" loading="lazy">
                    </div>
                    <div class="sponsor-item flex justify-center opacity-40 hover:opacity-80 transition-opacity duration-500">
                        <img src="assets/img/sponsor4.png" alt="Sponsor partner" class="h-8 md:h-10 object-contain grayscale hover:grayscale-0 transition-all duration-500" loading="lazy">
                    </div>
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
                        <span class="counter block text-3xl lg:text-4xl font-display font-bold text-neon-blue" data-target="98">0</span>
                        <span class="text-gray-500 text-sm">% Satisfaction</span>
                    </div>
                    <div class="text-center p-6 rounded-2xl border border-white/5 bg-dark-900/30">
                        <span class="counter block text-3xl lg:text-4xl font-display font-bold text-white" data-target="2847">0</span>
                        <span class="text-gray-500 text-sm">5-Star Reviews</span>
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
                    <span class="text-neon-purple text-sm font-semibold uppercase tracking-widest">Why Beats</span>
                    <h2 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3">The <span class="bg-gradient-to-r from-neon-purple to-neon-pink bg-clip-text text-transparent">Beats</span> Difference</h2>
                </div>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="trust-card group text-center p-8 rounded-3xl border border-white/5 bg-dark-900/30 hover:bg-dark-900/60 hover:border-neon-blue/20 transition-all duration-500">
                        <div class="w-16 h-16 mx-auto bg-gradient-to-br from-neon-blue/20 to-neon-blue/5 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i class="ri-truck-line text-3xl text-neon-blue"></i>
                        </div>
                        <h3 class="text-white font-semibold text-lg mb-2">Free 24h Delivery</h3>
                        <p class="text-gray-500 text-sm">Lightning-fast shipping on every order. Get your Beats 3 at your door by tomorrow.</p>
                    </div>
                    <div class="trust-card group text-center p-8 rounded-3xl border border-white/5 bg-dark-900/30 hover:bg-dark-900/60 hover:border-neon-purple/20 transition-all duration-500">
                        <div class="w-16 h-16 mx-auto bg-gradient-to-br from-neon-purple/20 to-neon-purple/5 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i class="ri-shield-check-line text-3xl text-neon-purple"></i>
                        </div>
                        <h3 class="text-white font-semibold text-lg mb-2">2-Year Warranty</h3>
                        <p class="text-gray-500 text-sm">Full coverage against manufacturing defects. We stand behind every pair we make.</p>
                    </div>
                    <div class="trust-card group text-center p-8 rounded-3xl border border-white/5 bg-dark-900/30 hover:bg-dark-900/60 hover:border-neon-pink/20 transition-all duration-500">
                        <div class="w-16 h-16 mx-auto bg-gradient-to-br from-neon-pink/20 to-neon-pink/5 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i class="ri-refresh-line text-3xl text-neon-pink"></i>
                        </div>
                        <h3 class="text-white font-semibold text-lg mb-2">30-Day Returns</h3>
                        <p class="text-gray-500 text-sm">Not in love? Send them back, no questions asked. Full refund guaranteed.</p>
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
                    <p class="text-gray-500 mt-4 max-w-md mx-auto"><?= count($products) ?> premium colorways, one iconic sound. Find the Beats 3 that matches your vibe.</p>
                </div>

                <div class="products-scroll flex lg:grid lg:grid-cols-<?= min(count($products), 5) ?> gap-6 overflow-x-auto pb-4 snap-x snap-mandatory lg:overflow-visible scrollbar-hide">
                    <?php foreach ($products as $product): ?>
                    <article class="product-card group min-w-[260px] lg:min-w-0 snap-center flex-shrink-0 cursor-pointer" 
                        data-id="<?= (int) $product['id'] ?>" 
                        data-name="<?= e($product['name']) ?>" 
                        data-price="<?= e($product['sale_price'] ?? $product['price']) ?>" 
                        data-img="<?= e($product['image']) ?>" 
                        data-color="<?= e($product['color']) ?>"
                        data-color-hex="<?= e($product['color_hex'] ?? '#1a1a1a') ?>">
                        <div class="relative p-6 rounded-3xl border border-white/5 bg-dark-900/40 backdrop-blur-sm hover:border-neon-blue/30 transition-all duration-500 overflow-hidden">
                            <?php if ($product['featured']): ?>
                            <div class="absolute top-3 right-3 text-xs px-2 py-1 bg-neon-blue/10 text-neon-blue rounded-full font-medium"><?= $product['slug'] === 'beats3-blue' ? 'New' : 'Popular' ?></div>
                            <?php endif; ?>
                            <?php if ($product['sale_price']): ?>
                            <div class="absolute top-3 left-3 text-xs px-2 py-1 bg-red-500/10 text-red-400 rounded-full font-medium">Sale</div>
                            <?php endif; ?>
                            <div class="flex justify-center py-6 mb-4">
                                <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" class="w-40 h-40 object-contain drop-shadow-xl group-hover:scale-110 transition-transform duration-500" loading="lazy">
                            </div>
                            <h3 class="text-white font-semibold text-lg"><?= e($product['color']) ?></h3>
                            <div class="flex items-center justify-between mt-2">
                                <?php if ($product['sale_price']): ?>
                                <div>
                                    <span class="text-xl font-bold text-white"><?= formatPrice($product['sale_price']) ?></span>
                                    <span class="text-sm text-gray-500 line-through ml-2"><?= formatPrice($product['price']) ?></span>
                                </div>
                                <?php else: ?>
                                <span class="text-xl font-bold text-white"><?= formatPrice($product['price']) ?></span>
                                <?php endif; ?>
                                <button class="add-to-cart-btn w-10 h-10 bg-neon-blue/10 hover:bg-neon-blue text-neon-blue hover:text-dark-950 rounded-xl flex items-center justify-center transition-all duration-300" aria-label="Add <?= e($product['color']) ?> to cart">
                                    <i class="ri-shopping-bag-line"></i>
                                </button>
                            </div>
                            <?php if ($product['stock'] < 10 && $product['stock'] > 0): ?>
                            <p class="text-xs text-orange-400 mt-2">Only <?= (int) $product['stock'] ?> left</p>
                            <?php elseif ($product['stock'] === 0): ?>
                            <p class="text-xs text-red-400 mt-2">Out of stock</p>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- ============ TESTIMONIALS ============ -->
        <section id="testimonials" class="py-24 lg:py-32 relative overflow-hidden border-t border-white/5">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="text-neon-pink text-sm font-semibold uppercase tracking-widest">Social Proof</span>
                    <h2 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3">Loved by <span class="bg-gradient-to-r from-neon-pink to-neon-purple bg-clip-text text-transparent">Thousands</span></h2>
                </div>

                <div class="testimonial-track relative overflow-hidden">
                    <div id="testimonial-slider" class="flex transition-transform duration-700 ease-out">
                        <div class="testimonial-slide min-w-full md:min-w-[50%] lg:min-w-[33.333%] px-3">
                            <div class="p-8 rounded-3xl border border-white/5 bg-dark-900/40 backdrop-blur-sm h-full">
                                <div class="flex text-yellow-400 text-sm mb-4"><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i></div>
                                <p class="text-gray-300 mb-6 leading-relaxed">&ldquo;The sound quality is absolutely insane. I&rsquo;m a music producer and these are now my daily drivers. The bass is deep without being muddy. Worth every penny.&rdquo;</p>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-neon-blue to-neon-purple rounded-full flex items-center justify-center text-white font-bold text-sm">JR</div>
                                    <div><p class="text-white font-medium text-sm">James Rodriguez</p><p class="text-gray-500 text-xs">Music Producer, LA</p></div>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-slide min-w-full md:min-w-[50%] lg:min-w-[33.333%] px-3">
                            <div class="p-8 rounded-3xl border border-white/5 bg-dark-900/40 backdrop-blur-sm h-full">
                                <div class="flex text-yellow-400 text-sm mb-4"><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i></div>
                                <p class="text-gray-300 mb-6 leading-relaxed">&ldquo;40 hours battery life is no joke. I use these on 14-hour flights and still have juice left. The noise isolation is incredible for on-ear headphones.&rdquo;</p>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-neon-pink to-neon-purple rounded-full flex items-center justify-center text-white font-bold text-sm">SK</div>
                                    <div><p class="text-white font-medium text-sm">Sarah Kim</p><p class="text-gray-500 text-xs">Travel Blogger</p></div>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-slide min-w-full md:min-w-[50%] lg:min-w-[33.333%] px-3">
                            <div class="p-8 rounded-3xl border border-white/5 bg-dark-900/40 backdrop-blur-sm h-full">
                                <div class="flex text-yellow-400 text-sm mb-4"><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-half-fill"></i></div>
                                <p class="text-gray-300 mb-6 leading-relaxed">&ldquo;Best headphones under $300, period. The build quality feels premium, the cushions are like clouds, and they fold flat for my backpack. Game changer.&rdquo;</p>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-neon-blue to-neon-cyan rounded-full flex items-center justify-center text-white font-bold text-sm">MT</div>
                                    <div><p class="text-white font-medium text-sm">Marcus Thompson</p><p class="text-gray-500 text-xs">Tech Reviewer</p></div>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-slide min-w-full md:min-w-[50%] lg:min-w-[33.333%] px-3">
                            <div class="p-8 rounded-3xl border border-white/5 bg-dark-900/40 backdrop-blur-sm h-full">
                                <div class="flex text-yellow-400 text-sm mb-4"><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i></div>
                                <p class="text-gray-300 mb-6 leading-relaxed">&ldquo;I bought the Blue ones for my wife and she literally cried. The unboxing experience alone is worth it. These headphones scream luxury.&rdquo;</p>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold text-sm">DL</div>
                                    <div><p class="text-white font-medium text-sm">David Lee</p><p class="text-gray-500 text-xs">Verified Buyer</p></div>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-slide min-w-full md:min-w-[50%] lg:min-w-[33.333%] px-3">
                            <div class="p-8 rounded-3xl border border-white/5 bg-dark-900/40 backdrop-blur-sm h-full">
                                <div class="flex text-yellow-400 text-sm mb-4"><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i></div>
                                <p class="text-gray-300 mb-6 leading-relaxed">&ldquo;Switched from Sony XM5s and honestly don&rsquo;t miss them. The Beats 3 have more character in the sound. Plus they look way better.&rdquo;</p>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-teal-500 rounded-full flex items-center justify-center text-white font-bold text-sm">AN</div>
                                    <div><p class="text-white font-medium text-sm">Aisha Nakamura</p><p class="text-gray-500 text-xs">Audio Engineer</p></div>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-slide min-w-full md:min-w-[50%] lg:min-w-[33.333%] px-3">
                            <div class="p-8 rounded-3xl border border-white/5 bg-dark-900/40 backdrop-blur-sm h-full">
                                <div class="flex text-yellow-400 text-sm mb-4"><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i></div>
                                <p class="text-gray-300 mb-6 leading-relaxed">&ldquo;The Fast Fuel charging is a lifesaver. Forgot to charge before a long run, plugged in for 10 minutes, and had enough for my entire workout.&rdquo;</p>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-sm">CP</div>
                                    <div><p class="text-white font-medium text-sm">Carlos Perez</p><p class="text-gray-500 text-xs">Fitness Coach</p></div>
                                </div>
                            </div>
                        </div>
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

        <!-- ============ DISCOUNT / LIMITED OFFER ============ -->
        <section class="py-24 lg:py-32 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-neon-blue/5 via-neon-purple/10 to-neon-pink/5 pointer-events-none"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="discount-text">
                        <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-red-500/10 border border-red-500/20 rounded-full text-red-400 text-xs font-semibold uppercase tracking-widest mb-6">
                            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span> Limited Time Offer
                        </span>
                        <h2 class="font-display text-4xl lg:text-6xl font-bold text-white mb-4">Immerse Yourself in<br><span class="bg-gradient-to-r from-neon-blue via-neon-purple to-neon-pink bg-clip-text text-transparent">Pure Sound</span></h2>
                        <p class="text-gray-400 text-lg mb-8">Get up to 50% off for a limited time. This offer expires when the timer hits zero.</p>

                        <!-- Countdown timer -->
                        <div class="flex gap-4 mb-8" id="countdown">
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

                        <button class="group relative px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-2xl font-bold text-dark-950 text-lg flex items-center gap-3 hover:scale-105 transition-all duration-300 overflow-hidden add-to-cart-trigger" data-id="<?= (int) $heroProduct['id'] ?>" data-name="<?= e($heroProduct['name']) ?>" data-price="<?= e($heroProduct['sale_price'] ?? $heroProduct['price']) ?>" data-img="<?= e($heroProduct['image']) ?>">
                            <i class="ri-shopping-bag-line text-xl"></i> Shop Now — 50% Off
                            <div class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 skew-x-12"></div>
                        </button>
                    </div>
                    <div class="discount-image flex justify-center relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-neon-blue/20 to-neon-purple/20 rounded-full blur-[100px] scale-75"></div>
                        <img src="assets/img/discount.png" alt="Beats 3 limited discount offer" class="relative z-10 w-full max-w-[400px] drop-shadow-2xl" loading="lazy">
                    </div>
                </div>
            </div>
        </section>

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
                            <p class="text-gray-400 pb-6 leading-relaxed">We offer a hassle-free 30-day return policy. If you&rsquo;re not completely satisfied with your Beats 3, return them in their original packaging for a full refund. No questions asked.</p>
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
                <p class="text-gray-400 text-lg max-w-xl mx-auto mb-10">Join 50,000+ music lovers who made the switch. Subscribe now and get 10% off your first order.</p>
                
                <form id="newsletter-form" class="flex flex-col sm:flex-row gap-4 max-w-lg mx-auto mb-6" action="api/newsletter.php" method="POST">
                    <?= csrfField() ?>
                    <input type="email" id="newsletter-email" name="email" placeholder="Enter your email" required class="flex-1 px-6 py-4 bg-dark-900/60 border border-white/10 rounded-2xl text-white placeholder-gray-500 focus:border-neon-blue/50 focus:outline-none transition-colors" autocomplete="email">
                    <button type="submit" class="px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple text-dark-950 font-bold rounded-2xl hover:scale-105 transition-transform flex items-center justify-center gap-2 whitespace-nowrap">
                        <i class="ri-send-plane-line"></i> Subscribe
                    </button>
                </form>
                <p class="text-xs text-gray-600">No spam, ever. Unsubscribe anytime.</p>
            </div>
        </section>

    <!-- Floating Add to Cart (dynamic from hero product) -->
    <?php if ($heroProduct): ?>
    <div id="floating-cta" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[140] opacity-0 translate-y-10 pointer-events-none transition-all duration-500">
        <button class="flex items-center gap-3 px-6 py-3 bg-dark-900/90 backdrop-blur-xl border border-neon-blue/30 rounded-full shadow-2xl shadow-neon-blue/10 hover:border-neon-blue/60 transition-all add-to-cart-trigger" 
            data-id="<?= (int) $heroProduct['id'] ?>" 
            data-name="<?= e($heroProduct['name']) ?>" 
            data-price="<?= e($heroProduct['sale_price'] ?? $heroProduct['price']) ?>" 
            data-img="<?= e($heroProduct['image']) ?>">
            <i class="ri-shopping-bag-line text-neon-blue"></i>
            <span class="text-white font-medium text-sm">Add Beats 3 to Cart</span>
            <span class="text-neon-blue font-bold"><?= formatPrice($heroProduct['sale_price'] ?? $heroProduct['price']) ?></span>
        </button>
    </div>
    <?php endif; ?>

<!-- JSON-LD: Organization + WebSite -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "Organization",
            "name": "Beats 3 Store",
            "url": "<?= APP_URL ?>/",
            "logo": "<?= APP_URL ?>/assets/img/logo.png",
            "sameAs": [
                "https://www.facebook.com/",
                "https://www.instagram.com/",
                "https://twitter.com/",
                "https://www.youtube.com/"
            ]
        },
        {
            "@type": "WebSite",
            "name": "Beats 3 Store",
            "url": "<?= APP_URL ?>/",
            "potentialAction": {
                "@type": "SearchAction",
                "target": "<?= APP_URL ?>/shop.php?q={search_term_string}",
                "query-input": "required name=search_term_string"
            }
        }
    ]
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
