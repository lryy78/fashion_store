<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

// Filters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$filter_category = $_GET['category'] ?? 'all';

// Shared time-range constraint
$time_range = " AND o.created_at BETWEEN " . $pdo->quote($start_date . ' 00:00:00') . " AND " . $pdo->quote($end_date . ' 23:59:59');

// 1. Top Selling Products
$top_where = $time_range;
if ($filter_category != 'all') $top_where .= " AND p.category_id = " . (int)$filter_category;

$top_selling_query = "SELECT p.name, SUM(oi.quantity) as total_sold 
                      FROM order_items oi 
                      JOIN orders o ON oi.order_id = o.id
                      JOIN product_variations pv ON oi.variation_id = pv.id 
                      JOIN products p ON pv.product_id = p.id 
                      WHERE o.status NOT IN ('cancelled','refunded') $top_where
                      GROUP BY p.id ORDER BY total_sold DESC LIMIT 5";
$top_selling = $pdo->query($top_selling_query)->fetchAll();

// 2. Most Viewed Products
$view_where = ($filter_category != 'all') ? " WHERE category_id = " . (int)$filter_category : "";
$most_viewed = $pdo->query("SELECT name, views FROM products $view_where ORDER BY views DESC LIMIT 5")->fetchAll();

// 3. Low Conversion Products
$conv_p_where = ($filter_category != 'all') ? " AND p.category_id = " . (int)$filter_category : "";

$conversion_query = "SELECT p.name, p.views, IFNULL(SUM(oi.total_sold), 0) as total_sold, 
                     (IFNULL(SUM(oi.total_sold), 0) / NULLIF(p.views, 0)) * 100 as conversion_rate
                     FROM products p
                     LEFT JOIN (
                        SELECT pv.product_id, SUM(oi.quantity) as total_sold
                        FROM order_items oi 
                        JOIN orders o ON oi.order_id = o.id 
                        JOIN product_variations pv ON oi.variation_id = pv.id
                        WHERE o.status NOT IN ('cancelled','refunded') $time_range
                        GROUP BY pv.product_id
                     ) oi ON p.id = oi.product_id
                     WHERE p.views > 10 $conv_p_where
                     GROUP BY p.id
                     ORDER BY conversion_rate ASC LIMIT 5";
$low_conversion = $pdo->query($conversion_query)->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

include '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px;">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Performance Insights</div>
                <h1 style="margin: 0; font-size: 40px;">Product Analytics</h1>
            </div>
        </header>

        <!-- Filters Bar -->
        <div class="surface-card" style="padding: 24px; margin-bottom: 40px; border: 1px solid var(--colors-hairline);">
            <form method="GET" style="display: grid; grid-template-columns: repeat(3, 1fr) auto auto; gap: 20px; align-items: flex-end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">From Date</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-input" style="padding: 10px;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">To Date</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-input" style="padding: 10px;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">Category</label>
                    <select name="category" class="form-input" style="padding: 10px;">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="button-primary" style="padding: 12px;">Apply Analysis</button>
                <a href="product_analytics.php" class="button-secondary" style="padding: 12px 24px; text-decoration: none;">Reset Filters</a>
            </form>
        </div>

        <div style="margin-bottom: 32px;">
            <!-- Top Selling Chart -->
            <div class="surface-card" style="padding: 32px;">
                <h3 style="font-size: 18px; margin-bottom: 24px;">Top Selling Products (Volume)</h3>
                <canvas id="topSellingChart" height="250"></canvas>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
            <!-- Most Viewed List -->
            <div class="surface-card" style="padding: 32px;">
                <h3 style="font-size: 18px; margin-bottom: 24px;">Product Interest (Most Viewed)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align: right;">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($most_viewed as $p): ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($p['name']); ?></td>
                                <td style="text-align: right; font-family: var(--typography-code-font);"><?php echo number_format($p['views']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Conversion Rate (Low Conversion) -->
            <div class="surface-card" style="padding: 32px;">
                <h3 style="font-size: 18px; margin-bottom: 24px;">Low Conversion Items (Action Needed)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Conv. Rate</th>
                            <th style="text-align: right;">Sales/Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_conversion as $p): ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($p['name']); ?></td>
                                <td>
                                    <div style="width: 100px; height: 8px; background: #eee; border-radius: 4px; overflow: hidden;">
                                        <div style="width: <?php echo min(100, $p['conversion_rate'] * 5); ?>%; height: 100%; background: var(--colors-error);"></div>
                                    </div>
                                </td>
                                <td style="text-align: right; font-size: 12px; color: var(--colors-muted);">
                                    <?php echo $p['total_sold']; ?> / <?php echo $p['views']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="font-size: 11px; color: var(--colors-muted); margin-top: 20px;">* Showing products with >10 views and lowest checkout rates.</p>
            </div>
        </div>
    </div>
</div>

<script>
// Top Selling Chart
const topSellingCtx = document.getElementById('topSellingChart').getContext('2d');
new Chart(topSellingCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($top_selling, 'name')); ?>,
        datasets: [{
            label: 'Units Sold',
            data: <?php echo json_encode(array_column($top_selling, 'total_sold')); ?>,
            backgroundColor: '#ff6b6b',
            borderRadius: 6
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { grid: { display: false } }, y: { grid: { display: false } } }
    }
});

</script>

<?php include '../includes/footer.php'; ?>
