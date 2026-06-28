<?php
session_start();
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';

$product_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id 
                      WHERE p.id = ? AND (p.status = 'published' OR (p.status = 'scheduled' AND p.publish_at <= NOW()))");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div style='text-align: center; padding: 15rem 0;'><h2 style='font-weight: 400; font-family: var(--typography-display-font);'>Piece not found</h2><a href='products.php' class='button-secondary' style='margin-top: 2rem; display: inline-block;'>Return to Collection</a></div>";
    include __DIR__ . '/../includes/footer.php';
    exit();
}

// Increment view count
$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$product_id]);

$stmt = $pdo->prepare("SELECT * FROM product_variations WHERE product_id = ?");
$stmt->execute([$product_id]);
$variations = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT id FROM product_images WHERE product_id = ?");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();
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

            <h1 class="product-title-studio">
                <?php echo htmlspecialchars($product['name']); ?>
                <?php 
                $total_stock = array_sum(array_column($variations, 'stock_quantity'));
                if ($total_stock <= 0): 
                ?>
                    <span style="font-size: 14px; background: var(--colors-error); color: #fff; padding: 4px 12px; border-radius: 100px; vertical-align: middle; margin-left: 12px; font-family: var(--typography-body-font);">OUT OF STOCK</span>
                <?php endif; ?>
            </h1>
            <!-- Average Rating -->
            <?php
            $avg_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?");
            $avg_stmt->execute([$product_id]);
            $rating_data = $avg_stmt->fetch();
            $avg_rating = round($rating_data['avg_rating'], 1);
            $review_count = $rating_data['review_count'];
            ?>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                <span style="color: #fbbf24; letter-spacing: 2px; font-size: 16px;">
                    <?php
                    if ($review_count > 0) {
                        $full = floor($avg_rating);
                        $half = ($avg_rating - $full) >= 0.5 ? 1 : 0;
                        $empty = 5 - $full - $half;
                        echo str_repeat('★', $full);
                        // if ($half) echo '½';
                        if ($half) echo '<span style="position:relative;display:inline-block;"><span style="position:absolute;overflow:hidden;width:45%;">★</span>☆</span>';
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

            <span class="product-price-studio">RM <?php echo number_format($product['price'], 2); ?></span>

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
                    <input type="number" name="quantity" value="1" min="1" max="10" style="width: 80px; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
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

            <div style="margin-top: 48px; border-top: 1px solid var(--colors-hairline); pt: 24px;">
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
</div>

<!-- Size Chart Modal -->
<div class="size-chart-modal" id="size-chart-modal">
    <div class="size-chart-content" style="max-width: 800px;">
        <span class="close-modal" onclick="toggleSizeChart(false)">&times;</span>
        <h2 style="margin-bottom: 24px; font-family: var(--typography-display-font);">Sizing Architecture</h2>
        <div style="font-size: 14px; line-height: 1.8; white-space: pre-wrap; font-family: var(--typography-body-font); color: var(--colors-ink);">
            <?php echo htmlspecialchars(str_replace('\n', "\n", $product['size_chart'])); ?>
        </div>
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
    
    // If size is selected, disable colors that don't have this size
    if (selectedSize) {
        colorButtons.forEach(btn => {
            const color = btn.getAttribute('data-color');
            const hasSize = variations.some(v => v.size === selectedSize && v.color === color && v.stock_quantity > 0);
            
            if (!hasSize) {
                btn.classList.add('disabled');
                btn.disabled = true;
            } else {
                btn.classList.remove('disabled');
                btn.disabled = false;
            }
        });
    } else {
        // Enable all colors if no size selected
        colorButtons.forEach(btn => {
            btn.classList.remove('disabled');
            btn.disabled = false;
        });
    }
    
    // If color is selected, disable sizes that don't have this color
    if (selectedColor) {
        sizeButtons.forEach(btn => {
            const size = btn.getAttribute('data-size');
            const hasColor = variations.some(v => v.size === size && v.color === selectedColor && v.stock_quantity > 0);
            
            if (!hasColor) {
                btn.classList.add('disabled');
                btn.disabled = true;
            } else {
                btn.classList.remove('disabled');
                btn.disabled = false;
            }
        });
    } else {
        // Enable all sizes if no color selected
        sizeButtons.forEach(btn => {
            btn.classList.remove('disabled');
            btn.disabled = false;
        });
    }
}

function updateVariations() {
    const msg = document.getElementById('variation-msg');
    const btn = document.getElementById('add-to-cart-btn');
    const varInput = document.getElementById('selected-variation-id');
    
    if (selectedSize && selectedColor) {
        const found = variations.find(v => v.size === selectedSize && v.color === selectedColor);
        if (found) {
            if (found.stock_quantity > 0) {
                varInput.value = found.id;
                btn.disabled = false;
                msg.innerText = "";
            } else {
                varInput.value = "";
                btn.disabled = true;
                msg.innerText = "This combination is currently out of stock.";
            }
        } else {
            varInput.value = "";
            btn.disabled = true;
            msg.innerText = "This combination is not available.";
        }
    } else {
        btn.disabled = true;
        msg.innerText = "";
    }
}

function toggleSizeChart(show) {
    document.getElementById('size-chart-modal').style.display = show ? 'flex' : 'none';
}
</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>
