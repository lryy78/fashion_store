<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

$range = $_GET['range'] ?? 30; // 7, 30, 90
$range = (int)$range;


// 1. High-Level KPIs — revenue/orders from orders table only (no JOIN duplication)
$stats_current = $pdo->prepare("
    SELECT 
        SUM(total_amount) as total_rev,
        COUNT(DISTINCT id) as total_ord,
        COUNT(DISTINCT user_id) as total_cust
    FROM orders
    WHERE status NOT IN ('cancelled','refunded')
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
");
$stats_current->execute([$range - 1]);
$curr = $stats_current->fetch();
$total_revenue = $curr['total_rev'] ?: 0;
$total_orders = $curr['total_ord'] ?: 0;
$total_customers = $curr['total_cust'] ?: 0;

// Net profit needs cost — calculate as: total_revenue - total_cost (total_amount already includes discount)
$cost_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity * p.cost_price), 0)
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN product_variations pv ON oi.variation_id = pv.id
    LEFT JOIN products p ON pv.product_id = p.id
    WHERE o.status NOT IN ('cancelled','refunded')
    AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
");
$cost_stmt->execute([$range - 1]);
$total_cost = $cost_stmt->fetchColumn() ?: 0;
$net_profit = $total_revenue - $total_cost;



$stats_prev = $pdo->prepare("
    SELECT SUM(o.total_amount) as total_rev
    FROM orders o
    WHERE o.status NOT IN ('cancelled','refunded')
    AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    AND o.created_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)
");
$stats_prev->execute([($range * 2) - 1, $range - 1]);
$prev_revenue = $stats_prev->fetchColumn() ?: 0;
$growth_percent = $prev_revenue > 0 ? (($total_revenue - $prev_revenue) / $prev_revenue) * 100 : ($total_revenue > 0 ? 100 : 0);

// 2. Daily Analytics for Chart.js (revenue from orders, profit from subquery — no JOIN inflation)
$stmt = $pdo->prepare("
    SELECT 
        DATE(o.created_at) as date, 
        SUM(o.total_amount) as total,
        SUM(o.total_amount) - SUM(COALESCE(pf.total_cost, 0)) as profit
    FROM orders o
    LEFT JOIN (
        SELECT oi.order_id, SUM(oi.quantity * p.cost_price) as total_cost
        FROM order_items oi
        LEFT JOIN product_variations pv ON oi.variation_id = pv.id
        LEFT JOIN products p ON pv.product_id = p.id
        GROUP BY oi.order_id
    ) pf ON o.id = pf.order_id
    WHERE o.status NOT IN ('cancelled','refunded') AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY DATE(o.created_at)
    ORDER BY date ASC
");
$stmt->execute([$range - 1]);
$daily_sales_raw = $stmt->fetchAll();

$chart_labels = [];
$chart_revenue = [];
$chart_profit = [];

$interval_days = 1;
if ($range == 30) $interval_days = 5;
if ($range == 90) $interval_days = 15;

$buckets = [];
for ($i = $range - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $bucket_index = floor(($range - 1 - $i) / $interval_days);
    if (!isset($buckets[$bucket_index])) {
        $buckets[$bucket_index] = ['start_date' => $d, 'end_date' => $d, 'revenue' => 0, 'profit' => 0];
    }
    $buckets[$bucket_index]['end_date'] = $d;
    foreach ($daily_sales_raw as $ds) {
        if ($ds['date'] == $d) {
            $buckets[$bucket_index]['revenue'] += (float)$ds['total'];
            $buckets[$bucket_index]['profit'] += (float)$ds['profit'];
            break;
        }
    }
}
foreach ($buckets as $b) {
    if ($b['start_date'] == $b['end_date']) {
        $chart_labels[] = date('M d', strtotime($b['start_date']));
    } else {
        $chart_labels[] = date('M d', strtotime($b['start_date'])) . ' - ' . date('M d', strtotime($b['end_date']));
    }
    $chart_revenue[] = $b['revenue'];
    $chart_profit[] = $b['profit'];
}

// 3. Top Drivers
$top_products = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as qty, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status NOT IN ('cancelled','refunded') AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 3
");
$top_products->execute([$range - 1]);
$top_products = $top_products->fetchAll();

$top_customers = $pdo->prepare("
    SELECT u.username, COUNT(o.id) as order_count, SUM(o.total_amount) as total_spent
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    WHERE o.status NOT IN ('cancelled','refunded') AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY u.id 
    ORDER BY total_spent DESC 
    LIMIT 3
");
$top_customers->execute([$range - 1]);
$top_customers = $top_customers->fetchAll();


// 5. Decision Recommendation Engine
$recommendations = [];
if ($growth_percent > 10) {
    $recommendations[] = [
        'icon' => '🚀',
        'title' => 'Momentum Strong',
        'desc' => 'Revenue is up ' . number_format($growth_percent, 1) . '% vs previous period. <strong>Recommendation:</strong> Maintain current acquisition strategy.'
    ];
} elseif ($growth_percent < 0) {
    $recommendations[] = [
        'icon' => '⚠️',
        'title' => 'Revenue Contraction',
        'desc' => 'Revenue dropped ' . number_format(abs($growth_percent), 1) . '% vs previous period. <strong>Recommendation:</strong> Analyze top product margins in Profitability section.'
    ];
}

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<style>
    .analytics-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: var(--spacing-xxl);
        flex-wrap: wrap;
        gap: 20px;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 24px;
        margin-bottom: 40px;
    }
    .stat-card-premium {
        background: #fff;
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }
    .stat-card-premium:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.08);
    }
    .stat-card-premium .stat-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--colors-muted);
        margin-bottom: 12px;
    }
    .stat-card-premium .stat-value {
        font-size: 32px;
        font-weight: 700;
        font-family: var(--typography-display-font);
        color: var(--colors-ink);
        line-height: 1.1;
    }
    .stat-card-premium .stat-desc {
        font-size: 13px;
        color: var(--colors-muted);
        margin-top: 16px;
    }
    .stat-trend {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        margin-top: 16px;
        align-self: flex-start;
    }
    .trend-up {
        background-color: rgba(34, 197, 94, 0.1);
        color: #16a34a;
    }
    .trend-down {
        background-color: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }
    .chart-container {
        background: #fff;
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    }
    .chart-header {
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 24px;
        color: var(--colors-ink);
    }

