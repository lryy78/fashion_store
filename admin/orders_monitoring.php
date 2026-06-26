<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$filter_status = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$query = "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];

if ($filter_status != 'all') {
    $query .= " AND o.status = ?";
    $params[] = $filter_status;
}

if (!empty($search_query)) {
    $query .= " AND (u.username LIKE ? OR o.id LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<?php require_once '../includes/sidebar.php'; ?>
<div class="dashboard-layout">
    <?php renderSidebar('admin'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">System Monitor</div>
                <h1 style="margin: 0; font-size: 40px;">Global Transaction Feed</h1>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px; align-items: flex-end;">
                <form method="GET" style="display: flex; gap: 8px;">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                    <input type="text" name="search" placeholder="Search customer or Order ID..." value="<?php echo htmlspecialchars($search_query); ?>" class="form-input" style="padding: 8px 16px; border-radius: 20px; font-size: 13px; width: 250px;">
                    <button type="submit" class="button-secondary" style="padding: 8px 16px; border-radius: 20px;">Search</button>
                </form>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; max-width: 400px; align-items: center;">
                    <a href="orders_monitoring.php?status=all&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_status == 'all' ? 'badge-pill-dark' : 'badge-info'; ?>" style="text-decoration: none;">All</a>
                    <a href="orders_monitoring.php?status=pending&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_status == 'pending' ? 'badge-pill-dark' : 'badge-pending'; ?>" style="text-decoration: none;">Pending</a>
                    <a href="orders_monitoring.php?status=processing&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_status == 'processing' ? 'badge-pill-dark' : 'badge-info'; ?>" style="text-decoration: none;">Processing</a>
                    <a href="orders_monitoring.php?status=shipped&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_status == 'shipped' ? 'badge-pill-dark' : 'badge-info'; ?>" style="text-decoration: none;">Shipped</a>
                    <a href="orders_monitoring.php?status=completed&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_status == 'completed' ? 'badge-pill-dark' : 'badge-success'; ?>" style="text-decoration: none;">Completed</a>
                    <a href="orders_monitoring.php?status=refund_requested&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_status == 'refund_requested' ? 'badge-pill-dark' : 'badge-pending'; ?>" style="text-decoration: none;">Refund Requested</a>
                    <a href="orders_monitoring.php?status=cancelled&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_status == 'cancelled' ? 'badge-pill-dark' : 'badge-error'; ?>" style="text-decoration: none;">Cancelled</a>
                    <a href="orders_monitoring.php?status=refunded&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_status == 'refunded' ? 'badge-pill-dark' : 'badge-error'; ?>" style="text-decoration: none;">Refunded</a>
                </div>
            </div>
        </header>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Timestamp</th>
                        <th>Transaction</th>
                        <th style="text-align: right;">System Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td style="font-family: var(--typography-code-font); font-size: 13px; color: var(--colors-muted);">#ORD-<?php echo str_pad($o['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($o['username']); ?></td>
                            <td style="font-size: 13px; color: var(--colors-muted);"><?php echo date('M d, Y H:i', strtotime($o['created_at'])); ?></td>
                            <td style="font-family: var(--typography-code-font); font-weight: 600;">RM <?php echo number_format($o['total_amount'], 2); ?></td>
                            <td style="text-align: right;">
                                <span class="badge badge-<?php echo ($o['status'] == 'completed' ? 'success' : (in_array($o['status'], ['cancelled', 'refunded']) ? 'error' : 'pending')); ?>">
                                    <?php echo strtoupper($o['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
