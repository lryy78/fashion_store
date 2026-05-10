<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
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
                                <td style="font-family: var(--typography-code-font); font-size: 13px; color: var(--colors-muted);">#ORD-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td style="font-weight: 500;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td style="font-family: var(--typography-code-font); font-weight: 600;">RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td style="text-align: right;">
                                    <span class="badge badge-<?php echo ($order['status'] == 'completed' ? 'success' : ($order['status'] == 'cancelled' ? 'error' : 'pending')); ?>">
                                        <?php echo strtoupper($order['status']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($order['status'] == 'completed'): ?>
                                        <a href="write_review.php?order_id=<?php echo $order['id']; ?>" class="button-text-link" style="font-size: 12px; text-decoration: underline;">Review</a>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: var(--colors-muted);">N/A</span>
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
