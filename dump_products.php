<?php
$pdo = new PDO('mysql:host=localhost;dbname=fashion_store', 'root', '');
$stmt = $pdo->query("SELECT p.id, p.name, p.gender, p.price, (SELECT id FROM product_images WHERE product_id = p.id LIMIT 1) as image_id FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE c.name = 'Tops'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
