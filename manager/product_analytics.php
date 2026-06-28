<?php
session_start();
require_once '../config/db.php';
require_once '../includes/sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'manager') {
    header("Location: ../login.php");
    exit();
}

// Filters
$filter_month_from = $_GET['month_from'] ?? date('Y-m', strtotime('-3 months'));
$filter_month_to = $_GET['month_to'] ?? date('Y-m');
$filter_category = $_GET['category'] ?? 'all';

// Calculate start and end of the selected month range
$start_date = $filter_month_from . '-01';
$end_date = date('Y-m-t', strtotime($filter_month_to . '-01'));

// Shared time-range constraint
$time_range = " AND o.created_at BETWEEN " . $pdo->quote($start_date . ' 00:00:00') . " AND " . $pdo->quote($end_date . ' 23:59:59');

// 1. Top Selling Products
$top_where = $time_range;
if ($filter_category != 'all') $top_where .= " AND p.category_id = " . (int)$filter_category;

$top_selling_query = "SELECT p.name, SUM(oi.quantity) as total_sold 
                      FROM order_items oi 
                      JOIN orders o ON oi.order_id = o.id
                      JOIN product_variations pv ON oi.variation_id = pv.id 
                      JOIN products p ON pv.product_id = p.id 
                      WHERE o.status NOT IN ('cancelled','refunded') $top_where
                      GROUP BY p.id ORDER BY total_sold DESC LIMIT 5";
$top_selling = $pdo->query($top_selling_query)->fetchAll();

// 2. Most Viewed Products
$view_where = ($filter_category != 'all') ? " WHERE category_id = " . (int)$filter_category : "";
$most_viewed = $pdo->query("SELECT name, views FROM products $view_where ORDER BY views DESC LIMIT 5")->fetchAll();

// 3. Low Conversion Products
$conv_p_where = ($filter_category != 'all') ? " AND p.category_id = " . (int)$filter_category : "";

$conversion_query = "SELECT p.name, p.views, IFNULL(SUM(oi.total_sold), 0) as total_sold, 
                     (IFNULL(SUM(oi.total_sold), 0) / NULLIF(p.views, 0)) * 100 as conversion_rate
                     FROM products p
                     LEFT JOIN (
                        SELECT pv.product_id, SUM(oi.quantity) as total_sold
                        FROM order_items oi 
                        JOIN orders o ON oi.order_id = o.id 
                        JOIN product_variations pv ON oi.variation_id = pv.id
                        WHERE o.status NOT IN ('cancelled','refunded') $time_range
                        GROUP BY pv.product_id
                     ) oi ON p.id = oi.product_id
                     WHERE p.views > 10 $conv_p_where
                     GROUP BY p.id
                     ORDER BY conversion_rate ASC LIMIT 5";
$low_conversion = $pdo->query($conversion_query)->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

include '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="dashboard-layout">
    <?php renderSidebar('manager'); ?>

    <div class="dashboard-main fade-in-up">
        <header style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px;">
            <div>
                <div style="font-size: 14px; color: var(--colors-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; font-weight: 600;">Performance Insights</div>
                <h1 style="margin: 0; font-size: 40px;">Product Analytics</h1>
            </div>
        </header>

        <!-- Filters Bar -->
        <div class="surface-card" style="padding: 24px; margin-bottom: 40px; border: 1px solid var(--colors-hairline);">
            <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto auto; gap: 20px; align-items: flex-end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">From Month</label>
                    <input type="month" name="month_from" value="<?php echo $filter_month_from; ?>" class="form-input" style="padding: 10px;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">To Month</label>
                    <input type="month" name="month_to" value="<?php echo $filter_month_to; ?>" class="form-input" style="padding: 10px;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label" style="font-size: 11px;">Category</label>
                    <select name="category" class="form-input" style="padding: 10px;">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $filter_category == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="button-primary" style="padding: 12px;">Apply Analysis</button>
                <a href="product_analytics.php" class="button-secondary" style="padding: 12px 24px; text-decoration: none;">Reset Filters</a>
            </form>
        </div>

        <!-- Row 1: Top Selling (Doughnut) + Most Viewed (Bar) -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- Top Selling Doughnut -->
            <div class="surface-card" style="padding: 24px; display: flex; flex-direction: column;">
                <h3 style="font-size: 16px; margin-bottom: 16px;">Top Selling Products</h3>
                <div style="position: relative; flex-grow: 1; min-height: 0; height: 280px;">
                    <canvas id="topSellingChart"></canvas>
                </div>
            </div>

            <!-- Most Viewed Bar Chart -->
            <div class="surface-card" style="padding: 24px; display: flex; flex-direction: column;">
                <h3 style="font-size: 16px; margin-bottom: 16px;">Most Viewed Products</h3>
                <div style="position: relative; flex-grow: 1; min-height: 0; height: 280px;">
                    <canvas id="mostViewedChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Row 2: Low Conversion (Horizontal Bar) -->
        <div style="margin-bottom: 24px;">
            <div class="surface-card" style="padding: 24px; display: flex; flex-direction: column;">
                <h3 style="font-size: 16px; margin-bottom: 16px;">Low Conversion Items</h3>
                <p style="font-size: 11px; color: var(--colors-muted); margin-bottom: 12px;">Products with >10 views and lowest checkout rates. Conversion = (Sales ÷ Views) × 100</p>
                <div style="position: relative; height: 250px;">
                    <canvas id="lowConversionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// === Top Selling Doughnut ===
