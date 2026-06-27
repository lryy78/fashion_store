<?php
// Configure the local XAMPP database connection.

$host = '127.0.0.1';
$user = 'root';
$pass = '';

// Apply the schema first, then compatibility updates, then demo data.
$databaseFiles = [
    'Schema' => __DIR__ . '/database/schema.sql',
    'Compatibility migration' => __DIR__ . '/database/migrations/001_compatibility.sql',
    'Demo data' => __DIR__ . '/database/seed.sql',
    'Catalogue images' => __DIR__ . '/database/migrations/002_catalog_images.sql',
];

// Connect to MySQL using the default local XAMPP credentials.
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);

    echo '<h2>Setting up HypeThread Database...</h2>';

    // Execute each SQL file in order and stop if a file is missing or unreadable.
    foreach ($databaseFiles as $label => $path) {
        if (!is_file($path)) {
            throw new RuntimeException("Missing database file: $path");
        }

        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException("Unable to read database file: $path");
        }

        $pdo->exec($sql);
        echo '&#10003; ' . htmlspecialchars($label) . ' applied successfully.<br>';
    }

    echo '<h3>Setup Complete!</h3>';
    echo "<p><a href='index.php' style='padding:10px 20px;background:#000;color:#fff;text-decoration:none;'>Go to Website</a></p>";
// Show a readable setup error instead of a raw exception.
} catch (Throwable $e) {
    echo "<h3 style='color:red;'>Setup Failed</h3>";
    echo 'Error: ' . htmlspecialchars($e->getMessage()) . '<br><br>';
    echo 'Make sure MySQL is running in XAMPP and the root credentials are correct.';
}
