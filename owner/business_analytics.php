<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

// The default report covers the complete current calendar year. An owner may
// optionally choose an inclusive start month and end month.
$requested_start_month = trim($_GET['start_month'] ?? '');
$requested_end_month = trim($_GET['end_month'] ?? '');
$filter_error = '';
$filter_applied = false;
$current_year = (int)date('Y');
$period_start_date = new DateTimeImmutable($current_year . '-01-01');
$period_end_date = new DateTimeImmutable(($current_year + 1) . '-01-01');
$month_pattern = '/^\d{4}-(0[1-9]|1[0-2])$/';

if ($requested_start_month !== '' || $requested_end_month !== '') {
    if ($requested_start_month === '' || $requested_end_month === '') {
        $filter_error = 'Select both a start month and an end month.';
    } elseif (!preg_match($month_pattern, $requested_start_month) || !preg_match($month_pattern, $requested_end_month)) {
        $filter_error = 'Select a valid month range.';
    } else {
        $selected_start = new DateTimeImmutable($requested_start_month . '-01');
        $selected_end = new DateTimeImmutable($requested_end_month . '-01');
        $month_span = (((int)$selected_end->format('Y') - (int)$selected_start->format('Y')) * 12)
            + ((int)$selected_end->format('n') - (int)$selected_start->format('n')) + 1;

        if ($selected_start > $selected_end) {
            $filter_error = 'The start month cannot be after the end month.';
        } elseif ($month_span > 60) {
            $filter_error = 'Select a range of five years or less.';
        } else {
            $period_start_date = $selected_start;
            $period_end_date = $selected_end->modify('first day of next month');
            $filter_applied = true;
        }
    }
}

$period_start = $period_start_date->format('Y-m-d');
$period_end = $period_end_date->format('Y-m-d');
$period_last_month = $period_end_date->modify('-1 month');
$period_label = $period_start_date->format('M Y') . ' - ' . $period_last_month->format('M Y');

// 1. Period revenue, orders, cost, and profit.
$summary_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(o.total_amount), 0) AS total_revenue, COUNT(*) AS total_orders
    FROM orders o
    WHERE o.status NOT IN ('cancelled','refunded')
      AND o.created_at >= ? AND o.created_at < ?
");
$summary_stmt->execute([$period_start, $period_end]);
$summary = $summary_stmt->fetch();
$total_revenue = (float)$summary['total_revenue'];
$total_orders = (int)$summary['total_orders'];

$cost_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity * COALESCE(p.cost_price, 0)), 0)
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN product_variations pv ON pv.id = oi.variation_id
    LEFT JOIN products p ON p.id = pv.product_id
    WHERE o.status NOT IN ('cancelled','refunded')
      AND o.created_at >= ? AND o.created_at < ?
");
$cost_stmt->execute([$period_start, $period_end]);
$total_cost = (float)$cost_stmt->fetchColumn();
$net_profit = $total_revenue - $total_cost;

// 2. Monthly revenue and profit for the selected period.
$monthly_stmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(o.created_at, '%Y-%m') AS month,
        SUM(o.total_amount) AS total,
        SUM(o.total_amount) - SUM(COALESCE(pc.total_cost, 0)) AS profit
    FROM orders o
    LEFT JOIN (
        SELECT oi.order_id, SUM(oi.quantity * COALESCE(p.cost_price, 0)) AS total_cost
        FROM order_items oi
        LEFT JOIN product_variations pv ON oi.variation_id = pv.id
        LEFT JOIN products p ON pv.product_id = p.id
        GROUP BY oi.order_id
    ) pc ON o.id = pc.order_id
    WHERE o.status NOT IN ('cancelled','refunded')
      AND o.created_at >= ? AND o.created_at < ?
    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_stmt->execute([$period_start, $period_end]);
