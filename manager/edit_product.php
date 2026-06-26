<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'];
$min_publish_at = date('Y-m-d\TH:i');
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $gender = $_POST['gender'];
    $description = $_POST['description'];
    $size_chart = $_POST['size_chart'];
    $price = $_POST['price'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, gender = ?, description = ?, size_chart = ?, price = ?, is_featured = ? WHERE id = ?");
    $stmt->execute([$name, $category_id, $gender, $description, $size_chart, $price, $is_featured, $id]);
    
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

        <?php if ($error_message): ?>
            <div style="margin-bottom: 24px; padding: 14px 16px; border: 1px solid var(--colors-error); color: var(--colors-error); background: #fff;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px; align-items: start;">
                
                <!-- Main Content Column -->
                <div style="display: flex; flex-direction: column; gap: 32px;">
                    <!-- Basic Info Card -->
                    <div class="surface-card" style="padding: 32px; border: 1px solid var(--colors-hairline); margin: 0;">
                        <h3 style="font-size: 16px; margin-bottom: 24px; border-bottom: 1px solid var(--colors-hairline-soft); padding-bottom: 12px;">Basic Information</h3>
                        
                        <div class="form-group">
                            <label class="form-label">Piece Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="form-input">
                        </div>
                        
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Description & Narrative</label>
                            <textarea name="description" rows="8" class="form-input"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                    </div>

                    <!-- Size Architecture Card -->
                    <div class="surface-card" style="padding: 32px; border: 1px solid var(--colors-hairline); margin: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--colors-hairline-soft); padding-bottom: 12px;">
                            <h3 style="font-size: 16px; margin: 0;">Size Details & Fit</h3>
                            <button type="button" onclick="applyDefaultGuide()" class="button-secondary" style="font-size: 11px; padding: 4px 12px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Use Template</button>
                        </div>
                        <textarea id="size_guide_area" name="size_chart" rows="6" class="form-input" style="font-family: var(--typography-code-font); font-size: 12px; line-height: 1.6; margin: 0;" placeholder="Add size measurements or apply a template..."><?php echo htmlspecialchars($product['size_chart']); ?></textarea>
                    </div>
                </div>

                <!-- Sidebar Column -->
                <div style="display: flex; flex-direction: column; gap: 32px;">
                    
                    <!-- Management Actions Card -->
                    <div class="surface-card" style="padding: 24px; border: 1px solid var(--colors-hairline); margin: 0; background: var(--colors-surface-soft);">
                        <h3 style="font-size: 14px; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--colors-ink);">Product Assets</h3>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <a href="manage_images.php?id=<?php echo $id; ?>" class="button-secondary" style="text-align: center; background: var(--colors-accent-teal); color: #fff; border: none; padding: 12px;">Manage Media Gallery</a>
                            <a href="manage_variations.php?id=<?php echo $id; ?>" class="button-secondary" style="text-align: center; background: #fff; color: var(--colors-ink); border: 1px solid var(--colors-hairline); padding: 12px;">Manage Variations</a>
                        </div>
                    </div>

                    <!-- Organization Card -->
                    <div class="surface-card" style="padding: 24px; border: 1px solid var(--colors-hairline); margin: 0;">
                        <h3 style="font-size: 14px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--colors-muted);">Organization</h3>
                        
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
                        
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label">Gender Focus</label>
                            <select name="gender" required class="form-input">
                                <option value="Unisex" <?php echo $product['gender'] == 'Unisex' ? 'selected' : ''; ?>>Unisex Focus</option>
                                <option value="Men" <?php echo $product['gender'] == 'Men' ? 'selected' : ''; ?>>Men's Collection</option>
                                <option value="Women" <?php echo $product['gender'] == 'Women' ? 'selected' : ''; ?>>Women's Collection</option>
                                <option value="Kids" <?php echo $product['gender'] == 'Kids' ? 'selected' : ''; ?>>Kids' Collection</option>
                            </select>
                        </div>
                    </div>

                    <!-- Pricing & Status Card -->
                    <div class="surface-card" style="padding: 24px; border: 1px solid var(--colors-hairline); margin: 0;">
                        <h3 style="font-size: 14px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--colors-muted);">Pricing & Status</h3>
                        
                        <div class="form-group">
                            <label class="form-label">Price (RM)</label>
                            <input type="number" name="price" step="0.01" value="<?php echo $product['price']; ?>" required class="form-input" style="font-size: 18px; font-weight: 600;">
                        </div>

                        <div class="form-group" style="display: flex; align-items: flex-start; gap: 12px; margin: 0; padding-top: 16px; border-top: 1px solid var(--colors-hairline-soft);">
                            <input type="checkbox" name="is_featured" id="is_featured" <?php echo $product['is_featured'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--colors-accent-coral); margin-top: 2px;">
                            <label for="is_featured" class="form-label" style="margin: 0; font-weight: 500; line-height: 1.4;">Highlight as Featured Piece</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid var(--colors-hairline); display: flex; gap: 16px; justify-content: flex-end;">
                <a href="products_list.php" class="button-secondary" style="padding: 14px 32px;">Discard Changes</a>
                <button type="submit" class="button-primary" style="padding: 14px 32px;">Update Piece</button>
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
        </form>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
