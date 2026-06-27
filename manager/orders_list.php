<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $valid_statuses = ['pending', 'processing', 'shipped', 'completed', 'refund_requested', 'cancelled', 'refunded'];

    if (in_array($status, $valid_statuses, true)) {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT status, stock_restored FROM orders WHERE id = ? FOR UPDATE");
        $stmt->execute([$order_id]);
        $order_state = $stmt->fetch();
        $current_status = $order_state['status'] ?? null;
        $stock_restored = (int)($order_state['stock_restored'] ?? 0);

        $is_allowed_change = $current_status;
        if (in_array($current_status, ['completed', 'cancelled', 'refunded'], true)) {
            $is_allowed_change = false;
        }
        if ($current_status === 'refund_requested' && $status !== 'refunded') {
            $is_allowed_change = false;
        }
        if ($status === 'cancelled' && !in_array($current_status, ['pending', 'processing'], true)) {
            $is_allowed_change = false;
        }
        if ($status === 'refunded' && $current_status !== 'refund_requested') {
            $is_allowed_change = false;
        }

        if ($is_allowed_change) {
            $should_restore_stock = in_array($status, ['cancelled', 'refunded'], true) && $stock_restored === 0;

            if ($should_restore_stock) {
                $stmt = $pdo->prepare("
                    UPDATE product_variations pv
                    JOIN order_items oi ON oi.variation_id = pv.id
                    SET pv.stock_quantity = pv.stock_quantity + oi.quantity
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$order_id]);
            }

            $stmt = $pdo->prepare("
                UPDATE orders
                SET status = ?,
                    completed_at = CASE WHEN ? = 'completed' AND completed_at IS NULL THEN NOW() ELSE completed_at END,
                    stock_restored = CASE WHEN ? THEN 1 ELSE stock_restored END
                WHERE id = ?
            ");
            $stmt->execute([$status, $status, $should_restore_stock ? 1 : 0, $order_id]);
        }

        $pdo->commit();
    }
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
.status-refund_requested { background: #fff7ed; color: #c2410c; }
.status-cancelled { background: #fef2f2; color: #dc2626; }
.status-refunded { background: #fff7ed; color: #c2410c; }
.orders-compact-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}
.orders-compact-header h1 {
    margin: 0;
    font-size: 34px;
    letter-spacing: 0;
}
.orders-filter-panel {
    margin-bottom: 18px;
    padding: 14px 16px;
    border: 1px solid var(--colors-hairline-soft);
    border-radius: 8px;
    background: var(--colors-surface-soft);
}
.orders-filter-form {
    display: grid;
    grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) auto auto;
    gap: 10px;
    align-items: end;
}
.orders-filter-form .form-input {
    height: 38px;
    padding: 0 10px;
    background: var(--colors-surface);
}
.orders-filter-action {
    display: inline-flex;
    height: 38px;
    box-sizing: border-box;
    align-items: center;
    justify-content: center;
    padding: 0 14px;
    border-radius: 6px;
    font-size: 11px;
    white-space: nowrap;
}
.orders-table-shell {
    max-height: min(540px, calc(100vh - 285px));
    overflow: auto;
    border: 1px solid var(--colors-hairline-soft);
    border-radius: 8px;
    background: var(--colors-surface);
}
.orders-table-shell .data-table {
    min-width: 980px;
    margin: 0;
}
.orders-table-shell .data-table th {
    position: sticky;
    top: 0;
    z-index: 3;
    padding: 11px 14px;
    background: var(--colors-surface-soft);
}
.orders-table-shell .data-table td {
    padding: 11px 14px;
}
.orders-count {
    color: var(--colors-muted);
    font-size: 12px;
}
@media (max-width: 900px) {
    .orders-filter-form { grid-template-columns: 1fr 1fr; }
    .orders-filter-action { width: 100%; }
}
@media (max-width: 600px) {
    .orders-filter-form { grid-template-columns: 1fr; }
    .orders-table-shell { max-height: 520px; }
}
</style>

<?php require_once '../includes/sidebar.php'; ?>
<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main fade-in-up">
        <header class="orders-compact-header">
            <div>
                <div style="font-size: 11px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0; margin-bottom: 4px; font-weight: 700;">HypeThread Logistics</div>
                <h1>Order Flow</h1>
            </div>
            <div class="orders-count"><?php echo count($orders); ?> orders shown</div>
        </header>

        <!-- Filters -->
        <div class="orders-filter-panel">
            <form method="GET" class="orders-filter-form">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">Shipment Status</label>
                    <select name="status" class="form-input">
                        <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $filter_status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $filter_status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="refund_requested" <?php echo $filter_status == 'refund_requested' ? 'selected' : ''; ?>>Refund Requested</option>
                        <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="refunded" <?php echo $filter_status == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">Reporting Period</label>
                    <select name="date" class="form-input">
                        <option value="all" <?php echo $filter_date == 'all' ? 'selected' : ''; ?>>All Period</option>
                        <option value="today" <?php echo $filter_date == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $filter_date == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                    </select>
                </div>
                <button type="submit" class="button-primary orders-filter-action">Apply</button>
                <?php if ($filter_status != 'all' || $filter_date != 'all'): ?>
                    <a href="orders_list.php" class="button-secondary orders-filter-action" style="text-decoration: none;">Reset</a>
                <?php else: ?>
                    <span aria-hidden="true"></span>
                <?php endif; ?>
            </form>
        </div>

        <div class="orders-table-shell">
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
                    <?php if (!$orders): ?>
                        <tr><td colspan="7" style="padding: 32px; text-align: center; color: var(--colors-muted);">No orders match these filters.</td></tr>
                    <?php endif; ?>
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
                                    <?php if (in_array($o['status'], ['completed', 'cancelled', 'refunded'], true)): ?>
                                        <span style="font-size: 12px; color: var(--colors-muted);">Final</span>
                                    <?php else: ?>
                                        <form method="POST" style="display: flex; gap: 4px;">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            <select name="status" class="form-input" style="width: auto; padding: 4px 8px; font-size: 11px; height: 28px; background: var(--colors-surface-soft);">
                                                <?php if ($o['status'] == 'refund_requested'): ?>
                                                    <option value="refund_requested" selected>Refund Requested</option>
                                                    <option value="refunded">Refunded</option>
                                                <?php else: ?>
                                                    <option value="pending" <?php echo $o['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="processing" <?php echo $o['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                    <option value="shipped" <?php echo $o['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                    <option value="completed" <?php echo $o['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <?php if (in_array($o['status'], ['pending', 'processing'], true)): ?>
                                                        <option value="cancelled">Cancelled</option>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </select>
                                            <button type="submit" name="update_status" class="button-primary" style="padding: 0 10px; height: 28px; font-size: 10px; background: var(--colors-ink);">Update</button>
                                        </form>
                                    <?php endif; ?>
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
