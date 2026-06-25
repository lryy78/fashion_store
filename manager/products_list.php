<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

// Bulk Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $ids = $_POST['product_ids'] ?? [];
    $action = $_POST['bulk_action'];
    
    if (!empty($ids)) {
        if ($action == 'publish') {
            $id_list = implode(',', array_map('intval', $ids));
            $pdo->query("UPDATE products SET status = 'published' WHERE id IN ($id_list)");
        } elseif ($action == 'hide') {
            $id_list = implode(',', array_map('intval', $ids));
            $pdo->query("UPDATE products SET status = 'draft' WHERE id IN ($id_list)");
        } elseif ($action == 'delete') {
            foreach ($ids as $pid) {
                deleteProductCompletely($pid);
            }
        }
        header("Location: products_list.php?bulk_success=1");
        exit();
    }
}

// Single Action Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    deleteProductCompletely($_GET['id']);
    header("Location: products_list.php?msg=Product removed");
    exit();
}

function deleteProductCompletely($pid) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        // Delete variations
        $pdo->prepare("DELETE FROM product_variations WHERE product_id = ?")->execute([$pid]);
        // Delete images
        $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$pid]);
        // Delete from cart
        $pdo->prepare("DELETE FROM cart WHERE variation_id IN (SELECT id FROM product_variations WHERE product_id = ?)")->execute([$pid]);
        // Delete product
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$pid]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
}

// Filters
$search = $_GET['search'] ?? '';
$filter_category = $_GET['category'] ?? 'all';
$filter_stock = $_GET['stock_status'] ?? 'all';
$filter_status = $_GET['pub_status'] ?? 'all';

