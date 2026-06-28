<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

// Time Range Filter
$range = $_GET['range'] ?? '7';
$interval = "INTERVAL $range DAY";
if ($range == 'month') $interval = "INTERVAL 1 MONTH";


// 1. Enhanced Alerts Logic (Compact summary)
$urgent_alerts = $pdo->query("SELECT * FROM system_alerts WHERE is_read = 0 ORDER BY priority='critical' DESC, created_at DESC LIMIT 3")->fetchAll();

// Fetch System Settings
$settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$low_stock_threshold = (int)($settings['low_stock_threshold'] ?? 10);
$overstock_threshold = (int)($settings['overstock_threshold'] ?? 100);

// 2. Inventory Health
$total_skus = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$health_out = $pdo->query("SELECT COUNT(DISTINCT product_id) FROM product_variations WHERE stock_quantity = 0")->fetchColumn();
$health_low = $pdo->query("SELECT COUNT(DISTINCT product_id) FROM product_variations WHERE stock_quantity > 0 AND stock_quantity <= $low_stock_threshold")->fetchColumn();
$health_healthy = $total_skus - $health_out - $health_low;

$health_out_pct = ($total_skus > 0) ? round(($health_out / $total_skus) * 100) : 0;
$health_low_pct = ($total_skus > 0) ? round(($health_low / $total_skus) * 100) : 0;
$health_healthy_pct = 100 - $health_out_pct - $health_low_pct;

