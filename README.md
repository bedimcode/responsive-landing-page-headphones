<div align="center">

# 🎧 Headphones1.Duka — Headphones E-Commerce Store

[![PHP](https://img.shields.io/badge/PHP_8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![GSAP](https://img.shields.io/badge/GSAP-88CE02?style=for-the-badge&logo=greensock&logoColor=black)](https://greensock.com/gsap/)

**A complete PHP + MySQL online store: animated storefront, customer accounts, coupons, M-Pesa / bank / cash-on-delivery checkout, order emails and an admin panel — configurable without touching code.**

![Hero Preview](preview.png)

</div>

---

## ✨ Features

### Storefront
| Feature | Description |
|---------|-------------|
| 🎬 **Animated landing page** | GSAP hero timeline, scroll reveals, 3D card tilt, Lenis smooth scroll |
| 🛒 **Server-side cart** | Works for guests, merges into the account on sign-in, checks stock |
| 💳 **Checkout** | Cash on Delivery, **M-Pesa** (Paybill, Till or Send Money) and bank transfer — switch each on in Settings |
| 🏷️ **Coupons** | Percent or fixed discounts, minimum order, usage limits, expiry, one-per-customer |
| ⏱️ **Real promotions** | Homepage offer with a countdown to the end date you set; it hides itself when the offer ends |
| 📧 **Emails** | Order confirmation with payment steps, shipping updates, password reset, newsletter welcome code |
| 🔍 **SEO** | Database-driven `sitemap.xml`, canonical URLs, Open Graph tags, Product / Organization structured data |
| 💬 **WhatsApp button** | Optional chat button with your number |

### Customer Accounts
| Feature | Description |
|---------|-------------|
| 👤 **Sign up / sign in** | With login throttling and email password reset |
| 📦 **Orders** | Order history, status timeline, tracking number, payment instructions |
| 🧾 **Payment confirmation** | Customers submit their M-Pesa / bank transaction code on the order page |
| ❤️ **Wishlist & addresses** | Saved products and addresses (default address pre-fills checkout) |

### Admin Panel
| Feature | Description |
|---------|-------------|
| 📊 **Dashboard & analytics** | Revenue charts, order stats, low-stock and pending-order alerts |
| 📦 **Products** | Add / edit products with image upload, sale prices, stock, featured flag |
| 🛍️ **Orders** | Update status and payment, add tracking numbers and notes; customers are emailed; cancelling restocks |
| 🏷️ **Coupons & testimonials** | Create discount codes; manage homepage reviews |
| ⚙️ **Settings** | Store details, currency & tax, shipping, return / warranty policy, payment methods, promotion, notifications, maintenance mode |
| 🛠️ **System** | One-click database updates and a "send test email" button |

---

## 🛠️ Requirements

- **PHP 8.1+** with `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`
- **MySQL 5.7+** or **MariaDB 10.4+**
- **Apache** with `mod_rewrite` and `AllowOverride All` (XAMPP, cPanel shared hosting and most VPS setups work)
- **Node.js 18+** — only to rebuild the CSS after changing Tailwind classes; not needed on the server

---

## 🚀 Quick Start (XAMPP)

1. Copy the project to `C:\xampp\htdocs\headphones1.duka`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`) → **Import** → `sql/schema.sql`.
4. Visit `http://localhost/headphones1.duka`.
5. Sign in to the admin panel at `/auth/login.php` with **admin@beats3.store / Admin@123**, then change the password straight away (**My Profile → Password**). The dashboard shows a red warning until you do.

Out of the box the store runs in **demo mode** (a small "Demo store" badge) with sample products, coupons and testimonials, and emails are written to `logs/mail.log` instead of being sent.

---

## 🌍 Going Live — Checklist

1. **Upload** all files except `node_modules/` and `static-site/`.
2. **Database:** create one in your hosting panel and import `sql/schema.sql`. If the host created the database for you, delete the `CREATE DATABASE` and `USE` lines at the top of the file first.
3. **Local config:** copy `includes/config.local.sample.php` to `includes/config.local.php` and set:

   | Setting | Example |
   |---------|---------|
   | `APP_URL` | `https://www.yourshop.co.ke` (no trailing slash) |
   | `APP_ENV` | `production` |
   | `DEMO_MODE` | `false` |
   | `APP_TIMEZONE` | `Africa/Nairobi` |
   | `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | from your hosting panel |
   | `MAIL_TRANSPORT` + `SMTP_*` | see **Email** below |

4. **.htaccess:** if the shop is at the domain root, change `RewriteBase /headphones1.duka/` to `RewriteBase /` and the three `ErrorDocument` paths to `/404.php…`. Uncomment the **Force HTTPS** and **Strict-Transport-Security** lines once SSL is active.
5. **Folders:** make sure `logs/` and `assets/uploads/products/` are writable by PHP.
6. **Admin:** change the admin email and password (**My Profile**), then work through **Settings** tab by tab.
7. **Branding & content:** replace the logo, product photos and copy (see **Branding** below), and remove the sample coupons and testimonials.
8. **Legal pages:** the Terms, Privacy and Cookie pages fill in your store details automatically, but they are templates — have them reviewed for your country.
9. Place a test order with each payment method and check that the emails arrive.

---

## 🇰🇪 Selling in Kenya (KES + M-Pesa)

1. **Settings → Commerce:** symbol `KSh`, code `KES`, decimals `0`, tax name `VAT`, rate `16`, tick **Prices already include tax**. Set shipping (e.g. KSh 300, free from KSh 10,000), delivery estimate and default country `Kenya`.
2. **Products:** update every price to KES (changing the currency does not convert prices).
3. **Settings → Payments:** switch on **M-Pesa**, choose **Paybill** (customers use the order number as the account number), **Till** or **Send Money**, and enter your number and business name.
4. When a customer pays, they enter the M-PESA confirmation code on their order page. Check it against your M-PESA statement, then set the order's **Payment Status → Paid** in **Orders**.

> M-Pesa is only allowed while the currency is KES — the settings page blocks other combinations so customers are never asked to send "$249" over M-Pesa.

---

## 📧 Email

Set `MAIL_TRANSPORT` in `includes/config.local.php`:

| Value | Use |
|-------|-----|
| `log` | Development and demos — emails go to `logs/mail.log` |
| `mail` | Shared hosting where PHP `mail()` is configured |
| `smtp` | Recommended — Gmail / Google Workspace, Zoho, Mailgun, your host's mail server |

Gmail example: `SMTP_HOST=smtp.gmail.com`, `SMTP_PORT=587`, `SMTP_SECURE=tls`, `SMTP_USER` = your address, `SMTP_PASS` = an **App Password** (Google Account → Security → App passwords). Use **Settings → Notifications → Send a test email** to check it.

---

## 🔄 Updating an Existing Installation

Back up the database, upload the new files, then either open **Admin → Settings → System → Apply updates** or run:

```bash
php scripts/migrate.php
```

Updates are safe to run more than once. Your settings, products and orders are kept.

---

## 🎨 Branding & Customisation

- **Logo / favicon:** replace `assets/img/logo.png` and `assets/img/favicon.png`.
- **Store name, contact details, socials:** **Settings → General**.
- **Products:** **Admin → Products** (upload images there; transparent PNGs look best).
- **Homepage copy:** `index.php` (hero text, specs, FAQ).
- **Colours / fonts:** `tailwind.config.js` and `assets/css/styles.css`. After changing Tailwind classes or the config, rebuild the stylesheet:

  ```bash
  npm install
  npm run build:css      # or: npm run watch:css while editing
  ```

> ⚠️ **Trademarks:** the demo uses "Beats" product names, photos and the Beats logo as sample content. Beats is a trademark of Apple Inc. Replace the logo, product images and names with your own (or ones you are licensed to use) before running a real store or delivering this project to a client.

---

## ☁️ Static Demo (Vercel)

`static-site/` is a display-only copy of the storefront for showcasing (no PHP or database; the cart runs in the browser and checkout is disabled).

```bash
# With XAMPP running:
C:\xampp\php\php.exe scripts/export-static.php --site-url=https://your-demo.vercel.app
```

Commit `static-site/` and import the repository in Vercel — `vercel.json` already points at it.

---

## 📁 Project Structure

```
headphones1.duka/
├── index.php, shop.php, product.php, checkout.php, contact.php, 404.php
├── sitemap.php, robots.php          # Served as /sitemap.xml and /robots.txt
├── newsletter-unsubscribe.php
├── api/                             # JSON endpoints: cart, checkout, coupon, contact, newsletter, wishlist
├── auth/                            # login, register, logout, forgot-password, reset-password
├── account/                         # Customer dashboard (orders, wishlist, addresses, profile)
├── admin/                           # Admin panel (orders, products, coupons, testimonials, settings…)
├── includes/
│   ├── config.php                   # Defaults; loads config.local.php
│   ├── config.local.sample.php      # Copy to config.local.php on each server
│   ├── functions.php                # Settings, prices, totals, coupons, auth helpers
│   ├── mailer.php                   # Email delivery + templates
│   ├── migrations.php               # Database updates
│   ├── header.php, footer.php, maintenance.php
├── legal/                           # Terms, privacy and cookie policies
├── assets/
│   ├── css/tailwind.css             # Compiled Tailwind (built from tailwind.src.css)
│   ├── css/styles.css               # Custom styles
│   ├── js/main.js, js/cart.js
│   ├── img/                         # Logo and sample product images
│   └── uploads/products/            # Product images uploaded in the admin
├── logs/                            # error.log, mail.log (web access blocked)
├── scripts/                         # migrate.php, export-static.php
├── sql/schema.sql                   # Fresh-install schema + sample data
└── static-site/                     # Generated demo for Vercel
```

---

## 🔒 Security

- PDO prepared statements everywhere; output escaped with `htmlspecialchars`
- CSRF tokens on every form and state-changing API call
- bcrypt password hashing, session ID regeneration on sign-in, HTTP-only cookies
- Sign-in, password-reset, contact and coupon throttling
- One-time, expiring, hashed password-reset tokens
- Image uploads checked by content type and stored where PHP cannot execute
- Sensitive folders (`includes/`, `sql/`, `logs/`, `scripts/`, tooling files) blocked in `.htaccess`
- Dashboard warning while the default admin password is in use

---

## 📄 License

Distributed under the **MIT License**. See [`LICENSE`](LICENSE) for details.

## 🙏 Credits

- **Original landing page concept** by [Bedimcode](https://www.youtube.com/c/Bedimcode)
- **Full-stack e-commerce build** by **NAKOLA** — Made with ❤️ in Nairobi
