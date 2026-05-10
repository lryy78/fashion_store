<?php
require_once 'config/db.php';

try {
    // Check if column exists first to avoid errors on multiple runs
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'voucher_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN voucher_id INT NULL");
        $pdo->exec("ALTER TABLE orders ADD CONSTRAINT fk_order_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE SET NULL");
        echo "voucher_id column added.\n";
        
        // Let's seed some dummy voucher_ids to existing orders so we have data
        // Get available vouchers
        $vouchers = $pdo->query("SELECT id FROM vouchers LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($vouchers)) {
            // Assign random vouchers to ~30% of existing orders
            $orders = $pdo->query("SELECT id FROM orders")->fetchAll(PDO::FETCH_COLUMN);
            $update_stmt = $pdo->prepare("UPDATE orders SET voucher_id = ? WHERE id = ?");
            
            $seeded_count = 0;
            foreach ($orders as $oid) {
                if (rand(1, 100) <= 30) {
                    $vid = $vouchers[array_rand($vouchers)];
                    $update_stmt->execute([$vid, $oid]);
                    $seeded_count++;
                    
                    // also mark the voucher as used by some user
                    $pdo->exec("UPDATE vouchers SET is_used = 1 WHERE id = " . (int)$vid);
                }
            }
            echo "Seeded $seeded_count orders with random vouchers.\n";
        }
    } else {
        echo "voucher_id already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
