<?php
session_start();
// Legacy redirect — reports.php is now customer_intelligence.php
header("Location: /fashion_store/owner/customer_intelligence.php");
exit();
    exit();
}

// 1. Average Order Value (AOV)
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;
$total_valid_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;
$aov = $total_valid_orders > 0 ? $total_revenue / $total_valid_orders : 0;

// 2. Repeat Customer %
$total_buyers = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;
$repeat_buyers = $pdo->query("SELECT COUNT(*) FROM (SELECT user_id FROM orders WHERE status != 'cancelled' GROUP BY user_id HAVING COUNT(*) > 1) as repeat_users")->fetchColumn() ?: 0;
$repeat_rate = $total_buyers > 0 ? ($repeat_buyers / $total_buyers) * 100 : 0;

// 3. Customer Segmentation Data
$segments_raw = $pdo->query("
    SELECT u.id, SUM(o.total_amount) as total_spent
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    WHERE o.status != 'cancelled'
    GROUP BY u.id 
")->fetchAll();

$high_value = 0; // > 1000
$medium_value = 0; // 200 - 1000
$low_value = 0; // < 200

foreach ($segments_raw as $s) {
    if ($s['total_spent'] >= 1000) $high_value++;
    elseif ($s['total_spent'] >= 200) $medium_value++;
    else $low_value++;
}

// 4. High Value Customers (LTV Table)
$high_value_customers = $pdo->query("
    SELECT u.username, u.email, COUNT(o.id) as order_count, SUM(o.total_amount) as total_spent, MAX(o.created_at) as last_order
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    WHERE o.status != 'cancelled'
    GROUP BY u.id 
    ORDER BY total_spent DESC 
    LIMIT 20
")->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php require_once '../includes/sidebar.php'; renderSidebar('owner'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Audience Analytics</div>
                <h1 style="margin: 0; font-size: 40px;">Customer Insights</h1>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 14px; color: var(--colors-muted);">Report Generated</div>
                <div style="font-weight: 500; color: var(--colors-ink);"><?php echo date('M d, Y'); ?></div>
            </div>
        </header>

        <div class="stats-row" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card">
                <div class="stat-label">Average Order Value (AOV)</div>
                <div class="stat-value" style="color: var(--colors-ink);">RM <?php echo number_format($aov, 2); ?></div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Per transaction yield</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Repeat Customer Rate</div>
                <div class="stat-value" style="color: var(--colors-primary);"><?php echo number_format($repeat_rate, 1); ?>%</div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);"><?php echo $repeat_buyers; ?> out of <?php echo $total_buyers; ?> buyers returned</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Top Tier Cohort LTV</div>
                <div class="stat-value">
                    <?php 
                    $top_tier_sum = 0;
                    foreach(array_slice($high_value_customers, 0, 5) as $hvc) { $top_tier_sum += $hvc['total_spent']; }
                    echo "RM " . number_format($top_tier_sum, 2);
                    ?>
                </div>
                <div style="margin-top: 12px; font-size: 12px; color: var(--colors-muted);">Combined value of top 5 buyers</div>
            </div>
        </div>

        <div class="dashboard-split" style="grid-template-columns: 1fr 350px; gap: 32px; align-items: start; margin-top: 40px;">
            <div class="surface-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 24px; border-bottom: 1px solid var(--colors-hairline-soft); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; font-weight: 600; margin: 0; font-family: var(--typography-body-font);">Lifetime Value (LTV) Registry</h3>
                    <span style="font-size: 12px; color: var(--colors-muted);">Top 20 patrons</span>
                </div>
                <table class="data-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>Patron</th>
                            <th>Engagement</th>
                            <th>Last Purchase</th>
                            <th style="text-align: right;">Lifetime Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($high_value_customers as $hvc): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--colors-ink);"><?php echo htmlspecialchars($hvc['username']); ?></div>
                                    <div style="font-size: 12px; color: var(--colors-muted);"><?php echo htmlspecialchars($hvc['email']); ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-info" style="font-family: var(--typography-code-font);"><?php echo $hvc['order_count']; ?> Orders</span>
                                </td>
                                <td style="font-size: 13px; color: var(--colors-muted);">
                                    <?php echo date('M d, Y', strtotime($hvc['last_order'])); ?>
                                </td>
                                <td style="text-align: right; font-family: var(--typography-code-font); color: var(--colors-primary); font-weight: 600; font-size: 15px;">
                                    RM <?php echo number_format($hvc['total_spent'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($high_value_customers)): ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--colors-muted); padding: 32px;">No sales data available to calculate LTV.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="surface-card" style="padding: 24px;">
                <h3 style="font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px;">Customer Segmentation</h3>
                <div style="height: 250px; width: 100%; display: flex; justify-content: center; position: relative;">
                    <canvas id="segmentationChart"></canvas>
                </div>
                <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--colors-hairline-soft); display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: var(--colors-muted);">High Value (> RM 1000)</span>
                        <span style="font-weight: 600;"><?php echo $high_value; ?> users</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: var(--colors-muted);">Medium Value (RM 200 - 1000)</span>
                        <span style="font-weight: 600;"><?php echo $medium_value; ?> users</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: var(--colors-muted);">Low Value (< RM 200)</span>
                        <span style="font-weight: 600;"><?php echo $low_value; ?> users</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('segmentationChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['High Value (>1000)', 'Medium Value (200-1000)', 'Low Value (<200)'],
            datasets: [{
                data: [<?php echo $high_value; ?>, <?php echo $medium_value; ?>, <?php echo $low_value; ?>],
                backgroundColor: ['#1a1f36', '#ff6b6b', '#e5e7eb'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            cutout: '75%'
        }
    });
});
</script>

<?php include $include_path . 'footer.php'; ?>
