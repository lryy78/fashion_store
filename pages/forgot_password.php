<?php
/**
 * HypeThread — Forgot Password
 *
 * Processing order (must be top-to-bottom):
 *  1. POST action=request_reset → generate token, show link
 *  2. POST action=do_reset      → validate token from POST, update password
 *  3. GET  ?token=xxx           → validate token, show reset form
 *  4. Default GET               → show request form
 */

session_start();
require_once __DIR__ . '/../config/db.php';

$step         = 'request';
$error        = '';
$token        = '';
$reset_link   = '';
$display_user = '';

// ── STEP 1 → 2: Generate reset token (POST) ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_reset') {
    $identifier = trim($_POST['identifier'] ?? '');
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user) {
        $token   = bin2hex(random_bytes(32));
        // Use MySQL NOW() so expiry is always on the same clock as the comparison
        $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?")
            ->execute([$token, $user['id']]);

        $scheme     = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $reset_link = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/fashion_store/forgot_password.php?token=' . $token;
        $display_user = $user['username'];
        $step = 'sent';
    } else {
        $error = "No account found with that username or email.";
        $step  = 'request';
    }

// ── STEP 4: Submit new password (POST) ───────────────────────────────────────
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'do_reset') {
    $token    = $_POST['token'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($new_pass) < 6) {
        $error = "Password must be at least 6 characters.";
        $step  = 'reset';
    } elseif ($new_pass !== $confirm) {
        $error = "Passwords do not match.";
        $step  = 'reset';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?")
                ->execute([$hashed, $user['id']]);
            $step = 'done';
        } else {
            $error = "Reset link has expired. Please request a new one.";
            $step  = 'request';
        }
    }

// ── STEP 3: Token link followed (GET with ?token=) ───────────────────────────
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['token'])) {
    $token = trim($_GET['token']);
    $stmt  = $pdo->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $step = 'reset';
    } else {
        $error = "This reset link is invalid or has expired. Please request a new one.";
        $step  = 'request';
    }
}

// ── Default: show request form (step stays 'request') ────────────────────────

include __DIR__ . '/../includes/header.php';
?>

<style>
.fp-card {
    max-width: 460px;
    width: 100%;
    padding: 48px;
    background: #fff;
    border: 1px solid var(--colors-hairline-soft);
    box-shadow: var(--shadow-lg);
    border-radius: 2px;
}
.fp-title {
    font-family: var(--typography-display-font);
    font-size: 30px;
    letter-spacing: -0.02em;
    margin-bottom: 8px;
}
.fp-sub {
    color: var(--colors-muted);
    font-size: 14px;
    margin-bottom: 32px;
    line-height: 1.5;
}
.fp-alert-success {
    background: #f0fdf4; color: #166534; padding: 14px 16px;
    border-radius: 6px; font-size: 13px; margin-bottom: 24px;
    border: 1px solid #dcfce7; line-height: 1.5;
}
.fp-alert-error {
    background: #fef2f2; color: #991b1b; padding: 14px 16px;
    border-radius: 6px; font-size: 13px; margin-bottom: 24px;
    border: 1px solid #fee2e2;
}
.fp-token-box {
    background: var(--colors-surface-soft);
    border: 1px solid var(--colors-hairline);
    border-radius: 6px;
    padding: 14px 16px;
    font-family: var(--typography-code-font);
    font-size: 12px;
    word-break: break-all;
    color: var(--colors-ink);
    margin: 16px 0 24px;
    line-height: 1.6;
}
</style>

<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; background-color: var(--colors-canvas);">
    <div class="fp-card fade-in-up">

        <?php if ($step === 'request'): ?>
            <div class="fp-title">Forgot Password</div>
            <p class="fp-sub">Enter your username or email address and we'll generate a secure reset link for you.</p>

            <?php if ($error): ?><div class="fp-alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <form method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="hidden" name="action" value="request_reset">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; color: var(--colors-muted);">Username or Email</label>
                    <input type="text" name="identifier" required placeholder="" autofocus>
                </div>
                <button type="submit" class="button-primary" style="width: 100%; padding: 14px; font-size: 15px;">Generate Reset Link</button>
            </form>
            <div style="margin-top: 24px; text-align: center;">
                <a href="login.php" style="font-size: 13px; color: var(--colors-muted); text-decoration: underline;">Back to Sign In</a>
            </div>

        <?php elseif ($step === 'sent'): ?>
            <div class="fp-title">Reset Link Ready</div>
            <p class="fp-sub">A password reset link has been generated for <strong><?php echo htmlspecialchars($display_user); ?></strong>. This link expires in <strong>1 hour</strong>.</p>

            <div style="font-size: 11px; color: var(--colors-muted); margin-bottom: 8px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.08em;">Your Reset Link</div>
            <div class="fp-token-box"><?php echo htmlspecialchars($reset_link); ?></div>

            <a href="<?php echo htmlspecialchars($reset_link); ?>" class="button-primary" style="display: block; width: 100%; text-align: center; padding: 14px; font-size: 15px; box-sizing: border-box; margin-bottom: 16px;">Click Here to Reset Password</a>

            <div>
            </div>
            <div style="text-align: center;">
                <a href="login.php" style="font-size: 13px; color: var(--colors-muted); text-decoration: underline;">Back to Sign In</a>
            </div>

        <?php elseif ($step === 'reset'): ?>
            <div class="fp-title">Set New Password</div>
            <p class="fp-sub">Choose a strong new password for your HypeThread account.</p>

            <?php if ($error): ?><div class="fp-alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <form method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="hidden" name="action" value="do_reset">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; color: var(--colors-muted);">New Password</label>
                    <input type="password" name="new_password" required placeholder="At least 6 characters" minlength="6" autofocus>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; color: var(--colors-muted);">Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="Repeat your new password">
                </div>
                <button type="submit" class="button-primary" style="width: 100%; padding: 14px; font-size: 15px;">Update Password</button>
            </form>

        <?php elseif ($step === 'done'): ?>
            <div style="text-align: center;">
                <div style="font-size: 48px; margin-bottom: 16px;">✅</div>
                <div class="fp-title">Password Updated!</div>
                <p class="fp-sub" style="margin-bottom: 32px;">Your password has been changed successfully. You can now sign in with your new credentials.</p>
                <a href="login.php" class="button-primary" style="display: inline-block; padding: 14px 32px; font-size: 15px;">Go to Sign In</a>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
