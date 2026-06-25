<?php
session_start();
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? 0;
$max_price = $_GET['max_price'] ?? 1000;
$selected_size = $_GET['size'] ?? '';
$selected_color = $_GET['color'] ?? '';
$selected_gender = $_GET['gender'] ?? '';
$in_stock = isset($_GET['in_stock']);
$on_sale = isset($_GET['on_sale']);

$sort_rating = $_GET['sort_rating'] ?? '';

$query = "SELECT p.*, c.name as category_name, (SELECT id FROM product_images WHERE product_id = p.id LIMIT 1) as image_id,
          COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = p.id), 0) as avg_rating,
          (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN product_variations pv ON p.id = pv.product_id
          WHERE (p.status = 'published' OR (p.status = 'scheduled' AND p.publish_at <= NOW()))";
$params = [];

if ($search) {
    $query .= " AND p.name LIKE ?";
    $params[] = "%$search%";
}
if ($category) {
    $query .= " AND c.name = ?";
    $params[] = $category;
}
if ($selected_gender) {
    $query .= " AND p.gender = ?";
    $params[] = $selected_gender;
}
if ($selected_size) {
    $query .= " AND pv.size = ?";
    $params[] = $selected_size;
}
if ($selected_color) {
    $query .= " AND pv.color = ?";
    $params[] = $selected_color;
}
if ($in_stock) {
    $query .= " AND pv.stock_quantity > 0";
}
if ($on_sale) {
    $query .= " AND p.discount_price IS NOT NULL";
}

$query .= " AND p.price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;

$query .= " GROUP BY p.id";

// Apply rating sort
if ($sort_rating === 'high') {
    $query .= " ORDER BY avg_rating DESC, review_count DESC";
} elseif ($sort_rating === 'low') {
    $query .= " ORDER BY avg_rating ASC, review_count DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$all_colors = $pdo->query("SELECT DISTINCT color FROM product_variations WHERE color IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$all_sizes = $pdo->query("SELECT DISTINCT size FROM product_variations WHERE size IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
?>

<!-- Shop specific layout styles -->
<style>
.shop-layout {
    display: flex;
    gap: 48px;
    margin-bottom: var(--spacing-section);
    padding-top: var(--spacing-xl);
}

.filter-sidebar {
    width: 220px;
    flex-shrink: 0;
}

.filter-group h4 {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--colors-muted);
    margin-bottom: 12px;
    font-weight: 600;
}

.price-inputs {
    display: flex;
    gap: 8px;
    align-items: center;
}

.shop-main {
    flex: 1;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 48px 32px;
}

.product-card-studio {
    cursor: pointer;
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.product-card-studio:hover {
    transform: translateY(-8px);
}

.product-card-studio .image-wrapper {
    aspect-ratio: 4/5;
    background: var(--colors-surface-soft);
    margin-bottom: 16px;
    overflow: hidden;
    position: relative;
    border-radius: var(--rounded-md);
}

.product-card-studio img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.product-card-studio:hover img {
    transform: scale(1.05);
}

.sale-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: var(--colors-primary);
    color: #fff;
    padding: 4px 8px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-radius: 2px;
    z-index: 10;
}

.product-card-studio .meta {
    padding: 0 4px;
}

.product-card-studio .cat {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--colors-muted);
    margin-bottom: 4px;
}

.product-card-studio .name {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 6px;
    color: var(--colors-ink);
    font-family: var(--typography-display-font);
}

.product-card-studio .price-container {
    display: flex;
    gap: 12px;
    align-items: baseline;
}

.product-card-studio .current-price {
    font-size: 14px;
    color: var(--colors-ink);
    font-weight: 600;
}

.product-card-studio .old-price {
    font-size: 13px;
    color: var(--colors-muted);
    text-decoration: line-through;
}

@media (max-width: 768px) {
    .shop-layout {
        flex-direction: column;
        gap: 32px;
    }
    .filter-sidebar {
        width: 100%;
    }
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
}
</style>

<div class="shop-banner" style="background: var(--colors-surface-soft); padding: 80px 0; border-bottom: 1px solid var(--colors-hairline);">
    <div class="container">
        <h1 style="font-size: 48px; margin-bottom: 16px;">The Collection</h1>
        <p style="font-size: 18px; color: var(--colors-muted); max-width: 600px;">Discover our meticulously curated selection of ready-to-wear pieces, accessories, and timeless essentials.</p>
    </div>
</div>

<div class="container shop-layout">
    <aside class="filter-sidebar">
        <h3 style="margin-bottom: 32px; font-size: 18px; font-weight: 400;">Refine Pieces</h3>
        <form method="GET">
            <div class="filter-group" style="margin-bottom: 24px;">
                <h4>Search</h4>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Keywords..." style="width: 100%; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
            </div>

            <div class="filter-group" style="margin-bottom: 24px;">
                <h4>Gender</h4>
                <select name="gender" style="width: 100%; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
                    <option value="">All Genders</option>
                    <option value="Women" <?php echo $selected_gender == 'Women' ? 'selected' : ''; ?>>Women</option>
                    <option value="Men" <?php echo $selected_gender == 'Men' ? 'selected' : ''; ?>>Men</option>
                    <option value="Kids" <?php echo $selected_gender == 'Kids' ? 'selected' : ''; ?>>Kids</option>
                    <option value="Unisex" <?php echo $selected_gender == 'Unisex' ? 'selected' : ''; ?>>Unisex</option>
                </select>
            </div>

            <div class="filter-group" style="margin-bottom: 24px;">
                <h4>Category</h4>
                <select name="category" style="width: 100%; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo $category == $cat['name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group" style="margin-bottom: 24px;">
                <h4>Price Range</h4>
                <div class="price-inputs">
                    <input type="number" name="min_price" placeholder="Min" value="<?php echo htmlspecialchars($min_price); ?>" style="width: 80px; padding: 8px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
                    <span style="color: var(--colors-muted);">-</span>
                    <input type="number" name="max_price" placeholder="Max" value="<?php echo htmlspecialchars($max_price); ?>" style="width: 80px; padding: 8px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
                </div>
            </div>
            
            <div class="filter-group" style="margin-bottom: 24px;">
                <h4>Size</h4>
                <select name="size" style="width: 100%; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
                    <option value="">All Sizes</option>
                    <?php foreach ($all_sizes as $size_opt): ?>
                        <option value="<?php echo htmlspecialchars($size_opt); ?>" <?php echo $selected_size == $size_opt ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($size_opt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group" style="margin-bottom: 24px;">
                <h4>Color</h4>
                <select name="color" style="width: 100%; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
                    <option value="">All Colors</option>
                    <?php foreach ($all_colors as $color_opt): ?>
                        <option value="<?php echo htmlspecialchars($color_opt); ?>" <?php echo $selected_color == $color_opt ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($color_opt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group" style="margin-bottom: 32px;">
                <h4>Offerings</h4>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; margin-bottom: 8px;">
                    <input type="checkbox" name="in_stock" <?php echo $in_stock ? 'checked' : ''; ?>>
                    Available Now
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer;">
                    <input type="checkbox" name="on_sale" <?php echo $on_sale ? 'checked' : ''; ?>>
                    Special Offers
                </label>
            </div>

            <div class="filter-group" style="margin-bottom: 32px;">
                <h4>Sort by Rating</h4>
                <select name="sort_rating" style="width: 100%; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
                    <option value="" <?php echo $sort_rating == '' ? 'selected' : ''; ?>>Default</option>
                    <option value="high" <?php echo $sort_rating == 'high' ? 'selected' : ''; ?>>Highest Rated</option>
                    <option value="low" <?php echo $sort_rating == 'low' ? 'selected' : ''; ?>>Lowest Rated</option>
                </select>
            </div>

            <button type="submit" class="button-primary" style="width: 100%; padding: 16px;">Apply Filters</button>
            <?php if($search || $category || $min_price != 0 || $max_price != 1000 || $selected_size || $selected_color || $in_stock || $on_sale || $sort_rating): ?>
                <a href="products.php" style="display: block; text-align: center; margin-top: 16px; font-size: 13px; text-decoration: underline; color: var(--colors-muted);">Reset Filters</a>
            <?php endif; ?>
        </form>
    </aside>

    <main class="shop-main">
        <div class="shop-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; border-bottom: 1px solid var(--colors-hairline); padding-bottom: 16px;">
            <h2 style="font-size: 24px; font-weight: 400;"><?php echo $category ? htmlspecialchars($category) : ($on_sale ? 'Special Offers' : 'All Arrivals'); ?></h2>
            <span style="color: var(--colors-muted); font-size: 14px;"><?php echo count($products); ?> Pieces Found</span>
        </div>
        
        <div class="product-grid">
            <?php if ($products): ?>
                <?php foreach ($products as $index => $product): ?>
                    <div class="product-card-studio" onclick="window.location.href='product_detail.php?id=<?php echo $product['id']; ?>'">
                        <div class="image-wrapper">
                            <?php if ($product['discount_price']): ?>
                                <div class="sale-badge">Sale</div>
                            <?php endif; ?>
                            <img src="get_image.php?id=<?php echo $product['image_id']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="meta">
                            <div class="cat"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <div class="name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="price-container">
                                <?php if ($product['discount_price']): ?>
                                    <span class="current-price">RM <?php echo number_format($product['discount_price'], 2); ?></span>
                                    <span class="old-price">RM <?php echo number_format($product['price'], 2); ?></span>
                                <?php else: ?>
                                    <span class="current-price">RM <?php echo number_format($product['price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; margin-top: 6px; min-height: 18px;">
                                <span style="color: #fbbf24; letter-spacing: 2px; font-size: 12px;">
                                    <?php 
                                    if ($product['review_count'] > 0) {
                                        $avg = round($product['avg_rating'] * 2) / 2;
                                        $full = floor($avg);
                                        $half = ($avg - $full) >= 0.5 ? 1 : 0;
                                        echo str_repeat('★', $full);
                                        if ($half) echo '½';
                                        echo str_repeat('☆', 5 - $full - $half);
                                    } else {
                                        echo '☆☆☆☆☆';
                                    }
                                    ?>
                                </span>
                                <span style="color: var(--colors-muted); font-size: 11px;">
                                    <?php if ($product['review_count'] > 0): ?>
                                        (<?php echo $product['review_count']; ?>)
                                    <?php else: ?>
                                        No ratings
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; padding: 100px 40px; text-align: center; background: var(--colors-surface-soft); border-radius: var(--rounded-lg);">
                    <p style="font-size: 20px; color: var(--colors-muted); margin-bottom: 24px;">No pieces match your current criteria.</p>
                    <a href="products.php" class="button-secondary">Reset All Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
