<?php
session_start();
require_once '../config/db.php';
require_once '../includes/alert_generator.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

// Handle actions (BEFORE sync)
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'read') {
        $stmt = $pdo->prepare("UPDATE system_alerts SET is_read = 1 WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } elseif ($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM system_alerts WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } elseif ($_GET['action'] == 'read_all') {
        $pdo->query("UPDATE system_alerts SET is_read = 1");
    }
    header("Location: alerts_center.php");
    exit();
}

// Sync alerts after actions
syncAlerts($pdo);

// Fetch Thresholds and Active Alerts for the form below
$settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$low_stock_limit = $settings['low_stock_threshold'] ?? 10;
$overstock_limit = $settings['overstock_threshold'] ?? 100;
$active_alerts = explode(',', $settings['dashboard_active_alerts'] ?? 'out_of_stock,low_stock,overstock');

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_thresholds'])) {
    $low = $_POST['low_stock'];
    $over = $_POST['overstock'];
    $alerts = isset($_POST['active_alerts']) ? implode(',', $_POST['active_alerts']) : '';
    
    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'low_stock_threshold'");
    $stmt->execute([$low]);
    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'overstock_threshold'");
    $stmt->execute([$over]);
    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'dashboard_active_alerts'");
    $stmt->execute([$alerts]);
    
    header("Location: alerts_center.php?updated=1");
    exit();
}

// Fetch alerts
$filter_priority = $_GET['priority'] ?? 'all';
$query = "SELECT * FROM system_alerts WHERE 1=1";
if ($filter_priority != 'all') {
    $query .= " AND priority = " . $pdo->quote($filter_priority);
}
$query .= " ORDER BY created_at DESC";
$alerts = $pdo->query($query)->fetchAll();

include '../includes/header.php';
?>

<style>
.alert-item-studio {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid var(--colors-hairline-soft);
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: transform 0.2s ease;
}
.alert-item-studio:hover {
    transform: translateX(4px);
}
.alert-item-studio.unread {
    border-left: 4px solid var(--colors-primary);
}
.alert-item-studio.critical { border-left-color: var(--colors-error); }
.alert-item-studio.warning { border-left-color: #f59e0b; }
.alert-item-studio.info { border-left-color: var(--colors-accent-teal); }

.priority-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
}

.config-section {
    background: var(--colors-surface-soft);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
    border: 1px solid var(--colors-hairline);
}
.alerts-list {
    max-height: 500px;
    overflow-y: auto;
    padding-right: 8px;
}
</style>

