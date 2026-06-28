<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

// Get Filters
$filter_category = $_GET['filter_category'] ?? '';
$sort_top = $_GET['sort_top'] ?? 'revenue_desc';
$sort_zero = $_GET['sort_zero'] ?? 'name_asc';
$active_tab = $_GET['tab'] ?? 'top-products';

$all_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// 1. Top Products
$top_params = [];
$top_where = "o.status NOT IN ('cancelled','refunded')";
if ($filter_category) {
    $top_where .= " AND c.id = ?";
    $top_params[] = $filter_category;
}
$top_order = "revenue DESC";
if ($sort_top == 'revenue_asc') $top_order = "revenue ASC";
elseif ($sort_top == 'qty_desc') $top_order = "qty DESC";
elseif ($sort_top == 'qty_asc') $top_order = "qty ASC";

$stmt = $pdo->prepare("
    SELECT p.name, c.name as category_name, SUM(oi.quantity) as qty, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE $top_where
    GROUP BY p.id
    ORDER BY $top_order
");
$stmt->execute($top_params);
$top_products = $stmt->fetchAll();

// 2. Underperforming Products — zero valid sales
$zero_params = [];
$zero_where = "p.id NOT IN (
    SELECT DISTINCT pv2.product_id
    FROM order_items oi2
    JOIN product_variations pv2 ON oi2.variation_id = pv2.id
    JOIN orders o2 ON oi2.order_id = o2.id
    WHERE o2.status NOT IN ('cancelled','refunded')
)";
if ($filter_category) {
    $zero_where .= " AND c.id = ?";
    $zero_params[] = $filter_category;
}
$zero_order = "p.name ASC";
if ($sort_zero == 'name_desc') $zero_order = "p.name DESC";