$monthly_results = $monthly_stmt->fetchAll();
$monthly_lookup = [];
foreach ($monthly_results as $month_result) {
    $monthly_lookup[$month_result['month']] = $month_result;
}

$month_labels = [];
$month_revenue = [];
$month_profit = [];
for ($month_cursor = $period_start_date; $month_cursor < $period_end_date; $month_cursor = $month_cursor->modify('+1 month')) {
    $month_key = $month_cursor->format('Y-m');
    $month_labels[] = $month_cursor->format('M Y');
    $month_revenue[] = isset($monthly_lookup[$month_key]) ? (float)$monthly_lookup[$month_key]['total'] : 0;
    $month_profit[] = isset($monthly_lookup[$month_key]) ? (float)$monthly_lookup[$month_key]['profit'] : 0;
}

// 3. Latest ten active sales days within the selected period.
$daily_stmt = $pdo->prepare("
    SELECT
        DATE(o.created_at) AS date,
        SUM(o.total_amount) AS total,
        COUNT(DISTINCT o.id) AS volume,
        SUM(o.total_amount) - SUM(COALESCE(pc.total_cost, 0)) AS profit
    FROM orders o
    LEFT JOIN (
        SELECT oi.order_id, SUM(oi.quantity * COALESCE(p.cost_price, 0)) AS total_cost
        FROM order_items oi
        LEFT JOIN product_variations pv ON oi.variation_id = pv.id
        LEFT JOIN products p ON pv.product_id = p.id
        GROUP BY oi.order_id
    ) pc ON o.id = pc.order_id
    WHERE o.status NOT IN ('cancelled','refunded')
      AND o.created_at >= ? AND o.created_at < ?
    GROUP BY DATE(o.created_at)
    ORDER BY date DESC
    LIMIT 10
");
$daily_stmt->execute([$period_start, $period_end]);
$daily_raw = $daily_stmt->fetchAll();

// 4. Category revenue within the selected period.
$category_stmt = $pdo->prepare("
    SELECT c.name, SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status NOT IN ('cancelled','refunded')
      AND o.created_at >= ? AND o.created_at < ?
    GROUP BY c.id, c.name
    ORDER BY revenue DESC
");
$category_stmt->execute([$period_start, $period_end]);
$cat_raw = $category_stmt->fetchAll();

// Compare the selected period with the immediately preceding period of the
// same length. Existing KPI variable names are retained for the template.
$period_interval = $period_start_date->diff($period_end_date);
$previous_end_date = $period_start_date;
$previous_start_date = $previous_end_date->sub($period_interval);
$previous_start = $previous_start_date->format('Y-m-d');
$previous_end = $previous_end_date->format('Y-m-d');

$previous_summary_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(o.total_amount), 0)
    FROM orders o
    WHERE o.status NOT IN ('cancelled','refunded')
      AND o.created_at >= ? AND o.created_at < ?
");
$previous_summary_stmt->execute([$previous_start, $previous_end]);
$previous_revenue = (float)$previous_summary_stmt->fetchColumn();

$previous_cost_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity * COALESCE(p.cost_price, 0)), 0)
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN product_variations pv ON pv.id = oi.variation_id
    LEFT JOIN products p ON p.id = pv.product_id
    WHERE o.status NOT IN ('cancelled','refunded')
      AND o.created_at >= ? AND o.created_at < ?
");
$previous_cost_stmt->execute([$previous_start, $previous_end]);
$previous_cost = (float)$previous_cost_stmt->fetchColumn();

$this_month_rev = $total_revenue;
$this_month_profit = $net_profit;
$last_month_rev = $previous_revenue;
$last_month_profit = $previous_revenue - $previous_cost;
$mom_growth = $previous_revenue > 0
    ? (($total_revenue - $previous_revenue) / $previous_revenue) * 100
    : ($total_revenue > 0 ? 100 : 0);

// Derived KPIs
$profit_margin = $total_revenue > 0 ? ($net_profit / $total_revenue) * 100 : 0;
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

