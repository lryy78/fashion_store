USE fashion_store;

-- 1. Insert/Update Categories
INSERT IGNORE INTO categories (id, name, description) VALUES 
(1, 'Tops', 'T-shirts, blouses, and shirts'),
(2, 'Bottoms', 'Pants, skirts, and shorts'),
(3, 'Accessories', 'Bags, belts, and jewelry'),
(4, 'Outerwear', 'Coats, jackets, and blazers'),
(5, 'Footwear', 'Shoes, boots, and sneakers'),
(6, 'Loungewear', 'Comfortable home wear');

-- 2. Insert Products
-- Using REPLACE to ensure data is updated if IDs exist, or just INSERT IGNORE
-- We want to ENSURE data for all categories.

-- Women's 
INSERT IGNORE INTO products (id, category_id, gender, name, description, price, is_featured) VALUES
(1, 1, 'Women', 'Silk Evening Blouse', 'Elegant silk blouse perfect for formal gatherings.', 85.00, 1),
(2, 2, 'Women', 'High-Rise Skinny Jeans', 'Classic skinny jeans with a flattering high-rise fit.', 75.00, 1),
(3, 3, 'Women', 'Leather Tote Bag', 'Spacious leather bag for all your essentials.', 145.00, 1),
(4, 4, 'Women', 'Wool Blend Overcoat', 'Timeless wool overcoat in a soft camel shade.', 195.00, 0),
(5, 5, 'Women', 'Minimalist Sandals', 'Clean-lined leather sandals for summer days.', 60.00, 0),
(6, 6, 'Women', 'Cashmere Lounge Set', 'Ultra-soft cashmere top and joggers for home comfort.', 120.00, 0);

-- Men's
INSERT IGNORE INTO products (id, category_id, gender, name, description, price, is_featured) VALUES
(7, 1, 'Men', 'Oxford Button-Down', 'Classic oxford shirt in crisp white cotton.', 55.00, 1),
(8, 2, 'Men', 'Straight Fit Chinos', 'Versatile chinos in a tailored straight fit.', 65.00, 1),
(9, 3, 'Men', 'Leather Briefcase', 'Professional leather bag with multiple compartments.', 195.00, 1),
(10, 4, 'Men', 'Harrington Jacket', 'Lightweight Harrington jacket in navy cotton.', 110.00, 0),
(11, 5, 'Men', 'Suede Chelsea Boots', 'Elegant suede boots with elastic side panels.', 140.00, 0),
(12, 6, 'Men', 'Waffle Knit Robe', 'Textured waffle knit robe in dark grey.', 45.00, 0);

-- Kids'
INSERT IGNORE INTO products (id, category_id, gender, name, description, price, is_featured) VALUES
(13, 1, 'Kids', 'Animal Print Tee', 'Fun animal print t-shirt in soft jersey.', 20.00, 1),
(14, 2, 'Kids', 'Stretchy Denim Overalls', 'Classic denim overalls with adjustable straps.', 40.00, 1),
(15, 3, 'Kids', 'Colorful Backpack', 'Lightweight and durable backpack for school.', 30.00, 1),
(16, 4, 'Kids', 'Cozy Teddy Jacket', 'Ultra-soft fleece jacket for kids.', 45.00, 1),
(17, 5, 'Kids', 'Light-up Sneakers', 'Fun sneakers with LED lights in the sole.', 50.00, 0),
(18, 6, 'Kids', 'Patterned Pajama Set', 'Soft cotton pajamas with fun space prints.', 25.00, 0);

-- 3. Variations (Sizes & Stock)
-- We use a cross join logic to insert variations for all products we just added
INSERT IGNORE INTO product_variations (product_id, size, color, stock_quantity)
SELECT p.id, s.size, 'Default', 20
FROM products p
CROSS JOIN (SELECT 'S' as size UNION SELECT 'M' UNION SELECT 'L' UNION SELECT 'One Size') s
WHERE p.id BETWEEN 1 AND 18;

-- 4. Images
INSERT IGNORE INTO product_images (product_id, image_path) VALUES
(1, 'assets/img/products/silk_evening_blouse.png'),
(2, 'assets/img/products/high_rise_jeans.png'),
(3, 'assets/img/products/leather_tote_bag.png'),
(4, 'https://images.unsplash.com/photo-1539533113208-f6df8cc8b543?q=80&w=800'),
(5, 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?q=80&w=800'),
(6, 'https://images.unsplash.com/photo-1516750105099-4b8a83e217ee?q=80&w=800'),
(7, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?q=80&w=800'),
(8, 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?q=80&w=800'),
(9, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?q=80&w=800'),
(10, 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?q=80&w=800'),
(11, 'https://images.unsplash.com/photo-1630308759363-ef2ae313364f?q=80&w=800'),
(12, 'https://images.unsplash.com/photo-1595180447330-80252655073e?q=80&w=800'),
(13, 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?q=80&w=800'),
(14, 'https://images.unsplash.com/photo-1519457431-75514b723b93?q=80&w=800'),
(15, 'https://images.unsplash.com/photo-1519278444521-59330fbfa32c?q=80&w=800'),
(16, 'https://images.unsplash.com/photo-1617114919297-3c8ddb01f599?q=80&w=800'),
(17, 'https://images.unsplash.com/photo-1514989940723-e8e51635b782?q=80&w=800'),
(18, 'https://images.unsplash.com/photo-1530124560676-587cabeeaad4?q=80&w=800');