<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px;">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">System Intelligence</div>
                <h1 style="margin: 0; font-size: 40px;">Alerts Center</h1>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="alerts_center.php?action=read_all" class="button-secondary">Mark All as Read</a>
            </div>
        </header>

        <!-- Configuration Panel -->
        <div class="config-section">
            <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 24px; font-family: var(--typography-body-font);">Threshold & Dashboard Configuration</h3>
            <form method="POST">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" style="font-size: 11px;">Low Stock Limit</label>
                        <input type="number" name="low_stock" value="<?php echo $low_stock_limit; ?>" class="form-input" style="padding: 12px; background: #fff;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" style="font-size: 11px;">Overstock Limit</label>
                        <input type="number" name="overstock" value="<?php echo $overstock_limit; ?>" class="form-input" style="padding: 12px; background: #fff;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" style="font-size: 11px; margin-bottom: 12px; display: block;">Dashboard Display</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 16px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer;">
                                <input type="checkbox" name="active_alerts[]" value="out_of_stock" <?php echo in_array('out_of_stock', $active_alerts) ? 'checked' : ''; ?>> Stockouts
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer;">
                                <input type="checkbox" name="active_alerts[]" value="low_stock" <?php echo in_array('low_stock', $active_alerts) ? 'checked' : ''; ?>> Low Stock
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; cursor: pointer;">
                                <input type="checkbox" name="active_alerts[]" value="overstock" <?php echo in_array('overstock', $active_alerts) ? 'checked' : ''; ?>> Overstock
                            </label>
                        </div>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <button type="submit" name="update_thresholds" class="button-primary" style="padding: 12px 32px;">Save Intelligence Preferences</button>
                    <?php if (isset($_GET['updated'])): ?>
                        <span style="font-size: 13px; color: var(--colors-success); font-weight: 600;">✓ Configuration updated successfully.</span>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Filters -->
        <div style="display: flex; gap: 12px; margin-bottom: 32px; border-top: 1px solid var(--colors-hairline); padding-top: 32px; align-items: center; flex-wrap: wrap;">
            <a href="alerts_center.php?priority=all" class="button-secondary <?php echo $filter_priority == 'all' ? 'active' : ''; ?>" style="padding: 8px 16px; font-size: 12px;">All Levels</a>
            <a href="alerts_center.php?priority=critical" class="button-secondary <?php echo $filter_priority == 'critical' ? 'active' : ''; ?>" style="padding: 8px 16px; font-size: 12px; color: var(--colors-error);">Critical</a>
            <a href="alerts_center.php?priority=warning" class="button-secondary <?php echo $filter_priority == 'warning' ? 'active' : ''; ?>" style="padding: 8px 16px; font-size: 12px; color: #f59e0b;">Warning</a>
            <a href="alerts_center.php?priority=info" class="button-secondary <?php echo $filter_priority == 'info' ? 'active' : ''; ?>" style="padding: 8px 16px; font-size: 12px; color: var(--colors-accent-teal);">Info</a>
        </div>

        <div class="alerts-list">
            <?php if (empty($alerts)): ?>
                <div style="text-align: center; padding: 64px; background: var(--colors-surface-soft); border-radius: 12px; color: var(--colors-muted);">
                    <div style="font-size: 32px; margin-bottom: 16px;">✓</div>
                    <p>All systems operational. No active alerts found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($alerts as $a): ?>
                    <div class="alert-item-studio <?php echo $a['is_read'] ? '' : 'unread'; ?> <?php echo $a['priority']; ?>">
                        <div style="display: flex; align-items: flex-start; gap: 16px;">
                            <div style="padding-top: 4px;">
                                <?php if ($a['priority'] == 'critical'): ?>
                                    <span style="color: var(--colors-error);">⚠️</span>
                                <?php elseif ($a['priority'] == 'warning'): ?>
                                    <span style="color: #f59e0b;">⚡</span>
                                <?php else: ?>
                                    <span style="color: var(--colors-accent-teal);">ℹ️</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 15px; margin-bottom: 8px; color: var(--colors-ink); line-height: 1.4;">
                                    <?php echo nl2br(htmlspecialchars($a['message'])); ?>
                                </div>
                                <div style="font-size: 12px; color: var(--colors-muted); display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                    <span><?php echo date('M d, Y • H:i', strtotime($a['created_at'])); ?></span>
                                    <span style="text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: var(--colors-<?php echo ($a['priority'] == 'warning' ? 'ink' : ($a['priority'] == 'info' ? 'accent-teal' : 'error')); ?>);">
                                        <?php echo $a['priority']; ?>
                                    </span>
                                </div>
                                <?php if ($a['reference_id'] && ($a['type'] == 'low_stock' || $a['type'] == 'out_of_stock')): ?>
                                    <a href="manage_variations.php?id=<?php echo $a['reference_id']; ?>" class="button-secondary" style="padding: 4px 12px; font-size: 11px; background: var(--colors-surface-soft);">Manage Inventory</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 12px; min-width: 120px; border-left: 1px solid var(--colors-hairline-soft); padding-left: 20px; margin-left: 20px;">
                            <?php if (!$a['is_read']): ?>
                                <a href="alerts_center.php?action=read&id=<?php echo $a['id']; ?>" class="button-secondary" style="padding: 10px 16px; font-size: 11px; text-align: center; width: 100%; white-space: nowrap;">Mark Read</a>
                            <?php endif; ?>
                            <a href="alerts_center.php?action=delete&id=<?php echo $a['id']; ?>" class="button-secondary" style="padding: 10px 16px; font-size: 11px; border-color: var(--colors-error); color: var(--colors-error) !important; text-align: center; width: 100%; white-space: nowrap;">Delete Message</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
