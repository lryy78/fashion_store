<?php
session_start();
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';

$selected_gender = $_GET['gender'] ?? '';

// Featured Products Query
$featured_query = "SELECT p.*, c.name as category_name, (SELECT id FROM product_images WHERE product_id = p.id LIMIT 1) as image_id,
                  COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = p.id), 0) as avg_rating,
                  (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE is_featured = 1 AND (p.status = 'published' OR (p.status = 'scheduled' AND p.publish_at <= NOW()))";
if ($selected_gender) {
    $featured_query .= " AND p.gender = :gender";
}
$featured_query .= " LIMIT 8";
$stmt = $pdo->prepare($featured_query);
if ($selected_gender) {
    $stmt->bindValue(':gender', $selected_gender);
}
$stmt->execute();
$featured_products = $stmt->fetchAll();

// Helper to fetch products by category and gender
function getProductsByCategory($category_name, $gender = '', $limit = 8) {
    global $pdo;
    $query = "SELECT p.*, c.name as category_name, (SELECT id FROM product_images WHERE product_id = p.id LIMIT 1) as image_id 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE c.name = :cat AND (p.status = 'published' OR (p.status = 'scheduled' AND p.publish_at <= NOW()))";
    if ($gender) {
        $query .= " AND p.gender = :gender";
    }
    $query .= " LIMIT :limit";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':cat', $category_name);
    if ($gender) {
        $stmt->bindValue(':gender', $gender);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Fetch products for gender sections if on main page
function getProductsByGender($gender, $limit = 8) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, (SELECT id FROM product_images WHERE product_id = p.id LIMIT 1) as image_id 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.gender = ? AND (p.status = 'published' OR (p.status = 'scheduled' AND p.publish_at <= NOW())) LIMIT ?");
    $stmt->execute([$gender, $limit]);
    return $stmt->fetchAll();
}

if (!$selected_gender) {
    $women_products = getProductsByGender('Women');
    $men_products = getProductsByGender('Men');
    $kids_products = getProductsByGender('Kids');
} else {
    $tops_products = getProductsByCategory('Tops', $selected_gender);
    $bottoms_products = getProductsByCategory('Bottoms', $selected_gender);
    $acc_products = getProductsByCategory('Accessories', $selected_gender);
}
?>

<!-- Index specific editorial styles -->
<style>
    .hero-section-studio {
        background-color: var(--colors-canvas);
        padding: 80px 0;
        border-bottom: 1px solid var(--colors-hairline-soft);
    }

    .hero-studio-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 64px;
        align-items: center;
    }

    .hero-studio-content h1 {
        font-size: clamp(48px, 6vw, 84px);
        line-height: 0.95;
        margin-bottom: 32px;
        letter-spacing: -0.04em;
    }

    .hero-studio-content p {
        font-size: 18px;
        line-height: 1.6;
        color: var(--colors-muted);
        max-width: 480px;
        margin-bottom: 40px;
    }

    .category-grid-studio {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2px;
        background-color: var(--colors-hairline);
        border: 1px solid var(--colors-hairline);
        margin-top: -1px;
    }

    .category-card-studio {
        background: #fff;
        padding: 64px 32px;
        text-align: center;
        text-decoration: none;
        transition: background 0.3s ease;
    }

    .category-card-studio:hover {
        background: var(--colors-surface-soft);
    }

    .category-card-studio .icon {
        font-size: 32px;
        margin-bottom: 24px;
        display: block;
    }

    .category-card-studio h3 {
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-weight: 600;
        color: var(--colors-ink);
    }

    .promo-banner-studio {
        background-color: var(--colors-surface-card);
        color: var(--colors-ink);
        padding: 120px 0;
        text-align: center;
        position: relative;
        overflow: hidden;
        border-top: 1px solid var(--colors-hairline-soft);
        border-bottom: 1px solid var(--colors-hairline-soft);
    }

    .promo-banner-studio::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(204, 120, 92, 0.05) 0%, transparent 60%);
        pointer-events: none;
    }

    .promo-banner-studio h2 {
        font-size: 56px;
        margin-bottom: 24px;
        font-family: var(--typography-display-font);
        letter-spacing: -0.04em;
        color: var(--colors-ink);
    }

    .promo-banner-studio p {
        font-size: 18px;
        color: var(--colors-muted);
        margin-bottom: 48px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.6;
    }

    .section-header-studio {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 32px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--colors-hairline-soft);
    }

    .section-header-studio h2 {
        font-size: 32px;
        margin: 0;
    }

    /* Horizontal Scroll Layout */
    .product-grid-horizontal {
        display: flex;
        overflow-x: auto;
        gap: 32px;
        padding-bottom: 32px;
        scroll-snap-type: x mandatory;
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .product-grid-horizontal::-webkit-scrollbar {
        display: none;
    }

    .product-grid-horizontal .product-card-studio {
        flex: 0 0 280px;
        scroll-snap-align: start;
    }

    /* Product Card Studio Styles */
    .product-card-studio {
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .product-card-studio:hover {
        transform: translateY(-4px);
    }

    .product-card-studio .image-wrapper {
        aspect-ratio: 4/5;
        background: var(--colors-surface-soft);
        margin-bottom: 16px;
        overflow: hidden;
        position: relative;
        border-radius: var(--rounded-lg);
    }

    .product-card-studio img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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
        font-size: 15px;
        font-weight: 500;
        margin-bottom: 4px;
        color: var(--colors-ink);
    }

    .product-card-studio .price {
        font-size: 14px;
        color: var(--colors-ink);
        font-weight: 400;
    }

    @media (max-width: 992px) {
        .hero-studio-grid {
            grid-template-columns: 1fr;
            gap: 48px;
        }
        .category-grid-studio {
            grid-template-columns: repeat(2, 1fr);
        }
        .product-grid-horizontal .product-card-studio {
            flex: 0 0 240px;
        }
    }
</style>

<section class="hero-section-studio" style="padding: 0; position: relative;">
    <div style="height: 80vh; width: 100%; position: relative; overflow: hidden; background: var(--colors-canvas);">
        <img src="<?php 
            if ($selected_gender == 'Women') echo 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=2000';
            elseif ($selected_gender == 'Men') echo 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?q=80&w=2000';
            elseif ($selected_gender == 'Kids') echo 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?q=80&w=2000';
            else echo 'assets/img/hero.png'; 
        ?>" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.85;">
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, rgba(250, 249, 245, 0.9) 0%, rgba(250, 249, 245, 0) 60%);"></div>
        <div style="position: absolute; top: 50%; left: 10%; transform: translateY(-50%); color: var(--colors-ink);">
            <div style="font-size: 14px; letter-spacing: 0.3em; margin-bottom: 24px; color: var(--colors-muted); font-weight: 600;">
                <?php echo $selected_gender ? strtoupper($selected_gender) : 'SUMMER 2026'; ?>
            </div>
            <h1 style="font-size: 80px; margin-bottom: 24px; color: var(--colors-ink);">
                <?php echo $selected_gender ? $selected_gender . "'s <br>Collection." : 'The New <br>Standard.'; ?>
            </h1>
            <p style="font-size: 20px; max-width: 400px; margin-bottom: 40px; color: var(--colors-body);">Engineered for performance, designed for the street. Discover our most technical collection yet.</p>
            <a href="products.php<?php echo $selected_gender ? '?gender='.$selected_gender : ''; ?>" class="button-primary" style="padding: 16px 48px;">Shop All New Arrivals</a>
        </div>
    </div>
