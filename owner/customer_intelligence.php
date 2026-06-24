<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

// 1. Core KPIs
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status NOT IN ('cancelled','refunded')")->fetchColumn() ?: 0;
$total_valid_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status NOT IN ('cancelled','refunded')")->fetchColumn() ?: 0;
$aov = $total_valid_orders > 0 ? $total_revenue / $total_valid_orders : 0;

$total_buyers = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE status NOT IN ('cancelled','refunded')")->fetchColumn() ?: 0;
$repeat_buyers = $pdo->query("SELECT COUNT(*) FROM (SELECT user_id FROM orders WHERE status NOT IN ('cancelled','refunded') GROUP BY user_id HAVING COUNT(*) > 1) as r")->fetchColumn() ?: 0;
$new_buyers = $total_buyers - $repeat_buyers;
$repeat_rate = $total_buyers > 0 ? ($repeat_buyers / $total_buyers) * 100 : 0;

// 2. Customer Segmentation
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

// 3. Top Customers LTV Table
$top_customers = $pdo->query("
    SELECT u.username, u.email, COUNT(o.id) as order_count, SUM(o.total_amount) as total_spent, MAX(o.created_at) as last_order
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    WHERE o.status NOT IN ('cancelled','refunded')
    GROUP BY u.id 
    ORDER BY total_spent DESC 
    LIMIT 20
")->fetchAll();

// 4. Insight: Top 20% vs 80% revenue
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
$top_20_pct = $total_revenue > 0 ? round(($top_20_revenue / $total_revenue) * 100) : 0;

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600; font-family: var(--typography-body-font);">Audience Analytics</div>
                <h1 style="margin: 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Customer Intelligence</h1>
            </div>
            <div style="font-size: 13px; color: var(--colors-muted);">Generated <?php echo date('M d, Y'); ?></div>
        </header>

        <!-- KPIs -->
        <div class="stats-row" style="grid-template-columns: repeat(4, 1fr);">
            <div class="stat-card">
                <div class="stat-label">Avg Order Value (AOV)</div>
                <div class="stat-value" style="color: var(--colors-ink);">RM <?php echo number_format($aov, 2); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Per transaction yield</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Repeat Customer Rate</div>
                <div class="stat-value" style="color: var(--colors-primary);"><?php echo number_format($repeat_rate, 1); ?>%</div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);"><?php echo $repeat_buyers; ?> of <?php echo $total_buyers; ?> returned</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">New Customers</div>
                <div class="stat-value"><?php echo number_format($new_buyers); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">First-time buyers</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">High-Value (VIP)</div>
                <div class="stat-value"><?php echo number_format($high_value); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Customers > RM 1,000 LTV</div>
            </div>
        </div>

        <!-- Insights Banner -->
        <?php if ($top_20_pct > 0): ?>
        <div style="margin-top: 24px; padding: 16px 24px; background: linear-gradient(90deg, #181715 0%, #252320 100%); border-radius: 12px; display: flex; align-items: center; gap: 16px;">
            <span style="font-size: 28px;">💡</span>
            <div>
                <div style="font-weight: 700; color: #fff; font-size: 15px; font-family: var(--typography-body-font);">Top <?php echo ceil(0.2 * 100); ?>% of customers generate <?php echo $top_20_pct; ?>% of total revenue</div>
                <div style="color: rgba(255,255,255,0.65); font-size: 13px; margin-top: 4px;">Consider launching a VIP loyalty tier targeting your high-value segment to increase retention.</div>
            </div>
        </div>
        <?php endif; ?>

        <div style="display: flex; flex-direction: column; gap: 32px; margin-top: 32px;">
            <!-- LTV Table (Full Width) -->
            <div class="surface-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid var(--colors-hairline-soft); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 16px; font-weight: 700; margin: 0;">Lifetime Value (LTV) Registry</h3>
                    <span style="font-size: 12px; color: var(--colors-muted);">Top 20 patrons</span>
                </div>
                <table class="data-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>Patron</th>
                            <th>Tier</th>
                            <th>Engagements</th>
                            <th>Last Purchase</th>
                            <th style="text-align: right;">Lifetime Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_customers as $idx => $hvc): 
                            if ($hvc['total_spent'] >= 1000) { $tier = ['label' => 'VIP', 'class' => 'badge-pill-dark']; }
                            elseif ($hvc['total_spent'] >= 200) { $tier = ['label' => 'Mid', 'class' => 'badge-info']; }
                            else { $tier = ['label' => 'Low', 'class' => 'badge-pending']; }
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--colors-ink);">
                                        <?php if ($idx < 3): ?><span style="color: var(--colors-primary);">🏆 </span><?php endif; ?>
                                        <?php echo htmlspecialchars($hvc['username']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--colors-muted);"><?php echo htmlspecialchars($hvc['email']); ?></div>
                                </td>
                                <td><span class="badge <?php echo $tier['class']; ?>"><?php echo $tier['label']; ?></span></td>
                                <td><span class="badge badge-info" style="font-family: var(--typography-code-font);"><?php echo $hvc['order_count']; ?> orders</span></td>
                                <td style="font-size: 13px; color: var(--colors-muted);"><?php echo date('M d, Y', strtotime($hvc['last_order'])); ?></td>
                                <td style="text-align: right; font-family: var(--typography-code-font); color: var(--colors-primary); font-weight: 600; font-size: 15px;">RM <?php echo number_format($hvc['total_spent'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Segmentation & Behavioral Row -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                <!-- Customer Segmentation Chart -->
                <div class="surface-card" style="padding: 32px; display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: center;">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px;">Customer Segmentation</h3>
                        <div style="height: 200px; width: 100%; display: flex; justify-content: center;">
                            <canvas id="segmentChart"></canvas>
                        </div>
                    </div>
                    <div style="padding-left: 32px; border-left: 1px solid var(--colors-hairline-soft); display: flex; flex-direction: column; gap: 16px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="display: flex; align-items: center; gap: 8px;"><span style="width: 10px; height: 10px; background: #181715; border-radius: 50%; display: inline-block;"></span> VIP (≥ RM 1,000)</span>
                            <strong><?php echo $high_value; ?> users</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="display: flex; align-items: center; gap: 8px;"><span style="width: 10px; height: 10px; background: #cc785c; border-radius: 50%; display: inline-block;"></span> Mid (RM 200–1,000)</span>
                            <strong><?php echo $medium_value; ?> users</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="display: flex; align-items: center; gap: 8px;"><span style="width: 10px; height: 10px; background: #efe9de; border-radius: 50%; display: inline-block;"></span> Low (< RM 200)</span>
                            <strong><?php echo $low_value; ?> users</strong>
                        </div>
                    </div>
                </div>

                <!-- Behavioral Signals -->
                <div class="surface-card" style="padding: 32px;">
                    <h3 style="font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 32px;">Behavioral Signals</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px;">
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                <span style="color: var(--colors-muted);">Repeat Purchase Rate</span>
                                <strong style="color: var(--colors-primary); font-size: 18px;"><?php echo number_format($repeat_rate, 1); ?>%</strong>
                            </div>
                            <div style="height: 6px; background: var(--colors-hairline-soft); border-radius: 3px; overflow: hidden;">
                                <div style="height: 100%; width: <?php echo min(100, $repeat_rate); ?>%; background: var(--colors-primary);"></div>
                            </div>
                            <div style="font-size: 12px; color: var(--colors-muted);">Retention health index</div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            <div style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                                <span style="color: var(--colors-muted);">New vs Returning</span>
                                <span style="font-weight: 700; font-family: var(--typography-code-font);"><?php echo $new_buyers; ?> / <?php echo $repeat_buyers; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                                <span style="color: var(--colors-muted);">Global AOV</span>
                                <span style="font-weight: 700; font-family: var(--typography-code-font);">RM <?php echo number_format($aov, 2); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; align-items: center;">
                                <span style="color: var(--colors-muted);">Churn Risk</span>
                                <span class="badge badge-success" style="font-size: 10px;">Low</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('segmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['VIP (≥RM1000)', 'Mid (RM200-1000)', 'Low (<RM200)'],
            datasets: [{
                data: [<?php echo $high_value; ?>, <?php echo $medium_value; ?>, <?php echo $low_value; ?>],
                backgroundColor: ['#181715', '#cc785c', '#efe9de'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '75%'
        }
    });
});
</script>

<?php include $include_path . 'footer.php'; ?>
