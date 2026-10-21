-- ============================================
-- Headphones1.Duka — Database Schema
-- Run this in phpMyAdmin or MySQL CLI (fresh installs only).
-- Existing databases: run `php scripts/migrate.php` or use
-- Admin → Settings → System instead of re-importing.
-- Shared hosting: if your host already created the database, delete the
-- CREATE DATABASE and USE lines below and import into that database.
-- ============================================

CREATE DATABASE IF NOT EXISTS headphones1_duka
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE headphones1_duka;

-- ============ CATEGORIES ============
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- ============ PRODUCTS ============
CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    description TEXT NULL,
    short_description VARCHAR(500) NULL,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NULL,
    image VARCHAR(255) NOT NULL,
    color VARCHAR(50) NULL,
    color_hex VARCHAR(100) NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    sku VARCHAR(50) NULL UNIQUE,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_featured (featured),
    INDEX idx_active (is_active),
    INDEX idx_category (category_id)
) ENGINE=InnoDB;

-- ============ PRODUCT IMAGES ============
CREATE TABLE product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- ============ USERS ============
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    reset_token VARCHAR(100) NULL,
    reset_expires DATETIME NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- ============ SESSIONS / CART (Guest + Logged-in) ============
CREATE TABLE cart (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NULL,
    user_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_product (cart_id, product_id),
    INDEX idx_cart (cart_id)
) ENGINE=InnoDB;

-- ============ ORDERS ============
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    order_number VARCHAR(30) NOT NULL UNIQUE,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    coupon_code VARCHAR(40) NULL,
    shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    -- Shipping info
    shipping_name VARCHAR(100) NOT NULL,
    shipping_email VARCHAR(255) NOT NULL,
    shipping_phone VARCHAR(20) NULL,
    shipping_address VARCHAR(255) NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_state VARCHAR(100) NULL,
    shipping_zip VARCHAR(20) NOT NULL,
    shipping_country VARCHAR(100) NOT NULL DEFAULT 'United States',
    -- Payment
    payment_method VARCHAR(50) NOT NULL DEFAULT 'card',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    payment_reference VARCHAR(100) NULL,
    -- Tracking
    tracking_number VARCHAR(100) NULL,
    -- Notes
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(200) NOT NULL,
    product_image VARCHAR(255) NULL,
    quantity INT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- ============ ORDER STATUS HISTORY ============
CREATE TABLE order_status_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(20) NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_by INT UNSIGNED NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============ WISHLISTS ============
CREATE TABLE wishlists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ============ USER ADDRESSES ============
CREATE TABLE user_addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    label VARCHAR(50) NOT NULL DEFAULT 'Home',
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NULL,
    zip VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'United States',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ============ SITE SETTINGS ============
CREATE TABLE site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB;

-- ============ NEWSLETTER ============
CREATE TABLE newsletter (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    token VARCHAR(64) NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ============ COUPONS ============
CREATE TABLE coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
    value DECIMAL(10,2) NOT NULL,
    min_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    max_uses INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    once_per_customer TINYINT(1) NOT NULL DEFAULT 0,
    starts_at DATETIME NULL,
    expires_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============ TESTIMONIALS ============
CREATE TABLE testimonials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NULL,
    quote TEXT NOT NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============ AUTH ATTEMPTS (login / reset throttling) ============
CREATE TABLE auth_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(30) NOT NULL,
    attempt_key VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action_key (action, attempt_key, created_at),
    INDEX idx_action_ip (action, ip, created_at)
) ENGINE=InnoDB;

-- ============ CONTACT MESSAGES ============
CREATE TABLE contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- SEED DATA — Default Categories
-- ============================================
INSERT INTO categories (name, slug, description) VALUES
    ('Headphones', 'headphones', 'Premium over-ear and on-ear headphones'),
    ('Earphones', 'earphones', 'Wired and wireless in-ear earphones'),
    ('Earbuds', 'earbuds', 'True wireless earbuds'),
    ('Accessories', 'accessories', 'Cases, cables, and audio accessories');

