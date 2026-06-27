<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

$range = $_GET['range'] ?? 30; // 7, 30, 90
$range = (int)$range;

// 1. Core KPIs (Filtered by Range)
$stats_current = $pdo->prepare("
    SELECT 
        SUM(total_amount) as total_rev,
        COUNT(id) as total_ord,
        COUNT(DISTINCT user_id) as total_buyers
    FROM orders
    WHERE status NOT IN ('cancelled','refunded')
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
");
$stats_current->execute([$range - 1]);
$curr = $stats_current->fetch();
$total_revenue = $curr['total_rev'] ?: 0;
$total_valid_orders = $curr['total_ord'] ?: 0;
$total_buyers = $curr['total_buyers'] ?: 0;

$aov = $total_valid_orders > 0 ? $total_revenue / $total_valid_orders : 0;

// To get repeat buyers within the period, it's those who have > 1 order in this period
$repeat_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM (
        SELECT user_id 
        FROM orders 
        WHERE status NOT IN ('cancelled','refunded') 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY user_id 
        HAVING COUNT(id) > 1
    ) as r
");
$repeat_stmt->execute([$range - 1]);
$repeat_buyers = $repeat_stmt->fetchColumn() ?: 0;
$new_buyers = max(0, $total_buyers - $repeat_buyers);
$repeat_rate = $total_buyers > 0 ? ($repeat_buyers / $total_buyers) * 100 : 0;

// Calculate Previous Period for Risk Analysis
$prev_start = ($range * 2) - 1;
$prev_end = $range;
$prev_buyers_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT user_id) 
    FROM orders 
    WHERE status NOT IN ('cancelled','refunded') 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    AND created_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)
");
$prev_buyers_stmt->execute([$prev_start, $prev_end]);
$prev_total_buyers = $prev_buyers_stmt->fetchColumn() ?: 0;

$prev_repeat_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM (
        SELECT user_id 
        FROM orders 
        WHERE status NOT IN ('cancelled','refunded') 
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        AND created_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY user_id 
        HAVING COUNT(id) > 1
    ) as r
");
$prev_repeat_stmt->execute([$prev_start, $prev_end]);
$prev_repeat_buyers = $prev_repeat_stmt->fetchColumn() ?: 0;
$prev_repeat_rate = $prev_total_buyers > 0 ? ($prev_repeat_buyers / $prev_total_buyers) * 100 : 0;

$risk_diff = $prev_repeat_rate - $repeat_rate;
if ($risk_diff <= 0) {
    $churn_risk_label = 'Low';
    $churn_style = 'background: rgba(34, 197, 94, 0.1); color: #16a34a;'; // green
} elseif ($risk_diff <= 5) {
    $churn_risk_label = 'Medium';
    $churn_style = 'background: rgba(234, 179, 8, 0.1); color: #ca8a04;'; // yellow
} else {
    $churn_risk_label = 'High';
    $churn_style = 'background: rgba(239, 68, 68, 0.1); color: #dc2626;'; // red
}

