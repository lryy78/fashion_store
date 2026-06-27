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
    SELECT (SELECT SUM(total_amount) FROM orders WHERE status NOT IN ('cancelled','refunded'))
           - COALESCE(SUM(pc.total_cost), 0)
    FROM (
        SELECT oi.order_id, SUM(oi.quantity * p.cost_price) AS total_cost
        FROM order_items oi
        LEFT JOIN product_variations pv ON oi.variation_id = pv.id
        LEFT JOIN products p ON pv.product_id = p.id
        WHERE oi.order_id IN (SELECT id FROM orders WHERE status NOT IN ('cancelled','refunded'))
        GROUP BY oi.order_id
    ) pc
")->fetchColumn() ?: 0;

$total_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status NOT IN ('cancelled','refunded')")->fetchColumn() ?: 0;

// 2. Month-over-Month Growth
$this_month_rev = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status NOT IN ('cancelled','refunded') AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn() ?: 0;
$last_month_rev = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status NOT IN ('cancelled','refunded') AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))")->fetchColumn() ?: 0;

$this_month_profit = $pdo->query("
    SELECT (SELECT SUM(total_amount) FROM orders WHERE status NOT IN ('cancelled','refunded') AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()))
           - COALESCE(SUM(pc.total_cost), 0)
    FROM (
        SELECT oi.order_id, SUM(oi.quantity * p.cost_price) AS total_cost
        FROM order_items oi
        LEFT JOIN product_variations pv ON oi.variation_id = pv.id
        LEFT JOIN products p ON pv.product_id = p.id
        WHERE oi.order_id IN (SELECT id FROM orders WHERE status NOT IN ('cancelled','refunded') AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()))
        GROUP BY oi.order_id
    ) pc
")->fetchColumn() ?: 0;

$last_month_profit = $pdo->query("
    SELECT (SELECT SUM(total_amount) FROM orders WHERE status NOT IN ('cancelled','refunded') AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)))
           - COALESCE(SUM(pc.total_cost), 0)
    FROM (
        SELECT oi.order_id, SUM(oi.quantity * p.cost_price) AS total_cost
        FROM order_items oi
        LEFT JOIN product_variations pv ON oi.variation_id = pv.id
        LEFT JOIN products p ON pv.product_id = p.id
        WHERE oi.order_id IN (SELECT id FROM orders WHERE status NOT IN ('cancelled','refunded') AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)))
        GROUP BY oi.order_id
    ) pc
")->fetchColumn() ?: 0;

$mom_growth = $last_month_rev > 0 ? (($this_month_rev - $last_month_rev) / $last_month_rev) * 100 : ($this_month_rev > 0 ? 100 : 0);

