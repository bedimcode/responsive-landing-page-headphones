<?php
/**
 * Contact Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contact Us — Beats Audio';
$pageDescription = 'Get in touch with Beats Audio support. We\'re here to help with orders, products, and general inquiries.';
$pageType = 'page';
$canonical = APP_URL . '/contact.php';

require_once __DIR__ . '/includes/header.php';
?>

    <main class="pt-32 pb-24">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="text-center mb-16">
                <span class="text-neon-blue text-sm font-semibold uppercase tracking-widest">Get in Touch</span>
                <h1 class="font-display text-4xl lg:text-5xl font-bold text-white mt-3 mb-4">Contact Us</h1>
                <p class="text-gray-400 max-w-lg mx-auto">Have a question about your order, product, or just want to say hi? We&rsquo;d love to hear from you.</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-12">
                <!-- Contact Info Cards -->
                <div class="space-y-6">
                    <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/40">
                        <div class="w-12 h-12 bg-neon-blue/10 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-mail-line text-2xl text-neon-blue"></i>
                        </div>
                        <h3 class="text-white font-semibold mb-1">Email Us</h3>
                        <p class="text-gray-500 text-sm mb-2">We reply within 24-48 hours</p>
                        <span class="text-neon-blue text-sm">support@beatsaudio.com</span>
                    </div>
                    <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/40">
                        <div class="w-12 h-12 bg-neon-purple/10 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-phone-line text-2xl text-neon-purple"></i>
                        </div>
                        <h3 class="text-white font-semibold mb-1">Call Us</h3>
                        <p class="text-gray-500 text-sm mb-2">Mon-Fri, 9 AM &ndash; 6 PM PST</p>
                        <span class="text-neon-purple text-sm">+1 (800) BEATS-03</span>
                    </div>
                    <div class="p-6 rounded-2xl border border-white/5 bg-dark-900/40">
                        <div class="w-12 h-12 bg-neon-pink/10 rounded-xl flex items-center justify-center mb-4">
                            <i class="ri-map-pin-line text-2xl text-neon-pink"></i>
                        </div>
                        <h3 class="text-white font-semibold mb-1">Visit Us</h3>
                        <p class="text-gray-500 text-sm">8600 Beats Blvd<br>Culver City, CA 90232, USA</p>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="lg:col-span-2">
                    <form id="contact-form" class="space-y-6" action="<?= e($basePath) ?>/api/contact.php" method="POST">
                        <?= csrfField() ?>
                        <div class="grid sm:grid-cols-2 gap-6">
                            <div>
                                <label for="contact-name" class="block text-sm font-medium text-gray-400 mb-2">Your Name</label>
                                <input type="text" id="contact-name" name="name" required minlength="2"
                                    class="w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                                    placeholder="John Doe">
                            </div>
                            <div>
                                <label for="contact-email" class="block text-sm font-medium text-gray-400 mb-2">Email Address</label>
                                <input type="email" id="contact-email" name="email" required
                                    class="w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors"
                                    placeholder="you@example.com">
                            </div>
                        </div>
                        <div>
                            <label for="contact-subject" class="block text-sm font-medium text-gray-400 mb-2">Subject</label>
                            <select id="contact-subject" name="subject" required
                                class="w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white focus:border-neon-blue/50 focus:outline-none transition-colors">
                                <option value="" class="bg-dark-900">Select a topic</option>
                                <option value="Order Support" class="bg-dark-900">Order Support</option>
                                <option value="Product Question" class="bg-dark-900">Product Question</option>
                                <option value="Returns & Refunds" class="bg-dark-900">Returns &amp; Refunds</option>
                                <option value="Technical Issue" class="bg-dark-900">Technical Issue</option>
                                <option value="General Inquiry" class="bg-dark-900">General Inquiry</option>
                            </select>
                        </div>
                        <div>
                            <label for="contact-message" class="block text-sm font-medium text-gray-400 mb-2">Message</label>
                            <textarea id="contact-message" name="message" rows="6" required minlength="10"
                                class="w-full px-5 py-3.5 bg-dark-900/60 border border-white/10 rounded-xl text-white placeholder-gray-600 focus:border-neon-blue/50 focus:outline-none transition-colors resize-none"
                                placeholder="Tell us how we can help..."></textarea>
                        </div>

                        <div id="contact-result" class="hidden p-4 rounded-2xl text-sm"></div>

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

        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Sending...';

        try {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken || '' },
                body: JSON.stringify(data)
            });
            const json = await res.json();

            result.classList.remove('hidden', 'bg-red-500/10', 'border-red-500/20', 'text-red-400', 'bg-green-500/10', 'border-green-500/20', 'text-green-400');

            if (json.success) {
                result.classList.add('bg-green-500/10', 'border', 'border-green-500/20', 'text-green-400');
                result.innerHTML = '<i class="ri-check-line"></i> ' + json.message;
                form.reset();
            } else {
                result.classList.add('bg-red-500/10', 'border', 'border-red-500/20', 'text-red-400');
                result.innerHTML = '<i class="ri-error-warning-line"></i> ' + json.message;
            }
        } catch (err) {
            result.classList.remove('hidden');
            result.classList.add('bg-red-500/10', 'border', 'border-red-500/20', 'text-red-400');
            result.textContent = 'Network error. Please try again.';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-send-plane-line"></i> Send Message';
        }
    });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
