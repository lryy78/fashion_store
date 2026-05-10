<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

// 1. Top Products
$top_products = $pdo->query("
    SELECT p.name, c.name as category_name, SUM(oi.quantity) as qty, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 10
")->fetchAll();

// 2. Underperforming Products — products with 0 sales
$zero_sales = $pdo->query("
    SELECT p.name, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.id NOT IN (
        SELECT DISTINCT pv2.product_id
        FROM order_items oi2
        JOIN product_variations pv2 ON oi2.variation_id = pv2.id
    )
    LIMIT 10
")->fetchAll();

// 3. Category Breakdown
$categories_raw = $pdo->query("
    SELECT c.name, SUM(oi.quantity * oi.price) as revenue, SUM(oi.quantity) as qty
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
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
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600; font-family: var(--typography-body-font);">Performance Intelligence</div>
                <h1 style="margin: 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Product Insights</h1>
            </div>
            <div style="font-size: 13px; color: var(--colors-muted);">Revenue driven view — no inventory</div>
        </header>

        <div class="dashboard-split" style="grid-template-columns: 1fr 1fr; align-items: stretch; margin-bottom: 32px;">
            <!-- Top Products -->
            <div class="surface-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--colors-hairline-soft); background: var(--colors-surface-soft); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">🏆 Top Revenue Products</h3>
                    <span style="font-size: 11px; color: var(--colors-muted);">Last 30 Days</span>
                </div>
                <table class="data-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align: right;">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($top_products, 0, 5) as $p): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($p['name']); ?></div>
                                    <div style="font-size: 11px; color: var(--colors-muted); margin-top: 2px;"><?php echo htmlspecialchars($p['category_name']); ?> • <?php echo number_format($p['qty']); ?> sold</div>
                                </td>
                                <td style="text-align: right; font-family: var(--typography-code-font); font-weight: 700; color: var(--colors-primary);">RM <?php echo number_format($p['revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Zero Sales Products -->
            <div class="surface-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--colors-hairline-soft); background: #fff5f5; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0; color: var(--colors-error);">📉 Critical: Zero Sales</h3>
                    <span style="font-size: 11px; color: #e53e3e;">Needs Attention</span>
                </div>
                <table class="data-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align: right;">Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($zero_sales)): ?>
                            <tr><td colspan="2" style="text-align: center; padding: 24px; color: var(--colors-muted);">All products have sales!</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice($zero_sales, 0, 5) as $zp): ?>
                                <tr>
                                    <td style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($zp['name']); ?></td>
                                    <td style="text-align: right;"><span class="badge badge-info" style="font-size: 11px;"><?php echo htmlspecialchars($zp['category_name']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-split" style="grid-template-columns: 400px 1fr; align-items: stretch;">
            <!-- Category Breakdown Chart -->
            <div class="surface-card" style="padding: 24px;">
                <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px;">📊 Market Share</h3>
                <div style="height: 300px;">
                    <canvas id="catChart"></canvas>
                </div>
            </div>

            <!-- Category Details Table -->
            <div class="surface-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--colors-hairline-soft);">
                    <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">Category Performance Detail</h3>
                </div>
                <table class="data-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Units Sold</th>
                            <th>Revenue Share</th>
                            <th style="text-align: right;">Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories_raw as $cat): 
                            $pct = round(($cat['revenue'] / $total_cat_rev) * 100);
                        ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--colors-ink);"><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td style="font-family: var(--typography-code-font);"><?php echo number_format($cat['qty']); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="flex: 1; height: 6px; background: var(--colors-hairline); border-radius: 3px; overflow: hidden;">
                                            <div style="height: 100%; width: <?php echo $pct; ?>%; background: var(--colors-primary);"></div>
                                        </div>
                                        <span style="font-size: 12px; color: var(--colors-muted); font-weight: 600; width: 35px;"><?php echo $pct; ?>%</span>
                                    </div>
                                </td>
                                <td style="text-align: right; font-family: var(--typography-code-font); font-weight: 700; color: var(--colors-ink);">RM <?php echo number_format($cat['revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('catChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($cat_labels); ?>,
            datasets: [{
                label: 'Revenue (RM)',
                data: <?php echo json_encode($cat_revenues); ?>,
                backgroundColor: ['#181715', '#cc785c', '#efe9de', '#6c6a64', '#8e8b82', '#faf9f5'],
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0' },
                    ticks: { callback: v => 'RM ' + v }
                },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php include $include_path . 'footer.php'; ?>
