<?php
require_once 'config/db.php';
$stmt = $pdo->query("
    SELECT user_id, u.username, COUNT(o.id) as order_count 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.status NOT IN ('cancelled','refunded') 
    GROUP BY user_id
");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results, JSON_PRETTY_PRINT);
