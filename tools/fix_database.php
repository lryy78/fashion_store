<?php
// fix_database.php - Utility to add missing columns and tables safely

require_once 'config/db.php';

echo "<h2>Repairing HypeThread Database...</h2>";

try {
    // 1. Fix Users Table
    $users_cols = [
        "is_active" => "ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1",
        "reset_token" => "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL",
        "reset_expires" => "ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL"
    ];

    foreach ($users_cols as $col => $sql) {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'")->fetch();
        if (!$check) {
            $pdo->exec($sql);
            echo "✔ Added column '$col' to 'users' table.<br>";
        } else {
            echo "• Column '$col' already exists in 'users' table.<br>";
        }
    }

    // 2. Fix Products Table
    $products_cols = [
        "size_chart" => "ALTER TABLE products ADD COLUMN size_chart TEXT AFTER description",
        "status" => "ALTER TABLE products ADD COLUMN status ENUM('published', 'draft', 'scheduled') DEFAULT 'published' AFTER gender",
        "publish_at" => "ALTER TABLE products ADD COLUMN publish_at TIMESTAMP NULL DEFAULT NULL AFTER status",
        "views" => "ALTER TABLE products ADD COLUMN views INT DEFAULT 0 AFTER is_featured"
    ];

    foreach ($products_cols as $col => $sql) {
        $check = $pdo->query("SHOW COLUMNS FROM products LIKE '$col'")->fetch();
        if (!$check) {
            $pdo->exec($sql);
            echo "✔ Added column '$col' to 'products' table.<br>";
        } else {
            echo "• Column '$col' already exists in 'products' table.<br>";
        }
    }

    // 3. Add Missing Tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT
    )");
    echo "✔ Table 'system_settings' checked/created.<br>";

    $pdo->exec("CREATE TABLE IF NOT EXISTS system_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50),
        priority ENUM('info', 'warning', 'critical'),
        message TEXT,
        reference_id INT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✔ Table 'system_alerts' checked/created.<br>";

    // 4. Initialize Settings
    $pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
        ('low_stock_threshold', '10'),
        ('overstock_threshold', '100'),
        ('dashboard_active_alerts', 'out_of_stock,low_stock,overstock')");
    echo "✔ System settings initialized.<br>";

    echo "<h3>Database repair complete!</h3>";
    echo "<p>The 'Fatal Error' should now be resolved. You can safely delete this file.</p>";
    echo "<a href='index.php' style='padding: 10px 20px; background: #000; color: #fff; text-decoration: none;'>Go to Home</a>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>Repair Failed</h3>";
    echo "Error: " . $e->getMessage();
}
?>
