<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

$range = $_GET['range'] ?? 30;
$range = (int)$range;

// 1. Daily Data (Order Volume & Revenue Trend)
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as volume, SUM(total_amount) as daily_revenue 
    FROM orders 
    WHERE status NOT IN ('cancelled','refunded') 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
");
$stmt->execute([$range - 1]);
$daily_data_raw = $stmt->fetchAll();

$chart_labels = [];
$chart_orders = [];
$chart_revenue = [];

$interval_days = 1;
if ($range == 30) $interval_days = 5;
if ($range == 90) $interval_days = 15;

$buckets = [];
for ($i = $range - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    
    $bucket_index = floor(($range - 1 - $i) / $interval_days);
    if (!isset($buckets[$bucket_index])) {
        $buckets[$bucket_index] = [
            'start_date' => $d,
            'end_date' => $d,
            'orders' => 0,
            'revenue' => 0
        ];
    }
    $buckets[$bucket_index]['end_date'] = $d;
    
    foreach ($daily_data_raw as $row) {
        if ($row['date'] == $d) {
            $buckets[$bucket_index]['orders'] += (int)$row['volume'];
            $buckets[$bucket_index]['revenue'] += (float)$row['daily_revenue'];
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
    $chart_orders[] = $b['orders'];
    $chart_revenue[] = $b['revenue'];
}

// 2. Aggregate Stats for Range
$stmt = $pdo->prepare("
    SELECT SUM(total_amount) as total_rev, COUNT(*) as total_ord 
    FROM orders 
    WHERE status NOT IN ('cancelled','refunded') 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
");
$stmt->execute([$range - 1]);
$agg = $stmt->fetch();
$total_revenue = $agg['total_rev'] ?: 0;
$total_orders = $agg['total_ord'] ?: 0;
$period_aov = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// 3. Category Performance for Range
$stmt = $pdo->prepare("
    SELECT c.name, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status NOT IN ('cancelled','refunded')
    AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY c.id
    ORDER BY revenue DESC
");
$stmt->execute([$range - 1]);
$categories_raw = $stmt->fetchAll();

$pie_labels = [];
$pie_data = [];
foreach ($categories_raw as $cat) {
    $pie_labels[] = $cat['name'];
    $pie_data[] = (float)$cat['revenue'];
}

$top_category = $pie_labels[0] ?? 'N/A';

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
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
    .chart-container {
        background: #fff;
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        margin-bottom: 32px;
    }
    .chart-header {
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 24px;
        color: var(--colors-ink);
    }
    .charts-split {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 32px;
    }
    @media (max-width: 992px) {
        .charts-split {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>

    <div class="dashboard-main fade-in-up">
        <header class="analytics-header">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600;">Deep Dive</div>
                <h1 style="margin: 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Business Analytics</h1>
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
                <div class="stat-title">Total Revenue</div>
                <div class="stat-value">RM <?php echo number_format($total_revenue, 2); ?></div>
                <div class="stat-desc">Generated in the last <?php echo $range; ?> days</div>
            </div>
            <div class="stat-card-premium">
                <div class="stat-title">Total Orders</div>
                <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                <div class="stat-desc">Valid transactions in period</div>
            </div>
            <div class="stat-card-premium">
                <div class="stat-title">Average Order Value</div>
                <div class="stat-value">RM <?php echo number_format($period_aov, 2); ?></div>
                <div class="stat-desc">Average yield per transaction</div>
            </div>
            <div class="stat-card-premium">
                <div class="stat-title">Top Category</div>
                <div class="stat-value" style="color: var(--colors-primary); font-size: 28px;"><?php echo htmlspecialchars($top_category); ?></div>
                <div class="stat-desc">Highest revenue driver</div>
            </div>
        </div>

        <div class="charts-split">
            <div class="chart-container">
                <h3 class="chart-header">Revenue & Orders Trend</h3>
                <div style="height: 350px; width: 100%;">
                    <canvas id="mixedChart"></canvas>
                </div>
            </div>

            <div class="chart-container">
                <h3 class="chart-header">Revenue by Category</h3>
                <div style="height: 350px; width: 100%; display: flex; justify-content: center;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = "'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
    Chart.defaults.color = '#8e8b82';

    // Common scales for line/bar charts
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

    // 1. Revenue & Orders Mixed Chart
    const mixCtx = document.getElementById('mixedChart').getContext('2d');
    
    // Create gradient
    let revGradient = mixCtx.createLinearGradient(0, 0, 0, 400);
    revGradient.addColorStop(0, 'rgba(204, 120, 92, 0.2)'); // var(--colors-primary)
    revGradient.addColorStop(1, 'rgba(204, 120, 92, 0)');

    new Chart(mixCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    type: 'line',
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
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    type: 'bar',
                    label: 'Orders',
                    data: <?php echo json_encode($chart_orders); ?>,
                    backgroundColor: '#181715',
                    borderRadius: 6,
                    barPercentage: 0.5,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        font: { size: 12 }
                    }
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
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataset.type === 'line') {
                                label += 'RM ' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                            } else {
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: commonScales.x,
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.04)', drawBorder: false },
                    border: { display: false },
                    title: {
                        display: true,
                        text: 'Revenue (RM)',
                        color: 'var(--colors-ink)',
                        font: { size: 12, weight: '600' }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: { drawOnChartArea: false }, // avoid overlapping grid lines
                    border: { display: false },
                    ticks: { stepSize: 1 },
                    title: {
                        display: true,
                        text: 'Orders',
                        color: 'var(--colors-ink)',
                        font: { size: 12, weight: '600' }
                    }
                }
            }
        }
    });

    // 3. Category Pie Chart
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($pie_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($pie_data); ?>,
                backgroundColor: ['#cc785c', '#181715', '#6c6a64', '#8e8b82', '#dcd8d1', '#faf9f5'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    backgroundColor: '#181715',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / total) * 100) + '%';
                            return ' RM ' + value.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' (' + percentage + ')';
                        }
                    }
                }
            },
            cutout: '75%'
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
