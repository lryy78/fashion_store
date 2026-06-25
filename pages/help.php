<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_enquiry_id = $_GET['id'] ?? null;

// Handle New Enquiry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_enquiry'])) {
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    $stmt = $pdo->prepare("INSERT INTO enquiries (user_id, subject, message, status) VALUES (?, ?, ?, 'open')");
    $stmt->execute([$user_id, $subject, $message]);
    $enquiry_id = $pdo->lastInsertId();
    
    // Also add to messages table for consistency
    $stmt = $pdo->prepare("INSERT INTO enquiry_messages (enquiry_id, sender_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$enquiry_id, $user_id, $message]);

    // AUTO-REPLY LOGIC
    $faqs = $pdo->query("SELECT * FROM faqs")->fetchAll();
    $auto_reply = "";
    foreach ($faqs as $faq) {
        $clean_q = strtolower(str_replace(['?', '!', '.', ','], '', $faq['question']));
        $keywords = explode(' ', $clean_q);
        $keywords = array_filter($keywords, function($w) { return strlen(trim($w)) > 3; });
        
        $clean_msg = strtolower(str_replace(['?', '!', '.', ','], '', $message));
        $clean_subject = strtolower(str_replace(['?', '!', '.', ','], '', $subject));

        foreach ($keywords as $kw) {
            $kw = trim($kw);
            if (stripos($clean_msg, $kw) !== false || stripos($clean_subject, $kw) !== false) {
                $auto_reply = "I noticed you might be asking about: " . $faq['question'] . "\n\n" . $faq['answer'];
                break 2;
            }
        }
    }

    if ($auto_reply) {
        // Find an admin ID to act as sender or just use a system ID (NULL or 0 if allowed)
        // Here we'll just use a generic 'Support Assistant' notice
        $stmt = $pdo->prepare("INSERT INTO enquiry_messages (enquiry_id, sender_id, message) VALUES (?, NULL, ?)");
        $stmt->execute([$enquiry_id, "[AUTO-REPLY] " . $auto_reply]);
    }
    
    header("Location: help.php?id=" . $enquiry_id);
    exit();
}

// Handle Buyer Message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message']) && $selected_enquiry_id) {
    $message = $_POST['message'];
    
    // Check if ticket is still open
    $stmt = $pdo->prepare("SELECT status FROM enquiries WHERE id = ?");
    $stmt->execute([$selected_enquiry_id]);
    $status = $stmt->fetchColumn();
    
    if ($status == 'open') {
        $stmt = $pdo->prepare("INSERT INTO enquiry_messages (enquiry_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$selected_enquiry_id, $user_id, $message]);

        // AUTO-REPLY LOGIC
        $faqs = $pdo->query("SELECT * FROM faqs")->fetchAll();
        $auto_reply = "";
        foreach ($faqs as $faq) {
            $clean_q = strtolower(str_replace(['?', '!', '.', ','], '', $faq['question']));
            $keywords = explode(' ', $clean_q);
            $keywords = array_filter($keywords, function($w) { return strlen(trim($w)) > 3; });
            
            $clean_msg = strtolower(str_replace(['?', '!', '.', ','], '', $message));

            foreach ($keywords as $kw) {
                $kw = trim($kw);
                if (stripos($clean_msg, $kw) !== false) {
                    $auto_reply = "Regarding your question, this might help: \n\n" . $faq['answer'];
                    break 2;
                }
            }
        }

        if ($auto_reply) {
            $stmt = $pdo->prepare("INSERT INTO enquiry_messages (enquiry_id, sender_id, message) VALUES (?, NULL, ?)");
            $stmt->execute([$selected_enquiry_id, "[AUTO-REPLY] " . $auto_reply]);
        }
    }
    
    header("Location: help.php?id=" . $selected_enquiry_id);
    exit();
}

// Handle Close Ticket (Buyer verification)
if (isset($_GET['close_id'])) {
    $close_id = $_GET['close_id'];
    $stmt = $pdo->prepare("UPDATE enquiries SET status = 'closed' WHERE id = ? AND user_id = ?");
    $stmt->execute([$close_id, $user_id]);
    header("Location: help.php?id=" . $close_id);
    exit();
}

require_once __DIR__ . '/../includes/sidebar.php';

$enquiries = $pdo->prepare("SELECT * FROM enquiries WHERE user_id = ? ORDER BY created_at DESC");
$enquiries->execute([$user_id]);
$enquiries = $enquiries->fetchAll();

