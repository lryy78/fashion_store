<?php
session_start();
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';

// Read the requested product ID and load its published record.
$product_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id 
                      WHERE p.id = ? AND (p.status = 'published' OR (p.status = 'scheduled' AND p.publish_at <= NOW()))");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// Stop if the product does not exist or is not currently published.
if (!$product) {
    echo "<div style='text-align: center; padding: 15rem 0;'><h2 style='font-weight: 400; font-family: var(--typography-display-font);'>Piece not found</h2><a href='products.php' class='button-secondary' style='margin-top: 2rem; display: inline-block;'>Return to Collection</a></div>";
    include __DIR__ . '/../includes/footer.php';
    exit();
}

// Increment the product view count for popularity sorting.
$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$product_id]);

// Load selectable product variations and gallery images.
$stmt = $pdo->prepare("SELECT * FROM product_variations WHERE product_id = ?");
$stmt->execute([$product_id]);
$variations = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT id FROM product_images WHERE product_id = ?");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll();

// Load reviews and calculate rating totals for the summary.
$stmt = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

$ratingData = ['avg_rating' => 0, 'review_count' => 0];
$ratingCounts = array_fill(1, 5, 0);
foreach ($reviews as $review) {
    $rating = (int)$review['rating'];
    if ($rating >= 1 && $rating <= 5) {
        $ratingCounts[$rating]++;
    }
}
if ($reviews) {
    $ratingData['review_count'] = count($reviews);
    $ratingData['avg_rating'] = array_sum(array_map(static fn($r) => (int)$r['rating'], $reviews)) / count($reviews);
}
$avg_rating = round((float)$ratingData['avg_rating'], 1);
$review_count = (int)$ratingData['review_count'];

// Check whether the logged-in buyer has saved this product.
$isWishlisted = false;
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'buyer') {
    try {
        $wishlistStmt = $pdo->prepare('SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?');
        $wishlistStmt->execute([$_SESSION['user_id'], $product_id]);
        $isWishlisted = (bool)$wishlistStmt->fetchColumn();
    } catch (PDOException $e) {
        $isWishlisted = false;
    }
}

$wishlistMessage = $_SESSION['wishlist_message'] ?? '';
unset($_SESSION['wishlist_message']);

// Store a compact recently viewed history in the session.
$recentIds = array_values(array_filter(array_map('intval', $_SESSION['recently_viewed_products'] ?? [])));
$recentBeforeCurrent = array_values(array_filter($recentIds, static fn($id) => $id !== (int)$product_id));
$_SESSION['recently_viewed_products'] = array_slice(array_merge([(int)$product_id], $recentBeforeCurrent), 0, 8);

// Prepare shared product-card data for recommendations and browsing history.
$cardSelect = "SELECT p.id, p.name, p.price, p.discount_price, p.gender, c.name AS category_name,
                      (SELECT id FROM product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS image_id,
                      COALESCE((SELECT SUM(stock_quantity) FROM product_variations WHERE product_id = p.id), 0) AS total_stock,
                      COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = p.id), 0) AS avg_rating,
                      (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) AS review_count
               FROM products p
               LEFT JOIN categories c ON c.id = p.category_id";

