/* ============================================
   DEMO MODE — Static Vercel display build
   Stands in for the PHP API: the cart lives in localStorage,
   forms answer with a demo notice.
   Injected by scripts/export-static.php — not used by the PHP site.
   ============================================ */

(() => {
    const PRODUCTS = /*__DEMO_PRODUCTS__*/[];
    const CART_KEY = 'demo_cart';
    const DEMO_MESSAGE = 'This is a display demo — accounts and checkout are turned off.';

    // ---- Storage (may be unavailable in private browsing) ----
    function loadCart() {
        try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; } catch (e) { return []; }
    }

    function saveCart(lines) {
        try { localStorage.setItem(CART_KEY, JSON.stringify(lines)); } catch (e) { /* ignore */ }
    }

    const countOf = (lines) => lines.reduce((sum, l) => sum + l.quantity, 0);

    // Same rule as effectivePrice() in includes/functions.php
    const priceOf = (p) => (p.sale_price > 0 && p.sale_price < p.price) ? p.sale_price : p.price;

    // ---- Cart (mirrors api/cart.php responses; item id = product id) ----
    function getCart() {
        const items = loadCart().map(line => {
            const product = PRODUCTS.find(p => p.id === line.product_id);
            if (!product) return null;
            const effectivePrice = priceOf(product);
            return {
                ...product,
                product_id: product.id,
                quantity: line.quantity,
                effective_price: effectivePrice,
                line_total: effectivePrice * line.quantity,
            };
        }).filter(Boolean);

        return {
            success: true,
            items,
            total: items.reduce((sum, i) => sum + i.line_total, 0),
            count: countOf(items),
        };
    }

    function changeCart(body) {
        const id = Number(body.product_id ?? body.item_id);
        const product = PRODUCTS.find(p => p.id === id);
        let lines = loadCart();
        const line = lines.find(l => l.product_id === id);

        switch (body.action) {
            case 'add': {
                if (!product) return { success: false, message: 'Product not found.' };
                const qty = (line ? line.quantity : 0) + Math.max(1, Number(body.quantity) || 1);
                if (qty > product.stock) return { success: false, message: 'Cannot exceed available stock.' };
                if (line) line.quantity = qty;
                else lines.push({ product_id: id, quantity: qty });
                break;
            }
            case 'update': {
                if (!line || !product) return { success: false, message: 'Cart item not found.' };
                const qty = Math.max(1, Number(body.quantity) || 1);
                if (qty > product.stock) return { success: false, message: 'Cannot exceed available stock.' };
                line.quantity = qty;
                break;
            }
            case 'remove':
                lines = lines.filter(l => l.product_id !== id);
                break;
            case 'clear':
                lines = [];
                break;
            default:
                return { success: false, message: 'Invalid action.' };
        }

        saveCart(lines);
        return { success: true, count: countOf(lines) };
    }

    // ---- Wishlist (per page view, matching the server-rendered guest state) ----
    const wishlist = new Set();
    function toggleWishlist(body) {
        const id = Number(body.product_id);
        const removed = wishlist.delete(id);
        if (!removed) wishlist.add(id);
        return { success: true, action: removed ? 'removed' : 'added' };
    }

    // ---- API stand-in ----
    const handlers = {
        cart: (method, body) => (method === 'GET' ? getCart() : changeCart(body)),
        wishlist: (method, body) => toggleWishlist(body),
        contact: () => ({ success: true, message: 'Thanks! This is a display demo, so your message was not actually sent.' }),
        newsletter: () => ({ success: true, message: 'Thanks for subscribing! (Display demo — nothing was saved and no email is sent.)' }),
        coupon: () => ({ success: false, message: DEMO_MESSAGE }),
    };

    const realFetch = window.fetch.bind(window);
    window.fetch = (input, init = {}) => {
        const url = new URL(input instanceof Request ? input.url : String(input), location.href);
        const endpoint = url.origin === location.origin && url.pathname.match(/\/api\/([\w-]+)\.php$/);
        if (!endpoint) return realFetch(input, init);

        let body = {};
        try { body = typeof init.body === 'string' ? JSON.parse(init.body) : {}; } catch (e) { /* not JSON */ }
        const method = (init.method || 'GET').toUpperCase();
        const handler = handlers[endpoint[1]];
        const data = handler ? handler(method, body) : { success: false, message: DEMO_MESSAGE };

        return Promise.resolve(new Response(JSON.stringify(data), {
            headers: { 'Content-Type': 'application/json' },
        }));
    };

    // ---- Notice toast (same styling as the cart toast) ----
    function showNotice(message) {
        document.querySelector('.toast')?.remove();
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = '<i class="ri-information-line"></i> <span></span>';
        toast.querySelector('span').textContent = message;
        document.body.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        }, 3500);
    }

    // ---- Full-page POST forms (login, register, reset) have no backend here ----
    document.addEventListener('submit', (e) => {
        const form = e.target;
        const action = form.getAttribute('action') || '';
        if (form.method.toLowerCase() !== 'post' || /(^|\/)api\//.test(action)) return;
        e.preventDefault();
        showNotice(DEMO_MESSAGE);
    }, true);
})();
