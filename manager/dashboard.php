<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit();
}

$settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$low_stock_limit = max(0, (int)($settings['low_stock_threshold'] ?? 10));

$inventory_stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_products,
        SUM(total_stock = 0) AS stockout_products,
        SUM(total_stock > 0 AND minimum_stock <= ?) AS low_stock_products,
        SUM(total_stock > 0 AND minimum_stock > ?) AS healthy_products
    FROM (
        SELECT
            p.id,
            COALESCE(SUM(pv.stock_quantity), 0) AS total_stock,
            COALESCE(MIN(pv.stock_quantity), 0) AS minimum_stock
        FROM products p
        LEFT JOIN product_variations pv ON pv.product_id = p.id
        WHERE p.status = 'published'
        GROUP BY p.id
    ) inventory
");
$inventory_stmt->execute([$low_stock_limit, $low_stock_limit]);
$inventory = $inventory_stmt->fetch();

$total_products = (int)($inventory['total_products'] ?? 0);
$stockout_products = (int)($inventory['stockout_products'] ?? 0);
$low_stock_products = (int)($inventory['low_stock_products'] ?? 0);
$healthy_products = (int)($inventory['healthy_products'] ?? 0);
$healthy_percent = $total_products > 0 ? round(($healthy_products / $total_products) * 100) : 0;

$urgent_alerts = $pdo->query("
    SELECT * FROM system_alerts
    WHERE is_read = 0
    ORDER BY priority = 'critical' DESC, created_at DESC
    LIMIT 3
")->fetchAll();

$recent_orders = $pdo->query("
    SELECT o.id, o.created_at, o.status, u.username,
           COALESCE((SELECT SUM(quantity) FROM order_items WHERE order_id = o.id), 0) AS item_count
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 6
")->fetchAll();

$top_products = $pdo->query("
    SELECT p.id, p.name, c.name AS category_name, SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN product_variations pv ON pv.id = oi.variation_id
    JOIN products p ON p.id = pv.product_id
    LEFT JOIN categories c ON c.id = p.category_id
    JOIN orders o ON o.id = oi.order_id
    WHERE o.status NOT IN ('cancelled', 'refunded')
    GROUP BY p.id, p.name, c.name
    ORDER BY total_sold DESC
    LIMIT 6
")->fetchAll();

include '../includes/header.php';
?>

<style>
.manager-compact .manager-toolbar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
}
.manager-compact .manager-toolbar h1 {
    margin: 0;
    font-size: 34px;
    letter-spacing: 0;
}
.manager-compact .manager-eyebrow {
    margin-bottom: 4px;
    color: var(--colors-muted);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0;
}
.manager-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}
.manager-kpi {
    min-height: 126px;
    padding: 18px;
    border: 1px solid var(--colors-hairline-soft);
    border-left: 4px solid var(--kpi-color);
    border-radius: 8px;
    background: var(--colors-surface);
    cursor: pointer;
}
.manager-kpi__label {
    color: var(--kpi-color);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0;
}
.manager-kpi__value {
    margin-top: 10px;
    color: var(--colors-ink);
    font-size: 30px;
    font-weight: 700;
    line-height: 1;
}
.manager-kpi__value span {
    color: var(--colors-muted);
    font-size: 12px;
    font-weight: 500;
}
.manager-kpi__hint {
    margin-top: 10px;
    color: var(--colors-muted);
    font-size: 11px;
}
.manager-health-track {
    height: 5px;
    margin-top: 12px;
    overflow: hidden;
    border-radius: 3px;
    background: #eaf7ee;
}
.manager-health-track span {
    display: block;
    height: 100%;
    background: var(--colors-success);
}
.manager-alerts {
    margin-bottom: 18px;
    overflow: hidden;
    border: 1px solid var(--colors-hairline-soft);
    border-radius: 8px;
    background: var(--colors-surface);
}
.manager-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 16px;
    border-bottom: 1px solid var(--colors-hairline-soft);
}
.manager-panel-header h2 {
    margin: 0;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0;
}
.manager-alert-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--colors-hairline-soft);
    font-size: 12px;
}
.manager-alert-row:last-child { border-bottom: 0; }
.manager-alert-row p { margin: 0; line-height: 1.4; }
.manager-content-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
    gap: 16px;
}
.manager-panel {
    min-width: 0;
    overflow: hidden;
    border: 1px solid var(--colors-hairline-soft);
    border-radius: 8px;
    background: var(--colors-surface);
}
.manager-table-scroll {
    max-height: 300px;
    overflow: auto;
}
.manager-panel .data-table {
    min-width: 560px;
    margin: 0;
}
.manager-panel .data-table th,
.manager-panel .data-table td {
    padding: 10px 14px;
    font-size: 12px;
}
.manager-panel .data-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--colors-surface-soft);
}
.manager-empty {
    padding: 28px 16px;
    color: var(--colors-muted);
    font-size: 12px;
    text-align: center;
}
@media (max-width: 1000px) {
    .manager-content-grid { grid-template-columns: 1fr; }
}
@media (max-width: 760px) {
    .manager-kpi-grid { grid-template-columns: 1fr; }
    .manager-kpi { min-height: 0; }
}
</style>

