    <?php if ($is_visual_mode): ?>
            </div> <!-- end dashboard-main -->
        </div> <!-- end dashboard-layout -->
    <?php endif; ?>
    </main> <!-- end main-content -->
    <style>
        .footer-anthropic {
            background-color: var(--colors-surface-dark);
            color: var(--colors-on-dark-soft);
            padding: 64px 0;
            font-family: var(--typography-body-font);
        }
        .footer-anthropic .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--colors-on-dark);
            font-family: var(--typography-display-font);
            font-size: 24px;
            margin-bottom: 48px;
        }
        .footer-brand svg {
            width: 20px;
            height: 20px;
        }
        .footer-grid-anthropic {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 32px;
            margin-bottom: 64px;
        }
        .footer-col h4 {
            color: var(--colors-on-dark);
            font-family: var(--typography-body-font);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        .footer-col ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .footer-col ul li {
            margin-bottom: 12px;
        }
        .footer-col a {
            color: var(--colors-on-dark-soft);
            text-decoration: none;
            font-size: 14px;
        }
        .footer-col a:hover {
            color: var(--colors-on-dark);
        }
        .footer-bottom-anthropic {
            border-top: 1px solid var(--colors-surface-dark-elevated);
            padding-top: 32px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }
        @media (max-width: 768px) {
            .footer-grid-anthropic {
                grid-template-columns: 1fr;
            }
            .footer-bottom-anthropic {
                flex-direction: column;
                gap: 16px;
            }
        }
    </style>
    <footer class="footer-anthropic">
        <div class="container">
            <div class="footer-brand">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L12 10M12 10L20 10M12 10L12 18M12 10L4 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="12" cy="12" r="2" fill="currentColor"/>
                </svg>
                HypeThread
            </div>
            <div class="footer-grid-anthropic">
                <div class="footer-col">
                    <h4>Shop & Company</h4>
                    <ul>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'buyer'): ?>
    <li><a href="/fashion_store/products.php">Collections</a></li>
<?php else: ?>
    <li><a href="/fashion_store/login.php">Collections</a></li>
<?php endif; ?>
                        <li><a href="/fashion_store/about_group.php">About Us</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom-anthropic">
                <div>&copy; 2026 HypeThread. All rights reserved.</div>
                <div>System Status: <span style="color: var(--colors-success);">●</span> Operational</div>
            </div>
        </div>
    </footer>
</body>
</html>
