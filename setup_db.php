<?php
// setup_db.php - Automatic Database Setup

$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password is empty

try {
    // 1. Connect to MySQL (without selecting a DB)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>Setting up HypeThread Database...</h2>";

    // 2. Create the Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS fashion_store");
    $pdo->exec("USE fashion_store");
    echo "✔ Database 'fashion_store' created.<br>";

    // 3. Import Schema
    $schema = file_get_contents('schema.sql');
    $pdo->exec($schema);
    echo "✔ Tables created successfully.<br>";

    // 4. Import Seed Data
    if (file_exists('seed_data.sql')) {
        $seed = file_get_contents('seed_data.sql');
        // PDO::exec can struggle with multiple queries in some environments.
        // We'll execute the seed data queries.
        try {
            $pdo->exec($seed);
            echo "✔ Demo data (products & users) imported.<br>";
        } catch (Exception $e) {
            echo "<i>Note: Seed data import might have partial success or conflict if run twice.</i><br>";
        }
    }

    echo "<h3>Setup Complete!</h3>";
    echo "<p><a href='index.php' style='padding: 10px 20px; background: #000; color: #fff; text-decoration: none;'>Go to Website</a></p>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Setup Failed</h3>";
    echo "Error: " . $e->getMessage() . "<br><br>";
    echo "Please make sure <b>MySQL</b> is started in your XAMPP Control Panel.";
}
?>
