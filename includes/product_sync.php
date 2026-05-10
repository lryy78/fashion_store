<?php
// product_sync.php
// Automatically publishes scheduled products when their release time is reached

require_once __DIR__ . '/../config/db.php';

try {
    // Update products that are scheduled and have reached their publish time
    $stmt = $pdo->prepare("UPDATE products SET status = 'published' WHERE status = 'scheduled' AND publish_at <= NOW()");
    $stmt->execute();
} catch (PDOException $e) {
    // Silently fail or log error
    error_log("Product Sync Error: " . $e->getMessage());
}
?>