$messages = [];
$current_enquiry = null;
if ($selected_enquiry_id) {
    $stmt = $pdo->prepare("SELECT * FROM enquiries WHERE id = ? AND user_id = ?");
    $stmt->execute([$selected_enquiry_id, $user_id]);
    $current_enquiry = $stmt->fetch();
    
    if ($current_enquiry) {
        $stmt = $pdo->prepare("SELECT em.*, u.username, u.role FROM enquiry_messages em LEFT JOIN users u ON em.sender_id = u.id WHERE em.enquiry_id = ? ORDER BY em.created_at ASC");
        $stmt->execute([$selected_enquiry_id]);
        $messages = $stmt->fetchAll();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout" style="background: var(--colors-canvas); height: 100vh; overflow: hidden;">
    <?php renderSidebar('buyer'); ?>
    
    <div style="flex: 1; display: flex; overflow: hidden;">
        <!-- Enquiry List -->
        <div style="width: 300px; border-right: 1px solid var(--colors-hairline-soft); background: #fff; display: flex; flex-direction: column; height: 100%;">
            <div style="padding: 24px; border-bottom: 1px solid var(--colors-hairline-soft);">
                <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 16px;">Support</h2>
                <a href="help.php" class="button-primary" style="width: 100%; display: block; text-align: center; text-decoration: none; font-size: 13px; padding: 10px;">+ New Enquiry</a>
            </div>
            <div style="flex: 1; overflow-y: auto; padding: 8px 0;">
                <?php if ($enquiries): ?>
                    <?php foreach ($enquiries as $e): ?>
                        <a href="help.php?id=<?php echo $e['id']; ?>" style="display: block; padding: 16px 24px; text-decoration: none; border-bottom: 1px solid var(--colors-hairline-soft); background: <?php echo $selected_enquiry_id == $e['id'] ? '#f8f9fa' : 'transparent'; ?>;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span style="font-weight: 600; color: var(--colors-ink); font-size: 14px;">Ticket #<?php echo $e['id']; ?></span>
                                <span style="font-size: 10px; color: <?php echo $e['status'] == 'open' ? 'var(--colors-primary)' : 'var(--colors-success)'; ?>; font-weight: 700;"><?php echo strtoupper($e['status']); ?></span>
                            </div>
                            <div style="font-size: 12px; color: var(--colors-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($e['subject']); ?></div>
                            <div style="font-size: 10px; color: var(--colors-muted); margin-top: 4px;"><?php echo date('M d', strtotime($e['created_at'])); ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 40px 24px; text-align: center; color: var(--colors-muted); font-size: 14px;">No enquiries yet.</div>
                <?php endif; ?>
            </div>
        </div>

    <div style="flex: 1; display: flex; flex-direction: column; background: #fafafa; overflow: hidden; height: 100%;">
        <!-- FAQ Section (Persistent) -->
        <?php
        $faqs = $pdo->query("SELECT * FROM faqs ORDER BY created_at DESC LIMIT 10")->fetchAll();
        if ($faqs):
        ?>
        <div style="background: #fff; border-bottom: 1px solid var(--colors-hairline-soft); padding: 12px 40px;">
            <button onclick="toggleFaqSection()" style="background: none; border: none; font-weight: 600; font-size: 13px; color: var(--colors-primary); cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <span>❓ Frequently Asked Questions</span>
                <span id="faq-toggle-icon">▼</span>
            </button>
            <div id="faq-section" style="display: none; margin-top: 16px; padding-bottom: 8px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px;">
                    <?php foreach ($faqs as $f): ?>
                        <div style="background: var(--colors-canvas); padding: 12px; border-radius: 8px; border: 1px solid var(--colors-hairline-soft);">
                            <div style="font-weight: 600; font-size: 13px; margin-bottom: 4px; color: var(--colors-ink);"><?php echo htmlspecialchars($f['question']); ?></div>
                            <div style="font-size: 12px; color: var(--colors-muted); line-height: 1.4;"><?php echo nl2br(htmlspecialchars($f['answer'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <script>
            function toggleFaqSection() {
                const section = document.getElementById('faq-section');
                const icon = document.getElementById('faq-toggle-icon');
                const isVisible = section.style.display === 'block';
                section.style.display = isVisible ? 'none' : 'block';
                icon.innerHTML = isVisible ? '▼' : '▲';
            }
        </script>
        <?php endif; ?>

        <?php if ($selected_enquiry_id && $current_enquiry): ?>
            <div style="padding: 16px 40px; background: #fff; border-bottom: 1px solid var(--colors-hairline-soft); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0; font-size: 16px;"><?php echo htmlspecialchars($current_enquiry['subject']); ?></h3>
                    <div style="font-size: 11px; color: var(--colors-muted);">Status: <span style="color: <?php echo $current_enquiry['status'] == 'open' ? 'var(--colors-primary)' : 'var(--colors-success)'; ?>; font-weight: 600;"><?php echo strtoupper($current_enquiry['status']); ?></span></div>
                </div>
                <?php if ($current_enquiry['status'] == 'open'): ?>
                    <a href="help.php?close_id=<?php echo $selected_enquiry_id; ?>" class="button-secondary" style="font-size: 12px; padding: 6px 16px;" onclick="return confirm('Is your issue resolved?')">Mark as Solved</a>
                <?php endif; ?>
            </div>
            
            <div id="chat-messages" style="flex: 1; padding: 32px 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($messages as $m): ?>
                    <?php 
                        $is_me = ($m['sender_id'] == $user_id); 
                        $is_system = ($m['sender_id'] === NULL);
                    ?>
                    <div style="display: flex; flex-direction: column; align-items: <?php echo $is_me ? 'flex-end' : ($is_system ? 'center' : 'flex-start'); ?>; margin-bottom: 8px;">
                        <div style="max-width: 85%; padding: 12px 16px; border-radius: 16px; 
                            background: <?php echo $is_me ? 'var(--colors-ink)' : ($is_system ? '#f0f4f8' : '#fff'); ?>; 
                            color: <?php echo $is_me ? '#fff' : 'var(--colors-ink)'; ?>; 
                            box-shadow: var(--shadow-sm); 
                            border: <?php echo ($is_me || $is_system) ? 'none' : '1px solid var(--colors-hairline-soft)'; ?>;
                            <?php echo $is_system ? 'border: 1px dashed #cbd5e1; font-style: italic;' : ''; ?>">
                            
                            <?php if ($is_system): ?>
                                <div style="font-size: 11px; font-weight: 700; color: var(--colors-primary); margin-bottom: 4px; text-transform: uppercase;">Assistant Bot</div>
                            <?php endif; ?>
                            
                            <p style="margin: 0; font-size: 14px; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                        </div>
                        <div style="font-size: 9px; color: var(--colors-muted); margin-top: 4px;">
                            <?php echo $is_me ? 'You' : ($is_system ? 'System' : 'Support Agent'); ?> • <?php echo date('H:i', strtotime($m['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($current_enquiry['status'] == 'open'): ?>
                <div style="padding: 20px 40px; background: #fff; border-top: 1px solid var(--colors-hairline-soft);">
                    <form method="POST" style="display: flex; gap: 12px;">
                        <input type="text" name="message" placeholder="Type your message..." style="flex: 1; padding: 12px 16px; border-radius: 100px; border: 1px solid var(--colors-hairline);" required autocomplete="off">
                        <button type="submit" name="send_message" class="button-primary" style="padding: 10px 24px; border-radius: 100px;">Send</button>
                    </form>
                </div>
            <?php else: ?>
                <div style="padding: 20px; text-align: center; background: #fff; color: var(--colors-muted); font-size: 13px; border-top: 1px solid var(--colors-hairline-soft);">
                    This conversation has been closed.
                </div>
            <?php endif; ?>

            <script>
                // Pop-up logic for auto-replies or new chat
                function scrollToBottom() {
                    const messages = document.getElementById('chat-messages');
                    if (messages) {
                        messages.scrollTop = messages.scrollHeight;
                    }
                }
                window.onload = scrollToBottom;
            </script>
        <?php else: ?>
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px;">
                <div class="surface-card" style="max-width: 500px; width: 100%; padding: 40px; border-radius: 20px; box-shadow: var(--shadow-lg);">
                    <h2 style="margin-bottom: 8px; font-size: 24px;">New Enquiry</h2>
                    <p style="color: var(--colors-muted); font-size: 14px; margin-bottom: 32px;">Describe your issue and our team will get back to you shortly.</p>
                    
                    <form method="POST">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" required placeholder="What can we help you with?" style="width: 100%; padding: 12px; border-radius: 8px;">
                        </div>
                        <div class="form-group" style="margin-bottom: 32px;">
                            <label class="form-label">Message</label>
                            <textarea name="message" rows="5" required placeholder="Tell us more about the problem..." style="width: 100%; padding: 12px; border-radius: 8px;"></textarea>
                        </div>
                        <button type="submit" name="new_enquiry" class="button-primary" style="width: 100%; padding: 14px;">Start Conversation</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
