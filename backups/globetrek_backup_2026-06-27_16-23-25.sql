-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: globetrek
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
-- Table structure for table `accommodations`
--

DROP TABLE IF EXISTS `accommodations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accommodations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `location` varchar(200) NOT NULL,
  `property_type` enum('Hotel','Villa','Boutique','Resort') NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `image` varchar(500) DEFAULT NULL,
  `has_wifi` tinyint(1) DEFAULT 0,
  `has_pool` tinyint(1) DEFAULT 0,
  `has_spa` tinyint(1) DEFAULT 0,
  `has_restaurant` tinyint(1) DEFAULT 0,
  `has_fitness` tinyint(1) DEFAULT 0,
  `provider_name` varchar(150) DEFAULT NULL,
  `provider_email` varchar(150) DEFAULT NULL,
  `provider_phone` varchar(30) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accommodations`
--

LOCK TABLES `accommodations` WRITE;
/*!40000 ALTER TABLE `accommodations` DISABLE KEYS */;
INSERT INTO `accommodations` VALUES (1,'Coral Tide Resort','coral-tide-resort','A stunning beachfront resort overlooking the Indian Ocean in Unawatuna. Featuring private balconies, an infinity pool, and direct beach access. Perfect for romantic getaways and luxury retreats.','Beachfront luxury resort with infinity pool and ocean views in Unawatuna.','Unawatuna, Sri Lanka','Resort',350.00,4.9,'images/accommodations/accom_1782569307_3bbeaa9b.jpeg',1,1,1,1,1,'Coral Tide Hospitality','reservations@coraltide.lk','+94 91 234 5678',1,1,'2026-06-24 09:56:37','2026-06-27 14:08:27'),(2,'Hilltop Tea Lodge','hilltop-tea-lodge','A sustainable mountain lodge nestled among tea plantations in Hatton. Powered by solar energy, offering farm-to-table dining, guided tea estate walks, and panoramic mountain views from every room.','Sustainable mountain lodge with farm-to-table dining in the tea country.','Hatton, Sri Lanka','Boutique',210.00,4.7,'images/accommodations/accom_1782569341_3bb71cda.jpg',1,0,0,1,0,'Tea Country Retreats','bookings@teacountry.lk','+94 51 234 5678',1,1,'2026-06-24 09:56:37','2026-06-27 14:09:01'),(3,'Colombo City Suites','colombo-city-suites','A sleek, modern hotel in the heart of Colombo. Clean lines, smart-room technology, and a rooftop bar with skyline views. Steps from Galle Face and top-rated restaurants.','Modern hotel in central Colombo with rooftop bar and skyline views.','Colombo, Sri Lanka','Hotel',180.00,4.5,'images/accommodations/accom_1782569367_a9d5cf23.jpg',1,0,0,1,1,'Urban Stay Lanka','info@colombocitysuites.lk','+94 11 234 5678',0,1,'2026-06-24 09:56:37','2026-06-27 14:09:27'),(4,'Lagoon Edge Villas','lagoon-edge-villas','An exclusive villa complex on the banks of the Bentota Lagoon. Private plunge pools, boat rides through mangroves, and Ayurveda spa treatments. The ultimate tropical escape.','Lagoon-side villas with private pools and Ayurveda spa in Bentota.','Bentota, Sri Lanka','Villa',520.00,4.8,'images/accommodations/accom_1782569412_a98710ba.jpg',1,1,1,1,1,'Lagoon Edge Hospitality','reservations@lagoonedgelk','+94 34 234 5678',1,1,'2026-06-24 09:56:37','2026-06-27 14:10:12'),(5,'Heritage Boutique Inn','heritage-boutique-inn','A restored colonial-era boutique inn in the heart of Galle Fort. Antiques, courtyard garden, and personalized concierge service. Walk to cobblestone streets, cafes, and the ramparts.','Restored colonial inn in Galle Fort with courtyard garden.','Galle, Sri Lanka','Boutique',135.00,4.6,'images/accommodations/accom_1782569449_ab97b367.jpg',1,0,0,1,0,'Heritage Inns Sri Lanka','stay@heritageinns.lk','+94 91 234 5678',1,1,'2026-06-24 09:56:37','2026-06-27 14:10:49'),(6,'Safari Tented Camp','safari-tented-camp','Luxury tented accommodation on the edge of Yala National Park. Wake to wildlife sounds, enjoy bush dinners under the stars, and embark on guided safari drives at dawn.','Luxury tents on the edge of Yala National Park with safari drives.','Yala, Sri Lanka','Resort',275.00,4.7,'images/accommodations/accom_1782569515_bd2fa8a4.jpg',1,0,0,1,0,'Wild Safari Camps','book@wildsafari.lk','+94 11 987 6543',1,1,'2026-06-24 09:56:37','2026-06-27 14:11:55');
/*!40000 ALTER TABLE `accommodations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,NULL,'staff_created','staff',1,'New staff member created: test_staff','127.0.0.1','2026-06-24 17:25:24'),(2,9,'availability_toggled','staff',1,'Availability toggled','127.0.0.1','2026-06-24 17:46:17'),(3,9,'availability_toggled','staff',1,'Availability toggled','127.0.0.1','2026-06-24 17:46:19'),(4,9,'staff_assigned','booking',3,'Staff #1 assigned to booking','127.0.0.1','2026-06-24 17:46:24'),(5,9,'staff_unassigned','booking',0,'Assignment #1 removed','127.0.0.1','2026-06-24 17:46:31'),(6,9,'staff_assigned','booking',3,'Staff #1 assigned to booking','127.0.0.1','2026-06-24 17:46:41'),(7,9,'staff_assigned','booking',2,'Staff #1 assigned to booking','127.0.0.1','2026-06-24 17:46:50'),(8,9,'booking_status_updated','booking',2,'Status changed to confirmed','127.0.0.1','2026-06-24 17:46:53'),(9,12,'database_backup_created','system',NULL,'Backup: globetrek_backup_2026-06-27_16-23-13.sql.gz','127.0.0.1','2026-06-27 14:23:13');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `package_id` int(11) NOT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `num_travellers` int(11) DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(20) DEFAULT NULL,
  `card_last_four` varchar(4) DEFAULT NULL,
  `travel_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_reference` (`booking_reference`),
  KEY `user_id` (`user_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,1,3,'GT-6A38304E43226','test','1','example@example.com','123456789','lk','',2,87978.00,'pending',NULL,NULL,NULL,'2026-06-21 18:41:18'),(2,NULL,1,'GT-6A383BEB00B94','test','2','example@example.com','123456789','lk','',2,167198.00,'confirmed',NULL,NULL,NULL,'2026-06-21 19:30:51'),(3,1,1,'GT-6A383C0934C46','test','2','example@example.com','888888888','lk','',2,167198.00,'confirmed','credit_card','6546',NULL,'2026-06-21 19:31:21'),(4,11,1,'GT-6A3E987ECA22E','Raif','Raif','insathraifyk3@gmail.com','0710719859','lk','test case',2,167198.00,'confirmed','credit_card','1111',NULL,'2026-06-26 15:19:26'),(5,11,1,'GT-6A3E99B69B628','Raif','Raif','insathraifyk3@gmail.com','071071995959','lk','test',2,167198.00,'confirmed','credit_card','8888',NULL,'2026-06-26 15:24:38');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES (1,'Test User','test@testing.com','Test Inquiry','This is a test message from automated testing.',0,'2026-06-23 05:37:14'),(2,'Test User','test@testing.com','Test Inquiry','This is a test message from automated testing.',0,'2026-06-23 05:42:36');
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `custom_trip_requests`
--

DROP TABLE IF EXISTS `custom_trip_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_trip_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `destination` varchar(300) DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `num_travelers` int(11) DEFAULT NULL,
  `estimated_dates` varchar(100) DEFAULT NULL,
  `travel_style` enum('luxury','adventure','cultural','relaxation') DEFAULT NULL,
  `interests` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interests`)),
  `additional_details` text DEFAULT NULL,
  `status` enum('pending','reviewed','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `custom_trip_requests`
--

LOCK TABLES `custom_trip_requests` WRITE;
/*!40000 ALTER TABLE `custom_trip_requests` DISABLE KEYS */;
INSERT INTO `custom_trip_requests` VALUES (1,'wqd','ad@asda.com','fwewfwe',12,2,'232332','adventure','[\"food_drink\"]','test case','pending','2026-06-24 09:34:25'),(2,'Test','example@example.com','sigiriya',2,2,'2026','relaxation','[\"food_drink\"]','weqwewqe','pending','2026-06-25 12:14:26'),(3,'Raif','insathraifyk3@gmail.com','Kandy',5,2,'2026-06-26 to 2026-07-01','adventure','[\"food_drink\",\"nature_wildlife\",\"active_outdoors\"]','test case','pending','2026-06-26 15:18:32');
/*!40000 ALTER TABLE `custom_trip_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destination_tags`
--

DROP TABLE IF EXISTS `destination_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `destination_tags` (
  `destination_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`destination_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `destination_tags_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `destination_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destination_tags`
--

LOCK TABLES `destination_tags` WRITE;
/*!40000 ALTER TABLE `destination_tags` DISABLE KEYS */;
INSERT INTO `destination_tags` VALUES (1,4),(1,8),(2,9),(2,10),(3,11),(3,12),(4,13),(4,14);
/*!40000 ALTER TABLE `destination_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destinations`
--

DROP TABLE IF EXISTS `destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `destinations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `region` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT 'Cultural',
  `rating` decimal(2,1) DEFAULT 4.5,
  `review_count` int(11) DEFAULT 100,
  `image` varchar(500) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destinations`
--

LOCK TABLES `destinations` WRITE;
/*!40000 ALTER TABLE `destinations` DISABLE KEYS */;
INSERT INTO `destinations` VALUES (1,'Sigiriya Rock Fortress, Matale','sigiriya-rock-fortress-matale','A dramatic, UNESCO-protected ancient palace complex perched atop a massive 180-meter-high granite rock column. Built by King Kashyapa in the 5th century, it is famous for its colorful frescoes, graffiti-mirror wall, and monumental lion\'s paw gateway.','','Cultural',4.8,1250,'images/destinations/dest_1782568969_b15d0929.jpg',1,1,'2026-06-21 18:37:31'),(2,'Galle Fort, Galle','galle-fort-galle','A living UNESCO World Heritage monument originally built by the Portuguese in 1588 and heavily fortified by the Dutch. Today, its atmospheric cobblestone streets are lined with beautifully preserved colonial villas, boutique cafes, and old churches, bounded by historic seaside ramparts.','','Cultural',4.7,890,'images/destinations/dest_1782569008_88cbb4a6.jpg',1,1,'2026-06-21 18:37:31'),(3,'Nine Arch Bridge (Ella), Badulla','nine-arch-bridge-ella-badulla','An iconic, colonial-era railway bridge built completely out of brick, rock, and cement without using a single piece of steel. It stands hidden amid lush green tea plantations and misty mountains, drawing travelers who come to watch trains slowly pass over its line and admire arches.','','Adventure',4.9,1150,'images/destinations/dest_1782569052_21c6187d.jpg',1,1,'2026-06-21 18:37:31'),(4,'Ancient City of Polonnaruwa, Polonnaruwa','ancient-city-of-polonnaruwa-polonnaruwa','Sri Lanka\'s second ancient royal capital, active from the 10th to the 13th centuries. The vast, park-like archaeological site features marvelous preserved ruins, including the grand Royal Palace, massive stone stupas, and the famous Gal Vihara rock-cut Buddha statues.','','Cultural',4.6,720,'images/destinations/dest_1782569107_a991abc4.jpg',1,1,'2026-06-21 18:37:31'),(5,'Nuwara Eliya','nuwara-eliya','Famous dubbed \"Little England,\" this high-altitude mountain station was favored by British colonizers for its cool climate. It is the premier destination for exploring manicured green tea estates, sprawling colonial-era bungalows, and dramatic waterfalls.','','Hill Country',4.8,980,'images/destinations/dest_1782569147_fcaeb60a.jpg',1,1,'2026-06-21 18:37:31'),(6,'Mirissa, Matara','mirissa-matara','A laid-back coastal paradise renowned as one of the best locations in the world for blue whale watching safaris. It is also widely visited for its crescent-shaped sandy beaches, vibrant beachside restaurants, and the iconic Coconut Turtle Hill viewpoint.','','Beach',4.9,1300,'images/destinations/dest_1782569172_59f76935.jpg',1,1,'2026-06-21 18:37:31');
/*!40000 ALTER TABLE `destinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_verifications`
--

DROP TABLE IF EXISTS `email_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(64) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_ev_token` (`token`),
  KEY `idx_ev_user` (`user_id`),
  CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verifications`
--

LOCK TABLES `email_verifications` WRITE;
/*!40000 ALTER TABLE `email_verifications` DISABLE KEYS */;
INSERT INTO `email_verifications` VALUES (3,13,'','3b7390a5d891f6e017060e4f8d03985c3d83a70afdd87842a44045463e79f8be',0,'2026-06-28 04:52:34','2026-06-27 08:22:34'),(4,15,'','576cd82732b80467ad318000e0c099909454c477586f9bad28bbd8b0448909b3',0,'2026-06-28 06:00:26','2026-06-27 09:30:26');
/*!40000 ALTER TABLE `email_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guide_reviews`
--

DROP TABLE IF EXISTS `guide_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guide_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `guide_id` int(11) NOT NULL,
  `reviewer_name` varchar(150) NOT NULL,
  `reviewer_country` varchar(100) DEFAULT NULL,
  `reviewer_avatar` varchar(500) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_guide_reviews_guide` (`guide_id`),
  KEY `idx_guide_reviews_status` (`status`),
  KEY `idx_guide_reviews_user` (`user_id`),
  CONSTRAINT `guide_reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `guide_reviews_ibfk_2` FOREIGN KEY (`guide_id`) REFERENCES `guides` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guide_reviews`
--

LOCK TABLES `guide_reviews` WRITE;
/*!40000 ALTER TABLE `guide_reviews` DISABLE KEYS */;
INSERT INTO `guide_reviews` VALUES (6,1,7,'Sarah Mitchell','United Kingdom','https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face',5,'Kasun was amazing!','Kasun made our hill country trek absolutely unforgettable. His knowledge of tea plantations is incredible, and he shared stories that brought the landscape to life. Highly recommend him for any Nuwara Eliya adventure.','approved','2026-06-25 14:04:10'),(7,1,9,'Hans van der Berg','Netherlands','https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face',4,'Great safari guide','Ravi is passionate about wildlife and it shows. He spotted animals we would have missed on our own. The only reason for 4 stars is that the vehicle could have been more comfortable, but that is not Ravis fault.','approved','2026-06-25 14:04:10'),(8,1,8,'Yuki Tanaka','Japan','https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face',5,'Nipuni brought history to life','Our cultural triangle tour with Nipuni was exceptional. She has a deep understanding of archaeology and made every temple visit fascinating. Her enthusiasm is contagious.','approved','2026-06-25 14:04:10'),(9,1,10,'Emma Chen','Australia','https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop&crop=face',3,'Decent culinary tour','Malsha knew the best street food spots, which was great. However, some of the kitchen visits felt rushed and we did not get enough hands-on cooking time as expected.','approved','2026-06-25 14:04:10'),(10,1,12,'Marco Rossi','Italy','https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop&crop=face',2,'Disappointing dive experience','Dilini was knowledgeable about marine life, but the dive equipment was outdated and the boat trip was uncomfortable. We also waited over an hour for the boat to depart. Not what I expected for the price.','pending','2026-06-25 14:04:10');
/*!40000 ALTER TABLE `guide_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guide_tags`
--

DROP TABLE IF EXISTS `guide_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guide_tags` (
  `guide_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`guide_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `guide_tags_ibfk_1` FOREIGN KEY (`guide_id`) REFERENCES `guides` (`id`) ON DELETE CASCADE,
  CONSTRAINT `guide_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guide_tags`
--

LOCK TABLES `guide_tags` WRITE;
/*!40000 ALTER TABLE `guide_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `guide_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guides`
--

DROP TABLE IF EXISTS `guides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `specialty` varchar(200) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 4.5,
  `languages` varchar(255) DEFAULT 'English, Sinhala',
  `years_experience` int(11) DEFAULT 5,
  `image` varchar(500) DEFAULT NULL,
  `profile_link` varchar(500) DEFAULT '#',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guides`
--

LOCK TABLES `guides` WRITE;
/*!40000 ALTER TABLE `guides` DISABLE KEYS */;
INSERT INTO `guides` VALUES (7,'Kasun Bandara','Hill Country & Tea Plantations','Central Highlands','Born in Nuwara Eliya, Kasun has over 15 years of experience guiding treks through Sri Lanka misty hill country. Passionate about tea culture and high-altitude flora.',4.9,'English, Sinhala, Tamil',8,'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop&crop=face','#',1,1,'2026-06-24 09:56:37'),(8,'Nipuni Silva','Cultural Heritage & Temples','Cultural Triangle','An archaeology enthusiast from Anuradhapura, Nipuni specializes in deep-dive cultural tours across the ancient cities and sacred sites of the cultural triangle.',4.8,'English, Sinhala',6,'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&h=400&fit=crop&crop=face','#',1,1,'2026-06-24 09:56:37'),(9,'Ravi Tennakoon','Wildlife & Safari','Southern Coast','An expert tracker and wildlife conservationist from Tissamaharama, Ravi leads transformative safari experiences in Yala and Bundala with minimal ecological impact.',4.9,'English, Sinhala, Tamil',10,'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&h=400&fit=crop&crop=face','#',1,1,'2026-06-24 09:56:37'),(10,'Malsha Fernando','Culinary Tours','Western Province','A culinary enthusiast from Colombo, Malsha brings travelers into local kitchens and street food markets, offering an authentic taste of Sri Lankan cuisine.',4.7,'English, Sinhala',5,'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&h=400&fit=crop&crop=face','#',1,1,'2026-06-24 09:56:37'),(11,'Tharaka Perera','Urban Exploration','Eastern Province','Tharaka uncovers the hidden cultural gems of Sri Lanka east, from Trincomalee beaches to Batticaloa lagoons, contrasting colonial history with modern island life.',4.8,'English, Sinhala',7,'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop&crop=face','#',1,1,'2026-06-24 09:56:37'),(12,'Dilini Jayasuriya','Marine & Diving','Southern Coast','A marine biologist turned dive guide from Mirissa, Dilini leads whale watching and scuba trips focused on marine conservation and reef education.',4.9,'English, Sinhala, Tamil',9,'images/guides/guide_1782406855_ce70875f.png','#',1,1,'2026-06-24 09:56:37');
/*!40000 ALTER TABLE `guides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inquiries`
--

DROP TABLE IF EXISTS `inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `booking_reference` varchar(20) DEFAULT NULL,
  `inquiry_id_code` varchar(20) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','waiting_for_response','under_review','resolved') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `inquiry_id_code` (`inquiry_id_code`),
  KEY `user_id` (`user_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inquiries_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiries`
--

LOCK TABLES `inquiries` WRITE;
/*!40000 ALTER TABLE `inquiries` DISABLE KEYS */;
INSERT INTO `inquiries` VALUES (1,1,NULL,NULL,'INQ-75721','jhgkyyugf','kvliyhkyugfvuyfvc','under_review','2026-06-22 11:40:44','2026-06-23 15:14:44'),(2,1,1,NULL,'INQ-44521','Question about Island Escape package','Hello, I was wondering if the airport transfer is included in the premium package? We are arriving late at night and wanted to confirm transportation details before booking.','under_review','2024-10-24 05:00:00','2026-06-24 09:56:37'),(3,1,4,NULL,'INQ-44102','Dietary requirements for Cultural Discovery tour','Our team has contacted the local vendors regarding a severe shellfish allergy. We are compiling a list of guaranteed safe alternatives for the street food portion of the itinerary...','waiting_for_response','2024-10-18 08:45:00','2026-06-24 09:56:37'),(4,1,3,NULL,'INQ-43888','Group discount inquiry for 12 people','Hi, I am planning a corporate retreat for 12 people. Would you be able to offer a group discount for the Beach Paradise package? We are looking at dates in March.','resolved','2024-10-05 03:30:00','2026-06-24 09:56:37');
/*!40000 ALTER TABLE `inquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inquiry_replies`
--

DROP TABLE IF EXISTS `inquiry_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inquiry_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inquiry_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_role` enum('user','staff','admin') NOT NULL DEFAULT 'user',
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inquiry_id` (`inquiry_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `inquiry_replies_ibfk_1` FOREIGN KEY (`inquiry_id`) REFERENCES `inquiries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inquiry_replies_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiry_replies`
--

LOCK TABLES `inquiry_replies` WRITE;
/*!40000 ALTER TABLE `inquiry_replies` DISABLE KEYS */;
INSERT INTO `inquiry_replies` VALUES (1,1,NULL,'admin','Thank you for reaching out! Airport transfers are included in all our premium packages. For late-night arrivals, we arrange a private pickup with a driver who will wait for you at the arrivals terminal. Would you like us to confirm the specific transfer details for your booking?','2024-10-24 10:30:00'),(2,2,NULL,'admin','We have confirmed with all restaurant partners along the Cultural Discovery route. Each venue has been briefed on the shellfish allergy and will provide separate preparation areas. We will send you the detailed safe-dining guide within 24 hours.','2024-10-19 06:00:00'),(3,3,NULL,'admin','Great news! For groups of 10 or more, we offer a 15% discount on the total package price. For 12 people, that would bring the per-person cost down significantly. Shall we prepare a formal quote for your corporate retreat?','2024-10-06 03:15:00'),(4,3,1,'user','That sounds perfect! Could you please send over the formal quote? We would also like to know if you can accommodate team-building activities as part of the package.','2024-10-06 04:50:00'),(5,3,NULL,'admin','Absolutely! We can include team-building activities such as beach volleyball, cooking classes, and sunset boat tours. I will prepare a comprehensive quote with all options and send it to your email within 24 hours.','2024-10-06 08:30:00');
/*!40000 ALTER TABLE `inquiry_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_time` (`email`,`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (15,'testuser@example.com','::1','2026-06-27 09:26:20'),(16,'wrong@email.com','::1','2026-06-27 09:27:06'),(17,'user@test.com','::1','2026-06-27 09:30:47');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscriptions`
--

DROP TABLE IF EXISTS `newsletter_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscriptions`
--

LOCK TABLES `newsletter_subscriptions` WRITE;
/*!40000 ALTER TABLE `newsletter_subscriptions` DISABLE KEYS */;
INSERT INTO `newsletter_subscriptions` VALUES (1,'example@example.com',1,'2026-06-22 16:22:58');
/*!40000 ALTER TABLE `newsletter_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_codes`
--

DROP TABLE IF EXISTS `otp_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `type` enum('login','password_reset','email_verify') NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_otp_user` (`user_id`),
  KEY `idx_otp_type` (`type`),
  CONSTRAINT `otp_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_codes`
--

LOCK TABLES `otp_codes` WRITE;
/*!40000 ALTER TABLE `otp_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otps`
--

DROP TABLE IF EXISTS `otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `purpose` enum('registration','login','password_reset') NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_purpose` (`email`,`purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otps`
--

LOCK TABLES `otps` WRITE;
/*!40000 ALTER TABLE `otps` DISABLE KEYS */;
/*!40000 ALTER TABLE `otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `package_tags`
--

DROP TABLE IF EXISTS `package_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_tags` (
  `package_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`package_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `package_tags_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `package_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package_tags`
--

LOCK TABLES `package_tags` WRITE;
/*!40000 ALTER TABLE `package_tags` DISABLE KEYS */;
INSERT INTO `package_tags` VALUES (1,1),(1,2),(1,3),(2,2),(2,4),(3,1),(3,5),(4,3),(5,6),(6,7);
/*!40000 ALTER TABLE `package_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `duration_days` int(11) NOT NULL,
  `duration_nights` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `destination_category` varchar(100) DEFAULT NULL,
  `price_range` varchar(50) DEFAULT NULL,
  `max_group_size` int(11) DEFAULT 12,
  `difficulty_level` varchar(50) DEFAULT 'Moderate',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packages`
--

LOCK TABLES `packages` WRITE;
/*!40000 ALTER TABLE `packages` DISABLE KEYS */;
INSERT INTO `packages` VALUES (1,'Island Escape','island-escape','Immerse yourself in the breathtaking beauty of Sri Lanka with our exclusive Island Escape package. Designed for thrill-seekers and nature lovers alike, this 5-day journey takes you from pristine beaches to lush tropical jungles. Experience local culture, wildlife encounters, and unparalleled relaxation in carefully selected accommodations.','Explore Sri Lanka pristine beaches and jungles on this 5-day adventure.',5,4,75999.00,'images/packages/pkg_1782568480_cf020fc7.jpg','Beaches & Coastal Getaways','Mid-Range',12,'Moderate',1,1,'2026-06-21 18:37:31'),(2,'Mountain Explorer','mountain-explorer','Embark on a 6-day trekking adventure through the misty hill country of Sri Lanka. From tea plantations to cloud forests, experience the island mountainous interior like never before.','Discover Sri Lanka lush hill country on this 6-day trekking adventure.',6,5,65999.00,'images/packages/pkg_1782568556_88039fdd.jpg','Hill Country & Nature','Mid-Range',6,'Moderate',1,1,'2026-06-21 18:37:31'),(3,'Beach Paradise','beach-paradise','Unwind on the sun-kissed shores of southern Sri Lanka. This 4-day getaway includes beach activities, water sports, and serene coastal relaxation.','Relax on pristine southern beaches with this 4-day coastal escape.',4,3,39990.00,'images/packages/pkg_1782568630_48f8035d.jpg','Beaches & Coastal Getaways','Budget',4,'Moderate',1,1,'2026-06-21 18:37:31'),(4,'Cultural Discovery','cultural-discovery','Dive deep into the rich cultural tapestry of Sri Lanka. Visit ancient temples, royal palaces, and UNESCO World Heritage sites across the cultural triangle.','Explore ancient temples and UNESCO sites on this 7-day cultural journey.',7,6,55990.00,'images/packages/pkg_1782568693_da0f9998.jpg','Cultural & Historical Sites','Mid-Range',5,'Moderate',1,1,'2026-06-21 18:37:31'),(5,'City Lights','city-lights','Experience the vibrant urban life of Colombo and surrounding cities. From bustling markets to modern skyline views, discover the dynamic side of Sri Lanka.','Experience the vibrant urban life of Colombo on this 3-day city break.',3,2,35490.00,'images/packages/pkg_1782568766_41dcf708.jpg','Urban & Cultural Capitals','',12,'Moderate',1,1,'2026-06-21 18:37:31'),(6,'Wild Safari','wild-safari','Get up close with Sri Lanka incredible wildlife. Visit Yala, Udawalawe, and other renowned national parks for unforgettable safari experiences.','Encounter Sri Lanka incredible wildlife on this 5-day safari adventure.',5,4,82490.00,'images/packages/pkg_1782568823_1e0d6686.jpg','Wildlife & National Parks','',12,'Moderate',1,1,'2026-06-21 18:37:31');
/*!40000 ALTER TABLE `packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL DEFAULT '',
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_pr_token` (`token`),
  KEY `idx_pr_user` (`user_id`),
  KEY `idx_email` (`email`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `card_last_four` varchar(4) DEFAULT NULL,
  `card_brand` varchar(30) DEFAULT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'completed',
  `billing_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,4,11,170998.00,'credit_card','1111','Card','TXN-20DF239319DF6C83','completed','vch, vch, eastern 30400, Sri Lanka','2026-06-26 15:20:08'),(2,4,11,170998.00,'credit_card','1111','Card','TXN-1723A47E9B617930','completed','vch, vch, eastern 30400, Sri Lanka','2026-06-26 15:23:42'),(3,5,11,170998.00,'credit_card','8888','Card','TXN-6B252D17644D92F1','completed','vch, vch, eastern 40100, Sri Lanka','2026-06-26 15:25:37');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remember_tokens`
--

DROP TABLE IF EXISTS `remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL COMMENT 'SHA-256 hash of the raw token',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remember_tokens`
--

LOCK TABLES `remember_tokens` WRITE;
/*!40000 ALTER TABLE `remember_tokens` DISABLE KEYS */;
INSERT INTO `remember_tokens` VALUES (14,13,'f6c4fa626656a2da7d2336c35ee6a8cce9512a2458b1c2bbcefd00572d4ce6e1','2026-07-27 10:29:02','2026-06-27 08:29:02'),(15,14,'320efa16234166fed5b7f7d53946332471bb62390babb2120bf6772cbfd764f9','2026-07-27 10:29:22','2026-06-27 08:29:22'),(19,12,'ea488cfcf63b8fede6b7e00b41ffcd2d429d14648c3d286ba0a01fc3f21979ae','2026-07-27 15:51:58','2026-06-27 13:51:58');
/*!40000 ALTER TABLE `remember_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_assignments`
--

DROP TABLE IF EXISTS `staff_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `entity_type` enum('booking','inquiry') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`staff_id`,`entity_type`,`entity_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `idx_staff_assignments_entity` (`entity_type`,`entity_id`),
  KEY `idx_staff_assignments_staff` (`staff_id`),
  CONSTRAINT `staff_assignments_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_assignments_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_assignments`
--

LOCK TABLES `staff_assignments` WRITE;
/*!40000 ALTER TABLE `staff_assignments` DISABLE KEYS */;
INSERT INTO `staff_assignments` VALUES (2,1,'booking',3,'2026-06-24 17:46:41',9),(3,1,'booking',2,'2026-06-24 17:46:50',9);
/*!40000 ALTER TABLE `staff_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_permissions`
--

DROP TABLE IF EXISTS `staff_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission` (`staff_id`,`permission`),
  CONSTRAINT `staff_permissions_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_permissions`
--

LOCK TABLES `staff_permissions` WRITE;
/*!40000 ALTER TABLE `staff_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_profiles`
--

DROP TABLE IF EXISTS `staff_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `department` enum('operations','customer_service','sales','marketing') NOT NULL,
  `position` varchar(100) NOT NULL,
  `hire_date` date DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `max_concurrent_tasks` int(11) DEFAULT 10,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_staff_profiles_department` (`department`),
  KEY `idx_staff_profiles_available` (`is_available`),
  CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_profiles`
--

LOCK TABLES `staff_profiles` WRITE;
/*!40000 ALTER TABLE `staff_profiles` DISABLE KEYS */;
INSERT INTO `staff_profiles` VALUES (1,9,'operations','test_staff','2026-06-24',1,3,'test case','2026-06-24 17:25:24','2026-06-24 17:46:19');
/*!40000 ALTER TABLE `staff_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tag_name` (`name`),
  UNIQUE KEY `unique_tag_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,'Beach','beach','2026-06-26 05:42:42'),(2,'Adventure','adventure','2026-06-26 05:42:42'),(3,'Culture','culture','2026-06-26 05:42:42'),(4,'Mountain','mountain','2026-06-27 13:55:56'),(5,'Relaxation','relaxation','2026-06-27 13:57:10'),(6,'City','city','2026-06-27 13:59:26'),(7,'Wild Life','wild-life','2026-06-27 14:01:10'),(8,'Sigiriya','sigiriya','2026-06-27 14:02:49'),(9,'Fort','fort','2026-06-27 14:03:28'),(10,'Galle','galle','2026-06-27 14:03:28'),(11,'Nine Arch','nine-arch','2026-06-27 14:04:12'),(12,'Bridge','bridge','2026-06-27 14:04:12'),(13,'Ancient','ancient','2026-06-27 14:05:07'),(14,'Polonaruwa','polonaruwa','2026-06-27 14:05:07');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `testimonials`
--

DROP TABLE IF EXISTS `testimonials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `reviewer_name` varchar(150) NOT NULL,
  `reviewer_country` varchar(100) DEFAULT NULL,
  `reviewer_avatar` varchar(500) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_testimonials_status` (`status`),
  KEY `idx_testimonials_user` (`user_id`),
  KEY `idx_testimonials_package` (`package_id`),
  CONSTRAINT `fk_testimonials_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_testimonials_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `testimonials`
--

LOCK TABLES `testimonials` WRITE;
/*!40000 ALTER TABLE `testimonials` DISABLE KEYS */;
INSERT INTO `testimonials` VALUES (1,NULL,NULL,'Sarah Mitchell','United Kingdom','https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face',5,'An Unforgettable Adventure!','The Island Escape package exceeded all our expectations. From the moment we landed, everything was perfectly orchestrated. The Sigiriya climb was breathtaking, and the local guides were incredibly knowledgeable. We will definitely book again!','approved',1,'2026-06-22 14:59:31'),(2,NULL,NULL,'Hans van der Berg','Netherlands','https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face',5,'Perfect Honeymoon Trip','We chose the Cultural Discovery package for our honeymoon and it was magical. The ancient temples, the lush countryside, and the warm hospitality made it a trip of a lifetime. The team was responsive and accommodating throughout.','approved',1,'2026-06-22 14:59:31'),(3,NULL,NULL,'Yuki Tanaka','Japan','https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face',4,'Great Value for Money','The Beach Paradise package was exactly what we needed. Beautiful beaches, comfortable accommodations, and plenty of activities. The only minor issue was a slight delay in transfer, but overall an excellent experience.','approved',1,'2026-06-22 14:59:31'),(4,NULL,NULL,'Marco Rossi','Italy','https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop&crop=face',5,'Outstanding Safari Experience','The Wild Safari package was the highlight of our Sri Lanka trip. Seeing elephants and leopards in their natural habitat was incredible. Our guide Samir was exceptional - passionate, patient, and incredibly observant.','approved',0,'2026-06-22 14:59:31'),(5,NULL,NULL,'Emma Chen','Australia','https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop&crop=face',5,'Dream Trip Come True','From the initial planning to the farewell dinner, every detail was taken care of. The Mountain Explorer package gave us stunning views and unforgettable memories. The team truly understands what travelers want.','approved',0,'2026-06-22 14:59:31'),(6,NULL,NULL,'James Wilson','Canada','https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&h=150&fit=crop&crop=face',4,'Professional and Caring Team','What sets GlobeTrek apart is their attention to detail. They arranged a surprise birthday celebration for my wife during our City Lights tour. That personal touch made all the difference. Highly recommended!','approved',0,'2026-06-22 14:59:31'),(13,NULL,NULL,'Sarah Mitchell','United Kingdom','https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face',5,'An Unforgettable Adventure!','The Island Escape package exceeded all our expectations. From the moment we landed, everything was perfectly orchestrated. The Sigiriya climb was breathtaking, and the local guides were incredibly knowledgeable. We will definitely book again!','approved',1,'2026-06-24 09:56:37'),(14,NULL,NULL,'Hans van der Berg','Netherlands','https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face',5,'Perfect Honeymoon Trip','We chose the Cultural Discovery package for our honeymoon and it was magical. The ancient temples, the lush countryside, and the warm hospitality made it a trip of a lifetime. The team was responsive and accommodating throughout.','approved',1,'2026-06-24 09:56:37'),(15,NULL,NULL,'Yuki Tanaka','Japan','https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face',4,'Great Value for Money','The Beach Paradise package was exactly what we needed. Beautiful beaches, comfortable accommodations, and plenty of activities. The only minor issue was a slight delay in transfer, but overall an excellent experience.','approved',1,'2026-06-24 09:56:37'),(16,NULL,NULL,'Marco Rossi','Italy','https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop&crop=face',5,'Outstanding Safari Experience','The Wild Safari package was the highlight of our Sri Lanka trip. Seeing elephants and leopards in their natural habitat was incredible. Our guide Samir was exceptional - passionate, patient, and incredibly observant.','approved',0,'2026-06-24 09:56:37'),(17,NULL,NULL,'Emma Chen','Australia','https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop&crop=face',5,'Dream Trip Come True','From the initial planning to the farewell dinner, every detail was taken care of. The Mountain Explorer package gave us stunning views and unforgettable memories. The team truly understands what travelers want.','approved',0,'2026-06-24 09:56:37'),(18,NULL,NULL,'James Wilson','Canada','https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&h=150&fit=crop&crop=face',4,'Professional and Caring Team','What sets GlobeTrek apart is their attention to detail. They arranged a surprise birthday celebration for my wife during our City Lights tour. That personal touch made all the difference. Highly recommended!','approved',0,'2026-06-24 09:56:37');
/*!40000 ALTER TABLE `testimonials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transportations`
--

DROP TABLE IF EXISTS `transportations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transportations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `location` varchar(200) NOT NULL,
  `vehicle_type` enum('Three-Wheeler','Car','Bike','Minivan') NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `image` varchar(500) DEFAULT NULL,
  `has_ac` tinyint(1) DEFAULT 0,
  `has_driver` tinyint(1) DEFAULT 0,
  `has_insurance` tinyint(1) DEFAULT 0,
  `provider_name` varchar(150) DEFAULT NULL,
  `provider_email` varchar(150) DEFAULT NULL,
  `provider_phone` varchar(30) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transportations`
--

LOCK TABLES `transportations` WRITE;
/*!40000 ALTER TABLE `transportations` DISABLE KEYS */;
INSERT INTO `transportations` VALUES (1,'Colombo City Tuk-Tuk','colombo-city-tuk-tuk','A lively three-wheeler perfect for navigating Colombo bustling streets. Ideal for short trips, market visits, and exploring the city compact alleys with an experienced local driver.','Zippy three-wheeler for quick Colombo city trips with local driver.','Colombo','Three-Wheeler',2500.00,4.5,'images/transport/transport_1782569849_95574aea.jpg',1,1,1,'TukTuk Express','rent@tuktukexpress.lk','+94 77 123 4567',1,1,1,'2026-06-24 09:56:37','2026-06-27 14:17:29'),(2,'Kandy Hill Country Tuk','kandy-hill-country-tuk','Explore the scenic hill roads of Kandy in this comfortable three-wheeler. Perfect for visits to the Temple of the Tooth and surrounding tea gardens.','Comfortable tuk for scenic Kandy hill road exploration.','Kandy','Three-Wheeler',3000.00,4.3,'images/transport/transport_1782569936_d4712834.png',0,1,1,'TukTuk Express','rent@tuktukexpress.lk','+94 77 123 4567',1,0,1,'2026-06-24 09:56:37','2026-06-27 14:18:56'),(3,'Galle Fort Explorer Tuk','galle-fort-explorer-tuk','Cruise along the southern coast in this vibrant tuk-tuk. From Galle Fort to Unawatuna beach, experience the best of the southern coastline.','Vibrant tuk for southern coast and Galle Fort exploration.','Galle','Three-Wheeler',3500.00,4.7,'https://images.unsplash.com/photo-1586521995568-095a3c17fb89?w=600&h=400&fit=crop&crop=bottom',0,1,1,'TukTuk Express','rent@tuktukexpress.lk','+94 77 123 4567',1,1,1,'2026-06-24 09:56:37','2026-06-25 16:31:03'),(4,'Premium Sedan - Colombo','premium-sedan-colombo','A sleek, air-conditioned sedan for comfortable city travel and airport transfers. Spacious interior with luggage space, ideal for business travelers and families.','AC sedan for comfortable Colombo city and airport transfers.','Colombo','Car',8500.00,4.8,'https://images.unsplash.com/photo-1549317661-bd32c8ce0afa?w=600&h=400&fit=crop',1,1,1,'Lanka Car Rentals','info@lankacars.lk','+94 11 456 7890',1,1,1,'2026-06-24 09:56:37','2026-06-25 16:31:03'),(5,'Coastal Cruiser SUV','coastal-cruiser-suv','A robust SUV built for long-distance coastal drives. From Colombo to Galle, enjoy powerful AC, ample boot space, and a smooth ride on highway and beach roads.','Robust SUV for long-distance coastal highway drives.','Colombo','Car',12000.00,4.6,'https://images.unsplash.com/photo-1549317661-bd32c8ce0afa?w=600&h=400&fit=crop&crop=right',1,1,1,'Lanka Car Rentals','info@lankacars.lk','+94 11 456 7890',1,1,1,'2026-06-24 09:56:37','2026-06-25 16:31:03'),(6,'Hill Country Adventure SUV','hill-country-adventure-suv','A rugged 4x4 SUV designed for mountain terrain. Perfect for winding roads to Nuwara Eliya, Ella, and Horton Plains. Expert local drivers who know every bend.','Rugged 4x4 SUV for Sri Lanka mountain terrain adventures.','Nuwara Eliya','Car',14000.00,4.7,'https://images.unsplash.com/photo-1549317661-bd32c8ce0afa?w=600&h=400&fit=crop&crop=top',1,1,1,'Lanka Car Rentals','info@lankacars.lk','+94 11 456 7890',1,1,1,'2026-06-24 09:56:37','2026-06-25 16:31:03'),(7,'Sigiriya Trail Bike','sigiriya-trail-bike','A lightweight motorbike for adventurous solo travelers. Ride through paddy fields and villages to the majestic Sigiriya Rock Fortress. Helmet and insurance included.','Lightweight motorbike for Sigiriya trail adventures.','Sigiriya','Bike',4500.00,4.4,'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600&h=400&fit=crop',0,0,1,'Pedal Paradise','rent@pedalparadise.lk','+94 77 987 6543',1,0,1,'2026-06-24 09:56:37','2026-06-25 16:31:03'),(8,'Ella Gap Scenic Bike','ella-gap-scenic-bike','An off-road bike ready for the breathtaking Ella Gap. Navigate tea plantations and waterfalls with this well-maintained machine. Safety gear provided.','Off-road bike for breathtaking Ella Gap scenic routes.','Ella','Bike',5000.00,4.6,'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600&h=400&fit=crop&crop=center',0,0,1,'Pedal Paradise','rent@pedalparadise.lk','+94 77 987 6543',1,1,1,'2026-06-24 09:56:37','2026-06-25 16:31:03'),(9,'Family Minivan - 8 Seater','family-minivan-8-seater','A spacious 8-seater minivan perfect for family groups. Comfortable seating, large windows for sightseeing, and generous luggage space for multi-day tours.','Spacious 8-seater minivan for family group tours.','Colombo','Minivan',15000.00,4.8,'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=600&h=400&fit=crop',1,1,1,'Lanka Van Services','bookings@lankavans.lk','+94 11 321 6540',1,1,1,'2026-06-24 09:56:37','2026-06-25 16:31:03'),(10,'Airport Transfer Minivan','airport-transfer-minivan','Reliable minivan for airport pickups and drop-offs. Fits 6 passengers with full luggage. Meet-and-greet service with name board, water bottles, and Wi-Fi.','Reliable airport transfer minivan with meet-and-greet service.','Colombo','Minivan',10000.00,4.5,'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=600&h=400&fit=crop&crop=right',1,1,1,'Lanka Van Services','bookings@lankavans.lk','+94 11 321 6540',1,0,1,'2026-06-24 09:56:37','2026-06-25 16:31:03');
/*!40000 ALTER TABLE `transportations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(30) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_photo` varchar(500) DEFAULT NULL,
  `role` enum('user','staff','admin') DEFAULT 'user',
  `email_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `notification_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_preferences`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'test','example@example.com','$2y$10$0d0frB/O83VNGFiEYfDTNuyiJCh.hOCuX6VF4myYnU1gkR0uTaAey','0710749855','2000-06-09','Male','Sri Lanka','Colombo','this is a test case while development','images/profiles/user_1_1782125691.jpg','admin',NULL,1,NULL,'2026-06-21 18:39:36'),(7,'Test User','testuser@testing.com','$2y$10$c.FM1JBgaxYNyHDQIzbq2u8opfo6Fo.Rx3nKDWm8ERwio20LW2f8a',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'user',0,1,NULL,'2026-06-23 05:38:43'),(9,'test_staff','test_staff@example.com','$2y$10$rP8JV5GoP0B/6fVd8efE0.nmFnuAFwTuuVFuKps0Nd8hRN3DZo.D2','+94 71 123 4568',NULL,NULL,NULL,NULL,NULL,NULL,'staff',0,1,NULL,'2026-06-24 17:25:24'),(11,'Raif','insathraifyk3@gmail.com','$2y$10$RthOzP1ZevEhIx6USyq9CehVCTsIezTrhR0lqO7lwgk29mbNrBT.C','',NULL,NULL,NULL,NULL,NULL,'images/profiles/user_11_1782486763.jpg','user',1,1,NULL,'2026-06-26 14:53:35'),(12,'Insath Raif','admin@globetrek.lk','$2y$10$CWHsQhU58ZnNQJVagMlkyOGYX65yMX3w4OegIiBCcMwfZ0hEuj3Qq','+94 71 074 9859','2005-06-09','Male','Sri Lanka','Valaichenai','Admin Account','images/profiles/user_12_1782568409.jpg','admin',1,1,NULL,'2026-06-27 07:30:26'),(13,'Test User','user@test.com','$2y$10$J5Xb0NpsaSIACNT5Hme6YuaPAPm8oIfPKcJ1wSS0.2JMIe9.5wZ4e','0771234567',NULL,NULL,NULL,NULL,NULL,NULL,'user',1,1,NULL,'2026-06-27 08:22:34'),(14,'Admin User','admin@globetrek.com','$2y$10$Uzjqf415q9PfgdjQxwt8T.09.vc3tXALY3qCh28u7zbFvyhk/vUeK','0779876543',NULL,NULL,NULL,NULL,NULL,NULL,'admin',1,1,NULL,'2026-06-27 08:25:17'),(15,'Test User QA','testqa1782552617948@testing.com','$2y$10$5QTWkj7aOgintJ/WLB46cO/LZZMlAEx05VIseT2HmnfxGltuukWeO','0771234567',NULL,NULL,NULL,NULL,NULL,NULL,'user',0,1,NULL,'2026-06-27 09:30:26');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist_item` (`user_id`,`package_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (1,1,1,NULL,'2026-06-22 12:51:44'),(2,11,5,NULL,'2026-06-27 12:37:04');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-27 19:53:26
