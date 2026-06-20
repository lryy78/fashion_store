<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

$members = [
    [
        'initials' => 'MA',
        'name' => 'Muhammad Aqil Bin Bachtiar Affendy',
        'student_id' => '1211104729',
        'module' => 'Buyer Shopping Interface',
        'pages' => ['Home Page', 'Product Listing', 'Product Details', 'Help Centre'],
        'features' => [
            'Promotional banners and featured-product presentation',
            'Product browsing and filters for category, size, colour, price, and availability',
            'Product-detail viewing and customer enquiry submission',
        ],
    ],
    [
        'initials' => 'RY',
        'name' => 'Lam Rong Yi',
        'student_id' => '1211107112',
        'module' => 'Authentication, Cart and Order Module',
        'pages' => ['Login', 'Sign Up', 'Shopping Cart', 'Checkout and Order History'],
        'features' => [
            'User registration and secure authentication',
            'Shopping-cart management and checkout processing',
            'Order placement, history, tracking, and public category navigation',
        ],
    ],
    [
        'initials' => 'WT',
        'name' => 'Willie Teoh Chin Wei',
        'student_id' => '1211106712',
        'module' => 'Product Manager Module',
        'pages' => ['Manager Dashboard', 'Product Management', 'Add Product', 'Inventory and Order Fulfilment'],
        'features' => [
            'Product and sales summary monitoring',
            'Product creation, editing, images, sizes, colours, and variations',
            'Stock monitoring, inventory alerts, and order-status fulfilment',
        ],
    ],
    [
        'initials' => 'SJ',
        'name' => 'Sia Jing Liang',
        'student_id' => '1211106208',
        'module' => 'Admin and Business Owner Module',
        'pages' => ['Admin Dashboard', 'User Management', 'Order Monitoring', 'Analytics and Voucher Management'],
        'features' => [
            'User-account and role administration',
            'Order monitoring, customer enquiries, and review management',
            'Sales analytics, performance reporting, and voucher campaigns',
        ],
    ],
];
?>

<style>
    .group-page {
        background: var(--colors-canvas);
        color: var(--colors-ink);
    }
    .group-intro {
        padding: 72px 0 56px;
        border-bottom: 1px solid var(--colors-hairline);
    }
    .group-eyebrow {
        margin-bottom: 16px;
        color: var(--colors-primary);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0;
    }
    .group-intro h1 {
        max-width: 760px;
        margin: 0 0 20px;
        font-family: var(--typography-display-font);
        font-size: 56px;
        font-weight: 500;
        line-height: 1.05;
        letter-spacing: 0;
    }
    .group-intro p {
        max-width: 720px;
        margin: 0;
        color: var(--colors-body);
        font-size: 17px;
        line-height: 1.7;
    }
    .group-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        margin-top: 32px;
        color: var(--colors-muted);
        font-size: 13px;
    }
    .member-list {
        padding: 64px 0 88px;
    }
    .member-card {
        display: grid;
        grid-template-columns: 72px minmax(220px, 0.8fr) minmax(260px, 1fr) minmax(300px, 1.3fr);
        gap: 28px;
        align-items: start;
        padding: 32px 0;
        border-bottom: 1px solid var(--colors-hairline);
    }
    .member-card:first-child {
        border-top: 1px solid var(--colors-hairline);
    }
    .member-initials {
        display: flex;
        width: 56px;
        height: 56px;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--colors-primary);
        border-radius: 50%;
        color: var(--colors-primary);
        font-family: var(--typography-code-font);
        font-size: 14px;
        font-weight: 600;
    }
    .member-name h2 {
        margin: 0 0 8px;
        font-family: var(--typography-display-font);
        font-size: 27px;
        font-weight: 500;
        line-height: 1.15;
        letter-spacing: 0;
    }
    .member-id {
        color: var(--colors-muted);
        font-family: var(--typography-code-font);
        font-size: 12px;
    }
    .member-section h3 {
        margin: 0 0 12px;
        color: var(--colors-muted);
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0;
    }
    .member-module {
        margin: 0 0 16px;
        color: var(--colors-body-strong);
        font-size: 15px;
        font-weight: 600;
        line-height: 1.5;
    }
    .member-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .member-tag {
        padding: 6px 9px;
        border: 1px solid var(--colors-hairline);
        border-radius: 4px;
        background: var(--colors-surface-soft);
        color: var(--colors-body);
        font-size: 11px;
        line-height: 1.25;
    }
    .feature-list {
        margin: 0;
        padding-left: 18px;
        color: var(--colors-body);
        font-size: 14px;
        line-height: 1.6;
    }
    .feature-list li + li {
        margin-top: 7px;
    }
    @media (max-width: 960px) {
        .member-card {
            grid-template-columns: 64px 1fr 1fr;
        }
        .member-card .member-section:last-child {
            grid-column: 2 / -1;
        }
    }
    @media (max-width: 640px) {
        .group-intro {
            padding: 48px 0 40px;
        }
        .group-intro h1 {
            font-size: 40px;
        }
        .member-list {
            padding: 40px 0 64px;
        }
        .member-card {
            grid-template-columns: 56px 1fr;
            gap: 20px 16px;
        }
        .member-card .member-section {
            grid-column: 1 / -1;
        }
    }
</style>

<div class="group-page">
    <section class="group-intro">
        <div class="container">
            <div class="group-eyebrow">CIT6224 Web Application Development</div>
            <h1>Meet the team behind HypeThread.</h1>
            <p>HypeThread is a Group 9 fashion e-commerce project developed by four Faculty of Computing and Informatics students. Responsibilities are divided by operational module so each member owns a clear set of pages and system features.</p>
            <div class="group-meta">
                <span>Lecture: TC1L</span>
                <span>Tutorial: TT1L</span>
                <span>Group: 9</span>
                <span>Project: Online Fashion Store</span>
            </div>
        </div>
    </section>

    <section class="member-list" aria-labelledby="team-heading">
        <div class="container">
            <h2 id="team-heading" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">Group members and contributions</h2>
            <?php foreach ($members as $member): ?>
                <article class="member-card">
                    <div class="member-initials" aria-hidden="true"><?php echo htmlspecialchars($member['initials']); ?></div>
                    <div class="member-name">
                        <h2><?php echo htmlspecialchars($member['name']); ?></h2>
                        <div class="member-id">Student ID <?php echo htmlspecialchars($member['student_id']); ?></div>
                    </div>
                    <div class="member-section">
                        <h3>Assigned Module</h3>
                        <p class="member-module"><?php echo htmlspecialchars($member['module']); ?></p>
                        <div class="member-tags">
                            <?php foreach ($member['pages'] as $page): ?>
                                <span class="member-tag"><?php echo htmlspecialchars($page); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="member-section">
                        <h3>Main Features</h3>
                        <ul class="feature-list">
                            <?php foreach ($member['features'] as $feature): ?>
                                <li><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
