<?php
/**
 * Contact Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contact Us — ' . storeName();
$pageDescription = 'Get in touch with ' . storeName() . '. We\'re here to help with orders, products and general questions.';
$pageType = 'page';
$canonical = absoluteUrl('/contact.php');

require_once __DIR__ . '/includes/header.php';

// Pre-fill for signed-in customers
$prefill = ['name' => '', 'email' => ''];
if (isLoggedIn()) {
    try {
        $stmt = getDB()->prepare('SELECT name, email FROM users WHERE id = ?');
        $stmt->execute([currentUserId()]);
        $prefill = $stmt->fetch() ?: $prefill;
    } catch (Exception $e) {}
}
$field = 'w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors';
$wa = whatsappLink('Hi ' . storeName() . ', I have a question');
?>

    <main class="pt-32 pb-24">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="text-center mb-16">
                <span class="text-neon-blue text-sm font-semibold uppercase tracking-widest">Get in Touch</span>
                <h1 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3 mb-4">Contact Us</h1>
                <p class="text-gray-400 max-w-lg mx-auto">Have a question about your order, a product, or anything else? We&rsquo;d love to hear from you.</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-12">
                <!-- Contact Info Cards (from Admin → Settings → General) -->
                <div class="space-y-6">
                    <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/40">
                        <div class="w-12 h-12 bg-neon-blue/10 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-mail-line text-2xl text-neon-blue"></i>
                        </div>
                        <h2 class="text-white font-semibold mb-1">Email Us</h2>
                        <p class="text-gray-500 text-sm mb-2">We reply within 1&ndash;2 business days</p>
                        <a href="mailto:<?= e(setting('store_email')) ?>" class="text-neon-blue text-sm hover:underline break-all"><?= e(setting('store_email')) ?></a>
                    </div>
                    <?php if (setting('store_phone') || $wa): ?>
                    <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/40">
                        <div class="w-12 h-12 bg-neon-purple/10 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-phone-line text-2xl text-neon-purple"></i>
                        </div>
                        <h2 class="text-white font-semibold mb-1">Call or Chat</h2>
                        <?php if (setting('support_hours')): ?><p class="text-gray-500 text-sm mb-2"><?= e(setting('support_hours')) ?></p><?php endif; ?>
                        <?php if (setting('store_phone')): ?>
                        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('store_phone'))) ?>" class="block text-neon-purple text-sm hover:underline"><?= e(setting('store_phone')) ?></a>
                        <?php endif; ?>
                        <?php if ($wa): ?>
                        <a href="<?= e($wa) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 mt-2 text-green-400 text-sm hover:underline"><i class="ri-whatsapp-line"></i> Chat on WhatsApp</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (setting('store_address')): ?>
                    <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/40">
                        <div class="w-12 h-12 bg-neon-pink/10 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-map-pin-line text-2xl text-neon-pink"></i>
                        </div>
                        <h2 class="text-white font-semibold mb-1">Visit Us</h2>
                        <p class="text-gray-500 text-sm"><?= e(setting('store_address')) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Contact Form -->
                <div class="lg:col-span-2">
                    <form id="contact-form" class="space-y-6" action="<?= e($basePath) ?>/api/contact.php" method="POST">
                        <?= csrfField() ?>
                        <!-- Spam trap: hidden from people, filled in by bots -->
                        <div class="hidden" aria-hidden="true">
                            <label for="contact-website">Leave this empty</label>
                            <input type="text" id="contact-website" name="website" tabindex="-1" autocomplete="off">
                        </div>
                        <div class="grid sm:grid-cols-2 gap-6">
                            <div>
                                <label for="contact-name" class="block text-sm font-medium text-gray-400 mb-2">Your Name</label>
                                <input type="text" id="contact-name" name="name" required minlength="2" maxlength="100" autocomplete="name" class="<?= $field ?>" value="<?= e($prefill['name']) ?>">
                            </div>
                            <div>
                                <label for="contact-email" class="block text-sm font-medium text-gray-400 mb-2">Email Address</label>
                                <input type="email" id="contact-email" name="email" required maxlength="255" autocomplete="email" class="<?= $field ?>" value="<?= e($prefill['email']) ?>" placeholder="you@example.com">
                            </div>
                        </div>
                        <div>
                            <label for="contact-subject" class="block text-sm font-medium text-gray-400 mb-2">Subject</label>
                            <select id="contact-subject" name="subject" required class="<?= $field ?>">
                                <option value="" class="bg-dark-900">Select a topic</option>
                                <option value="Order Support" class="bg-dark-900">Order Support</option>
                                <option value="Product Question" class="bg-dark-900">Product Question</option>
                                <option value="Returns & Refunds" class="bg-dark-900">Returns &amp; Refunds</option>
                                <option value="Payment Help" class="bg-dark-900">Payment Help</option>
                                <option value="General Inquiry" class="bg-dark-900">General Inquiry</option>
                            </select>
                        </div>
                        <div>
                            <label for="contact-message" class="block text-sm font-medium text-gray-400 mb-2">Message</label>
                            <textarea id="contact-message" name="message" rows="6" required minlength="10" maxlength="5000" class="<?= $field ?> resize-none" placeholder="Tell us how we can help. Include your order number if it is about an order."></textarea>
                        </div>

                        <div id="contact-result" class="hidden p-4 rounded-2xl text-sm" role="alert"></div>

                        <button type="submit" id="contact-submit" class="px-8 py-4 bg-gradient-to-r from-neon-blue to-neon-purple rounded-xl font-bold text-dark-950 text-lg hover:scale-[1.02] transition-transform flex items-center gap-3">
                            <i class="ri-send-plane-line"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.getElementById('contact-form')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('contact-submit');
        const result = document.getElementById('contact-result');
        const form = this;

        function show(message, ok) {
            result.textContent = message;
            result.className = 'p-4 rounded-2xl text-sm border ' + (ok ? 'bg-green-500/10 border-green-500/20 text-green-400' : 'bg-red-500/10 border-red-500/20 text-red-400');
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Sending...';

        try {
            const data = Object.fromEntries(new FormData(form));
            const res = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            show(json.message || (json.success ? 'Message sent.' : 'Something went wrong.'), json.success);
            if (json.success) form.reset();
        } catch (err) {
            show('Network error. Please try again.', false);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-send-plane-line"></i> Send Message';
        }
    });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
