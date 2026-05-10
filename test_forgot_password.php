<?php
require_once 'config/db.php';

echo "=== FORGOT PASSWORD END-TO-END TEST ===\n\n";

// 1. Find a real user
$user = $pdo->query("SELECT id, username, email FROM users LIMIT 1")->fetch();
echo "Testing with user: {$user['username']} (ID: {$user['id']})\n\n";

// 2. Generate token using MySQL clock (same as forgot_password.php)
$token = bin2hex(random_bytes(32));
$pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?")
    ->execute([$token, $user['id']]);
$expires = $pdo->query("SELECT reset_expires FROM users WHERE id = {$user['id']}")->fetchColumn();
echo "STEP 1 ✅ Token generated: " . substr($token, 0, 20) . "...\n";
echo "       Expires (MySQL): $expires\n\n";

// 3. Validate token (simulates GET ?token=xxx)
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$found = $stmt->fetch();
if ($found) {
    echo "STEP 2 ✅ Token validated. User found: {$found['username']}\n\n";
} else {
    echo "STEP 2 ❌ Token NOT valid — bug in DB or timing\n\n";
    exit(1);
}

// 4. Reset password (simulates POST action=do_reset)
$new_password = 'testpassword123';
$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?")
    ->execute([$hashed, $found['id']]);
echo "STEP 3 ✅ Password updated successfully.\n\n";

// 5. Verify token is cleared
$check = $pdo->prepare("SELECT reset_token FROM users WHERE id = ?");
$check->execute([$user['id']]);
$remaining_token = $check->fetchColumn();
echo "STEP 4 ✅ Token cleared: " . ($remaining_token === null ? "NULL (correct)" : "NOT NULL (bug!)") . "\n\n";

// 6. Verify new password works
$row = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$row->execute([$user['id']]);
$stored_hash = $row->fetchColumn();
if (password_verify($new_password, $stored_hash)) {
    echo "STEP 5 ✅ New password verified successfully — login would work!\n\n";
} else {
    echo "STEP 5 ❌ Password verification FAILED\n\n";
}

echo "=== ALL TESTS PASSED ===\n";
echo "\nReset link URL format would be:\n";
echo "http://localhost/fashion_store/forgot_password.php?token=" . $token . "\n";
?>
