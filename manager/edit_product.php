<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $gender = $_POST['gender'];
    $description = $_POST['description'];
    $size_chart = $_POST['size_chart'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $publish_at = !empty($_POST['publish_at']) ? $_POST['publish_at'] : null;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, gender = ?, description = ?, size_chart = ?, price = ?, status = ?, publish_at = ?, is_featured = ? WHERE id = ?");
    $stmt->execute([$name, $category_id, $gender, $description, $size_chart, $price, $status, $publish_at, $is_featured, $id]);
    
    header("Location: products_list.php?msg=Product updated");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$include_path = '../includes/';
include $include_path . 'header.php';
?>

<?php require_once '../includes/sidebar.php'; ?>
<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="margin-bottom: 40px;">
            <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">HypeThread Creative</div>
            <h1 style="margin: 0; font-size: 40px;">Edit Piece</h1>
        </header>

        <div class="surface-card" style="max-width: 900px; padding: 40px; border: 1px solid var(--colors-hairline);">
            <h3 style="font-size: 18px; margin-bottom: 32px; border-bottom: 1px solid var(--colors-hairline-soft); padding-bottom: 16px;">Product Specification</h3>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Piece Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="form-input">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px;">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" required class="form-input">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (RM)</label>
                        <input type="number" name="price" step="0.01" value="<?php echo $product['price']; ?>" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender Focus</label>
                        <select name="gender" required class="form-input">
                            <option value="Unisex" <?php echo $product['gender'] == 'Unisex' ? 'selected' : ''; ?>>Unisex Focus</option>
                            <option value="Men" <?php echo $product['gender'] == 'Men' ? 'selected' : ''; ?>>Men's Collection</option>
                            <option value="Women" <?php echo $product['gender'] == 'Women' ? 'selected' : ''; ?>>Women's Collection</option>
                            <option value="Kids" <?php echo $product['gender'] == 'Kids' ? 'selected' : ''; ?>>Kids' Collection</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description & Narrative</label>
                    <textarea name="description" rows="6" class="form-input"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div style="background: var(--colors-surface-soft); padding: 24px; border-radius: 12px; margin: 32px 0; border: 1px solid var(--colors-hairline-soft);">
                    <h4 style="margin-top: 0; color: var(--colors-ink); text-transform: uppercase; letter-spacing: 0.1em; font-size: 12px; margin-bottom: 20px;">Publishing & Lifecycle</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; background: #fff; padding: 20px; border-radius: 8px;">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Lifecycle Status</label>
                            <select name="status" class="form-input">
                                <option value="published" <?php echo $product['status'] == 'published' ? 'selected' : ''; ?>>Published Immediately</option>
                                <option value="draft" <?php echo $product['status'] == 'draft' ? 'selected' : ''; ?>>Save as Draft</option>
                                <option value="scheduled" <?php echo $product['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled Release</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Release Date & Time</label>
                            <input type="datetime-local" name="publish_at" value="<?php echo $product['publish_at'] ? date('Y-m-d\TH:i', strtotime($product['publish_at'])) : ''; ?>" class="form-input" style="background: var(--colors-surface-soft); font-weight: 600;">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label class="form-label" style="margin: 0;">Size Architecture</label>
                        <button type="button" onclick="applyDefaultGuide()" class="button-secondary" style="font-size: 11px; padding: 4px 12px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Apply Default Guide</button>
                    </div>
                    <p style="font-size: 12px; color: var(--colors-muted); margin-bottom: 12px;">Modify the guide below to reflect the specific measurements for this piece.</p>
                    <textarea id="size_guide_area" name="size_chart" rows="12" class="form-input" style="font-family: var(--typography-code-font); font-size: 12px; line-height: 1.6;"><?php echo htmlspecialchars($product['size_chart']); ?></textarea>
                </div>

                <script>
                function applyDefaultGuide() {
                    const catSelect = document.querySelector('select[name="category_id"]');
                    const catName = catSelect.options[catSelect.selectedIndex].text.toLowerCase();
                    const area = document.getElementById('size_guide_area');
                    
                    if (catName.includes('footwear') || catName.includes('shoes')) {
                        area.value = `Footwear Size Conversion
Use the table below to find your perfect fit across international standards.

EU Size | US Men | US Women | UK | Length (cm)
36 | 4.5 | 6 | 3.5 | 23.0
37 | 5 | 6.5 | 4 | 23.5
38 | 6 | 7.5 | 5 | 24.0
39 | 7 | 8.5 | 6 | 24.5
40 | 7.5 | 9 | 6.5 | 25.0
41 | 8 | 9.5 | 7 | 25.5
42 | 9 | 10.5 | 8 | 26.0
43 | 10 | 11.5 | 9 | 26.5
44 | 11 | 12.5 | 10 | 27.0
45 | 12 | 13.5 | 11 | 27.5

How to Measure
Place your foot on a piece of paper and mark the longest point. Measure the distance in centimeters for the most accurate fit.`;
                    } else {
                        area.value = `Detailed Size Guide
All measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.

Size | Chest / Bust | Waist | Hips | Length
XS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5
S | 34 - 35 | 26 - 27 | 36 - 37 | 25.0
M | 36 - 37 | 28 - 29 | 38 - 39 | 25.5
L | 38 - 40 | 30 - 32 | 40 - 42 | 26.0
XL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5

How to Measure
Bust/Chest: Measure around the fullest part of your chest.
Waist: Measure around your natural waistline (narrowest part).
Hips: Measure around the fullest part of your hips.`;
                    }
                }
                </script>

                <div class="form-group" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: #fff; border: 1px solid var(--colors-hairline-soft); border-radius: 8px;">
                    <input type="checkbox" name="is_featured" id="is_featured" <?php echo $product['is_featured'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--colors-accent-coral);">
                    <label for="is_featured" class="form-label" style="margin: 0;">Highlight as Featured Piece</label>
                </div>

                <div style="margin-top: 40px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                    <button type="submit" class="button-primary" style="padding: 16px;">Update Piece</button>
                    <a href="manage_variations.php?id=<?php echo $id; ?>" class="button-secondary" style="padding: 16px; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid var(--colors-hairline); color: var(--colors-ink);">Manage Variations</a>
                    <a href="manage_images.php?id=<?php echo $id; ?>" class="button-secondary" style="padding: 16px; display: flex; align-items: center; justify-content: center; background: var(--colors-accent-teal); color: #fff; border: none;">Manage Media Gallery</a>
                    <a href="products_list.php" class="button-secondary" style="padding: 16px; display: flex; align-items: center; justify-content: center;">Discard Changes</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
