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
    $address_line = trim($_POST['address_line'] ?? '');
    $address_city = trim($_POST['address_city'] ?? '');
    $address_postcode = trim($_POST['address_postcode'] ?? '');
    $address_country = trim($_POST['address_country'] ?? '');
    $password = $_POST['password'];

    // Validate all address fields are filled
    $address_error = '';
    if (empty($address_line) || empty($address_city) || empty($address_postcode) || empty($address_country)) {
        $address_error = 'Please fill in all address fields (street address, city, postcode, and country) to save a valid default address.';
    }

    if (empty($address_error)) {
        // Combine address parts into single address field
        $address = "$address_line, $address_city, $address_postcode, $address_country";

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $address, $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $address, $user_id]);
        }
        $msg = "Profile updated successfully!";
    } else {
        $msg = $address_error;
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Parse saved address into structured fields for display
// Address format: "full_name, street_address, city, postcode, country"
$saved_address = $user['address'] ?? '';
$address_parts = [];
if ($saved_address) {
    $address_parts = explode(', ', $saved_address);
    // Skip first element (full_name) - it's stored separately in the users table
    $user['address_line'] = $address_parts[1] ?? '';
    $user['address_city'] = $address_parts[2] ?? '';
    $user['address_postcode'] = $address_parts[3] ?? '';
    $user['address_country'] = $address_parts[4] ?? '';
} else {
    $user['address_line'] = '';
    $user['address_city'] = '';
    $user['address_postcode'] = '';
    $user['address_country'] = '';
}

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
                    <input type="text" name="address_line" placeholder="Street address" value="<?php echo htmlspecialchars($user['address_line'] ?? ''); ?>" style="width: 100%; padding: 10px 16px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md); margin-bottom: 8px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <input type="text" name="address_city" placeholder="City" value="<?php echo htmlspecialchars($user['address_city'] ?? ''); ?>" style="width: 100%; padding: 10px 16px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
                        <input type="text" name="address_postcode" placeholder="Postcode" value="<?php echo htmlspecialchars($user['address_postcode'] ?? ''); ?>" style="width: 100%; padding: 10px 16px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md);">
                    </div>
                    <select name="address_country" style="width: 100%; padding: 10px 16px; border: 1px solid var(--colors-hairline); border-radius: var(--rounded-md); margin-top: 8px;">
                        <option value="">Select country…</option>
                        <?php
                        $countries = ['Malaysia','Singapore','Indonesia','Thailand','Philippines','Vietnam','United States','United Kingdom','Australia'];
                        $selectedCountry = $user['address_country'] ?? '';
                        foreach ($countries as $c) {
                            $sel = ($c === $selectedCountry) ? 'selected' : '';
                            echo "<option value=\"$c\" $sel>$c</option>";
                        }
                        ?>
                    </select>
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
