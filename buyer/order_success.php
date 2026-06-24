<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$order_id = intval($_GET['id'] ?? 0);
$user_id  = $_SESSION['user_id'];
$is_detail_view = ($_GET['view'] ?? '') === 'details';

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: dashboard.php');
    exit();
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.quantity, oi.price, p.name, pv.size, pv.color,
           (SELECT id FROM product_images WHERE product_id = p.id LIMIT 1) as image_id
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

include '../includes/header.php';
?>

<style>
.success-wrapper {
    max-width: 680px;
    margin: 60px auto 100px;
    padding: 0 24px;
}

.success-hero {
    text-align: center;
    padding: 48px 32px 36px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #bbf7d0;
    border-radius: 16px;
    margin-bottom: 28px;
}

.success-check {
    width: 72px;
    height: 72px;
    background: #22c55e;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin: 0 auto 20px;
    animation: pop-in 0.4s cubic-bezier(0.17,0.89,0.32,1.27);
}

@keyframes pop-in {
    from { transform: scale(0); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}

.success-hero h1 {
    font-family: var(--typography-display-font);
    font-size: 30px;
    color: #15803d;
    margin-bottom: 8px;
}

.success-hero p {
    font-size: 15px;
    color: #166534;
}

.order-card {
    background: var(--colors-surface, #fff);
    border: 1px solid var(--colors-border, #e8e4dc);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
}

.order-card-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--colors-muted, #888);
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--colors-border, #e8e4dc);
}

.order-meta-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.order-meta-item .label {
    font-size: 11px;
    color: var(--colors-muted, #888);
    margin-bottom: 3px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 600;
}

.order-meta-item .value {
    font-size: 14px;
    font-weight: 600;
    color: var(--colors-ink, #1a1a1a);
}

.status-badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: capitalize;
    background: #fef9c3;
    color: #854d0e;
}

.success-item-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 0;
    border-bottom: 1px solid var(--colors-border, #e8e4dc);
}

.success-item-row:last-child { border-bottom: none; }

.success-item-img {
    width: 52px;
    height: 64px;
    object-fit: cover;
    border-radius: 6px;
    background: #f0ede6;
    flex-shrink: 0;
}

.success-item-placeholder {
    width: 52px;
    height: 64px;
    border-radius: 6px;
    background: linear-gradient(135deg, #f0ede6, #e0dbd2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.success-actions {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}

.btn-continue {
    flex: 1;
    text-align: center;
    padding: 14px;
    background: var(--colors-ink, #1a1a1a);
    color: #fff;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s;
}

.btn-continue:hover { background: var(--colors-primary, #d4a574); }

.btn-secondary-action {
    flex: 1;
    text-align: center;
    padding: 14px;
    border: 1px solid var(--colors-border, #e8e4dc);
    color: var(--colors-ink, #1a1a1a);
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: border-color 0.2s;
}

.btn-secondary-action:hover { border-color: var(--colors-ink, #1a1a1a); }
</style>

<div class="success-wrapper">
    <!-- Hero -->
    <?php if (!$is_detail_view): ?>
    <div class="success-hero">
        <div class="success-check">✓</div>
        <h1>Order Confirmed!</h1>
        <p>Thank you for your purchase. Your order <strong>#<?php echo $order_id; ?></strong> has been placed.</p>
    </div>
    <?php endif; ?>

    <!-- Order Meta -->
    <div class="order-card">
        <div class="order-card-label">Order Details</div>
        <div class="order-meta-grid">
            <div class="order-meta-item">
                <div class="label">Order ID</div>
                <div class="value">#<?php echo $order['id']; ?></div>
            </div>
            <div class="order-meta-item">
                <div class="label">Status</div>
                <div class="value"><span class="status-badge"><?php echo $order['status']; ?></span></div>
            </div>
            <div class="order-meta-item">
                <div class="label">Total Paid</div>
                <div class="value" style="color:var(--colors-primary,#d4a574);">RM <?php echo number_format($order['total_amount'], 2); ?></div>
            </div>
            <div class="order-meta-item">
                <div class="label">Date</div>
                <div class="value"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></div>
            </div>
        </div>
        <?php if (!empty($order['address'])): ?>
            <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--colors-border,#e8e4dc);">
                <div class="order-meta-item">
                    <div class="label">Shipping To</div>
                    <div class="value" style="font-weight:400; line-height:1.6;"><?php echo htmlspecialchars($order['address']); ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Items -->
    <div class="order-card">
        <div class="order-card-label">Items Ordered (<?php echo count($items); ?>)</div>
        <?php foreach ($items as $item): ?>
            <div class="success-item-row">
                <?php if (!empty($item['image_id'])): ?>
                    <img src="/fashion_store/get_image.php?id=<?php echo $item['image_id']; ?>"
                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                         class="success-item-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="success-item-placeholder" style="display:none;">👗</div>
                <?php else: ?>
                    <div class="success-item-placeholder">👗</div>
                <?php endif; ?>
                <div style="flex:1;">
                    <div style="font-size:14px; font-weight:600; color:var(--colors-ink); margin-bottom:3px;"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div style="font-size:12px; color:var(--colors-muted,#888);">
                        <?php echo htmlspecialchars($item['size']); ?> &bull; <?php echo htmlspecialchars($item['color']); ?> &bull; Qty: <?php echo $item['quantity']; ?>
                    </div>
                </div>
                <div style="font-size:13px; font-weight:600; color:var(--colors-ink); flex-shrink:0;">
                    RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Actions -->
    <div class="success-actions">
        <a href="dashboard.php" class="btn-secondary-action">View My Orders</a>
        <a href="../products.php" class="btn-continue">Continue Shopping</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
