/* ============================================
   MAIN.JS — Beats 3 Premium Landing Page
   GSAP 3 + ScrollTrigger + Lenis + All Logic
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {
    // Register GSAP plugins
    gsap.registerPlugin(ScrollTrigger);

    // ==============================
    // LENIS SMOOTH SCROLL
    // ==============================
    const lenis = new Lenis({
        autoRaf: false,
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        touchMultiplier: 2,
    });

    // Single raf source: GSAP ticker drives Lenis which updates ScrollTrigger
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });
    gsap.ticker.lagSmoothing(0);

    // Smooth anchor clicks
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.querySelector(anchor.getAttribute('href'));
            if (target) lenis.scrollTo(target, { offset: -60 });
        });
    });

    // ==============================
    // INIT CART
    // ==============================
    Cart.init();

    // ==============================
    // MOBILE NAV
    // ==============================
    const navMenu = document.getElementById('nav-menu');
    const navToggle = document.getElementById('nav-toggle');
    const navClose = document.getElementById('nav-close');

    navToggle?.addEventListener('click', () => navMenu?.classList.add('open'));
    navClose?.addEventListener('click', () => navMenu?.classList.remove('open'));

    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => navMenu?.classList.remove('open'));
    });

    // ==============================
    // HEADER SCROLL EFFECT
    // ==============================
    const header = document.getElementById('header');
    ScrollTrigger.create({
        start: 'top -80',
        onUpdate: (self) => {
            if (self.direction === 1 && window.scrollY > 80) {
                header.classList.add('scrolled');
            } else if (window.scrollY <= 80) {
                header.classList.remove('scrolled');
            }
        }
    });

    // ==============================
    // SCROLL PROGRESS BAR
    // ==============================
    const progressBar = document.getElementById('scroll-progress');
    window.addEventListener('scroll', () => {
        const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrolled = (window.scrollY / scrollHeight) * 100;
        if (progressBar) progressBar.style.width = scrolled + '%';
    });

    // ==============================
    // ACTIVE NAV LINK HIGHLIGHT
    // ==============================
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');

    ScrollTrigger.create({
        start: 'top top',
        end: 'bottom bottom',
        onUpdate: () => {
            let current = '';
            sections.forEach(section => {
                const top = section.offsetTop - 120;
                if (window.scrollY >= top) current = section.getAttribute('id');
            });
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.dataset.section === current) link.classList.add('active');
            });
        }
    });

    // ==============================
    // SCROLL UP + FLOATING CTA
    // ==============================
    const scrollUp = document.getElementById('scroll-up');
    const floatingCta = document.getElementById('floating-cta');

    ScrollTrigger.create({
        start: 'top -400',
        onUpdate: () => {
            if (window.scrollY > 400) {
                scrollUp?.classList.add('visible');
                floatingCta?.classList.add('visible');
            } else {
                scrollUp?.classList.remove('visible');
                floatingCta?.classList.remove('visible');
            }
        }
    });

    // ==============================
    // HERO ANIMATIONS (GSAP Timeline)
    // ==============================
    const heroTl = gsap.timeline({ defaults: { ease: 'power3.out' } });

    heroTl
        .from('#hero-product-img', { y: 80, opacity: 0, scale: 0.8, duration: 1.2 }, 0.2)
        .from('.hero-text span:first-child', { opacity: 0, y: 20, duration: 0.6 }, 0.4)
        .from('.hero-line', { y: 60, opacity: 0, stagger: 0.15, duration: 0.8 }, 0.6)
        .from('.hero-desc', { y: 30, opacity: 0, duration: 0.7 }, 1)
        .from('.hero-colors', { y: 20, opacity: 0, duration: 0.6 }, 1.2)
        .from('.hero-cta', { y: 20, opacity: 0, duration: 0.6 }, 1.4)
        .from('.scroll-hint', { y: 20, opacity: 0, duration: 0.6 }, 1.6);

    // Floating hero image
    gsap.to('#hero-product-img', {
        y: -15,
        duration: 3,
        ease: 'sine.inOut',
        yoyo: true,
        repeat: -1
    });

    // ==============================
    // HERO COLOR SWATCHES
    // ==============================
    const heroImg = document.getElementById('hero-product-img');
    const heroAddBtn = document.getElementById('hero-add-to-cart');
    const colorSwatches = document.querySelectorAll('#hero-colors .color-swatch');

    const colorNames = {
        'black': 'Beats 3 — Black',
        'red-black': 'Beats 3 — Red Black',
        'night-black': 'Beats 3 — Night Black',
        'blue': 'Beats 3 — Blue',
        'gray': 'Beats 3 — Twilight Gray'
    };

    colorSwatches.forEach(swatch => {
        swatch.addEventListener('click', () => {
            colorSwatches.forEach(s => s.classList.remove('active'));
            swatch.classList.add('active');

            const newImg = swatch.dataset.img;
            const color = swatch.dataset.color;

            // Animate image swap
            heroImg.classList.add('switching');
            setTimeout(() => {
                heroImg.src = newImg;
                heroImg.alt = `Beats 3 Wireless Headphones in ${swatch.title}`;
                heroImg.classList.remove('switching');
            }, 300);

            // Update hero add-to-cart button data
            if (heroAddBtn) {
                heroAddBtn.dataset.id = 'beats3-' + color;
                heroAddBtn.dataset.name = colorNames[color] || 'Beats 3';
                heroAddBtn.dataset.img = newImg;
            }
        });
    });

    // ==============================
    // SPONSOR SECTION ANIMATION
    // ==============================
    gsap.from('.sponsor-item', {
        scrollTrigger: {
            trigger: '#sponsors',
            start: 'top 85%',
        },
        y: 30,
        opacity: 0,
        stagger: 0.1,
        duration: 0.8,
        ease: 'power2.out'
    });

    // ==============================
    // SPECS SECTION ANIMATIONS
    // ==============================
    gsap.from('.spec-card', {
        scrollTrigger: {
            trigger: '#specs',
            start: 'top 70%',
        },
        y: 50,
        opacity: 0,
        stagger: 0.15,
        duration: 0.8,
        ease: 'power2.out'
    });

    // Specs image parallax
    gsap.from('#specs-image-wrap img', {
        scrollTrigger: {
            trigger: '#specs-image-wrap',
            start: 'top 85%',
        },
        y: 60,
        opacity: 0,
        scale: 0.9,
        duration: 1,
        ease: 'power2.out'
    });

    // ==============================
    // COUNTER ANIMATION
    // ==============================
    document.querySelectorAll('.counter').forEach(counter => {
        const target = parseInt(counter.dataset.target);
        ScrollTrigger.create({
            trigger: counter,
            start: 'top 85%',
            once: true,
            onEnter: () => {
                gsap.to(counter, {
                    textContent: target,
                    duration: 2,
                    ease: 'power1.out',
                    snap: { textContent: 1 },
                    onUpdate: function() {
                        counter.textContent = Math.round(parseFloat(counter.textContent)).toLocaleString();
                    }
                });
            }
        });
    });

    // ==============================
    // CASE SECTION PARALLAX
    // ==============================
    gsap.from('.case-image', {
        scrollTrigger: {
            trigger: '#case',
            start: 'top 80%',
        },
        x: -60,
        opacity: 0,
        duration: 1,
        ease: 'power2.out'
    });

    gsap.from('.case-text', {
        scrollTrigger: {
            trigger: '#case',
            start: 'top 75%',
        },
        x: 60,
        opacity: 0,
        duration: 1,
        delay: 0.2,
        ease: 'power2.out'
    });

    // ==============================
    // TRUST CARDS ANIMATION
    // ==============================
    gsap.from('.trust-card', {
        scrollTrigger: {
            trigger: '.trust-card',
            start: 'top 85%',
        },
        y: 50,
        opacity: 0,
        stagger: 0.15,
        duration: 0.8,
        ease: 'power2.out'
    });

    // ==============================
    // PRODUCT CARDS ANIMATION
    // ==============================
    gsap.from('.product-card', {
        scrollTrigger: {
            trigger: '#products',
            start: 'top 70%',
        },
        y: 60,
        opacity: 0,
        stagger: 0.1,
        duration: 0.8,
        ease: 'power2.out'
    });

    // 3D Tilt on product cards (desktop only)
    if (window.matchMedia('(hover: hover)').matches) {
        document.querySelectorAll('.product-card > div').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 15;
                const rotateY = (centerX - x) / 15;
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
            });
        });
    }

    // ==============================
    // PRODUCT QUICK VIEW MODAL
    // ==============================
    const qvModal = document.getElementById('quickview-modal');
    const qvOverlay = document.getElementById('quickview-overlay');
    const qvClose = document.getElementById('quickview-close');

    // Build products from DOM product cards (uses data attributes set by PHP)
    const products = Array.from(document.querySelectorAll('.product-card')).map(card => ({
        id: card.dataset.id,
        name: card.dataset.name,
        price: parseFloat(card.dataset.price),
        img: card.dataset.img,
        color: card.dataset.color || '',
        swatch: card.dataset.colorHex || '#1a1a1a'
    }));

    function openQuickView(productId) {
        const product = products.find(p => p.id === productId);
        if (!product) return;

        document.getElementById('qv-image').src = product.img;
        document.getElementById('qv-title').textContent = product.color;
        document.getElementById('qv-price').textContent = '$' + product.price;
        document.getElementById('qv-color-name').textContent = product.color;

        const addBtn = document.getElementById('qv-add-btn');
        addBtn.dataset.id = product.id;
        addBtn.dataset.name = product.name;
        addBtn.dataset.price = product.price;
        addBtn.dataset.img = product.img;

        // Render color swatches in modal
        const colorsContainer = document.getElementById('qv-colors');
        colorsContainer.innerHTML = products.map(p => `
            <button class="w-7 h-7 rounded-full border-2 transition-all hover:scale-110 ${p.id === product.id ? 'border-white/50 scale-110' : 'border-transparent'}"
                style="background: ${p.swatch}" title="${p.color}"
                onclick="document.getElementById('quickview-modal').dispatchEvent(new CustomEvent('switchColor', {detail: '${p.id}'}))">
            </button>
        `).join('');

        qvModal.classList.add('open');
        qvOverlay.classList.add('open');
        document.documentElement.classList.add('lenis-stopped');
    }

    function closeQuickView() {
        qvModal?.classList.remove('open');
        qvOverlay?.classList.remove('open');
        document.documentElement.classList.remove('lenis-stopped');
    }

    // Switch color inside quickview
    qvModal?.addEventListener('switchColor', (e) => {
        openQuickView(e.detail);
    });

    // Click product card image area to open quickview
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart-btn')) return;
            openQuickView(card.dataset.id);
        });
    });

    qvClose?.addEventListener('click', closeQuickView);
    qvOverlay?.addEventListener('click', closeQuickView);

    // ==============================
    // TESTIMONIALS CAROUSEL (GSAP)
    // ==============================
    const slider = document.getElementById('testimonial-slider');
    const slides = document.querySelectorAll('.testimonial-slide');
    const dotsContainer = document.getElementById('test-dots');
    let currentSlide = 0;

    function getSlidesPerView() {
        if (window.innerWidth >= 1024) return 3;
        if (window.innerWidth >= 768) return 2;
        return 1;
    }

    function getMaxSlide() {
        return Math.max(0, slides.length - getSlidesPerView());
    }

    function goToSlide(index) {
        currentSlide = Math.max(0, Math.min(index, getMaxSlide()));
        const slideWidth = 100 / getSlidesPerView();
        gsap.to(slider, {
            x: -(currentSlide * slideWidth) + '%',
            duration: 0.7,
            ease: 'power2.out'
        });
        updateDots();
    }

    function updateDots() {
        if (!dotsContainer) return;
        const totalDots = getMaxSlide() + 1;
        dotsContainer.innerHTML = '';
        for (let i = 0; i < totalDots; i++) {
            const dot = document.createElement('button');
            dot.className = `test-dot ${i === currentSlide ? 'active' : ''}`;
            dot.setAttribute('aria-label', `Go to review ${i + 1}`);
            dot.addEventListener('click', () => goToSlide(i));
            dotsContainer.appendChild(dot);
        }
    }

    document.getElementById('test-next')?.addEventListener('click', () => goToSlide(currentSlide + 1));
    document.getElementById('test-prev')?.addEventListener('click', () => goToSlide(currentSlide - 1));

    // Init dots
    updateDots();
    window.addEventListener('resize', () => {
        goToSlide(Math.min(currentSlide, getMaxSlide()));
    });

    // Testimonials scroll reveal
    gsap.from('.testimonial-slide', {
        scrollTrigger: {
            trigger: '#testimonials',
            start: 'top 75%',
        },
        y: 40,
        opacity: 0,
        stagger: 0.1,
        duration: 0.8,
        ease: 'power2.out'
    });

    // ==============================
    // DISCOUNT SECTION ANIMATION
    // ==============================
    gsap.from('.discount-text', {
        scrollTrigger: {
            trigger: '.discount-text',
            start: 'top 80%',
        },
        x: -50,
        opacity: 0,
        duration: 1,
        ease: 'power2.out'
    });

    gsap.from('.discount-image', {
        scrollTrigger: {
            trigger: '.discount-image',
            start: 'top 85%',
        },
        x: 50,
        opacity: 0,
        duration: 1,
        ease: 'power2.out'
    });

    // ==============================
    // COUNTDOWN TIMER
    // ==============================
    function startCountdown() {
        // Set end date to 7 days from now (persisted per session)
        let endTime = sessionStorage.getItem('beats3_countdown_end');
        if (!endTime) {
            endTime = Date.now() + 7 * 24 * 60 * 60 * 1000;
            sessionStorage.setItem('beats3_countdown_end', endTime);
        }
        endTime = Number(endTime);

        function tick() {
            const now = Date.now();
            let diff = endTime - now;
            if (diff <= 0) diff = 0;

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const secs = Math.floor((diff % (1000 * 60)) / 1000);

            const dEl = document.getElementById('countdown-days');
            const hEl = document.getElementById('countdown-hours');
            const mEl = document.getElementById('countdown-mins');
            const sEl = document.getElementById('countdown-secs');

            if (dEl) dEl.textContent = String(days).padStart(2, '0');
            if (hEl) hEl.textContent = String(hours).padStart(2, '0');
            if (mEl) mEl.textContent = String(mins).padStart(2, '0');
            if (sEl) sEl.textContent = String(secs).padStart(2, '0');
        }

        tick();
        setInterval(tick, 1000);
    }
    startCountdown();

    // ==============================
    // FAQ ACCORDION (GSAP)
    // ==============================
    document.querySelectorAll('.faq-trigger').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const item = trigger.closest('.faq-item');
            const content = item.querySelector('.faq-content');
            const isOpen = item.classList.contains('active');

            // Close all
            document.querySelectorAll('.faq-item.active').forEach(openItem => {
                openItem.classList.remove('active');
                gsap.to(openItem.querySelector('.faq-content'), {
                    height: 0,
                    duration: 0.4,
                    ease: 'power2.inOut'
                });
            });

            // Open clicked (if wasn't already open)
            if (!isOpen) {
                item.classList.add('active');
                gsap.to(content, {
                    height: 'auto',
                    duration: 0.4,
                    ease: 'power2.inOut'
                });
            }
        });
    });

    // FAQ scroll reveal
    gsap.from('.faq-item', {
        scrollTrigger: {
            trigger: '#faq',
            start: 'top 80%',
            toggleActions: 'play none none none',
        },
        y: 30,
        opacity: 0,
        stagger: 0.1,
        duration: 0.6,
        ease: 'power2.out',
        clearProps: 'opacity,transform'
    });

    // ==============================
    // NEWSLETTER FORM
    // ==============================
    const newsletterForm = document.getElementById('newsletter-form');
    newsletterForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const emailInput = document.getElementById('newsletter-email');
        if (!emailInput?.value) return;

        // Replace form with success message
        newsletterForm.innerHTML = '<p class="newsletter-success"><i class="ri-check-line"></i> Thank you! Check your inbox for your 10% off code.</p>';
    });

    // ==============================
    // SECTION TITLES SCROLL REVEAL
    // ==============================
    document.querySelectorAll('section').forEach(section => {
        const heading = section.querySelector('h2');
        if (heading) {
            gsap.from(heading, {
                scrollTrigger: {
                    trigger: heading,
                    start: 'top 85%',
                    once: true
                },
                y: 40,
                opacity: 0,
                duration: 0.8,
                ease: 'power2.out'
            });
        }
    });

    // ==============================
    // KEYBOARD ESCAPE TO CLOSE MODALS
    // ==============================
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            Cart.closeDrawer();
            closeQuickView();
            navMenu?.classList.remove('open');
        }
    });

});