-- ============================================
-- SEED DATA — Products (Beats 3 Lineup)
-- ============================================
INSERT INTO products (category_id, name, slug, description, short_description, price, sale_price, image, color, color_hex, stock, sku, featured) VALUES
    (1, 'Beats 3 — Black', 'beats3-black', 'Studio-grade wireless headphones with 40h battery, Bluetooth 5.2, and premium sound. The Black edition features a sleek matte finish perfect for any style.', 'Studio-grade wireless headphones with 40h battery life.', 249.00, NULL, 'assets/img/product1.png', 'Black', '#1a1a1a', 47, 'BEATS3-BLK', 1),
    (1, 'Beats 3 — Red Black', 'beats3-redblack', 'Studio-grade wireless headphones with 40h battery, Bluetooth 5.2, and premium sound. The Red Black edition makes a bold statement with its dual-tone design.', 'Bold dual-tone design with studio-grade sound.', 249.00, NULL, 'assets/img/product2.png', 'Red Black', 'linear-gradient(135deg, #dc2626 50%, #1a1a1a 50%)', 35, 'BEATS3-RBK', 0),
    (1, 'Beats 3 — Night Black', 'beats3-nightblack', 'Studio-grade wireless headphones with 40h battery, Bluetooth 5.2, and premium sound. The Night Black edition features an ultra-dark finish for understated elegance.', 'Ultra-dark finish for understated elegance.', 249.00, NULL, 'assets/img/product3.png', 'Night Black', '#0f0f0f', 42, 'BEATS3-NBK', 0),
    (1, 'Beats 3 — Blue', 'beats3-blue', 'Studio-grade wireless headphones with 40h battery, Bluetooth 5.2, and premium sound. The Blue edition brings a vibrant pop of color to your audio experience.', 'Vibrant blue with premium sound quality.', 249.00, NULL, 'assets/img/product4.png', 'Blue', '#2563eb', 29, 'BEATS3-BLU', 1),
    (1, 'Beats 3 — Twilight Gray', 'beats3-gray', 'Studio-grade wireless headphones with 40h battery, Bluetooth 5.2, and premium sound. The Twilight Gray edition offers a sophisticated neutral tone.', 'Sophisticated neutral tone with premium comfort.', 249.00, NULL, 'assets/img/product5.png', 'Twilight Gray', '#6b7280', 38, 'BEATS3-GRY', 0);

-- ============================================
-- SEED DATA — Default Admin User
-- Email: admin@beats3.store · Password: Admin@123
-- Change both right after your first login (Admin → My Profile);
-- the dashboard shows a warning until you do.
-- ============================================
INSERT INTO users (name, email, password_hash, role) VALUES
    ('Admin', 'admin@beats3.store', '$2y$10$zD7OCUVTdLXQXklGC7oS/.rY5iihnXJ4F5C1hK3sjXbPcmCjrT2f2', 'admin');

-- ============================================
-- SITE SETTINGS
-- No rows needed: defaults live in settingDefaults() (includes/functions.php)
-- and anything saved in Admin → Settings is stored in site_settings.
-- ============================================

-- ============================================
-- SEED DATA — Demo coupons and sample testimonials
-- Replace with your own in Admin → Coupons and Admin → Testimonials.
-- ============================================
INSERT INTO coupons (code, description, type, value, once_per_customer, expires_at) VALUES
    ('HOLIDAY10', 'Holiday sale — 10% off any order', 'percent', 10, 0, '2026-12-31 23:59:59'),
    ('WELCOME10', 'Newsletter welcome — 10% off a first order', 'percent', 10, 1, NULL);

INSERT INTO testimonials (name, role, quote, rating, sort_order) VALUES
    ('James Rodriguez', 'Music Producer', 'The sound quality is absolutely insane. These are now my daily drivers in the studio. The bass is deep without being muddy.', 5, 1),
    ('Sarah Kim', 'Travel Blogger', '40 hours of battery is no joke. I use these on long flights and still have charge left when I land.', 5, 2),
    ('Marcus Thompson', 'Tech Reviewer', 'The build quality feels premium, the cushions are like clouds, and they fold flat for my backpack.', 4, 3),
    ('Aisha Nakamura', 'Audio Engineer', 'Clear mids, controlled highs and a fun low end. They look great too.', 5, 4),
    ('Carlos Perez', 'Fitness Coach', 'Fast charging is a lifesaver. Ten minutes on the cable gave me enough for my whole workout.', 5, 5);
