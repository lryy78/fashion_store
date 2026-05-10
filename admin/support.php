<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$status = $_GET['status'] ?? '';
$query = "SELECT id, user_id, subject, status, created_at FROM enquiries";
$params = [];
if ($status && in_array($status, ['open','closed'])) {
    $query .= " WHERE status = ?";
    $params[] = $status;
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

include '../includes/header.php';
?>
<div class="container" style="padding:40px 0;">
    <h2>Support Tickets</h2>
    <form method="GET" class="filter-form" style="margin-bottom:20px;">
        <label>Status:
            <select name="status" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="open" <?php echo $status=='open'?'selected':''; ?>>Open</option>
                <option value="closed" <?php echo $status=='closed'?'selected':''; ?>>Closed</option>
            </select>
        </label>
    </form>
    <table class="data-table" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr><th>ID</th><th>User</th><th>Subject</th><th>Status</th><th>Created</th></tr>
        </thead>
        <tbody>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td>#<?php echo $t['id']; ?></td>
                    <td><?php echo htmlspecialchars($t['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($t['subject']); ?></td>
                    <td><?php echo htmlspecialchars($t['status']); ?></td>
                    <td><?php echo $t['created_at']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
