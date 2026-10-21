<?php
/**
 * Cookie Policy
 * Lists the cookies and browser storage this site really uses. Update it if you
 * add analytics, chat widgets or ad pixels.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Cookie Policy — ' . storeName();
$pageDescription = 'How ' . storeName() . ' uses cookies and similar technologies.';
$pageType = 'page';

require_once __DIR__ . '/../includes/header.php';
$store = e(storeName());
$sessionName = e(session_name());
?>

    <main class="pt-32 pb-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="mb-16">
                <a href="<?= e($basePath) ?>/" class="inline-flex items-center gap-2 text-gray-500 hover:text-neon-blue text-sm mb-6 transition-colors"><i class="ri-arrow-left-line"></i> Back to Home</a>
                <span class="block text-neon-blue text-sm font-semibold uppercase tracking-widest">Legal</span>
                <h1 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3 mb-4">Cookie Policy</h1>
                <p class="text-gray-500">Last updated: October 20, 2026</p>
            </div>

            <!-- Content -->
            <div class="prose-legal space-y-12">

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">1. What Are Cookies?</h2>
                    <p class="text-gray-400 leading-relaxed">
                        Cookies are small text files a website stores in your browser. This policy explains which cookies and similar technologies <?= $store ?> (&ldquo;we&rdquo;) uses and why. We keep this list short on purpose: we do not use advertising or analytics cookies.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">2. What We Store</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-gray-400">
                            <thead>
                                <tr class="border-b border-white/10 text-left text-white">
                                    <th class="py-3 pr-4 font-semibold">Name</th>
                                    <th class="py-3 pr-4 font-semibold">Type</th>
                                    <th class="py-3 pr-4 font-semibold">Duration</th>
                                    <th class="py-3 font-semibold">Purpose</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr>
                                    <td class="py-3 pr-4 font-mono text-xs text-neon-blue"><?= $sessionName ?></td>
                                    <td class="py-3 pr-4">Essential cookie</td>
                                    <td class="py-3 pr-4">Until you close the browser</td>
                                    <td class="py-3">Keeps you signed in, remembers your cart and protects forms against forgery</td>
                                </tr>
                                <tr>
                                    <td class="py-3 pr-4 font-mono text-xs text-neon-blue">cookie_consent</td>
                                    <td class="py-3 pr-4">Browser storage</td>
                                    <td class="py-3 pr-4">Until you clear it</td>
                                    <td class="py-3">Remembers that you accepted or rejected this notice</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-gray-400 leading-relaxed mt-4">
                        The essential cookie is needed for the shop to work, so it is set even if you click &ldquo;Reject&rdquo;. It contains only a random ID; your cart and account details stay on our server.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">3. Third-Party Services</h2>
                    <p class="text-gray-400 leading-relaxed mb-4">Our pages load fonts, icons and animation code from these providers. Your browser connects to them directly, so they receive your IP address, but they do not set cookies for us:</p>
                    <ul class="list-disc list-inside text-gray-400 space-y-2 ml-4">
                        <li><strong class="text-gray-300">Google Fonts</strong> &mdash; typefaces</li>
                        <li><strong class="text-gray-300">jsDelivr, cdnjs and unpkg</strong> &mdash; icon font and animation libraries</li>
                        <?php if (whatsappLink()): ?><li><strong class="text-gray-300">WhatsApp</strong> &mdash; only if you click the chat button</li><?php endif; ?>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">4. Managing Cookies</h2>
                    <p class="text-gray-400 leading-relaxed">
                        You can delete or block cookies in your browser settings. If you block the essential cookie you will not be able to sign in or keep items in your cart.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">5. Changes to This Policy</h2>
                    <p class="text-gray-400 leading-relaxed">
                        If we add new cookies (for example for analytics), we will update this page and the &ldquo;Last updated&rdquo; date first.
                    </p>
                </section>

                <section>
                    <h2 class="text-xl font-display font-bold text-white mb-4">6. Contact Us</h2>
                    <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/30 space-y-2">
                        <p class="text-gray-300"><strong class="text-white"><?= $store ?></strong></p>
                        <p class="text-gray-400 text-sm">Email: <a href="mailto:<?= e(setting('store_email')) ?>" class="text-neon-blue hover:underline"><?= e(setting('store_email')) ?></a></p>
                        <?php if (setting('store_address')): ?><p class="text-gray-400 text-sm">Address: <?= e(setting('store_address')) ?></p><?php endif; ?>
                    </div>
                </section>

            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
