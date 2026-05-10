<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'];
$order = $pdo->prepare("SELECT o.*, u.username, u.email, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$order->execute([$id]);
$details = $order->fetch();

$items = $pdo->prepare("SELECT oi.*, p.name, pv.size, pv.color 
                        FROM order_items oi 
                        JOIN product_variations pv ON oi.variation_id = pv.id 
                        JOIN products p ON pv.product_id = p.id 
                        WHERE oi.order_id = ?");
$items->execute([$id]);
$order_items = $items->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<?php require_once '../includes/sidebar.php'; ?>
<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main">
        <header style="margin-bottom: var(--spacing-xl);">
            <h1 style="margin: 0; font-size: 32px; font-family: var(--typography-display-font);">Order Manifest: #<?php echo $id; ?></h1>
        </header>

        <div class="dashboard-split">
            <div class="product-mockup-card-dark" style="margin-bottom: 0;">
                <h3 style="border-bottom: 1px solid var(--colors-surface-dark-elevated); margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-sm);">Order Items</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Piece</th>
                            <th>Variation</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td style="font-size: 13px; color: var(--colors-on-dark-soft);"><?php echo htmlspecialchars($item['size']); ?> / <?php echo htmlspecialchars($item['color']); ?></td>
                                <td style="font-family: var(--typography-code-font);">RM <?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td style="text-align: right; font-family: var(--typography-code-font);">RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align: right; margin-top: var(--spacing-xl); padding-top: var(--spacing-md); border-top: 1px solid var(--colors-surface-dark-elevated);">
                    <div style="color: var(--colors-on-dark-soft); font-size: 13px; margin-bottom: 4px;">Grand Total</div>
                    <div style="font-family: var(--typography-code-font); font-size: 24px; color: var(--colors-primary);">RM <?php echo number_format($details['total_amount'], 2); ?></div>
                </div>
            </div>

            <div class="product-mockup-card-dark" style="margin-bottom: 0; height: fit-content;">
                <h3 style="border-bottom: 1px solid var(--colors-surface-dark-elevated); margin-bottom: var(--spacing-md); padding-bottom: var(--spacing-sm);">Customer Profile</h3>
                <div style="margin-bottom: var(--spacing-lg);">
                    <div style="font-size: 12px; color: var(--colors-on-dark-soft); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Identity</div>
                    <div style="font-weight: 500;"><?php echo htmlspecialchars($details['username']); ?></div>
                </div>
                <div style="margin-bottom: var(--spacing-lg);">
                    <div style="font-size: 12px; color: var(--colors-on-dark-soft); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Contact</div>
                    <div style="font-size: 14px;"><?php echo htmlspecialchars($details['email']); ?></div>
                    <div style="font-size: 14px;"><?php echo htmlspecialchars($details['phone']); ?></div>
                </div>
                <div style="margin-bottom: var(--spacing-xl);">
                    <div style="font-size: 12px; color: var(--colors-on-dark-soft); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Shipping Destination</div>
                    <div style="font-size: 14px; line-height: 1.6; color: var(--colors-on-dark-soft);"><?php echo nl2br(htmlspecialchars($details['address'])); ?></div>
                </div>
                <a href="orders_list.php" class="button-secondary" style="width: 100%; text-align: center;">Back to Registry</a>
            </div>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
