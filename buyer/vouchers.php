<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's vouchers
$stmt = $pdo->prepare("SELECT * FROM vouchers WHERE (user_id = ? OR user_id IS NULL) AND is_used = 0 AND is_active = 1 AND (expiry_date >= CURDATE() OR expiry_date IS NULL) ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$vouchers = $stmt->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php renderSidebar('buyer'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="margin-bottom: var(--spacing-xxl);">
            <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Rewards & Incentives</div>
            <h1 style="margin: 0; font-size: 40px;">My Vouchers</h1>
        </header>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px;">
            <?php if ($vouchers): ?>
                <?php foreach ($vouchers as $voucher): ?>
                    <div class="surface-card" style="padding: 32px; border-radius: var(--rounded-lg); border-left: 4px solid var(--colors-primary); position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -10px; right: -10px; font-size: 80px; opacity: 0.05; pointer-events: none;">🎫</div>
                        
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--colors-muted); margin-bottom: 12px; font-weight: 600;">
                            <?php echo $voucher['discount_type'] == 'percentage' ? 'Percentage Discount' : 'Fixed Discount'; ?>
                        </div>
                        
                        <div style="font-size: 32px; font-weight: 700; margin-bottom: 20px; font-family: var(--typography-display-font);">
                            <?php echo $voucher['discount_type'] == 'percentage' ? number_format($voucher['discount_value'], 0) . '%' : '$' . number_format($voucher['discount_value'], 2); ?> OFF
                        </div>
                        
                        <div style="background: var(--colors-canvas); padding: 12px 16px; border-radius: 8px; border: 1px dashed var(--colors-hairline); display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <span style="font-family: var(--typography-code-font); font-weight: 700; font-size: 18px; letter-spacing: 1px;"><?php echo htmlspecialchars($voucher['code']); ?></span>
                            <button onclick="navigator.clipboard.writeText('<?php echo $voucher['code']; ?>'); alert('Copied!');" style="background: none; border: none; cursor: pointer; color: var(--colors-primary); font-size: 12px; font-weight: 600;">COPY</button>
                        </div>
                        
                        <div style="font-size: 13px; color: var(--colors-muted);">
                            <?php if ($voucher['expiry_date']): ?>
                                Valid until <?php echo date('M d, Y', strtotime($voucher['expiry_date'])); ?>
                            <?php else: ?>
                                No expiration date
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; padding: 100px 0; text-align: center; background: var(--colors-surface-soft); border-radius: var(--rounded-lg);">
                    <div style="font-size: 48px; margin-bottom: 24px; opacity: 0.2;">🎟️</div>
                    <p style="font-size: 18px; color: var(--colors-muted);">You don't have any active vouchers yet.</p>
                    <p style="font-size: 14px; color: var(--colors-muted); margin-top: 8px;">Keep an eye on your rewards for future purchases.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
