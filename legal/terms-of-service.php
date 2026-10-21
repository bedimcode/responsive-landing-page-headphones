<?php
/**
 * Terms of Service
 * Store details, payment methods and policies come from Admin → Settings.
 * This is a starting template — have it reviewed for your country before launch.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Terms of Service — ' . storeName();
$pageDescription = 'Read the terms governing your use of the ' . storeName() . ' website and your purchases.';
$pageType = 'page';

require_once __DIR__ . '/../includes/header.php';

$store      = e(storeName());
$returnDays = (int) setting('return_days');
$warranty   = warrantyText();
$methods    = array_column(paymentMethods(), 'label');
$law        = setting('governing_law');
?>

    <main class="pt-32 pb-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="mb-16">
                <a href="<?= e($basePath) ?>/" class="inline-flex items-center gap-2 text-gray-500 hover:text-neon-blue text-sm mb-6 transition-colors"><i class="ri-arrow-left-line"></i> Back to Home</a>
                <span class="block text-neon-blue text-sm font-semibold uppercase tracking-widest">Legal</span>
                <h1 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3 mb-4">Terms of Service</h1>
                <p class="text-gray-500">Last updated: October 20, 2026</p>
            </div>

            <!-- Content -->
            <div class="prose-legal space-y-12">

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">1. Acceptance of Terms</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        By using the <?= $store ?> website and placing orders (the &ldquo;Services&rdquo;), you agree to these Terms of Service (&ldquo;Terms&rdquo;). If you do not agree, please do not use the Services.
                    </p>
                    <p class="text-gray-400 leading-relaxed">
                        We may update these Terms from time to time. The version shown when you place an order applies to that order.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">2. Eligibility</h2>
                    <p class="text-gray-400 leading-relaxed">
                        You must be at least 18 years old, or have a parent or guardian&rsquo;s permission, to place an order. By ordering you confirm that the information you give us is accurate.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">3. Your Account</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">You need an account to order. You agree to:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li>Provide accurate, current and complete information</li>
                        <li>Keep your password secure and confidential</li>
                        <li>Tell us straight away about any unauthorised use of your account</li>
                    </ul>
                    <p class="text-gray-400 leading-relaxed mt-4">
                        We may suspend accounts that are used for fraud or that break these Terms.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">4. Products and Pricing</h2>
                    <h3 class="text-lg font-semibold text-white mb-3">4.1 Product Information</h3>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        We describe and photograph our products as accurately as we can. Colours may look slightly different on your screen.
                    </p>
                    <h3 class="text-lg font-semibold text-white mb-3">4.2 Pricing</h3>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        Prices are shown in <?= e(setting('currency_code')) ?>.
                        <?= settingOn('tax_inclusive')
                            ? 'Prices include ' . e(setting('tax_label') ?: 'tax') . '.'
                            : e(setting('tax_label') ?: 'Tax') . ' is added at checkout.' ?>
                        Shipping costs are shown at checkout before you place your order. If a price is clearly wrong, we will contact you and you may cancel the order for a full refund.
                    </p>
                    <h3 class="text-lg font-semibold text-white mb-3">4.3 Availability</h3>
                    <p class="text-gray-400 leading-relaxed">
                        All products are subject to availability. If an item you ordered is no longer available, we will tell you and refund any payment for it.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">5. Orders and Payment</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        Placing an order is an offer to buy. We confirm it by email. We may decline or cancel an order because of pricing errors, suspected fraud or stock problems; if you have already paid, we refund you in full.
                    </p>
                    <?php if ($methods): ?>
                    <p class="text-gray-400 leading-relaxed mb-4">We currently accept: <?= e(implode(', ', $methods)) ?>.</p>
                    <?php endif; ?>
                    <p class="text-gray-400 leading-relaxed">
                        For M-Pesa and bank transfers, your order is processed once we have confirmed your payment. Orders that are not paid within 3 days may be cancelled. We never ask for your card number, PIN or password by phone, SMS or email.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">6. Shipping and Delivery</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        We usually deliver within <?= e(setting('delivery_estimate')) ?> after your order is confirmed. <?= e(shippingSummary()) ?>. Delivery times are estimates; we will let you know if there is a delay.
                    </p>
                    <p class="text-gray-400 leading-relaxed">
                        Please check your parcel on delivery and tell us within 48 hours if anything is missing or damaged.
                    </p>
                </section>

                <section id="returns" class="scroll-mt-28">
                    <h2 class="text-xl font-display font-bold text-white mb-4">7. Returns and Refunds</h2>
                    <?php if ($returnDays > 0): ?>
                    <p class="text-gray-400 leading-relaxed mb-4">You may return unused products in their original packaging within <?= $returnDays ?> days of delivery. To start a return:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li><a href="<?= e($basePath) ?>/contact.php" class="text-neon-blue hover:underline">Contact us</a> with your order number within <?= $returnDays ?> days of delivery</li>
                        <li>Products must be unused, undamaged and in their original packaging</li>
                        <li>Return shipping is paid by you unless the item is faulty or we sent the wrong item</li>
                    </ul>
                    <p class="text-gray-400 leading-relaxed mt-4">
                        We refund you within 10 business days of receiving the returned item, using the payment method you paid with (or M-Pesa / bank transfer for cash orders).
                    </p>
                    <?php else: ?>
                    <p class="text-gray-400 leading-relaxed">
                        Please <a href="<?= e($basePath) ?>/contact.php" class="text-neon-blue hover:underline">contact us</a> about returns. This does not affect your legal rights for faulty goods.
                    </p>
                    <?php endif; ?>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">8. Warranty</h2>
                    <?php if ($warranty): ?>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        Our products come with a <?= e($warranty) ?> warranty against defects in materials and workmanship under normal use, starting on the delivery date. We will repair or replace a faulty product, or refund it if neither is possible. The warranty does not cover:
                    </p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li>Damage caused by misuse, accidents, liquids or unauthorised repairs</li>
                        <li>Normal wear and tear (for example ear cushions)</li>
                        <li>Lost or stolen products</li>
                    </ul>
                    <?php else: ?>
                    <p class="text-gray-400 leading-relaxed">Products are covered by any manufacturer warranty that applies and by your legal rights for faulty goods.</p>
                    <?php endif; ?>
                    <p class="text-gray-400 leading-relaxed mt-4">Nothing in these Terms limits the rights you have under consumer-protection law.</p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">9. Acceptable Use</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">You agree not to:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li>Use the Services for anything unlawful or fraudulent</li>
                        <li>Try to gain unauthorised access to any part of the Services</li>
                        <li>Interfere with the security or performance of the website</li>
                        <li>Copy website content for commercial use without our written permission</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">10. Limitation of Liability</h2>
                    <p class="text-gray-400 leading-relaxed">
                        To the extent the law allows, <?= $store ?> is not liable for indirect or consequential losses arising from your use of the Services. Our total liability for any order is limited to the amount you paid for it. This does not limit liability that cannot be limited by law.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">11. Governing Law</h2>
                    <p class="text-gray-400 leading-relaxed">
                        These Terms are governed by the laws of <?= $law !== '' ? e($law) : 'the country where ' . $store . ' is registered' ?>. Disputes will be handled by the courts there, unless your local consumer law gives you the right to use your own courts.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">12. Contact Us</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">Questions about these Terms? Contact us:</p>
                    <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/30 space-y-2">
                        <p class="text-gray-300"><strong class="text-white"><?= $store ?></strong></p>
                        <p class="text-gray-400 text-sm">Email: <a href="mailto:<?= e(setting('store_email')) ?>" class="text-neon-blue hover:underline"><?= e(setting('store_email')) ?></a></p>
                        <?php if (setting('store_phone')): ?><p class="text-gray-400 text-sm">Phone: <?= e(setting('store_phone')) ?></p><?php endif; ?>
                        <?php if (setting('store_address')): ?><p class="text-gray-400 text-sm">Address: <?= e(setting('store_address')) ?></p><?php endif; ?>
                    </div>
                </section>

            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
