<?php
// config/db.php – sets up PDO connection and creates the database if it does not exist.
date_default_timezone_set('Asia/Kuala_Lumpur');

$host = '127.0.0.1';
$db   = 'fashion_store';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Attempt to connect to the specified database
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // If the database does not exist (error code 1049), create it automatically
    if ($e->getCode() == 1049) {
        // Connect without specifying a database
        $dsnTmp = "mysql:host=$host;charset=$charset";
        $pdoTmp = new PDO($dsnTmp, $user, $pass, $options);
        $pdoTmp->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdoTmp = null;
        // Reconnect to the newly created database
        $pdo = new PDO($dsn, $user, $pass, $options);
    } else {
        // Rethrow any other exception
        throw $e;
    }
}

require_once __DIR__ . '/../includes/order_status_sync.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    syncOrderStatuses($pdo);
}
?>