// Thresholds
$settings = $pdo->query("SELECT setting_key, setting_value FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$low_stock_limit = $settings['low_stock_threshold'] ?? 10;

// Base Query
$query = "SELECT p.*, c.name as category_name, 
          (SELECT SUM(stock_quantity) FROM product_variations WHERE product_id = p.id) as total_stock,
          (SELECT MIN(stock_quantity) FROM product_variations WHERE product_id = p.id) as min_stock,
          (SELECT SUM(oi.quantity)
           FROM order_items oi
           JOIN product_variations pv ON oi.variation_id = pv.id
           JOIN orders o ON oi.order_id = o.id
           WHERE pv.product_id = p.id AND o.status NOT IN ('cancelled','refunded')) as total_sales
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";

$params = [];
if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.id = ?)";
    $params[] = "%$search%";
    $params[] = (int)$search;
}
if ($filter_category != 'all') {
    $query .= " AND p.category_id = ?";
    $params[] = (int)$filter_category;
}
if ($filter_status != 'all') {
    $query .= " AND p.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Filter by Stock Status in PHP (easier due to aggregate min_stock)
if ($filter_stock != 'all') {
    $products = array_filter($products, function($p) use ($filter_stock, $low_stock_limit) {
        $total_stock = (int)($p['total_stock'] ?? 0);
        if ($filter_stock == 'out') return $total_stock <= 0;
        if ($filter_stock == 'low') return $total_stock > 0 && $total_stock <= $low_stock_limit;
        if ($filter_stock == 'healthy') return $total_stock > $low_stock_limit;
        return true;
    });
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

include '../includes/header.php';
?>

<style>
.inventory-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.inventory-table th {
    background: var(--colors-surface-soft);
    padding: 16px 20px;
    text-align: left;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 700;
    color: var(--colors-muted);
    border-bottom: 1px solid var(--colors-hairline);
}
.inventory-table td {
    padding: 20px;
    border-bottom: 1px solid var(--colors-hairline-soft);
    vertical-align: middle;
}
.variation-row {
    background: #fafafa;
    display: none;
}
.variation-row td {
    padding: 12px 20px 12px 60px;
    font-size: 13px;
    border-bottom: 1px solid var(--colors-hairline-soft);
}
.status-badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.status-healthy { background: #ecfdf5; color: #059669; }
.status-low { background: #fffbeb; color: #d97706; }
.status-out { background: #fef2f2; color: #dc2626; }

.pub-published { background: #eff6ff; color: #2563eb; }
.pub-draft { background: #f3f4f6; color: #4b5563; }
.pub-scheduled { background: #fdf4ff; color: #a21caf; }
.pub-expired { background: #71717a; color: #fff; }

.action-dropdown {
    position: relative;
    display: inline-block;
}
.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #fff;
    min-width: 160px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    z-index: 100;
    border-radius: 8px;
    border: 1px solid var(--colors-hairline);
    overflow: hidden;
}
.dropdown-content a, .dropdown-content button {
    color: var(--colors-ink);
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    font-size: 13px;
    text-align: left;
    width: 100%;
    background: none;
    border: none;
    cursor: pointer;
}
.dropdown-content a:hover, .dropdown-content button:hover {
    background-color: var(--colors-surface-soft);
}
.action-dropdown:hover .dropdown-content { display: block; }

/* Fix bulk actions bar button hover — keeps text white on dark bg */
#bulk-actions-bar .button-secondary:hover {
    background: rgba(255, 255, 255, 0.2) !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
    color: #fff !important;
}
#bulk-actions-bar .button-secondary[style*="colors-error"]:hover {
    background: rgba(198, 69, 69, 0.8) !important;
    border-color: var(--colors-error) !important;
    color: #fff !important;
}

.expand-btn {
    cursor: pointer;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background 0.2s;
}
.expand-btn:hover { background: var(--colors-surface-soft); }

.mini-stat {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.mini-stat-label { font-size: 10px; color: var(--colors-muted); text-transform: uppercase; }
.mini-stat-value { font-size: 13px; font-weight: 600; font-family: var(--typography-code-font); }
</style>

<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Product Management</div>
                <h1 style="margin: 0; font-size: 40px;">Inventory Catalog</h1>
            </div>
            <a href="add_product.php" class="button-primary">+ New Product</a>
        </header>

        <!-- Search & Filters -->
        <div class="surface-card" style="padding: 24px; margin-bottom: 32px; border: 1px solid var(--colors-hairline);">
            <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 120px; gap: 16px; align-items: flex-end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">Search by Name or ID</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="e.g. Jeans or 101" class="form-input" style="padding: 10px;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">Category</label>
                    <select name="category" class="form-input" style="padding: 10px;">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">Stock Status</label>
                    <select name="stock_status" class="form-input" style="padding: 10px;">
                        <option value="all">All Levels</option>
                        <option value="healthy" <?php echo $filter_stock == 'healthy' ? 'selected' : ''; ?>>Healthy</option>
                        <option value="low" <?php echo $filter_stock == 'low' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="out" <?php echo $filter_stock == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">Publishing</label>
                    <select name="pub_status" class="form-input" style="padding: 10px;">
                        <option value="all">All Status</option>
                        <option value="published" <?php echo $filter_status == 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="draft" <?php echo $filter_status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="scheduled" <?php echo $filter_status == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    </select>
                </div>
                <button type="submit" class="button-secondary" style="padding: 12px;">Filter</button>
                <?php if (!empty($search) || $filter_category != 'all' || $filter_stock != 'all' || $filter_status != 'all'): ?>
                    <a href="products_list.php" class="button-secondary" style="padding: 12px 24px; text-decoration: none;">Reset Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <form method="POST" id="bulk-form">
            <!-- Bulk Actions Bar -->
            <div id="bulk-actions-bar" style="display: none; background: var(--colors-ink); color: #fff; padding: 16px 24px; border-radius: 8px; margin-bottom: 24px; align-items: center; gap: 24px;">
                <span id="selected-count" style="font-size: 14px; font-weight: 600;">0 items selected</span>
                <div style="display: flex; gap: 12px; margin-left: auto;">
                    <button type="submit" name="bulk_action" value="publish" class="button-secondary" style="border-color: rgba(255,255,255,0.2); color: #fff; background: rgba(255,255,255,0.1);">Publish</button>
                    <button type="submit" name="bulk_action" value="hide" class="button-secondary" style="border-color: rgba(255,255,255,0.2); color: #fff; background: rgba(255,255,255,0.1);">Hide</button>
                    <button type="submit" name="bulk_action" value="delete" class="button-secondary" style="border-color: var(--colors-error); color: #fff; background: var(--colors-error);" onclick="return confirm('Permanently delete selected products?')">Delete</button>
                </div>
            </div>

            <table class="inventory-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="select-all"></th>
                        <th style="width: 40px;"></th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Stock Status</th>
                        <th>Publishing</th>
                        <th>Analytics</th>
                        <th>Price</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): 
                        // Stock Status logic
                        $total_stock = (int)($p['total_stock'] ?? 0);
                        if ($total_stock <= 0) {
                            $stock_cls = 'status-out'; $stock_lbl = 'Out of Stock';
                        } elseif ($total_stock <= $low_stock_limit) {
                            $stock_cls = 'status-low'; $stock_lbl = 'Low Stock';
                        } else {
                            $stock_cls = 'status-healthy'; $stock_lbl = 'Healthy';
                        }

                        $sales = $p['total_sales'] ?? 0;
                        $conversion = ($p['views'] > 0) ? round(($sales / $p['views']) * 100, 1) : 0;
                    ?>
                        <tr class="product-row" data-id="<?php echo $p['id']; ?>">
                            <td><input type="checkbox" name="product_ids[]" value="<?php echo $p['id']; ?>" class="product-check"></td>
                            <td>
                                <div class="expand-btn" onclick="toggleVariations(<?php echo $p['id']; ?>)">▶</div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="font-size: 11px; color: var(--colors-muted); font-family: var(--typography-code-font);">#<?php echo $p['id']; ?></div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($p['name']); ?></div>
                                </div>
                            </td>
                            <td style="font-size: 13px;"><?php echo htmlspecialchars($p['category_name']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $stock_cls; ?>">
                                    <?php echo $stock_lbl; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge pub-<?php echo $p['status']; ?>">
                                    <?php echo ucfirst($p['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 16px;">
                                    <div class="mini-stat">
                                        <span class="mini-stat-label">Views</span>
                                        <span class="mini-stat-value"><?php echo $p['views']; ?></span>
                                    </div>
                                    <div class="mini-stat">
                                        <span class="mini-stat-label">Sales</span>
                                        <span class="mini-stat-value"><?php echo $sales; ?></span>
                                    </div>
                                    <div class="mini-stat">
                                        <span class="mini-stat-label">Conv.</span>
                                        <span class="mini-stat-value"><?php echo $conversion; ?>%</span>
                                    </div>
                                </div>
                            </td>
                            <td style="font-family: var(--typography-code-font); font-weight: 600;">RM <?php echo number_format($p['price'], 2); ?></td>
                            <td style="text-align: right;">
                                <div class="action-dropdown">
                                    <button type="button" class="button-secondary" style="padding: 6px 12px; font-size: 12px;">Actions ▼</button>
                                    <div class="dropdown-content">
                                        <a href="edit_product.php?id=<?php echo $p['id']; ?>">✎ Edit Details</a>
                                        <a href="manage_variations.php?id=<?php echo $p['id']; ?>">📦 Manage Stock</a>
                                        <a href="manage_images.php?id=<?php echo $p['id']; ?>">🖼 Photos</a>
                                        <a href="products_list.php?action=delete&id=<?php echo $p['id']; ?>" style="color: var(--colors-error);" onclick="return confirm('Delete this product?')">🗑 Remove</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <!-- Hidden Variations Row -->
                        <tr id="variations-<?php echo $p['id']; ?>" class="variation-row">
                            <td colspan="9">
                                <div style="padding: 10px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                                    <?php 
                                    $v_stmt = $pdo->prepare("SELECT * FROM product_variations WHERE product_id = ?");
                                    $v_stmt->execute([$p['id']]);
                                    $vars = $v_stmt->fetchAll();
                                    foreach ($vars as $v):
                                        $v_cls = ($v['stock_quantity'] == 0) ? 'color: var(--colors-error); font-weight: 700;' : '';
                                    ?>
                                        <div style="background: #fff; padding: 12px; border-radius: 6px; border: 1px solid var(--colors-hairline); display: flex; justify-content: space-between;">
                                            <span><?php echo $v['size']; ?> / <?php echo $v['color']; ?></span>
                                            <span style="<?php echo $v_cls; ?>"><?php echo $v['stock_quantity']; ?> left</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

<script>
function toggleVariations(id) {
    const row = document.getElementById('variations-' + id);
    const btn = document.querySelector(`tr[data-id="${id}"] .expand-btn`);
    if (row.style.display === 'table-row') {
        row.style.display = 'none';
        btn.innerText = '▶';
        btn.style.transform = 'rotate(0deg)';
    } else {
        row.style.display = 'table-row';
        btn.innerText = '▼';
    }
}

// Bulk Selection
const selectAll = document.getElementById('select-all');
const productChecks = document.querySelectorAll('.product-check');
const bulkBar = document.getElementById('bulk-actions-bar');
const selectedCount = document.getElementById('selected-count');

function updateBulkBar() {
    const checked = document.querySelectorAll('.product-check:checked');
    if (checked.length > 0) {
        bulkBar.style.display = 'flex';
        selectedCount.innerText = checked.length + ' items selected';
    } else {
        bulkBar.style.display = 'none';
    }
}

selectAll.addEventListener('change', () => {
    productChecks.forEach(cb => cb.checked = selectAll.checked);
    updateBulkBar();
});

productChecks.forEach(cb => {
    cb.addEventListener('change', updateBulkBar);
});
</script>

<?php include '../includes/footer.php'; ?>
