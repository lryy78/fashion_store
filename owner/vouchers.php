<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("Location: ../login.php");
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_campaign'])) {
    $code = strtoupper($_POST['code']);
    $type = $_POST['discount_type'];
    $value = $_POST['discount_value'];
    $min_spend = $_POST['min_spend'] ?: 0;
    $expiry = $_POST['expiry_date'];
    $usage_limit = $_POST['usage_limit'] ?: null;
    $is_one_time = isset($_POST['is_one_time']) ? 1 : 0;
    $target_type = $_POST['target_type'];
    $target_user_id = ($target_type == 'specific') ? $_POST['target_user_id'] : null;
    $target_group = ($target_type == 'group') ? $_POST['target_group'] : null;

    $stmt = $pdo->prepare("INSERT INTO vouchers (code, discount_type, discount_value, min_spend, expiry_date, usage_limit, is_one_time, target_type, target_user_id, target_group) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$code, $type, $value, $min_spend, $expiry, $usage_limit, $is_one_time, $target_type, $target_user_id, $target_group]);
    $success_msg = "Campaign '$code' deployed successfully!";
}

// Fetch Buyers for Dropdown
$buyers = $pdo->query("SELECT id, username FROM users WHERE role = 'buyer' ORDER BY username ASC")->fetchAll();

