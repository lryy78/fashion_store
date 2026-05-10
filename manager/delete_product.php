<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}

header("Location: products_list.php?msg=Product deleted");
exit();
?>
