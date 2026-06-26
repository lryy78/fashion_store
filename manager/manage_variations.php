<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$product_id = $_GET['id'];
$product_stmt = $pdo->prepare("SELECT p.name, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$product_stmt->execute([$product_id]);
$product_data = $product_stmt->fetch();
$product_name = $product_data['name'];
$category_name = $product_data['category_name'];

function getColorHex($colorString) {
    $colorString = strtolower($colorString);
    $map = [
        'black' => '#000000',
        'white' => '#ffffff',
        'red' => '#ef4444',
        'blue' => '#3b82f6',
        'green' => '#22c55e',
        'yellow' => '#eab308',
        'orange' => '#f97316',
        'purple' => '#a855f7',
        'pink' => '#ec4899',
        'gray' => '#6b7280',
        'grey' => '#6b7280',
        'navy' => '#1e3a8a',
        'beige' => '#f5f5dc',
        'brown' => '#92400e',
        'gold' => '#ffd700',
        'silver' => '#c0c0c0',
        'olive' => '#808000',
        'maroon' => '#800000'
    ];
    
    foreach ($map as $key => $hex) {
        if (strpos($colorString, $key) !== false) {
            return $hex;
        }
    }
    return '#ddd'; // Default
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_variation'])) {
        $size = $_POST['size'];
        $color = $_POST['color'];
        $stock = $_POST['stock'];

        $stmt = $pdo->prepare("INSERT INTO product_variations (product_id, size, color, stock_quantity) VALUES (?, ?, ?, ?)");
        $stmt->execute([$product_id, $size, $color, $stock]);
    } elseif (isset($_POST['update_stock'])) {
        $variation_id = $_POST['variation_id'];
        $new_stock = $_POST['new_stock'];
        $stmt = $pdo->prepare("UPDATE product_variations SET stock_quantity = ? WHERE id = ?");
        $stmt->execute([$new_stock, $variation_id]);
    }
}

$variations = $pdo->prepare("SELECT * FROM product_variations WHERE product_id = ?");
$variations->execute([$product_id]);
$variations_list = $variations->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<?php require_once '../includes/sidebar.php'; ?>
<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="margin-bottom: 40px;">
            <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Manage Inventory</div>
            <h1 style="margin: 0; font-size: 40px;">Variations: <?php echo htmlspecialchars($product_name); ?></h1>
        </header>

        <!-- Add New Variation Card -->
        <div class="surface-card" style="margin-bottom: 32px; padding: 32px; border: 1px solid var(--colors-hairline);">
            <h3 style="font-size: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--colors-hairline-soft); padding-bottom: 12px;">Add New Variation</h3>
            <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 16px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Size</label>
                    <select name="size" required class="form-input">
                        <?php if (stripos($category_name, 'footwear') !== false || stripos($category_name, 'shoes') !== false): ?>
                            <optgroup label="Footwear Sizing (EU)">
                                <option value="36">36</option>
                                <option value="37">37</option>
                                <option value="38">38</option>
                                <option value="39">39</option>
                                <option value="40">40</option>
                                <option value="41">41</option>
                                <option value="42">42</option>
                                <option value="43">43</option>
                                <option value="44">44</option>
                                <option value="45">45</option>
                            </optgroup>
                            <optgroup label="Footwear Sizing (US)">
                                <option value="US 7">US 7</option>
                                <option value="US 8">US 8</option>
                                <option value="US 9">US 9</option>
                                <option value="US 10">US 10</option>
                                <option value="US 11">US 11</option>
                                <option value="US 12">US 12</option>
                            </optgroup>
                        <?php else: ?>
                            <optgroup label="Apparel Sizing">
                                <option value="XS">Extra Small (XS)</option>
                                <option value="S">Small (S)</option>
                                <option value="M" selected>Medium (M)</option>
                                <option value="L">Large (L)</option>
                                <option value="XL">Extra Large (XL)</option>
                                <option value="XXL">Double Extra Large (XXL)</option>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Color</label>
                    <input type="text" name="color" placeholder="e.g. Black, White" required class="form-input">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="stock" value="0" required class="form-input">
                </div>
                <button type="submit" name="add_variation" class="button-primary" style="padding: 14px 24px;">Add Variation</button>
            </form>
        </div>

        <!-- Existing Variations Card -->
        <div class="surface-card" style="margin-bottom: 0; padding: 32px; border: 1px solid var(--colors-hairline);">
            <h3 style="font-size: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--colors-hairline-soft); padding-bottom: 12px;">Existing Variations</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="background: var(--colors-surface-soft); width: 20%;">Size</th>
                        <th style="background: var(--colors-surface-soft); width: 30%;">Color</th>
                        <th style="background: var(--colors-surface-soft); width: 30%;">Stock</th>
                        <th style="background: var(--colors-surface-soft); text-align: right; width: 20%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variations_list as $v): ?>
                        <tr>
                            <td>
                                <span style="display: inline-block; padding: 4px 12px; background: var(--colors-surface-soft); border: 1px solid var(--colors-hairline); border-radius: 4px; font-weight: 600; font-size: 12px;">
                                    <?php echo htmlspecialchars($v['size']); ?>
                                </span>
                            </td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 6px; font-weight: 500; font-size: 13px;">
                                    <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: <?php echo getColorHex($v['color']); ?>; border: 1px solid rgba(0,0,0,0.1);"></span>
                                    <?php echo htmlspecialchars($v['color']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: flex; gap: 8px; align-items: center; margin: 0;">
                                    <input type="hidden" name="variation_id" value="<?php echo $v['id']; ?>">
                                    <input type="number" name="new_stock" value="<?php echo $v['stock_quantity']; ?>" 
                                           style="width: 80px; padding: 6px 12px; font-size: 13px; font-family: var(--typography-code-font); font-weight: 600; border: 1px solid var(--colors-hairline); border-radius: 4px; transition: border-color 0.2s;">
                                    <button type="submit" name="update_stock" class="button-secondary" style="padding: 6px 12px; font-size: 12px; background: #fff;">Update</button>
                                </form>
                            </td>
                            <td style="text-align: right;">
                                <a href="delete_variation.php?id=<?php echo $v['id']; ?>&product_id=<?php echo $product_id; ?>" class="button-secondary" style="color: var(--colors-error); font-size: 12px; padding: 6px 12px; border: 1px solid #fee2e2; background: #fef2f2;">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top: 32px; display: flex; justify-content: flex-end;">
                <a href="products_list.php" class="button-primary" style="font-size: 13px; padding: 10px 24px;">Done Editing Variations</a>
            </div>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
