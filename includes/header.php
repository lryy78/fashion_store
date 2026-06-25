<?php 
require_once __DIR__ . '/sidebar.php'; 
require_once __DIR__ . '/product_sync.php'; 

$is_manager = (isset($_SESSION['role']) && $_SESSION['role'] == 'manager');
$is_in_manager_dir = (strpos($_SERVER['PHP_SELF'], '/manager/') !== false);
$is_visual_mode = ($is_manager && !$is_in_manager_dir && isset($_SESSION['visual_mode']) && $_SESSION['visual_mode'] === true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HypeThread</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/fashion_store/assets/css/theme.css?v=3">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/fashion_store/assets/js/main.js" defer></script>
    <style>
        .top-nav {
            height: 64px;
            background-color: var(--colors-canvas);
            border-bottom: 1px solid var(--colors-hairline);
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
        }
        .top-nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .nav-left {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .brand-logo {
            font-family: var(--typography-display-font);
            font-size: 24px;
            font-weight: 500;
            color: var(--colors-ink);
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.5px;
        }
        .brand-logo svg {
            width: 20px;
            height: 20px;
        }
        .nav-menu {
            display: flex;
            gap: 24px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .nav-menu a {
            font-family: var(--typography-body-font);
            font-size: 14px;
            font-weight: 500;
            color: var(--colors-ink);
            text-decoration: none;
        }
        .nav-menu a:hover {
            color: var(--colors-primary);
        }
        .nav-menu a.nav-active {
            color: var(--colors-primary);
            border-bottom: 2px solid var(--colors-primary);
            padding-bottom: 2px;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .nav-right a {
            font-family: var(--typography-body-font);
            font-size: 14px;
            font-weight: 500;
        }
        .nav-right .button-text-link {
            color: var(--colors-ink);
        }
    </style>
</head>
<body>
    <header class="top-nav">
        <div class="container">
            <div class="nav-left">
                <?php 
                $nav_role = $_SESSION['role'] ?? 'guest';
                $brand_href = '/fashion_store/index.php';
                if ($nav_role === 'manager') {
                    $brand_href = '/fashion_store/manager/dashboard.php';
                } elseif ($nav_role === 'admin') {
                    $brand_href = '/fashion_store/admin/dashboard.php';
                } elseif ($nav_role === 'owner') {
                    $brand_href = '/fashion_store/owner/dashboard.php';
                }
                ?>
                <a href="<?php echo $brand_href; ?>" class="brand-logo">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L12 10M12 10L20 10M12 10L12 18M12 10L4 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="12" r="2" fill="currentColor"/>
                    </svg>
                    HypeThread
                </a>
                <?php
                $nav_role    = $_SESSION['role'] ?? 'guest';
                $current_uri = $_SERVER['REQUEST_URI'];
                function nav_active(string $path): string {
                    return (strpos($_SERVER['REQUEST_URI'], $path) !== false) ? ' class="nav-active"' : '';
                }
                if ($nav_role === 'manager'): ?>
                <ul class="nav-menu">
                    <li><a href="/fashion_store/manager/dashboard.php"<?php echo nav_active('/manager/dashboard'); ?>>Dashboard</a></li>
                    <li><a href="/fashion_store/manager/alerts_center.php"<?php echo nav_active('/manager/alerts_center'); ?>>Alerts Center</a></li>
                    <li><a href="/fashion_store/manager/product_analytics.php"<?php echo nav_active('/manager/product_analytics'); ?>>Analytics</a></li>
                    <li><a href="/fashion_store/manager/products_list.php"<?php echo nav_active('/manager/products_list'); ?>>Inventory</a></li>
                    <li><a href="/fashion_store/manager/orders_list.php"<?php echo nav_active('/manager/orders_list'); ?>>Orders</a></li>
                </ul>
                <?php elseif ($nav_role === 'admin'): ?>
                <ul class="nav-menu">
                    <li><a href="/fashion_store/admin/dashboard.php"<?php echo nav_active('/admin/dashboard'); ?>>Dashboard</a></li>
                    <li><a href="/fashion_store/admin/users_list.php"<?php echo nav_active('/admin/users_list'); ?>>Accounts</a></li>
                    <li><a href="/fashion_store/admin/orders_monitoring.php"<?php echo nav_active('/admin/orders_monitoring'); ?>>Activity</a></li>
                    <li><a href="/fashion_store/admin/help_center_manage.php"<?php echo nav_active('/admin/help_center_manage'); ?>>Support</a></li>
                    <li><a href="/fashion_store/admin/reviews.php"<?php echo nav_active('/admin/reviews'); ?>>Reviews</a></li>
                </ul>
                <?php elseif ($nav_role === 'owner'): ?>
                <ul class="nav-menu">
                    <li><a href="/fashion_store/owner/dashboard.php"<?php echo nav_active('/owner/dashboard'); ?>>Dashboard</a></li>
                    <li><a href="/fashion_store/owner/business_analytics.php"<?php echo nav_active('/owner/business_analytics'); ?>>Analytics</a></li>
                    <li><a href="/fashion_store/owner/customer_intelligence.php"<?php echo nav_active('/owner/customer_intelligence'); ?>>Customers</a></li>
                    <li><a href="/fashion_store/owner/product_insights.php"<?php echo nav_active('/owner/product_insights'); ?>>Products</a></li>
                    <li><a href="/fashion_store/owner/vouchers.php"<?php echo nav_active('/owner/vouchers'); ?>>Vouchers</a></li>
                    <li><a href="/fashion_store/owner/revenue_reports.php"<?php echo nav_active('/owner/revenue_reports'); ?>>Reports</a></li>
                </ul>
                <?php else: ?>
                <ul class="nav-menu">
                    <li><a href="/fashion_store/index.php">Home</a></li>
                    <li><a href="/fashion_store/index.php?gender=Women">Women</a></li>
                    <li><a href="/fashion_store/index.php?gender=Men">Men</a></li>
                    <li><a href="/fashion_store/index.php?gender=Kids">Kids</a></li>
                </ul>
                <?php endif; ?>

            </div>
            
            <div class="nav-right">
                <?php if (isset($_SESSION['user_id'])): 
                    $role = $_SESSION['role'];
                    // Fetch cart count only for buyers
                    $cart_count = 0;
                    if ($role == 'buyer') {
                        $cart_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
                        $cart_stmt->execute([$_SESSION['user_id']]);
                        $cart_count = $cart_stmt->fetchColumn() ?: 0;
                    }
                ?>
                    <?php if ($role == 'buyer'): ?>
                        <a href="/fashion_store/buyer/profile.php" class="button-text-link">Profile</a>
                        <a href="/fashion_store/actions/logout.php" class="button-text-link">Sign out</a>
                        <a href="/fashion_store/buyer/cart.php" class="button-primary">Cart (<?php echo $cart_count; ?>)</a>
                    <?php else: ?>
                        <!-- Staff Navigation -->
                        <?php if ($role == 'manager'): 
                            $unread_alerts = $pdo->query("SELECT COUNT(*) FROM system_alerts WHERE is_read = 0")->fetchColumn();
                        ?>
                            <a href="/fashion_store/manager/alerts_center.php" style="position: relative; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: var(--colors-surface-soft); color: var(--colors-ink); text-decoration: none;">
                                🔔
                                <?php if ($unread_alerts > 0): ?>
                                    <span style="position: absolute; top: 0; right: 0; background: var(--colors-error); color: white; font-size: 10px; font-weight: 700; min-width: 18px; height: 18px; border-radius: 9px; display: flex; align-items: center; justify-content: center; border: 2px solid var(--colors-canvas);"><?php echo $unread_alerts; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="/fashion_store/manager/dashboard.php" class="button-primary">Manager Dashboard</a>
                        <?php elseif ($role == 'admin'): ?>
                            <a href="/fashion_store/admin/dashboard.php" class="button-primary">Admin Dashboard</a>
                        <?php elseif ($role == 'owner'): ?>
                            <a href="/fashion_store/owner/dashboard.php" class="button-primary">Owner Dashboard</a>
                        <?php endif; ?>
                        
                        <!-- Role-based Profile link -->
                        <a href="/fashion_store/<?php echo $role; ?>/profile.php" class="button-text-link">Profile</a>
                        <a href="/fashion_store/actions/logout.php" class="button-text-link">Sign out</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="/fashion_store/login.php" class="button-text-link">Sign in</a>
                    <a href="/fashion_store/signup.php" class="button-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="main-content">
    <?php if ($is_visual_mode): ?>
        <div class="dashboard-layout" style="background: var(--colors-canvas);">
            <?php renderSidebar('manager'); ?>
            <div class="dashboard-main" style="padding: 0;">
                <div style="background: var(--colors-ink); color: #fff; padding: 12px 24px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; display: flex; justify-content: space-between; align-items: center;">
                    <span>Visual Mode: Active Viewing as Manager</span>
                    <a href="/fashion_store/actions/toggle_visual_mode.php?exit=1" style="color: #fff; text-decoration: underline;">Exit Visual Mode</a>
                </div>
    <?php endif; ?>
