/* ============================================
   CART MODULE — Beats 3 Landing Page
   Full localStorage cart with live UI updates
   ============================================ */

const Cart = (() => {
    const STORAGE_KEY = 'beats3_cart';

    // ---- State ----
    let items = [];

    // ---- XSS Protection ----
    function esc(str) {
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    // ---- DOM refs (lazy-bound) ----
    const el = (id) => document.getElementById(id);

    // ---- Persistence ----
    function load() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            items = raw ? JSON.parse(raw) : [];
        } catch {
            items = [];
        }
    }
    function save() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    }

    // ---- Core methods ----
    function addItem(id, name, price, img) {
        const existing = items.find(i => i.id === id);
        if (existing) {
            existing.qty += 1;
        } else {
            items.push({ id, name, price: Number(price), img, qty: 1 });
        }
        save();
        renderCart();
        updateBadge();
        showToast(name);
    }

    function removeItem(id) {
        items = items.filter(i => i.id !== id);
        save();
        renderCart();
        updateBadge();
    }

    function updateQty(id, delta) {
        const item = items.find(i => i.id === id);
        if (!item) return;
        item.qty += delta;
        if (item.qty <= 0) {
            removeItem(id);
            return;
        }
        save();
        renderCart();
        updateBadge();
    }

    function getTotal() {
        return items.reduce((s, i) => s + i.price * i.qty, 0);
    }

    function getCount() {
        return items.reduce((s, i) => s + i.qty, 0);
    }

    // ---- UI Rendering ----
    function renderCart() {
        const container = el('cart-items');
        const empty = el('cart-empty');
        const footer = el('cart-footer');
        const totalEl = el('cart-total');
        const totalBtn = el('cart-total-btn');
        if (!container) return;

        if (items.length === 0) {
            container.innerHTML = '<p id="cart-empty" class="text-center text-gray-500 py-12">Your cart is empty</p>';
            if (footer) footer.classList.add('hidden');
            return;
        }

        container.innerHTML = items.map(item => `
            <div class="cart-item" data-id="${esc(item.id)}">
                <img src="${esc(item.img)}" alt="${esc(item.name)}">
                <div class="cart-item-info">
                    <p class="cart-item-name">${esc(item.name)}</p>
                    <p class="cart-item-price">$${esc(item.price)}</p>
                    <div class="cart-item-qty">
                        <button onclick="Cart.updateQty('${esc(item.id)}', -1)" aria-label="Decrease quantity">−</button>
                        <span>${esc(item.qty)}</span>
                        <button onclick="Cart.updateQty('${esc(item.id)}', 1)" aria-label="Increase quantity">+</button>
                    </div>
                </div>
                <button class="cart-item-remove" onclick="Cart.removeItem('${esc(item.id)}')" aria-label="Remove ${esc(item.name)}">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        `).join('');

        if (footer) footer.classList.remove('hidden');
        const total = getTotal();
        if (totalEl) totalEl.textContent = `$${total}`;
        if (totalBtn) totalBtn.textContent = `$${total}`;
    }

    function updateBadge() {
        const badge = el('cart-count');
        if (!badge) return;
        const count = getCount();
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('opacity-0', 'scale-0');
            badge.classList.add('opacity-100', 'scale-100');
        } else {
            badge.classList.add('opacity-0', 'scale-0');
            badge.classList.remove('opacity-100', 'scale-100');
        }
    }

    // ---- Toast ----
    function showToast(name) {
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `<i class="ri-check-line"></i> <span>${esc(name)} added to cart</span>`;
        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        }, 2500);
    }

    // ---- Cart Drawer Toggle ----
    function openDrawer() {
        el('cart-drawer')?.classList.add('open');
        el('cart-overlay')?.classList.add('open');
        document.documentElement.classList.add('lenis-stopped');
    }

    function closeDrawer() {
        el('cart-drawer')?.classList.remove('open');
        el('cart-overlay')?.classList.remove('open');
        document.documentElement.classList.remove('lenis-stopped');
    }

    // ---- Init ----
    function init() {
        load();
        renderCart();
        updateBadge();

        // Drawer controls
        el('cart-toggle')?.addEventListener('click', openDrawer);
        el('cart-close')?.addEventListener('click', closeDrawer);
        el('cart-overlay')?.addEventListener('click', closeDrawer);

        // "Add to Cart" buttons on product cards
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const card = btn.closest('.product-card');
                if (!card) return;
                addItem(card.dataset.id, card.dataset.name, card.dataset.price, card.dataset.img);
            });
        });

        // Generic "add-to-cart-trigger" buttons (hero, discount, floating)
        document.querySelectorAll('.add-to-cart-trigger').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                addItem(btn.dataset.id, btn.dataset.name, btn.dataset.price, btn.dataset.img);
            });
        });

        // Quick-view modal add button
        el('qv-add-btn')?.addEventListener('click', () => {
            const btn = el('qv-add-btn');
            addItem(btn.dataset.id, btn.dataset.name, btn.dataset.price, btn.dataset.img);
        });
    }

    // Public API
    return { init, addItem, removeItem, updateQty, openDrawer, closeDrawer, clear() { items = []; save(); renderCart(); updateBadge(); } };
})();
