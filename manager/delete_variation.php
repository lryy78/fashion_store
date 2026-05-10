<?php
session_start();
require_once '../config/db.php';

// Ensure the user is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit();
}

$variation_id = $_GET['id'] ?? null;
$product_id   = $_GET['product_id'] ?? null;

if ($variation_id && $product_id) {
    // Delete the variation safely using prepared statement
    $stmt = $pdo->prepare('DELETE FROM product_variations WHERE id = ? AND product_id = ?');
    $stmt->execute([$variation_id, $product_id]);
}

// Redirect back to the variations management page for the product
header('Location: manage_variations.php?id=' . urlencode($product_id));
exit();
?>
