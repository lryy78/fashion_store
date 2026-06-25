<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    echo "Column added.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
