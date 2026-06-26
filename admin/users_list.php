<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$role, $user_id]);
}

if (isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'];
    $is_active = $_POST['is_active'] ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$is_active, $user_id]);
}

$reg_error = '';
$reg_success = '';
if (isset($_POST['register_user'])) {
    $new_username = trim($_POST['new_username']);
    $new_email    = trim($_POST['new_email']);
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $new_role     = $_POST['new_role'];
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$new_username, $new_email, $new_password, $new_role]);
        $reg_success = "User '" . htmlspecialchars($new_username) . "' registered as " . ucfirst($new_role) . " successfully.";
    } catch (PDOException $e) {
        $reg_error = "Username or email already exists.";
    }
}

$filter_role = $_GET['role'] ?? 'all';
$search_query = $_GET['search'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($filter_role != 'all') {
    $query .= " AND role = ?";
    $params[] = $filter_role;
}

if (!empty($search_query)) {
    $query .= " AND (username LIKE ? OR email LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<?php require_once '../includes/sidebar.php'; ?>
<div class="dashboard-layout">
    <?php renderSidebar('admin'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xxl);">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Governance</div>
                <h1 style="margin: 0; font-size: 40px;">Account Registry</h1>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px; align-items: flex-end;">
                <form method="GET" style="display: flex; gap: 8px;">
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($filter_role); ?>">
                    <input type="text" name="search" placeholder="Search username or email..." value="<?php echo htmlspecialchars($search_query); ?>" class="form-input" style="padding: 8px 16px; border-radius: 20px; font-size: 13px; width: 250px;">
                    <button type="submit" class="button-secondary" style="padding: 8px 16px; border-radius: 20px;">Search</button>
                </form>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <a href="users_list.php?role=all&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_role == 'all' ? 'badge-pill-dark' : 'badge-info'; ?>" style="text-decoration: none;">All</a>
                    <a href="users_list.php?role=buyer&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_role == 'buyer' ? 'badge-pill-dark' : 'badge-info'; ?>" style="text-decoration: none;">Buyers</a>
                    <a href="users_list.php?role=manager&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_role == 'manager' ? 'badge-pill-dark' : 'badge-info'; ?>" style="text-decoration: none;">Managers</a>
                    <a href="users_list.php?role=admin&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_role == 'admin' ? 'badge-pill-dark' : 'badge-info'; ?>" style="text-decoration: none;">Admins</a>
                    <a href="users_list.php?role=owner&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_role == 'owner' ? 'badge-pill-dark' : 'badge-info'; ?>" style="text-decoration: none;">Owners</a>
                </div>
            </div>
        </header>

        <!-- Admin Register User Panel -->
        <div class="surface-card" style="padding: 0; overflow: hidden; margin-bottom: 32px; border: 1px solid var(--colors-hairline);">
            <div style="padding: 16px 24px; border-bottom: 1px solid var(--colors-hairline-soft); background: var(--colors-surface-soft); display: flex; align-items: center; gap: 10px; cursor: pointer;" onclick="document.getElementById('regPanel').style.display = document.getElementById('regPanel').style.display === 'none' ? 'block' : 'none';">
                <span style="font-size: 16px;">➕</span>
                <span style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;">Register New User</span>
                <span style="margin-left: auto; font-size: 12px; color: var(--colors-muted);">Click to expand</span>
            </div>
            <div id="regPanel" style="display: <?php echo ($reg_error || $reg_success) ? 'block' : 'none'; ?>; padding: 24px;">
                <?php if ($reg_success): ?>
                    <div style="background: #f0fdf4; color: #166534; padding: 12px 16px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; border: 1px solid #dcfce7;"><?php echo $reg_success; ?></div>
                <?php endif; ?>
                <?php if ($reg_error): ?>
                    <div style="background: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; border: 1px solid #fee2e2;"><?php echo $reg_error; ?></div>
                <?php endif; ?>
                <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 16px; align-items: flex-end;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Username</label>
                        <input type="text" name="new_username" required placeholder="username" class="form-input">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Email</label>
                        <input type="email" name="new_email" required placeholder="email@example.com" class="form-input">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Password</label>
                        <input type="password" name="new_password" required placeholder="password" class="form-input">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Role</label>
                        <select name="new_role" class="form-input" style="height: 44px;">
                            <option value="buyer">Buyer</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                    <button type="submit" name="register_user" class="button-primary" style="height: 44px; padding: 0 20px; white-space: nowrap;">Register</button>
                </form>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Identity</th>
                        <th>Contact</th>
                        <th>System Access</th>
                        <th style="text-align: right;">Status & Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--colors-ink);"><?php echo htmlspecialchars($u['username']); ?></div>
                                <div style="font-family: var(--typography-code-font); font-size: 11px; color: var(--colors-muted);">ID: #<?php echo str_pad($u['id'], 4, '0', STR_PAD_LEFT); ?></div>
                            </td>
                            <td style="font-size: 14px;"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <select name="role" style="width: auto; padding: 6px 30px 6px 12px; font-size: 12px; height: 32px;">
                                        <option value="buyer" <?php echo $u['role'] == 'buyer' ? 'selected' : ''; ?>>Buyer</option>
                                        <option value="manager" <?php echo $u['role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                        <option value="admin" <?php echo $u['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="owner" <?php echo $u['role'] == 'owner' ? 'selected' : ''; ?>>Owner</option>
                                    </select>
                                    <button type="submit" name="update_role" class="button-primary" style="padding: 0 12px; height: 32px; font-size: 11px;">Save</button>
                                </form>
                            </td>
                            <td style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                <?php if (isset($u['is_active']) && !$u['is_active']): ?>
                                    <span class="badge badge-error" style="font-size: 10px;">DEACTIVATED</span>
                                <?php else: ?>
                                    <span class="badge badge-success" style="font-size: 10px;">ACTIVE</span>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <?php if (isset($u['is_active']) && !$u['is_active']): ?>
                                        <input type="hidden" name="is_active" value="1">
                                        <button type="submit" name="toggle_status" class="button-secondary" style="padding: 4px 8px; font-size: 10px; border-color: var(--colors-success); color: var(--colors-success);">Activate</button>
                                    <?php else: ?>
                                        <input type="hidden" name="is_active" value="0">
                                        <button type="submit" name="toggle_status" class="button-secondary" style="padding: 4px 8px; font-size: 10px; border-color: var(--colors-error); color: var(--colors-error);">Deactivate</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
