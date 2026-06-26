-- HypeThread database schema (MariaDB 10.4+ / MySQL 8+)
CREATE DATABASE IF NOT EXISTS fashion_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fashion_store;

-- Core account and product catalogue tables.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('buyer','manager','admin','owner') NOT NULL DEFAULT 'buyer',
    full_name VARCHAR(100) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_reset_token (reset_token)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    size_chart TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    gender ENUM('Men','Women','Kids','Unisex') NOT NULL DEFAULT 'Unisex',
    status ENUM('published','draft','scheduled') NOT NULL DEFAULT 'published',
    publish_at DATETIME DEFAULT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_products_catalog (status, gender, category_id),
    INDEX idx_products_featured (is_featured)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_variations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    size VARCHAR(20) NOT NULL,
    color VARCHAR(40) NOT NULL,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_variations_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uq_product_variation (product_id, size, color),
    INDEX idx_variations_stock (stock_quantity)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    image_data LONGBLOB DEFAULT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    CONSTRAINT fk_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_images_product (product_id)
) ENGINE=InnoDB;

-- Promotion, order, cart, wishlist, support, review, and system tables.
CREATE TABLE IF NOT EXISTS vouchers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    discount_type ENUM('percentage','fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_spend DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    expiry_date DATE DEFAULT NULL,
    usage_limit INT UNSIGNED DEFAULT NULL,
    is_one_time TINYINT(1) NOT NULL DEFAULT 1,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    user_id INT UNSIGNED DEFAULT NULL,
    target_type ENUM('all','specific','group') NOT NULL DEFAULT 'all',
    target_user_id INT UNSIGNED DEFAULT NULL,
    target_group ENUM('new','repeat','vip','inactive','reviewers') DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vouchers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_vouchers_target_user FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_vouchers_available (is_active, is_used, expiry_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    voucher_id INT UNSIGNED DEFAULT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','processing','shipped','completed','refund_requested','cancelled','refunded') NOT NULL DEFAULT 'pending',
    address TEXT DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    stock_restored TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_orders_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE SET NULL,
    INDEX idx_orders_user_date (user_id, created_at),
    INDEX idx_orders_status_date (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    variation_id INT UNSIGNED DEFAULT NULL,
    quantity INT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_variation FOREIGN KEY (variation_id) REFERENCES product_variations(id) ON DELETE SET NULL,
    INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    variation_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_variation FOREIGN KEY (variation_id) REFERENCES product_variations(id) ON DELETE CASCADE,
    UNIQUE KEY uq_cart_item (user_id, variation_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wishlists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist_item (user_id, product_id),
    INDEX idx_wishlists_user_date (user_id, created_at),
    INDEX idx_wishlists_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enquiries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    response TEXT DEFAULT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_enquiries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_enquiries_status_date (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enquiry_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED DEFAULT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_enquiry FOREIGN KEY (enquiry_id) REFERENCES enquiries(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_messages_enquiry_date (enquiry_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT DEFAULT NULL,
    admin_reply TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY uq_order_product_review (order_id, product_id, user_id)
) ENGINE=InnoDB;

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
    CONSTRAINT fk_redemptions_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
    CONSTRAINT fk_redemptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_redemptions_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    UNIQUE KEY uq_voucher_order_redemption (voucher_id, order_id)
) ENGINE=InnoDB;