</section>

<div class="category-grid-studio">
    <?php if (!$selected_gender): ?>
        <a href="index.php?gender=Women" class="category-card-studio"><h3>Women</h3></a>
        <a href="index.php?gender=Men" class="category-card-studio"><h3>Men</h3></a>
        <a href="index.php?gender=Kids" class="category-card-studio"><h3>Kids</h3></a>
    <?php else: ?>
        <a href="products.php?gender=<?php echo $selected_gender; ?>&category=Tops" class="category-card-studio"><h3>Tops</h3></a>
        <a href="products.php?gender=<?php echo $selected_gender; ?>&category=Bottoms" class="category-card-studio"><h3>Bottoms</h3></a>
        <a href="products.php?gender=<?php echo $selected_gender; ?>&category=Accessories" class="category-card-studio"><h3>Accessories</h3></a>
    <?php endif; ?>
</div>

<!-- Featured Products -->
<section style="padding: 80px 0; background: #fff;">
    <div class="container">
        <div class="section-header-studio">
            <h2 style="font-family: var(--typography-display-font);">Featured Selections <?php echo $selected_gender ? '('.$selected_gender.')' : ''; ?></h2>
            <a href="products.php?is_featured=1<?php echo $selected_gender ? '&gender='.$selected_gender : ''; ?>" style="font-size: 14px; font-weight: 600; text-decoration: underline;">View All</a>
        </div>
        <div class="product-grid-horizontal">
            <?php foreach ($featured_products as $product): ?>
                <div class="product-card-studio" onclick="window.location.href='product_detail.php?id=<?php echo $product['id']; ?>'">
                    <div class="image-wrapper">
                        <img src="<?php echo $product['image'] ?? 'assets/img/dress.png'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="meta">
                        <div class="cat"><?php echo htmlspecialchars($product['category_name']); ?></div>
                        <div class="name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
                            <div style="display: flex; align-items: center; gap: 4px; margin-top: 4px; min-height: 16px;">
                                <span style="color: #fbbf24; letter-spacing: 1px; font-size: 11px;">
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
                                <span style="color: var(--colors-muted); font-size: 10px;">
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
        </div>
    </div>
</section>

