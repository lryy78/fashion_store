<?php
require_once 'config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT image_data, mime_type FROM product_images WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetch();

    if ($image && $image['image_data']) {
        header("Content-Type: " . ($image['mime_type'] ?: "image/png"));
        echo $image['image_data'];
        exit;
    }
}

// Fallback image if not found
header("Content-Type: image/png");
readfile("assets/img/hero.png"); // or any default placeholder
?>
