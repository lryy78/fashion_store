-- Repeatable demo data that is safe for fresh and existing databases.
-- Demo password: password123
USE fashion_store;

-- Insert reusable catalogue categories.
INSERT INTO categories (name, description) VALUES
 ('Tops','T-shirts, blouses, shirts, and knitwear'),
 ('Bottoms','Jeans, trousers, skirts, and shorts'),
 ('Accessories','Bags, watches, belts, and jewellery'),
 ('Outerwear','Coats, jackets, and blazers'),
 ('Footwear','Shoes, boots, sandals, and sneakers'),
 ('Loungewear','Comfortable clothing for home and travel')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Insert demo accounts only when they do not already exist.
INSERT INTO users (username, email, password, role, full_name, is_active)
SELECT 'buyer_demo','buyer@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','buyer','Demo Buyer',1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='buyer_demo' OR email='buyer@example.com');
INSERT INTO users (username, email, password, role, full_name, is_active)
SELECT 'manager_demo','manager@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','manager','Demo Manager',1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='manager_demo' OR email='manager@example.com');
INSERT INTO users (username, email, password, role, full_name, is_active)
SELECT 'admin_demo','admin@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','admin','Demo Administrator',1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='admin_demo' OR email='admin@example.com');
INSERT INTO users (username, email, password, role, full_name, is_active)
SELECT 'owner_demo','owner@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','owner','Demo Owner',1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='owner_demo' OR email='owner@example.com');

UPDATE users SET password='$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2', role='buyer', full_name='Demo Buyer', is_active=1 WHERE username='buyer_demo';
UPDATE users SET password='$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2', role='manager', full_name='Demo Manager', is_active=1 WHERE username='manager_demo';
UPDATE users SET password='$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2', role='admin', full_name='Demo Administrator', is_active=1 WHERE username='admin_demo';
UPDATE users SET password='$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2', role='owner', full_name='Demo Owner', is_active=1 WHERE username='owner_demo';

-- Use temporary tables to keep product, variation, and image seeding repeatable.
DROP TEMPORARY TABLE IF EXISTS seed_products;
CREATE TEMPORARY TABLE seed_products (
 category_name VARCHAR(50), name VARCHAR(100), description TEXT,
 price DECIMAL(10,2), cost_price DECIMAL(10,2), gender VARCHAR(10), featured TINYINT(1)
);
INSERT INTO seed_products VALUES
 ('Tops','Silk Evening Blouse','Elegant silk blouse for formal gatherings.',85.00,42.00,'Women',1),
 ('Bottoms','High-Rise Skinny Jeans','Classic high-rise jeans with comfortable stretch.',75.00,36.00,'Women',1),
 ('Accessories','Leather Tote Bag','A spacious leather bag for everyday essentials.',145.00,72.00,'Women',1),
 ('Outerwear','Wool Blend Overcoat','A timeless wool-blend overcoat in camel.',195.00,98.00,'Women',0),
 ('Footwear','Minimalist Sandals','Minimal leather sandals for everyday wear.',60.00,28.00,'Women',0),
 ('Tops','Oxford Button-Down','A crisp cotton oxford shirt.',55.00,25.00,'Men',1),
 ('Bottoms','Straight Fit Chinos','Versatile chinos with a tailored straight fit.',65.00,31.00,'Men',1),
 ('Accessories','Leather Briefcase','A structured leather briefcase for work and travel.',195.00,96.00,'Men',1),
 ('Outerwear','Harrington Jacket','A lightweight cotton Harrington jacket.',110.00,54.00,'Men',1),
 ('Tops','Animal Print Tee','A soft jersey tee with a playful animal print.',20.00,8.00,'Kids',1),
 ('Bottoms','Stretchy Denim Overalls','Adjustable denim overalls for active kids.',40.00,18.00,'Kids',1),
 ('Accessories','Colorful Backpack','A lightweight school and travel backpack.',30.00,13.00,'Kids',1);

