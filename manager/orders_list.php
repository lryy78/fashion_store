<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
}

// Handle Filters
$filter_status = $_GET['status'] ?? 'all';
$filter_date = $_GET['date'] ?? 'all';

$query = "SELECT o.*, u.username, 
          (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as item_count 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

if ($filter_status != 'all') {
    $query .= " AND o.status = " . $pdo->quote($filter_status);
}

if ($filter_date == 'today') {
    $query .= " AND DATE(o.created_at) = CURDATE()";
} elseif ($filter_date == 'week') {
    $query .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
}

$query .= " ORDER BY o.created_at DESC";
$orders = $pdo->query($query)->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<style>
.status-pill {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.status-pending { background: #fff5f2; color: #ff6b6b; }
.status-processing { background: #fdf4ff; color: #a21caf; }
.status-shipped { background: #eff6ff; color: #2563eb; }
.status-completed { background: #f0fdf4; color: #16a34a; }
</style>

<?php require_once '../includes/sidebar.php'; ?>
<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">HypeThread Logistics</div>
                <h1 style="margin: 0; font-size: 40px;">Order Flow</h1>
            </div>
        </header>

        <!-- Filters -->
        <div class="surface-card" style="padding: 24px; margin-bottom: 32px; border: 1px solid var(--colors-hairline);">
            <form method="GET" style="display: flex; gap: 24px; align-items: flex-end;">
                <div class="form-group" style="margin: 0; flex: 1;">
                    <label class="form-label" style="font-size: 11px;">Shipment Status</label>
                    <select name="status" class="form-input" style="padding: 10px;">
                        <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $filter_status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $filter_status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0; flex: 1;">
                    <label class="form-label" style="font-size: 11px;">Reporting Period</label>
                    <select name="date" class="form-input" style="padding: 10px;">
                        <option value="all" <?php echo $filter_date == 'all' ? 'selected' : ''; ?>>All Period</option>
                        <option value="today" <?php echo $filter_date == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $filter_date == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                    </select>
                </div>
                <button type="submit" class="button-secondary" style="padding: 12px 24px;">Filter</button>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Client</th>
                        <th>Items</th>
                        <th>Value</th>
                        <th>Current State</th>
                        <th style="text-align: right;">Operations</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td style="font-size: 13px; color: var(--colors-muted);"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
                            <td style="font-family: var(--typography-code-font); font-size: 13px; color: var(--colors-ink); font-weight: 600;">#ORD-<?php echo str_pad($o['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td style="font-weight: 500;"><?php echo htmlspecialchars($o['username']); ?></td>
                            <td style="font-size: 13px;"><?php echo $o['item_count'] ?: 0; ?> pieces</td>
                            <td style="font-family: var(--typography-code-font); font-weight: 600;">RM <?php echo number_format($o['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-pill status-<?php echo $o['status']; ?>">
                                    <?php echo $o['status']; ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                    <form method="POST" style="display: flex; gap: 4px;">
                                        <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                        <select name="status" class="form-input" style="width: auto; padding: 4px 8px; font-size: 11px; height: 28px; background: var(--colors-surface-soft);">
                                            <option value="pending" <?php echo $o['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $o['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $o['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="completed" <?php echo $o['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                        <button type="submit" name="update_status" class="button-primary" style="padding: 0 10px; height: 28px; font-size: 10px; background: var(--colors-ink);">Update</button>
                                    </form>
                                    <a href="order_details.php?id=<?php echo $o['id']; ?>" class="button-secondary" style="padding: 6px 12px; font-size: 11px;">Details</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
