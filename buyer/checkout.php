<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's saved address
$stmt = $pdo->prepare("SELECT full_name, address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();
$saved_address = $user_data['address'] ?? '';
$saved_full_name = $user_data['full_name'] ?? '';

// Parse saved address into components
// Supports both formats: "full_name, street, city, postcode, country" (old) and "street, city, postcode, country" (new)
$address_parts = [];
if ($saved_address) {
    $address_parts = explode(', ', $saved_address);
}

// Use saved full_name from profile, not from address parsing
$default_full_name = $saved_full_name;

// Determine if address includes full_name (5 parts) or not (4 parts)
if (count($address_parts) >= 5) {
    // Old format: "full_name, street, city, postcode, country"
    $default_address_line = $address_parts[1] ?? '';
    $default_city = $address_parts[2] ?? '';
    $default_postcode = $address_parts[3] ?? '';
    $default_country = $address_parts[4] ?? '';
} else {
    // New format: "street, city, postcode, country"
    $default_address_line = $address_parts[0] ?? '';
    $default_city = $address_parts[1] ?? '';
    $default_postcode = $address_parts[2] ?? '';
    $default_country = $address_parts[3] ?? '';
}

// Fetch cart items with product + variation details
$stmt = $pdo->prepare("
    SELECT c.id as cart_id, c.quantity, c.variation_id,
           p.name, p.price, 
           (SELECT id FROM product_images WHERE product_id = p.id LIMIT 1) as image_id,
           pv.size, pv.color, pv.stock_quantity
    FROM cart c
    JOIN product_variations pv ON c.variation_id = pv.id
    JOIN products p ON pv.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

if (!$cart_items) {
    header("Location: cart.php");
    exit();
}

// Stock validation
foreach ($cart_items as $item) {
    if ($item['stock_quantity'] < $item['quantity']) {
        header("Location: cart.php?error=out_of_stock");
        exit();
    }
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Voucher handling
$vouchers = $pdo->prepare("SELECT * FROM vouchers WHERE (user_id = ? OR user_id IS NULL) AND is_used = 0 AND is_active = 1 AND (expiry_date >= CURDATE() OR expiry_date IS NULL)");
$vouchers->execute([$user_id]);
$available_vouchers = $vouchers->fetchAll();

$selected_voucher_id = $_POST['voucher_id'] ?? null;
$discount = 0;
$voucher_code = '';

if ($selected_voucher_id) {
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE id = ? AND (user_id = ? OR user_id IS NULL) AND is_used = 0 AND is_active = 1");
    $stmt->execute([$selected_voucher_id, $user_id]);
    $v = $stmt->fetch();
    if ($v) {
        if ($v['discount_type'] === 'percentage') {
            $discount = $subtotal * ($v['discount_value'] / 100);
        } else {
            $discount = min($v['discount_value'], $subtotal);
        }
        $voucher_code = $v['code'];
    }
}

$shipping = $subtotal >= 100 ? 0 : 9.99;
$total = max(0, $subtotal - $discount) + $shipping;

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $full_name    = trim($_POST['full_name'] ?? '');
    $address_line = trim($_POST['address_line'] ?? '');
    $city         = trim($_POST['city'] ?? '');
    $postcode     = trim($_POST['postcode'] ?? '');
    $country      = trim($_POST['country'] ?? '');
    $payment      = $_POST['payment_method'] ?? '';

    if (!$full_name)    $errors[] = 'Full name is required.';
    if (!$address_line) $errors[] = 'Address is required.';
    if (!$city)         $errors[] = 'City is required.';
    if (!$postcode)     $errors[] = 'Postcode is required.';
    if (!$country)      $errors[] = 'Country is required.';
    if (!$payment)      $errors[] = 'Please select a payment method.';

    if (empty($errors)) {
        $full_address = "$full_name, $address_line, $city, $postcode, $country";

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, address, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $total, $full_address]);
            $order_id = $pdo->lastInsertId();

            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, variation_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['variation_id'], $item['quantity'], $item['price']]);

                $stmt = $pdo->prepare("UPDATE product_variations SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['variation_id']]);
            }

            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            // Save address as default if user doesn't have one yet
            if (empty($saved_address)) {
                // Save address WITHOUT full_name (it's stored separately in users.full_name)
                $address_only = "$address_line, $city, $postcode, $country";
                $update_addr = $pdo->prepare("UPDATE users SET full_name = ?, address = ? WHERE id = ?");
                $update_addr->execute([$full_name, $address_only, $user_id]);
            }

            if ($selected_voucher_id) {
                $stmt = $pdo->prepare("UPDATE vouchers SET is_used = 1 WHERE id = ?");
                $stmt->execute([$selected_voucher_id]);
            }

            $pdo->commit();
            header("Location: order_success.php?id=" . $order_id);
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Checkout failed. Please try again.";
        }
    }
}

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<style>
.checkout-wrapper {
    max-width: 1100px;
    margin: 0 auto;
    padding: 48px 24px 80px;
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 40px;
    align-items: start;
}