const topSellingCtx = document.getElementById('topSellingChart').getContext('2d');
new Chart(topSellingCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($top_selling, 'name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_map('intval', array_column($top_selling, 'total_sold'))); ?>,
            backgroundColor: ['#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#ff9ff3'],
            borderWidth: 2,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 14, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' }
            },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return ctx.label + ': ' + ctx.parsed + ' units';
                    }
                }
            }
        }
    }
});

// Helper: wrap long labels into multi-line arrays for Chart.js
function wrapLabel(str, maxWidth) {
    if (str.length <= maxWidth) return str;
    const words = str.split(' ');
    const lines = [];
    let current = '';
    words.forEach(w => {
        if ((current + ' ' + w).trim().length > maxWidth) {
            lines.push(current.trim());
            current = w;
        } else {
            current = (current + ' ' + w).trim();
        }
    });
    if (current) lines.push(current.trim());
    return lines;
}

// === Most Viewed Bar ===
const mostViewedCtx = document.getElementById('mostViewedChart').getContext('2d');
new Chart(mostViewedCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($most_viewed, 'name')); ?>.map(l => wrapLabel(l, 16)),
        datasets: [{
            label: 'Page Views',
            data: <?php echo json_encode(array_map('intval', array_column($most_viewed, 'views'))); ?>,
            backgroundColor: ['#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe'],
            borderRadius: 6,
            barPercentage: 0.6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return ctx.parsed.x.toLocaleString() + ' views';
                    }
                }
            }
        },
        scales: {
            x: {
                title: { display: true, text: 'Views', font: { weight: 'bold' } },
                grid: { color: 'rgba(0,0,0,0.04)', drawBorder: true, drawOnChartArea: true },
                border: { display: true, color: '#000000', width: 1 },
                ticks: { font: { size: 11 } }
            },
            y: {
                title: { display: true, text: 'Product', font: { weight: 'bold' } },
                grid: { display: false, drawBorder: true, drawOnChartArea: false },
                border: { display: true, color: '#000000', width: 1 },
                ticks: {
                    font: { size: 11 },
                    autoSkip: false
                }
            }
        }
    }
});

// === Low Conversion Horizontal Bar ===
const lowConvCtx = document.getElementById('lowConversionChart').getContext('2d');
new Chart(lowConvCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($low_conversion, 'name')); ?>.map(l => wrapLabel(l, 14)),
        datasets: [
            {
                label: 'Views',
                data: <?php echo json_encode(array_map('intval', array_column($low_conversion, 'views'))); ?>,
                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                borderColor: '#6366f1',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.6,
                categoryPercentage: 0.5,
                maxBarThickness: 50,
                grouped: false
            },
            {
                label: 'Sales',
                data: <?php echo json_encode(array_map('intval', array_column($low_conversion, 'total_sold'))); ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderColor: '#ef4444',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.6,
                categoryPercentage: 0.5,
                maxBarThickness: 50,
                grouped: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                align: 'end',
                labels: { padding: 16, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' }
            },
            tooltip: {
                callbacks: {
                    afterBody: function(ctx) {
                        const idx = ctx[0].dataIndex;
                        const rates = <?php echo json_encode(array_map(function($p) { return round($p['conversion_rate'], 2); }, $low_conversion)); ?>;
                        return 'Conversion: ' + rates[idx] + '%';
                    }
                }
            }
        },
        scales: {
            x: {
                title: { display: true, text: 'Product', font: { weight: 'bold' } },
                grid: { display: false, drawBorder: true, drawOnChartArea: false },
                border: { display: true, color: '#000000', width: 1 },
                ticks: {
                    font: { size: 11 },
                    autoSkip: false,
                    maxRotation: 0
                }
            },
            y: {
                title: { display: true, text: 'Views', font: { weight: 'bold' } },
                grid: { color: 'rgba(0,0,0,0.04)', drawBorder: true, drawOnChartArea: true },
                border: { display: true, color: '#000000', width: 1 },
                ticks: { font: { size: 11 } }
            }
        }
    },
    plugins: [{
        id: 'conversionLabels',
        afterDatasetsDraw(chart, args, pluginOptions) {
            const { ctx } = chart;
            const rates = <?php echo json_encode(array_map(function($p) { return round($p['conversion_rate'], 1); }, $low_conversion)); ?>;
            const meta = chart.getDatasetMeta(0);
            
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#4f46e5';
            ctx.font = '600 12px Inter, sans-serif';
            
            meta.data.forEach((bar, index) => {
                const rate = rates[index];
                if (rate !== undefined && bar.height > 20) {
                    ctx.fillText(rate + '%', bar.x, bar.y + 16);
                } else if (rate !== undefined) {
                    ctx.fillText(rate + '%', bar.x, bar.y - 10);
                }
            });
            ctx.restore();
        }
    }]
});
</script>

<?php include '../includes/footer.php'; ?>