$stmt = $pdo->prepare("
    SELECT p.name, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE $zero_where
    ORDER BY $zero_order
");
$stmt->execute($zero_params);
$zero_sales = $stmt->fetchAll();

// 3. Category Breakdown
$categories_raw = $pdo->query("
    SELECT c.name, SUM(oi.quantity * oi.price) as revenue, SUM(oi.quantity) as qty
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status NOT IN ('cancelled','refunded')
    GROUP BY c.id
    ORDER BY revenue DESC
")->fetchAll();

$cat_labels = [];
$cat_revenues = [];
$total_cat_rev = array_sum(array_column($categories_raw, 'revenue')) ?: 1;
foreach ($categories_raw as $cat) {
    $cat_labels[] = $cat['name'];
    $cat_revenues[] = (float)$cat['revenue'];
}

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xl);">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600; font-family: var(--typography-body-font);">Performance Intelligence</div>
                <h1 style="margin: 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Product Insights</h1>
            </div>
            <div style="font-size: 13px; color: var(--colors-muted); background: var(--colors-surface-soft); padding: 8px 16px; border-radius: 20px; border: 1px solid var(--colors-hairline);">
                Data synchronized in real-time
            </div>
        </header>

        <!-- Tab Controls -->
        <div style="display: flex; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid var(--colors-hairline);">
            <button class="tab-btn <?php echo $active_tab == 'top-products' ? 'active' : ''; ?>" onclick="switchTab('top-products')" style="padding: 12px 24px; background: none; border: none; border-bottom: 2px solid <?php echo $active_tab == 'top-products' ? 'var(--colors-primary)' : 'transparent'; ?>; font-weight: 600; font-size: 14px; cursor: pointer; color: <?php echo $active_tab == 'top-products' ? 'var(--colors-ink)' : 'var(--colors-muted)'; ?>; transition: 0.2s;">
                🏆 Top Revenue (<?php echo count($top_products); ?>)
            </button>
            <button class="tab-btn <?php echo $active_tab == 'zero-sales' ? 'active' : ''; ?>" onclick="switchTab('zero-sales')" style="padding: 12px 24px; background: none; border: none; border-bottom: 2px solid <?php echo $active_tab == 'zero-sales' ? 'var(--colors-primary)' : 'transparent'; ?>; font-weight: 600; font-size: 14px; cursor: pointer; color: <?php echo $active_tab == 'zero-sales' ? 'var(--colors-ink)' : 'var(--colors-muted)'; ?>; transition: 0.2s;">
                📉 Zero Sales (<?php echo count($zero_sales); ?>)
            </button>

        </div>

        <!-- Tab Content: Top Products -->
        <div id="top-products" class="tab-content" style="display: <?php echo $active_tab == 'top-products' ? 'block' : 'none'; ?>;">
            <div class="surface-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--colors-hairline-soft); display: flex; justify-content: space-between; align-items: center; background: var(--colors-surface);">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 600; margin: 0; margin-bottom: 4px;">Top Performing Products</h3>
                        <span style="font-size: 12px; color: var(--colors-muted);">View revenue and units sold</span>
                    </div>
                    
                    <form method="GET" style="display: flex; gap: 12px; align-items: center;">
                        <input type="hidden" name="tab" value="top-products">
                        <select name="filter_category" class="form-input" style="padding: 6px 12px; font-size: 12px; height: auto; width: auto">
                            <option value="">All Categories</option>
                            <?php foreach($all_categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $filter_category == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="sort_top" class="form-input" style="padding: 6px 12px; font-size: 12px; height: auto;">
                            <option value="revenue_desc" <?php echo $sort_top == 'revenue_desc' ? 'selected' : ''; ?>>Revenue: High to Low</option>
                            <option value="revenue_asc" <?php echo $sort_top == 'revenue_asc' ? 'selected' : ''; ?>>Revenue: Low to High</option>
                            <option value="qty_desc" <?php echo $sort_top == 'qty_desc' ? 'selected' : ''; ?>>Units Sold: High to Low</option>
                            <option value="qty_asc" <?php echo $sort_top == 'qty_asc' ? 'selected' : ''; ?>>Units Sold: Low to High</option>
                        </select>
                        <button type="submit" class="button-primary" style="padding: 6px 16px; font-size: 12px;">Apply</button>
                        <a href="?tab=top-products" class="button-secondary" style="padding: 6px 16px; font-size: 12px; text-decoration: none;">Reset</a>
                    </form>
                </div>
                <div class="table-container" style="margin: 0; box-shadow: none; border: none; border-radius: 0; max-height: 350px; overflow-y: auto;">
                    <table class="data-table" style="margin: 0;">
                        <thead style="position: sticky; top: 0; z-index: 10; background: var(--colors-surface);">
                            <tr>
                                <th>Rank</th>
                                <th>Product Details</th>
                                <th>Category</th>
                                <th>Units Sold</th>
                                <th style="text-align: right;">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($top_products as $p): ?>
                                <tr>
                                    <td style="font-family: var(--typography-code-font); font-weight: 700; color: var(--colors-muted);">#<?php echo $rank++; ?></td>
                                    <td>
                                        <div style="font-weight: 600; font-size: 14px; color: var(--colors-ink);"><?php echo htmlspecialchars($p['name']); ?></div>
                                    </td>
                                    <td><span class="badge badge-info" style="font-size: 11px;"><?php echo htmlspecialchars($p['category_name']); ?></span></td>
                                    <td style="font-family: var(--typography-code-font);"><?php echo number_format($p['qty']); ?></td>
                                    <td style="text-align: right; font-family: var(--typography-code-font); font-weight: 700; color: var(--colors-success);">RM <?php echo number_format($p['revenue'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($top_products)): ?>
                                <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--colors-muted);">No product sales data available yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Content: Zero Sales -->
        <div id="zero-sales" class="tab-content" style="display: <?php echo $active_tab == 'zero-sales' ? 'block' : 'none'; ?>;">
            <div class="surface-card" style="padding: 0; overflow: hidden; border: 1px solid #fee2e2;">
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--colors-hairline-soft); background: #fff5f5; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 700; margin: 0; margin-bottom: 4px; color: var(--colors-error);">Underperforming Products</h3>
                        <span style="font-size: 12px; color: #e53e3e; font-weight: 600;">Requires Marketing Attention</span>
                    </div>

                    <form method="GET" style="display: flex; gap: 12px; align-items: center;">
                        <input type="hidden" name="tab" value="zero-sales">
                        <select name="filter_category" class="form-input" style="padding: 6px 12px; font-size: 12px; height: auto; border-color: #fecaca; background: #fff;">
                            <option value="">All Categories</option>
                            <?php foreach($all_categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $filter_category == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="sort_zero" class="form-input" style="padding: 6px 12px; font-size: 12px; height: auto; border-color: #fecaca; background: #fff;">
                            <option value="name_asc" <?php echo $sort_zero == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="name_desc" <?php echo $sort_zero == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                        </select>
                        <button type="submit" class="button-primary" style="padding: 6px 16px; font-size: 12px; background: var(--colors-error); border-color: var(--colors-error);">Apply</button>
                        <a href="?tab=zero-sales" class="button-secondary" style="padding: 6px 16px; font-size: 12px; text-decoration: none; border-color: #fecaca; color: var(--colors-error);">Reset</a>
                    </form>
                </div>
                <div class="table-container" style="margin: 0; box-shadow: none; border: none; border-radius: 0; max-height: 350px; overflow-y: auto;">
                    <table class="data-table" style="margin: 0;">
                        <thead style="position: sticky; top: 0; z-index: 10; background: var(--colors-surface);">
                            <tr>
                                <th>Product Name</th>
                                <th style="text-align: right;">Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($zero_sales)): ?>
                                <tr><td colspan="2" style="text-align: center; padding: 40px; color: var(--colors-success); font-weight: 600;">Excellent! All products have generated sales.</td></tr>
                            <?php else: ?>
                                <?php foreach ($zero_sales as $zp): ?>
                                    <tr>
                                        <td style="font-weight: 600; font-size: 14px; color: var(--colors-ink);"><?php echo htmlspecialchars($zp['name']); ?></td>
                                        <td style="text-align: right;"><span class="badge" style="background: var(--colors-surface-soft); color: var(--colors-muted); font-size: 11px;"><?php echo htmlspecialchars($zp['category_name']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </div>
</div>

<script>
function switchTab(tabId) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(el => {
        el.style.display = 'none';
    });
    // Unstyle all tabs
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.style.color = 'var(--colors-muted)';
        el.style.borderBottomColor = 'transparent';
    });
    
    // Show active content
    document.getElementById(tabId).style.display = 'block';
    
    // Style active tab
    const activeBtn = event.currentTarget;
    activeBtn.style.color = 'var(--colors-ink)';
    activeBtn.style.borderBottomColor = 'var(--colors-primary)';
}


</script>

<?php include $include_path . 'footer.php'; ?>