INSERT INTO products (category_id, name, description, price, cost_price, gender, status, is_featured)
SELECT c.id, s.name, s.description, s.price, s.cost_price, s.gender, 'published', s.featured
FROM seed_products s JOIN categories c ON c.name=s.category_name
WHERE NOT EXISTS (SELECT 1 FROM products p WHERE p.name=s.name AND p.gender=s.gender);

UPDATE products p
JOIN seed_products s ON p.name=s.name AND p.gender=s.gender
JOIN categories c ON c.name=s.category_name
SET p.category_id=c.id, p.description=s.description, p.price=s.price,
    p.cost_price=s.cost_price, p.status='published', p.is_featured=s.featured;

DROP TEMPORARY TABLE IF EXISTS seed_variations;
CREATE TEMPORARY TABLE seed_variations (
 product_name VARCHAR(100), gender VARCHAR(10), size VARCHAR(20), color VARCHAR(40), stock INT
);
INSERT INTO seed_variations VALUES
 ('Silk Evening Blouse','Women','S','Ivory',12),('Silk Evening Blouse','Women','M','Ivory',15),('Silk Evening Blouse','Women','L','Black',8),
 ('High-Rise Skinny Jeans','Women','S','Indigo',10),('High-Rise Skinny Jeans','Women','M','Indigo',14),('High-Rise Skinny Jeans','Women','L','Indigo',7),
 ('Leather Tote Bag','Women','One Size','Tan',9),('Leather Tote Bag','Women','One Size','Black',6),
 ('Wool Blend Overcoat','Women','S','Camel',4),('Wool Blend Overcoat','Women','M','Camel',7),('Wool Blend Overcoat','Women','L','Camel',3),
 ('Minimalist Sandals','Women','38','Black',10),('Minimalist Sandals','Women','39','Black',8),
 ('Oxford Button-Down','Men','S','White',10),('Oxford Button-Down','Men','M','White',14),('Oxford Button-Down','Men','L','Blue',9),
 ('Straight Fit Chinos','Men','30','Khaki',8),('Straight Fit Chinos','Men','32','Khaki',11),('Straight Fit Chinos','Men','34','Navy',6),
 ('Leather Briefcase','Men','One Size','Black',6),
 ('Harrington Jacket','Men','M','Navy',6),('Harrington Jacket','Men','L','Navy',5),('Harrington Jacket','Men','XL','Black',2),
 ('Animal Print Tee','Kids','S','White',14),('Animal Print Tee','Kids','M','Green',12),('Animal Print Tee','Kids','L','Blue',10),
 ('Stretchy Denim Overalls','Kids','S','Blue',8),('Stretchy Denim Overalls','Kids','M','Blue',9),('Stretchy Denim Overalls','Kids','L','Blue',6),
 ('Colorful Backpack','Kids','One Size','Red',11),('Colorful Backpack','Kids','One Size','Blue',8);

INSERT INTO product_variations (product_id, size, color, stock_quantity)
SELECT p.id, s.size, s.color, s.stock
FROM seed_variations s JOIN products p ON p.name=s.product_name AND p.gender=s.gender
WHERE NOT EXISTS (
 SELECT 1 FROM product_variations v
 WHERE v.product_id=p.id AND v.size=s.size AND v.color=s.color
);

UPDATE product_variations v
JOIN products p ON p.id=v.product_id
JOIN seed_variations s ON p.name=s.product_name AND p.gender=s.gender AND v.size=s.size AND v.color=s.color
SET v.stock_quantity=s.stock;

DROP TEMPORARY TABLE IF EXISTS seed_images;
CREATE TEMPORARY TABLE seed_images (
 product_name VARCHAR(100),
 gender VARCHAR(10),
 image_path VARCHAR(500)
);

