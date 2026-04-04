# Execution Plan — Beats 3 E-Commerce Complete Overhaul

> **Project:** headphones1.duka (Beats 3 Store)  
> **Date:** April 4, 2026  
> **Scope:** Cleanup, User Dashboard, Mobile Responsiveness, SEO Optimization, Schema Rewrite  
> **Status:** ✅ ALL PHASES COMPLETE  

---

## Pre-Execution Audit Summary

### Current State
- **Tech Stack:** PHP 8+, MySQL (PDO), Tailwind CSS (CDN), GSAP, Chart.js
- **Admin Dashboard:** Fully functional (10 pages: Dashboard, Analytics, Products, Orders, Payments, Customers, Newsletter, Messages, Notifications, Settings, Profile)
- **Auth System:** Login, Register, Logout, Forgot Password
- **APIs:** Cart CRUD, Checkout, Contact, Newsletter
- **Frontend:** Homepage, Checkout, Contact, 404, Legal pages (3)

### Issues Identified

#### Unnecessary/Duplicate Files (to remove)
| File | Reason |
|------|--------|
| `index.html` | Duplicate of `index.php` — static HTML version superseded by PHP |
| `privacy-policy.html` | Duplicate of `legal/privacy-policy.php` |
| `terms-of-service.html` | Duplicate of `legal/terms-of-service.php` |
| `cookie-policy.html` | Duplicate of `legal/cookie-policy.php` |

#### Missing Pages (to create)
| Page | Purpose |
|------|---------|
| `product.php` | Individual product detail page (SEO-critical) |
| `shop.php` | Product catalog/browse page with filtering |
| `account/index.php` | User dashboard — overview with recent orders |
| `account/orders.php` | User order history with status tracking |
| `account/order-view.php` | User individual order detail view |
| `account/profile.php` | User profile management (name, email, password) |
| `account/addresses.php` | Saved shipping addresses |
| `account/wishlist.php` | User wishlist |
| `.htaccess` | URL rewriting, security headers, access control |

#### Missing Infrastructure Files
| File | Purpose |
|------|---------|
| `account/includes/header.php` | User dashboard layout (sidebar + topbar) |
| `account/includes/footer.php` | User dashboard layout closing |
| `api/wishlist.php` | Wishlist CRUD API |
| `api/account.php` | User profile update API |

#### Schema Gaps
- No `wishlists` table for user wishlist feature
- No `user_addresses` table for saved addresses
- No `reviews` table (future consideration)
- No `settings` table (currently uses JSON file)
- No `order_status_history` table for tracking order changes

#### SEO Issues
- No individual product pages (biggest SEO gap)
- No `shop.php` landing page for product browsing
- `sitemap.xml` is static, not dynamic
- Missing JSON-LD structured data on product pages
- Missing Open Graph meta tags on several pages
- No breadcrumb navigation on inner pages

#### Navigation Issues
- Footer "Earphones", "Earbuds", "Accessories" links are dead ("Coming Soon")
- No user dashboard link for logged-in users
- No "My Orders" accessible from nav for customers

---

## Execution Phases

---

### PHASE 1: Cleanup — Remove Unnecessary Files
> Remove duplicate static HTML files that are superseded by PHP versions.

**Files to Delete:**
1. `index.html` — replaced by `index.php`
2. `privacy-policy.html` — replaced by `legal/privacy-policy.php`
3. `terms-of-service.html` — replaced by `legal/terms-of-service.php`
4. `cookie-policy.html` — replaced by `legal/cookie-policy.php`

**Verification:** Ensure no remaining internal links point to `.html` versions; redirect or update any references.

---

### PHASE 2: Database Schema Rewrite
> Extend the schema to support user dashboard features, settings, and order history tracking.

**2.1 — New Tables to Add:**
- `wishlists` (id, user_id FK, product_id FK, created_at) — UNIQUE(user_id, product_id)
- `user_addresses` (id, user_id FK, label, address, city, state, zip, country, is_default, timestamps)
- `site_settings` (id, setting_key UNIQUE, setting_value, updated_at) — replaces JSON file
- `order_status_history` (id, order_id FK, old_status, new_status, changed_by FK, note, created_at)