// 2. Customer Segmentation (All-Time for True LTV Analysis)
$segments_raw = $pdo->query("
    SELECT SUM(o.total_amount) as total_spent
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    WHERE o.status NOT IN ('cancelled','refunded')
    GROUP BY u.id 
")->fetchAll(PDO::FETCH_COLUMN);

$high_value = 0; $medium_value = 0; $low_value = 0;
foreach ($segments_raw as $spent) {
    if ($spent >= 1000) $high_value++;
    elseif ($spent >= 200) $medium_value++;
    else $low_value++;
}

// 3. Top Customers LTV Table (All-Time)
$top_customers = $pdo->query("
    SELECT u.username, u.email, COUNT(o.id) as order_count, SUM(o.total_amount) as total_spent, MAX(o.created_at) as last_order
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    WHERE o.status NOT IN ('cancelled','refunded')
    GROUP BY u.id 
    ORDER BY total_spent DESC 
    LIMIT 100
")->fetchAll();

// 4. Insight: Top 20% vs 80% revenue (All-Time)
$total_customers_with_orders = count($segments_raw);
$top_20_count = max(1, round($total_customers_with_orders * 0.2));
$stmt = $pdo->query("
    SELECT SUM(o.total_amount) as total_spent
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    WHERE o.status NOT IN ('cancelled','refunded')
    GROUP BY u.id 
    ORDER BY total_spent DESC
    LIMIT $top_20_count
");
$top_20_revenue = array_sum($stmt->fetchAll(PDO::FETCH_COLUMN)) ?: 0;
$all_time_revenue = array_sum($segments_raw) ?: 0;
$top_20_pct = $all_time_revenue > 0 ? round(($top_20_revenue / $all_time_revenue) * 100) : 0;

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
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card-premium {
        background: #fff;
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
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
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--colors-muted);
        margin-bottom: 8px;
    }
    .stat-card-premium .stat-value {
        font-size: 24px;
        font-weight: 700;
        font-family: var(--typography-display-font);
        color: var(--colors-ink);
        line-height: 1.1;
    }
    .stat-card-premium .stat-desc {
        font-size: 12px;
        color: var(--colors-muted);
        margin-top: 12px;
    }
    .chart-container {
        background: #fff;
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }
    .chart-header {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 16px;
        color: var(--colors-ink);
    }
    .badge-vip { background: #181715; color: #fff; }
    .badge-mid { background: rgba(204, 120, 92, 0.15); color: #cc785c; }
    .badge-low { background: #f3f4f6; color: #6b7280; }
</style>

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>
    <div class="dashboard-main fade-in-up">
        <header class="analytics-header">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600;">Audience Analytics</div>
                <h1 style="margin: 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Customer Intelligence</h1>
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

        <!-- Insights Banner -->
        <?php if ($top_20_pct > 0): ?>
        <div style="margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 16px; padding: 16px 24px; background: linear-gradient(90deg, rgba(204,120,92,0.1) 0%, rgba(204,120,92,0.02) 100%); border-left: 4px solid var(--colors-primary); border-radius: 8px; margin-bottom: 8px;">
                <div style="font-size: 24px; animation: pulse 2s infinite;">💡</div>
                <div style="flex: 1;">
                    <span style="font-weight: 700; font-size: 14px; color: var(--colors-ink); margin-right: 8px;">AI Insight: VIP Dependency</span>
                    <span style="font-size: 13px; color: var(--colors-body);">Top <?php echo ceil(0.2 * 100); ?>% of customers generate <strong><?php echo $top_20_pct; ?>%</strong> of total lifetime revenue. Consider launching a VIP loyalty tier.</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- KPIs -->
        <div class="stats-grid">
            <div class="stat-card-premium">
                <div>
                    <div class="stat-title">Avg Order Value</div>
                    <div class="stat-value" style="color: var(--colors-primary);">RM <?php echo number_format($aov, 2); ?></div>
                </div>
                <div class="stat-desc">Per transaction yield (selected period)</div>
            </div>
            
            <div class="stat-card-premium">
                <div>
                    <div class="stat-title">Repeat Rate</div>
                    <div class="stat-value"><?php echo number_format($repeat_rate, 1); ?>%</div>
                </div>
                <div class="stat-desc"><?php echo $repeat_buyers; ?> of <?php echo $total_buyers; ?> returning in period</div>
            </div>
            
            <div class="stat-card-premium">
                <div>
                    <div class="stat-title">New Buyers</div>
                    <div class="stat-value"><?php echo number_format($new_buyers); ?></div>
                </div>
                <div class="stat-desc">Acquired in selected period</div>
            </div>
            
            <div class="stat-card-premium">
                <div>
                    <div class="stat-title">True VIPs</div>
                    <div class="stat-value"><?php echo number_format($high_value); ?></div>
                </div>
                <div class="stat-desc">Customers > RM 1,000 all-time LTV</div>
            </div>
        </div>

         <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start;">
            <!-- LTV Table (Left Side) -->
            <div class="chart-container" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                <div style="padding: 16px 20px 8px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--colors-hairline-soft);">
                    <div>
                        <h3 class="chart-header" style="margin: 0; margin-bottom: 4px;">Lifetime Value (LTV) Registry</h3>
                        <span style="font-size: 11px; color: var(--colors-muted); font-weight: 600; text-transform: uppercase;">Top 100 Patrons</span>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button onclick="document.getElementById('tierFilter').value='all'; filterLTVTable();" style="background: none; border: none; font-size: 11px; color: var(--colors-primary); cursor: pointer; padding: 4px; font-weight: 600;">Reset</button>
                        <select id="tierFilter" onchange="filterLTVTable()" style="padding: 4px 8px; border-radius: 4px; border: 1px solid var(--colors-hairline); font-size: 12px; outline: none; cursor: pointer;">
                            <option value="all">All Tiers</option>
                            <option value="VIP">VIP (≥ RM 1k)</option>
                            <option value="Mid">Mid (RM 200 - 1k)</option>
                            <option value="Low">Low (< RM 200)</option>
                        </select>
                    </div>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    <table class="data-table" id="ltvTable" style="margin: 0; width: 100%;">
                        <thead style="position: sticky; top: 0; background: #fff; z-index: 10; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <tr>
                                <th style="padding: 10px 20px; font-size: 12px;">Patron</th>
                                <th style="font-size: 12px;">Tier</th>
                                <th style="font-size: 12px;">Engagements</th>
                                <th style="font-size: 12px;">Last Purchase</th>
                                <th style="text-align: right; padding: 10px 20px; font-size: 12px;">Lifetime Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_customers as $idx => $hvc): 
                                if ($hvc['total_spent'] >= 1000) { $tier = ['label' => 'VIP', 'class' => 'badge-vip']; }
                                elseif ($hvc['total_spent'] >= 200) { $tier = ['label' => 'Mid', 'class' => 'badge-mid']; }
                                else { $tier = ['label' => 'Low', 'class' => 'badge-low']; }
                            ?>
                                <tr class="ltv-row" data-tier="<?php echo $tier['label']; ?>">
                                    <td style="padding: 12px 20px;">
                                        <div style="font-weight: 600; font-size: 13px; color: var(--colors-ink);">
                                            <?php if ($idx < 3): ?><span style="color: var(--colors-primary);">🏆 </span><?php endif; ?>
                                            <?php echo htmlspecialchars($hvc['username']); ?>
                                        </div>
                                        <div style="font-size: 11px; color: var(--colors-muted);"><?php echo htmlspecialchars($hvc['email']); ?></div>
                                    </td>
                                    <td><span class="badge <?php echo $tier['class']; ?>" style="font-size: 10px; padding: 3px 6px; font-weight: 700;"><?php echo $tier['label']; ?></span></td>
                                    <td><span style="font-family: var(--typography-code-font); font-size: 12px; font-weight: 600; color: var(--colors-ink);"><?php echo $hvc['order_count']; ?> orders</span></td>
                                    <td style="font-size: 12px; color: var(--colors-muted);"><?php echo date('M d, y', strtotime($hvc['last_order'])); ?></td>
                                    <td style="text-align: right; padding: 12px 20px; font-family: var(--typography-code-font); color: var(--colors-primary); font-weight: 700; font-size: 13px;">RM <?php echo number_format($hvc['total_spent'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Segmentation & Behavioral Column -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Customer Segmentation Chart -->
                <div class="chart-container">
                    <h3 class="chart-header" style="margin-bottom: 8px;">Segmentation (All-Time)</h3>
                    <div style="height: 140px; width: 100%; display: flex; justify-content: center; margin-bottom: 16px;">
                        <canvas id="segmentChart"></canvas>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px;">
                            <span style="display: flex; align-items: center; gap: 8px;"><span style="width: 8px; height: 8px; background: #181715; border-radius: 50%; display: inline-block;"></span> VIP (≥ RM 1,000)</span>
                            <strong><?php echo $high_value; ?> users</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px;">
                            <span style="display: flex; align-items: center; gap: 8px;"><span style="width: 8px; height: 8px; background: #cc785c; border-radius: 50%; display: inline-block;"></span> Mid (RM 200–1,000)</span>
                            <strong><?php echo $medium_value; ?> users</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px;">
                            <span style="display: flex; align-items: center; gap: 8px;"><span style="width: 8px; height: 8px; background: #efe9de; border-radius: 50%; display: inline-block;"></span> Low (< RM 200)</span>
                            <strong><?php echo $low_value; ?> users</strong>
                        </div>
                    </div>
                </div>

                <!-- Behavioral Signals -->
                <div class="chart-container">
                 <h3 class="chart-header" style="margin-bottom: 12px;">Customer Behavior</h3>
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div style="display: flex; justify-content: space-between; font-size: 12px;">
                                <span style="color: var(--colors-muted); font-weight: 600;">Returning Customer %</span>   
                                <strong style="color: var(--colors-primary); font-size: 13px;"><?php echo number_format($repeat_rate, 1); ?>%</strong>
                            </div>
                            <div style="height: 6px; background: rgba(0,0,0,0.05); border-radius: 3px; overflow: hidden;">
                                <div style="height: 100%; width: <?php echo min(100, $repeat_rate); ?>%; background: var(--colors-primary);"></div>
                            </div>
                        </div>
                        <hr style="border: 0; border-top: 1px solid var(--colors-hairline-soft); margin: 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; align-items: center;">
                            <span style="color: var(--colors-muted); font-weight: 600;">Avg Spent Per Order</span>
                            <span style="font-weight: 700; font-family: var(--typography-code-font);">RM <?php echo number_format($aov, 2); ?></span>
                        </div>
                        <hr style="border: 0; border-top: 1px solid var(--colors-hairline-soft); margin: 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; align-items: center;">
                            <span style="color: var(--colors-muted); font-weight: 600;">Customer Loss Risk</span>
                            <span style="font-size: 10px; padding: 3px 6px; font-weight: 700; border-radius: 4px; <?php echo $churn_style; ?>"><?php echo $churn_risk_label; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function filterLTVTable() {
    const filter = document.getElementById('tierFilter').value;
    const rows = document.querySelectorAll('.ltv-row');
    rows.forEach(row => {
        if (filter === 'all' || row.dataset.tier === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = "'Inter', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
    
    const ctx = document.getElementById('segmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['VIP (≥RM1000)', 'Mid (RM200-1000)', 'Low (<RM200)'],
            datasets: [{
                data: [<?php echo $high_value; ?>, <?php echo $medium_value; ?>, <?php echo $low_value; ?>],
                backgroundColor: ['#181715', '#cc785c', '#efe9de'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#181715',
                    titleFont: { size: 13 },
                    bodyFont: { size: 14, weight: 'bold' },
                    padding: 12
                }
            },
            cutout: '75%'
        }
    });
});
</script>
<?php include $include_path . 'footer.php'; ?>


