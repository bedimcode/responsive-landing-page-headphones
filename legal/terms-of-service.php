<?php
/**
 * Terms of Service
 */
$pageTitle = 'Terms of Service — Beats Audio';
$pageDescription = 'Terms of Service — Beats Audio. Read the terms governing your use of our website and services.';
$pageType = 'page';
$noIndex = true;

require_once __DIR__ . '/../includes/header.php';
?>

    <main class="pt-32 pb-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="mb-16">
                <a href="<?= e($basePath) ?>/" class="inline-flex items-center gap-2 text-gray-500 hover:text-neon-blue text-sm mb-6 transition-colors"><i class="ri-arrow-left-line"></i> Back to Home</a>
                <span class="block text-neon-blue text-sm font-semibold uppercase tracking-widest">Legal</span>
                <h1 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3 mb-4">Terms of Service</h1>
                <p class="text-gray-500">Last updated: March 22, 2026</p>
            </div>

            <!-- Content -->
            <div class="prose-legal space-y-12">

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">1. Acceptance of Terms</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        By accessing and using the Beats Audio website and services (&ldquo;Services&rdquo;), you accept and agree to be bound by the terms and conditions outlined in these Terms of Service (&ldquo;Terms&rdquo;). If you do not agree to these Terms, you must not access or use our Services.
                    </p>
                    <p class="text-gray-400 leading-relaxed">
                        We reserve the right to update or modify these Terms at any time without prior notice. Your continued use of the Services following any changes constitutes your acceptance of the revised Terms.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">2. Eligibility</h2>
                    <p class="text-gray-400 leading-relaxed">
                        You must be at least 16 years of age to use our Services. By using our website and placing an order, you represent and warrant that you are at least 16 years old and have the legal capacity to enter into a binding contract. If you are under 18, you may use our Services only with the involvement of a parent or guardian.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">3. Account Registration</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">To access certain features, you may need to create an account. When creating an account, you agree to:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li>Provide accurate, current, and complete information</li>
                        <li>Maintain and promptly update your account information</li>
                        <li>Keep your password secure and confidential</li>
                        <li>Accept responsibility for all activities under your account</li>
                        <li>Notify us immediately of any unauthorized use</li>
                    </ul>
                    <p class="text-gray-400 leading-relaxed mt-4">
                        We reserve the right to suspend or terminate your account at our discretion if we suspect any violation of these Terms.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">4. Products and Pricing</h2>
                    <h3 class="text-lg font-semibold text-white mb-3">4.1 Product Information</h3>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        We make every effort to display our products accurately, including descriptions, images, and specifications. However, we do not warrant that product descriptions, colors, or other content on the website are accurate, complete, or error-free.
                    </p>
                    <h3 class="text-lg font-semibold text-white mb-3">4.2 Pricing</h3>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        All prices are displayed in USD and are subject to change without notice. Prices do not include applicable taxes, shipping, or handling fees unless explicitly stated. We reserve the right to correct any pricing errors and to cancel orders placed at an incorrect price.
                    </p>
                    <h3 class="text-lg font-semibold text-white mb-3">4.3 Availability</h3>
                    <p class="text-gray-400 leading-relaxed">
                        All products are subject to availability. We reserve the right to limit the quantity of any product or discontinue any product at any time without notice.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">5. Orders and Payment</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        When you place an order, you are making an offer to purchase. Order confirmation does not constitute acceptance of your order. We reserve the right to refuse or cancel any order for any reason, including but not limited to:
                    </p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li>Product or pricing errors</li>
                        <li>Suspected fraudulent activity</li>
                        <li>Product availability issues</li>
                        <li>Violations of these Terms</li>
                    </ul>
                    <p class="text-gray-400 leading-relaxed mt-4">
                        We accept major credit cards, debit cards, PayPal, and Apple Pay. All payments are processed securely through PCI DSS-compliant payment processors.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">6. Shipping and Delivery</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        Shipping times and costs vary based on your location and selected shipping method. Estimated delivery times are provided for reference only and are not guaranteed. Risk of loss and title for items purchased pass to you upon delivery of the items to the carrier.
                    </p>
                    <p class="text-gray-400 leading-relaxed">
                        We are not responsible for delays caused by carriers, customs, weather, or other events beyond our control. International orders may be subject to import duties and taxes, which are the buyer&rsquo;s responsibility.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">7. Returns and Refunds</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">We offer a 30-day return policy for unused products in their original packaging. To initiate a return:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li>Contact our support team within 30 days of delivery</li>
                        <li>Products must be unused, undamaged, and in original packaging</li>
                        <li>Proof of purchase is required</li>
                        <li>Return shipping costs are the buyer&rsquo;s responsibility unless the item is defective</li>
                    </ul>
                    <p class="text-gray-400 leading-relaxed mt-4">
                        Refunds will be processed within 10 business days of receiving the returned item. Refunds are issued to the original payment method.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">8. Intellectual Property</h2>
                    <p class="text-gray-400 leading-relaxed">
                        All content on this website &mdash; including text, graphics, logos, images, audio, video, software, and other materials &mdash; is the property of Beats Audio or its licensors and is protected by copyright, trademark, and other intellectual property laws. You may not reproduce, distribute, modify, create derivative works from, or commercially exploit any content without our prior written consent.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">9. Prohibited Conduct</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">You agree not to:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li>Use the Services for any unlawful or unauthorized purpose</li>
                        <li>Interfere with or disrupt the security, integrity, or performance of the Services</li>
                        <li>Attempt to gain unauthorized access to any part of the Services</li>
                        <li>Use automated tools (bots, scrapers, etc.) to access or interact with the Services</li>
                        <li>Upload malicious code, viruses, or harmful content</li>
                        <li>Impersonate any person or entity, or falsify your affiliation</li>
                        <li>Engage in any activity that could damage, overburden, or impair the Services</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">10. Warranty Disclaimer</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        Our hardware products are covered by a limited 1-year manufacturer&rsquo;s warranty against defects in materials and workmanship under normal use. This warranty does not cover:
                    </p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li>Damage caused by misuse, abuse, or unauthorized modifications</li>
                        <li>Normal wear and tear</li>
                        <li>Damage resulting from accidents or neglect</li>
                        <li>Products purchased from unauthorized resellers</li>
                    </ul>
                    <p class="text-gray-400 leading-relaxed mt-4">
                        THE WEBSITE AND SERVICES ARE PROVIDED &ldquo;AS IS&rdquo; AND &ldquo;AS AVAILABLE&rdquo; WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">11. Limitation of Liability</h2>
                    <p class="text-gray-400 leading-relaxed">
                        TO THE MAXIMUM EXTENT PERMITTED BY LAW, BEATS AUDIO SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES ARISING FROM YOUR USE OF OR INABILITY TO USE THE SERVICES, EVEN IF WE HAVE BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES. OUR TOTAL LIABILITY SHALL NOT EXCEED THE AMOUNT PAID BY YOU FOR THE PRODUCT OR SERVICE GIVING RISE TO THE CLAIM.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">12. Governing Law</h2>
                    <p class="text-gray-400 leading-relaxed">
                        These Terms shall be governed by and construed in accordance with the laws of the State of California, United States, without regard to its conflict of law provisions. Any disputes arising from these Terms or your use of the Services shall be resolved in the courts located in Los Angeles County, California.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">13. Contact Us</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">If you have any questions about these Terms of Service, please contact us:</p>
                    <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/30 space-y-2">
                        <p class="text-gray-300"><strong class="text-white">Beats Audio &mdash; Legal Department</strong></p>
                        <p class="text-gray-400 text-sm">Email: <span class="text-neon-blue">legal@beatsaudio.com</span></p>
                        <p class="text-gray-400 text-sm">Address: 8600 Beats Blvd, Culver City, CA 90232, USA</p>
                    </div>
                </section>

            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
