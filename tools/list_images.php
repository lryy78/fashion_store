<?php
require_once 'c:/xampp/htdocs/fashion_store/config/db.php';
$stmt = $pdo->query("SELECT * FROM product_images");
foreach($stmt->fetchAll() as $row) {
    echo $row['id'] . ": " . $row['image_path'] . "\n";
}
?>
