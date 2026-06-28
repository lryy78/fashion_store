<?php
// setup_db.php – Web interface to (re)initialize the Fashion Store database.
// Imports schema.sql (DDL) first, then data.sql (DML) for a clean two-step setup.

require_once __DIR__ . '/config/db.php'; // provides $pdo

$schemaPath = __DIR__ . '/database/schema.sql';
$dataPath   = __DIR__ . '/database/data.sql';

function renderHeader() {
    echo "<!DOCTYPE html><html><head><title>Database Setup</title>";
    echo "<style>body{font-family:Arial,sans-serif;background:#f5f5f5;padding:2rem;}";
    echo "button{padding:0.6rem 1.2rem;font-size:1rem;background:#007bff;color:#fff;border:none;border-radius:4px;cursor:pointer;}";
    echo "button:hover{background:#0056b3;}</style>";
    echo "</head><body>";
}

function renderFooter() {
    echo "</body></html>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify both SQL files exist
    $missing = [];
    if (!is_file($schemaPath)) $missing[] = 'schema.sql';
    if (!is_file($dataPath))   $missing[] = 'data.sql';

    if (!empty($missing)) {
        renderHeader();
        echo "<h2 style='color:red;'>Setup Failed</h2>";
        echo "<p>Missing SQL file(s): " . htmlspecialchars(implode(', ', $missing)) . "</p>";
        renderFooter();
        exit;
    }

    // Read both files
    $schemaSql = file_get_contents($schemaPath);
    $dataSql   = file_get_contents($dataPath);

    if ($schemaSql === false || $dataSql === false) {
        renderHeader();
        echo "<h2 style='color:red;'>Setup Failed</h2>";
        echo "<p>Unable to read one or more SQL files.</p>";
        renderFooter();
        exit;
    }

    try {
        // Step 1: Create/recreate all tables (schema)
        $pdo->exec($schemaSql);

        // Step 2: Insert seed data
        $pdo->exec($dataSql);

        renderHeader();
        echo "<h2>Database setup complete</h2>";
        echo "<p>Schema and seed data imported successfully.</p>";
        echo "<p><a href='index.php' style='display:inline-block;padding:10px 20px;background:#000;color:#fff;text-decoration:none;'>Go to Website</a></p>";
        renderFooter();
    } catch (Throwable $e) {
        renderHeader();
        echo "<h2 style='color:red;'>Setup Failed</h2>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Ensure MySQL is running in XAMPP and credentials are correct.</p>";
        renderFooter();
    }
} else {
    // Show setup button
    renderHeader();
    echo "<h2>Initialize Database</h2>";
    echo "<p>This will drop and recreate the <code>fashion_store</code> database with seed data.</p>";
    echo "<form method='POST'><button type='submit'>Run Setup</button></form>";

    // Optional: data-only reset via ?reset=true
    if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $tables = [
            'cart', 'order_items', 'orders', 'reviews',
            'enquiry_messages', 'enquiries',
            'voucher_redemptions', 'vouchers',
            'product_images', 'product_variations', 'products',
            'categories', 'users', 'system_settings', 'system_alerts', 'faqs',
        ];
        foreach ($tables as $t) {
            $pdo->exec("TRUNCATE TABLE `$t`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        echo '<p style="color:green;">Database tables truncated. Run setup again to re-seed data.</p>';
    }

    renderFooter();
}
?>
