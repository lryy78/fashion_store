<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$selected_enquiry_id = $_GET['id'] ?? null;
$filter_status = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Handle Admin Response
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_response']) && $selected_enquiry_id) {
    $message = $_POST['message'];
    $close = isset($_POST['close_ticket']) ? 'closed' : 'open';
    
    $stmt = $pdo->prepare("INSERT INTO enquiry_messages (enquiry_id, sender_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$selected_enquiry_id, $admin_id, $message]);
    
    if ($close == 'closed') {
        $stmt = $pdo->prepare("UPDATE enquiries SET status = 'closed' WHERE id = ?");
        $stmt->execute([$selected_enquiry_id]);
    }
    
    header("Location: help_center_manage.php?id=" . $selected_enquiry_id . "&status=" . $filter_status . "&search=" . urlencode($search_query));
    exit();
}

$query = "SELECT e.*, u.username, u.email FROM enquiries e JOIN users u ON e.user_id = u.id WHERE 1=1";
$params = [];
if ($filter_status != 'all') {
    $query .= " AND e.status = ?";
    $params[] = $filter_status;
}
if (!empty($search_query)) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
}
$query .= " ORDER BY e.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$enquiries = $stmt->fetchAll();

$messages = [];
$current_enquiry = null;
if ($selected_enquiry_id) {
    $stmt = $pdo->prepare("SELECT e.*, u.username FROM enquiries e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
    $stmt->execute([$selected_enquiry_id]);
    $current_enquiry = $stmt->fetch();
    
    if ($current_enquiry) {
        $stmt = $pdo->prepare("SELECT em.*, u.username, u.role FROM enquiry_messages em LEFT JOIN users u ON em.sender_id = u.id WHERE em.enquiry_id = ? ORDER BY em.created_at ASC");
        $stmt->execute([$selected_enquiry_id]);
        $messages = $stmt->fetchAll();
    }
}

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout" style="background: var(--colors-canvas); height: 100vh; overflow: hidden;">
    <?php require_once '../includes/sidebar.php'; renderSidebar('admin'); ?>

    <div style="display: flex; flex: 1; overflow: hidden;">
        <!-- Enquiry List Sidebar -->
        <div style="width: 350px; border-right: 1px solid var(--colors-hairline-soft); background: #fff; display: flex; flex-direction: column;">
            <div style="padding: 24px; border-bottom: 1px solid var(--colors-hairline-soft);">
                <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 16px;">Support Desk</h2>
                
                <form method="GET" style="display: flex; gap: 8px; margin-bottom: 16px;">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                    <?php if ($selected_enquiry_id): ?>
                        <input type="hidden" name="id" value="<?php echo $selected_enquiry_id; ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="Search buyer..." value="<?php echo htmlspecialchars($search_query); ?>" class="form-input" style="padding: 6px 12px; border-radius: 6px; font-size: 12px; flex: 1;">
                    <button type="submit" class="button-secondary" style="padding: 6px 12px; font-size: 12px;">Go</button>
                </form>

                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <a href="help_center_manage.php?status=all&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_status == 'all' ? 'badge-pill-dark' : 'badge-info'; ?>" style="text-decoration: none; cursor: pointer;">All</a>
                    <a href="help_center_manage.php?status=open&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_status == 'open' ? 'badge-pill-dark' : 'badge-pending'; ?>" style="text-decoration: none; cursor: pointer;">Open</a>
                    <a href="help_center_manage.php?status=closed&search=<?php echo urlencode($search_query); ?>" class="badge <?php echo $filter_status == 'closed' ? 'badge-pill-dark' : 'badge-success'; ?>" style="text-decoration: none; cursor: pointer;">Closed</a>
                    <?php if ($filter_status != 'all' || !empty($search_query)): ?>
                        <a href="help_center_manage.php" class="button-secondary" style="padding: 6px 12px; font-size: 11px; text-decoration: none; margin-left: 8px;">Reset Filters</a>
                    <?php endif; ?>
                </div>
            </div>
            <div style="flex: 1; overflow-y: auto;">
                <?php foreach ($enquiries as $e): ?>
                    <a href="help_center_manage.php?id=<?php echo $e['id']; ?>&status=<?php echo $filter_status; ?>" style="display: block; padding: 16px 24px; text-decoration: none; border-bottom: 1px solid var(--colors-hairline-soft); background: <?php echo $selected_enquiry_id == $e['id'] ? '#f8f9fa' : 'transparent'; ?>;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-weight: 600; color: var(--colors-ink); font-size: 14px;"><?php echo htmlspecialchars($e['username']); ?></span>
                            <span class="badge badge-<?php echo $e['status'] == 'open' ? 'pending' : 'success'; ?>" style="font-size: 10px;"><?php echo strtoupper($e['status']); ?></span>
                        </div>
                        <div style="font-size: 12px; color: var(--colors-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($e['subject']); ?></div>
                        <div style="font-size: 10px; color: var(--colors-muted); margin-top: 4px;"><?php echo date('M d, H:i', strtotime($e['created_at'])); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div style="flex: 1; display: flex; flex-direction: column; background: #fafafa; overflow: hidden;">
            <?php if ($selected_enquiry_id && $current_enquiry): ?>
                <div style="padding: 20px 40px; background: #fff; border-bottom: 1px solid var(--colors-hairline-soft); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0; font-size: 18px;"><?php echo htmlspecialchars($current_enquiry['subject']); ?></h3>
                        <div style="font-size: 12px; color: var(--colors-muted);">Conversation with <strong><?php echo htmlspecialchars($current_enquiry['username']); ?></strong></div>
                    </div>
                    <span class="badge badge-<?php echo $current_enquiry['status'] == 'open' ? 'pending' : 'success'; ?>">
                        <?php echo strtoupper($current_enquiry['status']); ?>
                    </span>
                </div>
                
                <div id="chat-messages" style="flex: 1; padding: 32px 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; background: #fafafa;">
                    <?php foreach ($messages as $m): ?>
                        <?php 
                            $is_admin = ($m['role'] == 'admin'); 
                            $is_system = ($m['sender_id'] === NULL);
                        ?>
                        <div style="display: flex; flex-direction: column; align-items: <?php echo $is_admin ? 'flex-end' : ($is_system ? 'center' : 'flex-start'); ?>; margin-bottom: 8px;">
                            <div style="max-width: 75%; padding: 10px 14px; border-radius: 14px; 
                                background: <?php echo $is_admin ? 'var(--colors-ink)' : ($is_system ? '#f0f4f8' : '#fff'); ?>; 
                                color: <?php echo $is_admin ? '#fff' : 'var(--colors-ink)'; ?>; 
                                box-shadow: var(--shadow-sm); 
                                border: <?php echo ($is_admin || $is_system) ? 'none' : '1px solid var(--colors-hairline-soft)'; ?>;
                                <?php echo $is_system ? 'border: 1px dashed #cbd5e1; font-style: italic;' : ''; ?>">
                                
                                <?php if ($is_system): ?>
                                    <div style="font-size: 10px; font-weight: 700; color: var(--colors-primary); margin-bottom: 4px; text-transform: uppercase;">Assistant Bot (Auto-Reply)</div>
                                <?php endif; ?>
                                
                                <p style="margin: 0; font-size: 14px; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                            </div>
                            <div style="font-size: 9px; color: var(--colors-muted); margin-top: 4px;">
                                <?php echo $is_admin ? 'You (Admin)' : ($is_system ? 'System' : htmlspecialchars($m['username'])); ?> • <?php echo date('H:i', strtotime($m['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="padding: 24px 40px; background: #fff; border-top: 1px solid var(--colors-hairline-soft);">
                    <?php if ($current_enquiry['status'] == 'open'): ?>
                        <form method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                            <textarea name="message" placeholder="Write a response..." style="width: 100%; min-height: 80px; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--colors-hairline); resize: none;" required></textarea>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--colors-muted); cursor: pointer;">
                                    <input type="checkbox" name="close_ticket"> Close ticket after sending
                                </label>
                                <button type="submit" name="send_response" class="button-primary" style="padding: 10px 32px; border-radius: 100px;">Send Response</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div style="text-align: center; color: var(--colors-muted); font-size: 14px; padding: 12px;">
                            This conversation is closed.
                        </div>
                    <?php endif; ?>
                </div>
                <script>
                    var objDiv = document.getElementById("chat-messages");
                    objDiv.scrollTop = objDiv.scrollHeight;
                </script>
            <?php else: ?>
                <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: var(--colors-muted);">
                    <div style="text-align: center;">
                        <div style="font-size: 64px; margin-bottom: 20px; opacity: 0.2;">💬</div>
                        <p style="font-size: 18px;">Select a conversation to start messaging</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