// KPI Intelligence Calculations
$stockout_recent = $pdo->query("SELECT COUNT(*) FROM system_alerts WHERE type = 'out_of_stock' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();
$stockout_text = $stockout_recent > 0 ? "+$stockout_recent since yesterday" : "No recent stockouts";

$low_stock_urgent = $pdo->query("SELECT COUNT(DISTINCT product_id) FROM product_variations WHERE stock_quantity > 0 AND stock_quantity <= " . max(1, floor($low_stock_threshold / 2)))->fetchColumn();
$low_stock_text = $low_stock_urgent > 0 ? "$low_stock_urgent require urgent restock" : "Threshold: <= $low_stock_threshold";


//

// 3. Recent Activity (Enhanced)
$recent_orders = $pdo->query("SELECT o.*, u.username, 
                             (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as item_count 
                             FROM orders o JOIN users u ON o.user_id = u.id 
                             ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

// 4. Best Sellers (Enhanced with Category)
$top_products = $pdo->query("SELECT p.name, c.name as category_name, SUM(oi.quantity) as total_sold, SUM(oi.price * oi.quantity) as total_revenue 
                             FROM order_items oi 
                             JOIN product_variations pv ON oi.variation_id = pv.id 
                             JOIN products p ON pv.product_id = p.id 
                             LEFT JOIN categories c ON p.category_id = c.id
                             JOIN orders o ON oi.order_id = o.id
                             WHERE o.status NOT IN ('cancelled','refunded')
                             GROUP BY p.id ORDER BY total_sold DESC LIMIT 5")->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main fade-in-up">
        <!-- Dashboard Toolbar -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
            <header>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Operational Overview</div>
                <h1 style="margin: 0; font-size: 40px;">Inventory Management</h1>
            </header>
            <div style="display: flex; gap: 12px; align-items: center;">
                <!-- Filter removed per request -->
            </div>
        </div>

        <div class="dashboard-split" style="grid-template-columns: 1fr; gap: 32px; align-items: start;">
            <div style="display: flex; flex-direction: column; gap: 32px;">

                <!-- KPI Intelligence Row -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
                    <div onclick="location.href='products_list.php?stock_status=out'" class="surface-card" style="cursor:pointer; padding: 24px; border-left: 4px solid var(--colors-error);">
                        <div style="font-size: 11px; font-weight: 700; color: var(--colors-error); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Stockouts</div>
                        <div style="font-size: 28px; font-weight: 600;">🔴 <?php echo $health_out; ?> <span style="font-size:12px; color:var(--colors-muted)">Variations</span></div>
                        <div style="font-size: 11px; color: var(--colors-muted); margin-top: 8px;"><?php echo htmlspecialchars($stockout_text); ?></div>
                    </div>
                    <div onclick="location.href='products_list.php?stock_status=low'" class="surface-card" style="cursor:pointer; padding: 24px; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 11px; font-weight: 700; color: #f59e0b; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Low Stock</div>
                        <div style="font-size: 28px; font-weight: 600;">🟠 <?php echo $health_low; ?> <span style="font-size:12px; color:var(--colors-muted)">Products</span></div>
                        <div style="font-size: 11px; color: var(--colors-muted); margin-top: 8px;"><?php echo htmlspecialchars($low_stock_text); ?></div>
                    </div>
                    <div class="surface-card" style="padding: 24px; border-left: 4px solid #3b82f6;">
                        <div style="font-size: 11px; font-weight: 700; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Inventory Health</div>
                        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 12px;">
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                                    <span>Healthy</span>
                                    <span><?php echo $health_healthy_pct; ?>%</span>
                                </div>
                                <div style="height: 6px; background: #f0fdf4; border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?php echo $health_healthy_pct; ?>%; height: 100%; background: #16a34a;"></div>
                                </div>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                                    <span>Low Stock</span>
                                    <span><?php echo $health_low_pct; ?>%</span>
                                </div>
                                <div style="height: 6px; background: #fffbeb; border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?php echo $health_low_pct; ?>%; height: 100%; background: #d97706;"></div>
                                </div>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                                    <span>Out of Stock</span>
                                    <span><?php echo $health_out_pct; ?>%</span>
                                </div>
                                <div style="height: 6px; background: #fef2f2; border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?php echo $health_out_pct; ?>%; height: 100%; background: #dc2626;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <!-- Side-by-Side Activity and Sellers -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                    <!-- Recent Activity -->
                    <div class="table-container" style="margin: 0;">
                        <div style="padding: 20px; border-bottom: 1px solid var(--colors-hairline-soft); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin: 0;">Recent Activity</h3>
                            <a href="orders_list.php" class="button-secondary" style="padding: 6px 12px; font-size: 11px;">View All</a>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr onclick="location.href='order_details.php?id=<?php echo $order['id']; ?>'" style="cursor:pointer;">
                                        <td style="font-family: var(--typography-code-font); font-size: 12px; font-weight: 600;">#ORD-<?php echo $order['id']; ?></td>
                                        <td style="font-weight: 500; font-size: 12px;"><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td style="font-family: var(--typography-code-font); font-size: 12px;">RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo ($order['status'] == 'completed' ? 'success' : ($order['status'] == 'shipped' ? 'primary' : (in_array($order['status'], ['cancelled', 'refunded']) ? 'error' : 'pending'))); ?>" style="font-size: 10px;">
                                                <?php echo htmlspecialchars($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Best Sellers -->
                    <div class="surface-card" style="padding: 0; border: 1px solid var(--colors-hairline); overflow: hidden;">
                        <div style="background: var(--colors-surface-soft); padding: 20px; border-bottom: 1px solid var(--colors-hairline); display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="font-size: 14px; font-weight: 700; margin: 0; text-transform: uppercase; letter-spacing: 0.1em;">Best Sellers</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th style="text-align: center;">Pcs</th>
                                    <th style="text-align: right;">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $tp): ?>
                                    <tr onclick="location.href='product_analytics.php'" style="cursor:pointer;">
                                        <td style="font-weight: 600; font-size: 12px;"><?php echo htmlspecialchars($tp['name']); ?></td>
                                        <td style="font-size: 12px; color: var(--colors-muted);"><?php echo htmlspecialchars($tp['category_name'] ?? 'N/A'); ?></td>
                                        <td style="text-align: center; font-size: 12px; font-family: var(--typography-code-font);"><?php echo $tp['total_sold']; ?></td>
                                        <td style="text-align: right; font-family: var(--typography-code-font); font-size: 12px;">RM<?php echo number_format($tp['total_revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