</style>

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>

    <div class="dashboard-main fade-in-up">
        <header class="analytics-header">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600;">Executive Overview</div>
                <h1 style="margin: 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Dashboard</h1>
            </div>
            <div>
                <form method="GET" style="display: flex; gap: 12px; align-items: center; background: #fff; padding: 8px 16px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <span style="font-size: 13px; color: var(--colors-muted); font-weight: 600; text-transform: uppercase;">Range</span>
                    <select name="range" onchange="this.form.submit()" style="border: none; background: transparent; font-size: 14px; font-weight: 600; color: var(--colors-ink); cursor: pointer; outline: none;">
                        <option value="7" <?php echo $range == 7 ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="30" <?php echo $range == 30 ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="90" <?php echo $range == 90 ? 'selected' : ''; ?>>Last 90 Days</option>
                    </select>
                </form>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card-premium">
                <div>
                    <div class="stat-title">Total Revenue</div>
                    <div class="stat-value" style="color: var(--colors-primary);">RM <?php echo number_format($total_revenue, 2); ?></div>
                </div>
                <?php if ($growth_percent >= 0): ?>
                    <div class="stat-trend trend-up">↑ <?php echo number_format($growth_percent, 1); ?>% vs prev</div>
                <?php else: ?>
                    <div class="stat-trend trend-down">↓ <?php echo number_format(abs($growth_percent), 1); ?>% vs prev</div>
                <?php endif; ?>
            </div>
            
            <div class="stat-card-premium">
                <div>
                    <div class="stat-title">Net Profit</div>
                    <div class="stat-value" style="color: var(--colors-success);">RM <?php echo number_format($net_profit, 2); ?></div>
                </div>
                <div class="stat-desc">True cost-basis margin</div>
            </div>
            
            <div class="stat-card-premium">
                <div>
                    <div class="stat-title">Total Orders</div>
                    <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                </div>
                <div class="stat-desc">Completed in period</div>
            </div>
            
            <div class="stat-card-premium">
                <div>
                    <div class="stat-title">Active Customers</div>
                    <div class="stat-value"><?php echo number_format($total_customers); ?></div>
                </div>
                <div class="stat-desc">Made a purchase</div>
            </div>
        </div>

        <!-- Decision Recommendation Engine -->
        <div style="margin-bottom: 24px;">
            <?php foreach ($recommendations as $rec): ?>
                <div style="display: flex; align-items: center; gap: 16px; padding: 16px 24px; background: linear-gradient(90deg, rgba(204,120,92,0.1) 0%, rgba(204,120,92,0.02) 100%); border-left: 4px solid var(--colors-primary); border-radius: 8px; margin-bottom: 8px;">
                    <div style="font-size: 24px; animation: pulse 2s infinite;"><?php echo $rec['icon']; ?></div>
                    <div style="flex: 1;">
                        <span style="font-weight: 700; font-size: 14px; color: var(--colors-ink); margin-right: 8px;">AI Insight: <?php echo htmlspecialchars($rec['title']); ?></span>
                        <span style="font-size: 13px; color: var(--colors-body);"><?php echo $rec['desc']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($recommendations)): ?>
                <div style="display: flex; align-items: center; gap: 16px; padding: 16px 24px; background: #fafafa; border-left: 4px solid var(--colors-muted); border-radius: 8px;">
                    <div style="font-size: 24px; opacity: 0.5;">🧠</div>
                    <div style="flex: 1;">
                        <span style="font-weight: 600; font-size: 14px; color: var(--colors-muted); margin-right: 8px;">AI Insight: Stable</span>
                        <span style="font-size: 13px; color: var(--colors-muted);">No immediate actions or anomalies detected in the current period.</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: stretch;">
            <!-- Main Chart -->
            <div class="chart-container" style="display: flex; flex-direction: column;">
                <h3 class="chart-header">Revenue vs Profit Trend</h3>
                <div style="flex: 1; min-height: 350px; width: 100%;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Top Drivers Sidebar -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Top Products Table -->
                <div class="chart-container" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                    <div style="padding: 16px 20px 8px 20px;">
                        <h3 class="chart-header" style="margin: 0; font-size: 13px;">Top Sales Products</h3>
                    </div>
                    <table class="data-table" style="margin: 0; width: 100%;">
                        <tbody>
                            <?php foreach ($top_products as $p): ?>
                                <tr>
                                    <td style="font-weight: 600; padding: 10px 20px; font-size: 13px;"><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td style="text-align: right; color: var(--colors-muted); font-size: 12px; padding: 10px 20px;"><?php echo $p['qty']; ?> sold</td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($top_products)): ?>
                                <tr><td colspan="2" style="text-align:center; padding: 16px; color: var(--colors-muted); font-size: 13px;">No data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Top Customers Table -->
                <div class="chart-container" style="padding: 0; overflow: hidden;">
                    <div style="padding: 16px 20px 8px 20px;">
                        <h3 class="chart-header" style="margin: 0; font-size: 13px;">Top Customers</h3>
                    </div>
                    <table class="data-table" style="margin: 0; width: 100%;">
                        <tbody>
                            <?php foreach ($top_customers as $c): ?>
                                <tr>
                                    <td style="font-weight: 600; padding: 10px 20px; font-size: 13px;"><?php echo htmlspecialchars($c['username']); ?></td>
                                    <td style="text-align: right; font-family: var(--typography-code-font); font-weight: 600; color: var(--colors-ink); padding: 10px 20px; font-size: 12px;">RM <?php echo number_format($c['total_spent'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($top_customers)): ?>
                                <tr><td colspan="2" style="text-align:center; padding: 16px; color: var(--colors-muted); font-size: 13px;">No data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = "'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
    Chart.defaults.color = '#8e8b82';

    const commonScales = {
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0, 0, 0, 0.04)', drawBorder: false },
            border: { display: false }
        },
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: {
                maxTicksLimit: 10,
                maxRotation: 45,
                minRotation: 0
            }
        }
    };

    // Revenue vs Profit Chart
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    
    let revGradient = revCtx.createLinearGradient(0, 0, 0, 400);
    revGradient.addColorStop(0, 'rgba(204, 120, 92, 0.2)');
    revGradient.addColorStop(1, 'rgba(204, 120, 92, 0)');

    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Revenue (RM)',
                    data: <?php echo json_encode($chart_revenue); ?>,
                    borderColor: '#cc785c',
                    backgroundColor: revGradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#cc785c',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Net Profit (RM)',
                    data: <?php echo json_encode($chart_profit); ?>,
                    borderColor: '#181715',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointBackgroundColor: '#181715',
                    pointRadius: 3,
                    fill: false,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'top',
                    labels: { usePointStyle: true, font: { size: 12 } }
                },
                tooltip: {
                    backgroundColor: '#181715',
                    titleFont: { size: 13 },
                    bodyFont: { size: 14, weight: 'bold' },
                    padding: 12,
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': RM ' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: commonScales
        }
    });

});
</script>

<?php include '../includes/footer.php'; ?>
