<?php
/**
 * Headphones1.Duka — Database Migrations
 * Brings databases created from an older sql/schema.sql up to date.
 * Every step checks first, so running them again is harmless.
 *
 * Run from Admin → Settings → System, or: php scripts/migrate.php
 */

function tableExists(PDO $db, string $table): bool {
    $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function columnExists(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Ordered list of migrations: description => [isApplied(PDO): bool, apply(PDO): void]
 */
function migrationSteps(): array {
    return [
        'Add discount and coupon columns to orders' => [
            fn(PDO $db) => columnExists($db, 'orders', 'discount'),
            function (PDO $db) {
                $db->exec("ALTER TABLE orders
                    ADD COLUMN discount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER subtotal,
                    ADD COLUMN coupon_code VARCHAR(40) NULL AFTER discount");
            },
        ],
        'Add payment reference column to orders' => [
            fn(PDO $db) => columnExists($db, 'orders', 'payment_reference'),
            fn(PDO $db) => $db->exec("ALTER TABLE orders ADD COLUMN payment_reference VARCHAR(100) NULL AFTER payment_status"),
        ],
        'Create coupons table' => [
            fn(PDO $db) => tableExists($db, 'coupons'),
            function (PDO $db) {
                $db->exec("CREATE TABLE coupons (
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
                ) ENGINE=InnoDB");
                if (DEMO_MODE) {
                    $db->exec("INSERT INTO coupons (code, description, type, value, once_per_customer, expires_at) VALUES
                        ('HOLIDAY10', 'Holiday sale — 10% off any order', 'percent', 10, 0, '2026-12-31 23:59:59'),
                        ('WELCOME10', 'Newsletter welcome — 10% off a first order', 'percent', 10, 1, NULL)");
                }
            },
        ],
        'Create testimonials table' => [
            fn(PDO $db) => tableExists($db, 'testimonials'),
            function (PDO $db) {
                $db->exec("CREATE TABLE testimonials (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    role VARCHAR(100) NULL,
                    quote TEXT NOT NULL,
                    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
                    sort_order INT NOT NULL DEFAULT 0,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB");
                if (DEMO_MODE) {
                    $db->exec(demoTestimonialsSql());
                }
            },
        ],
        'Create auth_attempts table (login throttling)' => [
            fn(PDO $db) => tableExists($db, 'auth_attempts'),
            function (PDO $db) {
                $db->exec("CREATE TABLE auth_attempts (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    action VARCHAR(30) NOT NULL,
                    attempt_key VARCHAR(255) NOT NULL,
                    ip VARCHAR(45) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_action_key (action, attempt_key, created_at),
                    INDEX idx_action_ip (action, ip, created_at)
                ) ENGINE=InnoDB");
            },
        ],
        'Add unsubscribe token to newsletter' => [
            fn(PDO $db) => columnExists($db, 'newsletter', 'token'),
            function (PDO $db) {
                $db->exec("ALTER TABLE newsletter ADD COLUMN token VARCHAR(64) NULL UNIQUE AFTER is_active");
                $db->exec("UPDATE newsletter SET token = SHA2(CONCAT(id, email, RAND()), 256) WHERE token IS NULL");
            },
        ],
    ];
}

/**
 * Sample testimonials shown on the demo store (shared with sql/schema.sql).
 */
function demoTestimonialsSql(): string {
    return "INSERT INTO testimonials (name, role, quote, rating, sort_order) VALUES
        ('James Rodriguez', 'Music Producer', 'The sound quality is absolutely insane. These are now my daily drivers in the studio. The bass is deep without being muddy.', 5, 1),
        ('Sarah Kim', 'Travel Blogger', '40 hours of battery is no joke. I use these on long flights and still have charge left when I land.', 5, 2),
        ('Marcus Thompson', 'Tech Reviewer', 'The build quality feels premium, the cushions are like clouds, and they fold flat for my backpack.', 4, 3),
        ('Aisha Nakamura', 'Audio Engineer', 'Clear mids, controlled highs and a fun low end. They look great too.', 5, 4),
        ('Carlos Perez', 'Fitness Coach', 'Fast charging is a lifesaver. Ten minutes on the cable gave me enough for my whole workout.', 5, 5)";
}

/**
 * Migrations that have not been applied yet.
 */
function pendingMigrations(PDO $db): array {
    $pending = [];
    foreach (migrationSteps() as $label => [$isApplied]) {
        if (!$isApplied($db)) $pending[] = $label;
    }
    return $pending;
}

/**
 * Apply all pending migrations. Returns the labels that ran.
 */
function runMigrations(PDO $db): array {
    $applied = [];
    foreach (migrationSteps() as $label => [$isApplied, $apply]) {
        if ($isApplied($db)) continue;
        $apply($db);
        $applied[] = $label;
    }
    return $applied;
}
