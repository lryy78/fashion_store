<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, $hashed_password, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, $user_id]);
    }
    $msg = "Profile updated successfully!";
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$include_path = '../includes/';
include $include_path . 'header.php';
require_once '../includes/sidebar.php';
?>

<div class="dashboard-layout">
    <?php renderSidebar($role); ?>

    <div class="dashboard-main fade-in-up">
        <header style="margin-bottom: var(--spacing-xl);">
            <h1 style="margin: 0; font-size: 32px; font-family: var(--typography-display-font);">Profile Settings</h1>
            <p style="color: var(--colors-muted);">Manage your account details and password.</p>
        </header>

        <div class="surface-card" style="padding: var(--spacing-xl); border-radius: var(--rounded-lg); max-width: 600px;">
            <?php if ($msg): ?>
                <div style="background: var(--colors-success); color: #fff; padding: 12px; border-radius: var(--rounded-sm); margin-bottom: var(--spacing-lg);">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
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
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                <div class="form-group" style="margin-top: var(--spacing-xl); border-top: 1px solid var(--colors-hairline); padding-top: var(--spacing-lg);">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current password">
                    <small style="color: var(--colors-muted); display: block; margin-top: 4px;">For security, use a strong password.</small>
                </div>
                <button type="submit" class="button-primary" style="width: 100%; margin-top: var(--spacing-xl);">Update Account</button>
            </form>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