<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main manager-compact fade-in-up">
        <header class="manager-toolbar">
            <div>
                <div class="manager-eyebrow">Operational Overview</div>
                <h1>Inventory Management</h1>
            </div>
            <a href="products_list.php" class="button-primary" style="padding: 10px 16px; font-size: 12px;">Manage inventory</a>
        </header>

        <section class="manager-kpi-grid" aria-label="Inventory summary">
            <div class="manager-kpi" style="--kpi-color: var(--colors-error);" onclick="location.href='products_list.php?stock_status=out'">
                <div class="manager-kpi__label">Stockouts</div>
                <div class="manager-kpi__value"><?php echo $stockout_products; ?> <span>products</span></div>
                <div class="manager-kpi__hint">Published products with no available stock</div>
            </div>
            <div class="manager-kpi" style="--kpi-color: #d97706;" onclick="location.href='products_list.php?stock_status=low'">
                <div class="manager-kpi__label">Low Stock</div>
                <div class="manager-kpi__value"><?php echo $low_stock_products; ?> <span>products</span></div>
                <div class="manager-kpi__hint">At least one variation is at or below <?php echo $low_stock_limit; ?></div>
            </div>
            <div class="manager-kpi" style="--kpi-color: var(--colors-success);" onclick="location.href='products_list.php?stock_status=healthy'">
                <div class="manager-kpi__label">Healthy Inventory</div>
                <div class="manager-kpi__value"><?php echo $healthy_products; ?> <span>of <?php echo $total_products; ?></span></div>
                <div class="manager-health-track"><span style="width: <?php echo $healthy_percent; ?>%;"></span></div>
                <div class="manager-kpi__hint"><?php echo $healthy_percent; ?>% of published products are healthy</div>
            </div>
        </section>

        <?php if ($urgent_alerts): ?>
            <section class="manager-alerts" aria-labelledby="manager-alert-title">
                <div class="manager-panel-header">
                    <h2 id="manager-alert-title">Operational Alerts</h2>
                    <a href="alerts_center.php" class="button-secondary" style="padding: 6px 10px; font-size: 10px;">View all</a>
                </div>
                <?php foreach ($urgent_alerts as $alert): ?>
                    <div class="manager-alert-row">
                        <p><?php echo htmlspecialchars($alert['message']); ?></p>
                        <a href="<?php echo $alert['reference_id'] ? 'manage_variations.php?id=' . (int)$alert['reference_id'] : 'alerts_center.php'; ?>" style="font-size: 11px; font-weight: 600; white-space: nowrap;">Review</a>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <div class="manager-content-grid">
            <section class="manager-panel" aria-labelledby="recent-orders-title">
                <div class="manager-panel-header">
                    <h2 id="recent-orders-title">Recent Orders</h2>
                    <a href="orders_list.php" class="button-secondary" style="padding: 6px 10px; font-size: 10px;">View all</a>
                </div>
                <div class="manager-table-scroll">
                    <?php if ($recent_orders): ?>
                        <table class="data-table">
                            <thead>
                                <tr><th>Order</th><th>Customer</th><th>Items</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr onclick="location.href='order_details.php?id=<?php echo $order['id']; ?>'" style="cursor: pointer;">
                                        <td style="font-family: var(--typography-code-font); font-weight: 600;">#ORD-<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td><?php echo (int)$order['item_count']; ?></td>
                                        <td><span class="badge badge-<?php echo $order['status'] === 'completed' ? 'success' : (in_array($order['status'], ['cancelled', 'refunded'], true) ? 'error' : 'pending'); ?>" style="font-size: 9px;"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="manager-empty">No recent orders.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="manager-panel" aria-labelledby="best-sellers-title">
                <div class="manager-panel-header">
                    <h2 id="best-sellers-title">Best Sellers</h2>
                    <a href="product_analytics.php" class="button-secondary" style="padding: 6px 10px; font-size: 10px;">Analytics</a>
                </div>
                <div class="manager-table-scroll">
                    <?php if ($top_products): ?>
                        <table class="data-table">
                            <thead>
                                <tr><th>Product</th><th>Category</th><th style="text-align: right;">Sold</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td style="color: var(--colors-muted);"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorised'); ?></td>
                                        <td style="text-align: right; font-family: var(--typography-code-font);"><?php echo (int)$product['total_sold']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="manager-empty">No sales activity yet.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