INSERT INTO seed_images VALUES
 ('Silk Evening Blouse','Women','assets/img/silk_blouse_1.jpg'),
 ('Silk Evening Blouse','Women','assets/img/silk_blouse_2.jpg'),
 ('High-Rise Skinny Jeans','Women','assets/img/skinny_jeans_1.jpg'),
 ('High-Rise Skinny Jeans','Women','assets/img/skinny_jeans_2.jpg'),
 ('Leather Tote Bag','Women','assets/img/leather_tote_1.jpg'),
 ('Leather Tote Bag','Women','assets/img/leather_tote_2.jpg'),
 ('Wool Blend Overcoat','Women','assets/img/wool_overcoat_1.jpg'),
 ('Wool Blend Overcoat','Women','assets/img/wool_overcoat_2.jpg'),
 ('Minimalist Sandals','Women','assets/img/sandals_1.jpg'),
 ('Minimalist Sandals','Women','assets/img/sandals_2.jpg'),
 ('Oxford Button-Down','Men','assets/img/oxford_shirt_1.jpg'),
 ('Oxford Button-Down','Men','assets/img/oxford_shirt_2.jpg'),
 ('Straight Fit Chinos','Men','assets/img/chinos_1.jpg'),
 ('Straight Fit Chinos','Men','assets/img/chinos_2.jpg'),
 ('Leather Briefcase','Men','assets/img/briefcase_1.jpg'),
 ('Leather Briefcase','Men','assets/img/briefcase_2.jpg'),
 ('Harrington Jacket','Men','assets/img/harrington_1.jpg'),
 ('Harrington Jacket','Men','assets/img/harrington_2.jpg'),
 ('Animal Print Tee','Kids','assets/img/animal_tee_1.jpg'),
 ('Animal Print Tee','Kids','assets/img/animal_tee_2.jpg'),
 ('Stretchy Denim Overalls','Kids','assets/img/denim_overalls_1.jpg'),
 ('Stretchy Denim Overalls','Kids','assets/img/denim_overalls_2.jpg'),
 ('Colorful Backpack','Kids','assets/img/backpack_1.png'),
 ('Colorful Backpack','Kids','assets/img/backpack_2.jpg');

-- Keep the preloaded image mapping deterministic when setup_db.php is rerun.
DELETE pi
FROM product_images pi
JOIN products p ON p.id = pi.product_id
JOIN seed_products s ON s.name = p.name AND s.gender = p.gender;

INSERT INTO product_images (product_id, image_path)
SELECT p.id, s.image_path
FROM seed_images s
JOIN products p ON p.name = s.product_name AND p.gender = s.gender
ORDER BY p.id, s.image_path;

-- Insert reusable vouchers, settings, and FAQ content.
INSERT INTO vouchers (code, discount_type, discount_value, min_spend, expiry_date, is_one_time, is_active, target_type) VALUES
 ('WELCOME10','percentage',10.00,50.00,'2027-12-31',1,1,'all'),
 ('SAVE20','fixed',20.00,120.00,'2027-12-31',1,1,'all')
ON DUPLICATE KEY UPDATE discount_type=VALUES(discount_type), discount_value=VALUES(discount_value), min_spend=VALUES(min_spend), expiry_date=VALUES(expiry_date), is_active=VALUES(is_active);

INSERT INTO system_settings (setting_key, setting_value) VALUES
 ('low_stock_threshold','10'),('overstock_threshold','100'),('dashboard_active_alerts','out_of_stock,low_stock,overstock')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

INSERT INTO faqs (question, answer)
SELECT 'How do I track my order?','Sign in and open your buyer dashboard to see the latest order status.'
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question='How do I track my order?');
INSERT INTO faqs (question, answer)
SELECT 'Can I return an item?','Contact support with your order details. Eligibility depends on condition and purchase date.'
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question='Can I return an item?');
INSERT INTO faqs (question, answer)
SELECT 'How do vouchers work?','Available vouchers appear during checkout and can be applied before placing an order.'
WHERE NOT EXISTS (SELECT 1 FROM faqs WHERE question='How do vouchers work?');

DROP TEMPORARY TABLE IF EXISTS seed_images;
DROP TEMPORARY TABLE IF EXISTS seed_variations;
DROP TEMPORARY TABLE IF EXISTS seed_products;
