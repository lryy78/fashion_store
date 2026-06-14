<?php
require_once 'config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT image_path, image_data, mime_type FROM product_images WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetch();

    if ($image && $image['image_data']) {
        header("Content-Type: " . ($image['mime_type'] ?: "image/png"));
        echo $image['image_data'];
        exit;
    }

    if ($image && !empty($image['image_path'])) {
        if (preg_match('/^https?:\/\//i', $image['image_path'])) {
            header("Location: " . $image['image_path']);
            exit;
        }

        $path = __DIR__ . '/' . ltrim(str_replace('\\', '/', $image['image_path']), '/');
        if (is_file($path)) {
            header("Content-Type: " . (mime_content_type($path) ?: "image/png"));
            readfile($path);
            exit;
        }
    }
}

// Fallback image if not found
header("Content-Type: image/png");
readfile("assets/img/hero.png"); // or any default placeholder
?>
