-- Replace the demo catalogue with the local product image collection.
-- This migration is repeatable and keeps historical order relationships intact.
USE fashion_store;

-- Old Unisex placeholders remain available to historical orders but are hidden
-- from the published storefront.
UPDATE products
SET status = 'draft', is_featured = 0
WHERE gender = 'Unisex';

-- Rename and refresh the ten existing sellable products.
UPDATE products p JOIN categories c ON c.name = 'Tops'
SET p.category_id = c.id,
    p.name = 'Women''s Dress',
    p.description = 'A versatile women''s dress with an elegant everyday silhouette.',
    p.price = 85.00,
    p.cost_price = 42.00,
    p.status = 'published',
    p.is_featured = 1
WHERE p.gender = 'Women' AND p.name IN ('Silk Evening Blouse', 'Women''s Dress');

UPDATE products p JOIN categories c ON c.name = 'Bottoms'
SET p.category_id = c.id,
    p.name = 'Women''s Short Skirt',
    p.description = 'A structured short skirt designed for easy everyday styling.',
    p.price = 49.00,
    p.cost_price = 24.00,
    p.status = 'published',
    p.is_featured = 1
WHERE p.gender = 'Women' AND p.name IN ('High-Rise Skinny Jeans', 'Women''s Short Skirt');

UPDATE products p JOIN categories c ON c.name = 'Accessories'
SET p.category_id = c.id,
    p.name = 'Women''s Handbag',
    p.description = 'A polished handbag with space for daily essentials.',
    p.price = 145.00,
    p.cost_price = 72.00,
    p.status = 'published',
    p.is_featured = 1
WHERE p.gender = 'Women' AND p.name IN ('Leather Tote Bag', 'Women''s Handbag');

UPDATE products p JOIN categories c ON c.name = 'Outerwear'
SET p.category_id = c.id,
    p.name = 'Women''s Coats & Jackets',
    p.description = 'A fitted women''s jacket with a clean contemporary finish.',
    p.price = 195.00,
    p.cost_price = 98.00,
    p.status = 'published',
    p.is_featured = 1
WHERE p.gender = 'Women' AND p.name IN ('Wool Blend Overcoat', 'Women''s Coats & Jackets');

UPDATE products p JOIN categories c ON c.name = 'Tops'
SET p.category_id = c.id,
    p.name = 'Men''s Essential T-Shirts',
    p.description = 'A set of clean essential T-shirts in versatile neutral colours.',
    p.price = 55.00,
    p.cost_price = 25.00,
    p.status = 'published',
    p.is_featured = 1
WHERE p.gender = 'Men' AND p.name IN ('Oxford Button-Down', 'Men''s Essential T-Shirts');

UPDATE products p JOIN categories c ON c.name = 'Bottoms'
SET p.category_id = c.id,
    p.name = 'Men''s Cargo Shorts',
    p.description = 'Relaxed cargo shorts with practical utility pockets.',
    p.price = 65.00,
    p.cost_price = 31.00,
    p.status = 'published',
    p.is_featured = 1
WHERE p.gender = 'Men' AND p.name IN ('Straight Fit Chinos', 'Men''s Cargo Shorts');

UPDATE products p JOIN categories c ON c.name = 'Tops'
SET p.category_id = c.id,
    p.name = 'Men''s Essentials Sweater',
    p.description = 'A relaxed mock-neck Essentials sweater for comfortable layering.',
    p.price = 79.00,
    p.cost_price = 38.00,
    p.status = 'published',
    p.is_featured = 1
WHERE p.gender = 'Men' AND p.name IN ('Harrington Jacket', 'Men''s Essentials Sweater');

UPDATE products p JOIN categories c ON c.name = 'Tops'
SET p.category_id = c.id,
    p.name = 'Kids Surf Graphic Sweatshirt',
    p.description = 'A soft cotton-rich sweatshirt with a playful surf graphic.',
    p.price = 45.00,
    p.cost_price = 20.00,
    p.status = 'published',
    p.is_featured = 1
WHERE p.gender = 'Kids' AND p.name IN ('Animal Print Tee', 'Kids Surf Graphic Sweatshirt');

UPDATE products p JOIN categories c ON c.name = 'Tops'
SET p.category_id = c.id,
    p.name = 'Kids Oversized Gelato Graphic Tee',
    p.description = 'An oversized green tee with a colourful gelato graphic.',
    p.price = 40.00,
    p.cost_price = 18.00,
    p.status = 'published',
    p.is_featured = 1
