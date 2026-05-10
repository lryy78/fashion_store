<?php
function syncAlerts($pdo) {
    // 1. Fetch Thresholds
    $settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $low_stock_limit = $settings['low_stock_threshold'] ?? 10;

    // --- AUTO-DISMISSAL LOGIC ---
    // Remove alerts if stock is now healthy
    $pdo->query("DELETE FROM system_alerts WHERE type = 'out_of_stock' AND reference_id IN (
        SELECT product_id FROM product_variations GROUP BY product_id HAVING MIN(stock_quantity) > 0
    )");
    $pdo->query("DELETE FROM system_alerts WHERE type = 'low_stock' AND reference_id IN (
        SELECT product_id FROM product_variations GROUP BY product_id HAVING MIN(stock_quantity) > $low_stock_limit
    )");

    // --- OUT OF STOCK GENERATION ---
    $out_of_stock_grouped = $pdo->query("SELECT p.id as product_id, p.name, GROUP_CONCAT(CONCAT(pv.size, ' / ', pv.color) SEPARATOR ', ') as variations, COUNT(*) as count 
                                         FROM product_variations pv 
                                         JOIN products p ON pv.product_id = p.id 
                                         WHERE pv.stock_quantity = 0 
                                         GROUP BY p.id")->fetchAll();
    foreach ($out_of_stock_grouped as $group) {
        $msg = "Critical: {$group['name']} has {$group['count']} out-of-stock variations.\n\nAffected Variations:\n- " . str_replace(', ', "\n- ", $group['variations']);
        
        // Re-alert ONLY if:
        // 1. No alert (read or unread) exists for this product
        // 2. OR an unread alert exists (we update nothing, NOT EXISTS handles this)
        // 3. OR the last alert is older than 24 hours
        $stmt = $pdo->prepare("INSERT INTO system_alerts (type, priority, message, reference_id) 
                               SELECT 'out_of_stock', 'critical', ?, ? 
                               WHERE NOT EXISTS (SELECT 1 FROM system_alerts WHERE type='out_of_stock' AND reference_id = ? AND is_read = 0)
                               AND NOT EXISTS (SELECT 1 FROM system_alerts WHERE type='out_of_stock' AND reference_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY))");
        $stmt->execute([$msg, $group['product_id'], $group['product_id'], $group['product_id']]);
    }

    // --- LOW STOCK GENERATION ---
    $low_stock_grouped = $pdo->query("SELECT p.id as product_id, p.name, GROUP_CONCAT(CONCAT(pv.size, ' / ', pv.color, ' (', pv.stock_quantity, ')') SEPARATOR ', ') as variations, COUNT(*) as count 
                                      FROM product_variations pv 
                                      JOIN products p ON pv.product_id = p.id 
                                      WHERE pv.stock_quantity > 0 AND pv.stock_quantity <= $low_stock_limit 
                                      GROUP BY p.id")->fetchAll();
    foreach ($low_stock_grouped as $group) {
        $msg = "Warning: {$group['name']} has {$group['count']} variations running low.\n\nAffected Variations:\n- " . str_replace(', ', "\n- ", $group['variations']);
        
        // Re-alert ONLY if:
        // 1. No alert (read or unread) exists for this product with this EXACT message
        // 2. AND No unread alert exists for this product (don't stack unread alerts for same product)
        $stmt = $pdo->prepare("INSERT INTO system_alerts (type, priority, message, reference_id) 
                               SELECT 'low_stock', 'warning', ?, ? 
                               WHERE NOT EXISTS (SELECT 1 FROM system_alerts WHERE type='low_stock' AND reference_id = ? AND is_read = 0)
                               AND NOT EXISTS (SELECT 1 FROM system_alerts WHERE type='low_stock' AND reference_id = ? AND message = ?)");
        $stmt->execute([$msg, $group['product_id'], $group['product_id'], $group['product_id'], $msg]);
    }

    // 4. Sales Trends (Simplified)
    // Compare today's sales to average of last 7 days
    $today_sales = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $avg_sales = $pdo->query("SELECT COUNT(*)/7 FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

    if ($today_sales > $avg_sales * 2 && $today_sales > 5) {
        $msg = "Sales Spike: Current sales are 200% above the 7-day average.";
        $pdo->prepare("INSERT IGNORE INTO system_alerts (type, priority, message) 
                       SELECT 'sales_spike', 'info', ? 
                       WHERE NOT EXISTS (SELECT 1 FROM system_alerts WHERE type='sales_spike' AND is_read = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY))")
            ->execute([$msg]);
    } elseif ($today_sales < $avg_sales * 0.5 && $avg_sales > 10) {
        $msg = "Sales Drop: Current sales are 50% below the 7-day average.";
        $pdo->prepare("INSERT IGNORE INTO system_alerts (type, priority, message) 
                       SELECT 'sales_drop', 'warning', ? 
                       WHERE NOT EXISTS (SELECT 1 FROM system_alerts WHERE type='sales_drop' AND is_read = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY))")
            ->execute([$msg]);
    }
}
?>
