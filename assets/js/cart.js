/* ============================================
   CART MODULE — Beats 3 Store
   API-driven cart with server sync + drawer UI
   ============================================ */

const Cart = (() => {
    const API_URL = document.querySelector('meta[name="cart-api"]')?.content || '/headphones1.duka/api/cart.php';

    // ---- State ----
    let items = [];
    let total = 0;
    let cartCount = 0;

    // ---- XSS Protection ----
    function esc(str) {
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    // ---- CSRF Token ----
    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    // ---- DOM refs ----
    const el = (id) => document.getElementById(id);

    // ---- API Calls ----
    async function apiGet() {
        try {
            const res = await fetch(API_URL, { credentials: 'same-origin' });
            return await res.json();
        } catch (e) {
            console.error('Cart fetch error:', e);
            return { success: false, items: [], total: 0, count: 0 };
        }
    }

    async function apiPost(action, data = {}) {
        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken()
                },
                body: JSON.stringify({ action, _token: csrfToken(), ...data })
            });
            return await res.json();
        } catch (e) {
            console.error('Cart API error:', e);
            return { success: false, message: 'Network error' };
        }
    }

    // ---- Core Methods ----
    async function fetchCart() {
        const data = await apiGet();
        if (data.success) {
            items = data.items;
            total = data.total;
            cartCount = data.count;
        }
        renderCart();
        updateBadge();
    }

    async function addItem(productId, name, price, img, qty = 1) {
        // Optimistic UI: show toast immediately
        showToast(name);

        const data = await apiPost('add', { product_id: Number(productId), quantity: qty });
        if (data.success) {
            await fetchCart();
            openDrawer();
        } else {
            showToast(data.message || 'Could not add to cart', true);
        }
    }

    async function removeItem(itemId) {
        const data = await apiPost('remove', { item_id: Number(itemId) });
        if (data.success) {
            await fetchCart();
        }
    }

    async function updateQty(itemId, newQty) {
        if (newQty <= 0) {
            removeItem(itemId);
            return;
        }
        const data = await apiPost('update', { item_id: Number(itemId), quantity: newQty });
        if (data.success) {
            await fetchCart();
        } else {
            showToast(data.message || 'Could not update quantity', true);
        }
    }

    async function clearCart() {
        const data = await apiPost('clear');
        if (data.success) {
            items = [];
            total = 0;
            cartCount = 0;
            renderCart();
            updateBadge();
        }
    }

    // ---- UI Rendering ----
    function renderCart() {
        const container = el('cart-items');
        const footer = el('cart-footer');
        const totalEl = el('cart-total');
        const totalBtn = el('cart-total-btn');
        if (!container) return;

        // Derive base path for relative image URLs
        const basePath = API_URL.replace(/\/api\/cart\.php$/, '');

        if (items.length === 0) {
            container.innerHTML = '<p class="text-center text-gray-500 py-12">Your cart is empty</p>';
            if (footer) footer.classList.add('hidden');
            return;
        }

        container.innerHTML = items.map(item => {
            const effectivePrice = item.sale_price || item.price;
            const imgSrc = item.image.startsWith('http') ? item.image : basePath + '/' + item.image;
            return `
            <div class="cart-item" data-item-id="${esc(item.id)}">
                <img src="${esc(imgSrc)}" alt="${esc(item.name)}">
                <div class="cart-item-info">
                    <p class="cart-item-name">${esc(item.name)}</p>
                    <p class="cart-item-price">$${Number(effectivePrice).toFixed(2)}</p>
                    <div class="cart-item-qty">
                        <button onclick="Cart.updateQty(${esc(item.id)}, ${item.quantity - 1})" aria-label="Decrease quantity">−</button>
                        <span>${esc(item.quantity)}</span>
                        <button onclick="Cart.updateQty(${esc(item.id)}, ${item.quantity + 1})" aria-label="Increase quantity">+</button>
                    </div>
                </div>
                <button class="cart-item-remove" onclick="Cart.removeItem(${esc(item.id)})" aria-label="Remove ${esc(item.name)}">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>`;
        }).join('');

        if (footer) footer.classList.remove('hidden');
        const fmtTotal = '$' + Number(total).toFixed(2);
        if (totalEl) totalEl.textContent = fmtTotal;
        if (totalBtn) totalBtn.textContent = fmtTotal;
    }

    function updateBadge() {
        const badge = el('cart-count');
        if (!badge) return;
        if (cartCount > 0) {
            badge.textContent = cartCount;
            badge.classList.remove('opacity-0', 'scale-0');
            badge.classList.add('opacity-100', 'scale-100');
        } else {
            badge.classList.add('opacity-0', 'scale-0');
            badge.classList.remove('opacity-100', 'scale-100');
        }
    }

    // ---- Toast ----
    function showToast(message, isError = false) {
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'toast';
        if (isError) {
            toast.innerHTML = `<i class="ri-error-warning-line" style="color:#ef4444"></i> <span>${esc(message)}</span>`;
        } else {
            toast.innerHTML = `<i class="ri-check-line"></i> <span>${esc(message)} added to cart</span>`;
        }
        document.body.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
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

    // ---- Button Binding ----
    function bindButtons() {
        // ".add-to-cart-btn" — reads data from button itself OR parent .product-card
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            if (btn._cartBound) return;
            btn._cartBound = true;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                // Try button data-* first, then parent .product-card
                const src = (btn.dataset.id) ? btn : btn.closest('.product-card');
                if (!src || !src.dataset.id) return;
                addItem(src.dataset.id, src.dataset.name, src.dataset.price, src.dataset.img);
            });
        });

        // ".add-to-cart-trigger" — hero, discount banners, floating CTA
        document.querySelectorAll('.add-to-cart-trigger').forEach(btn => {
            if (btn._cartBound) return;
            btn._cartBound = true;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (!btn.dataset.id) return;
                addItem(btn.dataset.id, btn.dataset.name, btn.dataset.price, btn.dataset.img);
            });
        });

        // Quick-view modal add button
        const qvBtn = el('qv-add-btn');
        if (qvBtn && !qvBtn._cartBound) {
            qvBtn._cartBound = true;
            qvBtn.addEventListener('click', () => {
                if (!qvBtn.dataset.id) return;
                addItem(qvBtn.dataset.id, qvBtn.dataset.name, qvBtn.dataset.price, qvBtn.dataset.img);
            });
        }
    }

    // ---- Init ----
    function init() {
        fetchCart();

        // Drawer controls
        el('cart-toggle')?.addEventListener('click', openDrawer);
        el('cart-close')?.addEventListener('click', closeDrawer);
        el('cart-overlay')?.addEventListener('click', closeDrawer);

        bindButtons();
    }

    // Public API
    return { init, addItem, removeItem, updateQty, openDrawer, closeDrawer, clear: clearCart, bindButtons };
})();