WHERE p.gender = 'Kids' AND p.name IN ('Stretch Denim Overalls', 'Kids Oversized Gelato Graphic Tee');

UPDATE products p JOIN categories c ON c.name = 'Bottoms'
SET p.category_id = c.id,
    p.name = 'Kids Casual Pants',
    p.description = 'Comfortable casual pants designed for active everyday wear.',
    p.price = 30.00,
    p.cost_price = 13.00,
    p.status = 'published',
    p.is_featured = 1
WHERE p.gender = 'Kids' AND p.name IN ('Colourful Backpack', 'Kids Casual Pants');

-- Add the seven products that do not replace an existing sellable record.
INSERT INTO products (category_id, name, description, price, cost_price, gender, status, is_featured)
SELECT c.id, 'Women''s Knitted Vest', 'A green argyle knitted vest for layered outfits.', 59.00, 28.00, 'Women', 'published', 1
FROM categories c
WHERE c.name = 'Tops'
  AND NOT EXISTS (SELECT 1 FROM products WHERE name = 'Women''s Knitted Vest' AND gender = 'Women');

INSERT INTO products (category_id, name, description, price, cost_price, gender, status, is_featured)
SELECT c.id, 'Women''s Necklaces', 'A refined necklace collection for everyday accessorising.', 39.00, 16.00, 'Women', 'published', 0
FROM categories c
WHERE c.name = 'Accessories'
  AND NOT EXISTS (SELECT 1 FROM products WHERE name = 'Women''s Necklaces' AND gender = 'Women');

INSERT INTO products (category_id, name, description, price, cost_price, gender, status, is_featured)
SELECT c.id, 'Women''s Rings', 'A coordinated ring collection with a polished finish.', 35.00, 14.00, 'Women', 'published', 0
FROM categories c
WHERE c.name = 'Accessories'
  AND NOT EXISTS (SELECT 1 FROM products WHERE name = 'Women''s Rings' AND gender = 'Women');

INSERT INTO products (category_id, name, description, price, cost_price, gender, status, is_featured)
SELECT c.id, 'Women''s Sweater', 'A comfortable women''s sweater for effortless layering.', 69.00, 32.00, 'Women', 'published', 1
FROM categories c
WHERE c.name = 'Tops'
  AND NOT EXISTS (SELECT 1 FROM products WHERE name = 'Women''s Sweater' AND gender = 'Women');

INSERT INTO products (category_id, name, description, price, cost_price, gender, status, is_featured)
SELECT c.id, 'Men''s Denim Jeans', 'Classic denim jeans with a versatile everyday fit.', 89.00, 42.00, 'Men', 'published', 1
FROM categories c
WHERE c.name = 'Bottoms'
  AND NOT EXISTS (SELECT 1 FROM products WHERE name = 'Men''s Denim Jeans' AND gender = 'Men');

INSERT INTO products (category_id, name, description, price, cost_price, gender, status, is_featured)
SELECT c.id, 'Men''s Duffle Bag', 'A spacious black duffle bag for travel and training.', 75.00, 34.00, 'Men', 'published', 0
FROM categories c
WHERE c.name = 'Accessories'
  AND NOT EXISTS (SELECT 1 FROM products WHERE name = 'Men''s Duffle Bag' AND gender = 'Men');

INSERT INTO products (category_id, name, description, price, cost_price, gender, status, is_featured)
SELECT c.id, 'Kids Graphic T-Shirt & Flare Leggings Set', 'A colourful graphic T-shirt and flare leggings outfit set.', 55.00, 25.00, 'Kids', 'published', 1
FROM categories c
WHERE c.name = 'Tops'
  AND NOT EXISTS (SELECT 1 FROM products WHERE name = 'Kids Graphic T-Shirt & Flare Leggings Set' AND gender = 'Kids');