// Load related products from the same category.
$relatedStmt = $pdo->prepare($cardSelect . " WHERE p.id <> ? AND p.category_id = ?
    AND (p.status = 'published' OR (p.status = 'scheduled' AND p.publish_at <= NOW()))
    ORDER BY p.is_featured DESC, p.views DESC, p.created_at DESC LIMIT 4");
$relatedStmt->execute([$product_id, $product['category_id']]);
$relatedProducts = $relatedStmt->fetchAll();

// Load previously viewed products except the current item.
$recentProducts = [];
$recentDisplayIds = array_slice($recentBeforeCurrent, 0, 4);
if ($recentDisplayIds) {
    $placeholders = implode(',', array_fill(0, count($recentDisplayIds), '?'));
    $recentStmt = $pdo->prepare($cardSelect . " WHERE p.id IN ($placeholders)
        AND (p.status = 'published' OR (p.status = 'scheduled' AND p.publish_at <= NOW()))
        ORDER BY FIELD(p.id, $placeholders)");
    $recentStmt->execute(array_merge($recentDisplayIds, $recentDisplayIds));
    $recentProducts = $recentStmt->fetchAll();
}
?>

<!-- Product Detail specific layout styles -->
<style>
.product-detail-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 64px;
    padding: 80px 0;
    background: var(--colors-canvas);
}

.product-gallery-studio {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.main-image-wrapper {
    aspect-ratio: 4/5;
    background: var(--colors-surface-soft);
    overflow: hidden;
    border-radius: var(--rounded-lg);
}

.main-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnails-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
}

.thumb-item {
    aspect-ratio: 1/1;
    background: var(--colors-surface-soft);
    cursor: pointer;
    border-radius: var(--rounded-sm);
    overflow: hidden;
    border: 2px solid transparent;
}

.thumb-item.active {
    border-color: var(--colors-primary);
}

.thumb-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info-studio {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.product-title-studio {
    font-size: 42px;
    margin-bottom: 16px;
}

.product-price-studio {
    font-size: 24px;
    color: var(--colors-ink);
    margin-bottom: 32px;
    display: block;
}

.selection-group {
    margin-bottom: 32px;
}

.selection-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--colors-muted);
    margin-bottom: 12px;
    display: block;
    font-weight: 600;
}

.options-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.option-btn {
    padding: 14px 28px;
    border: 2px solid var(--colors-hairline);
    background: #fff;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: var(--rounded-md);
    position: relative;
    overflow: hidden;
    min-width: 70px;
    text-align: center;
    letter-spacing: 0.02em;
}

.option-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.option-btn:not(.disabled):not(.selected):hover {
    border-color: var(--colors-ink);
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    background: #fafafa;
}

.option-btn:not(.disabled):not(.selected):hover::before {
    opacity: 1;
}

.option-btn:not(.disabled):not(.selected):active {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.1s ease;
}

.option-btn.selected {
    background: var(--colors-ink);
    color: #fff;
    border-color: var(--colors-ink);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    font-weight: 600;
    letter-spacing: 0.03em;
}

.option-btn.selected::before {
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 100%);
    opacity: 1;
}

.option-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: linear-gradient(135deg, #f8f8f8 0%, #f0f0f0 100%);
    color: #c0c0c0;
    border-color: #e5e5e5;
    text-decoration: none;
    position: relative;
    pointer-events: none;
    transform: none;
    box-shadow: none;
    filter: grayscale(30%);
}

.option-btn.disabled::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 140%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #d0d0d0, transparent);
    transform: translate(-50%, -50%) rotate(-45deg);
    transform-origin: center;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.option-btn:not(.disabled):not(.selected) {
    background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
}

