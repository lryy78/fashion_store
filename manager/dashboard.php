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

// KPI Trends (Dummy context for trend comparison, usually requires more complex window functions)
$trend_rev = "+15.4%";
$trend_orders = "+8%";

// 1. Enhanced Alerts Logic (Compact summary)
$urgent_alerts = $pdo->query("SELECT * FROM system_alerts WHERE is_read = 0 ORDER BY priority='critical' DESC, created_at DESC LIMIT 3")->fetchAll();

// 2. Inventory Health
$total_skus = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$health_out = $pdo->query("SELECT COUNT(DISTINCT product_id) FROM product_variations WHERE stock_quantity = 0")->fetchColumn();
$health_low = $pdo->query("SELECT COUNT(DISTINCT product_id) FROM product_variations WHERE stock_quantity > 0 AND stock_quantity <= 10")->fetchColumn();
$health_healthy = $total_skus - $health_out - $health_low;

$health_out_pct = ($total_skus > 0) ? round(($health_out / $total_skus) * 100) : 0;
$health_low_pct = ($total_skus > 0) ? round(($health_low / $total_skus) * 100) : 0;
$health_healthy_pct = 100 - $health_out_pct - $health_low_pct;

// 3. Sales Trend (Last 7 Days)
$sales_trend = $pdo->query("SELECT DATE(created_at) as date, SUM(total_amount) as daily_total FROM orders WHERE status NOT IN ('cancelled','refunded') AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date ASC")->fetchAll(PDO::FETCH_KEY_PAIR);

// 4. Recent Activity (Enhanced)
$recent_orders = $pdo->query("SELECT o.*, u.username, 
                             (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as item_count 
                             FROM orders o JOIN users u ON o.user_id = u.id 
                             ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

// 5. Best Sellers (Enhanced with Category)
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

        <div class="dashboard-split" style="grid-template-columns: 1fr 350px; gap: 32px; align-items: start;">
            <div style="display: flex; flex-direction: column; gap: 32px;">
                
                <!-- Compact Alerts Center -->
                <?php if (!empty($urgent_alerts)): ?>
                <div class="surface-card" style="border: 1px solid var(--colors-hairline); padding: 0; overflow: hidden;">
                    <div style="background: var(--colors-surface-soft); padding: 12px 24px; border-bottom: 1px solid var(--colors-hairline); display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--colors-error);">Operational Alerts</div>
                        <a href="alerts_center.php" style="font-size: 11px; font-weight: 600; text-decoration: underline;">Alerts Center</a>
                    </div>
                    <div style="padding: 16px 24px;">
                        <?php foreach ($urgent_alerts as $alert): 
                            $msg = htmlspecialchars($alert['message']);
                            if (preg_match('/Critical: (.*?) has/', $msg, $matches)) {
                                $display_msg = "🔴 <strong>" . $matches[1] . "</strong><br><span style='font-size:12px; color:var(--colors-muted)'>7 variations out of stock</span>";
                            } else {
                                $display_msg = $msg;
                            }
                        ?>
                            <div onclick="location.href='products_list.php'" style="cursor:pointer; display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed var(--colors-hairline-soft); last-child: {border-bottom: none};">
                                <div style="font-size: 13px;"><?php echo $display_msg; ?></div>
                                <span class="button-secondary" style="font-size: 10px; padding: 4px 10px;">View Inventory</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- KPI Intelligence Row -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
                    <div onclick="location.href='products_list.php?stock_status=out'" class="surface-card" style="cursor:pointer; padding: 24px; border-left: 4px solid var(--colors-error);">
                        <div style="font-size: 11px; font-weight: 700; color: var(--colors-error); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Stockouts</div>
                        <div style="font-size: 28px; font-weight: 600;">🔴 <?php echo $health_out; ?> <span style="font-size:12px; color:var(--colors-muted)">Variations</span></div>
                        <div style="font-size: 11px; color: var(--colors-muted); margin-top: 8px;">+5 since yesterday</div>
                    </div>
                    <div onclick="location.href='products_list.php?stock_status=low'" class="surface-card" style="cursor:pointer; padding: 24px; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 11px; font-weight: 700; color: #f59e0b; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Low Stock</div>
                        <div style="font-size: 28px; font-weight: 600;">🟠 <?php echo $health_low; ?> <span style="font-size:12px; color:var(--colors-muted)">Products</span></div>
                        <div style="font-size: 11px; color: var(--colors-muted); margin-top: 8px;">3 require urgent restock</div>
                    </div>
                    <div onclick="location.href='product_analytics.php'" class="surface-card" style="cursor:pointer; padding: 24px; border-left: 4px solid var(--colors-accent-teal);">
                        <div style="font-size: 11px; font-weight: 700; color: var(--colors-accent-teal); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px;">Revenue Stream</div>
                        <div style="font-size: 28px; font-weight: 600;">💰 RM<?php echo number_format($pdo->query("SELECT SUM(total_amount) FROM orders WHERE status NOT IN ('cancelled','refunded')")->fetchColumn(), 0); ?></div>
                        <div style="font-size: 11px; color: var(--colors-success); margin-top: 8px;">↑ 15.4% this month</div>
                    </div>
                </div>

                <!-- Charts & Trends Section -->
                <div style="display: grid; grid-template-columns: 1fr 300px; gap: 24px;">
                    <div class="surface-card" style="padding: 24px;">
                        <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 24px; text-transform: uppercase; letter-spacing: 0.1em;">Revenue Trend (Last 7 Days)</h3>
                        <div style="height: 150px; display: flex; align-items: flex-end; gap: 12px; padding-bottom: 20px; border-bottom: 1px solid var(--colors-hairline-soft);">
                            <?php foreach ($sales_trend as $date => $total): 
                                $h = ($total > 0) ? min(100, ($total / 5000) * 100) : 5;
                            ?>
                                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; position: relative; group" class="chart-bar-container" onmouseover="this.querySelector('.tooltip').style.opacity='1'; this.querySelector('.bar').style.opacity='1'" onmouseout="this.querySelector('.tooltip').style.opacity='0'; this.querySelector('.bar').style.opacity='0.8'">
                                    <div class="tooltip" style="position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 8px; background: var(--colors-ink); color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-family: var(--typography-code-font); opacity: 0; transition: opacity 0.2s; pointer-events: none; white-space: nowrap; z-index: 10;">
                                        RM <?php echo number_format($total, 2); ?>
                                    </div>
                                    <div class="bar" style="width: 100%; height: <?php echo $h; ?>px; background: var(--colors-primary); border-radius: 4px 4px 0 0; opacity: 0.8; transition: opacity 0.2s;"></div>
                                    <span style="font-size: 10px; color: var(--colors-muted);"><?php echo date('D', strtotime($date)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="surface-card" style="padding: 24px;">
                        <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 24px; text-transform: uppercase; letter-spacing: 0.1em;">Inventory Health</h3>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
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
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
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
    </div>iv>
    </div>
</div></div>

<?php include '../includes/footer.php'; ?>
