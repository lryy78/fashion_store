<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?msg=Please login to add items to cart.");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $variation_id = $_POST['variation_id'];
    $quantity = $_POST['quantity'];

    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND variation_id = ?");
    $stmt->execute([$user_id, $variation_id]);
    $item = $stmt->fetch();

    if ($item) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
        $stmt->execute([$quantity, $item['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, variation_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $variation_id, $quantity]);
    }

    header("Location: ../buyer/cart.php");
    exit();
}
?>
