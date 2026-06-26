<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict wishlist changes to signed-in buyers.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'buyer') {
    header('Location: ../login.php?msg=' . urlencode('Please sign in as a buyer to use your wishlist.'));
    exit;
}

// Accept wishlist changes only from POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../products.php');
    exit;
}

// Validate the selected product and the page to return to.
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$returnTo = $_POST['return_to'] ?? '../products.php';

// Only allow local redirects inside this project.
if (!is_string($returnTo) || str_contains($returnTo, '://') || str_starts_with($returnTo, '//')) {
    $returnTo = '../products.php';
}

// Reject missing or invalid product IDs.
if (!$productId) {
    $_SESSION['wishlist_message'] = 'The selected product is invalid.';
    header('Location: ' . $returnTo);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Confirm the product is still visible and available to buyers.
$stmt = $pdo->prepare('SELECT id FROM products WHERE id = ? AND (status = "published" OR (status = "scheduled" AND publish_at <= NOW()))');
$stmt->execute([$productId]);
if (!$stmt->fetchColumn()) {
    $_SESSION['wishlist_message'] = 'That product is no longer available.';
    header('Location: ' . $returnTo);
    exit;
}

// Check whether the item is already saved by this buyer.
$stmt = $pdo->prepare('SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?');
$stmt->execute([$userId, $productId]);
$wishlistId = $stmt->fetchColumn();

// Toggle the wishlist record and store a one-time feedback message.
if ($wishlistId) {
    $pdo->prepare('DELETE FROM wishlists WHERE id = ? AND user_id = ?')->execute([$wishlistId, $userId]);
    $_SESSION['wishlist_message'] = 'Removed from your wishlist.';
} else {
    $pdo->prepare('INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)')->execute([$userId, $productId]);
    $_SESSION['wishlist_message'] = 'Saved to your wishlist.';
}

// Return the buyer to the page where the action started.
header('Location: ' . $returnTo);
exit;