**2.2 — Table Modifications:**
- `users`: Add `avatar` (VARCHAR 255, nullable), `last_login` (TIMESTAMP, nullable)
- `orders`: Add `tracking_number` (VARCHAR 100, nullable)

**2.3 — Seed Data Updates:**
- Insert default site settings into `site_settings`
- Ensure all existing seed data remains intact

---

### PHASE 3: Core Infrastructure
> Create .htaccess, update config, set up user dashboard layout.

**3.1 — `.htaccess` File**
- Rewrite rules for clean URLs (product.php?slug=x → /product/x)
- Security headers (X-Content-Type-Options, X-Frame-Options, Referrer-Policy)
- Block access to `includes/`, `sql/`, `.env`, `admin/settings.json`
- Custom ErrorDocument 404 → 404.php
- Enable GZIP compression
- Cache static assets (images, CSS, JS)
- Prevent directory listing

**3.2 — Update `includes/config.php`**
- Add constants for user dashboard paths
- Add helper functions for user dashboard navigation

**3.3 — Update `includes/functions.php`**
- Add `getUserOrders($userId, $limit)` — fetch order history
- Add `getUserAddresses($userId)` — fetch saved addresses
- Add `getWishlistCount($userId)` — count wishlist items
- Add `isInWishlist($userId, $productId)` — check wishlist status
- Add `getSiteSetting($key, $default)` — read from site_settings table
- Add `breadcrumb($items)` — render breadcrumb HTML

**3.4 — User Dashboard Layout**
- `account/includes/header.php` — Sidebar navigation matching admin style with:
  - User avatar + name
  - Navigation: Dashboard, My Orders, Wishlist, Addresses, Profile
  - Visit Store link, Logout link
  - Mobile hamburger toggle
  - Page title + breadcrumb on topbar
- `account/includes/footer.php` — Closing layout tags, mobile nav JS

---

### PHASE 4: User Dashboard Pages
> Build all 6 user-facing account pages mirroring admin dashboard UI patterns.

**4.1 — `account/index.php` (User Dashboard)**
- KPI cards: Total Orders, Total Spent, Wishlist Items, Saved Addresses
- Recent Orders table (last 5) with status badges & view links
- Wishlist preview (last 4 items) with quick-remove
- Quick action buttons: Browse Products, View Orders, Edit Profile

**4.2 — `account/orders.php` (Order History)**
- Search/filter by: order number, status dropdown
- Paginated orders table: Order #, Date, Items Count, Total, Status badge, View button
- Status color coding matching admin (pending=yellow, processing=blue, shipped=purple, delivered=green, cancelled=red)
- Empty state with "No orders yet" + CTA to shop

**4.3 — `account/order-view.php` (Order Detail)**
- Order info card: order #, date, status badge, payment method
- Order items table with product images, names, quantities, prices
- Order totals: subtotal, shipping, tax, total
- Shipping address card
- Order status timeline (from order_status_history)
- Tracking number display (if available)
- Back to orders link

**4.4 — `account/profile.php` (Profile Settings)**
- Two-tab layout (matching admin profile):
  - **Profile Tab:** Name, Email, Phone edit form
  - **Password Tab:** Current password, New password, Confirm password with requirements
- Avatar display with initial-based fallback
- Account info section (member since, total orders)

**4.5 — `account/addresses.php` (Address Book)**
- List saved addresses as cards
- Default address badge
- Add New Address form (modal or inline)
- Edit/Delete/Set Default actions per address
- Empty state with "No addresses saved" message

**4.6 — `account/wishlist.php` (Wishlist)**
- Product grid matching shop page style
- Each card: Image, Name, Price, Stock status, Add to Cart button, Remove from Wishlist
- Empty state with "Your wishlist is empty" + CTA to browse products
- Quick add-to-cart with toast notification

---

### PHASE 5: Missing Frontend Pages
> Create product detail and shop pages that are essential for user experience and SEO.

**5.1 — `shop.php` (Product Catalog)**
- Product grid with category filter sidebar/dropdown
- Sort by: Price (low-high, high-low), Name, Newest
- Product cards: Image, Name, Price (sale highlighting), Quick View button, Add to Cart
- Category filter from database
- Result count
- SEO: Proper title, meta description, canonical URL

