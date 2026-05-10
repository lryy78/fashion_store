<?php
require_once 'config/db.php';
echo "<pre>";
print_r($pdo->query('SELECT id, name, price, cost_price FROM products LIMIT 20')->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
