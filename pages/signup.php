<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = 'buyer'; // Public signup is always buyer — role assignment is admin-only

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role]);
        header("Location: login.php?msg=Account created successfully! Please sign in.");
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Username or email is already taken. Please choose another.";
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; background-color: var(--colors-canvas);">
    <div class="auth-container fade-in-up" style="max-width: 440px; width: 100%; padding: var(--spacing-xxl); background: #fff; border: 1px solid var(--colors-hairline-soft); box-shadow: var(--shadow-lg);">
        <div style="text-align: center; margin-bottom: var(--spacing-xl);">
            <div style="font-family: var(--typography-display-font); font-size: 32px; letter-spacing: -0.02em; margin-bottom: 8px;">Create Account</div>
            <p style="color: var(--colors-muted); font-size: 14px;">Join HypeThread and start shopping</p>
        </div>

        <?php if ($error): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: var(--rounded-md); font-size: 13px; margin-bottom: var(--spacing-lg); text-align: center; border: 1px solid #fee2e2;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="display: flex; flex-direction: column; gap: var(--spacing-lg);">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; color: var(--colors-muted);">Username</label>
                <input type="text" name="username" required placeholder="Choose a username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; color: var(--colors-muted);">Email Address</label>
                <input type="email" name="email" required placeholder="you@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; color: var(--colors-muted);">Password</label>
                <input type="password" name="password" required placeholder="Create a strong password">
            </div>

            <input type="hidden" name="role" value="buyer">
            <button type="submit" class="button-primary" style="width: 100%; padding: 14px; font-size: 15px; margin-top: 8px;">Create Account</button>
        </form>

        <div style="margin-top: var(--spacing-xl); padding-top: var(--spacing-lg); border-top: 1px solid var(--colors-hairline-soft); text-align: center;">
            <p style="color: var(--colors-muted); font-size: 13px;">
                Already have an account? <a href="login.php" style="font-weight: 600; color: var(--colors-ink); text-decoration: underline;">Sign In</a>
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
