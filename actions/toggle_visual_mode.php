<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['exit'])) {
    unset($_SESSION['visual_mode']);
    header("Location: ../manager/dashboard.php");
} else {
    $_SESSION['visual_mode'] = true;
    header("Location: ../index.php");
}
exit();
?>
