-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: fashion_store
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `fashion_store`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `fashion_store` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `fashion_store`;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `variation_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `variation_id` (`variation_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (1,4,4,1,'2026-05-16 15:14:26');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=154 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Tops','T-shirts, blouses, shirts, and knitwear'),(2,'Accessories','Bags, watches, belts, and jewellery'),(3,'Bottoms','Jeans, trousers, skirts, and shorts'),(29,'Outerwear','Coats, jackets, and blazers'),(30,'Footwear','Shoes, boots, sandals, and sneakers'),(31,'Loungewear','Comfortable clothing for home and travel');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enquiries`
--

DROP TABLE IF EXISTS `enquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `response` text DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `enquiries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enquiries`
--

LOCK TABLES `enquiries` WRITE;
/*!40000 ALTER TABLE `enquiries` DISABLE KEYS */;
/*!40000 ALTER TABLE `enquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enquiry_messages`
--

DROP TABLE IF EXISTS `enquiry_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enquiry_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enquiry_id` int(11) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `enquiry_id` (`enquiry_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `enquiry_messages_ibfk_1` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enquiry_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enquiry_messages`
--

LOCK TABLES `enquiry_messages` WRITE;
/*!40000 ALTER TABLE `enquiry_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `enquiry_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES (1,'How do I track my order?','Sign in and open your buyer dashboard to see the latest order status.','2026-06-12 08:29:28'),(2,'Can I return an item?','Contact support with your order details. Eligibility depends on condition and purchase date.','2026-06-12 08:29:28'),(3,'How do vouchers work?','Available vouchers appear during checkout and can be applied before placing an order.','2026-06-12 08:29:28');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `variation_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `variation_id` (`variation_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (8,8,52,5,195.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','completed','refund_requested','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `address` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `stock_restored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (8,2,NULL,975.00,'completed','Willie Teoh, 31, Jalan USJ 14/1c, Subang Jaya, 47630, Malaysia','2026-06-24 15:08:40',0,'2026-06-24 07:08:31');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `image_data` longblob DEFAULT NULL,
  `mime_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=197 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (134,37,'assets/img/products/women/dress.jpg',NULL,'image/jpeg'),(135,38,'assets/img/products/women/short-skirt.jpg',NULL,'image/jpeg'),(136,39,'assets/img/products/women/handbag.jpg',NULL,'image/jpeg'),(137,40,'assets/img/products/women/coats-jackets.jpg',NULL,'image/jpeg'),(138,41,'assets/img/products/men/essential-t-shirts.jpg',NULL,'image/jpeg'),(139,42,'assets/img/products/men/cargo-shorts.jpg',NULL,'image/jpeg'),(140,43,'assets/img/products/men/essentials-sweater.jpg',NULL,'image/jpeg'),(141,44,'assets/img/products/kids/surf-sweatshirt.jpg',NULL,'image/jpeg'),(142,45,'assets/img/products/kids/gelato-graphic-tee.jpg',NULL,'image/jpeg'),(143,46,'assets/img/products/kids/pants.jpg',NULL,'image/jpeg'),(144,47,'assets/img/products/women/knitted-vest.jpg',NULL,'image/jpeg'),(145,48,'assets/img/products/women/necklaces.jpg',NULL,'image/jpeg'),(146,49,'assets/img/products/women/rings.jpg',NULL,'image/jpeg'),(147,50,'assets/img/products/women/sweater.jpg',NULL,'image/jpeg'),(148,51,'assets/img/products/men/denim-jeans.jpg',NULL,'image/jpeg'),(149,52,'assets/img/products/men/duffle-bag.jpg',NULL,'image/jpeg'),(150,53,'assets/img/products/kids/graphic-tee-flare-leggings-set.jpg',NULL,'image/jpeg'),(151,54,'assets/img/products/women/dress.jpg',NULL,'image/jpeg'),(152,55,'assets/img/products/women/short-skirt.jpg',NULL,'image/jpeg'),(153,56,'assets/img/products/women/handbag.jpg',NULL,'image/jpeg'),(154,57,'assets/img/products/women/coats-jackets.jpg',NULL,'image/jpeg'),(155,58,'assets/img/products/men/essential-t-shirts.jpg',NULL,'image/jpeg'),(156,59,'assets/img/products/men/cargo-shorts.jpg',NULL,'image/jpeg'),(157,60,'assets/img/products/men/essentials-sweater.jpg',NULL,'image/jpeg'),(158,61,'assets/img/products/kids/surf-sweatshirt.jpg',NULL,'image/jpeg'),(159,62,'assets/img/products/kids/gelato-graphic-tee.jpg',NULL,'image/jpeg'),(160,63,'assets/img/products/kids/pants.jpg',NULL,'image/jpeg'),(161,69,'assets/img/products/women/dress.jpg',NULL,'image/jpeg'),(162,70,'assets/img/products/women/short-skirt.jpg',NULL,'image/jpeg'),(163,71,'assets/img/products/women/handbag.jpg',NULL,'image/jpeg'),(164,72,'assets/img/products/women/coats-jackets.jpg',NULL,'image/jpeg'),(165,73,'assets/img/products/men/essential-t-shirts.jpg',NULL,'image/jpeg'),(166,74,'assets/img/products/men/cargo-shorts.jpg',NULL,'image/jpeg'),(167,75,'assets/img/products/men/essentials-sweater.jpg',NULL,'image/jpeg'),(168,76,'assets/img/products/kids/surf-sweatshirt.jpg',NULL,'image/jpeg'),(169,77,'assets/img/products/kids/gelato-graphic-tee.jpg',NULL,'image/jpeg'),(170,78,'assets/img/products/kids/pants.jpg',NULL,'image/jpeg');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variations`
--

DROP TABLE IF EXISTS `product_variations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_variations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=164 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variations`
--

LOCK TABLES `product_variations` WRITE;
/*!40000 ALTER TABLE `product_variations` DISABLE KEYS */;
INSERT INTO `product_variations` VALUES (1,1,'S','White',26),(2,1,'M','White',2),(3,1,'L','White',10),(4,3,'30','Indigo',12),(5,3,'32','Indigo',8),(6,3,'34','Indigo',5),(7,5,'One Size','Black/Gold',5),(8,1,'S','White',20),(9,1,'M','White',15),(10,1,'L','White',10),(11,3,'30','Indigo',12),(12,3,'32','Indigo',8),(13,3,'34','Indigo',5),(14,5,'One Size','Black/Gold',5),(15,1,'S','White',20),(16,1,'M','White',15),(17,1,'L','White',10),(18,3,'30','Indigo',12),(19,3,'32','Indigo',8),(20,3,'34','Indigo',5),(21,5,'One Size','Black/Gold',5),(22,1,'S','White',20),(23,1,'M','White',15),(24,1,'L','White',10),(25,3,'30','Indigo',12),(26,3,'32','Indigo',8),(27,3,'34','Indigo',5),(28,5,'One Size','Black/Gold',5),(29,1,'S','White',20),(30,1,'M','White',15),(31,1,'L','White',10),(32,3,'30','Indigo',12),(33,3,'32','Indigo',8),(34,3,'34','Indigo',5),(35,5,'One Size','Black/Gold',5),(36,1,'S','White',20),(37,1,'M','White',15),(38,1,'L','White',10),(39,3,'30','Indigo',12),(40,3,'32','Indigo',8),(41,3,'34','Indigo',5),(42,5,'One Size','Black/Gold',5),(43,37,'S','Ivory',12),(44,37,'M','Ivory',14),(45,37,'L','Black',8),(46,38,'S','Indigo',10),(47,38,'M','Indigo',13),(48,38,'L','Indigo',7),(49,39,'One Size','Tan',8),(50,39,'One Size','Black',6),(51,40,'S','Camel',4),(52,40,'M','Camel',7),(53,40,'L','Camel',3),(54,41,'S','White',10),(55,41,'M','White',14),(56,41,'L','Blue',9),(57,42,'30','Khaki',8),(58,42,'32','Khaki',11),(59,42,'34','Navy',6),(60,43,'M','Navy',6),(61,43,'L','Navy',5),(62,43,'XL','Black',2),(63,44,'S','White',14),(64,44,'M','Green',12),(65,44,'L','Blue',10),(66,45,'S','Blue',8),(67,45,'M','Blue',9),(68,45,'L','Blue',6),(69,46,'One Size','Red',11),(70,46,'One Size','Blue',8),(71,47,'S','Green',10),(72,47,'M','Green',12),(73,47,'L','Green',8),(74,48,'One Size','Gold',10),(75,48,'One Size','Silver',9),(76,49,'6','Gold',8),(77,49,'7','Gold',10),(78,49,'8','Gold',7),(79,50,'S','Cream',9),(80,50,'M','Cream',12),(81,50,'L','Cream',8),(82,51,'30','Blue',8),(83,51,'32','Blue',11),(84,51,'34','Blue',7),(85,52,'One Size','Black',14),(86,53,'S','Multicolour',10),(87,53,'M','Multicolour',12),(88,53,'L','Multicolour',9),(102,54,'S','Ivory',12),(103,54,'M','Ivory',15),(104,54,'L','Black',8),(105,55,'S','Indigo',10),(106,55,'M','Indigo',14),(107,55,'L','Indigo',7),(108,56,'One Size','Tan',9),(109,56,'One Size','Black',6),(110,57,'S','Camel',4),(111,57,'M','Camel',7),(112,57,'L','Camel',3),(113,58,'S','White',10),(114,58,'M','White',14),(115,58,'L','Blue',9),(116,59,'30','Khaki',8),(117,59,'32','Khaki',11),(118,59,'34','Navy',6),(119,60,'M','Navy',6),(120,60,'L','Navy',5),(121,60,'XL','Black',2),(122,61,'S','White',14),(123,61,'M','Green',12),(124,61,'L','Blue',10),(125,62,'S','Blue',8),(126,62,'M','Blue',9),(127,62,'L','Blue',6),(128,63,'One Size','Red',11),(129,63,'One Size','Blue',8),(133,69,'S','Ivory',12),(134,69,'M','Ivory',15),(135,69,'L','Black',8),(136,70,'S','Indigo',10),(137,70,'M','Indigo',14),(138,70,'L','Indigo',7),(139,71,'One Size','Tan',9),(140,71,'One Size','Black',6),(141,72,'S','Camel',4),(142,72,'M','Camel',7),(143,72,'L','Camel',3),(144,73,'S','White',10),(145,73,'M','White',14),(146,73,'L','Blue',9),(147,74,'30','Khaki',8),(148,74,'32','Khaki',11),(149,74,'34','Navy',6),(150,75,'M','Navy',6),(151,75,'L','Navy',5),(152,75,'XL','Black',2),(153,76,'S','White',14),(154,76,'M','Green',12),(155,76,'L','Blue',10),(156,77,'S','Blue',8),(157,77,'M','Blue',9),(158,77,'L','Blue',6),(159,78,'One Size','Red',11),(160,78,'One Size','Blue',8);
/*!40000 ALTER TABLE `product_variations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `size_chart` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `gender` enum('Men','Women','Kids','Unisex') DEFAULT 'Unisex',
  `status` enum('published','draft','scheduled') DEFAULT 'published',
  `publish_at` datetime DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'Classic White Tee','A timeless essential made from 100% organic cotton.',NULL,25.00,0.00,NULL,'Unisex','draft',NULL,0,23,'2026-05-16 15:01:41'),(2,1,'Silk Evening Blouse','Elegant silk blouse perfect for formal gatherings.',NULL,85.00,0.00,NULL,'Unisex','draft',NULL,0,8,'2026-05-16 15:01:41'),(3,2,'Slim Fit Indigo Jeans','High-quality denim with a comfortable stretch.',NULL,65.00,0.00,NULL,'Unisex','draft',NULL,0,5,'2026-05-16 15:01:41'),(4,2,'Black Tailored Trousers','Professional look with a modern tapered fit.',NULL,55.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-05-16 15:01:41'),(5,3,'Minimalist Leather Watch','Sleek black leather strap with a gold-tone dial.',NULL,120.00,0.00,NULL,'Unisex','draft',NULL,0,3,'2026-05-16 15:01:41'),(6,3,'Canvas Tote Bag','Spacious and durable tote for your daily essentials.',NULL,35.00,0.00,NULL,'Unisex','draft',NULL,0,1,'2026-05-16 15:01:41'),(8,1,'Silk Evening Blouse','Elegant silk blouse perfect for formal gatherings.',NULL,85.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-05-16 15:01:45'),(10,2,'Black Tailored Trousers','Professional look with a modern tapered fit.',NULL,55.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-05-16 15:01:45'),(12,3,'Canvas Tote Bag','Spacious and durable tote for your daily essentials.',NULL,35.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-05-16 15:01:45'),(14,1,'Silk Evening Blouse','Elegant silk blouse perfect for formal gatherings.',NULL,85.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-06-12 07:43:41'),(16,2,'Black Tailored Trousers','Professional look with a modern tapered fit.',NULL,55.00,0.00,NULL,'Unisex','draft',NULL,0,1,'2026-06-12 07:43:41'),(18,3,'Canvas Tote Bag','Spacious and durable tote for your daily essentials.',NULL,35.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-06-12 07:43:41'),(20,1,'Silk Evening Blouse','Elegant silk blouse perfect for formal gatherings.',NULL,85.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-06-12 07:43:42'),(22,2,'Black Tailored Trousers','Professional look with a modern tapered fit.',NULL,55.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-06-12 07:43:42'),(24,3,'Canvas Tote Bag','Spacious and durable tote for your daily essentials.',NULL,35.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-06-12 07:43:42'),(26,1,'Silk Evening Blouse','Elegant silk blouse perfect for formal gatherings.',NULL,85.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-06-12 07:43:49'),(28,2,'Black Tailored Trousers','Professional look with a modern tapered fit.',NULL,55.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-06-12 07:43:49'),(30,3,'Canvas Tote Bag','Spacious and durable tote for your daily essentials.',NULL,35.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-06-12 07:43:49'),(32,1,'Silk Evening Blouse','Elegant silk blouse perfect for formal gatherings.',NULL,85.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-06-12 07:56:41'),(34,2,'Black Tailored Trousers','Professional look with a modern tapered fit.',NULL,55.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-06-12 07:56:41'),(36,3,'Canvas Tote Bag','Spacious and durable tote for your daily essentials.',NULL,35.00,0.00,NULL,'Unisex','draft',NULL,0,0,'2026-06-12 07:56:41'),(37,1,'Women\'s Dress','A versatile women\'s dress with an elegant everyday silhouette.',NULL,85.00,42.00,NULL,'Women','published',NULL,1,3,'2026-06-12 08:29:28'),(38,3,'Women\'s Short Skirt','A structured short skirt designed for easy everyday styling.',NULL,49.00,24.00,NULL,'Women','published',NULL,1,6,'2026-06-12 08:29:28'),(39,2,'Women\'s Handbag','A polished handbag with space for daily essentials.',NULL,145.00,72.00,NULL,'Women','published',NULL,1,6,'2026-06-12 08:29:28'),(40,29,'Women\'s Coats & Jackets','A fitted women\'s jacket with a clean contemporary finish.',NULL,195.00,98.00,NULL,'Women','published',NULL,1,10,'2026-06-12 08:29:28'),(41,1,'Men\'s Essential T-Shirts','A set of clean essential T-shirts in versatile neutral colours.',NULL,55.00,25.00,NULL,'Men','published',NULL,1,5,'2026-06-12 08:29:28'),(42,3,'Men\'s Cargo Shorts','Relaxed cargo shorts with practical utility pockets.',NULL,65.00,31.00,NULL,'Men','published',NULL,1,0,'2026-06-12 08:29:28'),(43,1,'Men\'s Essentials Sweater','A relaxed mock-neck Essentials sweater for comfortable layering.',NULL,79.00,38.00,NULL,'Men','published',NULL,1,0,'2026-06-12 08:29:28'),(44,1,'Kids Surf Graphic Sweatshirt','A soft cotton-rich sweatshirt with a playful surf graphic.',NULL,45.00,20.00,NULL,'Kids','published',NULL,1,0,'2026-06-12 08:29:28'),(45,1,'Kids Oversized Gelato Graphic Tee','An oversized green tee with a colourful gelato graphic.',NULL,40.00,18.00,NULL,'Kids','published',NULL,1,0,'2026-06-12 08:29:28'),(46,3,'Kids Casual Pants','Comfortable casual pants designed for active everyday wear.',NULL,30.00,13.00,NULL,'Kids','published',NULL,1,0,'2026-06-12 08:29:28'),(47,1,'Women\'s Knitted Vest','A green argyle knitted vest for layered outfits.',NULL,59.00,28.00,NULL,'Women','published',NULL,1,0,'2026-06-27 08:30:23'),(48,2,'Women\'s Necklaces','A refined necklace collection for everyday accessorising.',NULL,39.00,16.00,NULL,'Women','published',NULL,0,0,'2026-06-27 08:30:23'),(49,2,'Women\'s Rings','A coordinated ring collection with a polished finish.',NULL,35.00,14.00,NULL,'Women','published',NULL,0,0,'2026-06-27 08:30:23'),(50,1,'Women\'s Sweater','A comfortable women\'s sweater for effortless layering.',NULL,69.00,32.00,NULL,'Women','published',NULL,1,0,'2026-06-27 08:30:23'),(51,3,'Men\'s Denim Jeans','Classic denim jeans with a versatile everyday fit.',NULL,89.00,42.00,NULL,'Men','published',NULL,1,0,'2026-06-27 08:30:23'),(52,2,'Men\'s Duffle Bag','A spacious black duffle bag for travel and training.',NULL,75.00,34.00,NULL,'Men','published',NULL,0,0,'2026-06-27 08:30:23'),(53,1,'Kids Graphic T-Shirt & Flare Leggings Set','A colourful graphic T-shirt and flare leggings outfit set.',NULL,55.00,25.00,NULL,'Kids','published',NULL,1,0,'2026-06-27 08:30:23'),(54,1,'Women\'s Dress','A versatile women\'s dress with an elegant everyday silhouette.',NULL,85.00,42.00,NULL,'Women','published',NULL,1,0,'2026-06-27 10:29:15'),(55,3,'Women\'s Short Skirt','A structured short skirt designed for easy everyday styling.',NULL,49.00,24.00,NULL,'Women','published',NULL,1,0,'2026-06-27 10:29:15'),(56,2,'Women\'s Handbag','A polished handbag with space for daily essentials.',NULL,145.00,72.00,NULL,'Women','published',NULL,1,0,'2026-06-27 10:29:15'),(57,29,'Women\'s Coats & Jackets','A fitted women\'s jacket with a clean contemporary finish.',NULL,195.00,98.00,NULL,'Women','published',NULL,1,0,'2026-06-27 10:29:15'),(58,1,'Men\'s Essential T-Shirts','A set of clean essential T-shirts in versatile neutral colours.',NULL,55.00,25.00,NULL,'Men','published',NULL,1,0,'2026-06-27 10:29:15'),(59,3,'Men\'s Cargo Shorts','Relaxed cargo shorts with practical utility pockets.',NULL,65.00,31.00,NULL,'Men','published',NULL,1,0,'2026-06-27 10:29:15'),(60,1,'Men\'s Essentials Sweater','A relaxed mock-neck Essentials sweater for comfortable layering.',NULL,79.00,38.00,NULL,'Men','published',NULL,1,0,'2026-06-27 10:29:15'),(61,1,'Kids Surf Graphic Sweatshirt','A soft cotton-rich sweatshirt with a playful surf graphic.',NULL,45.00,20.00,NULL,'Kids','published',NULL,1,0,'2026-06-27 10:29:15'),(62,1,'Kids Oversized Gelato Graphic Tee','An oversized green tee with a colourful gelato graphic.',NULL,40.00,18.00,NULL,'Kids','published',NULL,1,0,'2026-06-27 10:29:15'),(63,3,'Kids Casual Pants','Comfortable casual pants designed for active everyday wear.',NULL,30.00,13.00,NULL,'Kids','published',NULL,1,0,'2026-06-27 10:29:15'),(69,1,'Women\'s Dress','A versatile women\'s dress with an elegant everyday silhouette.',NULL,85.00,42.00,NULL,'Women','published',NULL,1,0,'2026-06-27 13:42:11'),(70,3,'Women\'s Short Skirt','A structured short skirt designed for easy everyday styling.',NULL,49.00,24.00,NULL,'Women','published',NULL,1,0,'2026-06-27 13:42:11'),(71,2,'Women\'s Handbag','A polished handbag with space for daily essentials.',NULL,145.00,72.00,NULL,'Women','published',NULL,1,0,'2026-06-27 13:42:11'),(72,29,'Women\'s Coats & Jackets','A fitted women\'s jacket with a clean contemporary finish.',NULL,195.00,98.00,NULL,'Women','published',NULL,1,0,'2026-06-27 13:42:11'),(73,1,'Men\'s Essential T-Shirts','A set of clean essential T-shirts in versatile neutral colours.',NULL,55.00,25.00,NULL,'Men','published',NULL,1,0,'2026-06-27 13:42:11'),(74,3,'Men\'s Cargo Shorts','Relaxed cargo shorts with practical utility pockets.',NULL,65.00,31.00,NULL,'Men','published',NULL,1,0,'2026-06-27 13:42:11'),(75,1,'Men\'s Essentials Sweater','A relaxed mock-neck Essentials sweater for comfortable layering.',NULL,79.00,38.00,NULL,'Men','published',NULL,1,0,'2026-06-27 13:42:11'),(76,1,'Kids Surf Graphic Sweatshirt','A soft cotton-rich sweatshirt with a playful surf graphic.',NULL,45.00,20.00,NULL,'Kids','published',NULL,1,0,'2026-06-27 13:42:11'),(77,1,'Kids Oversized Gelato Graphic Tee','An oversized green tee with a colourful gelato graphic.',NULL,40.00,18.00,NULL,'Kids','published',NULL,1,0,'2026-06-27 13:42:11'),(78,3,'Kids Casual Pants','Comfortable casual pants designed for active everyday wear.',NULL,30.00,13.00,NULL,'Kids','published',NULL,1,0,'2026-06-27 13:42:11');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_alerts`
--

DROP TABLE IF EXISTS `system_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `priority` enum('critical','warning','info') DEFAULT 'info',
  `message` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_system_alerts_read_priority` (`is_read`,`priority`),
  KEY `idx_system_alerts_type_reference` (`type`,`reference_id`),
  KEY `idx_system_alerts_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_alerts`
--

LOCK TABLES `system_alerts` WRITE;
/*!40000 ALTER TABLE `system_alerts` DISABLE KEYS */;
INSERT INTO `system_alerts` VALUES (1,'low_stock','warning','Warning: Classic White Tee has 2 variations running low.\n\nAffected Variations:\n- L / White (10)\n- L / White (10)',1,1,'2026-05-16 15:23:18'),(2,'low_stock','warning','Warning: Slim Fit Indigo Jeans has 4 variations running low.\n\nAffected Variations:\n- 32 / Indigo (8)\n- 34 / Indigo (5)\n- 32 / Indigo (8)\n- 34 / Indigo (5)',3,1,'2026-05-16 15:23:18'),(3,'low_stock','warning','Warning: Minimalist Leather Watch has 2 variations running low.\n\nAffected Variations:\n- One Size / Black/Gold (5)\n- One Size / Black/Gold (5)',5,1,'2026-05-16 15:23:18'),(4,'low_stock','warning','Warning: Silk Evening Blouse has 1 variations running low.\n\nAffected Variations:\n- L / Black (8)',37,1,'2026-06-12 13:28:27'),(5,'low_stock','warning','Warning: High-Rise Skinny Jeans has 2 variations running low.\n\nAffected Variations:\n- S / Indigo (10)\n- L / Indigo (7)',38,1,'2026-06-12 13:28:27'),(6,'low_stock','warning','Warning: Leather Tote Bag has 2 variations running low.\n\nAffected Variations:\n- One Size / Black (6)\n- One Size / Tan (9)',39,1,'2026-06-12 13:28:27'),(7,'low_stock','warning','Warning: Wool Blend Overcoat has 3 variations running low.\n\nAffected Variations:\n- S / Camel (4)\n- M / Camel (7)\n- L / Camel (3)',40,1,'2026-06-12 13:28:27'),(8,'low_stock','warning','Warning: Oxford Button-Down has 2 variations running low.\n\nAffected Variations:\n- S / White (10)\n- L / Blue (9)',41,1,'2026-06-12 13:28:27'),(9,'low_stock','warning','Warning: Straight Fit Chinos has 2 variations running low.\n\nAffected Variations:\n- 30 / Khaki (8)\n- 34 / Navy (6)',42,1,'2026-06-12 13:28:27'),(10,'low_stock','warning','Warning: Harrington Jacket has 3 variations running low.\n\nAffected Variations:\n- XL / Black (2)\n- L / Navy (5)\n- M / Navy (6)',43,1,'2026-06-12 13:28:27'),(11,'low_stock','warning','Warning: Animal Print Tee has 1 variations running low.\n\nAffected Variations:\n- L / Blue (10)',44,1,'2026-06-12 13:28:27'),(12,'low_stock','warning','Warning: Stretch Denim Overalls has 3 variations running low.\n\nAffected Variations:\n- S / Blue (8)\n- M / Blue (9)\n- L / Blue (6)',45,1,'2026-06-12 13:28:27'),(13,'low_stock','warning','Warning: Colourful Backpack has 1 variations running low.\n\nAffected Variations:\n- One Size / Blue (8)',46,1,'2026-06-12 13:28:27'),(14,'sales_spike','info','Sales Spike: Current sales are 200% above the 7-day average.',NULL,1,'2026-06-24 07:09:09'),(15,'low_stock','warning','Warning: Women\'s Knitted Vest has 2 variations running low.\n\nAffected Variations:\n- S / Green (10)\n- L / Green (8)',47,1,'2026-06-27 09:35:40'),(16,'low_stock','warning','Warning: Women\'s Necklaces has 2 variations running low.\n\nAffected Variations:\n- One Size / Gold (10)\n- One Size / Silver (9)',48,1,'2026-06-27 09:35:40'),(17,'low_stock','warning','Warning: Women\'s Rings has 3 variations running low.\n\nAffected Variations:\n- 8 / Gold (7)\n- 7 / Gold (10)\n- 6 / Gold (8)',49,1,'2026-06-27 09:35:40'),(18,'low_stock','warning','Warning: Women\'s Sweater has 2 variations running low.\n\nAffected Variations:\n- S / Cream (9)\n- L / Cream (8)',50,1,'2026-06-27 09:35:40'),(19,'low_stock','warning','Warning: Men\'s Denim Jeans has 2 variations running low.\n\nAffected Variations:\n- 30 / Blue (8)\n- 34 / Blue (7)',51,1,'2026-06-27 09:35:40'),(20,'low_stock','warning','Warning: Kids Graphic T-Shirt & Flare Leggings Set has 2 variations running low.\n\nAffected Variations:\n- S / Multicolour (10)\n- L / Multicolour (9)',53,1,'2026-06-27 09:35:40'),(21,'low_stock','warning','Warning: Classic White Tee has 7 variations running low.\n\nAffected Variations:\n- M / White (2)\n- L / White (10)\n- L / White (10)\n- L / White (10)\n- L / White (10)\n- L / White (10)\n- L / White (10)',1,1,'2026-06-27 09:48:12'),(22,'low_stock','warning','Warning: Slim Fit Indigo Jeans has 12 variations running low.\n\nAffected Variations:\n- 32 / Indigo (8)\n- 32 / Indigo (8)\n- 34 / Indigo (5)\n- 34 / Indigo (5)\n- 32 / Indigo (8)\n- 34 / Indigo (5)\n- 34 / Indigo (5)\n- 32 / Indigo (8)\n- 32 / Indigo (8)\n- 34 / Indigo (5)\n- 32 / Indigo (8)\n- 34 / Indigo (5)',3,1,'2026-06-27 09:48:12'),(23,'low_stock','warning','Warning: Minimalist Leather Watch has 6 variations running low.\n\nAffected Variations:\n- One Size / Black/Gold (5)\n- One Size / Black/Gold (5)\n- One Size / Black/Gold (5)\n- One Size / Black/Gold (5)\n- One Size / Black/Gold (5)\n- One Size / Black/Gold (5)',5,1,'2026-06-27 09:48:12'),(24,'low_stock','warning','Warning: Women\'s Dress has 1 variations running low.\n\nAffected Variations:\n- L / Black (8)',37,1,'2026-06-27 09:48:12'),(25,'low_stock','warning','Warning: Women\'s Short Skirt has 2 variations running low.\n\nAffected Variations:\n- S / Indigo (10)\n- L / Indigo (7)',38,1,'2026-06-27 09:48:12'),(26,'low_stock','warning','Warning: Women\'s Handbag has 2 variations running low.\n\nAffected Variations:\n- One Size / Black (6)\n- One Size / Tan (8)',39,1,'2026-06-27 09:48:12'),(27,'low_stock','warning','Warning: Women\'s Coats & Jackets has 3 variations running low.\n\nAffected Variations:\n- S / Camel (4)\n- M / Camel (7)\n- L / Camel (3)',40,1,'2026-06-27 09:48:12'),(28,'low_stock','warning','Warning: Men\'s Essential T-Shirts has 2 variations running low.\n\nAffected Variations:\n- S / White (10)\n- L / Blue (9)',41,1,'2026-06-27 09:48:12'),(29,'low_stock','warning','Warning: Men\'s Cargo Shorts has 2 variations running low.\n\nAffected Variations:\n- 34 / Navy (6)\n- 30 / Khaki (8)',42,1,'2026-06-27 09:48:12'),(30,'low_stock','warning','Warning: Men\'s Essentials Sweater has 3 variations running low.\n\nAffected Variations:\n- M / Navy (6)\n- L / Navy (5)\n- XL / Black (2)',43,1,'2026-06-27 09:48:12'),(31,'low_stock','warning','Warning: Kids Surf Graphic Sweatshirt has 1 variations running low.\n\nAffected Variations:\n- L / Blue (10)',44,1,'2026-06-27 09:48:12'),(32,'low_stock','warning','Warning: Kids Oversized Gelato Graphic Tee has 3 variations running low.\n\nAffected Variations:\n- L / Blue (6)\n- M / Blue (9)\n- S / Blue (8)',45,1,'2026-06-27 09:48:12'),(33,'low_stock','warning','Warning: Kids Casual Pants has 1 variations running low.\n\nAffected Variations:\n- One Size / Blue (8)',46,1,'2026-06-27 09:48:12');
/*!40000 ALTER TABLE `system_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=155 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'low_stock_threshold','10','2026-05-16 15:18:17'),(2,'overstock_threshold','100','2026-05-16 15:18:17'),(3,'dashboard_active_alerts','out_of_stock,low_stock,overstock','2026-06-27 10:29:15');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('buyer','manager','admin','owner') DEFAULT 'buyer',
  `full_name` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'manager_demo','manager@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','manager','Demo Manager',NULL,NULL,1,NULL,NULL,'2026-05-16 15:01:41'),(3,'admin_demo','admin@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','admin','Demo Administrator',NULL,NULL,1,NULL,NULL,'2026-05-16 15:01:41'),(4,'owner_demo','owner@example.com','$2y$10$W1tv6uVIGgGjGSqsCUi0n.IF1W3Q2Ep/qikAOT2td0YQZL/.apDR2','owner','Demo Owner',NULL,NULL,1,NULL,NULL,'2026-05-16 15:01:41');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher_redemptions`
--

DROP TABLE IF EXISTS `voucher_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voucher_redemptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voucher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `voucher_id` (`voucher_id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `voucher_redemptions_ibfk_1` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `voucher_redemptions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `voucher_redemptions_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher_redemptions`
--

LOCK TABLES `voucher_redemptions` WRITE;
/*!40000 ALTER TABLE `voucher_redemptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `voucher_redemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vouchers`
--

DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `campaign` varchar(100) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_spend` decimal(10,2) DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `is_one_time` tinyint(1) DEFAULT 1,
  `is_used` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `target_type` enum('all','specific','group') DEFAULT 'all',
  `target_user_id` int(11) DEFAULT NULL,
  `target_group` enum('new','repeat','vip','inactive','reviewers') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vouchers`
--

LOCK TABLES `vouchers` WRITE;
/*!40000 ALTER TABLE `vouchers` DISABLE KEYS */;
INSERT INTO `vouchers` VALUES (1,'WELCOME10','Welcome Campaign','percentage',10.00,50.00,'2027-12-31',NULL,1,1,1,NULL,'all',NULL,NULL,'2026-05-16 15:01:41'),(2,'FASHION20','Fashion Campaign','fixed',20.00,0.00,'2026-12-31',NULL,1,0,1,NULL,'all',NULL,NULL,'2026-05-16 15:01:41'),(60,'SAVE20',NULL,'fixed',20.00,120.00,'2027-12-31',NULL,1,0,1,NULL,'all',NULL,NULL,'2026-06-27 13:42:11');
/*!40000 ALTER TABLE `vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'fashion_store'
--

--
-- Dumping routines for database 'fashion_store'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-27 22:01:09
