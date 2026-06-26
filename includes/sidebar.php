<?php
function renderSidebar($role) {
    // Build the sidebar menu for the requested user role.
    $current_page = basename($_SERVER['PHP_SELF']);
    $menu = [];
    
    if ($role == 'manager') {
        $menu = [
            ['label' => 'Dashboard', 'link' => '/fashion_store/manager/dashboard.php', 'icon' => '⊞'],
            ['label' => 'Alerts Center', 'link' => '/fashion_store/manager/alerts_center.php', 'icon' => '🔔'],
            ['label' => 'Performance Analytics', 'link' => '/fashion_store/manager/product_analytics.php', 'icon' => '📈'],
            ['label' => 'Inventory', 'link' => '/fashion_store/manager/products_list.php', 'icon' => '📦'],
            ['label' => 'Orders', 'link' => '/fashion_store/manager/orders_list.php', 'icon' => '🛒'],
            ['label' => 'Main Site', 'link' => '/fashion_store/actions/toggle_visual_mode.php', 'icon' => '🏠'],
        ];
    } elseif ($role == 'admin') {
        $menu = [
            ['label' => 'Dashboard', 'link' => '/fashion_store/admin/dashboard.php', 'icon' => '⊞'],
            ['label' => 'Accounts', 'link' => '/fashion_store/admin/users_list.php', 'icon' => '👥'],
            ['label' => 'Reviews', 'link' => '/fashion_store/admin/reviews.php', 'icon' => '⭐'],
            ['label' => 'Manage FAQs', 'link' => '/fashion_store/admin/manage_faqs.php', 'icon' => '❓'],
            ['label' => 'Support', 'link' => '/fashion_store/admin/help_center_manage.php', 'icon' => '💬'],
            ['label' => 'Activity', 'link' => '/fashion_store/admin/orders_monitoring.php', 'icon' => '👁'],
        ];
    } elseif ($role == 'owner') {
        $menu = [
            ['label' => 'Dashboard', 'link' => '/fashion_store/owner/dashboard.php', 'icon' => '⊞'],
            ['label' => 'Business Analytics', 'link' => '/fashion_store/owner/business_analytics.php', 'icon' => '📈'],
            ['label' => 'Customer Intelligence', 'link' => '/fashion_store/owner/customer_intelligence.php', 'icon' => '👥'],
            ['label' => 'Product Insights', 'link' => '/fashion_store/owner/product_insights.php', 'icon' => '🛍'],
            ['label' => 'Voucher Management', 'link' => '/fashion_store/owner/vouchers.php', 'icon' => '🎟'],
            ['label' => 'Revenue Reports', 'link' => '/fashion_store/owner/revenue_reports.php', 'icon' => '💰'],
            ['label' => 'Product Profitability', 'link' => '/fashion_store/owner/product_profitability.php', 'icon' => '📊'],
        ];
    } elseif ($role == 'buyer') {
        $menu = [
            ['label' => 'My Orders', 'link' => '/fashion_store/buyer/dashboard.php', 'icon' => '📦'],
            ['label' => 'Reviews', 'link' => '/fashion_store/buyer/reviews.php', 'icon' => '⭐'],
            ['label' => 'Wishlist', 'link' => '/fashion_store/buyer/wishlist.php', 'icon' => '♡'],
            ['label' => 'Vouchers', 'link' => '/fashion_store/buyer/vouchers.php', 'icon' => '🎫'],
            ['label' => 'Settings', 'link' => '/fashion_store/buyer/profile.php', 'icon' => '👤'],
            ['label' => 'Support', 'link' => '/fashion_store/help.php', 'icon' => '💬'],
            ['label' => 'Main Site', 'link' => '/fashion_store/index.php', 'icon' => '🏠'],
        ];
    }
    ?>
    <div class="sidebar">
        <div class="sidebar-logo">HYPETHREAD</div>
        <ul class="sidebar-nav">
            <?php // Mark the current page as active while rendering each menu item. ?>
            <?php foreach ($menu as $item): 
                $isActive = ($current_page == basename($item['link']));
            ?>
                <li>
                    <a href="<?php echo $item['link']; ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                        <span style="font-size: 1.1rem; opacity: 0.8;"><?php echo $item['icon']; ?></span>
                        <?php echo $item['label']; ?>
                        <?php if ($item['label'] == 'Alerts Center'): ?>
                            <span class="badge badge-error" style="font-size: 10px; margin-left: 4px; padding: 2px 6px; background: #ff6b6b; color: white; border-radius: 4px;">New</span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <div style="margin-top: auto; padding-top: var(--spacing-xl); border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="/fashion_store/actions/logout.php" style="display: flex; align-items: center; gap: 12px; padding: 10px 16px; color: #ff6b6b; font-size: 14px; font-weight: 500; border-radius: var(--rounded-md); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,107,107,0.1)'" onmouseout="this.style.background='transparent'">
                <span style="font-size: 1.1rem;">⏻</span> Sign Out
            </a>
        </div>
    </div>
    <?php
}
?>
