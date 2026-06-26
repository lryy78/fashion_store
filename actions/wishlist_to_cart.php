<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict this action to authenticated buyer accounts.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
    header('Location: ../login.php');
    exit;
}

// Accept move-to-cart requests only through POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../buyer/wishlist.php');
    exit;
}

// Validate the selected product and identify the current buyer.
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$userId = (int)$_SESSION['user_id'];

if (!$productId) {
    $_SESSION['wishlist_message'] = 'The selected product is invalid.';
    header('Location: ../buyer/wishlist.php');
    exit;
}

// Choose the available variation with the highest remaining stock.
$stmt = $pdo->prepare(
    'SELECT id
     FROM product_variations
     WHERE product_id = ? AND stock_quantity > 0
     ORDER BY stock_quantity DESC, id ASC
     LIMIT 1'
);
$stmt->execute([$productId]);
$variationId = $stmt->fetchColumn();

if (!$variationId) {
    $_SESSION['wishlist_message'] = 'This product is currently out of stock.';
    header('Location: ../buyer/wishlist.php');
    exit;
}

// Reuse an existing cart row when the same variation is already present.
$stmt = $pdo->prepare('SELECT id FROM cart WHERE user_id = ? AND variation_id = ?');
$stmt->execute([$userId, $variationId]);
$cartId = $stmt->fetchColumn();

if ($cartId) {
    $pdo->prepare('UPDATE cart SET quantity = quantity + 1 WHERE id = ?')->execute([$cartId]);
} else {
    $pdo->prepare('INSERT INTO cart (user_id, variation_id, quantity) VALUES (?, ?, 1)')->execute([$userId, $variationId]);
}

// Remove the item from the wishlist after it is added to the cart.
$pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')->execute([$userId, $productId]);
$_SESSION['wishlist_message'] = 'Moved to your cart using the first available size and colour.';
header('Location: ../buyer/cart.php');
exit;
