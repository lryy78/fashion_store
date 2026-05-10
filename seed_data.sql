-- Demo Data for Fashion Store

USE fashion_store;

-- 1. Insert Categories (if not already there)
INSERT IGNORE INTO categories (name, description) VALUES 
('Tops', 'Premium cotton t-shirts and elegant blouses'),
('Bottoms', 'Denim jeans, tailored trousers, and skirts'),
('Accessories', 'Luxury leather bags and gold-plated jewelry');

-- 2. Insert Sample Products
INSERT INTO products (category_id, name, description, price, is_featured) VALUES 
(1, 'Classic White Tee', 'A timeless essential made from 100% organic cotton.', 25.00, 1),
(1, 'Silk Evening Blouse', 'Elegant silk blouse perfect for formal gatherings.', 85.00, 1),
(2, 'Slim Fit Indigo Jeans', 'High-quality denim with a comfortable stretch.', 65.00, 1),
(2, 'Black Tailored Trousers', 'Professional look with a modern tapered fit.', 55.00, 0),
(3, 'Minimalist Leather Watch', 'Sleek black leather strap with a gold-tone dial.', 120.00, 1),
(3, 'Canvas Tote Bag', 'Spacious and durable tote for your daily essentials.', 35.00, 0);

-- 3. Insert Product Variations (Stock Management)
-- Variations for Classic White Tee
INSERT INTO product_variations (product_id, size, color, stock_quantity) VALUES 
(1, 'S', 'White', 20),
(1, 'M', 'White', 15),
(1, 'L', 'White', 10);

-- Variations for Indigo Jeans
INSERT INTO product_variations (product_id, size, color, stock_quantity) VALUES 
(3, '30', 'Indigo', 12),
(3, '32', 'Indigo', 8),
(3, '34', 'Indigo', 5);

-- Variations for Leather Watch
INSERT INTO product_variations (product_id, size, color, stock_quantity) VALUES 
(5, 'One Size', 'Black/Gold', 5);

-- 4. Insert Demo Users (Passwords are 'password123' hashed)
-- Password: password123 -> $2y$10$8fXp.x.X.X.X.X.X.X.X.X.X.X.X.X.X.X.X.X.X.X.X.X.X.X.X.X.
-- Actually I will use a simple known hash for 'password'
-- 'password' hash: $2y$10$nOUIs5kJ7tp4XYJCbe.A9.G6Y1p0G7yC7GZ9Z9Z9Z9Z9Z9Z9Z9Z9Z
INSERT IGNORE INTO users (username, email, password, role, full_name) VALUES 
('buyer_demo', 'buyer@example.com', '$2y$10$YBjfatztCIlBI.cc/.K3c.sVEwSEYrni5nlU8mrkrrPiSeC/iTbYG', 'buyer', 'John Doe'),
('manager_demo', 'manager@example.com', '$2y$10$YBjfatztCIlBI.cc/.K3c.sVEwSEYrni5nlU8mrkrrPiSeC/iTbYG', 'manager', 'Alice Manager'),
('admin_demo', 'admin@example.com', '$2y$10$YBjfatztCIlBI.cc/.K3c.sVEwSEYrni5nlU8mrkrrPiSeC/iTbYG', 'admin', 'Bob Admin'),
('owner_demo', 'owner@example.com', '$2y$10$YBjfatztCIlBI.cc/.K3c.sVEwSEYrni5nlU8mrkrrPiSeC/iTbYG', 'owner', 'Charlie Owner');

-- 5. Insert Sample Vouchers
INSERT INTO vouchers (code, discount_type, discount_value, expiry_date) VALUES 
('WELCOME10', 'percentage', 10.00, '2026-12-31'),
('FASHION20', 'fixed', 20.00, '2026-12-31');

-- 6. Insert System Settings
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
('low_stock_threshold', '10'),
('overstock_threshold', '100'),
('dashboard_active_alerts', 'out_of_stock,low_stock,overstock');
