<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order_id'] ?? 0;

// Verify order belongs to user and is completed
$stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'completed'");
$stmt->execute([$order_id, $user_id]);
if (!$stmt->fetch()) {
    header("Location: dashboard.php");
    exit();
}

// Fetch products in this order
$stmt = $pdo->prepare("
    SELECT DISTINCT p.id as product_id, p.name, 
           (SELECT id FROM product_images WHERE product_id = p.id LIMIT 1) as image_id,
           (SELECT id FROM reviews WHERE order_id = ? AND product_id = p.id AND user_id = ? LIMIT 1) as has_reviewed
    FROM order_items oi
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id, $user_id, $order_id]);
$products = $stmt->fetchAll();

$success_voucher = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $product_id = $_POST['product_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        // Check if already reviewed to prevent duplicate vouchers
        $check = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ? AND product_id = ? AND user_id = ?");
        $check->execute([$order_id, $product_id, $user_id]);
        if (!$check->fetch()) {
            // Insert review
            $insert = $pdo->prepare("INSERT INTO reviews (user_id, product_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$user_id, $product_id, $order_id, $rating, $comment]);

            // Generate voucher with campaign
            $voucher_code = 'REV-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $expiry = date('Y-m-d', strtotime('+30 days'));
            $campaign = 'Product Review Rewards';
            $vstmt = $pdo->prepare("INSERT INTO vouchers (code, campaign, discount_type, discount_value, expiry_date, is_active, user_id, target_type, target_user_id, is_one_time) VALUES (?, ?, 'percentage', 10.00, ?, 1, ?, 'specific', ?, 1)");
            $vstmt->execute([$voucher_code, $campaign, $expiry, $user_id, $user_id]);
            
            $success_voucher = $voucher_code;
            
            // Refresh product list to show as reviewed
            $stmt->execute([$order_id, $user_id, $order_id]);
            $products = $stmt->fetchAll();
        }
    }
}

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<style>
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 4px;
}
.star-rating input {
    display: none;
}
.star-rating label {
    font-size: 24px;
    color: #e5e7eb;
    cursor: pointer;
    transition: color 0.2s;
}
.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #fbbf24;
}
</style>

<div class="dashboard-layout">
    <?php renderSidebar('buyer'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="margin-bottom: var(--spacing-xl);">
            <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Order #ORD-<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></div>
            <h1 style="margin: 0; font-size: 32px;">Review Products</h1>
        </header>

        <?php if ($success_voucher): ?>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 24px; border-radius: var(--rounded-md); margin-bottom: 32px; text-align: center;">
                <h3 style="color: #15803d; margin-bottom: 8px;">Thank You for Your Review!</h3>
                <p style="color: #166534; font-size: 14px; margin-bottom: 16px;">As a token of our appreciation, here is a 10% off voucher for your next purchase.</p>
                <div style="font-family: var(--typography-code-font); font-size: 24px; font-weight: 700; color: #166534; background: #dcfce7; display: inline-block; padding: 8px 16px; border-radius: 8px; letter-spacing: 2px;">
                    <?php echo $success_voucher; ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="display: grid; gap: 24px;">
            <?php foreach ($products as $product): ?>
                <div class="surface-card" style="display: flex; gap: 24px; padding: 24px; border-radius: var(--rounded-md);">
                    <img src="/fashion_store/get_image.php?id=<?php echo $product['image_id']; ?>" style="width: 100px; height: 120px; object-fit: cover; border-radius: 4px;">
                    
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 16px; font-size: 18px; font-weight: 500;"><?php echo htmlspecialchars($product['name']); ?></h3>
                        
                        <?php if ($product['has_reviewed']): ?>
                            <div style="color: var(--colors-success); font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 18px;">✓</span> You have already reviewed this piece.
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                
                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Rating</label>
                                    <div class="star-rating">
                                        <input type="radio" id="star5_<?php echo $product['product_id']; ?>" name="rating" value="5" required/><label for="star5_<?php echo $product['product_id']; ?>">★</label>
                                        <input type="radio" id="star4_<?php echo $product['product_id']; ?>" name="rating" value="4"/><label for="star4_<?php echo $product['product_id']; ?>">★</label>
                                        <input type="radio" id="star3_<?php echo $product['product_id']; ?>" name="rating" value="3"/><label for="star3_<?php echo $product['product_id']; ?>">★</label>
                                        <input type="radio" id="star2_<?php echo $product['product_id']; ?>" name="rating" value="2"/><label for="star2_<?php echo $product['product_id']; ?>">★</label>
                                        <input type="radio" id="star1_<?php echo $product['product_id']; ?>" name="rating" value="1"/><label for="star1_<?php echo $product['product_id']; ?>">★</label>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Your Review</label>
                                    <textarea name="comment" rows="3" required placeholder="What did you think about this piece?" style="width: 100%; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: 4px; font-family: var(--typography-body-font);"></textarea>
                                </div>
                                
                                <button type="submit" name="submit_review" class="button-primary" style="padding: 10px 20px; font-size: 14px;">Submit Review & Claim Voucher</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 32px;">
            <a href="dashboard.php" style="font-size: 14px; color: var(--colors-muted); text-decoration: underline;">← Back to Orders</a>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
