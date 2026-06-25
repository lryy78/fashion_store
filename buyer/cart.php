<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price, pv.size, pv.color, pv.stock_quantity 
                        FROM cart c 
                        JOIN product_variations pv ON c.variation_id = pv.id 
                        JOIN products p ON pv.product_id = p.id 
                        WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$total = 0;
$has_stock_issue = false;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
    if ($item['stock_quantity'] < $item['quantity']) {
        $has_stock_issue = true;
    }
}

// Adjust include paths because we are in a subdirectory
$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="container" style="padding: var(--spacing-section) 0;">
    <h1 style="font-size: 36px; margin-bottom: var(--spacing-xl); font-family: var(--typography-display-font);">Your Shopping Bag</h1>

    <?php if ($cart_items): ?>
        <form method="POST" action="../actions/update_cart.php" id="cart-form">
        <div class="surface-card" style="padding: var(--spacing-xl); border-radius: var(--rounded-lg); margin-bottom: var(--spacing-xl);">
            <table class="data-table data-table-light">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Variation</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td style="font-weight: 500;">
                                <?php echo htmlspecialchars($item['name']); ?>
                                <?php if ($item['stock_quantity'] <= 0): ?>
                                    <div style="color: var(--colors-error); font-size: 11px; font-weight: 400;">Out of Stock</div>
                                <?php elseif ($item['stock_quantity'] < $item['quantity']): ?>
                                    <div style="color: var(--colors-error); font-size: 11px; font-weight: 400;">Only <?php echo $item['stock_quantity']; ?> left</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['size']); ?> / <?php echo htmlspecialchars($item['color']); ?></td>
                            <td>RM <?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <div style="display: inline-flex; align-items: center; gap: 0; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md); overflow: hidden;">
                                    <button type="button" onclick="adjustQty(this, -1, <?php echo $item['stock_quantity']; ?>)" style="width: 32px; height: 32px; border: none; background: var(--colors-surface-soft); cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;">−</button>
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" style="width: 50px; padding: 8px; border: none; border-left: 1px solid var(--colors-hairline); border-right: 1px solid var(--colors-hairline); text-align: center; font-size: 14px;" onchange="validateQuantity(this, <?php echo $item['stock_quantity']; ?>)" required>
                                    <button type="button" onclick="adjustQty(this, 1, <?php echo $item['stock_quantity']; ?>)" style="width: 32px; height: 32px; border: none; background: var(--colors-surface-soft); cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;">+</button>
                                </div>
                            </td>
                            <td>RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td>
                                <a href="../actions/remove_from_cart.php?id=<?php echo $item['id']; ?>" style="color: var(--colors-error); font-size: 13px;">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: var(--spacing-lg);">
            <div class="surface-card" style="padding: var(--spacing-xl); border-radius: var(--rounded-lg); min-width: 300px;">
                <h3 style="margin-bottom: var(--spacing-md); display: flex; justify-content: space-between;">
                    <span>Total</span>
                    <span>RM <?php echo number_format($total, 2); ?></span>
                </h3>
                <?php if ($has_stock_issue): ?>
                    <button type="button" class="button-primary" style="width: 100%; opacity: 0.5; cursor: not-allowed;" disabled>Stock Issues in Bag</button>
                    <p style="font-size: 12px; color: var(--colors-error); margin-top: 12px; text-align: center;">Please remove or adjust items that are out of stock.</p>
                <?php else: ?>
                    <a href="checkout.php" class="button-primary" style="width: 100%; text-align: center; margin-bottom: 12px;">Proceed to Checkout</a>
                <?php endif; ?>
                <a href="../products.php" class="button-secondary" style="width: 100%; text-align: center; margin-top: 12px;">Continue Shopping</a>
            </div>
        </div>
        </form>
    <?php else: ?>
        <div class="surface-card" style="padding: var(--spacing-xxl); border-radius: var(--rounded-lg); text-align: center;">
            <p style="font-size: 18px; color: var(--colors-muted); margin-bottom: var(--spacing-lg);">Your bag is currently empty.</p>
            <a href="../products.php" class="button-secondary">Discover Pieces</a>
        </div>
    <?php endif; ?>
</div>

<script>
function adjustQty(btn, delta, maxStock) {
    const input = btn.parentElement.querySelector('input[type="number"]');
    let newVal = parseInt(input.value) + delta;
    if (newVal > maxStock) {
        newVal = maxStock;
        alert('Maximum available stock is ' + maxStock);
    }
    if (newVal < 1) newVal = 1;
    input.value = newVal;
    document.getElementById('cart-form').submit();
}

function validateQuantity(input, maxStock) {
    if (input.value > maxStock) {
        input.value = maxStock;
        alert('Maximum available stock is ' + maxStock);
    }
    if (input.value < 1) {
        input.value = 1;
    }
    document.getElementById('cart-form').submit();
}
</script>

<?php include $include_path . 'footer.php'; ?>
