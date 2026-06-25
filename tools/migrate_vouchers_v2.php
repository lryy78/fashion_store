<?php
require_once 'c:/xampp/htdocs/fashion_store/config/db.php';

try {
    // Check if voucher_redemptions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'voucher_redemptions'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE voucher_redemptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            voucher_id INT,
            user_id INT,
            order_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )");
        echo "voucher_redemptions table created.\n";
    }

    echo "Migration complete!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
