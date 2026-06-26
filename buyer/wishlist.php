<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Restrict the page to authenticated buyer accounts.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
    header('Location: ../login.php');
    exit;
}

// Fetch the buyer's saved products with image, stock, and rating data.
$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare(
    "SELECT w.created_at AS saved_at, p.id, p.name, p.price, p.discount_price, p.gender,
            c.name AS category_name,
            (SELECT id FROM product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS image_id,
            COALESCE((SELECT SUM(stock_quantity) FROM product_variations WHERE product_id = p.id), 0) AS total_stock,
            COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = p.id), 0) AS avg_rating,
            (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) AS review_count
     FROM wishlists w
     JOIN products p ON p.id = w.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE w.user_id = ?
       AND (p.status = 'published' OR (p.status = 'scheduled' AND p.publish_at <= NOW()))
     ORDER BY w.created_at DESC"
);
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

// Read and clear the one-time wishlist feedback message.
$message = $_SESSION['wishlist_message'] ?? '';
unset($_SESSION['wishlist_message']);

include __DIR__ . '/../includes/header.php';
?>

<style>
.wishlist-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:28px; }
.wishlist-card { background:#fff; border:1px solid var(--colors-hairline); border-radius:var(--rounded-lg); overflow:hidden; }
.wishlist-image { aspect-ratio:4/5; background:var(--colors-surface-soft); overflow:hidden; }
.wishlist-image img { width:100%; height:100%; object-fit:cover; }
.wishlist-body { padding:20px; }
.wishlist-actions { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:18px; }
.wishlist-actions form { margin:0; }
.wishlist-actions button,.wishlist-actions a { width:100%; min-height:44px; display:flex; align-items:center; justify-content:center; }
</style>

<div class="dashboard-layout">
    <?php renderSidebar('buyer'); ?>
    <div class="dashboard-main fade-in-up">
        <header style="margin-bottom:var(--spacing-xxl); display:flex; justify-content:space-between; gap:24px; align-items:flex-end;">
            <div>
                <div style="font-size:14px;color:var(--colors-muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px;font-weight:600;">Saved collection</div>
                <h1 style="margin:0;font-size:40px;">My Wishlist</h1>
            </div>
            <a href="../products.php" class="button-secondary">Continue Shopping</a>
        </header>

        <?php if ($message): ?>
            <div style="margin-bottom:24px;padding:14px 18px;background:var(--colors-surface-soft);border:1px solid var(--colors-hairline);border-radius:var(--rounded-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($items): ?>
            <div class="wishlist-grid">
                <?php foreach ($items as $item): ?>
                    <article class="wishlist-card">
                        <a class="wishlist-image" href="../product_detail.php?id=<?php echo (int)$item['id']; ?>" style="display:block;">
                            <img src="../get_image.php?id=<?php echo (int)$item['image_id']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </a>
                        <div class="wishlist-body">
                            <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--colors-muted);margin-bottom:6px;"><?php echo htmlspecialchars($item['category_name'] ?? 'Collection'); ?></div>
                            <h2 style="font-size:21px;margin:0 0 8px;"><?php echo htmlspecialchars($item['name']); ?></h2>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <strong>RM <?php echo number_format((float)($item['discount_price'] ?: $item['price']), 2); ?></strong>
                                <?php if ($item['discount_price']): ?><span style="text-decoration:line-through;color:var(--colors-muted);">RM <?php echo number_format((float)$item['price'], 2); ?></span><?php endif; ?>
                            </div>
                            <div style="margin-top:10px;font-size:12px;color:<?php echo $item['total_stock'] > 0 ? '#287a4b' : 'var(--colors-error)'; ?>;">
                                <?php echo $item['total_stock'] > 0 ? ((int)$item['total_stock'] <= 5 ? 'Only ' . (int)$item['total_stock'] . ' left' : 'In stock') : 'Out of stock'; ?>
                            </div>
                            <div style="margin-top:8px;color:#f0a500;font-size:12px;">
                                <?php echo $item['review_count'] ? str_repeat('★', (int)round($item['avg_rating'])) . ' ' . number_format((float)$item['avg_rating'], 1) : '☆☆☆☆☆ No reviews'; ?>
                            </div>
                            <div class="wishlist-actions">
                                <a class="button-secondary" href="../product_detail.php?id=<?php echo (int)$item['id']; ?>">Choose Options</a>
                                <?php if ($item['total_stock'] > 0): ?>
                                    <form method="post" action="../actions/wishlist_to_cart.php">
                                        <input type="hidden" name="product_id" value="<?php echo (int)$item['id']; ?>">
                                        <button type="submit" class="button-primary">Quick Add</button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="button-primary" disabled>Unavailable</button>
                                <?php endif; ?>
                                <form method="post" action="../actions/toggle_wishlist.php" style="grid-column:1/-1;">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$item['id']; ?>">
                                    <input type="hidden" name="return_to" value="../buyer/wishlist.php">
                                    <button type="submit" class="button-secondary">Remove</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="padding:100px 32px;text-align:center;background:var(--colors-surface-soft);border-radius:var(--rounded-lg);">
                <div style="font-size:52px;margin-bottom:18px;">♡</div>
                <h2 style="font-size:28px;margin-bottom:12px;">Your wishlist is empty</h2>
                <p style="color:var(--colors-muted);margin-bottom:24px;">Save pieces from the collection so you can find them again later.</p>
                <a href="../products.php" class="button-primary">Discover New Pieces</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