.checkout-left h1 {
    font-family: var(--typography-display-font);
    font-size: 36px;
    margin-bottom: 32px;
    color: var(--colors-ink);
    letter-spacing: -0.5px;
}

.checkout-section {
    background: var(--colors-surface, #fff);
    border: 1px solid var(--colors-border, #e8e4dc);
    border-radius: 12px;
    padding: 28px;
    margin-bottom: 24px;
}

.checkout-section-title {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--colors-muted, #888);
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--colors-border, #e8e4dc);
}

.checkout-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.checkout-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.checkout-field label {
    font-size: 12px;
    font-weight: 600;
    color: var(--colors-muted, #888);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.checkout-field input,
.checkout-field select {
    padding: 12px 14px;
    border: 1px solid var(--colors-border, #e8e4dc);
    border-radius: 8px;
    font-size: 14px;
    font-family: var(--typography-body-font);
    background: var(--colors-canvas, #faf9f5);
    color: var(--colors-ink, #1a1a1a);
    outline: none;
    transition: border-color 0.2s;
}

.checkout-field input:focus,
.checkout-field select:focus {
    border-color: var(--colors-primary, #d4a574);
    box-shadow: 0 0 0 3px rgba(212,165,116,0.12);
}

/* Payment method cards */
.payment-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.payment-option {
    position: relative;
}

.payment-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
}

.payment-option label {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 2px solid var(--colors-border, #e8e4dc);
    border-radius: 10px;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    font-size: 14px;
    font-weight: 500;
    color: var(--colors-ink, #1a1a1a);
}

.payment-option input[type="radio"]:checked + label {
    border-color: var(--colors-primary, #d4a574);
    background: rgba(212,165,116,0.06);
}

.payment-option label:hover {
    border-color: var(--colors-primary, #d4a574);
}

.payment-icon {
    width: 36px;
    height: 24px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    background: #f0ede6;
}

.payment-radio-circle {
    width: 18px;
    height: 18px;
    border: 2px solid var(--colors-border, #e8e4dc);
    border-radius: 50%;
    margin-left: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: border-color 0.2s;
}

.payment-option input[type="radio"]:checked + label .payment-radio-circle {
    border-color: var(--colors-primary, #d4a574);
    background: var(--colors-primary, #d4a574);
}

/* Order summary panel */
.order-summary-panel {
    background: var(--colors-surface, #fff);
    border: 1px solid var(--colors-border, #e8e4dc);
    border-radius: 12px;
    padding: 28px;
    position: sticky;
    top: 100px;
}

.order-summary-title {
    font-family: var(--typography-display-font);
    font-size: 20px;
    color: var(--colors-ink);
    margin-bottom: 20px;
}

.order-item-row {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid var(--colors-border, #e8e4dc);
}

.order-item-row:last-child {
    border-bottom: none;
}

.order-item-img {
    width: 60px;
    height: 72px;
    object-fit: cover;
    border-radius: 6px;
    background: #f0ede6;
    flex-shrink: 0;
}

.order-item-img-placeholder {
    width: 60px;
    height: 72px;
    border-radius: 6px;
    background: linear-gradient(135deg, #f0ede6, #e0dbd2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.order-item-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--colors-ink);
    line-height: 1.4;
    margin-bottom: 3px;
}

.order-item-meta {
    font-size: 11px;
    color: var(--colors-muted, #888);
}

.order-item-qty {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: var(--colors-muted, #888);
    color: #fff;
    border-radius: 50%;
    font-size: 10px;
    font-weight: 700;
    margin-left: 4px;
}

.order-item-price {
    margin-left: auto;
    font-size: 13px;
    font-weight: 600;
    color: var(--colors-ink);
    flex-shrink: 0;
}

.order-totals {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--colors-border, #e8e4dc);
}

.order-total-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: var(--colors-muted, #888);
    margin-bottom: 10px;
}

.order-total-row.grand-total {
    font-size: 16px;
    font-weight: 700;
    color: var(--colors-ink);
    padding-top: 12px;
    border-top: 1px solid var(--colors-border, #e8e4dc);
    margin-top: 4px;
}

.shipping-badge {
    display: inline-block;
    background: #d4edda;
    color: #155724;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    text-transform: uppercase;
}

.btn-place-order {
    width: 100%;
    padding: 16px;
    background: var(--colors-ink, #1a1a1a);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.05em;
    cursor: pointer;
    margin-top: 20px;
    transition: background 0.2s, transform 0.15s;
    font-family: var(--typography-body-font);
    text-transform: uppercase;
}

.btn-place-order:hover {
    background: var(--colors-primary, #d4a574);
    transform: translateY(-1px);
}

.error-box {
    background: #fff0f0;
    border: 1px solid #ffcccc;
    border-radius: 8px;
    padding: 14px 18px;
    margin-bottom: 24px;
    font-size: 13px;
    color: #c0392b;
}

.error-box ul {
    margin: 6px 0 0 18px;
    padding: 0;
}

@media (max-width: 768px) {
    .checkout-wrapper {
        grid-template-columns: 1fr;
        padding: 24px 16px 60px;
    }
    .checkout-form-row {
        grid-template-columns: 1fr;
    }
    .order-summary-panel {
        position: static;
    }
}
</style>

<div class="checkout-wrapper">
    <!-- LEFT: FORM -->
    <div class="checkout-left">
        <h1>Checkout</h1>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong>Please fix the following:</strong>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="checkout-form">

            <!-- SHIPPING ADDRESS -->
            <div class="checkout-section">
                <div class="checkout-section-title">📦 Shipping Address</div>

                <div class="checkout-field">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name"
                           placeholder="Full name"
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? $default_full_name); ?>" required>
                </div>

                <div class="checkout-field">
                    <label for="address_line">Street Address</label>
                    <input type="text" id="address_line" name="address_line"
                           placeholder="Street address"
                           value="<?php echo htmlspecialchars($_POST['address_line'] ?? $default_address_line); ?>" required>
                </div>

                <div class="checkout-form-row">
                    <div class="checkout-field" style="margin-bottom:0;">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city"
                               placeholder="City"
                               value="<?php echo htmlspecialchars($_POST['city'] ?? $default_city); ?>" required>
                    </div>
                    <div class="checkout-field" style="margin-bottom:0;">
                        <label for="postcode">Postcode</label>
                        <input type="text" id="postcode" name="postcode"
                               placeholder="Postcode"
                               value="<?php echo htmlspecialchars($_POST['postcode'] ?? $default_postcode); ?>" required>
                    </div>
                </div>

                <div class="checkout-field" style="margin-top:16px; margin-bottom:0;">
                    <label for="country">Country</label>
                    <select id="country" name="country" required>
                        <option value="">Select country…</option>
                        <?php
                        $countries = ['Malaysia','Singapore','Indonesia','Thailand','Philippines','Vietnam','United States','United Kingdom','Australia'];
                        $selectedCountry = $_POST['country'] ?? $default_country;
                        foreach ($countries as $c) {
                            $sel = ($c === $selectedCountry) ? 'selected' : '';
                            echo "<option value=\"$c\" $sel>$c</option>";
                        }
                        ?>
                    </select>
                </div>

                <?php if (empty($saved_address)) : ?>
                <div style="margin-top: 12px; padding: 12px; background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; font-size: 12px; color: #92400e;">
                    ℹ️ This address will be saved as your default for future checkouts.
                </div>
                <?php endif; ?>
            </div>

            <!-- PAYMENT METHOD -->
            <div class="checkout-section">
                <div class="checkout-section-title">💳 Payment Method</div>

                <div class="payment-options">
                    <?php
                    $payments = [
                        ['value'=>'credit_card',  'icon'=>'💳', 'label'=>'Credit / Debit Card',   'sub'=>'Visa, Mastercard, AMEX'],
                        ['value'=>'online_banking','icon'=>'🏦', 'label'=>'Online Banking (FPX)',  'sub'=>'All Malaysian banks'],
                        ['value'=>'ewallet',       'icon'=>'📱', 'label'=>'e-Wallet',              'sub'=>'Touch n Go, GrabPay, Boost'],
                    ];
                    $selectedPayment = $_POST['payment_method'] ?? 'credit_card';
                    foreach ($payments as $pm):
                        $checked = ($pm['value'] === $selectedPayment) ? 'checked' : '';
                    ?>
                        <div class="payment-option">
                            <input type="radio" id="pm_<?php echo $pm['value']; ?>"
                                   name="payment_method" value="<?php echo $pm['value']; ?>"
                                   <?php echo $checked; ?>>
                            <label for="pm_<?php echo $pm['value']; ?>">
                                <span class="payment-icon"><?php echo $pm['icon']; ?></span>
                                <span>
                                    <span style="display:block;"><?php echo $pm['label']; ?></span>
                                    <span style="font-size:11px; color:var(--colors-muted,#888); font-weight:400;"><?php echo $pm['sub']; ?></span>
                                </span>
                                <span class="payment-radio-circle"></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </form>
    </div>

    <!-- RIGHT: ORDER SUMMARY -->
    <div class="order-summary-panel">
        <div class="order-summary-title">Order Summary</div>

        <!-- Items -->
        <form method="POST" action="../actions/update_cart.php" id="checkout-quantity-form">
        <input type="hidden" name="redirect_to" value="checkout">
        <?php foreach ($cart_items as $item): ?>
            <div class="order-item-row">
                <?php if (!empty($item['image_id'])): ?>
                    <img src="/fashion_store/get_image.php?id=<?php echo $item['image_id']; ?>"
                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                         class="order-item-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="order-item-img-placeholder" style="display:none;">👗</div>
                <?php else: ?>
                    <div class="order-item-img-placeholder">👗</div>
                <?php endif; ?>

                <div style="flex:1; min-width:0;">
                    <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="order-item-meta">
                        Size: <?php echo htmlspecialchars($item['size']); ?> &bull;
                        <?php echo htmlspecialchars($item['color']); ?>
                    </div>
                    <div class="order-item-meta" style="margin-top:2px;">
                        <div style="display: inline-flex; align-items: center; gap: 0; border: 1px solid var(--colors-border, #e8e4dc); border-radius: 4px; overflow: hidden;">
                            <button type="button" onclick="adjustCheckoutQty(this, -1, <?php echo $item['stock_quantity']; ?>)" style="width: 24px; height: 24px; border: none; background: var(--colors-surface-soft); cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; line-height: 1;">−</button>
                            <input type="number" name="quantity[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" style="width: 44px; padding: 4px; border: none; border-left: 1px solid var(--colors-border, #e8e4dc); border-right: 1px solid var(--colors-border, #e8e4dc); text-align: center; font-size: 12px;" onchange="validateCheckoutQty(this, <?php echo $item['stock_quantity']; ?>)" required>
                            <button type="button" onclick="adjustCheckoutQty(this, 1, <?php echo $item['stock_quantity']; ?>)" style="width: 24px; height: 24px; border: none; background: var(--colors-surface-soft); cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; line-height: 1;">+</button>
                        </div>
                    </div>
                </div>

                <div class="order-item-price">
                    RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                </div>
            </div>
        <?php endforeach; ?>
        </form>
        <!-- Vouchers -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--colors-border, #e8e4dc);">
            <div style="font-size: 12px; font-weight: 600; color: var(--colors-muted, #888); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px;">Apply Voucher</div>
            <select name="voucher_id" form="checkout-form" onchange="this.form.submit()" style="width: 100%; padding: 12px; border: 1px solid var(--colors-border, #e8e4dc); border-radius: 8px; font-size: 13px; background: var(--colors-canvas, #faf9f5);">
                <option value="">No voucher selected</option>
                <?php foreach ($available_vouchers as $v): ?>
                    <option value="<?php echo $v['id']; ?>" <?php echo $selected_voucher_id == $v['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($v['code']); ?> (<?php echo $v['discount_type'] == 'percentage' ? number_format($v['discount_value'], 0) . '%' : '$' . $v['discount_value']; ?> OFF)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($discount > 0): ?>
                <div style="margin-top: 8px; font-size: 11px; color: #15803d; font-weight: 600;">
                    ✓ Voucher applied: -RM <?php echo number_format($discount, 2); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Totals -->
        <div class="order-totals">
            <div class="order-total-row">
                <span>Subtotal</span>
                <span>RM <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php if ($discount > 0): ?>
                <div class="order-total-row" style="color: #15803d;">
                    <span>Discount</span>
                    <span>-RM <?php echo number_format($discount, 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="order-total-row">
                <span>Shipping</span>
                <?php if ($shipping == 0): ?>
                    <span><span class="shipping-badge">Free</span></span>
                <?php else: ?>
                    <span>RM <?php echo number_format($shipping, 2); ?></span>
                <?php endif; ?>
            </div>
            <div class="order-total-row grand-total">
                <span>Total</span>
                <span>RM <?php echo number_format($total, 2); ?></span>
            </div>
        </div>

        <?php if ($shipping == 0): ?>
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:10px 14px; font-size:12px; color:#15803d; margin-top:12px;">
                🎉 You qualify for <strong>free shipping</strong>!
            </div>
        <?php else: ?>
            <div style="background:#fefce8; border:1px solid #fde68a; border-radius:8px; padding:10px 14px; font-size:12px; color:#92400e; margin-top:12px;">
                💡 Spend RM <?php echo number_format(100 - $subtotal, 2); ?> more for <strong>free shipping</strong>
            </div>
        <?php endif; ?>

        <button type="submit" name="place_order" form="checkout-form" class="btn-place-order">
            Place Order → RM <?php echo number_format($total, 2); ?>
        </button>

        <div style="margin-top:14px; text-align:center;">
            <a href="cart.php" style="font-size:12px; color:var(--colors-muted,#888); text-decoration:underline;">← Return to Bag</a>
        </div>

        <div style="margin-top:16px; display:flex; align-items:center; justify-content:center; gap:8px; font-size:11px; color:var(--colors-muted,#888);">
            🔒 Secure checkout &bull; SSL encrypted
        </div>
    </div>
</div>

<script>
function adjustCheckoutQty(btn, delta, maxStock) {
    const input = btn.parentElement.querySelector('input[type="number"]');
    let newVal = parseInt(input.value) + delta;
    if (newVal > maxStock) {
        newVal = maxStock;
        alert('Maximum available stock is ' + maxStock);
    }
    if (newVal < 1) newVal = 1;
    input.value = newVal;
    document.getElementById('checkout-quantity-form').submit();
}

function validateCheckoutQty(input, maxStock) {
    if (input.value > maxStock) {
        input.value = maxStock;
        alert('Maximum available stock is ' + maxStock);
    }
    if (input.value < 1) {
        input.value = 1;
    }
    document.getElementById('checkout-quantity-form').submit();
}
</script>

<?php include $include_path . 'footer.php'; ?>
