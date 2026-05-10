<?php
session_start();
require_once 'config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Check if account is active
        if (isset($user['is_active']) && $user['is_active'] == 0) {
            $error = "Your account has been deactivated. Please contact support.";
        } else {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Redirect based on role
            if ($user['role'] == 'buyer')   header("Location: index.php");
            elseif ($user['role'] == 'manager') header("Location: manager/dashboard.php");
            elseif ($user['role'] == 'admin')   header("Location: admin/dashboard.php");
            elseif ($user['role'] == 'owner')   header("Location: owner/dashboard.php");
            exit();
        }
    } else {
        $error = "Invalid username or password.";
    }
}

include 'includes/header.php';
?>

<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; background-color: var(--colors-canvas);">
    <div class="auth-container fade-in-up" style="max-width: 440px; width: 100%; padding: var(--spacing-xxl); background: #fff; border: 1px solid var(--colors-hairline-soft); box-shadow: var(--shadow-lg);">
        <div style="text-align: center; margin-bottom: var(--spacing-xl);">
            <div style="font-family: var(--typography-display-font); font-size: 32px; letter-spacing: -0.02em; margin-bottom: 8px;">HypeThread</div>
            <p style="color: var(--colors-muted); font-size: 14px;">Sign in to your account</p>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div style="background: #f0fdf4; color: #166534; padding: 12px; border-radius: var(--rounded-md); font-size: 13px; margin-bottom: var(--spacing-lg); text-align: center; border: 1px solid #dcfce7;">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: var(--rounded-md); font-size: 13px; margin-bottom: var(--spacing-lg); text-align: center; border: 1px solid #fee2e2;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="display: flex; flex-direction: column; gap: var(--spacing-lg);">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; color: var(--colors-muted);">Username</label>
                <input type="text" name="username" required placeholder="Enter your username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; color: var(--colors-muted);">Password</label>
                <input type="password" name="password" required placeholder="••••••••">
                <div style="text-align: right; margin-top: 6px;">
                    <a href="forgot_password.php" style="font-size: 12px; color: var(--colors-muted); text-decoration: underline;">Forgot password?</a>
                </div>
            </div>
            <button type="submit" class="button-primary" style="width: 100%; padding: 14px; font-size: 15px; margin-top: 8px;">Sign In</button>
        </form>

        <div style="margin-top: var(--spacing-xl); padding-top: var(--spacing-lg); border-top: 1px solid var(--colors-hairline-soft); text-align: center;">
            <p style="color: var(--colors-muted); font-size: 13px;">
                New to HypeThread? <a href="signup.php" style="font-weight: 600; color: var(--colors-ink); text-decoration: underline;">Create Account</a>
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
