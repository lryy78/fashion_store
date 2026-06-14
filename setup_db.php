<?php
// Organized database installer for local XAMPP development.

$host = '127.0.0.1';
$user = 'root';
$pass = '';

$databaseFiles = [
    'Schema' => __DIR__ . '/database/schema.sql',
    'Compatibility migration' => __DIR__ . '/database/migrations/001_compatibility.sql',
    'Demo data' => __DIR__ . '/database/seed.sql',
];

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);

    echo '<h2>Setting up HypeThread Database...</h2>';

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
} catch (Throwable $e) {
    echo "<h3 style='color:red;'>Setup Failed</h3>";
    echo 'Error: ' . htmlspecialchars($e->getMessage()) . '<br><br>';
    echo 'Make sure MySQL is running in XAMPP and the root credentials are correct.';
}
