    </main>

    <!-- ============ FOOTER ============ -->
    <footer class="border-t border-white/5 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                <div>
                    <a href="<?= e($basePath) ?>/" class="inline-block mb-6"><img src="<?= e($basePath) ?>/assets/img/logo.png" alt="Beats Audio Logo" class="h-10"></a>
                    <p class="text-gray-500 text-sm leading-relaxed mb-6">Studio-grade wireless headphones engineered for those who demand the best in sound, comfort, and style.</p>
                    <div class="flex gap-4">
                        <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-400 hover:bg-neon-blue/10 hover:text-neon-blue transition-all" aria-label="Facebook"><i class="ri-facebook-fill"></i></a>
                        <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-400 hover:bg-neon-pink/10 hover:text-neon-pink transition-all" aria-label="Instagram"><i class="ri-instagram-line"></i></a>
                        <a href="https://twitter.com/" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-400 hover:bg-neon-blue/10 hover:text-neon-blue transition-all" aria-label="Twitter"><i class="ri-twitter-x-line"></i></a>
                        <a href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-400 hover:bg-red-500/10 hover:text-red-400 transition-all" aria-label="YouTube"><i class="ri-youtube-line"></i></a>
                    </div>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-6">Products</h4>
                    <ul class="space-y-3">
                        <li><a href="<?= e($basePath) ?>/shop.php" class="text-gray-500 hover:text-neon-blue transition-colors text-sm">All Products</a></li>
                        <li><a href="<?= e($basePath) ?>/#products" class="text-gray-500 hover:text-neon-blue transition-colors text-sm">Headphones</a></li>
                        <li><span class="text-gray-600 text-sm cursor-default" title="Coming Soon">Earphones <span class="text-xs text-gray-700">(Coming Soon)</span></span></li>
                        <li><span class="text-gray-600 text-sm cursor-default" title="Coming Soon">Accessories <span class="text-xs text-gray-700">(Coming Soon)</span></span></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-6">Support</h4>
                    <ul class="space-y-3">
                        <li><a href="<?= e($basePath) ?>/#faq" class="text-gray-500 hover:text-neon-blue transition-colors text-sm">FAQs</a></li>
                        <li><a href="<?= e($basePath) ?>/contact.php" class="text-gray-500 hover:text-neon-blue transition-colors text-sm">Product Help</a></li>
                        <li><a href="<?= e($basePath) ?>/contact.php" class="text-gray-500 hover:text-neon-blue transition-colors text-sm">Register Product</a></li>
                        <li><a href="<?= e($basePath) ?>/#newsletter" class="text-gray-500 hover:text-neon-blue transition-colors text-sm">Updates</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-6">We Accept</h4>
                    <div class="flex flex-wrap gap-3 mb-6">
                        <div class="px-3 py-2 bg-white/5 rounded-lg text-gray-400 text-xs font-medium"><i class="ri-visa-line text-lg"></i></div>
                        <div class="px-3 py-2 bg-white/5 rounded-lg text-gray-400 text-xs font-medium"><i class="ri-mastercard-line text-lg"></i></div>
                        <div class="px-3 py-2 bg-white/5 rounded-lg text-gray-400 text-xs font-medium"><i class="ri-paypal-line text-lg"></i></div>
                        <div class="px-3 py-2 bg-white/5 rounded-lg text-gray-400 text-xs font-medium"><i class="ri-apple-line text-lg"></i></div>
                    </div>
                    <p class="text-gray-500 text-xs">All transactions are secure and encrypted.</p>
                </div>
            </div>
            <div class="border-t border-white/5 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-gray-600 text-sm">&copy; <?= date('Y') ?> Beats Audio. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="<?= e($basePath) ?>/legal/privacy-policy.php" class="text-gray-600 hover:text-gray-400 text-sm transition-colors">Privacy Policy</a>
                    <a href="<?= e($basePath) ?>/legal/terms-of-service.php" class="text-gray-600 hover:text-gray-400 text-sm transition-colors">Terms of Service</a>
                    <a href="<?= e($basePath) ?>/legal/cookie-policy.php" class="text-gray-600 hover:text-gray-400 text-sm transition-colors">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ============ COOKIE CONSENT BANNER ============ -->
    <div id="cookie-banner" class="fixed bottom-0 left-0 right-0 z-[200] p-4 bg-dark-900/95 backdrop-blur-2xl border-t border-white/5 translate-y-full transition-transform duration-500" style="display:none;">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-gray-400 text-sm text-center sm:text-left">We use cookies to enhance your experience. By continuing, you agree to our <a href="<?= e($basePath) ?>/legal/cookie-policy.php" class="text-neon-blue hover:underline">Cookie Policy</a>.</p>
            <div class="flex gap-3">
                <button id="cookie-reject" class="px-4 py-2 text-sm text-gray-400 border border-white/10 rounded-xl hover:bg-white/5 transition-colors">Reject</button>
                <button id="cookie-accept" class="px-4 py-2 text-sm text-dark-950 bg-neon-blue rounded-xl font-medium hover:bg-neon-blue/90 transition-colors">Accept</button>
            </div>
        </div>
    </div>

    <!-- ============ BACK TO TOP ============ -->
    <a href="#" id="scroll-up" class="fixed bottom-6 right-6 w-12 h-12 bg-dark-900/80 backdrop-blur-xl border border-white/10 rounded-full flex items-center justify-center text-neon-blue z-[140] opacity-0 translate-y-10 pointer-events-none transition-all duration-500 hover:border-neon-blue/30 hover:scale-110" aria-label="Back to top">
        <i class="ri-arrow-up-s-line text-xl"></i>
    </a>

    <!-- ============ SCRIPTS ============ -->
    <?php if ($includeAnimations): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/Observer.min.js"></script>
    <script src="https://unpkg.com/lenis@1.1.18/dist/lenis.min.js"></script>
    <?php endif; ?>
    <script src="<?= e($basePath) ?>/assets/js/cart.js"></script>
    <?php if ($includeAnimations): ?>
    <script src="<?= e($basePath) ?>/assets/js/main.js"></script>
    <?php endif; ?>

    <!-- Cookie consent logic -->
    <script>
    (function() {
        const banner = document.getElementById('cookie-banner');
        if (!banner) return;
        if (!localStorage.getItem('cookie_consent')) {
            banner.style.display = '';
            setTimeout(() => banner.classList.remove('translate-y-full'), 100);
        }
        document.getElementById('cookie-accept')?.addEventListener('click', () => {
            localStorage.setItem('cookie_consent', 'accepted');
            banner.classList.add('translate-y-full');
            setTimeout(() => banner.style.display = 'none', 500);
        });
        document.getElementById('cookie-reject')?.addEventListener('click', () => {
            localStorage.setItem('cookie_consent', 'rejected');
            banner.classList.add('translate-y-full');
            setTimeout(() => banner.style.display = 'none', 500);
        });
    })();
    </script>

    <!-- Simple navigation for non-home pages (no GSAP) -->
    <?php if (!$includeAnimations): ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Mobile nav
        const navMenu = document.getElementById('nav-menu');
        document.getElementById('nav-toggle')?.addEventListener('click', () => navMenu?.classList.add('open'));
        document.getElementById('nav-close')?.addEventListener('click', () => navMenu?.classList.remove('open'));
        document.querySelectorAll('.nav-link').forEach(l => l.addEventListener('click', () => navMenu?.classList.remove('open')));

        // Header scroll
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 80) header?.classList.add('scrolled');
            else header?.classList.remove('scrolled');
        });

        // Scroll progress
        const progressBar = document.getElementById('scroll-progress');
        window.addEventListener('scroll', () => {
            const h = document.documentElement.scrollHeight - window.innerHeight;
            if (progressBar && h > 0) progressBar.style.width = (window.scrollY / h * 100) + '%';
        });

        // Back to top
        const scrollUp = document.getElementById('scroll-up');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 400) scrollUp?.classList.add('visible');
            else scrollUp?.classList.remove('visible');
        });
        scrollUp?.addEventListener('click', (e) => { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });

        // Cart init
        if (typeof Cart !== 'undefined') Cart.init();

        // Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (typeof Cart !== 'undefined') Cart.closeDrawer();
                navMenu?.classList.remove('open');
            }
        });
    });
    </script>
    <?php endif; ?>
</body>
</html>