// 3. Monthly Revenue & Profit Data for Chart (Last 12 months)
$monthly_raw = $pdo->query("
    SELECT 
        DATE_FORMAT(o.created_at, '%Y-%m') as month, 
        SUM(o.total_amount) as total,
        SUM(o.total_amount) - SUM(COALESCE(pc.total_cost, 0)) as profit
    FROM orders o
    LEFT JOIN (
        SELECT oi.order_id, SUM(oi.quantity * p.cost_price) as total_cost
        FROM order_items oi
        LEFT JOIN product_variations pv ON oi.variation_id = pv.id
        LEFT JOIN products p ON pv.product_id = p.id
        GROUP BY oi.order_id
    ) pc ON o.id = pc.order_id
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
        SUM(o.total_amount) - SUM(COALESCE(pc.total_cost, 0)) as profit
    FROM orders o
    LEFT JOIN (
        SELECT oi.order_id, SUM(oi.quantity * p.cost_price) as total_cost
        FROM order_items oi
        LEFT JOIN product_variations pv ON oi.variation_id = pv.id
        LEFT JOIN products p ON pv.product_id = p.id
        GROUP BY oi.order_id
    ) pc ON o.id = pc.order_id
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

// Derived KPIs
$profit_margin = $total_revenue > 0 ? ($net_profit / $total_revenue) * 100 : 0;
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

$include_path = '../includes/';
include $include_path . 'header.php';
?>
<link rel="stylesheet" href="/fashion_store/assets/css/revenue_reports.css">

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>

    <div class="dashboard-main fade-in-up">

        <!-- ═══════════════════════════════════════════ -->
        <!-- Page Header                                 -->
        <!-- ═══════════════════════════════════════════ -->
        <header class="rev-page-header">
            <div>
                <div class="rev-page-eyebrow">Financial Overview</div>
                <h1 class="rev-page-title">Revenue Reports</h1>
                <p class="rev-page-subtitle">All figures exclude cancelled &amp; refunded orders · Profit = Revenue − Cost Price</p>
            </div>
        </header>

        <!-- ═══════════════════════════════════════════ -->
        <!-- Section 1 · Key Performance Indicators      -->
        <!-- ═══════════════════════════════════════════ -->
        <section class="rev-section">
            <h2 class="rev-section-title">Key Metrics</h2>
            <div class="rev-kpi-grid">

                <!-- Total Revenue -->
                <div class="rev-kpi-card">
                    <div class="rev-kpi-icon rev-kpi-icon--revenue">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="rev-kpi-label">Total Revenue</div>
                    <div class="rev-kpi-value rev-kpi-value--primary">RM <?php echo number_format($total_revenue, 2); ?></div>
                    <div class="rev-kpi-hint"><?php echo number_format($total_orders); ?> orders all-time</div>
                </div>

                <!-- Net Profit -->
                <div class="rev-kpi-card">
                    <div class="rev-kpi-icon rev-kpi-icon--profit">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    </div>
                    <div class="rev-kpi-label">Net Profit</div>
                    <div class="rev-kpi-value rev-kpi-value--success">RM <?php echo number_format($net_profit, 2); ?></div>
                    <div class="rev-kpi-hint">Margin: <?php echo number_format($profit_margin, 1); ?>%</div>
                </div>

                <!-- This Month -->
                <div class="rev-kpi-card">
                    <div class="rev-kpi-icon rev-kpi-icon--month">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="rev-kpi-label">This Month</div>
                    <div class="rev-kpi-value">RM <?php echo number_format($this_month_rev, 2); ?></div>
                    <div class="rev-kpi-sub">Profit: RM <?php echo number_format($this_month_profit, 2); ?></div>
                    <?php if ($mom_growth >= 0): ?>
                        <div class="stat-trend trend-up">↑ <?php echo number_format($mom_growth, 1); ?>% vs last month</div>
                    <?php else: ?>
                        <div class="stat-trend trend-down">↓ <?php echo number_format(abs($mom_growth), 1); ?>% vs last month</div>
                    <?php endif; ?>
                </div>

                <!-- Last Month -->
                <div class="rev-kpi-card">
                    <div class="rev-kpi-icon rev-kpi-icon--last">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="rev-kpi-label">Last Month</div>
                    <div class="rev-kpi-value">RM <?php echo number_format($last_month_rev, 2); ?></div>
                    <div class="rev-kpi-sub">Profit: RM <?php echo number_format($last_month_profit, 2); ?></div>
                    <div class="rev-kpi-hint">Previous period baseline</div>
                </div>

                <!-- Avg Order Value -->
                <div class="rev-kpi-card">
                    <div class="rev-kpi-icon rev-kpi-icon--aov">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    </div>
                    <div class="rev-kpi-label">Avg Order Value</div>
                    <div class="rev-kpi-value">RM <?php echo number_format($avg_order_value, 2); ?></div>
                    <div class="rev-kpi-hint">Revenue ÷ <?php echo number_format($total_orders); ?> orders</div>
                </div>

                <!-- Profit Margin -->
                <div class="rev-kpi-card">
                    <div class="rev-kpi-icon rev-kpi-icon--margin">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                    </div>
                    <div class="rev-kpi-label">Profit Margin</div>
                    <div class="rev-kpi-value rev-kpi-value--success"><?php echo number_format($profit_margin, 1); ?>%</div>
                    <div class="rev-kpi-hint">Net profit ÷ revenue</div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- Section 2 · Revenue vs Profit Trend         -->
        <!-- ═══════════════════════════════════════════ -->
        <section class="rev-section">
            <h2 class="rev-section-title">Revenue vs Profit Trend</h2>
            <p class="rev-section-desc">Monthly comparison of gross revenue and net profit over the last 12 months. The gap between lines represents total cost of goods sold.</p>
            <div class="rev-chart-card">
                <div class="rev-chart-wrap">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════ -->
        <!-- Section 3 · Daily Analytics + Category Split -->
        <!-- ═══════════════════════════════════════════ -->
        <section class="rev-section">
            <div class="rev-two-col">

                <!-- Daily Breakdown Table -->
                <div class="rev-panel">
                    <div class="rev-panel-header">
                        <h2 class="rev-section-title" style="margin-bottom:0;">Daily Analytics</h2>
                        <span class="badge">Last 5 Days</span>
                    </div>
                    <p class="rev-section-desc" style="padding: 0 24px;">Rolling window of recent orders, revenue, and profit per day.</p>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Revenue (RM)</th>
                                <th style="text-align: right;">Profit (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daily_raw)): ?>
                                <tr><td colspan="4" style="text-align:center; color: var(--colors-muted); padding: 32px;">No orders in the last 5 days</td></tr>
                            <?php else: ?>
                                <?php foreach (array_reverse($daily_raw) as $d): ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?php echo date('D, M d', strtotime($d['date'])); ?></td>
                                        <td><span class="badge badge-info"><?php echo $d['volume']; ?></span></td>
                                        <td style="font-family: var(--typography-code-font); font-weight: 600;"><?php echo number_format($d['total'], 2); ?></td>
                                        <td style="text-align: right; font-family: var(--typography-code-font); color: var(--colors-success); font-weight: 700;"><?php echo number_format($d['profit'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Category Revenue Breakdown (Donut + Table) -->
                <div class="rev-panel">
                    <div class="rev-panel-header">
                        <h2 class="rev-section-title" style="margin-bottom:0;">Revenue by Category</h2>
                    </div>
                    <p class="rev-section-desc" style="padding: 0 24px;">How each product category contributes to total revenue.</p>

                    <!-- Donut Chart -->
                    <div class="rev-donut-wrap">
                        <canvas id="categoryDonut"></canvas>
                    </div>

                    <!-- Category Breakdown Table -->
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th style="text-align: right;">Revenue (RM)</th>
                                <th style="text-align: right;">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_cat_rev = array_sum(array_column($cat_raw, 'revenue')) ?: 1;
                            foreach ($cat_raw as $cat): 
                                $pct = round(($cat['revenue'] / $total_cat_rev) * 100);
                            ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; font-size: 13px;"><?php echo htmlspecialchars($cat['name']); ?></div>
                                        <div class="rev-bar-track">
                                            <div class="rev-bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                                        </div>
                                    </td>
                                    <td style="text-align: right; font-family: var(--typography-code-font); font-weight: 600; font-size: 13px; white-space: nowrap;"><?php echo number_format($cat['revenue'], 2); ?></td>
                                    <td style="text-align: right; font-size: 13px; color: var(--colors-muted); font-weight: 600;"><?php echo $pct; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </div>
</div>

<!-- ═══════════════════════════════════════════ -->
<!-- Charts JS                                   -->
<!-- ═══════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    /* ── Revenue vs Profit Line Chart ── */
    const lineCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($month_labels); ?>,
            datasets: [
                {
                    label: 'Gross Revenue',
                    data: <?php echo json_encode($month_revenue); ?>,
                    borderColor: '#cc785c',
                    backgroundColor: 'rgba(204,120,92,0.08)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#cc785c',
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Net Profit',
                    data: <?php echo json_encode($month_profit); ?>,
                    borderColor: '#5db872',
                    backgroundColor: 'rgba(93,184,114,0.06)',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#5db872',
                    pointRadius: 3,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 800, easing: 'easeOutQuart' },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, padding: 20, font: { family: "'Inter', sans-serif", size: 12 } } },
                tooltip: {
                    backgroundColor: '#181715',
                    titleFont: { family: "'Inter', sans-serif" },
                    bodyFont: { family: "'Inter', sans-serif" },
                    padding: 14,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': RM ' + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { callback: v => 'RM ' + v.toLocaleString(), padding: 10, font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { padding: 10, font: { size: 11 } }
                }
            }
        }
    });

    /* ── Category Donut Chart ── */
    const donutCtx = document.getElementById('categoryDonut');
    if (donutCtx) {
        const catLabels = <?php echo json_encode(array_column($cat_raw, 'name')); ?>;
        const catData   = <?php echo json_encode(array_map('floatval', array_column($cat_raw, 'revenue'))); ?>;
        const palette   = ['#cc785c','#5db8a6','#e8a55a','#5d8fb8','#b85d9e','#8bc34a','#e91e63','#9c27b0','#ff9800','#607d8b'];

        new Chart(donutCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catData,
                    backgroundColor: palette.slice(0, catLabels.length),
                    borderWidth: 2,
                    borderColor: '#faf9f5',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                animation: { duration: 800, easing: 'easeOutQuart' },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { family: "'Inter', sans-serif", size: 12 } }
                    },
                    tooltip: {
                        backgroundColor: '#181715',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a,b) => a + b, 0);
                                const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                return ctx.label + ': RM ' + ctx.parsed.toLocaleString(undefined, {minimumFractionDigits:2}) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php include $include_path . 'footer.php'; ?>
