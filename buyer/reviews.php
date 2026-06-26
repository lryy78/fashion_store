<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';

// Base query for order items that are completed
$query = "
    SELECT 
        o.id as order_id, 
        o.created_at as order_date,
        p.id as product_id, 
        p.name as product_name, 
        (SELECT id FROM product_images WHERE product_id = p.id LIMIT 1) as image_id,
        r.id as review_id,
        r.rating,
        r.comment,
        r.created_at as review_date,
        r.admin_reply
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN product_variations pv ON oi.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    LEFT JOIN reviews r ON r.order_id = o.id AND r.product_id = p.id AND r.user_id = o.user_id
    WHERE o.user_id = ? AND o.status = 'completed'
";

if ($filter == 'pending') {
    $query .= " AND r.id IS NULL";
} elseif ($filter == 'reviewed') {
    $query .= " AND r.id IS NOT NULL";
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php renderSidebar('buyer'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Feedback Center</div>
                <h1 style="margin: 0; font-size: 40px;">Product Reviews</h1>
            </div>
            
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="?filter=all" class="button-secondary <?php echo $filter == 'all' ? 'active' : ''; ?>" style="font-size: 12px; padding: 8px 16px; <?php echo $filter == 'all' ? 'background: var(--colors-ink); color: #fff;' : ''; ?>">All</a>
                <a href="?filter=pending" class="button-secondary <?php echo $filter == 'pending' ? 'active' : ''; ?>" style="font-size: 12px; padding: 8px 16px; <?php echo $filter == 'pending' ? 'background: var(--colors-ink); color: #fff;' : ''; ?>">Pending</a>
                <a href="?filter=reviewed" class="button-secondary <?php echo $filter == 'reviewed' ? 'active' : ''; ?>" style="font-size: 12px; padding: 8px 16px; <?php echo $filter == 'reviewed' ? 'background: var(--colors-ink); color: #fff;' : ''; ?>">Reviewed</a>
            </div>
        </header>

        <div style="display: grid; gap: 24px;">
            <?php if ($items): ?>
                <?php foreach ($items as $item): ?>
                    <div class="surface-card" style="display: flex; gap: 32px; padding: 32px; border-radius: var(--rounded-lg);">
                        <div style="width: 120px; flex-shrink: 0;">
                            <img src="/fashion_store/get_image.php?id=<?php echo $item['image_id']; ?>" style="width: 100%; aspect-ratio: 4/5; object-fit: cover; border-radius: var(--rounded-md);">
                        </div>
                        
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                <div>
                                    <h3 style="margin: 0 0 4px; font-size: 20px; font-weight: 500;"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                    <div style="font-size: 12px; color: var(--colors-muted);">Purchased on <?php echo date('M d, Y', strtotime($item['order_date'])); ?> • Order #ORD-<?php echo str_pad($item['order_id'], 5, '0', STR_PAD_LEFT); ?></div>
                                </div>
                                
                                <?php if (!$item['review_id']): ?>
                                    <a href="write_review.php?order_id=<?php echo $item['order_id']; ?>" class="button-primary" style="font-size: 12px; padding: 8px 20px;">Write Review</a>
                                <?php else: ?>
                                    <div style="color: #fbbf24; font-size: 16px; letter-spacing: 2px;">
                                        <?php echo str_repeat('★', $item['rating']) . str_repeat('☆', 5 - $item['rating']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($item['review_id']): ?>
                                <div style="background: var(--colors-canvas); padding: 20px; border-radius: var(--rounded-md); margin-top: 16px;">
                                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--colors-muted); margin-bottom: 8px;">Your Review • <?php echo date('M d, Y', strtotime($item['review_date'])); ?></div>
                                    <p style="margin: 0; font-size: 14px; line-height: 1.6; color: var(--colors-ink);"><?php echo nl2br(htmlspecialchars($item['comment'])); ?></p>
                                    
                                    <?php if ($item['admin_reply']): ?>
                                        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px dashed var(--colors-hairline);">
                                            <div style="font-weight: 600; font-size: 12px; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                                <span style="color: var(--colors-primary);">●</span> Store Response
                                            </div>
                                            <p style="margin: 0; font-size: 13px; color: var(--colors-muted); line-height: 1.5; font-style: italic;"><?php echo nl2br(htmlspecialchars($item['admin_reply'])); ?></p>
                                            <div style="margin-top: 8px; font-size: 11px; color: var(--colors-muted);">View this reply in <a href="../help.php" style="text-decoration: underline;">Support Section</a></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div style="margin-top: 16px; display: flex; align-items: center; gap: 12px; color: var(--colors-muted); font-size: 14px;">
                                    <span>No review yet.</span>
                                    <span style="font-size: 12px; background: #fefce8; color: #854d0e; padding: 4px 10px; border-radius: 100px; font-weight: 600;">Earn 10% Discount Voucher</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding: 100px 0; text-align: center; background: var(--colors-surface-soft); border-radius: var(--rounded-lg);">
                    <div style="font-size: 48px; margin-bottom: 24px;">📝</div>
                    <p style="font-size: 18px; color: var(--colors-muted);">No items found matching your filter.</p>
                    <a href="../products.php" class="button-secondary" style="margin-top: 24px;">Discover New Pieces</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