-- Seed stock variations for newly added products without changing existing stock.
DROP TEMPORARY TABLE IF EXISTS catalog_variations;
CREATE TEMPORARY TABLE catalog_variations (
    product_name VARCHAR(100), gender VARCHAR(10), size VARCHAR(20), color VARCHAR(40), stock INT
);
INSERT INTO catalog_variations VALUES
 ('Women''s Knitted Vest','Women','S','Green',10),
 ('Women''s Knitted Vest','Women','M','Green',12),
 ('Women''s Knitted Vest','Women','L','Green',8),
 ('Women''s Necklaces','Women','One Size','Gold',10),
 ('Women''s Necklaces','Women','One Size','Silver',9),
 ('Women''s Rings','Women','6','Gold',8),
 ('Women''s Rings','Women','7','Gold',10),
 ('Women''s Rings','Women','8','Gold',7),
 ('Women''s Sweater','Women','S','Cream',9),
 ('Women''s Sweater','Women','M','Cream',12),
 ('Women''s Sweater','Women','L','Cream',8),
 ('Men''s Denim Jeans','Men','30','Blue',8),
 ('Men''s Denim Jeans','Men','32','Blue',11),
 ('Men''s Denim Jeans','Men','34','Blue',7),
 ('Men''s Duffle Bag','Men','One Size','Black',14),
 ('Kids Graphic T-Shirt & Flare Leggings Set','Kids','S','Multicolour',10),
 ('Kids Graphic T-Shirt & Flare Leggings Set','Kids','M','Multicolour',12),
 ('Kids Graphic T-Shirt & Flare Leggings Set','Kids','L','Multicolour',9);

INSERT INTO product_variations (product_id, size, color, stock_quantity)
SELECT p.id, v.size, v.color, v.stock
FROM catalog_variations v
JOIN products p ON p.name = v.product_name AND p.gender = v.gender
WHERE NOT EXISTS (
    SELECT 1 FROM product_variations pv
    WHERE pv.product_id = p.id AND pv.size = v.size AND pv.color = v.color
);

-- Replace catalogue images with the local assets. One image is used per product.
DROP TEMPORARY TABLE IF EXISTS catalog_images;
CREATE TEMPORARY TABLE catalog_images (
    product_name VARCHAR(100), gender VARCHAR(10), image_path VARCHAR(500)
);
INSERT INTO catalog_images VALUES
 ('Women''s Dress','Women','assets/img/products/women-dress.jpg'),
 ('Women''s Short Skirt','Women','assets/img/products/women-short-skirt.jpg'),
 ('Women''s Handbag','Women','assets/img/products/women-handbag.jpg'),
 ('Women''s Coats & Jackets','Women','assets/img/products/women-coats-jackets.jpg'),
 ('Women''s Knitted Vest','Women','assets/img/products/women-knitted-vest.jpg'),
 ('Women''s Necklaces','Women','assets/img/products/women-necklaces.jpg'),
 ('Women''s Rings','Women','assets/img/products/women-rings.jpg'),
 ('Women''s Sweater','Women','assets/img/products/women-sweater.jpg'),
 ('Men''s Essential T-Shirts','Men','assets/img/products/men-essential-t-shirts.jpg'),
 ('Men''s Cargo Shorts','Men','assets/img/products/men-cargo-shorts.jpg'),
 ('Men''s Essentials Sweater','Men','assets/img/products/men-essentials-sweater.jpg'),
 ('Men''s Denim Jeans','Men','assets/img/products/men-denim-jeans.jpg'),
 ('Men''s Duffle Bag','Men','assets/img/products/men-duffle-bag.jpg'),
 ('Kids Surf Graphic Sweatshirt','Kids','assets/img/products/kids-surf-sweatshirt.jpg'),
 ('Kids Oversized Gelato Graphic Tee','Kids','assets/img/products/kids-gelato-graphic-tee.jpg'),
 ('Kids Casual Pants','Kids','assets/img/products/kids-pants.jpg'),
 ('Kids Graphic T-Shirt & Flare Leggings Set','Kids','assets/img/products/kids-graphic-tee-flare-leggings-set.jpg');

DELETE pi
FROM product_images pi
JOIN products p ON p.id = pi.product_id
JOIN catalog_images ci ON ci.product_name = p.name AND ci.gender = p.gender;

INSERT INTO product_images (product_id, image_path, image_data, mime_type)
SELECT p.id, ci.image_path, NULL, 'image/jpeg'
FROM catalog_images ci
JOIN products p ON p.name = ci.product_name AND p.gender = ci.gender;

DROP TEMPORARY TABLE IF EXISTS catalog_images;
DROP TEMPORARY TABLE IF EXISTS catalog_variations;
