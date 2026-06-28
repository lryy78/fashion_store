<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$success_msg = null;
$error_msg = null;

// Handle FAQ creation/deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_faq'])) {
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);
        if ($question && $answer) {
            $stmt = $pdo->prepare("INSERT INTO faqs (question, answer) VALUES (?, ?)");
            $stmt->execute([$question, $answer]);
            $success_msg = "FAQ added successfully.";
        }
    } elseif (isset($_POST['delete_faq'])) {
        $id = $_POST['faq_id'];
        $stmt = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
        $stmt->execute([$id]);
        $success_msg = "FAQ deleted.";
    }
}

// Fetch all FAQs
$faqs = $pdo->query("SELECT * FROM faqs ORDER BY created_at DESC")->fetchAll();

$include_path = '../includes/';
include $include_path . 'header.php';
?>

<div class="dashboard-layout">
    <?php renderSidebar('admin'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="margin-bottom: var(--spacing-xxl);">
            <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Support Automation</div>
            <h1 style="margin: 0; font-size: 40px;">Manage FAQs</h1>
        </header>

        <?php if ($success_msg): ?>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; padding: 16px; border-radius: 8px; margin-bottom: 24px;">✓ <?php echo $success_msg; ?></div>
        <?php endif; ?>

        <div class="faq-container" style="display: flex; flex-wrap: wrap; gap: 24px; max-width: 1000px; margin: auto;">
            <!-- FAQ List & Form -->
            <div class="surface-card" style="padding: 32px; border-radius: 16px; margin-bottom: 32px; flex: 1; min-width: 300px; border: 1px solid #ddd;">                <h2 style="font-size: 20px; margin-bottom: 24px;">Add New FAQ</h2>
                <form method="POST">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 8px; color: var(--colors-muted);">Question</label>
                        <input type="text" name="question" required style="width: 100%; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: 8px;">
                    </div>
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 8px; color: var(--colors-muted);">Answer</label>
                        <textarea name="answer" rows="4" required style="width: 100%; padding: 12px; border: 1px solid var(--colors-hairline); border-radius: 8px; resize: none;"></textarea>
                    </div>
                    <button type="submit" name="add_faq" class="button-primary" style="width: 100%; padding: 14px;">Publish FAQ</button>
                </form>
            </div>

            <div class="surface-card" style="padding: 32px; border-radius: 16px; flex: 1; min-width: 300px; border: 1px solid #ddd;">
                <h2 style="font-size: 20px; margin-bottom: 24px;">Existing FAQs</h2>
                <?php if ($faqs): ?>
                    <div class="faq-list" style="max-height:400px; overflow-y:auto;">
                        <?php foreach ($faqs as $f): ?>
                            <div style="padding: 16px; border: 1px solid var(--colors-hairline-soft); border-radius: 12px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($f['question']); ?></div>
                                    <form method="POST" onsubmit="return confirm('Delete this FAQ?')">
                                        <input type="hidden" name="faq_id" value="<?php echo $f['id']; ?>">
                                        <button type="submit" name="delete_faq" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 12px;">Delete</button>
                                    </form>
                                </div>
                                <div style="font-size: 13px; color: var(--colors-muted);"><?php echo nl2br(htmlspecialchars($f['answer'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--colors-muted); font-size: 14px;">No FAQs created yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include $include_path . 'footer.php'; ?>
