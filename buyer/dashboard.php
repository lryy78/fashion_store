<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_action'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['order_action'];

    $allowed_actions = [
        'refund' => ['from' => 'completed', 'to' => 'refund_requested']
    ];

    if (isset($allowed_actions[$action])) {
        $rule = $allowed_actions[$action];
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT status, created_at, completed_at FROM orders WHERE id = ? AND user_id = ? FOR UPDATE");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch();

        $completed_at = $order ? ($order['completed_at'] ?? $order['created_at'] ?? null) : null;
        $is_refund_window_open = $completed_at && strtotime($completed_at) >= strtotime('-7 days');

        if ($order && $order['status'] === $rule['from'] && $is_refund_window_open) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$rule['to'], $order_id, $user_id]);
        }

        $pdo->commit();
    }

    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php renderSidebar('buyer'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Personal Collection</div>
                <h1 style="margin: 0; font-size: 40px;">Order History</h1>
            </div>
        </header>

        <div class="table-container">
            <?php if ($orders): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th style="text-align: right;">Fulfillment</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td style="font-family: var(--typography-code-font); font-size: 13px;">
                                    <a href="order_success.php?id=<?php echo $order['id']; ?>&view=details" style="color: var(--colors-ink); font-weight: 600; text-decoration: underline;">
                                        #ORD-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>
                                    </a>
                                </td>
                                <td style="font-weight: 500;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td style="font-family: var(--typography-code-font); font-weight: 600;">RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td style="text-align: right;">
                                    <span class="badge badge-<?php echo ($order['status'] == 'completed' ? 'success' : (in_array($order['status'], ['cancelled', 'refunded']) ? 'error' : 'pending')); ?>">
                                        <?php echo strtoupper($order['status']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($order['status'] == 'completed'): ?>
                                        <?php
                                            $completed_at = $order['completed_at'] ?? $order['created_at'];
                                            $refund_available = strtotime($completed_at) >= strtotime('-7 days');
                                        ?>
                                        <a href="write_review.php?order_id=<?php echo $order['id']; ?>" class="button-text-link" style="font-size: 12px; text-decoration: underline;">Review</a>
                                        <?php if ($refund_available): ?>
                                            <form method="POST" style="display: inline; margin-left: 12px;" onsubmit="return confirm('Request a refund for this completed order?');">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="order_action" value="refund" class="button-text-link" style="font-size: 12px; text-decoration: underline; border: 0; background: transparent; cursor: pointer; color: var(--colors-error);">Request Refund</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: var(--colors-muted); margin-left: 12px;">Refund window expired</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($order['status'] == 'refund_requested'): ?>
                                            <span style="font-size: 12px; color: var(--colors-muted);">Waiting for manager approval</span>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: var(--colors-muted);">N/A</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: var(--spacing-xxl); text-align: center;">
                    <div style="font-size: 48px; margin-bottom: var(--spacing-md); opacity: 0.2;">🛍️</div>
                    <p style="color: var(--colors-muted); font-size: 16px;">You haven't placed any orders yet.</p>
                    <a href="../products.php" class="button-primary" style="margin-top: var(--spacing-lg);">Browse Collection</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
