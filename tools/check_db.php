<?php
require_once 'c:/xampp/htdocs/fashion_store/config/db.php';
$stmt = $pdo->query("DESCRIBE vouchers");
print_r($stmt->fetchAll());
$stmt = $pdo->query("DESCRIBE orders");
print_r($stmt->fetchAll());
?>
