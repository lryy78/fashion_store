<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

$range = $_GET['range'] ?? 30; // 7, 30, 90
$range = (int)$range;

// 1. High-Level KPIs
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;

$net_profit = $pdo->query("
    SELECT SUM(oi.quantity * (oi.price - p.cost_price)) 
    FROM order_items oi 
    JOIN product_variations pv ON oi.variation_id = pv.id 
    JOIN products p ON pv.product_id = p.id 
    JOIN orders o ON oi.order_id = o.id 
    WHERE o.status != 'cancelled'
")->fetchColumn() ?: 0;

$total_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;
$total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'")->fetchColumn() ?: 0;

// 2. Growth Calculation (Last 30 Days vs Previous 30 Days)
$revenue_last_30 = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn() ?: 0;
$revenue_prev_30 = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn() ?: 0;
$growth_percent = $revenue_prev_30 > 0 ? (($revenue_last_30 - $revenue_prev_30) / $revenue_prev_30) * 100 : ($revenue_last_30 > 0 ? 100 : 0);

// 3. Daily Analytics for Chart.js
$display_days = 5; 
$daily_sales_raw = $pdo->query("
    SELECT 
        DATE(o.created_at) as date, 
        SUM(o.total_amount) as total,
        SUM(oi.quantity * (oi.price - p.cost_price)) as profit
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN product_variations pv ON oi.variation_id = pv.id
    LEFT JOIN products p ON pv.product_id = p.id
    WHERE o.status != 'cancelled' AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ".($display_days-1)." DAY)
    GROUP BY date
    ORDER BY date ASC
")->fetchAll();

$chart_labels = [];
$chart_revenue = [];
$chart_profit = [];

for ($i = $display_days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('M d', strtotime($d));
    $found = false;
    foreach ($daily_sales_raw as $ds) {
        if ($ds['date'] == $d) {
            $chart_revenue[] = (float)$ds['total'];
            $chart_profit[] = (float)$ds['profit'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $chart_revenue[] = 0;
        $chart_profit[] = 0;
    }
}

// 4. Top Drivers (Products & Customers)
$top_products = $pdo->query("
    SELECT p.name, SUM(oi.quantity) as qty, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 3
")->fetchAll();

$top_customers = $pdo->query("
    SELECT u.username, COUNT(o.id) as order_count, SUM(o.total_amount) as total_spent
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    WHERE o.status != 'cancelled'
    GROUP BY u.id 
    ORDER BY total_spent DESC 
    LIMIT 3
")->fetchAll();

// 5. Category Performance for Side-by-Side Chart
$categories_raw = $pdo->query("
    SELECT c.name, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY c.id
    ORDER BY revenue DESC
    LIMIT 5
")->fetchAll();

$category_labels = [];
$category_revenue = [];
foreach ($categories_raw as $cat) {
    $category_labels[] = $cat['name'];
    $category_revenue[] = (float)$cat['revenue'];
}

// 6. Decision Recommendation Engine
$recommendations = [];

if ($growth_percent > 10) {
    $recommendations[] = [
        'icon' => '🚀',
        'title' => 'Momentum Strong',
        'desc' => 'Revenue is up ' . number_format($growth_percent, 1) . '% M-o-M. <strong>Recommendation:</strong> Maintain current acquisition strategy.'
    ];
} elseif ($growth_percent < 0) {
    $recommendations[] = [
        'icon' => '⚠️',
        'title' => 'Revenue Contraction',
        'desc' => 'Revenue dropped ' . number_format(abs($growth_percent), 1) . '% M-o-M. <strong>Recommendation:</strong> Analyze top product margins in Profitability section.'
    ];
}

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600; font-family: var(--typography-body-font);">Executive Overview</div>
                <h1 style="margin: 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Dashboard</h1>
            </div>
            <div style="text-align: right;">
                <form method="GET" style="display: flex; gap: 8px; align-items: center;">
                    <span style="font-size: 12px; color: var(--colors-muted); font-weight: 600; text-transform: uppercase;">Trend Range:</span>
                    <select name="range" onchange="this.form.submit()" class="form-input" style="padding: 6px 12px; font-size: 13px; height: auto;">
                        <option value="7" <?php echo $range == 7 ? 'selected' : ''; ?>>7 Days</option>
                        <option value="30" <?php echo $range == 30 ? 'selected' : ''; ?>>30 Days</option>
                        <option value="90" <?php echo $range == 90 ? 'selected' : ''; ?>>90 Days</option>
                    </select>
                </form>
            </div>
        </header>

        <div class="stats-row" style="grid-template-columns: repeat(4, 1fr);">
            <div class="stat-card">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value" style="color: var(--colors-primary);">RM <?php echo number_format($total_revenue, 2); ?></div>
                <?php if ($growth_percent >= 0): ?>
                    <div class="stat-trend trend-up">↑ <?php echo number_format($growth_percent, 1); ?>% vs last 30d</div>
                <?php else: ?>
                    <div class="stat-trend trend-down">↓ <?php echo number_format(abs($growth_percent), 1); ?>% vs last 30d</div>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <div class="stat-label">Net Profit</div>
                <div class="stat-value" style="color: var(--colors-success);">RM <?php echo number_format($net_profit, 2); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">True cost-basis margin</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Lifetime completion</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Customers</div>
                <div class="stat-value"><?php echo number_format($total_customers); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Registered buyers</div>
            </div>
        </div>

        <div class="dashboard-split" style="grid-template-columns: 2fr 1fr; gap: 32px; align-items: start; margin-top: 40px;">
            <div style="display: flex; flex-direction: column; gap: 32px;">
                <!-- Charts Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                    <!-- Revenue vs Profit Chart -->
                    <div class="surface-card" style="padding: 24px;">
                        <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px;">Revenue vs Profit Trend</h3>
                        <div style="height: 250px; width: 100%;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Category Revenue Chart -->
                    <div class="surface-card" style="padding: 24px;">
                        <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px;">Revenue by Category</h3>
                        <div style="height: 250px; width: 100%;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                    <!-- Top Products -->
                    <div class="surface-card" style="padding: 0; overflow: hidden;">
                        <div style="padding: 20px 24px; border-bottom: 1px solid var(--colors-hairline-soft);">
                            <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">Top Products</h3>
                        </div>
                        <table class="data-table" style="margin: 0;">
                            <tbody>
                                <?php foreach ($top_products as $p): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td style="text-align: right; color: var(--colors-muted); font-size: 13px;"><?php echo $p['qty']; ?> sold</td>
                                        <td style="text-align: right; font-family: var(--typography-code-font); font-weight: 600; color: var(--colors-ink);">RM <?php echo number_format($p['revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Top Customers -->
                    <div class="surface-card" style="padding: 0; overflow: hidden;">
                        <div style="padding: 20px 24px; border-bottom: 1px solid var(--colors-hairline-soft);">
                            <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">Top Customers</h3>
                        </div>
                        <table class="data-table" style="margin: 0;">
                            <tbody>
                                <?php foreach ($top_customers as $c): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($c['username']); ?></td>
                                        <td style="text-align: right; color: var(--colors-muted); font-size: 13px;"><?php echo $c['order_count']; ?> orders</td>
                                        <td style="text-align: right; font-family: var(--typography-code-font); font-weight: 600; color: var(--colors-ink);">RM <?php echo number_format($c['total_spent'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 32px;">
                <!-- Decision Recommendation Engine -->
                <div class="surface-card" style="padding: 0; overflow: hidden; border: 1px solid var(--colors-primary);">
                    <div style="padding: 20px 24px; background: linear-gradient(135deg, var(--colors-surface-soft) 0%, #fff 100%); border-bottom: 1px solid var(--colors-hairline-soft); display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 20px;">🧠</span>
                        <h3 style="font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0; color: var(--colors-primary);">Decision Engine</h3>
                    </div>
                    <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                        <a href="product_profitability.php" class="button-primary" style="width: 100%; justify-content: center;">Product Profitability</a>
                        <a href="vouchers.php" class="button-secondary" style="width: 100%; justify-content: center;">Campaign Tracking</a>
                        <?php foreach ($recommendations as $rec): ?>
                            <div style="display: flex; gap: 16px; align-items: flex-start; padding: 16px; background: #fafafa; border-radius: 8px; border: 1px solid var(--colors-hairline);">
                                <div style="font-size: 24px;"><?php echo $rec['icon']; ?></div>
                                <div>
                                    <div style="font-weight: 700; font-size: 14px; margin-bottom: 4px; color: var(--colors-ink);"><?php echo htmlspecialchars($rec['title']); ?></div>
                                    <div style="font-size: 13px; color: var(--colors-body); line-height: 1.5;"><?php echo $rec['desc']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($recommendations)): ?>
                            <div style="text-align: center; color: var(--colors-muted); font-size: 13px;">No immediate actions required.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Revenue',
                    data: <?php echo json_encode($chart_revenue); ?>,
                    borderColor: '#cc785c',
                    backgroundColor: 'rgba(204, 120, 92, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Net Profit',
                    data: <?php echo json_encode($chart_profit); ?>,
                    borderColor: '#181715',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': RM ' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0' },
                    ticks: { callback: function(value) { return 'RM ' + value; } }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
    // Category Revenue Bar Chart
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($category_labels); ?>,
            datasets: [{
                label: 'Revenue by Category',
                data: <?php echo json_encode($category_revenue); ?>,
                backgroundColor: 'rgba(204, 120, 92, 0.6)',
                borderColor: '#cc785c',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': RM ' + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2}) } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'RM ' + v } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
