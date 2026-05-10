<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$product_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product_name = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $image_path = '';
    
    // Handle File Upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $target_dir = "../assets/uploads/products/";
        $db_dir = "assets/uploads/products/";
        $file_extension = pathinfo($_FILES["image_file"]["name"], PATHINFO_EXTENSION);
        $new_filename = "prod_" . time() . "_" . uniqid() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Simple validation
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($file_extension), $allowed_types)) {
            if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_file)) {
                $image_path = $db_dir . $new_filename;
            }
        }
    } 

    if (!empty($image_path)) {
        $content = file_get_contents($target_file);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($content);
        
        $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, image_data, mime_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$product_id, $image_path, $content, $mime]);
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?");
    $stmt->execute([$delete_id, $product_id]);
    header("Location: manage_images.php?id=" . $product_id);
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<?php require_once '../includes/sidebar.php'; ?>
<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="margin-bottom: 40px;">
            <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Media Library</div>
            <h1 style="margin: 0; font-size: 40px;">Manage Images: <?php echo htmlspecialchars($product_name); ?></h1>
        </header>

        <div class="dashboard-split" style="gap: 32px;">
            <div class="surface-card" style="padding: 32px; border: 1px solid var(--colors-hairline);">
                <h3 style="font-size: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--colors-hairline-soft); padding-bottom: 12px;">Product Gallery</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px; margin-top: 24px;">
                    <?php if (empty($images)): ?>
                        <div style="grid-column: 1/-1; padding: 40px; text-align: center; color: var(--colors-muted); background: var(--colors-surface-soft); border-radius: 8px;">
                            No images uploaded for this piece yet.
                        </div>
                    <?php endif; ?>
                    <?php foreach ($images as $img): ?>
                        <div style="position: relative; group">
                            <img src="../get_image.php?id=<?php echo $img['id']; ?>" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 8px; border: 1px solid var(--colors-hairline-soft);">
                            <a href="manage_images.php?id=<?php echo $product_id; ?>&delete_id=<?php echo $img['id']; ?>" 
                               style="position: absolute; top: 8px; right: 8px; background: rgba(255,0,0,0.8); color: #fff; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; text-decoration: none; box-shadow: 0 2px 8px rgba(0,0,0,0.2);"
                               onclick="return confirm('Remove this image?')">&times;</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="surface-card" style="height: fit-content; padding: 32px; border: 1px solid var(--colors-hairline);">
                <h3 style="font-size: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--colors-hairline-soft); padding-bottom: 12px;">Add Media</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Upload from Device</label>
                        <input type="file" name="image_file" accept="image/*" required class="form-input" style="padding: 8px;">
                    </div>
                    <button type="submit" class="button-primary" style="width: 100%; margin-top: 12px;">Process Media</button>
                </form>
                <div style="margin-top: 24px;">
                    <a href="edit_product.php?id=<?php echo $product_id; ?>" class="button-secondary" style="width: 100%; display: block; text-align: center; padding: 12px;">Back to Product</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
