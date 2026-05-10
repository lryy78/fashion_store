<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get role filter
$filter_role = $_GET['role'] ?? '';
$query = "SELECT id, username, role, created_at FROM users";
$params = [];
if ($filter_role && in_array($filter_role, ['buyer','manager','admin','owner'])) {
    $query .= " WHERE role = ?";
    $params[] = $filter_role;
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<div class="container">
    <h1>Accounts</h1>
    <form method="GET" class="filter-form" style="margin-bottom:20px;">
        <label>Filter by role:</label>
        <select name="role" onchange="this.form.submit()">
            <option value="">All Roles</option>
            <option value="buyer" <?php if($filter_role=='buyer') echo 'selected'; ?>>Buyer</option>
            <option value="manager" <?php if($filter_role=='manager') echo 'selected'; ?>>Manager</option>
            <option value="admin" <?php if($filter_role=='admin') echo 'selected'; ?>>Admin</option>
            <option value="owner" <?php if($filter_role=='owner') echo 'selected'; ?>>Owner</option>
        </select>
    </form>
    <table class="data-table">
        <thead>
            <tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th></tr>
        </thead>
        <tbody>
            <?php foreach($users as $u): ?>
                <tr>
                    <td>#<?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo $u['role']; ?></td>
                    <td><?php echo $u['created_at']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
