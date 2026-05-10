<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$success_msg = null;
$error_msg = null;

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_voucher'])) {
    $user_id = $_POST['user_id'];
    $voucher_id = $_POST['voucher_id'];
    
    // Check if the voucher is already assigned or used
    $check = $pdo->prepare("SELECT * FROM vouchers WHERE id = ?");
    $check->execute([$voucher_id]);
    $voucher = $check->fetch();
    
    if ($voucher) {
        // We "clone" the voucher for the user to keep the global template or just assign it?
        // Usually, templates are better, but here we'll just assign it if user_id is NULL.
        // If it's already assigned, we'll create a new one with same properties for this user.
        
        $stmt = $pdo->prepare("INSERT INTO vouchers (code, discount_type, discount_value, expiry_date, is_active, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $new_code = $voucher['code'] . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
        $stmt->execute([
            $new_code,
            $voucher['discount_type'],
            $voucher['discount_value'],
            $voucher['expiry_date'],
            1,
            $user_id
        ]);
        
        $success_msg = "Voucher assigned successfully to the user.";
    } else {
        $error_msg = "Invalid voucher selected.";
    }
}

// Fetch all buyers
$stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE role = 'buyer' ORDER BY full_name ASC");
$stmt->execute();
$buyers = $stmt->fetchAll();

// Fetch available global vouchers (templates)
$stmt = $pdo->prepare("SELECT * FROM vouchers WHERE user_id IS NULL AND is_active = 1 AND (expiry_date >= CURDATE() OR expiry_date IS NULL)");
$stmt->execute();
$global_vouchers = $stmt->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php renderSidebar('admin'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="margin-bottom: var(--spacing-xxl);">
            <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Marketing Tools</div>
            <h1 style="margin: 0; font-size: 40px;">Assign Vouchers</h1>
        </header>

        <?php if ($success_msg): ?>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; padding: 16px; border-radius: var(--rounded-md); margin-bottom: 24px; font-weight: 500;">
                ✓ <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 16px; border-radius: var(--rounded-md); margin-bottom: 24px; font-weight: 500;">
                ✕ <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="surface-card" style="padding: 40px; border-radius: var(--rounded-lg);">
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 12px; color: var(--colors-muted);">Select Buyer</label>
                        <select name="user_id" required style="width: 100%; padding: 14px; border: 1px solid var(--colors-hairline); border-radius: 8px; font-family: inherit;">
                            <option value="">Choose a customer...</option>
                            <?php foreach ($buyers as $buyer): ?>
                                <option value="<?php echo $buyer['id']; ?>"><?php echo htmlspecialchars($buyer['full_name']); ?> (<?php echo htmlspecialchars($buyer['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 12px; color: var(--colors-muted);">Select Voucher Template</label>
                        <select name="voucher_id" required style="width: 100%; padding: 14px; border: 1px solid var(--colors-hairline); border-radius: 8px; font-family: inherit;">
                            <option value="">Choose a voucher...</option>
                            <?php foreach ($global_vouchers as $v): ?>
                                <option value="<?php echo $v['id']; ?>">
                                    <?php echo htmlspecialchars($v['code']); ?> - 
                                    <?php echo $v['discount_type'] == 'percentage' ? $v['discount_value'] . '%' : '$' . $v['discount_value']; ?> 
                                    (Exp: <?php echo $v['expiry_date'] ?: 'Never'; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" name="assign_voucher" class="button-primary" style="padding: 14px 40px;">Assign Voucher to Buyer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