<?php if (!$selected_gender): ?>
    <!-- Women's Section -->
    <section style="padding: 80px 0; background: var(--colors-surface-soft);">
        <div class="container">
            <div class="section-header-studio">
                <h2>Women</h2>
                <a href="index.php?gender=Women" style="font-size: 14px; font-weight: 600; text-decoration: underline;">Explore Women</a>
            </div>
            <div class="product-grid-horizontal">
                <?php foreach ($women_products as $product): ?>
                    <div class="product-card-studio" onclick="window.location.href='product_detail.php?id=<?php echo $product['id']; ?>'">
                        <div class="image-wrapper"><img src="get_image.php?id=<?php echo $product['image_id']; ?>"></div>
                        <div class="meta">
                            <div class="cat"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <div class="name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Men's Section -->
    <section style="padding: 80px 0; background: #fff;">
        <div class="container">
            <div class="section-header-studio">
                <h2>Men</h2>
                <a href="index.php?gender=Men" style="font-size: 14px; font-weight: 600; text-decoration: underline;">Explore Men</a>
            </div>
            <div class="product-grid-horizontal">
                <?php foreach ($men_products as $product): ?>
                    <div class="product-card-studio" onclick="window.location.href='product_detail.php?id=<?php echo $product['id']; ?>'">
                        <div class="image-wrapper"><img src="<?php echo $product['image'] ?? 'assets/img/bag.png'; ?>"></div>
                        <div class="meta">
                            <div class="cat"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <div class="name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Kids' Section -->
    <section style="padding: 80px 0; background: var(--colors-canvas);">
        <div class="container">
            <div class="section-header-studio">
                <h2>Kids</h2>
                <a href="index.php?gender=Kids" style="font-size: 14px; font-weight: 600; text-decoration: underline;">Explore Kids</a>
            </div>
            <div class="product-grid-horizontal">
                <?php foreach ($kids_products as $product): ?>
                    <div class="product-card-studio" onclick="window.location.href='product_detail.php?id=<?php echo $product['id']; ?>'">
                        <div class="image-wrapper"><img src="get_image.php?id=<?php echo $product['image_id']; ?>"></div>
                        <div class="meta">
                            <div class="cat"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <div class="name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php else: ?>
    <!-- Gender Specific Category Sections -->
    <section style="padding: 80px 0; background: var(--colors-surface-soft);">
        <div class="container">
            <div class="section-header-studio">
                <h2>Tops</h2>
                <a href="products.php?gender=<?php echo $selected_gender; ?>&category=Tops" style="font-size: 14px; font-weight: 600; text-decoration: underline;">Shop Tops</a>
            </div>
            <div class="product-grid-horizontal">
                <?php foreach ($tops_products as $product): ?>
                    <div class="product-card-studio" onclick="window.location.href='product_detail.php?id=<?php echo $product['id']; ?>'">
                        <div class="image-wrapper"><img src="get_image.php?id=<?php echo $product['image_id']; ?>"></div>
                        <div class="meta">
                            <div class="cat"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <div class="name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section style="padding: 80px 0; background: #fff;">
        <div class="container">
            <div class="section-header-studio">
                <h2>Bottoms</h2>
                <a href="products.php?gender=<?php echo $selected_gender; ?>&category=Bottoms" style="font-size: 14px; font-weight: 600; text-decoration: underline;">Shop Bottoms</a>
            </div>
            <div class="product-grid-horizontal">
                <?php foreach ($bottoms_products as $product): ?>
                    <div class="product-card-studio" onclick="window.location.href='product_detail.php?id=<?php echo $product['id']; ?>'">
                        <div class="image-wrapper"><img src="get_image.php?id=<?php echo $product['image_id']; ?>"></div>
                        <div class="meta">
                            <div class="cat"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <div class="name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section style="padding: 80px 0; background: var(--colors-canvas);">
        <div class="container">
            <div class="section-header-studio">
                <h2>Accessories</h2>
                <a href="products.php?gender=<?php echo $selected_gender; ?>&category=Accessories" style="font-size: 14px; font-weight: 600; text-decoration: underline;">Shop Accessories</a>
            </div>
            <div class="product-grid-horizontal">
                <?php foreach ($acc_products as $product): ?>
                    <div class="product-card-studio" onclick="window.location.href='product_detail.php?id=<?php echo $product['id']; ?>'">
                        <div class="image-wrapper"><img src="<?php echo $product['image'] ?? 'assets/img/bag.png'; ?>"></div>
                        <div class="meta">
                            <div class="cat"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <div class="name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'buyer'): ?>
    <section class="promo-banner-studio">
        <div class="container" style="position: relative; z-index: 1;">
            <div style="font-size: 12px; letter-spacing: 0.4em; margin-bottom: 24px; color: var(--colors-primary); font-weight: 700;">MEMBERSHIP EXCLUSIVE</div>
            <h2>Earn points with every purchase.</h2>
            <p>Sign up now to get 15% off your first order and free shipping on all orders over RM100.</p>
            <a href="signup.php" class="button-primary" style="padding: 18px 56px; border-radius: 0; text-transform: uppercase; letter-spacing: 0.1em; font-size: 13px;">Join HypeThread Rewards</a>
        </div>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