**5.2 — `product.php` (Product Detail)**
- URL: `product.php?slug=beats3-black`
- Large product image
- Product info: Name, Price (sale pricing), Color, Stock status
- Color variant swatches linking to other variants
- Add to Cart with quantity selector
- Wishlist toggle button (heart icon)
- Product description (full)
- Specifications section
- Breadcrumb navigation (Home > Products > Product Name)
- SEO: JSON-LD Product structured data, OG tags, canonical URL, proper H1

---

### PHASE 6: API Endpoints for New Features
> Backend APIs to support user dashboard and wishlist.

**6.1 — `api/wishlist.php`**
- POST `action=toggle` — Add/remove product from wishlist
- GET — Fetch user's wishlist items
- Requires authentication
- Returns JSON with item details

**6.2 — `api/account.php`**
- POST `action=update_profile` — Update name, email, phone
- POST `action=update_password` — Change password with current password verification
- POST `action=add_address` — Add new shipping address
- POST `action=update_address` — Edit existing address
- POST `action=delete_address` — Remove address
- POST `action=set_default_address` — Set default shipping address
- All require authentication + CSRF

---

### PHASE 7: SEO Optimization (Site-Wide)
> Ensure every page is search-engine optimized.

**7.1 — Meta Tags & Head Elements (all pages)**
- Unique `<title>` with format: "Page Name | Beats 3 Store"
- Unique `<meta name="description">` per page (150-160 chars)
- Canonical URLs on every page
- Open Graph tags (og:title, og:description, og:image, og:url, og:type)
- Twitter Card meta tags
- `<meta name="robots">` — noindex on admin, auth, api, account pages
- Proper viewport meta tag (already present)

**7.2 — Structured Data (JSON-LD)**
- **Homepage:** Organization schema, WebSite schema with SearchAction
- **Product pages:** Product schema (name, price, availability, image, brand, sku, review aggregation)
- **Shop page:** ItemList schema
- **Contact page:** ContactPage schema with business contact info
- **Breadcrumbs:** BreadcrumbList schema on all inner pages

**7.3 — Content & HTML Structure**
- Single `<h1>` per page (verify all pages)
- Proper heading hierarchy (h1 → h2 → h3, no skips)
- Alt text on all images (audit and fix)
- Descriptive link text (no "click here")
- Semantic HTML elements (`<main>`, `<article>`, `<section>`, `<nav>`, `<aside>`)

**7.4 — Technical SEO**
- Update `sitemap.xml` to include all public pages dynamically
- Update `robots.txt` to disallow new private paths
- `.htaccess` compression and caching (Phase 3)
- Ensure 404 page returns proper HTTP 404 status code (already done)

**7.5 — Internal Linking Fixes**
- All footer links functional (fix dead links)
- Breadcrumb navigation on all inner pages
- Related products on product detail page
- Cross-linking between shop and product pages

---

### PHASE 8: Mobile Responsiveness Audit & Fixes
> Ensure every page works on all screen sizes (320px to 1920px+).

**8.1 — Global Layout Checks**
- Verify Tailwind responsive breakpoints used correctly (sm, md, lg, xl)
- Test navigation hamburger menu on all pages
- Cart drawer responsive behavior (full-width on mobile)
- Footer grid collapses properly on mobile

**8.2 — Frontend Pages**
- Homepage sections stack properly on mobile
- Checkout form single-column on mobile, two-column on desktop
- Contact page info cards stack on mobile
- Product grid: 1 col mobile, 2 col tablet, 3-4 col desktop
- Product detail: Image stacks above info on mobile

**8.3 — User Dashboard (built mobile-first)**
- Sidebar collapses to hamburger on mobile (matching admin pattern)
- Tables become card-based on small screens
- KPI cards: 2-col on mobile, 4-col on desktop
- Order details layout stacks on mobile
- Address cards single-column on mobile

**8.4 — Admin Dashboard Responsive Fixes**
- Audit existing admin pages for mobile breakpoints
- Fix any overflow issues in admin tables
- Ensure admin sidebar works on tablet

---

### PHASE 9: Navigation & Link Fixes
> Clean up all broken links and ensure seamless navigation.

**9.1 — Header Navigation Updates**
- Add "Shop" link to main nav (points to `shop.php`)
- Add user dropdown for logged-in users: My Account, My Orders, Wishlist, Logout
- Keep Cart icon with badge
- Ensure mobile nav includes all links

