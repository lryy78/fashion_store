<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL, ADD COLUMN reset_expires DATETIME NULL");
    echo "Columns added successfully.";
} catch (Exception $e) {
    echo "Error or already exists: " . $e->getMessage();
}
?>
