<?php
require_once 'config/db.php';
echo "<pre>";
$query = "
    SELECT oi.order_id, p.name, oi.price, p.cost_price, (oi.price - p.cost_price) as unit_profit
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    WHERE (oi.price - p.cost_price) < 0
    LIMIT 20
";
print_r($pdo->query($query)->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>
