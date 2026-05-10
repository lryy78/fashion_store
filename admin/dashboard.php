<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$new_users_today = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_enquiries = $pdo->query("SELECT COUNT(*) FROM enquiries WHERE status = 'open'")->fetchColumn();

// Fetch recent cross-platform activity
$recent_activity = [];

// Get recent users
$recent_users = $pdo->query("SELECT id, username, created_at, 'user' as type FROM users ORDER BY created_at DESC LIMIT 3")->fetchAll();
// Get recent orders
$recent_orders = $pdo->query("SELECT id, total_amount, created_at, 'order' as type FROM orders ORDER BY created_at DESC LIMIT 3")->fetchAll();
// Get recent tickets
$recent_tickets = $pdo->query("SELECT id, subject, created_at, 'ticket' as type FROM enquiries ORDER BY created_at DESC LIMIT 3")->fetchAll();

$recent_activity = array_merge($recent_users, $recent_orders, $recent_tickets);
usort($recent_activity, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recent_activity = array_slice($recent_activity, 0, 5);

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php renderSidebar('admin'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">System Administration</div>
                <h1 style="margin: 0; font-size: 40px;">Control Center</h1>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 14px; color: var(--colors-muted);">Root Access</div>
                <div style="font-weight: 500; color: var(--colors-ink);"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
            </div>
        </header>

        <div class="stats-row" style="grid-template-columns: repeat(4, 1fr);">
            <div class="stat-card">
                <div class="stat-label">Total Registered</div>
                <div class="stat-value"><?php echo $total_users; ?> <span style="font-size: 16px; font-family: var(--typography-body-font); color: var(--colors-muted);">Users</span></div>
                <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <a href="users_list.php" class="button-secondary" style="padding: 4px 10px; font-size: 11px;">Manage Registry</a>
                    <span style="font-size: 12px; color: var(--colors-success);">+<?php echo $new_users_today; ?> today</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Global Orders</div>
                <div class="stat-value"><?php echo $total_orders; ?></div>
                <div style="margin-top: 12px;"><a href="orders_monitoring.php" class="button-secondary" style="padding: 4px 10px; font-size: 11px;">Monitor Feed</a></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Support Queue</div>
                <div class="stat-value" style="color: var(--colors-primary);"><?php echo $pending_enquiries; ?></div>
                <div style="margin-top: 12px;"><a href="help_center_manage.php" class="button-secondary" style="padding: 4px 10px; font-size: 11px;">Open Tickets</a></div>
            </div>
            <div class="stat-card" style="background: var(--colors-surface-soft); border: 1px dashed var(--colors-primary);">
                <div class="stat-label" style="color: var(--colors-primary);">System Status</div>
                <div class="stat-value" style="font-size: 24px; color: var(--colors-success);">Operational</div>
                <div style="font-size: 11px; color: var(--colors-muted); margin-top: 12px;">All core services synced</div>
            </div>
        </div>

        <div class="dashboard-split" style="grid-template-columns: 1fr 350px; gap: 32px; align-items: start; margin-top: 40px;">
            <div class="table-container" style="margin: 0;">
                <div style="padding: 20px; border-bottom: 1px solid var(--colors-hairline-soft); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">System Audit Log</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Details</th>
                            <th style="text-align: right;">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_activity as $act): ?>
                            <tr>
                                <td>
                                    <?php if ($act['type'] == 'user'): ?>
                                        <span class="badge badge-info" style="font-size: 10px;">NEW USER</span>
                                    <?php elseif ($act['type'] == 'order'): ?>
                                        <span class="badge badge-success" style="font-size: 10px;">NEW ORDER</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning" style="font-size: 10px;">SUPPORT TICKET</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($act['type'] == 'user'): ?>
                                        User <strong><?php echo htmlspecialchars($act['username']); ?></strong> registered.
                                    <?php elseif ($act['type'] == 'order'): ?>
                                        Order #ORD-<?php echo $act['id']; ?> placed (RM <?php echo number_format($act['total_amount'], 2); ?>).
                                    <?php else: ?>
                                        Ticket #<?php echo $act['id']; ?> opened: <?php echo htmlspecialchars($act['subject']); ?>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; color: var(--colors-muted); font-size: 12px;">
                                    <?php 
                                        $diff = time() - strtotime($act['created_at']);
                                        if ($diff < 0) echo 'Just now';
                                        elseif ($diff < 60) echo $diff . ' secs ago';
                                        elseif ($diff < 3600) echo floor($diff/60) . ' mins ago';
                                        elseif ($diff < 86400) echo floor($diff/3600) . ' hrs ago';
                                        else echo date('M d', strtotime($act['created_at']));
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_activity)): ?>
                            <tr><td colspan="3" style="text-align: center; color: var(--colors-muted); padding: 24px;">No recent activity</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Quick Links -->
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 8px 0;">Quick Access</h3>
                <a href="users_list.php" class="surface-card" style="display: flex; align-items: center; gap: 16px; text-decoration: none; padding: 16px; border: 1px solid var(--colors-hairline); transition: all 0.2s;">
                    <div style="font-size: 24px;">👥</div>
                    <div>
                        <div style="font-size: 13px; font-weight: 600; color: var(--colors-ink);">Manage Accounts</div>
                        <div style="font-size: 11px; color: var(--colors-muted);">Activate/Deactivate users</div>
                    </div>
                </a>
                <a href="orders_monitoring.php" class="surface-card" style="display: flex; align-items: center; gap: 16px; text-decoration: none; padding: 16px; border: 1px solid var(--colors-hairline); transition: all 0.2s;">
                    <div style="font-size: 24px;">👁</div>
                    <div>
                        <div style="font-size: 13px; font-weight: 600; color: var(--colors-ink);">Global Feed</div>
                        <div style="font-size: 11px; color: var(--colors-muted);">Search and filter orders</div>
                    </div>
                </a>
                <a href="help_center_manage.php" class="surface-card" style="display: flex; align-items: center; gap: 16px; text-decoration: none; padding: 16px; border: 1px solid var(--colors-hairline); transition: all 0.2s;">
                    <div style="font-size: 24px;">💬</div>
                    <div>
                        <div style="font-size: 13px; font-weight: 600; color: var(--colors-ink);">Support Desk</div>
                        <div style="font-size: 11px; color: var(--colors-muted);">Resolve pending enquiries</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
