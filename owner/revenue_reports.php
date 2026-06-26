<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

// 1. Total Revenue & True Profit
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status NOT IN ('cancelled','refunded')")->fetchColumn() ?: 0;

$net_profit = $pdo->query("
    SELECT SUM(oi.quantity * (oi.price - p.cost_price)) 
    FROM order_items oi 
    JOIN product_variations pv ON oi.variation_id = pv.id 
    JOIN products p ON pv.product_id = p.id 
    JOIN orders o ON oi.order_id = o.id 
    WHERE o.status NOT IN ('cancelled','refunded')
")->fetchColumn() ?: 0;

$total_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status NOT IN ('cancelled','refunded')")->fetchColumn() ?: 0;

// 2. Month-over-Month Growth
$this_month_rev = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status NOT IN ('cancelled','refunded') AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn() ?: 0;
$last_month_rev = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status NOT IN ('cancelled','refunded') AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))")->fetchColumn() ?: 0;

$this_month_profit = $pdo->query("
    SELECT SUM(oi.quantity * (oi.price - p.cost_price)) 
    FROM order_items oi 
    JOIN product_variations pv ON oi.variation_id = pv.id 
    JOIN products p ON pv.product_id = p.id 
    JOIN orders o ON oi.order_id = o.id 
    WHERE o.status NOT IN ('cancelled','refunded') AND MONTH(o.created_at) = MONTH(NOW()) AND YEAR(o.created_at) = YEAR(NOW())
")->fetchColumn() ?: 0;

$last_month_profit = $pdo->query("
    SELECT SUM(oi.quantity * (oi.price - p.cost_price)) 
    FROM order_items oi 
    JOIN product_variations pv ON oi.variation_id = pv.id 
    JOIN products p ON pv.product_id = p.id 
    JOIN orders o ON oi.order_id = o.id 
    WHERE o.status NOT IN ('cancelled','refunded') AND MONTH(o.created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(o.created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
")->fetchColumn() ?: 0;

$mom_growth = $last_month_rev > 0 ? (($this_month_rev - $last_month_rev) / $last_month_rev) * 100 : ($this_month_rev > 0 ? 100 : 0);

// 3. Monthly Revenue & Profit Data for Chart (Last 12 months)
$monthly_raw = $pdo->query("
    SELECT 
        DATE_FORMAT(o.created_at, '%Y-%m') as month, 
        SUM(o.total_amount) as total,
        SUM(oi.quantity * (oi.price - p.cost_price)) as profit
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN product_variations pv ON oi.variation_id = pv.id
    LEFT JOIN products p ON pv.product_id = p.id
    WHERE o.status NOT IN ('cancelled','refunded')
    GROUP BY month
    ORDER BY month ASC
    LIMIT 12
")->fetchAll();

$month_labels = [];
$month_revenue = [];
$month_profit = [];
foreach ($monthly_raw as $m) {
    $month_labels[] = date('M Y', strtotime($m['month'] . '-01'));
    $month_revenue[] = (float)$m['total'];
    $month_profit[] = (float)$m['profit'];
}

// 4. Daily breakdown (last 5 days) with profit
$daily_raw = $pdo->query("
    SELECT 
        DATE(o.created_at) as date, 
        SUM(o.total_amount) as total, 
        COUNT(DISTINCT o.id) as volume,
        SUM(oi.quantity * (oi.price - p.cost_price)) as profit
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN product_variations pv ON oi.variation_id = pv.id
    LEFT JOIN products p ON pv.product_id = p.id
    WHERE o.status NOT IN ('cancelled','refunded') AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 4 DAY)
    GROUP BY date
    ORDER BY date ASC
")->fetchAll();

// 5. Category Revenue for breakdown
$cat_raw = $pdo->query("
    SELECT c.name, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status NOT IN ('cancelled','refunded')
    GROUP BY c.id ORDER BY revenue DESC
")->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600; font-family: var(--typography-body-font);">Financial Intelligence</div>
                <h1 style="margin: 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Revenue Reports</h1>
            </div>
            <div style="font-size: 13px; color: var(--colors-muted);">True margin model (Cost-Basis)</div>
        </header>

        <!-- KPI Row -->
        <div class="stats-row" style="grid-template-columns: repeat(4, 1fr);">
            <div class="stat-card">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value" style="color: var(--colors-primary);">RM <?php echo number_format($total_revenue, 2); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Gross lifecycle revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Net Profit</div>
                <div class="stat-value" style="color: var(--colors-success);">RM <?php echo number_format($net_profit, 2); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Actual cost-basis margin</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">This Month</div>
                <div class="stat-value">RM <?php echo number_format($this_month_rev, 2); ?></div>
                <div style="margin-top: 4px; font-size: 11px; color: var(--colors-success);">Profit: RM <?php echo number_format($this_month_profit, 2); ?></div>
                <?php if ($mom_growth >= 0): ?>
                    <div class="stat-trend trend-up">↑ <?php echo number_format($mom_growth, 1); ?>% vs last month</div>
                <?php else: ?>
                    <div class="stat-trend trend-down">↓ <?php echo number_format(abs($mom_growth), 1); ?>% vs last month</div>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <div class="stat-label">Last Month</div>
                <div class="stat-value">RM <?php echo number_format($last_month_rev, 2); ?></div>
                <div style="margin-top: 4px; font-size: 11px; color: var(--colors-success);">Profit: RM <?php echo number_format($last_month_profit, 2); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Previous month revenue</div>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 32px; margin-top: 40px;">
            <!-- Monthly Revenue Trend Chart -->
            <div class="surface-card" style="padding: 24px;">
                <h3 style="font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px;">Revenue vs True Profit Trend</h3>
                <div style="height: 300px; width: 100%;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start;">
                <!-- Daily Breakdown Table -->
                <div class="surface-card" style="padding: 0; overflow: hidden;">
                    <div style="padding: 20px 24px; border-bottom: 1px solid var(--colors-hairline-soft); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">Daily Analytics — Last 5 Days</h3>
                        <span class="badge" style="font-size: 10px;">Rolling Window</span>
                    </div>
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th style="text-align: right;">True Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($daily_raw) as $d): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?php echo date('M d, Y', strtotime($d['date'])); ?></td>
                                    <td><span class="badge badge-info"><?php echo $d['volume']; ?> orders</span></td>
                                    <td style="font-family: var(--typography-code-font); font-weight: 600;">RM <?php echo number_format($d['total'], 2); ?></td>
                                    <td style="text-align: right; font-family: var(--typography-code-font); color: var(--colors-success); font-weight: 700;">RM <?php echo number_format($d['profit'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Category Revenue -->
                <div class="surface-card" style="padding: 0; overflow: hidden;">
                    <div style="padding: 20px 24px; border-bottom: 1px solid var(--colors-hairline-soft);">
                        <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">Market Share by Category</h3>
                    </div>
                    <table class="data-table" style="margin: 0;">
                        <tbody>
                            <?php 
                            $total_cat_rev = array_sum(array_column($cat_raw, 'revenue')) ?: 1;
                            foreach ($cat_raw as $cat): 
                                $pct = round(($cat['revenue'] / $total_cat_rev) * 100);
                            ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; font-size: 13px;"><?php echo htmlspecialchars($cat['name']); ?></div>
                                        <div style="height: 4px; background: var(--colors-hairline); border-radius: 2px; margin-top: 6px;">
                                            <div style="height: 100%; width: <?php echo $pct; ?>%; background: var(--colors-primary); border-radius: 2px;"></div>
                                        </div>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <div style="font-family: var(--typography-code-font); font-weight: 600; font-size: 13px;">RM <?php echo number_format($cat['revenue'], 2); ?></div>
                                        <div style="font-size: 11px; color: var(--colors-muted);"><?php echo $pct; ?>%</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($month_labels); ?>,
            datasets: [
                {
                    label: 'Gross Revenue',
                    data: <?php echo json_encode($month_revenue); ?>,
                    borderColor: '#cc785c',
                    backgroundColor: 'rgba(204,120,92,0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Net Profit',
                    data: <?php echo json_encode($month_profit); ?>,
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
                legend: { position: 'top', labels: { usePointStyle: true, padding: 20 } },
                tooltip: {
                    padding: 12,
                    callbacks: {
                        label: ctx => ctx.dataset.label + ': RM ' + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2})
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0' },
                    ticks: { callback: v => 'RM ' + v, padding: 10 }
                },
                x: { grid: { display: false }, ticks: { padding: 10 } }
            }
        }
    });
});
</script>

<?php include $include_path . 'footer.php'; ?>
