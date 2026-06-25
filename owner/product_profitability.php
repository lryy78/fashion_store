<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

// Handle Cost Price Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cost'])) {
    $product_id = $_POST['product_id'];
    $new_cost = $_POST['cost_price'];
    $stmt = $pdo->prepare("UPDATE products SET cost_price = ? WHERE id = ?");
    $stmt->execute([$new_cost, $product_id]);
    $success_msg = "Cost updated successfully.";
}

// Search Logic
$search = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT id, name, price, cost_price FROM products";
if ($search) {
    $query .= " WHERE name LIKE :search";
}
$query .= " ORDER BY name ASC";

$stmt = $pdo->prepare($query);
if ($search) {
    $stmt->bindValue(':search', '%' . $search . '%');
}
$stmt->execute();
$products = $stmt->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600; font-family: var(--typography-body-font);">Inventory Economics</div>
                <h1 style="margin: 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Product Profitability</h1>
            </div>
            <div style="width: 300px; display: flex; gap: 8px; align-items: center;">
                <form method="GET" style="position: relative; flex: 1;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search product..." class="form-input" style="padding-left: 40px;">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); opacity: 0.5;">🔍</span>
                </form>
                <?php if (!empty($search)): ?>
                    <a href="product_profitability.php" class="button-secondary" style="padding: 8px 16px; text-decoration: none; white-space: nowrap;">Reset</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if (isset($success_msg)): ?>
            <div class="badge badge-success" style="margin-bottom: 24px; padding: 12px 24px; width: 100%; text-align: center;"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <div class="table-container" style="margin: 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 40%;">Product</th>
                        <th style="text-align: right;">Selling Price</th>
                        <th style="text-align: right;">Cost Price</th>
                        <th style="text-align: right;">Profit</th>
                        <th style="text-align: right;">Margin</th>
                        <th style="text-align: right; width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): 
                        $profit = $p['price'] - $p['cost_price'];
                        $margin = $p['price'] > 0 ? ($profit / $p['price']) * 100 : 0;
                    ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--colors-ink);"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div style="font-size: 11px; color: var(--colors-muted); margin-top: 2px;">ID: #PROD-<?php echo str_pad($p['id'], 4, '0', STR_PAD_LEFT); ?></div>
                            </td>
                            <td style="text-align: right; font-family: var(--typography-code-font);">RM <?php echo number_format($p['price'], 2); ?></td>
                            <td style="text-align: right;">
                                <form method="POST" style="display: inline-flex; align-items: center; gap: 8px;">
                                    <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                    <span style="font-size: 12px; color: var(--colors-muted);">RM</span>
                                    <input type="number" name="cost_price" value="<?php echo $p['cost_price']; ?>" step="0.01" class="form-input" style="width: 100px; padding: 6px 10px; font-family: var(--typography-code-font); text-align: right;">
                                    <button type="submit" name="update_cost" class="button-primary" style="padding: 6px 12px; font-size: 11px;">SET</button>
                                </form>
                            </td>
                            <td style="text-align: right; font-family: var(--typography-code-font); font-weight: 600; color: <?php echo $profit >= 0 ? 'var(--colors-success)' : 'var(--colors-error)'; ?>;">
                                RM <?php echo number_format($profit, 2); ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 8px;">
                                    <span style="font-family: var(--typography-code-font); font-weight: 600; color: var(--colors-muted);"><?php echo number_format($margin, 1); ?>%</span>
                                    <div style="width: 60px; height: 6px; background: var(--colors-surface-soft); border-radius: 3px; overflow: hidden; border: 1px solid var(--colors-hairline-soft);">
                                        <div style="height: 100%; width: <?php echo min(max($margin, 0), 100); ?>%; background: <?php echo $margin > 30 ? 'var(--colors-success)' : ($margin > 10 ? 'var(--colors-primary)' : 'var(--colors-error)'); ?>;"></div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <a href="product_insights.php?id=<?php echo $p['id']; ?>" class="badge badge-info" style="text-decoration: none;">View Stats</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 48px; color: var(--colors-muted);">No products found matching your search.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