.option-btn.selected {
    background: linear-gradient(135deg, var(--colors-ink) 0%, #2a2a2a 100%);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.option-btn {
    animation: fadeIn 0.4s ease-out;
}

.size-chart-link {
    font-size: 12px;
    text-decoration: underline;
    cursor: pointer;
    margin-top: 8px;
    display: inline-block;
}

.size-chart-modal {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.size-chart-content {
    background: #fff;
    padding: 48px;
    border-radius: var(--rounded-lg);
    max-width: 600px;
    width: 90%;
    position: relative;
}

.close-modal {
    position: absolute;
    top: 24px; right: 24px;
    cursor: pointer;
    font-size: 24px;
}


.product-title-row { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; }
.detail-wishlist-btn { width:46px; height:46px; flex:0 0 46px; border-radius:50%; border:1px solid var(--colors-hairline); background:#fff; color:var(--colors-ink); font-size:24px; cursor:pointer; transition:all .2s ease; }
.detail-wishlist-btn:hover,.detail-wishlist-btn.active { background:var(--colors-ink); color:#fff; transform:translateY(-2px); }
.stock-indicator { margin-top:-12px; margin-bottom:24px; padding:12px 14px; border-radius:var(--rounded-md); background:var(--colors-surface-soft); border:1px solid var(--colors-hairline); font-size:13px; }
.stock-indicator.good { color:#287a4b; }
.stock-indicator.low { color:#9a5b00; }
.stock-indicator.out { color:var(--colors-error); }
.product-strip { margin-top:80px; padding-top:56px; border-top:1px solid var(--colors-hairline); }
.product-strip-header { display:flex; align-items:end; justify-content:space-between; gap:16px; margin-bottom:28px; }
.mini-product-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:24px; }
.mini-product-card { display:block; color:inherit; text-decoration:none; }
.mini-product-image { aspect-ratio:4/5; overflow:hidden; border-radius:var(--rounded-md); background:var(--colors-surface-soft); margin-bottom:12px; }
.mini-product-image img { width:100%; height:100%; object-fit:cover; transition:transform .35s ease; }
.mini-product-card:hover img { transform:scale(1.04); }
.rating-summary { display:grid; grid-template-columns:180px 1fr; gap:36px; padding:28px; background:var(--colors-surface-soft); border-radius:var(--rounded-lg); margin-bottom:36px; }
.rating-bar-row { display:grid; grid-template-columns:44px 1fr 28px; gap:10px; align-items:center; font-size:12px; margin:7px 0; }
.rating-bar { height:8px; background:#e8e2da; border-radius:99px; overflow:hidden; }
.rating-bar span { display:block; height:100%; background:#f0a500; }
.size-guide-table { width:100%; border-collapse:collapse; margin-top:18px; }
.size-guide-table th,.size-guide-table td { padding:12px 10px; border-bottom:1px solid var(--colors-hairline); text-align:left; font-size:13px; }
.size-guide-note { margin-top:16px; color:var(--colors-muted); font-size:13px; line-height:1.6; }
@media (max-width: 768px) {
    .mini-product-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .rating-summary { grid-template-columns:1fr; }
}

@media (max-width: 992px) {
    .product-detail-layout {
        grid-template-columns: 1fr;
        gap: 48px;
        padding: 40px 0;
    }
}
</style>

<div class="container">
    <div class="product-detail-layout">
        <!-- Gallery -->
        <div class="product-gallery-studio">
            <div class="main-image-wrapper">
                <img id="main-product-img" src="<?php echo isset($images[0]) ? 'get_image.php?id='.$images[0]['id'] : 'assets/img/dress.png'; ?>">
            </div>
            <div class="thumbnails-grid">
                <?php foreach ($images as $index => $img): ?>
                    <div class="thumb-item <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeImage(this, 'get_image.php?id=<?php echo $img['id']; ?>')">
                        <img src="get_image.php?id=<?php echo $img['id']; ?>">
                    </div>
                <?php endforeach; ?>
                <?php if (count($images) < 1): ?>
                    <div class="thumb-item active"><img src="assets/img/dress.png"></div>
                    <div class="thumb-item" onclick="changeImage(this, 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=1000')"><img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=1000"></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info -->
        <div class="product-info-studio">
            <div class="breadcrumb" style="margin-bottom: 24px; font-size: 12px; color: var(--colors-muted);">
                <a href="index.php">HypeThread</a> / 
                <a href="products.php?gender=<?php echo $product['gender']; ?>"><?php echo $product['gender']; ?></a> / 
                <span style="color: var(--colors-ink);"><?php echo htmlspecialchars($product['name']); ?></span>
            </div>

            <?php if ($wishlistMessage): ?>
                <div style="margin-bottom:18px;padding:12px 14px;background:var(--colors-surface-soft);border:1px solid var(--colors-hairline);border-radius:var(--rounded-md);font-size:13px;">
                    <?php echo htmlspecialchars($wishlistMessage); ?>
                </div>
            <?php endif; ?>

            <?php $total_stock = array_sum(array_column($variations, 'stock_quantity')); ?>
            <div class="product-title-row">
                <h1 class="product-title-studio" style="margin-bottom:16px;">
                    <?php echo htmlspecialchars($product['name']); ?>
                    <?php if ($total_stock <= 0): ?>
                        <span style="font-size:14px;background:var(--colors-error);color:#fff;padding:4px 12px;border-radius:100px;vertical-align:middle;margin-left:12px;font-family:var(--typography-body-font);">OUT OF STOCK</span>
                    <?php endif; ?>
                </h1>
                <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'buyer'): ?>
                    <form method="post" action="actions/toggle_wishlist.php">
                        <input type="hidden" name="product_id" value="<?php echo (int)$product_id; ?>">
                        <input type="hidden" name="return_to" value="../product_detail.php?id=<?php echo (int)$product_id; ?>">
                        <button class="detail-wishlist-btn <?php echo $isWishlisted ? 'active' : ''; ?>" type="submit" title="<?php echo $isWishlisted ? 'Remove from wishlist' : 'Save to wishlist'; ?>" aria-label="Toggle wishlist"><?php echo $isWishlisted ? '♥' : '♡'; ?></button>
                    </form>
                <?php else: ?>
                    <a class="detail-wishlist-btn" href="login.php?msg=<?php echo urlencode('Please sign in to save products.'); ?>" style="display:flex;align-items:center;justify-content:center;text-decoration:none;" aria-label="Sign in to use wishlist">♡</a>
                <?php endif; ?>
            </div>
            <!-- Average Rating -->
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                <span style="color: #fbbf24; letter-spacing: 2px; font-size: 16px;">
                    <?php
                    if ($review_count > 0) {
                        $full = floor($avg_rating);
                        $half = ($avg_rating - $full) >= 0.5 ? 1 : 0;
                        $empty = 5 - $full - $half;
                        echo str_repeat('★', $full);
                        if ($half) echo '½';
                        echo str_repeat('☆', $empty);
                    } else {
                        echo '☆☆☆☆☆';
                    }
                    ?>
                </span>
                <?php if ($review_count > 0): ?>
                    <span style="color: var(--colors-ink); font-weight: 600; font-size: 14px;"><?php echo number_format($avg_rating, 1); ?></span>
                    <span style="color: var(--colors-muted); font-size: 13px;">(<?php echo $review_count; ?> review<?php echo $review_count > 1 ? 's' : ''; ?>)</span>
                <?php else: ?>
                    <span style="color: var(--colors-muted); font-size: 13px;">No reviews yet</span>
                <?php endif; ?>
            </div>

            <span class="product-price-studio">
                <?php if (!empty($product['discount_price'])): ?>
                    RM <?php echo number_format((float)$product['discount_price'], 2); ?>
                    <small style="font-size:15px;color:var(--colors-muted);text-decoration:line-through;margin-left:10px;">RM <?php echo number_format((float)$product['price'], 2); ?></small>
                <?php else: ?>
                    RM <?php echo number_format((float)$product['price'], 2); ?>
                <?php endif; ?>
            </span>

            <div id="stock-indicator" class="stock-indicator <?php echo $total_stock <= 0 ? 'out' : ($total_stock <= 5 ? 'low' : 'good'); ?>">
                <?php if ($total_stock <= 0): ?>Out of stock<?php elseif ($total_stock <= 5): ?>Only <?php echo (int)$total_stock; ?> units left across all options<?php else: ?>In stock — select a size and colour to see exact availability<?php endif; ?>
            </div>

            <p style="color: var(--colors-muted); margin-bottom: 40px; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

            <form action="actions/add_to_cart.php" method="POST" id="product-form">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                <input type="hidden" name="variation_id" id="selected-variation-id" required>

                <!-- Size Selection -->
                <div class="selection-group">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="selection-label">Select Size</span>
                        <span class="size-chart-link" onclick="toggleSizeChart(true)">Size Guide</span>
                    </div>
                    <div class="options-grid" id="size-options">
                        <?php 
                        $sizes = array_unique(array_column($variations, 'size'));
                        foreach ($sizes as $size): 
                        ?>
                            <button type="button" class="option-btn" data-size="<?php echo $size; ?>" onclick="selectSize(this, '<?php echo $size; ?>')"><?php echo $size; ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Color Selection -->
                <div class="selection-group">
                    <span class="selection-label">Select Color</span>
                    <div class="options-grid" id="color-options">
                        <?php 
                        $colors = array_unique(array_column($variations, 'color'));
                        foreach ($colors as $color): 
                        ?>
                            <button type="button" class="option-btn" data-color="<?php echo $color; ?>" onclick="selectColor(this, '<?php echo $color; ?>')"><?php echo $color; ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="selection-group">
                    <span class="selection-label">Quantity</span>
                    <input type="number" id="quantity-input" name="quantity" value="1" min="1" max="10" style="width: 80px; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
                </div>

                <div style="margin-top: 24px;">
                    <?php if ($is_visual_mode): ?>
                        <a href="/fashion_store/manager/edit_product.php?id=<?php echo $product_id; ?>" class="button-primary" style="width: 100%; display: block; text-align: center; padding: 20px; font-size: 16px; background: var(--colors-accent-teal);">✎ Edit Piece in HypeThread</a>
                    <?php else: ?>
                        <button type="submit" class="button-primary" id="add-to-cart-btn" style="width: 100%; padding: 20px; font-size: 16px;" disabled>Add to Bag</button>
                    <?php endif; ?>
                </div>
                <p id="variation-msg" style="font-size: 12px; color: var(--colors-error); margin-top: 12px; text-align: center;"></p>
            </form>

            <div style="margin-top: 48px; border-top: 1px solid var(--colors-hairline); padding-top: 24px;">
                <details style="padding: 16px 0; border-bottom: 1px solid var(--colors-hairline);">
                    <summary style="cursor: pointer; font-weight: 500;">Composition & Care</summary>
                    <div style="padding: 16px 0; color: var(--colors-muted); font-size: 14px;">
                        Hand wash only. Do not bleach. Dry flat in shade. Iron at low temperature.
                    </div>
                </details>
                <details style="padding: 16px 0; border-bottom: 1px solid var(--colors-hairline);">
                    <summary style="cursor: pointer; font-weight: 500;">Shipping & Returns</summary>
                    <div style="padding: 16px 0; color: var(--colors-muted); font-size: 14px;">
                        Free standard shipping on all orders over RM150. Returns accepted within 30 days.
                    </div>
                </details>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div style="margin-top: 80px; padding-top: 64px; border-top: 1px solid var(--colors-hairline);">
        <h2 style="font-family: var(--typography-display-font); font-size: 32px; margin-bottom: 40px; text-align: center;">What Others Say</h2>

        <div class="rating-summary">
            <div style="text-align:center;align-self:center;">
                <div style="font-family:var(--typography-display-font);font-size:54px;line-height:1;"><?php echo $review_count ? number_format($avg_rating, 1) : '—'; ?></div>
                <div style="color:#f0a500;letter-spacing:3px;font-size:18px;margin:10px 0;">
                    <?php echo $review_count ? str_repeat('★', (int)round($avg_rating)) . str_repeat('☆', 5 - (int)round($avg_rating)) : '☆☆☆☆☆'; ?>
                </div>
                <div style="font-size:13px;color:var(--colors-muted);"><?php echo $review_count; ?> verified review<?php echo $review_count === 1 ? '' : 's'; ?></div>
            </div>
            <div>
                <?php for ($star = 5; $star >= 1; $star--):
                    $countForStar = $ratingCounts[$star] ?? 0;
                    $percent = $review_count > 0 ? ($countForStar / $review_count) * 100 : 0;
                ?>
                    <div class="rating-bar-row">
                        <span><?php echo $star; ?> star</span>
                        <div class="rating-bar"><span style="width:<?php echo number_format($percent, 1, '.', ''); ?>%;"></span></div>
                        <span><?php echo $countForStar; ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <?php if (count($reviews) > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 32px;">
                <?php foreach ($reviews as $review): ?>
                    <div style="background: var(--colors-surface-soft); padding: 24px; border-radius: var(--rounded-md);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                            <div>
                                <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px;"><?php echo htmlspecialchars($review['full_name']); ?></div>
                                <div style="color: var(--colors-muted); font-size: 11px;"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                            </div>
                            <div style="color: #fbbf24; letter-spacing: 2px; font-size: 14px;">
                                <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                            </div>
                        </div>
                        <p style="font-size: 14px; line-height: 1.6; color: var(--colors-ink);"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        
                        <?php if ($review['admin_reply']): ?>
                            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px dashed var(--colors-hairline); font-size: 13px;">
                                <div style="font-weight: 600; margin-bottom: 4px;">Store Response:</div>
                                <p style="color: var(--colors-muted); margin: 0; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($review['admin_reply'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--colors-muted); font-size: 16px;">No reviews yet. Be the first to share your thoughts!</p>
        <?php endif; ?>
    </div>

    <?php if ($relatedProducts): ?>
        <section class="product-strip">
            <div class="product-strip-header">
                <div>
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--colors-muted);margin-bottom:5px;">Complete the look</div>
                    <h2 style="font-size:32px;margin:0;">You May Also Like</h2>
                </div>
                <a href="products.php?category=<?php echo urlencode($product['category_name']); ?>" style="font-size:13px;text-decoration:underline;">View Similar Pieces</a>
            </div>
            <div class="mini-product-grid">
                <?php foreach ($relatedProducts as $item): ?>
                    <a class="mini-product-card" href="product_detail.php?id=<?php echo (int)$item['id']; ?>">
                        <div class="mini-product-image"><img src="get_image.php?id=<?php echo (int)$item['image_id']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"></div>
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:var(--colors-muted);margin-bottom:5px;"><?php echo htmlspecialchars($item['category_name'] ?? 'Collection'); ?></div>
                        <div style="font-family:var(--typography-display-font);font-size:18px;margin-bottom:5px;"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div style="font-size:13px;font-weight:600;">RM <?php echo number_format((float)($item['discount_price'] ?: $item['price']), 2); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($recentProducts): ?>
        <section class="product-strip">
            <div class="product-strip-header">
                <div>
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--colors-muted);margin-bottom:5px;">Your browsing history</div>
                    <h2 style="font-size:32px;margin:0;">Recently Viewed</h2>
                </div>
            </div>
            <div class="mini-product-grid">
                <?php foreach ($recentProducts as $item): ?>
                    <a class="mini-product-card" href="product_detail.php?id=<?php echo (int)$item['id']; ?>">
                        <div class="mini-product-image"><img src="get_image.php?id=<?php echo (int)$item['image_id']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"></div>
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:var(--colors-muted);margin-bottom:5px;"><?php echo htmlspecialchars($item['category_name'] ?? 'Collection'); ?></div>
                        <div style="font-family:var(--typography-display-font);font-size:18px;margin-bottom:5px;"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div style="font-size:13px;font-weight:600;">RM <?php echo number_format((float)($item['discount_price'] ?: $item['price']), 2); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<!-- Size Chart Modal -->
<div class="size-chart-modal" id="size-chart-modal" role="dialog" aria-modal="true" aria-labelledby="size-guide-title" onclick="if(event.target===this) toggleSizeChart(false)">
    <div class="size-chart-content" style="max-width: 800px; max-height:90vh; overflow:auto;">
        <span class="close-modal" onclick="toggleSizeChart(false)" aria-label="Close size guide">&times;</span>
        <h2 id="size-guide-title" style="margin-bottom: 8px; font-family: var(--typography-display-font);">Size Guide</h2>
        <p style="color:var(--colors-muted);font-size:13px;">Measurements are approximate. Choose the larger size when between measurements.</p>
        <?php if (!empty($product['size_chart'])): ?>
            <div style="margin-top:18px;padding:16px;background:var(--colors-surface-soft);border-radius:var(--rounded-md);font-size:14px;line-height:1.7;white-space:pre-wrap;"><?php echo htmlspecialchars($product['size_chart']); ?></div>
        <?php endif; ?>

        <?php if ($product['category_name'] === 'Footwear'): ?>
            <table class="size-guide-table"><thead><tr><th>EU Size</th><th>Foot Length</th><th>UK Size</th></tr></thead><tbody>
                <tr><td>38</td><td>24.0 cm</td><td>5</td></tr><tr><td>39</td><td>24.7 cm</td><td>6</td></tr><tr><td>40</td><td>25.3 cm</td><td>6.5</td></tr><tr><td>41</td><td>26.0 cm</td><td>7.5</td></tr><tr><td>42</td><td>26.7 cm</td><td>8</td></tr>
            </tbody></table>
        <?php elseif ($product['category_name'] === 'Accessories'): ?>
            <div style="margin-top:22px;padding:22px;background:var(--colors-surface-soft);border-radius:var(--rounded-md);"><strong>One Size</strong><p class="size-guide-note">This accessory is designed with a universal fit. Product-specific dimensions are shown in the description where applicable.</p></div>
        <?php elseif ($product['gender'] === 'Kids'): ?>
            <table class="size-guide-table"><thead><tr><th>Size</th><th>Age</th><th>Height</th><th>Chest</th></tr></thead><tbody>
                <tr><td>S</td><td>4–6</td><td>104–116 cm</td><td>56–60 cm</td></tr><tr><td>M</td><td>7–9</td><td>122–134 cm</td><td>62–68 cm</td></tr><tr><td>L</td><td>10–12</td><td>140–152 cm</td><td>70–76 cm</td></tr>
            </tbody></table>
        <?php elseif ($product['gender'] === 'Men'): ?>
            <table class="size-guide-table"><thead><tr><th>Size</th><th>Chest</th><th>Waist</th><th>Hip</th></tr></thead><tbody>
                <tr><td>S</td><td>86–91 cm</td><td>71–76 cm</td><td>89–94 cm</td></tr><tr><td>M</td><td>96–101 cm</td><td>81–86 cm</td><td>99–104 cm</td></tr><tr><td>L</td><td>106–111 cm</td><td>91–96 cm</td><td>109–114 cm</td></tr><tr><td>XL</td><td>116–121 cm</td><td>101–106 cm</td><td>119–124 cm</td></tr>
            </tbody></table>
        <?php else: ?>
            <table class="size-guide-table"><thead><tr><th>Size</th><th>Bust</th><th>Waist</th><th>Hip</th></tr></thead><tbody>
                <tr><td>S</td><td>82–86 cm</td><td>64–68 cm</td><td>88–92 cm</td></tr><tr><td>M</td><td>87–92 cm</td><td>69–74 cm</td><td>93–98 cm</td></tr><tr><td>L</td><td>93–99 cm</td><td>75–81 cm</td><td>99–105 cm</td></tr>
            </tbody></table>
        <?php endif; ?>
        <p class="size-guide-note">How to measure: keep the tape level and comfortably close to the body without pulling tightly.</p>
    </div>
</div>

<script>
const variations = <?php echo json_encode($variations); ?>;
let selectedSize = '';
let selectedColor = '';

function changeImage(el, src) {
    document.getElementById('main-product-img').src = src;
    document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}

function selectSize(btn, size) {
    // Check if button is disabled
    if (btn.disabled) {
        return;
    }
    
    // If clicking the same size, deselect it
    if (selectedSize === size) {
        selectedSize = '';
        btn.classList.remove('selected');
        updateAvailableOptions();
        updateVariations();
        return;
    }
    
    selectedSize = size;
    document.querySelectorAll('#size-options .option-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    updateAvailableOptions();
    updateVariations();
}

function selectColor(btn, color) {
    // Check if button is disabled
    if (btn.disabled) {
        return;
    }
    
    // If clicking the same color, deselect it
    if (selectedColor === color) {
        selectedColor = '';
        btn.classList.remove('selected');
        updateAvailableOptions();
        updateVariations();
        return;
    }
    
    selectedColor = color;
    document.querySelectorAll('#color-options .option-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    updateAvailableOptions();
    updateVariations();
}

function updateAvailableOptions() {
    const sizeButtons = document.querySelectorAll('#size-options .option-btn');
    const colorButtons = document.querySelectorAll('#color-options .option-btn');

    sizeButtons.forEach(btn => {
        const size = btn.getAttribute('data-size');
        const available = variations.some(v =>
            v.size === size && Number(v.stock_quantity) > 0 && (!selectedColor || v.color === selectedColor)
        );
        btn.classList.toggle('disabled', !available);
        btn.disabled = !available;
        if (!available && selectedSize === size) {
            selectedSize = '';
            btn.classList.remove('selected');
        }
    });

    colorButtons.forEach(btn => {
        const color = btn.getAttribute('data-color');
        const available = variations.some(v =>
            v.color === color && Number(v.stock_quantity) > 0 && (!selectedSize || v.size === selectedSize)
        );
        btn.classList.toggle('disabled', !available);
        btn.disabled = !available;
        if (!available && selectedColor === color) {
            selectedColor = '';
            btn.classList.remove('selected');
        }
    });
}

function setStockMessage(text, state) {
    const stock = document.getElementById('stock-indicator');
    if (!stock) return;
    stock.textContent = text;
    stock.classList.remove('good', 'low', 'out');
    stock.classList.add(state);
}

function updateVariations() {
    const msg = document.getElementById('variation-msg');
    const btn = document.getElementById('add-to-cart-btn');
    const varInput = document.getElementById('selected-variation-id');
    const qtyInput = document.getElementById('quantity-input');

    if (!btn || !varInput) return;

    if (selectedSize && selectedColor) {
        const found = variations.find(v => v.size === selectedSize && v.color === selectedColor);
        if (found && Number(found.stock_quantity) > 0) {
            const stockQty = Number(found.stock_quantity);
            varInput.value = found.id;
            btn.disabled = false;
            msg.innerText = '';
            qtyInput.max = Math.min(stockQty, 10);
            if (Number(qtyInput.value) > Number(qtyInput.max)) qtyInput.value = qtyInput.max;
            if (stockQty <= 3) {
                setStockMessage(`Only ${stockQty} left in ${selectedSize} / ${selectedColor}`, 'low');
            } else {
                setStockMessage(`${stockQty} available in ${selectedSize} / ${selectedColor}`, 'good');
            }
        } else {
            varInput.value = '';
            btn.disabled = true;
            msg.innerText = found ? 'This combination is currently out of stock.' : 'This combination is not available.';
            setStockMessage('Selected option is unavailable', 'out');
        }
    } else {
        varInput.value = '';
        btn.disabled = true;
        msg.innerText = '';
        const matching = variations.filter(v =>
            Number(v.stock_quantity) > 0 &&
            (!selectedSize || v.size === selectedSize) &&
            (!selectedColor || v.color === selectedColor)
        );
        const remaining = matching.reduce((sum, v) => sum + Number(v.stock_quantity), 0);
        if (remaining <= 0) {
            setStockMessage('No stock is available for the current selection', 'out');
        } else if (selectedSize || selectedColor) {
            setStockMessage(`${remaining} units match your current selection — choose the remaining option`, remaining <= 5 ? 'low' : 'good');
        } else {
            setStockMessage('In stock — select a size and colour to see exact availability', 'good');
        }
    }
}

function toggleSizeChart(show) {
    const modal = document.getElementById('size-chart-modal');
    modal.style.display = show ? 'flex' : 'none';
    document.body.style.overflow = show ? 'hidden' : '';
}

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') toggleSizeChart(false);
});

updateAvailableOptions();
updateVariations();
</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>
