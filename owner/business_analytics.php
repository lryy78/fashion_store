<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

$range = $_GET['range'] ?? 30;
$range = (int)$range;

// 1. Order Volume Trend
$stmt = $pdo->prepare("SELECT DATE(created_at) as date, COUNT(*) as volume FROM orders WHERE status != 'cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY date ASC");
$stmt->execute([$range - 1]);
$daily_orders_raw = $stmt->fetchAll();

$chart_labels = [];
$chart_orders = [];

for ($i = $range - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('M d', strtotime($d));
    $found = false;
    foreach ($daily_orders_raw as $do) {
        if ($do['date'] == $d) {
            $chart_orders[] = (int)$do['volume'];
            $found = true;
            break;
        }
    }
    if (!$found) $chart_orders[] = 0;
}

// 2. Average Order Value (AOV) over time
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;
$global_aov = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// 3. Category Performance (Pie Chart)
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
")->fetchAll();

$pie_labels = [];
$pie_data = [];
foreach ($categories_raw as $cat) {
    $pie_labels[] = $cat['name'];
    $pie_data[] = (float)$cat['revenue'];
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
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600; font-family: var(--typography-body-font);">Deep Dive</div>
                <h1 style="margin: 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Business Analytics</h1>
            </div>
            <div style="text-align: right;">
                <form method="GET" style="display: flex; gap: 8px; align-items: center;">
                    <span style="font-size: 12px; color: var(--colors-muted); font-weight: 600; text-transform: uppercase;">Range:</span>
                    <select name="range" onchange="this.form.submit()" class="form-input" style="padding: 6px 12px; font-size: 13px; height: auto;">
                        <option value="7" <?php echo $range == 7 ? 'selected' : ''; ?>>7 Days</option>
                        <option value="30" <?php echo $range == 30 ? 'selected' : ''; ?>>30 Days</option>
                        <option value="90" <?php echo $range == 90 ? 'selected' : ''; ?>>90 Days</option>
                    </select>
                </form>
            </div>
        </header>

        <div class="stats-row" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card">
                <div class="stat-label">Global AOV</div>
                <div class="stat-value" style="color: var(--colors-ink);">RM <?php echo number_format($global_aov, 2); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Average yield per transaction</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Transactions</div>
                <div class="stat-value" style="color: var(--colors-ink);"><?php echo number_format($total_orders); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Lifetime valid orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Top Category</div>
                <div class="stat-value" style="color: var(--colors-primary);"><?php echo htmlspecialchars($pie_labels[0] ?? 'N/A'); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Highest revenue driver</div>
            </div>
        </div>

        <div class="dashboard-split" style="grid-template-columns: 2fr 1fr; gap: 32px; align-items: start; margin-top: 40px;">
            <div style="display: flex; flex-direction: column; gap: 32px;">
                <!-- Order Volume Chart -->
                <div class="surface-card" style="padding: 24px;">
                    <h3 style="font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px;">Order Volume Trend</h3>
                    <div style="height: 300px; width: 100%;">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 32px;">
                <!-- Category Revenue Pie Chart -->
                <div class="surface-card" style="padding: 24px;">
                    <h3 style="font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px;">Revenue by Category</h3>
                    <div style="height: 300px; width: 100%; display: flex; justify-content: center;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Order Volume Chart
    const ordCtx = document.getElementById('ordersChart').getContext('2d');
    new Chart(ordCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Orders',
                    data: <?php echo json_encode($chart_orders); ?>,
                    backgroundColor: '#181715',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0' },
                    ticks: { stepSize: 1 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Category Pie Chart
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($pie_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($pie_data); ?>,
                backgroundColor: ['#cc785c', '#181715', '#efe9de', '#6c6a64', '#8e8b82', '#faf9f5'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
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
            cutout: '70%'
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
