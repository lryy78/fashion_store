<?php
require_once 'c:/xampp/htdocs/fashion_store/config/db.php';

try {
    // 1. Add columns to vouchers table
    $pdo->exec("ALTER TABLE vouchers 
        ADD COLUMN min_spend DECIMAL(10,2) DEFAULT 0.00 AFTER discount_value,
        ADD COLUMN usage_limit INT DEFAULT NULL AFTER expiry_date,
        ADD COLUMN is_one_time BOOLEAN DEFAULT TRUE AFTER usage_limit,
        ADD COLUMN target_type ENUM('all', 'specific', 'group') DEFAULT 'all' AFTER is_one_time,
        ADD COLUMN target_user_id INT DEFAULT NULL AFTER target_type,
        ADD COLUMN target_group ENUM('new', 'repeat', 'vip', 'inactive') DEFAULT NULL AFTER target_user_id
    ");

    // 2. Create voucher_redemptions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS voucher_redemptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        voucher_id INT,
        user_id INT,
        order_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    // 3. Add voucher_id to orders table for easier revenue tracking
    $pdo->exec("ALTER TABLE orders ADD COLUMN voucher_id INT DEFAULT NULL AFTER user_id");
    $pdo->exec("ALTER TABLE orders ADD FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE SET NULL");

    echo "Migration successful!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
