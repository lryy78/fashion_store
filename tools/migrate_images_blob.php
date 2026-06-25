<?php
require_once 'c:/xampp/htdocs/fashion_store/config/db.php';

try {
    // 1. Add image_data column
    $pdo->exec("ALTER TABLE product_images ADD COLUMN image_data LONGBLOB AFTER image_path");
    $pdo->exec("ALTER TABLE product_images ADD COLUMN mime_type VARCHAR(50) AFTER image_data");
    
    echo "Migration successful!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
