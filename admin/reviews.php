<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$rating_filter = $_GET['rating'] ?? 'all';

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reply'])) {
    // ... existing reply logic ...
    $review_id = $_POST['review_id'];
    $reply_text = trim($_POST['reply_text']);
    $user_id = $_POST['user_id'];
    $product_name = $_POST['product_name'];
    
    if (!empty($reply_text)) {
        // Update review with admin reply
        $stmt = $pdo->prepare("UPDATE reviews SET admin_reply = ? WHERE id = ?");
        $stmt->execute([$reply_text, $review_id]);
        
        // Create an enquiry ticket so the buyer sees the reply in their support section
        $subject = "Reply to your review for: " . $product_name;
        $initial_message = "Thank you for reviewing $product_name. Store response: \n\n" . $reply_text;
        
        // Insert new enquiry
        $stmt = $pdo->prepare("INSERT INTO enquiries (user_id, subject, message, status) VALUES (?, ?, ?, 'closed')");
        $stmt->execute([$user_id, $subject, $initial_message]);
        $enquiry_id = $pdo->lastInsertId();
        
        // Insert the message into enquiry_messages
        $stmt = $pdo->prepare("INSERT INTO enquiry_messages (enquiry_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$enquiry_id, $admin_id, $initial_message]);
        
        $success_msg = "Reply submitted and sent to the buyer's support section.";
    }
}

// Fetch all reviews with optional filter
$query = "
    SELECT r.*, u.full_name as reviewer_name, p.name as product_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    WHERE 1=1
";

if ($rating_filter !== 'all') {
    $query .= " AND r.rating = " . (int)$rating_filter;
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$reviews = $stmt->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php renderSidebar('admin'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--spacing-xl);">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Customer Feedback</div>
                <h1 style="margin: 0; font-size: 32px;">Manage Reviews</h1>
            </div>
            
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="?rating=all" class="button-secondary" style="font-size: 12px; padding: 8px 16px; <?php echo $rating_filter == 'all' ? 'background: var(--colors-ink); color: #fff;' : ''; ?>">All</a>
                <?php for($i=5; $i>=1; $i--): ?>
                    <a href="?rating=<?php echo $i; ?>" class="button-secondary" style="font-size: 12px; padding: 8px 16px; <?php echo $rating_filter == $i ? 'background: var(--colors-ink); color: #fff;' : ''; ?>"><?php echo $i; ?> ★</a>
                <?php endfor; ?>
            </div>
        </header>

        <?php if (isset($success_msg)): ?>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; padding: 16px; border-radius: var(--rounded-md); margin-bottom: 24px; font-weight: 500;">
                ✓ <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <div class="table-container" style="max-height:500px; overflow-y:auto;">
            <?php if ($reviews): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Reviewer</th>
                            <th>Rating</th>
                            <th style="width: 30%;">Comment</th>
                            <th>Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($review['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($review['reviewer_name']); ?></td>
                                <td style="color: #fbbf24; letter-spacing: 2px;">
                                    <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                </td>
                                <td>
                                    <div style="font-size: 13px; line-height: 1.5; color: var(--colors-muted); max-height: 4.5em; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($review['comment']); ?>
                                    </div>
                                    <?php if ($review['admin_reply']): ?>
                                        <div style="margin-top: 8px; font-size: 12px; color: var(--colors-primary); font-weight: 600;">✓ Replied</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                <td style="text-align: right;">
                                    <?php if (!$review['admin_reply']): ?>
                                        <button class="button-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="openReplyModal(<?php echo $review['id']; ?>, '<?php echo htmlspecialchars(addslashes($review['product_name'])); ?>', <?php echo $review['user_id']; ?>)">Reply</button>
                                    <?php else: ?>
                                        <button class="button-secondary" style="padding: 6px 12px; font-size: 12px; opacity: 0.5;" onclick="openReplyModal(<?php echo $review['id']; ?>, '<?php echo htmlspecialchars(addslashes($review['product_name'])); ?>', <?php echo $review['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($review['admin_reply'])); ?>')">Edit Reply</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: var(--spacing-xxl); text-align: center;">
                    <p style="color: var(--colors-muted); font-size: 16px;">No reviews found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div id="reply-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 32px; border-radius: var(--rounded-lg); width: 100%; max-width: 500px; box-shadow: var(--shadow-lg);">
        <h3 style="margin-bottom: 24px; font-size: 20px;">Reply to Review</h3>
        <form method="POST">
            <input type="hidden" name="review_id" id="modal-review-id">
            <input type="hidden" name="user_id" id="modal-user-id">
            <input type="hidden" name="product_name" id="modal-product-name">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Your Reply</label>
                <textarea name="reply_text" id="modal-reply-text" rows="5" required style="width: 100%; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: 4px; font-family: var(--typography-body-font);"></textarea>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="button-secondary" onclick="document.getElementById('reply-modal').style.display='none'">Cancel</button>
                <button type="submit" name="submit_reply" class="button-primary">Send Reply</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReplyModal(reviewId, productName, userId, existingReply = '') {
    document.getElementById('modal-review-id').value = reviewId;
    document.getElementById('modal-product-name').value = productName;
    document.getElementById('modal-user-id').value = userId;
    document.getElementById('modal-reply-text').value = existingReply;
    document.getElementById('reply-modal').style.display = 'flex';
}
</script>

<?php include $include_path . 'footer.php'; ?>
