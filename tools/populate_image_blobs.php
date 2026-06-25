<?php
require_once 'c:/xampp/htdocs/fashion_store/config/db.php';

$stmt = $pdo->query("SELECT id, image_path FROM product_images WHERE image_data IS NULL");
$images = $stmt->fetchAll();

echo "Processing " . count($images) . " images...\n";

foreach ($images as $img) {
    $path = $img['image_path'];
    $id = $img['id'];
    
    echo "Processing ID $id: $path\n";
    
    // Check if it's a URL or local path
    if (strpos($path, 'http') === 0) {
        $content = @file_get_contents($path);
    } else {
        $local_path = "c:/xampp/htdocs/fashion_store/" . $path;
        $content = @file_get_contents($local_path);
    }
    
    if ($content !== false) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($content);
        
        $update = $pdo->prepare("UPDATE product_images SET image_data = ?, mime_type = ? WHERE id = ?");
        $update->execute([$content, $mime, $id]);
        echo "✅ Saved ID $id\n";
    } else {
        echo "❌ Failed to fetch ID $id\n";
    }
}

echo "All done!\n";
?>
