<?php
/**
 * Cookie Policy
 */
$pageTitle = 'Cookie Policy — Beats Audio';
$pageDescription = 'Cookie Policy — Beats Audio. Learn about how we use cookies and similar technologies on our website.';
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
                <h1 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3 mb-4">Cookie Policy</h1>
                <p class="text-gray-500">Last updated: March 22, 2026</p>
            </div>

            <!-- Content -->
            <div class="prose-legal space-y-12">

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">1. What Are Cookies?</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        Cookies are small text files placed on your device (computer, tablet, or smartphone) when you visit a website. They are widely used to make websites work efficiently, provide a better browsing experience, and give website operators useful information.
                    </p>
                    <p class="text-gray-400 leading-relaxed">
                        Cookies can be &ldquo;session cookies&rdquo; (which expire when you close your browser) or &ldquo;persistent cookies&rdquo; (which remain on your device for a set period or until you delete them). This Cookie Policy explains how Beats Audio (&ldquo;we,&rdquo; &ldquo;our,&rdquo; or &ldquo;us&rdquo;) uses cookies and similar technologies.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">2. How We Use Cookies</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">We use cookies for the following purposes:</p>

                    <div class="space-y-6">
                        <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/30">
                            <h3 class="text-lg font-semibold text-white mb-2 flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg bg-green-500/10 flex items-center justify-center text-green-400"><i class="ri-shield-check-line"></i></span>
                                Strictly Necessary Cookies
                            </h3>
                            <p class="text-gray-400 leading-relaxed text-sm">
                                These cookies are essential for the website to function and cannot be switched off. They are set in response to actions you take, such as setting your privacy preferences, logging in, or filling in forms. Without these cookies, our site would not work properly.
                            </p>
                            <div class="mt-3">
                                <span class="text-xs px-2 py-1 rounded bg-green-500/10 text-green-400 font-medium">Always Active</span>
                            </div>
                        </div>

                        <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/30">
                            <h3 class="text-lg font-semibold text-white mb-2 flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400"><i class="ri-bar-chart-line"></i></span>
                                Performance &amp; Analytics Cookies
                            </h3>
                            <p class="text-gray-400 leading-relaxed text-sm">
                                These cookies collect information about how visitors use our website, such as which pages are visited most often and if visitors encounter error messages. This data helps us improve website performance and user experience. All information these cookies collect is aggregated and anonymous.
                            </p>
                        </div>

                        <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/30">
                            <h3 class="text-lg font-semibold text-white mb-2 flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center text-purple-400"><i class="ri-settings-3-line"></i></span>
                                Functional Cookies
                            </h3>
                            <p class="text-gray-400 leading-relaxed text-sm">
                                These cookies enable enhanced functionality and personalization, such as remembering your preferences, language selections, and cart contents. They may be set by us or by third-party services we have added to our pages. If you do not allow these cookies, some or all of these services may not function properly.
                            </p>
                        </div>

                        <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/30">
                            <h3 class="text-lg font-semibold text-white mb-2 flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg bg-pink-500/10 flex items-center justify-center text-pink-400"><i class="ri-advertisement-line"></i></span>
                                Targeting &amp; Advertising Cookies
                            </h3>
                            <p class="text-gray-400 leading-relaxed text-sm">
                                These cookies may be set through our site by our advertising partners. They may be used to build a profile of your interests and show you relevant ads on other sites. They do not directly store personal information but are based on uniquely identifying your browser and device.
                            </p>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">3. Cookies We Use</h2>
                    <p class="text-gray-400 leading-relaxed mb-6">Below is a list of the main cookies used on our website:</p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="text-white font-semibold py-3 pr-4">Cookie Name</th>
                                    <th class="text-white font-semibold py-3 pr-4">Type</th>
                                    <th class="text-white font-semibold py-3 pr-4">Duration</th>
                                    <th class="text-white font-semibold py-3">Purpose</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-400">
                                <tr class="border-b border-white/5">
                                    <td class="py-3 pr-4 font-mono text-xs text-neon-blue">PHPSESSID</td>
                                    <td class="py-3 pr-4">Necessary</td>
                                    <td class="py-3 pr-4">Session</td>
                                    <td class="py-3">Maintains your session for cart and authentication</td>
                                </tr>
                                <tr class="border-b border-white/5">
                                    <td class="py-3 pr-4 font-mono text-xs text-neon-blue">beats3_cart</td>
                                    <td class="py-3 pr-4">Functional</td>
                                    <td class="py-3 pr-4">Persistent</td>
                                    <td class="py-3">Stores your shopping cart contents via localStorage</td>
                                </tr>
                                <tr class="border-b border-white/5">
                                    <td class="py-3 pr-4 font-mono text-xs text-neon-blue">beats3_countdown</td>
                                    <td class="py-3 pr-4">Functional</td>
                                    <td class="py-3 pr-4">Session</td>
                                    <td class="py-3">Persists discount countdown timer state</td>
                                </tr>
                                <tr class="border-b border-white/5">
                                    <td class="py-3 pr-4 font-mono text-xs text-neon-blue">_ga / _gid</td>
                                    <td class="py-3 pr-4">Analytics</td>
                                    <td class="py-3 pr-4">2 years / 24 hrs</td>
                                    <td class="py-3">Google Analytics &mdash; tracks anonymous usage data</td>
                                </tr>
                                <tr class="border-b border-white/5">
                                    <td class="py-3 pr-4 font-mono text-xs text-neon-blue">cookie_consent</td>
                                    <td class="py-3 pr-4">Necessary</td>
                                    <td class="py-3 pr-4">1 year</td>
                                    <td class="py-3">Remembers your cookie consent preferences</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">4. Third-Party Cookies</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        Some cookies are placed by third-party services that appear on our pages. We do not control the cookies set by these third parties. The major third-party services we use include:
                    </p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li><strong class="text-gray-300">Google Analytics:</strong> Web analytics service for measuring site traffic and usage patterns</li>
                        <li><strong class="text-gray-300">Google Fonts:</strong> Font delivery service for rendering typography</li>
                        <li><strong class="text-gray-300">Tailwind CSS CDN:</strong> Utility CSS delivery via CDN</li>
                        <li><strong class="text-gray-300">Payment processors:</strong> Secure payment services (Visa, Mastercard, PayPal, Apple Pay)</li>
                    </ul>
                    <p class="text-gray-400 leading-relaxed mt-4">
                        Each third party has its own privacy and cookie policy. We encourage you to review their policies for more information.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">5. Managing Your Cookies</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        You have the right to decide whether to accept or reject cookies. You can manage your cookie preferences in the following ways:
                    </p>

                    <h3 class="text-lg font-semibold text-white mb-3">5.1 Browser Settings</h3>
                    <p class="text-gray-400 leading-relaxed mb-4">
                        Most web browsers allow you to control cookies through their settings. You can typically find these settings in the &ldquo;Options&rdquo; or &ldquo;Preferences&rdquo; menu of your browser. The following links may help:
                    </p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4 mb-6">
                        <li>Chrome: Settings &gt; Privacy and Security &gt; Cookies</li>
                        <li>Firefox: Settings &gt; Privacy &amp; Security &gt; Cookies</li>
                        <li>Safari: Preferences &gt; Privacy</li>
                        <li>Edge: Settings &gt; Cookies and Site Permissions</li>
                    </ul>

                    <h3 class="text-lg font-semibold text-white mb-3">5.2 Opt-Out Tools</h3>
                    <p class="text-gray-400 leading-relaxed">
                        You can opt out of Google Analytics tracking by installing the Google Analytics Opt-out Browser Add-on. For advertising cookies, you can visit the Digital Advertising Alliance&rsquo;s opt-out page or the Network Advertising Initiative&rsquo;s opt-out page.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">6. Local Storage and Similar Technologies</h2>
                    <p class="text-gray-400 leading-relaxed">
                        In addition to cookies, we use localStorage and sessionStorage (HTML5 Web Storage) to store information on your device. These technologies serve similar purposes to cookies but can store larger amounts of data and are not automatically sent to the server with each request. For example, we use localStorage to persist your shopping cart contents and sessionStorage for the promotional countdown timer. You can clear this data through your browser&rsquo;s developer tools or settings.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">7. Impact of Disabling Cookies</h2>
                    <p class="text-gray-400 leading-relaxed">
                        If you choose to disable or block cookies, some features of our website may not function properly. For example, you may not be able to add items to your cart, complete a purchase, or have your preferences remembered. Strictly necessary cookies cannot be disabled as they are essential for the basic operation of the site.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">8. Changes to This Policy</h2>
                    <p class="text-gray-400 leading-relaxed">
                        We may update this Cookie Policy from time to time to reflect changes in our practices or for other operational, legal, or regulatory reasons. When we make changes, we will update the &ldquo;Last updated&rdquo; date at the top of this page. We encourage you to review this policy periodically.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">9. Contact Us</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">If you have questions about our use of cookies or this Cookie Policy, please contact us:</p>
                    <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/30 space-y-2">
                        <p class="text-gray-300"><strong class="text-white">Beats Audio &mdash; Privacy Team</strong></p>
                        <p class="text-gray-400 text-sm">Email: <span class="text-neon-blue">privacy@beatsaudio.com</span></p>
                        <p class="text-gray-400 text-sm">Address: 8600 Beats Blvd, Culver City, CA 90232, USA</p>
                    </div>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">10. Related Policies</h2>
                    <p class="text-gray-400 leading-relaxed">
                        For more information about how we handle your data, please also review our
                        <a href="<?= e($basePath) ?>/legal/privacy-policy.php" class="text-neon-blue hover:underline">Privacy Policy</a> and
                        <a href="<?= e($basePath) ?>/legal/terms-of-service.php" class="text-neon-blue hover:underline">Terms of Service</a>.
                    </p>
                </section>

            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
