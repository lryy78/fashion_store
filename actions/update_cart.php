 <?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quantity'])) {
    $user_id = $_SESSION['user_id'];
    
    foreach ($_POST['quantity'] as $cart_id => $new_quantity) {
        $cart_id = (int)$cart_id;
        $new_quantity = (int)$new_quantity;
        
        if ($new_quantity < 1) {
            $new_quantity = 1;
        }
        
        // Verify cart item belongs to user and get stock info
        $stmt = $pdo->prepare("SELECT c.id, pv.stock_quantity 
                               FROM cart c 
                               JOIN product_variations pv ON c.variation_id = pv.id 
                               WHERE c.id = ? AND c.user_id = ?");
        $stmt->execute([$cart_id, $user_id]);
        $cart_item = $stmt->fetch();
        
        if ($cart_item) {
            // Enforce stock limit
            if ($new_quantity > $cart_item['stock_quantity']) {
                $new_quantity = $cart_item['stock_quantity'];
            }
            
            // Update quantity
            if ($new_quantity > 0) {
                $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $update->execute([$new_quantity, $cart_id]);
            } else {
                // Remove item if quantity is 0
                $delete = $pdo->prepare("DELETE FROM cart WHERE id = ?");
                $delete->execute([$cart_id]);
            }
        }
    }
}

$redirect = $_POST['redirect_to'] ?? 'cart';
if ($redirect === 'checkout') {
    header("Location: ../buyer/checkout.php");
} else {
    header("Location: ../buyer/cart.php");
}
exit();
?>