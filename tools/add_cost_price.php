<?php
require_once 'c:/xampp/htdocs/fashion_store/config/db.php';

try {
    // 1. Add cost_price to products table
    $pdo->exec("ALTER TABLE products ADD COLUMN cost_price DECIMAL(10,2) DEFAULT 0.00 AFTER price");
    
    // 2. Initialize some cost prices (e.g. 60% of selling price) for existing data
    $pdo->exec("UPDATE products SET cost_price = price * 0.60 WHERE cost_price = 0");

    echo "Migration successful!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