**9.2 — Footer Link Fixes**
- "Earphones", "Earbuds", "Accessories" → Link to `shop.php` with category filter (or show "Coming Soon" tooltip)
- "Product Help" → `contact.php` ✓ (already works)
- All legal links → `legal/*.php` (verify paths)
- Add "My Account" / "Track Order" to Support column

**9.3 — Breadcrumb Navigation**
- Implement on: Shop, Product Detail, Checkout, Contact, Legal pages, Account pages
- Format: Home > Shop > Product Name
- JSON-LD BreadcrumbList on each page

**9.4 — Update `sitemap.xml`**
- Add: shop.php, product.php (for each product), account pages (noindex but still crawlable)
- Remove: deleted HTML files
- Add lastmod dates

**9.5 — Update `robots.txt`**
- Add disallow: /account/, /api/account.php, /api/wishlist.php
- Keep existing disallows for /admin/, /includes/, /sql/

---

### PHASE 10: Error Handling & Final Polish
> Ensure zero errors and clean user experience.

**10.1 — Error Handling Audit**
- All database queries wrapped in try/catch
- All API endpoints return proper JSON error responses
- 404 handling for non-existent products/orders
- Access control: redirect to login when unauthenticated
- Flash messages for success/error on all form submissions

**10.2 — Cross-Page Consistency**
- Consistent color palette across all pages
- Consistent button styles (primary, secondary, danger)
- Consistent table styles
- Consistent empty states
- Consistent loading states

**10.3 — Security Audit**
- CSRF tokens on all forms (user dashboard + new pages)
- Input validation on all form submissions
- XSS-safe output everywhere (htmlspecialchars)
- SQL injection prevention (all queries use prepared statements)
- Rate limiting on sensitive endpoints

**10.4 — Final Testing Checklist**
- [ ] All pages load without PHP errors
- [ ] All navigation links work
- [ ] Cart operations work (add, update, remove, checkout)
- [ ] User registration → login → dashboard flow works
- [ ] Order placement → order history visibility works
- [ ] Wishlist add/remove works
- [ ] Address CRUD works
- [ ] Profile update works
- [ ] Password change works
- [ ] Admin dashboard still fully functional
- [ ] Mobile layout on all pages (320px, 375px, 768px, 1024px, 1440px)
- [ ] SEO meta tags on all public pages
- [ ] sitemap.xml updated
- [ ] robots.txt updated
- [ ] No console errors in browser
- [ ] No PHP warnings/notices in error log

---

## Execution Order Summary

| Phase | Description | Dependencies |
|-------|-------------|--------------|
| 1 | Cleanup duplicate files | None |
| 2 | Database schema rewrite | None |
| 3 | Core infrastructure (.htaccess, config, layout) | Phase 2 |
| 4 | User dashboard pages (6 pages) | Phase 2, 3 |
| 5 | Missing frontend pages (shop, product detail) | Phase 2, 3 |
| 6 | API endpoints (wishlist, account) | Phase 2 |
| 7 | SEO optimization (all pages) | Phase 4, 5 |
| 8 | Mobile responsiveness audit/fixes | Phase 4, 5 |
| 9 | Navigation & link fixes | Phase 4, 5, 7 |
| 10 | Error handling & final polish | All phases |

---

## Files Created/Modified Summary

### New Files (17)
- `.htaccess`
- `shop.php`
- `product.php`
- `account/index.php`
- `account/orders.php`
- `account/order-view.php`
- `account/profile.php`
- `account/addresses.php`
- `account/wishlist.php`
- `account/includes/header.php`
- `account/includes/footer.php`
- `api/wishlist.php`
- `api/account.php`

### Files Deleted (4)
- `index.html`
- `privacy-policy.html`
- `terms-of-service.html`
- `cookie-policy.html`

### Files Modified (8+)
- `sql/schema.sql` — new tables + column additions
- `includes/config.php` — new constants
- `includes/functions.php` — new helper functions
- `includes/header.php` — nav updates, user dropdown, SEO meta
- `includes/footer.php` — link fixes, updated navigation
- `robots.txt` — additional disallow rules
- `sitemap.xml` — new pages added
- All existing PHP pages — SEO meta tags, breadcrumbs, mobile fixes
