-- Fashion Store – Seed Data
-- This file contains all INSERT statements for the fashion_store database.

USE `fashion_store`;

/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- categories
LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Tops','T-shirts, blouses, shirts, and knitwear'),(2,'Accessories','Bags, watches, belts, and jewellery'),(3,'Bottoms','Jeans, trousers, skirts, and shorts'),(29,'Outerwear','Coats, jackets, and blazers'),(30,'Footwear','Shoes, boots, sandals, and sneakers'),(31,'Loungewear','Comfortable clothing for home and travel');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

-- faqs
LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES (1,'How do I track my order?','Sign in and open your buyer dashboard to see the latest order status.','2026-06-12 08:29:28'),(2,'Can I return an item?','Contact support with your order details. Eligibility depends on condition and purchase date.','2026-06-12 08:29:28'),(3,'How do vouchers work?','Available vouchers appear during checkout and can be applied before placing an order.','2026-06-12 08:29:28');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

-- products
LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'Kids Gelato Graphic Tee','Crafted from soft premium cotton, the Kids Gelato Graphic Tee features a playful graphic print and a relaxed fit for all-day comfort. Lightweight, breathable, and easy to pair with shorts or jeans, it is perfect for everyday adventures.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',15.00,0.00,NULL,'Kids','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (2,1,'Kids Graphic Tee Flare Leggings Set','A stylish two-piece outfit featuring a soft graphic tee paired with comfortable flare leggings. Designed for active kids, this coordinated set offers flexibility, comfort, and effortless everyday style.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',25.00,0.00,NULL,'Kids','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (3,3,'Kids Pants','Designed for everyday comfort, these kids pants feature a relaxed fit with soft, durable fabric that allows unrestricted movement. Perfect for school, outdoor play, and casual family outings','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',55.00,0.00,NULL,'Kids','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (4,1,'Kids Surf Sweatshirt','Inspired by coastal adventures, the Kids Surf Sweatshirt combines soft fleece fabric with a relaxed silhouette to keep children warm and comfortable. Ideal for cooler weather and everyday wear.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',65.00,0.00,NULL,'Kids','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (5,3,'Men Cargo Shorts','Built for both functionality and style, these cargo shorts feature multiple utility pockets and a comfortable relaxed fit. Crafted from durable fabric, they are perfect for casual outings, travel, and everyday wear.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',35.00,0.00,NULL,'Men','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (6,3,'Men Denim Jeans','Crafted from premium stretch denim, these jeans provide a modern fit with lasting comfort. Designed for versatile everyday styling, they pair effortlessly with T-shirts, shirts, or jackets.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',75.00,0.00,NULL,'Men','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (7,2,'Men Duffle Bag','Designed for everyday versatility, this spacious duffle bag offers ample storage for gym sessions, weekend trips, or daily commuting. Durable construction and clean minimalist styling make it a practical companion wherever you go.','',90.00,0.00,NULL,'Men','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (8,1,'Men Essential T Shirts','An everyday wardrobe essential crafted from soft breathable cotton for exceptional comfort. Featuring a clean silhouette and regular fit, it is perfect for layering or wearing on its own.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',65.00,0.00,NULL,'Men','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (9,1,'Men Essential Sweater','Made from soft knitted fabric, this essential sweater delivers warmth without compromising comfort. Its timeless design makes it suitable for layering during cooler seasons or wearing as a standalone piece.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',75.00,0.00,NULL,'Men','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (10,29,'Women Coats Jacket','Tailored with a contemporary silhouette, this coat jacket provides warmth, elegance, and versatility. Designed to complement both casual and formal outfits throughout the colder seasons.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',65.00,0.00,NULL,'Women','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (11,1,'Women Dress','Designed with a flattering silhouette and flowing fabric, this dress offers effortless elegance for both casual occasions and special events. Comfortable, lightweight, and easy to style throughout the year.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',55.00,0.00,NULL,'Women','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (12,2,'Women Handbag','A timeless handbag designed with both functionality and sophistication in mind. Featuring a spacious interior and refined detailing, it is the perfect accessory for work, shopping, or everyday use.','',115.00,0.00,NULL,'Women','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (13,1,'Women Knitted Vest','Crafted from soft knitted fabric, this versatile vest provides lightweight warmth while adding texture and style to any outfit. Ideal for layering over shirts or dresses throughout the seasons.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',85.00,0.00,NULL,'Women','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (14,2,'Women Necklaces','Elevate your everyday style with this elegant necklace featuring a minimalist design that complements both casual and formal outfits. A timeless accessory suitable for every occasion.','',40.00,0.00,NULL,'Women','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (15,2,'Women Rings','Designed with understated elegance, this ring adds a refined finishing touch to your everyday wardrobe. Perfect for wearing alone or stacking with other jewellery pieces.','',45.00,0.00,NULL,'Women','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (16,3,'Women Short Skirt','Featuring a flattering high-waisted silhouette, this short skirt combines comfort with contemporary style. Crafted from lightweight fabric for effortless movement and versatile everyday wear.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',75.00,0.00,NULL,'Women','published',NULL,0,1,'2026-06-28 06:28:32');
INSERT INTO `products` VALUES (17,1,'Women Sweater','Crafted from soft premium knit fabric, this sweater offers exceptional comfort and warmth with a timeless silhouette. Perfect for layering during cooler weather or styling as an everyday essential.','Detailed Size Guide\\nAll measurements are in inches. For the best fit, we recommend measuring a similar garment you already own.\\n\\nSize | Chest / Bust | Waist | Hips | Length\\nXS | 32 - 33 | 24 - 25 | 34 - 35 | 24.5\\nS | 34 - 35 | 26 - 27 | 36 - 37 | 25.0\\nM | 36 - 37 | 28 - 29 | 38 - 39 | 25.5\\nL | 38 - 40 | 30 - 32 | 40 - 42 | 26.0\\nXL | 41 - 43 | 33 - 35 | 43 - 45 | 26.5\\n\\nHow to Measure\\nBust/Chest: Measure around the fullest part of your chest.\\nWaist: Measure around your natural waistline (narrowest part).\\nHips: Measure around the fullest part of your hips.',115.00,0.00,NULL,'Women','published',NULL,0,1,'2026-06-28 06:28:32');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

-- product_images
LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (NULL,1,'assets/uploads/products/kids-gelato-graphic-tee.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,2,'assets/uploads/products/kids-graphic-tee-flare-leggings-set.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,3,'assets/uploads/products/kids-pants.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,4,'assets/uploads/products/kids-surf-sweatshirt.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,5,'assets/uploads/products/men-cargo-shorts.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,6,'assets/uploads/products/men-denim-jeans.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,7,'assets/uploads/products/men-duffle-bag.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,8,'assets/uploads/products/men-essential-t-shirts.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,9,'assets/uploads/products/men-essentials-sweater.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,10,'assets/uploads/products/women-coats-jackets.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,11,'assets/uploads/products/women-dress.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,12,'assets/uploads/products/women-handbag.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,13,'assets/uploads/products/women-knitted-vest.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,14,'assets/uploads/products/women-necklaces.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,15,'assets/uploads/products/women-rings.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,16,'assets/uploads/products/women-short-skirt.jpg',NULL,'image/jpeg');
INSERT INTO `product_images` VALUES (NULL,17,'assets/uploads/products/women-sweater.jpg',NULL,'image/jpeg');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

-- product_variations
LOCK TABLES `product_variations` WRITE;
/*!40000 ALTER TABLE `product_variations` DISABLE KEYS */;
INSERT INTO `product_variations` VALUES (NULL,1,'XS','white',10);
INSERT INTO `product_variations` VALUES (NULL,1,'XS','blue',10);
INSERT INTO `product_variations` VALUES (NULL,1,'S','blue',10);
INSERT INTO `product_variations` VALUES (NULL,2,'S','blue',9);
INSERT INTO `product_variations` VALUES (NULL,3,'M','black',19);
INSERT INTO `product_variations` VALUES (NULL,4,'L','white',29);
INSERT INTO `product_variations` VALUES (NULL,2,'XL','blue',9);
INSERT INTO `product_variations` VALUES (NULL,3,'L','black',19);
INSERT INTO `product_variations` VALUES (NULL,4,'S','white',29);
INSERT INTO `product_variations` VALUES (NULL,3,'L','red',19);
INSERT INTO `product_variations` VALUES (NULL,4,'S','blue',29);
INSERT INTO `product_variations` VALUES (NULL,5,'XL','blue',39);
INSERT INTO `product_variations` VALUES (NULL,6,'M','black',49);
INSERT INTO `product_variations` VALUES (NULL,7,'L','white',59);
INSERT INTO `product_variations` VALUES (NULL,8,'L','blue',9);
INSERT INTO `product_variations` VALUES (NULL,9,'XL','black',9);
INSERT INTO `product_variations` VALUES (NULL,10,'XL','blue',39);
INSERT INTO `product_variations` VALUES (NULL,11,'M','black',49);
INSERT INTO `product_variations` VALUES (NULL,12,'L','white',59);
INSERT INTO `product_variations` VALUES (NULL,13,'L','blue',9);
INSERT INTO `product_variations` VALUES (NULL,14,'XL','black',9);
INSERT INTO `product_variations` VALUES (NULL,15,'XS','white',10);
INSERT INTO `product_variations` VALUES (NULL,16,'S','blue',9);
INSERT INTO `product_variations` VALUES (NULL,17,'M','black',19);
/*!40000 ALTER TABLE `product_variations` ENABLE KEYS */;
UNLOCK TABLES;

-- system_settings
LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'low_stock_threshold','10','2026-05-16 15:18:17'),(2,'overstock_threshold','100','2026-05-16 15:18:17'),(3,'dashboard_active_alerts','out_of_stock,low_stock,overstock','2026-06-27 10:29:15');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

-- users
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'manager_demo','manager@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','manager','Demo Manager',NULL,NULL,1,NULL,NULL,'2026-05-16 15:01:41'),(3,'admin_demo','admin@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','admin','Demo Administrator',NULL,NULL,1,NULL,NULL,'2026-05-16 15:01:41'),(4,'owner_demo','owner@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','owner','Demo Owner',NULL,NULL,1,NULL,NULL,'2026-05-16 15:01:41'),(5,'buyer_demo','buyer@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','buyer','Demo Buyer',NULL,NULL,1,NULL,NULL,'2026-06-27 14:23:17');
INSERT INTO `users` VALUES (6,'ali','ali@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','buyer','Ali',NULL,NULL,1,NULL,NULL,'2026-06-27 14:23:17'), (7,'lee','lee@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','buyer','Lee',NULL,NULL,1,NULL,NULL,'2026-06-27 08:23:17'), (8,'mary','mary@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','buyer','Mary',NULL,NULL,1,NULL,NULL,'2026-06-27 08:23:17');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

-- vouchers
LOCK TABLES `vouchers` WRITE;
/*!40000 ALTER TABLE `vouchers` DISABLE KEYS */;
INSERT INTO `vouchers` VALUES (1,'WELCOME10','Welcome Campaign','percentage',10.00,50.00,'2027-12-31',NULL,1,1,1,NULL,'all',NULL,NULL,'2026-05-16 15:01:41'),(2,'FASHION20','Fashion Campaign','fixed',20.00,0.00,'2026-12-31',NULL,1,0,1,NULL,'all',NULL,NULL,'2026-05-16 15:01:41');
/*!40000 ALTER TABLE `vouchers` ENABLE KEYS */;
UNLOCK TABLES;

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
 
LOCK TABLES `users` WRITE; 
INSERT INTO fashion_store.cart (user_id,variation_id,quantity,created_at) VALUES
	 (6,199,1,'2026-06-28 09:45:24');
INSERT INTO fashion_store.enquiries (user_id,subject,message,response,status,created_at) VALUES
	 (6,'Voucher Issue','How to use the voucher?',NULL,'open','2026-06-28 09:39:36');
INSERT INTO fashion_store.enquiry_messages (enquiry_id,sender_id,message,created_at) VALUES
	 (2,6,'How to use the voucher?','2026-06-28 09:39:36'),
	 (2,6,'how voucher works?','2026-06-28 09:44:34'),
	 (2,NULL,'[AUTO-REPLY] Regarding your question, this might help: 

Available vouchers appear during checkout and can be applied before placing an order.','2026-06-28 09:44:34');
INSERT INTO fashion_store.orders (id,user_id,voucher_id,total_amount,status,address,completed_at,stock_restored,created_at) VALUES
    (16,6,2,14.99,'completed','Ali, 123, Jalan ABC, puchong, 12122, Malaysia','2026-06-28 09:36:19',0,'2026-06-28 09:35:56');
INSERT INTO fashion_store.order_items (order_id,variation_id,quantity,price) VALUES
    (16,198,1,25.00);
INSERT INTO fashion_store.reviews (user_id,product_id,order_id,rating,comment,admin_reply,created_at) VALUES
	 (6,2,16,5,'Nice and Comfortable!',NULL,'2026-06-28 09:37:06');
INSERT INTO fashion_store.voucher_redemptions (voucher_id,user_id,order_id,redeemed_at) VALUES
	 (2,6,16,'2026-06-28 09:35:56');
LOCK TABLES `vouchers` WRITE;
INSERT INTO fashion_store.vouchers (code,campaign,discount_type,discount_value,min_spend,expiry_date,usage_limit,is_one_time,is_used,is_active,user_id,target_type,target_user_id,target_group,created_at) VALUES
    ('REV-B5018F05','Product Review Rewards','percentage',10.00,0.00,'2026-07-28',NULL,1,0,1,6,'specific',6,NULL,'2026-06-28 09:37:06');
UNLOCK TABLES;