// Calculate Group Sizes for Usage Rate
$group_sizes = [
    'all' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'")->fetchColumn(),
    'new' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'repeat' => (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE status != 'cancelled' GROUP BY user_id HAVING COUNT(*) >= 2")->rowCount(),
    'vip' => (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE status != 'cancelled' GROUP BY user_id HAVING SUM(total_amount) > 1000")->rowCount(),
    'inactive' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer' AND id NOT IN (SELECT user_id FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY))")->fetchColumn(),
    'reviewers' => (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM reviews")->fetchColumn(),
];

// Fetch Campaigns with Analytics
$vouchers = $pdo->query("
    SELECT 
        v.*,
        (SELECT COUNT(*) FROM voucher_redemptions vr WHERE vr.voucher_id = v.id) as redemption_count,
        (SELECT SUM(total_amount) FROM orders o WHERE o.voucher_id = v.id AND o.status != 'cancelled') as influenced_revenue
    FROM vouchers v
    ORDER BY created_at DESC
")->fetchAll();

// Chart Data (Top 5 by Revenue)
$chart_labels = [];
$chart_revenue = [];
$top_vouchers = $pdo->query("
    SELECT v.code, SUM(o.total_amount) as rev 
    FROM vouchers v 
    JOIN orders o ON v.id = o.voucher_id 
    WHERE o.status != 'cancelled' 
    GROUP BY v.id 
    ORDER BY rev DESC 
    LIMIT 5
")->fetchAll();

foreach ($top_vouchers as $tv) {
    $chart_labels[] = $tv['code'];
    $chart_revenue[] = (float)$tv['rev'];
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
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; font-weight: 600; font-family: var(--typography-body-font);">Campaign Hub</div>
                <h1 style="margin: 0; font-family: var(--typography-display-font); font-size: 48px; letter-spacing: -0.02em;">Vouchers & Rewards</h1>
            </div>
            <?php if (isset($success_msg)): ?>
                <div class="badge badge-success" style="padding: 12px 24px; font-size: 14px;"><?php echo $success_msg; ?></div>
            <?php endif; ?>
        </header>

        <div class="dashboard-split" style="grid-template-columns: 1fr 450px; gap: 32px; align-items: start;">
            <div style="display: flex; flex-direction: column; gap: 32px;">
                <!-- Performance Chart -->
                <div class="surface-card" style="padding: 24px;">
                    <h3 style="font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 24px;">Revenue Contribution by Campaign</h3>
                    <div style="height: 250px; width: 100%;">
                        <canvas id="campaignChart"></canvas>
                    </div>
                </div>

                <!-- Campaigns List -->
                <div class="table-container" style="margin: 0;">
                    <div style="padding: 24px; border-bottom: 1px solid var(--colors-hairline-soft);">
                        <h3 style="font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">Active Campaigns</h3>
                    </div>
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Targeting</th>
                                <th>Performance</th>
                                <th style="text-align: right;">ROI (Revenue)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vouchers as $v): 
                                $revenue = $v['influenced_revenue'] ?: 0;
                                $target_size = 1;
                                if ($v['target_type'] == 'all') $target_size = $group_sizes['all'];
                                elseif ($v['target_type'] == 'group') $target_size = $group_sizes[$v['target_group']] ?: 1;
                                $usage_rate = ($v['redemption_count'] / $target_size) * 100;
                            ?>
                                <tr>
                                    <td>
                                        <div style="font-family: var(--typography-code-font); font-weight: 700; color: var(--colors-primary); font-size: 16px;"><?php echo htmlspecialchars($v['code']); ?></div>
                                        <div style="font-size: 12px; color: var(--colors-muted); margin-top: 4px;">
                                            <?php echo $v['discount_type'] == 'fixed' ? 'RM ' : ''; ?><?php echo number_format($v['discount_value'], 0); ?><?php echo $v['discount_type'] == 'percentage' ? '%' : ''; ?> OFF
                                            <?php if($v['min_spend'] > 0): ?> • Min RM<?php echo number_format($v['min_spend'], 0); ?><?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 13px; font-weight: 600;">
                                            <?php if($v['target_type'] == 'all'): ?>
                                                🌍 All Customers
                                            <?php elseif($v['target_type'] == 'specific'): ?>
                                                👤 Specific User
                                            <?php elseif($v['target_type'] == 'group'): ?>
                                                👥 <?php echo $v['target_group'] == 'reviewers' ? 'Product Reviewers' : ucfirst($v['target_group']) . ' Segment'; ?>
                                            <?php else: ?>
                                                👥 Other Segment
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: 11px; color: var(--colors-muted); margin-top: 4px;">
                                            Target Size: <?php echo $target_size; ?> users
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-family: var(--typography-code-font); font-weight: 600;"><?php echo $v['redemption_count']; ?> uses</div>
                                        <div style="font-size: 11px; color: var(--colors-muted); margin-top: 4px;">Usage Rate: <?php echo number_format($usage_rate, 1); ?>%</div>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="font-family: var(--typography-code-font); font-weight: 700; color: var(--colors-success); font-size: 16px;">RM <?php echo number_format($revenue, 2); ?></div>
                                        <div style="font-size: 11px; color: var(--colors-muted); margin-top: 4px;">
                                            <?php if(strtotime($v['expiry_date']) < time()): ?>
                                                <span style="color: var(--colors-error);">Expired</span>
                                            <?php else: ?>
                                                Ends <?php echo date('M d', strtotime($v['expiry_date'])); ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Creation Form -->
            <div class="surface-card" style="padding: 32px; border: 1px solid var(--colors-primary);">
                <h3 style="font-size: 18px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 32px; color: var(--colors-primary);">Create New Campaign</h3>
                <form method="POST" style="display: flex; flex-direction: column; gap: 24px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Voucher Code</label>
                            <input type="text" name="code" placeholder="WELCOME10" class="form-input" style="text-transform: uppercase; font-family: var(--typography-code-font);" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Min. Spend (RM)</label>
                            <input type="number" name="min_spend" step="0.01" class="form-input" placeholder="0.00">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Discount Type</label>
                            <select name="discount_type" class="form-input">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed (RM)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Value</label>
                            <input type="number" name="discount_value" step="0.01" class="form-input" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-input" required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Usage Limit</label>
                            <input type="number" name="usage_limit" class="form-input" placeholder="Unlimited">
                        </div>
                    </div>

                    <div style="padding: 20px; background: var(--colors-surface-soft); border-radius: 8px; border: 1px solid var(--colors-hairline);">
                        <label class="form-label" style="margin-bottom: 16px; display: block;">Distribution Target</label>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px;">
                                <input type="radio" name="target_type" value="all" checked onclick="toggleTarget('all')"> 🌍 All Customers
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px;">
                                <input type="radio" name="target_type" value="specific" onclick="toggleTarget('specific')"> 👤 Specific User
                            </label>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px;">
                                <input type="radio" name="target_type" value="group" onclick="toggleTarget('group')"> 👥 Customer Segments
                            </label>
                        </div>

                        <div id="target_specific_div" style="display: none; margin-top: 16px;">
                            <select name="target_user_id" class="form-input">
                                <option value="">Select User...</option>
                                <?php foreach($buyers as $b): ?>
                                    <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['username']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="target_group_div" style="display: none; margin-top: 16px;">
                            <select name="target_group" class="form-input">
                                <option value="new">New Customers (30d)</option>
                                <option value="repeat">Repeat Customers (2+ orders)</option>
                                <option value="vip">VIP Customers (>RM1000)</option>
                                <option value="inactive">Inactive Customers (60d+)</option>
                                <option value="reviewers">Product Reviewers (Loyalists)</option>
                            </select>
                        </div>
                    </div>

                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; font-weight: 600;">
                        <input type="checkbox" name="is_one_time" checked> One-time use per customer
                    </label>

                    <button type="submit" name="create_campaign" class="button-primary" style="width: 100%; height: 50px; font-size: 16px;">Deploy Campaign</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleTarget(type) {
    document.getElementById('target_specific_div').style.display = (type === 'specific') ? 'block' : 'none';
    document.getElementById('target_group_div').style.display = (type === 'group') ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('campaignChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Influenced Revenue (RM)',
                data: <?php echo json_encode($chart_revenue); ?>,
                backgroundColor: 'rgba(204, 120, 92, 0.8)',
                borderColor: '#cc785c',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { callback: v => 'RM ' + v } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php include $include_path . 'footer.php'; ?>