$include_path = '../includes/';
include $include_path . 'header.php';
?>
<link rel="stylesheet" href="/fashion_store/assets/css/Business Analytics.css">

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>

    <div class="dashboard-main fade-in-up">

        <!-- ═══════════════════════════════════════════ -->
        <!-- Page Header                                 -->
        <!-- ═══════════════════════════════════════════ -->
        <header class="rev-page-header">
            <div>
                <div class="rev-page-eyebrow">Financial Overview</div>
                <h1 class="rev-page-title">Business Analytics</h1>
                <p class="rev-page-subtitle">Profit = Revenue - Cost Price</p>
            </div>
        </header>

        <section class="rev-filter-card" aria-labelledby="report-period-title">
            <div class="rev-filter-heading">
                <div>
                    <h2 id="report-period-title">Report Period</h2>
                    <p><?php echo $filter_applied ? 'Custom month range' : 'Current year overview'; ?>: <strong><?php echo htmlspecialchars($period_label); ?></strong></p>
                </div>
                <?php if ($filter_applied): ?>
                    <span class="badge badge-info">Filtered</span>
                <?php else: ?>
                    <span class="badge">Full Year</span>
                <?php endif; ?>
            </div>

            <?php if ($filter_error): ?>
                <div class="rev-filter-error"><?php echo htmlspecialchars($filter_error); ?> Showing the current year instead.</div>
            <?php endif; ?>

            <form method="GET" class="rev-filter-form">
                <div class="rev-filter-field">
                    <label for="start-month">Start month</label>
                    <input type="month" id="start-month" name="start_month" value="<?php echo htmlspecialchars($requested_start_month); ?>" required>
                </div>
                <div class="rev-filter-field">
                    <label for="end-month">End month</label>
                    <input type="month" id="end-month" name="end_month" value="<?php echo htmlspecialchars($requested_end_month); ?>" required>
                </div>
                <button type="submit" class="button-primary rev-filter-submit">Apply range</button>
                <a href="Business Analytics.php" class="button-secondary rev-filter-reset">Full year</a>
            </form>
        </section>

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
                    <div class="rev-kpi-hint"><?php echo htmlspecialchars($period_label); ?></div>
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
            <p class="rev-section-desc">Monthly comparison for <?php echo htmlspecialchars($period_label); ?>. Months without payments remain visible at zero.</p>
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
                        <span class="badge">Latest 10 Active Days</span>
                    </div>
                    <p class="rev-section-desc" style="padding: 0 24px;">Most recent sales days within the selected report period.</p>
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
                                <tr><td colspan="4" style="text-align:center; color: var(--colors-muted); padding: 32px;">No orders in the selected period</td></tr>
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
    const startMonthInput = document.getElementById('start-month');
    const endMonthInput = document.getElementById('end-month');
    const syncMonthRange = function() {
        if (!startMonthInput || !endMonthInput) return;
        endMonthInput.min = startMonthInput.value;
        if (startMonthInput.value && endMonthInput.value && endMonthInput.value < startMonthInput.value) {
            endMonthInput.value = startMonthInput.value;
        }
    };
    if (startMonthInput) {
        startMonthInput.addEventListener('change', syncMonthRange);
        syncMonthRange();
    }

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
                    borderColor: '#181715',
                    backgroundColor: 'rgba(24,23,21,0.06)',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#181715',
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
                    title: { display: true, text: 'Amount (RM)', font: { weight: 'bold' } },
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.04)', drawBorder: true },
                    border: { display: true, color: '#000000', width: 1 },
                    ticks: { callback: v => 'RM ' + v.toLocaleString(), padding: 10, font: { size: 11 } }
                },
                x: {
                    title: { display: true, text: 'Date', font: { weight: 'bold' } },
                    grid: { display: false, drawBorder: true },
                    border: { display: true, color: '#000000', width: 1 },
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
