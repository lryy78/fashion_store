<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, $address, $hashed_password, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, $address, $user_id]);
    }
    $msg = "Profile updated successfully!";
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<?php require_once '../includes/sidebar.php'; ?>
<div class="dashboard-layout">
    <?php renderSidebar('buyer'); ?>

    <div class="dashboard-main">
        <header style="margin-bottom: var(--spacing-xl);">
            <h1 style="margin: 0; font-size: 32px; font-family: var(--typography-display-font);">Profile Settings</h1>
        </header>

        <div class="surface-card" style="padding: var(--spacing-xl); border-radius: var(--rounded-lg); max-width: 600px;">
            <?php if ($msg): ?>
                <p style="color: var(--colors-success); margin-bottom: var(--spacing-md);"><?php echo htmlspecialchars($msg); ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Default Shipping Address</label>
                    <textarea name="address" rows="4"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-group" style="margin-top: var(--spacing-xl); border-top: 1px solid var(--colors-hairline); padding-top: var(--spacing-lg);">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current password">
                </div>
                <button type="submit" class="button-primary" style="width: 100%; margin-top: var(--spacing-md);">Update Profile</button>
            </form>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
