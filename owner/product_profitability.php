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

// Search and Filter Logic
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$profit_filter = isset($_GET['profit_filter']) ? $_GET['profit_filter'] : 'all';

// Build query with filters
$query = "SELECT id, name, price, cost_price, created_at FROM products";
$params = [];

if ($search) {
    $query .= " WHERE name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

// Add profit filter
if ($profit_filter == 'profitable') {
    $query .= ($search ? " AND" : " WHERE") . " price > cost_price";
} elseif ($profit_filter == 'loss') {
    $query .= ($search ? " AND" : " WHERE") . " price < cost_price";
} elseif ($profit_filter == 'break_even') {
    $query .= ($search ? " AND" : " WHERE") . " price = cost_price";
}

// Add sorting
switch ($sort) {
    case 'newest':
        $query .= " ORDER BY created_at DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY created_at ASC";
        break;
    case 'name_asc':
        $query .= " ORDER BY name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY name DESC";
        break;
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'profit_low':
        $query .= " ORDER BY (price - cost_price) ASC";
        break;
    case 'profit_high':
        $query .= " ORDER BY (price - cost_price) DESC";
        break;
    case 'margin_low':
        $query .= " ORDER BY (cost_price / NULLIF(price, 0)) ASC";
        break;
    case 'margin_high':
        $query .= " ORDER BY (cost_price / NULLIF(price, 0)) DESC";
        break;
    default:
        $query .= " ORDER BY created_at DESC";
}

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$products = $stmt->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="margin-bottom: var(--spacing-xxl);">
            <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600; font-family: var(--typography-body-font);">Inventory Economics</div>
            <h1 style="margin: 0 0 var(--spacing-lg) 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Product Profitability</h1>
        </header>

        <?php if (isset($success_msg)): ?>
            <div class="badge badge-success" style="margin-bottom: 24px; padding: 12px 24px; width: 100%; text-align: center;"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <!-- Stats Summary -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: var(--colors-surface); padding: 20px; border-radius: 12px; border: 1px solid var(--colors-hairline-soft);">
                <div style="font-size: 12px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Total Products</div>
                <div style="font-size: 32px; font-weight: 700; color: var(--colors-ink); font-family: var(--typography-display-font);"><?php echo count($products); ?></div>
            </div>
            <div style="background: var(--colors-surface); padding: 20px; border-radius: 12px; border: 1px solid var(--colors-hairline-soft);">
                <div style="font-size: 12px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Profitable</div>
                <div style="font-size: 32px; font-weight: 700; color: var(--colors-success); font-family: var(--typography-display-font);">
                    <?php 
                    $profitable = 0;
                    foreach ($products as $p) {
                        if ($p['price'] > $p['cost_price']) $profitable++;
                    }
                    echo $profitable;
                    ?>
                </div>
            </div>
            <div style="background: var(--colors-surface); padding: 20px; border-radius: 12px; border: 1px solid var(--colors-hairline-soft);">
                <div style="font-size: 12px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">At Loss</div>
                <div style="font-size: 32px; font-weight: 700; color: var(--colors-error); font-family: var(--typography-display-font);">
                    <?php 
                    $loss = 0;
                    foreach ($products as $p) {
                        if ($p['price'] < $p['cost_price']) $loss++;
                    }
                    echo $loss;
                    ?>
                </div>
            </div>
            <div style="background: var(--colors-surface); padding: 20px; border-radius: 12px; border: 1px solid var(--colors-hairline-soft);">
                <div style="font-size: 12px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Break Even</div>
                <div style="font-size: 32px; font-weight: 700; color: var(--colors-muted); font-family: var(--typography-display-font);">
                    <?php 
                    $breakeven = 0;
                    foreach ($products as $p) {
                        if ($p['price'] == $p['cost_price']) $breakeven++;
                    }
                    echo $breakeven;
                    ?>
                </div>
            </div>
            <div style="background: var(--colors-surface); padding: 20px; border-radius: 12px; border: 1px solid var(--colors-hairline-soft);">
                <div style="font-size: 12px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Avg. Margin</div>
                <div style="font-size: 32px; font-weight: 700; color: var(--colors-primary); font-family: var(--typography-display-font);">
                    <?php
                    $total_margin = 0;
                    $count = 0;
                    foreach ($products as $p) {
                        if ($p['price'] > 0) {
                            $margin = (($p['price'] - $p['cost_price']) / $p['price']) * 100;
                            $total_margin += $margin;
                            $count++;
                        }
                    }
                    echo $count > 0 ? number_format($total_margin / $count, 1) . '%' : '0%';
                    ?>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div style="background: var(--colors-surface); padding: 16px; border-radius: 12px; border: 1px solid var(--colors-hairline-soft); margin-bottom: 24px; overflow-x: auto;">
            <form method="GET" id="filterForm" style="display: flex; gap: 10px; align-items: center; min-width: max-content;">
                <!-- Search -->
                <div style="position: relative; width: 280px;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products..." class="form-input" style="padding-left: 36px; width: 100%; font-size: 13px;">
                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); opacity: 0.5; font-size: 14px;">🔍</span>
                </div>

                <!-- Profit Filter -->
                <select name="profit_filter" class="form-input" style="width:auto; font-size: 13px;">
                    <option value="all" <?php echo $profit_filter == 'all' ? 'selected' : ''; ?>>All Products</option>
                    <option value="profitable" <?php echo $profit_filter == 'profitable' ? 'selected' : ''; ?>>Profitable</option>
                    <option value="loss" <?php echo $profit_filter == 'loss' ? 'selected' : ''; ?>>At Loss</option>
                    <option value="break_even" <?php echo $profit_filter == 'break_even' ? 'selected' : ''; ?>>Break Even</option>
                </select>

                <!-- Sort -->
                <select name="sort" class="form-input" style="width:auto; font-size: 13px;">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                    <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price (Low-High)</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price (High-Low)</option>
                    <option value="profit_low" <?php echo $sort == 'profit_low' ? 'selected' : ''; ?>>Profit (Low-High)</option>
                    <option value="profit_high" <?php echo $sort == 'profit_high' ? 'selected' : ''; ?>>Profit (High-Low)</option>
                    <option value="margin_low" <?php echo $sort == 'margin_low' ? 'selected' : ''; ?>>Margin (Low-High)</option>
                    <option value="margin_high" <?php echo $sort == 'margin_high' ? 'selected' : ''; ?>>Margin (High-Low)</option>
                </select>

                <!-- Filter Button -->
                <button type="submit" class="button-primary" style="padding: 8px 20px; white-space: nowrap; font-size: 13px;">
                    Apply Filters
                </button>

                <!-- Reset Button -->
                <?php if ($search || $sort != 'newest' || $profit_filter != 'all'): ?>
                    <a href="product_profitability.php" class="button-secondary" style="padding: 8px 16px; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; font-size: 13px;">
                        <span>↻</span> Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Products Table -->
        <div class="table-container" style="max-height: 400px; overflow-y: auto; margin: 0; background: var(--colors-surface); border-radius: 12px; border: 1px solid var(--colors-hairline-soft);">
            <table class="data-table">
                <thead style="position: sticky; top: 0; background: var(--colors-surface); z-index: 10;">
                    <tr>
                        <th style="width: 35%;">Product</th>
                        <th style="text-align: right;">Selling Price</th>
                        <th style="text-align: right;">Cost Price</th>
                        <th style="text-align: right;">Profit</th>
                        <th style="text-align: right;">Margin</th>
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
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 48px; color: var(--colors-muted);">No products found matching your criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
