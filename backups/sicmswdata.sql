-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: sicms
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
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `account_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `auth_provider` varchar(50) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'student',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `college_id` int(10) unsigned DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `course` varchar(255) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `phone_number` varchar(20) NOT NULL DEFAULT '',
  `gender` varchar(20) NOT NULL DEFAULT '',
  `birthday` date DEFAULT NULL,
  `address` text NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `uq_accounts_email` (`email`),
  KEY `idx_accounts_role` (`role`),
  KEY `idx_accounts_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (2,'Ricardo','Santos','ricardo.santos@sicms.local','$2y$10$kRqeDblG9N/GiGK9SdZeKegxDC72mZ85uQ1AhNpXraKpOZ1LtQqjC',NULL,'head-of-sdru','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-23 02:47:41','','',NULL,'','storage/profile_pics/pic_2_20260923024741.jpg'),(3,'Maria','Cruz','maria.cruz@sicms.local','$2y$10$UKA/3LfF8SCLMR1z4c9QQOXN93UGHrc6ZvinlVXpwWtkavWKMBla.',NULL,'coordinator','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(4,'Juan','Dela Pena','juan.delapena@sicms.local','$2y$10$UKA/3LfF8SCLMR1z4c9QQOXN93UGHrc6ZvinlVXpwWtkavWKMBla.',NULL,'coordinator','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(5,'Ana','Bautista','ana.bautista@sicms.local','$2y$10$UKA/3LfF8SCLMR1z4c9QQOXN93UGHrc6ZvinlVXpwWtkavWKMBla.',NULL,'coordinator','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(6,'Pedro','Reyes','pedro.reyes@sicms.local','$2y$10$klOygz/8utMnfFnPZ/usLu535PzlnKhPUkEYSpvnTn9AP4OFGGdZq',NULL,'sdru-staff','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:59:43','','',NULL,'',NULL),(7,'Carmen','Garcia','carmen.garcia@sicms.local','$2y$10$klOygz/8utMnfFnPZ/usLu535PzlnKhPUkEYSpvnTn9AP4OFGGdZq',NULL,'sdru-staff','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:59:43','','',NULL,'',NULL),(8,'Juan','Dela Cruz','juan.delacruz@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(9,'Maria','Santos','maria.santos@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,'23-2639','College of Engineering','Bachelor of Science in Information Technology (BSIT)','4-3','2026-09-01 22:25:52','2026-09-22 04:01:47','09763861513','Male','2005-02-26','Ladies Dorm 3, Central Luzon State University, Science City of Muñoz, Nueva Ecija\r\n0061/South Curva',NULL),(10,'Pedro','Garcia','pedro.garcia@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(11,'Ana','Reyes','ana.reyes@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,'23-2556','College of Education','Bachelor of Science in Accountancy (BSAc)','2-6','2026-09-01 22:25:52','2026-09-22 08:08:28','09763861513','Female','2018-03-22','Ladies Dorm 3, Central Luzon State University, Science City of Muñoz, Nueva Ecija\r\n0061/South Curva',NULL),(12,'Carmen','Mendoza','carmen.mendoza@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(13,'Luis','Bautista','luis.bautista@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(14,'Rosa','Cruz','rosa.cruz@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(15,'Miguel','Rivera','miguel.rivera@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(16,'Elena','Torres','elena.torres@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(17,'Jose','Flores','jose.flores@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(18,'Patricia','Gomez','patricia.gomez@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(19,'Daniel','Lopez','daniel.lopez@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(20,'Marcela','Diaz','marcela.diaz@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(21,'Francisco','Ramos','francisco.ramos@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(22,'Isabela','Morales','isabela.morales@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,'',NULL),(23,'Pedro','Reyes','pedro1.reyes@sicms.local','$2y$10$4oHf7SzpAzSI45B/n4F6Ju7Ueyzc3TjegMLxM.f9BKmJsWQ.yvEOO',NULL,'sdru-staff','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:57:41','2026-09-01 22:57:41','','',NULL,'',NULL),(24,'KWIN JHERANE','OCAREZA','kwinjherane.ocareza@clsu2.edu.ph','$2y$10$zZpVczLnM3iUTZ/MUSEczep1igSLuE.K9m9PjgH8ZliysnIN/xOey','google','student','active',NULL,'23-2620','College of Engineering','Bachelor of Science in Information Technology (BSIT)','4-2','2026-09-02 04:17:03','2026-09-19 14:27:05','09544834569','Female','2004-11-27','Sto. Nino 2nd, San Jose City, Nueva Ecija\r\nZone 6',NULL),(25,'ALEXA ANGELA','GABRIEL','alexaangela.gabriel@clsu2.edu.ph','$2y$10$vYzdLCn5fpqIF.3myJKiTuIcR1U/v1IFFl.Uz1AZwL3UsyPxFEI5a','google','student','active',NULL,'23-2623','College of Engineering','Bachelor of Science in Information Technology (BSIT)','4-2','2026-09-21 23:48:43','2026-09-21 23:49:35','09277289638','Female','2005-05-18','ZONE 3 AMAMPEREZ',NULL),(31,'Reformation','Coord','reform@sicms.local','$2y$10$UkYsvnBWmtimfxa9HejGPuFqSlgfAgQqEBLPN1iGhka0LBz.ryx4a',NULL,'reformation-coordinator','active',NULL,NULL,NULL,NULL,NULL,'2026-09-22 19:32:40','2026-09-22 19:32:40','','',NULL,'',NULL);
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `audit_log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(10) unsigned DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`audit_log_id`),
  KEY `idx_audit_logs_created_at` (`created_at`),
  KEY `idx_audit_logs_account` (`account_id`),
  KEY `idx_audit_logs_action` (`action`),
  CONSTRAINT `fk_audit_logs_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:26:47'),(2,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:48:50'),(3,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:53:30'),(4,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:55:22'),(5,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:55:46'),(6,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:56:28'),(7,23,'Pedro Reyes','sdru-staff','User Creation','User account created for pedro1.reyes@sicms.local.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:57:41'),(8,2,'Ricardo Santos','head-of-sdru','User Creation','Created SDRU Staff account for pedro1.reyes@sicms.local.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:57:41'),(9,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:01:11'),(10,23,'Pedro Reyes','sdru-staff','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:01:18'),(11,23,'Pedro Reyes','sdru-staff','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:04:32'),(12,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:04:54'),(13,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:19:04'),(14,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:19:37'),(15,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Elena Torres.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:14'),(16,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Jose Flores.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:18'),(17,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Ana Bautista.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:49'),(18,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Ana Bautista.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:50'),(19,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:52'),(20,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:59'),(21,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:21:02'),(22,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:23:47'),(23,3,'Maria Cruz','coordinator','Screenshot Attempt','PrintScreen key detected while viewing Case Management.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:32:00'),(24,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Marcela Diaz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:48:01'),(25,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Juan Dela Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:49:15'),(26,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:53:07'),(27,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:53:12'),(28,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:54:36'),(29,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Rosa Cruz.',NULL,'','2026-09-01 23:56:12'),(30,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Rosa Cruz.',NULL,'','2026-09-01 23:56:22'),(31,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:58:27'),(32,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Ana Bautista.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:58:30'),(33,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Marcela Diaz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:58:34'),(34,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Rosa Cruz.',NULL,'','2026-09-01 23:59:20'),(35,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:59:48'),(36,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Juan Dela Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:59:56'),(37,3,'Maria Cruz','coordinator','Messages Sent','Sent a direct message to Rosa Cruz.',NULL,'','2026-09-02 00:02:48'),(38,3,'Maria Cruz','coordinator','Messages Sent','Sent a direct message to Juan Dela Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:04:04'),(39,3,'Maria Cruz','coordinator','Messages Sent','Sent a direct message to System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:04:08'),(40,3,'Maria Cruz','coordinator','Messages Deleted','Deleted their copy of the conversation with Juan Dela Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:04:14'),(41,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:13'),(42,3,'Maria Cruz','coordinator','Messages Sent','Sent a direct message to System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:21'),(43,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:25'),(44,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:34'),(45,2,'Ricardo Santos','head-of-sdru','Conversations Started','Started a conversation with Maria Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:42'),(46,2,'Ricardo Santos','head-of-sdru','Messages Sent','Sent a direct message to Maria Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:44'),(47,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:46'),(48,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:50'),(49,3,'Maria Cruz','coordinator','Messages Deleted','Deleted their copy of the conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:57'),(50,3,'Maria Cruz','coordinator','Case Verification','Verified complaint 2026-013.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:19:34'),(51,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:28:59'),(52,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:06'),(53,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:36'),(54,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:40'),(55,3,'Maria Cruz','coordinator','Messages Sent','Sent a direct message to Ricardo Santos.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:46'),(56,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:48'),(57,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:51'),(58,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:30:09'),(59,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 02:43:46'),(60,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Case Details.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 03:40:41'),(61,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Case Details.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 03:41:54'),(62,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Case Details.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 03:44:26'),(63,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Users.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 03:53:49'),(64,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Chat & Messaging.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 03:57:34'),(65,2,'Ricardo Santos','head-of-sdru','Legacy Entry Created','Added SDRU in-charge Ricardo Santos to the legacy page.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:01:16'),(66,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Dashboard.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:01:40'),(67,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Hearings.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:11:48'),(68,2,'Ricardo Santos','head-of-sdru','Hearing Updates','Marked hearing for 2026-016 as completed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:12:16'),(69,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:12:40'),(70,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:12:53'),(71,3,'Maria Cruz','coordinator','Screenshot Attempt','PrintScreen key detected while viewing Case Details.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:14:52'),(72,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:15:13'),(73,6,'Pedro Reyes','sdru-staff','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:15:40'),(74,6,'Pedro Reyes','sdru-staff','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:16:44'),(75,24,'KWIN JHERANE OCAREZA','student','Screenshot Attempt','PrintScreen key detected while viewing Dashboard.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:17:06'),(76,24,'KWIN JHERANE OCAREZA','student','Screenshot Attempt','PrintScreen key detected while viewing Submit Complaint.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:21:00'),(77,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-03 02:48:21'),(78,2,'Ricardo Santos','head-of-sdru','Messages Sent','Sent a direct message to KWIN JHERANE OCAREZA.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-03 02:48:29'),(79,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-03 02:48:33'),(80,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-17 15:19:29'),(81,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-17 16:26:10'),(82,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-17 16:26:36'),(83,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-17 16:33:00'),(84,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-17 16:33:22'),(85,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 13:55:02'),(86,2,'Ricardo Santos','head-of-sdru','Case Verification','Verified complaint 2026-015.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 14:22:05'),(87,2,'Ricardo Santos','head-of-sdru','Messages Sent','Sent a direct message to KWIN JHERANE OCAREZA.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 14:23:33'),(88,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 14:43:56'),(89,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 15:04:01'),(90,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 15:31:01'),(91,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 15:31:39'),(92,2,'Ricardo Santos','head-of-sdru','Case Escalation','Marked case 2026-015 as escalated.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:33:45'),(93,2,'Ricardo Santos','head-of-sdru','Case Escalation Withdrawn','Withdrew escalation for 2026-015. Status returned to Under Investigation.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:40:57'),(94,2,'Ricardo Santos','head-of-sdru','Case Resolution','Marked case 2026-015 as resolved.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:41:54'),(95,2,'Ricardo Santos','head-of-sdru','Case Reopened','Reopened case 2026-015. Status returned to Under Investigation.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:41:57'),(96,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Case Details.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:42:44'),(97,2,'Ricardo Santos','head-of-sdru','Case Classification','Classified 2026-015 as Cyberbullying.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:44:00'),(98,2,'Ricardo Santos','head-of-sdru','Case Classification','Classified 2026-015 as Cyberbullying.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:44:01'),(99,2,'Ricardo Santos','head-of-sdru','Case Resolution','Marked case 2026-015 as resolved.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:44:46'),(100,2,'Ricardo Santos','head-of-sdru','Case Archival','Archived case 2026-015.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:45:00'),(101,2,'Ricardo Santos','head-of-sdru','Case Escalation','Marked case 2026-016 as escalated.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:45:31'),(102,2,'Ricardo Santos','head-of-sdru','Case Archival','Archived case 2026-016.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:45:36'),(103,2,'Ricardo Santos','head-of-sdru','Case Escalation','Marked case 2026-014 as escalated.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:46:32'),(104,2,'Ricardo Santos','head-of-sdru','Case Archival','Archived case 2026-014.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 16:46:37'),(105,2,'Ricardo Santos','head-of-sdru','Case Archival','Archived case 2026-012.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 17:20:13'),(106,2,'Ricardo Santos','head-of-sdru','Case Archival','Archived case 2026-008.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 17:49:30'),(107,2,'Ricardo Santos','head-of-sdru','Case Archival','Archived case 2026-008.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 18:10:52'),(108,2,'Ricardo Santos','head-of-sdru','Case Unarchival','Returned archived case 2026-008 to active cases.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 18:15:13'),(109,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 23:55:23'),(110,6,'Pedro Reyes','sdru-staff','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-20 00:43:58'),(111,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 00:22:46'),(112,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 00:24:27'),(113,2,'Ricardo Santos','head-of-sdru','Case Assignment','Assigned coordinator for SDRU-20260922-0001.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 00:25:30'),(114,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 00:25:46'),(115,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 00:26:02'),(116,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 03:12:00'),(117,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 03:26:14'),(118,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 03:27:36'),(119,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 03:27:58'),(120,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 03:28:01'),(121,2,'Ricardo Santos','head-of-sdru','Attachment Access','Inline access to project1-advisory_ratings.png for SDRU-20260922-0002.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 04:12:02'),(122,2,'Ricardo Santos','head-of-sdru','Attachment Access','Inline access to project1-advisory_ratings.png for SDRU-20260922-0002.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 04:12:08'),(123,2,'Ricardo Santos','head-of-sdru','Case Assignment','Assigned coordinator for SDRU-20260922-0002.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 04:12:58'),(124,5,'Ana Bautista','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 04:15:27'),(125,5,'Ana Bautista','coordinator','Attachment Access','Inline access to project1-advisory_ratings.png for SDRU-20260922-0002.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 04:17:54'),(126,5,'Ana Bautista','coordinator','Attachment Access','Attachment access to project1-advisory_ratings.png for SDRU-20260922-0002.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 04:18:02'),(127,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','curl/8.21.0','2026-09-22 04:33:24'),(128,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','curl/8.21.0','2026-09-22 04:33:25'),(129,6,'Pedro Reyes','sdru-staff','User Login','User logged in successfully.','::1','curl/8.21.0','2026-09-22 04:33:26'),(134,5,'Ana Bautista','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 04:38:28'),(135,5,'Ana Bautista','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 04:44:27'),(136,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-09-22 05:15:05'),(137,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-09-22 05:15:21'),(138,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-09-22 05:17:49'),(139,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-09-22 05:39:05'),(146,6,'Pedro Reyes','sdru-staff','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:13:19'),(147,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:26:17'),(148,2,'Ricardo Santos','head-of-sdru','Respondent Contact Updated','Updated email and contact number for respondent #1.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:27:20'),(149,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Users.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:27:45'),(150,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:35:42'),(151,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:36:45'),(152,2,'Ricardo Santos','head-of-sdru','Report Generation','Generated PDF yearly report.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:37:01'),(154,5,'Ana Bautista','coordinator','Respondent Account Created','Created respondent account kassandrasophia.fernandez@clsu2.edu.ph for Kassandra Sophia Antonio Fernandez on SDRU-20260922-0002.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:41:48'),(155,6,'Pedro Reyes','sdru-staff','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:51:16'),(158,2,'Ricardo Santos','head-of-sdru','Respondent Profile Updated','Updated respondent account kassandrasophia.fernandez@clsu2.edu.ph.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 08:13:08'),(159,5,'Ana Bautista','coordinator','Case Forwarded to Respondent','Forwarded SDRU-20260922-0002 to respondent(s): Kassandra Sophia Antonio Fernandez.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 08:31:08'),(161,5,'Ana Bautista','coordinator','Counter-Statement Forwarded to Complainant','Forwarded the counter-statement of respondent #2 to the complainant for response on SDRU-20260922-0002.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 08:34:36'),(162,5,'Ana Bautista','coordinator','Case Proceeded to Investigation','Proceeded to investigation after reviewing the counter-statement of respondent #2 on SDRU-20260922-0002.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 08:39:13'),(163,5,'Ana Bautista','coordinator','Counter-Statement Revision','Returned the counter-statement for Kassandra Sophia Antonio Fernandez on SDRU-20260922-0002 for revision.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 08:50:11'),(165,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 17:08:26'),(166,2,'Ricardo Santos','head-of-sdru','Case Forwarded to Respondent','Forwarded SDRU-20260922-0002 to respondent(s): Kassandra Sophia Antonio Fernandez.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 17:22:46'),(167,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Case Details.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 17:23:38'),(168,2,'Ricardo Santos','head-of-sdru','Attachment Access','Inline access to project1-advisory_ratings.png for SDRU-20260922-0002.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 17:47:51'),(169,2,'Ricardo Santos','head-of-sdru','Attachment Access','Attachment access to project1-advisory_ratings.png for SDRU-20260922-0002.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 17:47:56'),(170,2,'Ricardo Santos','head-of-sdru','Case Forwarded to Respondent','Forwarded SDRU-20260922-0002 to respondent(s): Kassandra Sophia Antonio Fernandez.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 18:06:16'),(171,2,'Ricardo Santos','head-of-sdru','Case Forwarded to Respondent','Forwarded SDRU-20260922-0002 to respondent(s): Kassandra Sophia Antonio Fernandez via email and in-app notification.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 18:17:42'),(172,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 19:17:59'),(173,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 19:18:21'),(174,31,'Reformation Coord','reformation-coordinator','User Creation','User account created for reform@sicms.local.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 19:32:41'),(175,2,'Ricardo Santos','head-of-sdru','User Creation','Created Reformation Coordinator account for reform@sicms.local.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 19:32:41'),(176,31,'Reformation Coord','reformation-coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 19:33:08'),(177,2,'Ricardo Santos','head-of-sdru','Attachment Access','Inline access to project1-advisory_ratings.png for SDRU-20260922-0002.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:05:45'),(178,2,'Ricardo Santos','head-of-sdru','Case Forwarded to Respondent','Forwarded SDRU-20260922-0002 to respondent(s): Kassandra Sophia Antonio Fernandez via email and in-app notification.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:07:48'),(179,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:18:01'),(180,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:18:20'),(181,2,'Ricardo Santos','head-of-sdru','Case Classification','Classified SDRU-20260922-0001 as Policy Violation.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:23:06'),(182,2,'Ricardo Santos','head-of-sdru','Case Classification','Classified SDRU-20260922-0001 as Plagiarism.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:23:11'),(183,2,'Ricardo Santos','head-of-sdru','Case Classification','Classified SDRU-20260922-0001 as Physical Assault.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:23:16'),(184,2,'Ricardo Santos','head-of-sdru','Case Assignment','Assigned coordinator for SDRU-20260922-0001.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:23:33'),(185,2,'Ricardo Santos','head-of-sdru','Case Assignment','Assigned coordinator for SDRU-20260922-0001.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:23:46'),(186,2,'Ricardo Santos','head-of-sdru','Case Update','Added Case Update (Update Respondent Details) to SDRU-20260922-0001.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:25:27'),(187,2,'Ricardo Santos','head-of-sdru','Complaint Updates','Returned complaint SDRU-20260922-0001 for revision. Fields: evidence.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:25:55'),(188,2,'Ricardo Santos','head-of-sdru','Case Update','Added Case Update (Update Respondent Details) to SDRU-20260922-0001.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:26:43'),(189,2,'Ricardo Santos','head-of-sdru','Case Update','Added Case Update (Update Respondent Details) to SDRU-20260922-0001.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:35:44'),(190,2,'Ricardo Santos','head-of-sdru','Case Assignment','Assigned coordinator for SDRU-20260922-0001.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:49:41'),(191,2,'Ricardo Santos','head-of-sdru','Respondent Account Auto-Linked','Automatically linked account KWIN JHERANE OCAREZA (kwinjherane.ocareza@clsu2.edu.ph) to respondent Kwin Jherane Ocareza on 2026-013.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 21:11:12'),(192,2,'Ricardo Santos','head-of-sdru','Case Update','Added Case Update (Update Respondent Details) to 2026-013.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 21:11:12'),(193,2,'Ricardo Santos','head-of-sdru','Case Update','Added Case Update (Update Respondent Details) to 2026-013.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 21:24:14'),(194,2,'Ricardo Santos','head-of-sdru','Case Update','Added Case Update (Update Respondent Details) to 2026-013.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:00:50'),(195,2,'Ricardo Santos','head-of-sdru','Case Forwarded to Respondent','Forwarded 2026-013 to respondent(s): KWIN JHERANE OCAREZA via email and in-app notification.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:01:38'),(196,2,'Ricardo Santos','head-of-sdru','Case Forwarded to Respondent','Forwarded 2026-013 to respondent(s): KWIN JHERANE OCAREZA via email and in-app notification.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:09:49'),(197,2,'Ricardo Santos','head-of-sdru','Respondent Account Auto-Linked','Automatically linked account KWIN JHERANE OCAREZA (kwinjherane.ocareza@clsu2.edu.ph) to respondent KWIN JHERANE OCAREZA on 2026-011.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:11:18'),(198,2,'Ricardo Santos','head-of-sdru','Case Update','Added Case Update (Update Respondent Details) to 2026-011.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:11:18'),(199,2,'Ricardo Santos','head-of-sdru','Case Forwarded to Respondent','Forwarded 2026-011 to respondent(s): KWIN JHERANE OCAREZA via email and in-app notification.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:11:49'),(200,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:21:38'),(201,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 01:34:13'),(202,2,'Ricardo Santos','head-of-sdru','Counter-Statement Revision','Returned the counter-statement for KWIN JHERANE OCAREZA on 2026-013 for revision.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 01:54:50'),(203,2,'Ricardo Santos','head-of-sdru','Counter-Statement Revision','Returned the counter-statement for KWIN JHERANE OCAREZA on 2026-013 for revision.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 02:02:33'),(204,2,'Ricardo Santos','head-of-sdru','Attachment Access','Inline access to laptop-removebg-preview.png for 2026-013.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 02:03:52'),(205,2,'Ricardo Santos','head-of-sdru','Attachment Access','Attachment access to laptop-removebg-preview.png for 2026-013.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 02:03:56'),(206,2,'Ricardo Santos','head-of-sdru','Counter-Statement Revision','Returned the counter-statement for KWIN JHERANE OCAREZA on 2026-013 for revision.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 02:07:19'),(207,2,'Ricardo Santos','head-of-sdru','Counter-Statement Revision','Returned the counter-statement for KWIN JHERANE OCAREZA on 2026-013 for revision.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 02:29:33'),(208,2,'Ricardo Santos','head-of-sdru','Attachment Access','Inline access to PHONE.png for 2026-013.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 02:30:23'),(209,2,'Ricardo Santos','head-of-sdru','Attachment Access','Attachment access to PHONE.png for 2026-013.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 02:30:25'),(210,2,'Ricardo Santos','head-of-sdru','User Update','User account updated.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 02:47:41');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `case_approvals`
--

DROP TABLE IF EXISTS `case_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `case_approvals` (
  `approval_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `requested_by_account_id` int(10) unsigned NOT NULL,
  `action_type` varchar(80) NOT NULL,
  `action_label` varchar(150) NOT NULL,
  `payload` longtext NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `reviewed_by_account_id` int(10) unsigned DEFAULT NULL,
  `review_remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`approval_id`),
  KEY `idx_case_approvals_status` (`status`),
  KEY `idx_case_approvals_complaint` (`complaint_id`),
  KEY `fk_case_approvals_requester` (`requested_by_account_id`),
  KEY `fk_case_approvals_reviewer` (`reviewed_by_account_id`),
  CONSTRAINT `fk_case_approvals_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_case_approvals_requester` FOREIGN KEY (`requested_by_account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_case_approvals_reviewer` FOREIGN KEY (`reviewed_by_account_id`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_approvals`
--

LOCK TABLES `case_approvals` WRITE;
/*!40000 ALTER TABLE `case_approvals` DISABLE KEYS */;
INSERT INTO `case_approvals` VALUES (1,35,3,'return','Return','{\"remarks\":\"ndjbdjewd\",\"revision_fields\":[\"evidence\"],\"case_action\":\"return\"}','Approved',2,NULL,'2026-09-22 00:26:28','2026-09-22 20:25:55');
/*!40000 ALTER TABLE `case_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `case_history`
--

DROP TABLE IF EXISTS `case_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `case_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `action` varchar(100) DEFAULT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `revision_fields` text DEFAULT NULL,
  `assigned_coordinator_account_id` int(10) unsigned DEFAULT NULL,
  `reformation_coordinator_account_id` int(10) unsigned DEFAULT NULL,
  `created_by_account_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `idx_history_complaint` (`complaint_id`),
  KEY `idx_history_created_by` (`created_by_account_id`),
  KEY `idx_history_coordinator` (`assigned_coordinator_account_id`),
  KEY `idx_history_reformation_coordinator` (`reformation_coordinator_account_id`),
  CONSTRAINT `fk_history_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_coordinator` FOREIGN KEY (`assigned_coordinator_account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_history_creator` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_history_reformation_coordinator` FOREIGN KEY (`reformation_coordinator_account_id`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_history`
--

LOCK TABLES `case_history` WRITE;
/*!40000 ALTER TABLE `case_history` DISABLE KEYS */;
INSERT INTO `case_history` VALUES (1,13,'Verified Complaint','Submitted','Verified','Okay. Please wait for another update.',NULL,NULL,NULL,3,'2026-09-02 00:19:34'),(2,31,'Complaint Submitted',NULL,'Submitted','Complaint filed via online submission by the complainant.',NULL,NULL,NULL,8,'2026-09-01 10:00:00'),(3,31,'Complaint Verified','Submitted','Verified','Complaint reviewed and verified by SDRU staff. All information confirmed accurate.',NULL,NULL,NULL,6,'2026-09-01 14:00:00'),(4,31,'Coordinator Assigned',NULL,NULL,'Case assigned to Coordinator Maria Cruz for investigation and mediation.',NULL,NULL,NULL,6,'2026-09-02 09:00:00'),(5,31,'Hearing Scheduled',NULL,NULL,'Initial hearing scheduled for September 15, 2026 at 10:00 AM at SDRU Conference Room 301.',NULL,NULL,NULL,3,'2026-09-02 09:30:00'),(6,15,'Verified Complaint','Submitted','Verified','',NULL,NULL,NULL,2,'2026-09-19 14:22:05'),(7,15,'Escalated Case','Under Investigation','Escalated','kasjdlkasjd',NULL,NULL,NULL,2,'2026-09-19 16:33:45'),(13,15,'Under Investigation','Escalated','Under Investigation','',NULL,NULL,NULL,2,'2026-09-19 16:40:57'),(14,15,'Resolved','Under Investigation','Resolved','Outcome: jheloo',NULL,NULL,NULL,2,'2026-09-19 16:41:54'),(15,15,'Under Investigation','Resolved','Under Investigation','',NULL,NULL,NULL,2,'2026-09-19 16:41:57'),(17,15,'Case Classification','Under Investigation','Under Investigation','',NULL,NULL,NULL,2,'2026-09-19 16:44:00'),(18,15,'Case Classification','Under Investigation','Under Investigation','',NULL,NULL,NULL,2,'2026-09-19 16:44:01'),(19,15,'Resolved','Under Investigation','Resolved','Outcome: okay na',NULL,NULL,NULL,2,'2026-09-19 16:44:46'),(20,15,'Archived Case','Resolved','Archived','',NULL,NULL,NULL,2,'2026-09-19 16:45:00'),(21,31,'Escalated Case','Under Investigation','Escalated','oki',NULL,NULL,NULL,2,'2026-09-19 16:45:31'),(22,31,'Archived Case','Escalated','Archived','',NULL,NULL,NULL,2,'2026-09-19 16:45:36'),(23,14,'Escalated Case','Under Investigation','Escalated','resolve',NULL,NULL,NULL,2,'2026-09-19 16:46:32'),(24,14,'Archived Case','Escalated','Archived','',NULL,NULL,NULL,2,'2026-09-19 16:46:37'),(25,12,'Archived Case','Resolved','Archived','',NULL,NULL,NULL,2,'2026-09-19 17:20:13'),(26,8,'Archived Case','Resolved','Archived','',NULL,NULL,NULL,2,'2026-09-19 17:49:30'),(27,8,'Unarchived Case','Archived','Resolved','smoke unarchive',NULL,NULL,NULL,2,'2026-09-19 18:10:08'),(28,8,'Archived Case','Resolved','Archived','',NULL,NULL,NULL,2,'2026-09-19 18:10:52'),(29,8,'Unarchived Case','Archived','Resolved','',NULL,NULL,NULL,2,'2026-09-19 18:15:13'),(30,35,'Assigned Coordinator','Under Investigation','Under Investigation','',NULL,3,NULL,2,'2026-09-22 00:25:27'),(50,35,'Case Classification','Under Investigation','Under Investigation','',NULL,NULL,NULL,2,'2026-09-22 20:23:06'),(51,35,'Case Classification','Under Investigation','Under Investigation','',NULL,NULL,NULL,2,'2026-09-22 20:23:11'),(52,35,'Case Classification','Under Investigation','Under Investigation','',NULL,NULL,NULL,2,'2026-09-22 20:23:16'),(53,35,'Assigned Coordinator','Under Investigation','Under Investigation','',NULL,5,NULL,2,'2026-09-22 20:23:27'),(54,35,'Assigned Coordinator','Under Investigation','Under Investigation','',NULL,3,NULL,2,'2026-09-22 20:23:42'),(55,35,'Returned for Revision','Under Investigation','Returned for Revision','ndjbdjewd','[\"evidence\"]',NULL,NULL,2,'2026-09-22 20:25:49'),(56,35,'Assigned Coordinator','Returned for Revision','Returned for Revision','',NULL,5,NULL,2,'2026-09-22 20:49:36'),(57,13,'Forwarded to Respondent','Under Investigation','Under Investigation','Coordinator released the permitted case information to the respondent(s).',NULL,3,NULL,2,'2026-09-22 22:01:26'),(58,13,'Forwarded to Respondent','Under Investigation','Under Investigation','Coordinator released the permitted case information to the respondent(s).',NULL,3,NULL,2,'2026-09-22 22:09:38'),(59,11,'Forwarded to Respondent','Under Investigation','Under Investigation','Coordinator released the permitted case information to the respondent(s).',NULL,4,NULL,2,'2026-09-22 22:11:38'),(60,13,'Counter-Statement Submitted','Under Investigation','Under Investigation','Respondent submitted their counter-statement for case 2026-013.',NULL,NULL,NULL,24,'2026-09-23 01:54:10'),(61,13,'Counter-Statement Submitted','Under Investigation','Under Investigation','Respondent submitted their counter-statement for case 2026-013.',NULL,NULL,NULL,24,'2026-09-23 02:00:07'),(62,13,'Counter-Statement Submitted','Under Investigation','Under Investigation','Respondent submitted their counter-statement for case 2026-013.',NULL,NULL,NULL,24,'2026-09-23 02:03:08'),(63,13,'Counter-Statement Submitted','Under Investigation','Under Investigation','Respondent submitted their counter-statement for case 2026-013.',NULL,NULL,NULL,24,'2026-09-23 02:07:31'),(64,13,'Counter-Statement Submitted','Under Investigation','Under Investigation','Respondent submitted their counter-statement for case 2026-013.',NULL,NULL,NULL,24,'2026-09-23 02:30:18');
/*!40000 ALTER TABLE `case_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `case_messages`
--

DROP TABLE IF EXISTS `case_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `case_messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `sender_account_id` int(10) unsigned NOT NULL,
  `receiver_account_id` int(10) unsigned NOT NULL,
  `message` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `idx_messages_complaint` (`complaint_id`),
  KEY `idx_messages_sender` (`sender_account_id`),
  KEY `idx_messages_receiver` (`receiver_account_id`),
  KEY `idx_messages_complaint_unread` (`complaint_id`,`receiver_account_id`,`is_read`),
  CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_account_id`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_messages`
--

LOCK TABLES `case_messages` WRITE;
/*!40000 ALTER TABLE `case_messages` DISABLE KEYS */;
INSERT INTO `case_messages` VALUES (11,0,2,3,'Hi',NULL,1,'2026-09-02 00:06:52','2026-09-02 00:06:44','2026-09-02 00:06:52'),(12,0,3,2,'hi',NULL,1,'2026-09-02 00:30:02','2026-09-02 00:29:46','2026-09-02 00:30:02'),(13,0,24,2,'hi',NULL,1,'2026-09-03 02:48:24','2026-09-02 04:18:12','2026-09-03 02:48:24'),(14,0,2,24,'HI',NULL,1,'2026-09-03 02:48:42','2026-09-03 02:48:28','2026-09-03 02:48:42'),(15,0,24,2,'HI',NULL,1,'2026-09-17 16:37:15','2026-09-03 02:48:48','2026-09-17 16:37:15'),(16,0,2,24,'hi',NULL,1,'2026-09-19 14:23:35','2026-09-19 14:23:33','2026-09-19 14:23:35'),(17,0,2,22,'Case 2026-015 has been resolved and is now closed. Thank you for your cooperation. Please contact the SDRU office if you have further concerns.',NULL,0,NULL,'2026-09-19 16:41:54','2026-09-19 16:41:54'),(18,0,2,5,'Case 2026-015 has been resolved and is now closed. Thank you for your cooperation. Please contact the SDRU office if you have further concerns.',NULL,1,'2026-09-22 07:59:21','2026-09-19 16:41:54','2026-09-22 07:59:21'),(19,0,2,22,'Case 2026-015 has been resolved and is now closed. Thank you for your cooperation. Please contact the SDRU office if you have further concerns.',NULL,0,NULL,'2026-09-19 16:44:46','2026-09-19 16:44:46'),(20,0,2,5,'Case 2026-015 has been resolved and is now closed. Thank you for your cooperation. Please contact the SDRU office if you have further concerns.',NULL,1,'2026-09-22 07:59:21','2026-09-19 16:44:46','2026-09-22 07:59:21'),(21,0,2,24,'Your SICMS account has been automatically linked to case 2026-013 as the respondent. You can view the case and submit a response there.',NULL,0,NULL,'2026-09-22 21:11:12','2026-09-22 21:11:12'),(22,0,2,24,'Your SICMS account has been automatically linked to case 2026-011 as the respondent. You can view the case and submit a response there.',NULL,0,NULL,'2026-09-22 22:11:18','2026-09-22 22:11:18');
/*!40000 ALTER TABLE `case_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `case_updates`
--

DROP TABLE IF EXISTS `case_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `case_updates` (
  `update_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `author_account_id` int(10) unsigned NOT NULL,
  `update_type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `case_status_snapshot` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`update_id`),
  KEY `idx_case_updates_complaint` (`complaint_id`),
  KEY `idx_case_updates_author` (`author_account_id`),
  CONSTRAINT `fk_case_updates_author` FOREIGN KEY (`author_account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_case_updates_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_updates`
--

LOCK TABLES `case_updates` WRITE;
/*!40000 ALTER TABLE `case_updates` DISABLE KEYS */;
INSERT INTO `case_updates` VALUES (1,35,2,'additional_details','Added the following respondent details: Respondent Type, Full Name, Gender, Student No.','Under Investigation','2026-09-22 20:25:27'),(2,35,2,'additional_details','Added the following respondent details: Respondent Type, Full Name, Student No.','Returned for Revision','2026-09-22 20:26:43'),(3,35,2,'additional_details','Added the following respondent details: Respondent Type, Full Name, Student No.','Returned for Revision','2026-09-22 20:35:44'),(4,13,2,'additional_details','Added the following respondent details: Respondent Type, Full Name, Student No. The respondent was automatically linked to the active SICMS account kwinjherane.ocareza@clsu2.edu.ph.','Under Investigation','2026-09-22 21:11:12'),(5,13,2,'additional_details','Added the following respondent details: Age.','Under Investigation','2026-09-22 21:24:14'),(6,13,2,'additional_details','Added the following respondent details: Full Name, Gender, College, Course Year, Contact Info, Email, Address.','Under Investigation','2026-09-22 22:00:50'),(7,11,2,'additional_details','Added the following respondent details: Respondent Type, Full Name, Gender, Age, Student No, College, Course Year, Contact Info, Email, Address. The respondent was automatically linked to the active SICMS account kwinjherane.ocareza@clsu2.edu.ph.','Under Investigation','2026-09-22 22:11:18');
/*!40000 ALTER TABLE `case_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `complaint_evidence`
--

DROP TABLE IF EXISTS `complaint_evidence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `complaint_evidence` (
  `evidence_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `update_id` int(10) unsigned DEFAULT NULL,
  `reformation_record_id` int(10) unsigned DEFAULT NULL,
  `counter_statement_id` int(10) unsigned DEFAULT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `doc_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`evidence_id`),
  KEY `idx_evidence_complaint` (`complaint_id`),
  KEY `idx_evidence_update` (`update_id`),
  KEY `idx_evidence_reformation_record` (`reformation_record_id`),
  KEY `fk_evidence_counter_statement` (`counter_statement_id`),
  CONSTRAINT `fk_evidence_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evidence_counter_statement` FOREIGN KEY (`counter_statement_id`) REFERENCES `counter_statements` (`counter_statement_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evidence_update` FOREIGN KEY (`update_id`) REFERENCES `case_updates` (`update_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaint_evidence`
--

LOCK TABLES `complaint_evidence` WRITE;
/*!40000 ALTER TABLE `complaint_evidence` DISABLE KEYS */;
INSERT INTO `complaint_evidence` VALUES (3,13,NULL,NULL,5,'PHONE.png','20260923022956_8be67114fa5c4529d706464c.png','storage/evidence/20260923022956_8be67114fa5c4529d706464c.png','image/png',123531,'2026-09-23 02:29:56','png');
/*!40000 ALTER TABLE `complaint_evidence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `complaint_respondents`
--

DROP TABLE IF EXISTS `complaint_respondents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `complaint_respondents` (
  `respondent_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `respondent_type` varchar(50) NOT NULL DEFAULT 'Student',
  `full_name` varchar(255) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `student_no` varchar(50) DEFAULT NULL,
  `employee_no` varchar(100) DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `office_department` varchar(255) DEFAULT NULL,
  `course_year` varchar(100) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `age` int(10) unsigned DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `invited_at` datetime DEFAULT NULL,
  `invitation_token` varchar(255) DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`respondent_id`),
  KEY `idx_respondents_complaint` (`complaint_id`),
  KEY `fk_respondents_account` (`account_id`),
  CONSTRAINT `fk_respondents_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_respondents_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaint_respondents`
--

LOCK TABLES `complaint_respondents` WRITE;
/*!40000 ALTER TABLE `complaint_respondents` DISABLE KEYS */;
INSERT INTO `complaint_respondents` VALUES (1,31,'Student','Maria Santos','Female','2024-00002',NULL,'College of Engineering',NULL,'BS Computer Science | B',NULL,NULL,'','Respondent allegedly initiated the verbal confrontation by making disparaging remarks about the complainant\'s academic performance.',NULL,NULL,'maria.santos@clsu2.edu.ph',NULL,NULL,NULL,NULL,NULL,'2026-09-02 03:34:39'),(6,35,'Student','Kwin Jherane Ocareza','','23-2620','','','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-22 20:35:44'),(7,13,'Student','KWIN JHERANE OCAREZA','Female','23-2620','','College of Engineering','','Bachelor of Science in Information Technology (BSIT) | 4-2','','','09544834569','',21,NULL,'kwinjherane.ocareza@clsu2.edu.ph','Sto. Nino 2nd, San Jose City, Nueva EcijaZone 6',NULL,NULL,NULL,24,'2026-09-22 21:11:07'),(8,11,'Student','KWIN JHERANE OCAREZA','Female','23-2620','','College of Engineering','','Bachelor of Science in Information Technology (BSIT) | 4-2','','','09544834569','',21,NULL,'kwinjherane.ocareza@clsu2.edu.ph','Sto. Nino 2nd, San Jose City, Nueva EcijaZone 6',NULL,NULL,NULL,24,'2026-09-22 22:11:15');
/*!40000 ALTER TABLE `complaint_respondents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `complaint_witnesses`
--

DROP TABLE IF EXISTS `complaint_witnesses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `complaint_witnesses` (
  `witness_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `person_type` varchar(50) NOT NULL DEFAULT 'Student',
  `full_name` varchar(255) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `age` int(10) unsigned DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `student_no` varchar(50) DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `statement` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `employee_no` varchar(100) DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `office_department` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `course_year` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`witness_id`),
  KEY `idx_witnesses_complaint` (`complaint_id`),
  CONSTRAINT `fk_witnesses_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaint_witnesses`
--

LOCK TABLES `complaint_witnesses` WRITE;
/*!40000 ALTER TABLE `complaint_witnesses` DISABLE KEYS */;
INSERT INTO `complaint_witnesses` VALUES (1,31,'Student','Pedro Garcia','Male',NULL,NULL,'2022-00003','pedro.garcia@clsu2.edu.ph','I was sitting at a nearby table when the argument started. Maria approached Juan first and said something about his grades. Juan responded angrily and things escalated. Both parties were shouting within minutes.',NULL,NULL,'College of Agriculture',NULL,NULL,NULL,NULL,'4th Year','BS Agriculture | A','2026-09-02 03:34:39');
/*!40000 ALTER TABLE `complaint_witnesses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `complaints`
--

DROP TABLE IF EXISTS `complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `complaints` (
  `complaint_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `case_number` varchar(50) NOT NULL,
  `complaint_title` varchar(255) NOT NULL,
  `submitted_by_account_id` int(10) unsigned NOT NULL,
  `complainant_name` varchar(255) NOT NULL,
  `complainant_gender` varchar(20) DEFAULT NULL,
  `complainant_age` int(10) unsigned DEFAULT NULL,
  `complainant_address` text DEFAULT NULL,
  `complainant_type` varchar(50) NOT NULL DEFAULT 'Student',
  `complainant_relationship` varchar(100) DEFAULT NULL,
  `complainant_employee_no` varchar(100) DEFAULT NULL,
  `complainant_department` varchar(255) DEFAULT NULL,
  `complainant_position` varchar(255) DEFAULT NULL,
  `complainant_affiliation` varchar(255) DEFAULT NULL,
  `complainant_purpose` varchar(255) DEFAULT NULL,
  `complainant_student_no` varchar(50) DEFAULT NULL,
  `complainant_email` varchar(255) DEFAULT NULL,
  `complainant_contact` varchar(50) DEFAULT NULL,
  `complainant_college` varchar(255) DEFAULT NULL,
  `complainant_course` varchar(255) DEFAULT NULL,
  `complainant_year_level` varchar(50) DEFAULT NULL,
  `complainant_section` varchar(50) DEFAULT NULL,
  `complainant_course_year` varchar(100) DEFAULT NULL,
  `case_classification` varchar(100) NOT NULL,
  `incident_datetime` datetime DEFAULT NULL,
  `incident_location` varchar(255) DEFAULT NULL,
  `complaint_details` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Under Investigation',
  `respondent_released_at` datetime DEFAULT NULL,
  `respondent_released_by_account_id` int(10) unsigned DEFAULT NULL,
  `respondent_visibility` text DEFAULT NULL,
  `assigned_coordinator_account_id` int(10) unsigned DEFAULT NULL,
  `assigned_reformation_coordinator_account_id` int(10) unsigned DEFAULT NULL,
  `reformation_completed_at` datetime DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `case_source` varchar(20) NOT NULL DEFAULT 'Online Submission',
  `original_case_date` date DEFAULT NULL,
  `legacy_outcome` varchar(255) DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `resolution_date` date DEFAULT NULL,
  `remarks_notes` text DEFAULT NULL,
  `legacy_entry_source` varchar(100) DEFAULT NULL,
  `outcome` text DEFAULT NULL,
  PRIMARY KEY (`complaint_id`),
  UNIQUE KEY `uq_complaints_case_number` (`case_number`),
  KEY `idx_complaints_submitted_by` (`submitted_by_account_id`),
  KEY `idx_complaints_coordinator` (`assigned_coordinator_account_id`),
  KEY `idx_complaints_status` (`status`),
  KEY `idx_complaints_classification` (`case_classification`),
  KEY `idx_complaints_submitted_at` (`submitted_at`),
  KEY `idx_complaints_source` (`case_source`),
  KEY `idx_complaints_source_orig_year` (`case_source`,`original_case_date`),
  KEY `idx_complaints_reformation_coordinator` (`assigned_reformation_coordinator_account_id`),
  KEY `fk_complaints_released_by` (`respondent_released_by_account_id`),
  CONSTRAINT `fk_complaints_coordinator` FOREIGN KEY (`assigned_coordinator_account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_complaints_reformation_coordinator` FOREIGN KEY (`assigned_reformation_coordinator_account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_complaints_released_by` FOREIGN KEY (`respondent_released_by_account_id`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_complaints_submitter` FOREIGN KEY (`submitted_by_account_id`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaints`
--

LOCK TABLES `complaints` WRITE;
/*!40000 ALTER TABLE `complaints` DISABLE KEYS */;
INSERT INTO `complaints` VALUES (1,'2026-001','Plagiarism in Research Paper',8,'Juan Dela Cruz','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00001','juan.delacruz@clsu2.edu.ph',NULL,'College of Science','BS Biology','3rd Year','A',NULL,'Academic Dishonesty','2026-01-15 09:30:00','Room 201, Science Building','Submitted a research paper with significant portions copied from published sources without proper citation.','Under Investigation',NULL,NULL,NULL,3,NULL,NULL,'2026-01-15 10:00:00','2026-09-01 22:25:52','2026-09-19 15:56:37','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,'2026-002','Cheating During Midterm Examination',9,'Maria Santos','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2024-00002','maria.santos@clsu2.edu.ph',NULL,'College of Engineering','BS Computer Science','2nd Year','B',NULL,'Academic Dishonesty','2026-01-28 10:00:00','Room 105, Engineering Building','Caught using unauthorized notes during the midterm examination in Data Structures.','Under Investigation',NULL,NULL,NULL,4,NULL,NULL,'2026-01-28 11:30:00','2026-09-01 22:25:52','2026-09-19 15:56:37','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,'2026-003','Unauthorized Collaboration on Assignment',10,'Pedro Garcia','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-00003','pedro.garcia@clsu2.edu.ph',NULL,'College of Agriculture','BS Agriculture','4th Year','A',NULL,'Academic Dishonesty','2026-02-10 14:00:00','Agriculture Lab 3','Submitted an assignment nearly identical to another students work, indicating unauthorized collaboration.','Under Investigation',NULL,NULL,NULL,5,NULL,NULL,'2026-02-10 15:00:00','2026-09-01 22:25:52','2026-09-19 15:56:37','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,'2026-004','Fabrication of Laboratory Results',11,'Ana Reyes','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00004','ana.reyes@clsu2.edu.ph',NULL,'College of Science','BS Chemistry','3rd Year','B',NULL,'Academic Dishonesty','2026-02-22 08:00:00','Chemistry Laboratory 2','Lab report contained fabricated data points that were physically impossible to obtain during the experiment.','Under Investigation',NULL,NULL,NULL,3,NULL,NULL,'2026-02-22 09:00:00','2026-09-01 22:25:52','2026-09-19 15:56:37','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,'2026-005','Disruptive Behavior in Class',12,'Carmen Mendoza','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2025-00005','carmen.mendoza@clsu2.edu.ph',NULL,'College of Education','BS Education','1st Year','A',NULL,'Behavioral Misconduct','2026-03-05 11:00:00','Room 302, Education Building','Repeatedly disrupted class proceedings by talking loudly and using phone despite multiple warnings.','Under Investigation',NULL,NULL,NULL,4,NULL,NULL,'2026-03-05 12:00:00','2026-09-01 22:25:52','2026-09-19 15:56:37','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,'2026-006','Academic Dishonesty in Online Quiz',13,'Luis Bautista','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2024-00006','luis.bautista@clsu2.edu.ph',NULL,'College of Business','BS Business Administration','2nd Year','A',NULL,'Academic Dishonesty','2026-03-18 09:00:00','Online - Canvas LMS','System logs show suspicious activity during online quiz including multiple tab switches and external website access.','Under Investigation',NULL,NULL,NULL,5,NULL,NULL,'2026-03-18 10:30:00','2026-09-01 22:25:52','2026-09-19 15:56:37','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,'2026-007','Vandalism of School Property',14,'Rosa Cruz','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00007','rosa.cruz@clsu2.edu.ph',NULL,'College of Engineering','BS Mechanical Engineering','3rd Year','A',NULL,'Property Damage','2026-04-02 16:00:00','Mechanical Engineering Workshop','Deliberately damaged laboratory equipment worth approximately Php 15,000 during an unsupervised session.','Under Investigation',NULL,NULL,NULL,3,NULL,NULL,'2026-04-02 17:00:00','2026-09-01 22:25:52','2026-09-19 15:56:37','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,'2026-008','Harassment of Classmate',15,'Miguel Rivera','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-00008','miguel.rivera@clsu2.edu.ph',NULL,'College of Arts','AB Psychology','4th Year','A',NULL,'Harassment','2026-04-15 13:00:00','College of Arts Commons','Made threatening remarks and intimidating gestures toward a classmate during a group discussion.','Resolved',NULL,NULL,NULL,4,NULL,NULL,'2026-04-15 14:00:00','2026-09-01 22:25:52','2026-09-19 18:15:13','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,'2026-009','Theft of Personal Belongings',16,'Elena Torres','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2024-00009','elena.torres@clsu2.edu.ph',NULL,'College of Science','BS Biology','2nd Year','B',NULL,'Theft','2026-05-01 07:30:00','Science Building Locker Room','Personal laptop was stolen from the locker room. Security footage is being reviewed.','Under Investigation',NULL,NULL,NULL,5,NULL,NULL,'2026-05-01 08:30:00','2026-09-01 22:25:52','2026-09-19 15:56:37','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,'2026-010','Violation of Dress Code Policy',17,'Jose Flores','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2025-00010','jose.flores@clsu2.edu.ph',NULL,'College of Agriculture','BS Agriculture','1st Year','B',NULL,'Policy Violation','2026-05-14 08:00:00','College of Agriculture Main Hall','Repeatedly violated the university dress code policy despite previous verbal warnings from faculty.','Under Investigation',NULL,NULL,NULL,3,NULL,NULL,'2026-05-14 09:00:00','2026-09-01 22:25:52','2026-09-19 15:56:37','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,'2026-011','Cyberbullying on Social Media',18,'Patricia Gomez','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00011','patricia.gomez@clsu2.edu.ph',NULL,'College of Education','BS Education','3rd Year','A',NULL,'Harassment','2026-06-03 20:00:00','Online - Social Media','Posted derogatory and humiliating content about a fellow student on social media platforms.','Under Investigation','2026-09-22 22:11:38',2,'{\"complaint_details\":true,\"incident\":true,\"hearings\":true,\"final_information\":true}',4,NULL,NULL,'2026-06-04 08:00:00','2026-09-01 22:25:52','2026-09-22 22:11:38','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(12,'2026-012','Forgery of Academic Documents',19,'Daniel Lopez','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-00012','daniel.lopez@clsu2.edu.ph',NULL,'College of Business','BS Business Administration','4th Year','A',NULL,'Fraud','2026-06-20 10:00:00','Registrar Office','Submitted a falsified transcript of records with altered grades for graduate school admission.','Archived',NULL,NULL,NULL,5,NULL,NULL,'2026-06-20 11:00:00','2026-09-01 22:25:52','2026-09-19 17:20:13','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(13,'2026-013','Attendance Fraud',20,'Marcela Diaz','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2024-00013','marcela.diaz@clsu2.edu.ph',NULL,'College of Science','BS Chemistry','2nd Year','A',NULL,'Academic Dishonesty','2026-07-10 07:00:00','Chemistry Lecture Hall','Used another students ID to mark attendance on multiple occasions throughout the semester.','Under Investigation','2026-09-22 22:09:38',2,'{\"complaint_details\":true,\"incident\":true,\"hearings\":true,\"final_information\":true}',3,NULL,NULL,'2026-07-10 08:00:00','2026-09-01 22:25:52','2026-09-22 22:09:38','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(14,'2026-014','Destruction of Library Materials',21,'Francisco Ramos','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00014','francisco.ramos@clsu2.edu.ph',NULL,'College of Arts','AB English','3rd Year','A',NULL,'Property Damage','2026-08-05 14:00:00','University Library','Deliberately tore pages from reference books and damaged library equipment during study session.','Archived',NULL,NULL,NULL,4,NULL,NULL,'2026-08-05 15:00:00','2026-09-01 22:25:52','2026-09-19 16:46:37','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(15,'2026-015','Verbal Abuse of Faculty Member',22,'Isabela Morales','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2025-00015','isabela.morales@clsu2.edu.ph',NULL,'College of Engineering','BS Computer Science','1st Year','A',NULL,'Cyberbullying','2026-09-01 11:00:00','Room 108, Engineering Building','Used offensive and disrespectful language toward a faculty member during a grade consultation.','Archived',NULL,NULL,NULL,5,NULL,NULL,'2026-09-01 12:00:00','2026-09-01 22:25:52','2026-09-19 16:45:00','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,'okay na'),(16,'2024-001','Legacy: Cheating in Final Examination',2,'Rica Aquino','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2020-01001','rica.aquino@clsu2.edu.ph',NULL,'College of Science','BS Biology','4th Year','A',NULL,'Academic Dishonesty','2024-01-15 08:00:00','Science Auditorium','Caught copying answers from a neighbor during the final examination in Genetics.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2024-01-15 09:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2024-01-15','Suspended for one semester','Student served suspension for one semester.','2024-02-28',NULL,'Legacy System','Suspended for one semester effective second semester AY 2023-2024.'),(17,'2024-002','Legacy: Plagiarism in Undergraduate Thesis',2,'Ricardo Vergara','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2020-01002','ricardo.vergara@clsu2.edu.ph',NULL,'College of Engineering','BS Computer Science','4th Year','A',NULL,'Academic Dishonesty','2024-02-20 10:00:00','Engineering Faculty Office','Thesis document found to contain large sections copied from previously published theses without attribution.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2024-02-20 11:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2024-02-20','Probation for one academic year','Placed on academic probation for the remainder of the program.','2024-04-10',NULL,'Legacy System','Probation for one academic year with mandatory ethics seminar.'),(18,'2024-003','Legacy: Unauthorized Use of AI in Essay',2,'Marissa Arceo','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-02003','marissa.arceo@clsu2.edu.ph',NULL,'College of Business','BS Business Administration','3rd Year','B',NULL,'Academic Dishonesty','2024-03-10 14:00:00','Online Submission','Essay submitted was determined to be entirely generated by artificial intelligence tools.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2024-03-10 15:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2024-03-10','Written warning','Issued formal written warning and required to resubmit original work.','2024-03-25',NULL,'Legacy System','Written warning with requirement to complete academic integrity workshop.'),(19,'2024-004','Legacy: Destruction of Laboratory Equipment',2,'Enrico Salazar','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-02004','enrico.salazar@clsu2.edu.ph',NULL,'College of Science','BS Chemistry','3rd Year','A',NULL,'Property Damage','2024-04-05 09:00:00','Chemistry Laboratory 1','Intentionally broke expensive laboratory glassware and equipment during a practical exam.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2024-04-05 10:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2024-04-05','Dismissed from program','Removed from the BS Chemistry program and transferred to general studies.','2024-05-15',NULL,'Legacy System','Dismissed from BS Chemistry program; allowed to enroll in other programs.'),(20,'2024-005','Legacy: Bullying Incident',2,'Teresa Magsaysay','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-03005','teresa.magsaysay@clsu2.edu.ph',NULL,'College of Education','BS Education','2nd Year','A',NULL,'Harassment','2024-05-18 11:00:00','Education Building Corridor','Physically and verbally bullied a younger student on multiple occasions.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2024-05-18 12:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2024-05-18','Probation for one semester','Placed on behavioral probation with mandatory counseling sessions.','2024-06-20',NULL,'Legacy System','Probation for one semester with mandatory counseling.'),(21,'2024-006','Legacy: Theft from Dormitory',2,'Alfredo Manalo','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-04006','alfredo.manalo@clsu2.edu.ph',NULL,'College of Agriculture','BS Agriculture','3rd Year','B',NULL,'Theft','2024-06-22 22:00:00','University Dormitory Room 412','Stole personal belongings including electronic devices from roommates desk.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2024-06-23 08:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2024-06-22','Suspended for two semesters','Suspended from university for two semesters with conditions for readmission.','2024-08-10',NULL,'Legacy System','Suspended for two semesters; must complete community service before readmission.'),(22,'2024-007','Legacy: Academic Fraud',2,'Violeta Sevilla','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2020-05007','violeta.sevilla@clsu2.edu.ph',NULL,'College of Arts','AB Psychology','4th Year','A',NULL,'Fraud','2024-08-10 09:00:00','Registrar Office','Submitted fraudulent documents claiming completion of required internship hours.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2024-08-10 10:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2024-08-10','Dismissed from university','Permanently dismissed from the university for systematic academic fraud.','2024-09-15',NULL,'Legacy System','Permanent dismissal from the university.'),(23,'2024-008','Legacy: Vandalism of Campus Facilities',2,'Fernando Lacson','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-06008','fernando.lacson@clsu2.edu.ph',NULL,'College of Engineering','BS Mechanical Engineering','2nd Year','A',NULL,'Property Damage','2024-09-15 17:00:00','Engineering Building Restroom','Graffiti and deliberate damage to fixtures in the engineering building restroom.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2024-09-16 08:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2024-09-15','Community service and written warning','Required 40 hours of community service and issued formal warning.','2024-10-20',NULL,'Legacy System','40 hours community service and written warning.'),(24,'2025-001','Legacy: Sexual Harassment Complaint',2,'Gloriosa Pascual','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-07009','gloriosa.pascual@clsu2.edu.ph',NULL,'College of Science','BS Biology','3rd Year','B',NULL,'Harassment','2025-01-12 10:00:00','Science Building Hallway','Made unwanted advances and inappropriate comments toward a fellow student.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2025-01-12 11:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2025-01-12','Probation for one academic year','Placed on strict behavioral probation with no-contact order.','2025-02-28',NULL,'Legacy System','Probation for one academic year with mandatory behavioral program.'),(25,'2025-002','Legacy: Examination Leak Involvement',2,'Edgardo Suarez','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2020-08010','edgardo.suarez@clsu2.edu.ph',NULL,'College of Business','BS Business Administration','4th Year','A',NULL,'Fraud','2025-02-28 08:00:00','Business Faculty Office','Distributed confidential examination materials to other students prior to scheduled exam.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2025-02-28 09:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2025-02-28','Suspended for one semester','Suspended for one semester; barred from honors list.','2025-04-10',NULL,'Legacy System','Suspended for one semester and removed from honors consideration.'),(26,'2025-003','Legacy: Falsification of Academic Records',2,'Margarita Villanueva','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-09011','margarita.villanueva@clsu2.edu.ph',NULL,'College of Education','BS Education','4th Year','A',NULL,'Fraud','2025-03-15 09:00:00','Registrar Office','Altered grades on official transcript using sophisticated forgery techniques.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2025-03-15 10:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2025-03-15','Dismissed from program','Dismissed from the Education program for record falsification.','2025-04-20',NULL,'Legacy System','Dismissed from BS Education program.'),(27,'2025-004','Legacy: Property Damage in Dormitory',2,'Rogelio Esguerra','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-10012','rogelio.esguerra@clsu2.edu.ph',NULL,'College of Agriculture','BS Agriculture','2nd Year','B',NULL,'Property Damage','2025-04-20 21:00:00','University Dormitory Common Area','Damaged common area furniture and appliances during a party.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2025-04-21 08:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2025-04-20','Restitution and written warning','Required to pay for damages and issued formal warning.','2025-05-15',NULL,'Legacy System','Full restitution of Php 8,500 and written warning.'),(28,'2025-005','Legacy: Verbal Threat to Fellow Student',2,'Emilia Rendon','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-11013','emilia.rendon@clsu2.edu.ph',NULL,'College of Arts','AB English','3rd Year','B',NULL,'Behavioral Misconduct','2025-05-10 15:00:00','Arts Building Cafeteria','Made repeated verbal threats of physical harm toward another student over a personal dispute.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2025-05-10 16:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2025-05-10','Probation for one semester','Behavioral probation with mandatory anger management counseling.','2025-06-10',NULL,'Legacy System','Probation for one semester with anger management program.'),(29,'2025-006','Legacy: Plagiarism in Research Essay',2,'Armando Cuatreras','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-12014','armando.cuatreras@clsu2.edu.ph',NULL,'College of Science','BS Chemistry','2nd Year','A',NULL,'Academic Dishonesty','2025-07-05 10:00:00','Science Faculty Office','Submitted a research essay with 78% similarity to published work detected through Turnitin.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-05 11:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2025-07-05','Suspended for one semester','Suspended for one semester with mandatory academic integrity seminar.','2025-08-15',NULL,'Legacy System','Suspended for one semester; must complete academic integrity course.'),(30,'2025-007','Legacy: Cheating in Laboratory Examination',2,'Carlota Oliva','Female',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-13015','carlota.oliva@clsu2.edu.ph',NULL,'College of Engineering','BS Computer Science','3rd Year','A',NULL,'Academic Dishonesty','2025-08-18 14:00:00','Computer Laboratory 4','Used hidden notes and a mobile phone during the laboratory examination despite warnings.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2025-08-18 15:00:00','2026-09-01 22:25:52','2026-09-22 04:31:49','Legacy','2025-08-18','Exonerated','Investigation concluded evidence was insufficient; student exonerated.','2025-09-10',NULL,'Legacy System','Exonerated due to insufficient evidence.'),(31,'2026-016','Verbal Altercation with Fellow Student',8,'Juan Dela Cruz','Male',NULL,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00001','juan.delacruz@clsu2.edu.ph',NULL,'College of Science','BS Biology','3rd Year','A',NULL,'Behavioral Misconduct','2026-08-28 14:30:00','College of Science Cafeteria','Engaged in a heated verbal altercation with a fellow student during lunch break. The argument escalated to personal insults and threatening language. Multiple witnesses were present and the incident disrupted the peace of the surrounding area.','Archived',NULL,NULL,NULL,3,NULL,NULL,'2026-09-01 10:00:00','2026-09-02 03:34:39','2026-09-19 16:45:36','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(35,'SDRU-20260922-0001','Student Complaint',25,'ALEXA ANGELA GABRIEL','Female',21,NULL,'Student','','','','','','','23-2623','alexaangela.gabriel@clsu2.edu.ph','09277289638','College of Engineering','Bachelor of Science in Information Technology (BSIT)','Fourth Year','4-2','Bachelor of Science in Information Technology (BSIT) | 4-2','Physical Assault','2026-09-09 03:23:00','dasbdajhsbd','sad nsa dns','Returned for Revision',NULL,NULL,NULL,5,NULL,NULL,'2026-09-22 00:23:22','2026-09-22 00:23:22','2026-09-22 20:49:36','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `complaints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversation_participants`
--

DROP TABLE IF EXISTS `conversation_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_participants` (
  `participant_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `counterpart_account_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`participant_id`),
  UNIQUE KEY `uniq_participant_pair` (`complaint_id`,`account_id`,`counterpart_account_id`),
  KEY `idx_participant_account` (`account_id`),
  KEY `fk_participant_counterpart` (`counterpart_account_id`),
  CONSTRAINT `fk_participant_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_participant_counterpart` FOREIGN KEY (`counterpart_account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversation_participants`
--

LOCK TABLES `conversation_participants` WRITE;
/*!40000 ALTER TABLE `conversation_participants` DISABLE KEYS */;
INSERT INTO `conversation_participants` VALUES (53,0,2,3,'2026-09-02 00:06:42'),(54,0,3,2,'2026-09-02 00:06:42'),(58,0,24,2,'2026-09-02 04:18:12'),(59,0,2,24,'2026-09-02 04:18:12'),(65,0,2,22,'2026-09-19 16:41:54'),(66,0,22,2,'2026-09-19 16:41:54'),(67,0,2,5,'2026-09-19 16:41:54'),(68,0,5,2,'2026-09-19 16:41:54');
/*!40000 ALTER TABLE `conversation_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `counter_statements`
--

DROP TABLE IF EXISTS `counter_statements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `counter_statements` (
  `counter_statement_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `respondent_id` int(10) unsigned NOT NULL,
  `respondent_account_id` int(10) unsigned NOT NULL,
  `content` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Draft',
  `submitted_at` datetime DEFAULT NULL,
  `coordinator_action` varchar(100) DEFAULT NULL,
  `coordinator_action_by_account_id` int(10) unsigned DEFAULT NULL,
  `coordinator_action_at` datetime DEFAULT NULL,
  `forwarded_at` datetime DEFAULT NULL,
  `complaint_response_content` text DEFAULT NULL,
  `complaint_response_updated_at` datetime DEFAULT NULL,
  `complaint_response_submitted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`counter_statement_id`),
  KEY `idx_counter_statements_complaint` (`complaint_id`),
  KEY `idx_counter_statements_respondent` (`respondent_id`),
  KEY `idx_counter_statements_account` (`respondent_account_id`),
  KEY `fk_counter_statements_action_account` (`coordinator_action_by_account_id`),
  CONSTRAINT `fk_counter_statements_account` FOREIGN KEY (`respondent_account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_counter_statements_action_account` FOREIGN KEY (`coordinator_action_by_account_id`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_counter_statements_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_counter_statements_respondent` FOREIGN KEY (`respondent_id`) REFERENCES `complaint_respondents` (`respondent_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `counter_statements`
--

LOCK TABLES `counter_statements` WRITE;
/*!40000 ALTER TABLE `counter_statements` DISABLE KEYS */;
INSERT INTO `counter_statements` VALUES (5,13,7,24,'i did not really','Submitted','2026-09-23 01:54:10',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-23 01:51:05','2026-09-23 02:30:18');
/*!40000 ALTER TABLE `counter_statements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hearings`
--

DROP TABLE IF EXISTS `hearings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hearings` (
  `hearing_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `scheduled_by_account_id` int(10) unsigned DEFAULT NULL,
  `hearing_datetime` datetime DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `google_meet_link` varchar(500) DEFAULT NULL,
  `google_event_id` varchar(1024) DEFAULT NULL,
  `hearing_type` varchar(50) NOT NULL DEFAULT 'Face-to-face',
  `instructions` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Scheduled',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`hearing_id`),
  KEY `idx_hearings_complaint` (`complaint_id`),
  KEY `idx_hearings_scheduled_by` (`scheduled_by_account_id`),
  KEY `idx_hearings_status` (`status`),
  KEY `idx_hearings_datetime` (`hearing_datetime`),
  CONSTRAINT `fk_hearings_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hearings_scheduler` FOREIGN KEY (`scheduled_by_account_id`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hearings`
--

LOCK TABLES `hearings` WRITE;
/*!40000 ALTER TABLE `hearings` DISABLE KEYS */;
INSERT INTO `hearings` VALUES (1,31,3,'2026-09-15 10:00:00','SDRU Conference Room 301',NULL,NULL,'Face-to-face',NULL,'Both parties and the witness are required to attend. Please bring any supporting evidence or written statements.','Completed','2026-09-02 03:34:39','2026-09-02 04:12:16');
/*!40000 ALTER TABLE `hearings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hidden_conversations`
--

DROP TABLE IF EXISTS `hidden_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hidden_conversations` (
  `hidden_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `counterpart_account_id` int(10) unsigned NOT NULL,
  `hidden_at` datetime NOT NULL DEFAULT current_timestamp(),
  `visible` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`hidden_id`),
  UNIQUE KEY `uniq_hidden_pair` (`complaint_id`,`account_id`,`counterpart_account_id`),
  KEY `fk_hidden_account` (`account_id`),
  KEY `fk_hidden_counterpart` (`counterpart_account_id`),
  CONSTRAINT `fk_hidden_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hidden_counterpart` FOREIGN KEY (`counterpart_account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hidden_conversations`
--

LOCK TABLES `hidden_conversations` WRITE;
/*!40000 ALTER TABLE `hidden_conversations` DISABLE KEYS */;
INSERT INTO `hidden_conversations` VALUES (54,0,2,3,'1000-01-01 00:00:00',1),(55,0,3,2,'1000-01-01 00:00:00',1),(60,0,24,2,'2026-09-22 20:48:37',1),(61,0,2,24,'1000-01-01 00:00:00',1),(68,0,2,22,'1000-01-01 00:00:00',1),(69,0,22,2,'1000-01-01 00:00:00',1),(70,0,2,5,'1000-01-01 00:00:00',1),(71,0,5,2,'1000-01-01 00:00:00',1);
/*!40000 ALTER TABLE `hidden_conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_incharges`
--

DROP TABLE IF EXISTS `legacy_incharges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_incharges` (
  `legacy_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `position` varchar(100) NOT NULL,
  `tenure` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`legacy_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_incharges`
--

LOCK TABLES `legacy_incharges` WRITE;
/*!40000 ALTER TABLE `legacy_incharges` DISABLE KEYS */;
INSERT INTO `legacy_incharges` VALUES (1,'Ricardo Santos','SDRU Head','2019-2026',NULL,NULL,'2026-09-02 04:01:16','2026-09-02 04:01:16');
/*!40000 ALTER TABLE `legacy_incharges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_sessions`
--

DROP TABLE IF EXISTS `login_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_sessions` (
  `session_id` varchar(128) NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `device_type` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `location` varchar(255) NOT NULL DEFAULT 'Unknown location',
  `user_agent` varchar(255) DEFAULT NULL,
  `last_login_at` datetime NOT NULL,
  `last_seen_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `idx_login_sessions_account` (`account_id`),
  CONSTRAINT `fk_login_sessions_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_sessions`
--

LOCK TABLES `login_sessions` WRITE;
/*!40000 ALTER TABLE `login_sessions` DISABLE KEYS */;
INSERT INTO `login_sessions` VALUES ('038d81b585f2880d1d906ec5cb25c9e3',2,'Desktop browser','127.0.0.1','Localhost (this computer)','','2026-09-23 02:45:09','2026-09-23 02:45:09',NULL),('1h52mad12s0mvbttmq7l2vjo42',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 19:18:21','2026-09-22 19:18:21',NULL),('1hbt1s756q7q25p8uujlbs7mau',24,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 16:54:53','2026-09-22 16:56:51',NULL),('1lun8ptuac08umpudat71rg28o',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:18:01','2026-09-23 01:34:11',NULL),('1o7rm493angnube326q7kal1f2',11,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0','2026-09-22 08:07:40','2026-09-22 08:11:36',NULL),('5to9h49snf29kqqdnr66mdh5q7',9,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0','2026-09-22 08:36:54','2026-09-22 08:36:54',NULL),('6msnqs8c2r6ej4qnfrstmi1q5t',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:21:38','2026-09-23 02:49:22',NULL),('7iek3q5fph4k4oakb4uojqbltj',31,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 19:33:08','2026-09-22 19:33:08',NULL),('7u17vs2tff2mc6c5hmap82ugnf',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:26:17','2026-09-22 07:26:17',NULL),('893m7belnp0nd6q2lmbgh5t8jj',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 03:27:36','2026-09-22 03:27:36',NULL),('89l2bjb57p0f316alcqc8aabfa',5,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 04:44:27','2026-09-22 04:44:27',NULL),('8jro1392p8dnhlgbl82ksjqoda',5,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 04:15:27','2026-09-22 04:15:27',NULL),('9tnoq0knpgfb11itoino3l95ri',24,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 17:10:52','2026-09-22 18:02:54',NULL),('ahiir882sel75st7kn351j0f56',25,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 00:22:52','2026-09-22 00:22:52',NULL),('bpp3qkbnr32t15bkp5qmh2uo76',2,'Desktop browser','::1','Localhost (this computer)','curl/8.21.0','2026-09-22 04:33:24','2026-09-22 04:34:45',NULL),('diueao4mi372dn0mmj6pjs9omq',25,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 23:48:43','2026-09-21 23:49:21',NULL),('fm1eu9tjejaai478k0hqriu680',9,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 03:56:10','2026-09-22 04:50:22',NULL),('fuba52cb8g71uaehp6u0530lun',6,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:13:19','2026-09-22 07:13:19',NULL),('gi62b049rnb2jium8704qfpdgc',9,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0','2026-09-22 05:54:35','2026-09-22 05:54:35',NULL),('iprb3lv8dcs1o9sal1lejjp7sd',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-09-22 05:39:05','2026-09-22 05:39:05',NULL),('lc2chgs9h88ccn5ntlbiuqhrld',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 03:27:58','2026-09-22 03:27:58',NULL),('lnu7fkrlsiio7brstuupr01hpc',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 00:24:27','2026-09-22 00:24:27',NULL),('m382giav1r0n1ln1kgo9nbl1m3',8,'Desktop browser','::1','Localhost (this computer)','curl/8.21.0','2026-09-22 04:33:26','2026-09-22 04:33:26',NULL),('m6u0m06rhtma7gsqf6omrkpa0b',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 07:36:45','2026-09-22 07:36:45',NULL),('mbj4vnuljt8ulc8qqbbkmbk0r4',6,'Desktop browser','::1','Localhost (this computer)','curl/8.21.0','2026-09-22 04:33:26','2026-09-22 04:33:26',NULL),('mk4r9pl77cls9699levrdtj0h5',3,'Desktop browser','::1','Localhost (this computer)','curl/8.21.0','2026-09-22 04:33:25','2026-09-22 04:33:25',NULL),('nl9ic1s6ssu7brtm259pu2i1t1',25,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 23:49:35','2026-09-21 23:49:35',NULL),('qks28igqioqqsgc9ihvu4dv922',3,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 00:26:02','2026-09-22 00:26:02',NULL),('rm1a35kp5ep3g5hieois434afl',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 03:12:00','2026-09-22 03:12:00',NULL),('t3imaho3jcjv6n75dteaneae37',24,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 20:18:27','2026-09-23 02:26:47',NULL),('ue1355cua6f7i6dbjerdel7oiu',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-09-22 05:15:21','2026-09-22 05:15:21',NULL),('ueb1t3rb7rl13c8u0eu8l1847k',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-09-22 05:15:05','2026-09-22 05:15:06',NULL),('v8m36td0fnflot269frm1mjl64',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 17:08:26','2026-09-22 17:08:26',NULL),('vhbc2v41rmc8cj1gd1f8hmv29b',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9444','2026-09-22 05:17:49','2026-09-22 05:17:49',NULL);
/*!40000 ALTER TABLE `login_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(10) unsigned NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_notifications_account` (`account_id`),
  KEY `idx_notifications_account_unread` (`account_id`,`is_read`),
  CONSTRAINT `fk_notifications_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,14,'message_received','New Message Received','You received a new message from Maria Cruz.','web/views/messages/index.php?conversation_id=3',0,NULL,'2026-09-02 00:02:48'),(2,8,'message_received','New Message Received','You received a new message from Maria Cruz.','web/views/messages/index.php?conversation_id=3',0,NULL,'2026-09-02 00:04:04'),(5,3,'message_received','New Message Received','You received a new message from Ricardo Santos.','web/views/messages/index.php?conversation_id=2',1,'2026-09-02 00:07:05','2026-09-02 00:06:44'),(6,20,'complaint_verified','Complaint Verified','Case 2026-013 is now Verified.','web/views/complaints/case_details.php?id=13',0,NULL,'2026-09-02 00:19:34'),(7,2,'message_received','New Message Received','You received a new message from Maria Cruz.','web/views/messages/index.php?conversation_id=3',1,'2026-09-02 00:30:02','2026-09-02 00:29:46'),(8,8,'hearing_completed','Hearing Completed','The hearing for case 2026-016 was marked as Completed.','web/views/complaints/case_details.php?id=31',0,NULL,'2026-09-02 04:12:16'),(9,2,'message_received','New Message Received','You received a new message from KWIN JHERANE OCAREZA.','web/views/messages/index.php?conversation_id=24',1,'2026-09-19 14:37:37','2026-09-02 04:18:12'),(10,24,'message_received','New Message Received','You received a new message from Ricardo Santos.','web/views/messages/index.php?conversation_id=2',1,'2026-09-19 14:27:30','2026-09-03 02:48:29'),(11,2,'message_received','New Message Received','You received a new message from KWIN JHERANE OCAREZA.','web/views/messages/index.php?conversation_id=24',1,'2026-09-19 14:37:37','2026-09-03 02:48:48'),(12,22,'complaint_verified','Complaint Verified','Case 2026-015 is now Verified.','web/views/complaints/case_details.php?id=15',0,NULL,'2026-09-19 14:22:05'),(13,24,'message_received','New Message Received','You received a new message from Ricardo Santos.','web/views/messages/index.php?conversation_id=2',1,'2026-09-19 14:27:30','2026-09-19 14:23:33'),(14,22,'case_status_updated','Case Escalated','Case 2026-015 is now Escalated.','web/views/complaints/case_details.php?id=15',0,NULL,'2026-09-19 16:33:45'),(20,22,'case_status_updated','Case Status Updated','Case 2026-015 is now Under Investigation.','web/views/complaints/case_details.php?id=15',0,NULL,'2026-09-19 16:40:57'),(21,22,'case_resolved','Case Resolved','Case 2026-015 is now Resolved.','web/views/complaints/case_details.php?id=15',0,NULL,'2026-09-19 16:41:54'),(22,22,'message_received','New Message Received','You received a new message from Ricardo Santos.','web/views/messages/index.php?conversation_id=2',0,NULL,'2026-09-19 16:41:54'),(23,5,'message_received','New Message Received','You received a new message from Ricardo Santos.','web/views/messages/index.php?conversation_id=2',0,NULL,'2026-09-19 16:41:54'),(24,22,'case_status_updated','Case Status Updated','Case 2026-015 is now Under Investigation.','web/views/complaints/case_details.php?id=15',0,NULL,'2026-09-19 16:41:57'),(26,22,'case_resolved','Case Resolved','Case 2026-015 is now Resolved.','web/views/complaints/case_details.php?id=15',0,NULL,'2026-09-19 16:44:46'),(27,22,'message_received','New Message Received','You received a new message from Ricardo Santos.','web/views/messages/index.php?conversation_id=2',0,NULL,'2026-09-19 16:44:46'),(28,5,'message_received','New Message Received','You received a new message from Ricardo Santos.','web/views/messages/index.php?conversation_id=2',0,NULL,'2026-09-19 16:44:46'),(29,22,'case_status_updated','Case Status Updated','Case 2026-015 is now Archived.','web/views/complaints/case_details.php?id=15',0,NULL,'2026-09-19 16:45:00'),(30,8,'case_status_updated','Case Escalated','Case 2026-016 is now Escalated.','web/views/complaints/case_details.php?id=31',0,NULL,'2026-09-19 16:45:31'),(31,8,'case_status_updated','Case Status Updated','Case 2026-016 is now Archived.','web/views/complaints/case_details.php?id=31',0,NULL,'2026-09-19 16:45:36'),(32,21,'case_status_updated','Case Escalated','Case 2026-014 is now Escalated.','web/views/complaints/case_details.php?id=14',0,NULL,'2026-09-19 16:46:32'),(33,21,'case_status_updated','Case Status Updated','Case 2026-014 is now Archived.','web/views/complaints/case_details.php?id=14',0,NULL,'2026-09-19 16:46:37'),(34,19,'case_status_updated','Case Status Updated','Case 2026-012 is now Archived.','web/views/complaints/case_details.php?id=12',0,NULL,'2026-09-19 17:20:13'),(35,15,'case_status_updated','Case Status Updated','Case 2026-008 is now Archived.','web/views/complaints/case_details.php?id=8',0,NULL,'2026-09-19 17:49:30'),(36,15,'case_resolved','Case Resolved','Case 2026-008 is now Resolved.','web/views/complaints/case_details.php?id=8',0,NULL,'2026-09-19 18:10:08'),(37,15,'case_status_updated','Case Status Updated','Case 2026-008 is now Archived.','web/views/complaints/case_details.php?id=8',0,NULL,'2026-09-19 18:10:52'),(38,15,'case_resolved','Case Resolved','Case 2026-008 is now Resolved.','web/views/complaints/case_details.php?id=8',0,NULL,'2026-09-19 18:15:13'),(39,25,'complaint_submitted','Complaint Submitted','Your complaint was submitted successfully. Case Number: SDRU-20260922-0001','web/views/complaints/create.php',0,NULL,'2026-09-22 00:23:22'),(41,2,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0001 was submitted by ALEXA ANGELA GABRIEL.','web/views/cases/show.php?id=35',1,'2026-09-22 00:24:33','2026-09-22 00:23:29'),(42,3,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0001 was submitted by ALEXA ANGELA GABRIEL.','web/views/cases/show.php?id=35',0,NULL,'2026-09-22 00:23:32'),(43,4,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0001 was submitted by ALEXA ANGELA GABRIEL.','web/views/cases/show.php?id=35',0,NULL,'2026-09-22 00:23:35'),(44,5,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0001 was submitted by ALEXA ANGELA GABRIEL.','web/views/cases/show.php?id=35',0,NULL,'2026-09-22 00:23:39'),(45,6,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0001 was submitted by ALEXA ANGELA GABRIEL.','web/views/cases/show.php?id=35',0,NULL,'2026-09-22 00:23:42'),(46,7,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0001 was submitted by ALEXA ANGELA GABRIEL.','web/views/cases/show.php?id=35',0,NULL,'2026-09-22 00:23:46'),(47,23,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0001 was submitted by ALEXA ANGELA GABRIEL.','web/views/cases/show.php?id=35',0,NULL,'2026-09-22 00:23:49'),(48,3,'coordinator_assigned','Case Assigned','You were assigned as coordinator for case SDRU-20260922-0001.','web/views/cases/show.php?id=35',1,'2026-09-22 00:26:10','2026-09-22 00:25:27'),(49,2,'case_action_approval_needed','Case Action Approval Needed','A coordinator submitted \"Return\" for case SDRU-20260922-0001 and is waiting for your review.','web/views/cases/show.php?id=35&approval_id=1',1,'2026-09-22 03:12:14','2026-09-22 00:26:28'),(50,9,'complaint_submitted','Complaint Submitted','Your complaint was submitted successfully. Case Number: SDRU-20260922-0002','web/views/complaints/create.php',1,'2026-09-22 08:50:57','2026-09-22 04:08:17'),(52,2,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0002 was submitted by Maria Santos.','web/views/cases/show.php?id=36',1,'2026-09-22 04:10:32','2026-09-22 04:08:28'),(53,3,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0002 was submitted by Maria Santos.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 04:08:33'),(54,4,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0002 was submitted by Maria Santos.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 04:08:39'),(55,5,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0002 was submitted by Maria Santos.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 04:08:44'),(56,6,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0002 was submitted by Maria Santos.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 04:08:49'),(57,7,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0002 was submitted by Maria Santos.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 04:08:53'),(58,23,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0002 was submitted by Maria Santos.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 04:08:56'),(59,5,'coordinator_assigned','Case Assigned','You were assigned as coordinator for case SDRU-20260922-0002.','web/views/cases/show.php?id=36',1,'2026-09-22 04:15:37','2026-09-22 04:12:54'),(60,2,'case_action_approval_needed','Case Action Approval Needed','A coordinator submitted \"Classify\" for case SDRU-20260922-0002 and is waiting for your review.','web/views/cases/show.php?id=36&approval_id=2',1,'2026-09-22 17:08:42','2026-09-22 04:16:28'),(61,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-714E0F580A was released to the respondent(s).','web/views/cases/show.php?id=37',1,'2026-09-22 17:08:42','2026-09-22 06:35:19'),(63,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-714E0F580A was released to the respondent(s).','web/views/cases/show.php?id=37',1,'2026-09-22 17:08:42','2026-09-22 06:35:28'),(65,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-060A33E636 was released to the respondent(s).','web/views/cases/show.php?id=38',1,'2026-09-22 17:08:42','2026-09-22 06:35:47'),(67,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-060A33E636 was released to the respondent(s).','web/views/cases/show.php?id=38',1,'2026-09-22 17:08:42','2026-09-22 06:35:55'),(69,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-287887F390 was released to the respondent(s).','web/views/cases/show.php?id=39',1,'2026-09-22 17:08:42','2026-09-22 06:36:16'),(71,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-287887F390 was released to the respondent(s).','web/views/cases/show.php?id=39',1,'2026-09-22 17:08:42','2026-09-22 06:36:25'),(73,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for SDRU-20260922-0002 was released to the respondent(s).','web/views/cases/show.php?id=36',1,'2026-09-22 17:08:42','2026-09-22 08:30:58'),(75,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',1,'2026-09-22 17:08:42','2026-09-22 08:33:51'),(76,3,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 08:33:55'),(77,4,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 08:33:59'),(78,5,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',1,'2026-09-22 08:34:13','2026-09-22 08:34:04'),(79,6,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 08:34:08'),(80,7,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 08:34:12'),(81,23,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 08:34:17'),(82,2,'case_action_approval_needed','Case Action Approval Needed','A coordinator submitted \"Classify\" for case SDRU-20260922-0002 and is waiting for your review.','web/views/cases/show.php?id=36&approval_id=3',1,'2026-09-22 17:08:42','2026-09-22 08:39:27'),(83,2,'case_action_approval_needed','Case Action Approval Needed','A coordinator submitted \"Classify\" for case SDRU-20260922-0002 and is waiting for your review.','web/views/cases/show.php?id=36&approval_id=4',1,'2026-09-22 17:08:42','2026-09-22 08:49:37'),(85,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',1,'2026-09-22 17:08:42','2026-09-22 08:51:25'),(86,3,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 08:51:30'),(87,4,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 08:51:34'),(88,5,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 08:51:38'),(89,6,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 08:51:43'),(90,7,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 08:51:47'),(91,23,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',0,NULL,'2026-09-22 08:51:52'),(92,2,'case_action_approval_needed','Case Action Approval Needed','A coordinator submitted \"Classify\" for case SDRU-20260922-0002 and is waiting for your review.','web/views/cases/show.php?id=36&approval_id=5',1,'2026-09-22 17:08:42','2026-09-22 09:21:25'),(93,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for SDRU-20260922-0002 was released to the respondent(s).','web/views/cases/show.php?id=36',1,'2026-09-22 17:22:51','2026-09-22 17:22:36'),(95,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for SDRU-20260922-0002 was released to the respondent(s).','web/views/cases/show.php?id=36',1,'2026-09-22 18:22:54','2026-09-22 18:06:06'),(97,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for SDRU-20260922-0002 was released to the respondent(s).','web/views/cases/show.php?id=36',1,'2026-09-22 18:22:54','2026-09-22 18:17:25'),(99,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for SDRU-20260922-0002 was released to the respondent(s).','web/views/cases/show.php?id=36',1,'2026-09-22 20:08:07','2026-09-22 20:07:33'),(101,5,'coordinator_assigned','Case Assigned','You were assigned as coordinator for case SDRU-20260922-0001.','web/views/cases/show.php?id=35',0,NULL,'2026-09-22 20:23:27'),(102,3,'coordinator_assigned','Case Assigned','You were assigned as coordinator for case SDRU-20260922-0001.','web/views/cases/show.php?id=35',0,NULL,'2026-09-22 20:23:42'),(103,25,'case_status_updated','Case Status Updated','Case SDRU-20260922-0001 is now Returned for Revision.','web/views/complaints/case_details.php?id=35',0,NULL,'2026-09-22 20:25:49'),(104,3,'case_action_approved','Case Action Approved','Your requested action \"Return\" for case SDRU-20260922-0001 was approved and executed.','web/views/cases/show.php?id=35',0,NULL,'2026-09-22 20:25:55'),(105,5,'coordinator_assigned','Case Assigned','You were assigned as coordinator for case SDRU-20260922-0001.','web/views/cases/show.php?id=35',0,NULL,'2026-09-22 20:49:36'),(106,24,'case_respondent_linked','Linked as Respondent on 2026-013','Your SICMS account has been automatically linked to case 2026-013 as the respondent. You can now view the case and submit a response.','web/views/respondent/case_show.php?id=13',1,'2026-09-22 22:02:35','2026-09-22 21:11:07'),(107,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for 2026-013 was released to the respondent(s).','web/views/cases/show.php?id=13',1,'2026-09-22 22:01:43','2026-09-22 22:01:26'),(108,24,'respondent_case_released','Case Forwarded to You','The SDRU has forwarded case 2026-013 to you as a respondent. You may now review the permitted details and file your counter-statement.','web/views/respondent/case_show.php?id=13',1,'2026-09-22 22:02:22','2026-09-22 22:01:30'),(109,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for 2026-013 was released to the respondent(s).','web/views/cases/show.php?id=13',1,'2026-09-22 22:09:58','2026-09-22 22:09:38'),(110,24,'respondent_case_released','Case Forwarded to You','The SDRU has forwarded case 2026-013 to you as a respondent. You may now review the permitted details and file your counter-statement.','web/views/respondent/case_show.php?id=13',1,'2026-09-22 22:10:26','2026-09-22 22:09:41'),(111,24,'case_respondent_linked','Linked as Respondent on 2026-011','Your SICMS account has been automatically linked to case 2026-011 as the respondent. You can now view the case and submit a response.','web/views/respondent/case_show.php?id=11',1,'2026-09-22 23:35:00','2026-09-22 22:11:15'),(112,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for 2026-011 was released to the respondent(s).','web/views/cases/show.php?id=11',1,'2026-09-22 22:21:48','2026-09-22 22:11:38'),(113,24,'respondent_case_released','Case Forwarded to You','The SDRU has forwarded case 2026-011 to you as a respondent. You may now review the permitted details and file your counter-statement.','web/views/respondent/case_show.php?id=11',1,'2026-09-22 23:35:00','2026-09-22 22:11:41'),(114,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',1,'2026-09-23 01:54:29','2026-09-23 01:54:10'),(115,3,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',0,NULL,'2026-09-23 01:54:14'),(116,4,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',0,NULL,'2026-09-23 01:54:18'),(117,5,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',0,NULL,'2026-09-23 01:54:22'),(118,6,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',0,NULL,'2026-09-23 01:54:25'),(119,7,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',0,NULL,'2026-09-23 01:54:29'),(120,23,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',0,NULL,'2026-09-23 01:54:34'),(121,31,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',1,'2026-09-23 01:56:59','2026-09-23 01:54:38'),(122,24,'counter_revision_requested','Counter-Statement Revision Requested','Your counter-statement for 2026-013 was returned for revision. Please edit and resubmit it.','web/views/respondent/case_show.php?id=13',1,'2026-09-23 01:54:52','2026-09-23 01:54:40'),(123,3,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',0,NULL,'2026-09-23 02:00:07'),(124,24,'counter_revision_requested','Counter-Statement Revision Requested','Your counter-statement for 2026-013 was returned for revision. Please edit and resubmit it.','web/views/respondent/case_show.php?id=13',1,'2026-09-23 02:02:31','2026-09-23 02:02:25'),(125,3,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',0,NULL,'2026-09-23 02:03:08'),(126,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',1,'2026-09-23 02:29:15','2026-09-23 02:03:12'),(127,24,'counter_revision_requested','Counter-Statement Revision Requested','Your counter-statement for 2026-013 was returned for revision. Please edit and resubmit it.','web/views/respondent/case_show.php?id=13',1,'2026-09-23 02:28:47','2026-09-23 02:07:10'),(128,3,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',0,NULL,'2026-09-23 02:07:31'),(129,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',1,'2026-09-23 02:29:15','2026-09-23 02:07:36'),(130,24,'counter_revision_requested','Counter-Statement Revision Requested','Your counter-statement for 2026-013 was returned for revision. Please edit and resubmit it.','web/views/respondent/case_show.php?id=13',0,NULL,'2026-09-23 02:29:26'),(131,3,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',0,NULL,'2026-09-23 02:30:18'),(132,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',1,'2026-09-23 02:41:40','2026-09-23 02:30:21');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reformation_records`
--

DROP TABLE IF EXISTS `reformation_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reformation_records` (
  `reformation_record_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `coordinator_account_id` int(10) unsigned NOT NULL,
  `activity` text NOT NULL,
  `progress_date` date NOT NULL,
  `progress_status` varchar(50) NOT NULL,
  `remarks` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reformation_record_id`),
  KEY `idx_reformation_records_complaint` (`complaint_id`),
  KEY `idx_reformation_records_coordinator` (`coordinator_account_id`),
  CONSTRAINT `fk_reformation_records_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reformation_records_coordinator` FOREIGN KEY (`coordinator_account_id`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reformation_records`
--

LOCK TABLES `reformation_records` WRITE;
/*!40000 ALTER TABLE `reformation_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `reformation_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reformation_reports`
--

DROP TABLE IF EXISTS `reformation_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reformation_reports` (
  `report_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int(10) unsigned NOT NULL,
  `coordinator_account_id` int(10) unsigned NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `mime_type` varchar(120) NOT NULL,
  `file_size` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `idx_reformation_reports_complaint` (`complaint_id`),
  KEY `idx_reformation_reports_coordinator` (`coordinator_account_id`),
  CONSTRAINT `fk_reformation_reports_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reformation_reports_coordinator` FOREIGN KEY (`coordinator_account_id`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reformation_reports`
--

LOCK TABLES `reformation_reports` WRITE;
/*!40000 ALTER TABLE `reformation_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `reformation_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_google_connections`
--

DROP TABLE IF EXISTS `user_google_connections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_google_connections` (
  `account_id` int(10) unsigned NOT NULL,
  `refresh_token` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_google_connections`
--

LOCK TABLES `user_google_connections` WRITE;
/*!40000 ALTER TABLE `user_google_connections` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_google_connections` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-23  2:51:27
