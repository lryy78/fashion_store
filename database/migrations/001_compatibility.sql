-- Upgrade older databases without deleting existing data.
USE fashion_store;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER phone,
    ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) DEFAULT NULL AFTER is_active,
    ADD COLUMN IF NOT EXISTS reset_expires DATETIME DEFAULT NULL AFTER reset_token;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS size_chart TEXT DEFAULT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price,
    ADD COLUMN IF NOT EXISTS discount_price DECIMAL(10,2) DEFAULT NULL AFTER cost_price,
    ADD COLUMN IF NOT EXISTS status ENUM('published','draft','scheduled') NOT NULL DEFAULT 'published' AFTER gender,
    ADD COLUMN IF NOT EXISTS publish_at DATETIME DEFAULT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS views INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_featured;

ALTER TABLE vouchers
    ADD COLUMN IF NOT EXISTS campaign VARCHAR(100) DEFAULT NULL AFTER code,
    ADD COLUMN IF NOT EXISTS min_spend DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER discount_value,
    ADD COLUMN IF NOT EXISTS usage_limit INT UNSIGNED DEFAULT NULL AFTER expiry_date,
    ADD COLUMN IF NOT EXISTS is_one_time TINYINT(1) NOT NULL DEFAULT 1 AFTER usage_limit,
    ADD COLUMN IF NOT EXISTS is_used TINYINT(1) NOT NULL DEFAULT 0 AFTER is_one_time,
    ADD COLUMN IF NOT EXISTS user_id INT UNSIGNED DEFAULT NULL AFTER is_active,
    ADD COLUMN IF NOT EXISTS target_type ENUM('all','specific','group') NOT NULL DEFAULT 'all' AFTER user_id,
    ADD COLUMN IF NOT EXISTS target_user_id INT UNSIGNED DEFAULT NULL AFTER target_type,
    ADD COLUMN IF NOT EXISTS target_group ENUM('new','repeat','vip','inactive','reviewers') DEFAULT NULL AFTER target_user_id;

ALTER TABLE vouchers
    MODIFY COLUMN target_group ENUM('new','repeat','vip','inactive','reviewers') DEFAULT NULL;

ALTER TABLE orders ADD COLUMN IF NOT EXISTS voucher_id INT UNSIGNED DEFAULT NULL AFTER user_id;
UPDATE orders SET status = 'processing' WHERE status = 'cancel_requested';
ALTER TABLE orders MODIFY COLUMN status ENUM('pending','processing','shipped','completed','refund_requested','cancelled','refunded') NOT NULL DEFAULT 'pending';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS completed_at DATETIME DEFAULT NULL AFTER address;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS stock_restored TINYINT(1) NOT NULL DEFAULT 0 AFTER completed_at;
UPDATE orders SET completed_at = created_at WHERE status IN ('completed','refund_requested','refunded') AND completed_at IS NULL;
UPDATE orders SET stock_restored = 1 WHERE status IN ('cancelled','refunded') AND stock_restored = 0;

DELETE FROM products
WHERE id IN (
    SELECT id FROM (
        SELECT p.id
        FROM products p
        LEFT JOIN product_variations pv ON pv.product_id = p.id
        WHERE pv.id IS NULL
          AND EXISTS (
              SELECT 1
              FROM products p2
              JOIN product_variations pv2 ON pv2.product_id = p2.id
              WHERE p2.name = p.name
                AND p2.gender = p.gender
                AND p2.id <> p.id
          )
    ) AS duplicate_products
);

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_alerts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    priority ENUM('critical','warning','info') NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    reference_id INT UNSIGNED DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alerts_read_priority (is_read, priority),
    INDEX idx_alerts_type_reference (type, reference_id),
    INDEX idx_alerts_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS faqs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS voucher_redemptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voucher_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED DEFAULT NULL,
    redeemed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_redemptions_voucher (voucher_id),
    INDEX idx_redemptions_user (user_id),
    INDEX idx_redemptions_order (order_id)
) ENGINE=InnoDB;
