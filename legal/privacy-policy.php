<?php
/**
 * Privacy Policy
 * Store details come from Admin → Settings. Starting template — review it for your country.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Privacy Policy — ' . storeName();
$pageDescription = 'Learn how ' . storeName() . ' collects, uses and protects your personal information.';
$pageType = 'page';

require_once __DIR__ . '/../includes/header.php';
$store = e(storeName());
?>

    <main class="pt-32 pb-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="mb-16">
                <a href="<?= e($basePath) ?>/" class="inline-flex items-center gap-2 text-gray-500 hover:text-neon-blue text-sm mb-6 transition-colors"><i class="ri-arrow-left-line"></i> Back to Home</a>
                <span class="block text-neon-blue text-sm font-semibold uppercase tracking-widest">Legal</span>
                <h1 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3 mb-4">Privacy Policy</h1>
                <p class="text-gray-500">Last updated: October 20, 2026</p>
            </div>

            <!-- Content -->
            <div class="prose-legal space-y-12">

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">1. Introduction</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        <?= $store ?> (&ldquo;we,&rdquo; &ldquo;our,&rdquo; or &ldquo;us&rdquo;) respects your privacy and is committed to protecting the personal information you share with us. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website, make a purchase, or interact with our services.
                    </p>
                    <p class="text-gray-400 leading-relaxed">
                        By using our website, you agree to the collection and use of information in accordance with this policy. If you do not agree with the terms of this Privacy Policy, please do not access or use our services.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">2. Information We Collect</h2>
                    <h3 class="text-lg font-semibold text-white mb-3">2.1 Personal Information</h3>
                    <p class="text-gray-400 leading-relaxed mb-4">When you make a purchase or create an account, we may collect:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 mb-6 ml-4">
                        <li>Full name and contact information (email address, phone number)</li>
                        <li>Billing and shipping addresses</li>
                        <li>Payment references you send us (for example an M-Pesa or bank transaction code). We never collect card numbers or PINs.</li>
                        <li>Order history and transaction details</li>
                        <li>Account login details (your password is stored only as a one-way hash)</li>
                        <li>Messages you send through our contact form</li>
                    </ul>

                    <h3 class="text-lg font-semibold text-white mb-3">2.2 Automatically Collected Information</h3>
                    <p class="text-gray-400 leading-relaxed mb-4">When you visit our website, our servers record:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li>Your IP address (also used to protect sign-in from password guessing)</li>
                        <li>Browser and device information sent with each request</li>
                        <li>A session cookie that keeps you signed in and remembers your cart (see our <a href="<?= e($basePath) ?>/legal/cookie-policy.php" class="text-neon-blue hover:underline">Cookie Policy</a>)</li>
                    </ul>
                    <p class="text-gray-400 leading-relaxed mt-4">We do not use advertising trackers or sell browsing data.</p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">3. How We Use Your Information</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">We use the information we collect to:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li>Process and fulfill your orders, including shipping and delivery</li>
                        <li>Communicate with you about your orders, account, or customer service inquiries</li>
                        <li>Send promotional emails and newsletters (only with your consent; you may opt out at any time)</li>
                        <li>Improve our website, products, and services</li>
                        <li>Prevent fraud and maintain the security of our platform</li>
                        <li>Comply with legal obligations and enforce our terms</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">4. Information Sharing and Disclosure</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">We do not sell your personal information. We may share your data with:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li><strong class="text-gray-300">Service providers:</strong> Couriers who deliver your order, our email provider, and our web host &mdash; only what they need to do their job</li>
                        <li><strong class="text-gray-300">Legal requirements:</strong> When required by law, subpoena, or government request</li>
                        <li><strong class="text-gray-300">Business transfers:</strong> In the event of a merger, acquisition, or sale of assets</li>
                        <li><strong class="text-gray-300">With your consent:</strong> When you have given us explicit permission to share your information</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">5. Data Security</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">We protect your personal information with measures including:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li>Encrypted connections (HTTPS) between your browser and our website</li>
                        <li>Passwords stored as one-way hashes, never in plain text</li>
                        <li>Limits on repeated sign-in attempts</li>
                        <li>Access to customer data restricted to authorised staff accounts</li>
                    </ul>
                    <p class="text-gray-400 leading-relaxed mt-4">
                        While we strive to protect your information, no method of electronic transmission or storage is 100% secure. We cannot guarantee absolute security but will notify you promptly in the event of a data breach.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">6. Your Rights and Choices</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">Depending on where you live (for example under Kenya&rsquo;s Data Protection Act, 2019 or the EU GDPR), you may have the following rights:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li><strong class="text-gray-300">Access:</strong> Request a copy of the personal data we hold about you</li>
                        <li><strong class="text-gray-300">Correction:</strong> Request correction of inaccurate or incomplete information</li>
                        <li><strong class="text-gray-300">Deletion:</strong> Request deletion of your personal data (subject to legal obligations)</li>
                        <li><strong class="text-gray-300">Opt-out:</strong> Unsubscribe from marketing emails at any time via the link in any email</li>
                        <li><strong class="text-gray-300">Data portability:</strong> Request your data in a structured, machine-readable format</li>
                        <li><strong class="text-gray-300">Restrict processing:</strong> Request limitation on how we process your data</li>
                    </ul>
                    <p class="text-gray-400 leading-relaxed mt-4">
                        To exercise any of these rights, please contact us at <a href="mailto:<?= e(setting('store_email')) ?>" class="text-neon-blue hover:underline"><?= e(setting('store_email')) ?></a>.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">7. Data Retention</h2>
                    <p class="text-gray-400 leading-relaxed">
                        We retain your personal information for as long as necessary to fulfill the purposes outlined in this policy, comply with legal obligations, resolve disputes, and enforce our agreements. Order and transaction data is retained for a minimum of 7 years for tax and accounting purposes. You may request deletion of your account and associated data at any time.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">8. Third-Party Links</h2>
                    <p class="text-gray-400 leading-relaxed">
                        Our website may contain links to third-party websites or services. We are not responsible for the privacy practices of these external sites. We encourage you to read the privacy policies of any third-party services you interact with.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">9. Children&rsquo;s Privacy</h2>
                    <p class="text-gray-400 leading-relaxed">
                        Our services are not directed to individuals under the age of 16. We do not knowingly collect personal information from children. If we become aware that we have collected data from a child under 16, we will take steps to delete that information promptly.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">10. International Data Transfers</h2>
                    <p class="text-gray-400 leading-relaxed">
                        Our website host or email provider may store data on servers in other countries. When that happens, we use providers that apply appropriate safeguards for personal data.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">11. Changes to This Policy</h2>
                    <p class="text-gray-400 leading-relaxed">
                        We may update this Privacy Policy from time to time. When we make changes, we will update the &ldquo;Last updated&rdquo; date at the top of this page and, for significant changes, notify you via email or a prominent notice on our website. Your continued use of our services after changes are posted constitutes acceptance of the updated policy.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">12. Contact Us</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">If you have any questions or concerns about this Privacy Policy, you can reach us at:</p>
                    <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/30 space-y-2">
                        <p class="text-gray-300"><strong class="text-white"><?= $store ?></strong></p>
                        <p class="text-gray-400 text-sm">Email: <a href="mailto:<?= e(setting('store_email')) ?>" class="text-neon-blue hover:underline"><?= e(setting('store_email')) ?></a></p>
                        <?php if (setting('store_address')): ?><p class="text-gray-400 text-sm">Address: <?= e(setting('store_address')) ?></p><?php endif; ?>
                        <p class="text-gray-400 text-sm">We respond to privacy requests within 30 days.</p>
                    </div>
                </section>

            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
