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
-- Current Database: `sicms`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sicms` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `sicms`;

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
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `uq_accounts_email` (`email`),
  KEY `idx_accounts_role` (`role`),
  KEY `idx_accounts_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,'System','Administrator','admin@sicms.local','$2y$10$ChGPw1PJZIQ6FDv/OnXdHOgemvVdJHgJq82JGoca508dR7RTrc6WO',NULL,'super-admin','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:25:52','','',NULL,''),(2,'Ricardo','Santos','ricardo.santos@sicms.local','$2y$10$kRqeDblG9N/GiGK9SdZeKegxDC72mZ85uQ1AhNpXraKpOZ1LtQqjC',NULL,'head-of-sdru','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:25:52','','',NULL,''),(3,'Maria','Cruz','maria.cruz@sicms.local','$2y$10$UKA/3LfF8SCLMR1z4c9QQOXN93UGHrc6ZvinlVXpwWtkavWKMBla.',NULL,'coordinator','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(4,'Juan','Dela Pena','juan.delapena@sicms.local','$2y$10$UKA/3LfF8SCLMR1z4c9QQOXN93UGHrc6ZvinlVXpwWtkavWKMBla.',NULL,'coordinator','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(5,'Ana','Bautista','ana.bautista@sicms.local','$2y$10$UKA/3LfF8SCLMR1z4c9QQOXN93UGHrc6ZvinlVXpwWtkavWKMBla.',NULL,'coordinator','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(6,'Pedro','Reyes','pedro.reyes@sicms.local','$2y$10$klOygz/8utMnfFnPZ/usLu535PzlnKhPUkEYSpvnTn9AP4OFGGdZq',NULL,'sdru-staff','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:59:43','','',NULL,''),(7,'Carmen','Garcia','carmen.garcia@sicms.local','$2y$10$klOygz/8utMnfFnPZ/usLu535PzlnKhPUkEYSpvnTn9AP4OFGGdZq',NULL,'sdru-staff','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:59:43','','',NULL,''),(8,'Juan','Dela Cruz','juan.delacruz@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(9,'Maria','Santos','maria.santos@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(10,'Pedro','Garcia','pedro.garcia@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(11,'Ana','Reyes','ana.reyes@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(12,'Carmen','Mendoza','carmen.mendoza@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(13,'Luis','Bautista','luis.bautista@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(14,'Rosa','Cruz','rosa.cruz@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(15,'Miguel','Rivera','miguel.rivera@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(16,'Elena','Torres','elena.torres@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(17,'Jose','Flores','jose.flores@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(18,'Patricia','Gomez','patricia.gomez@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(19,'Daniel','Lopez','daniel.lopez@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(20,'Marcela','Diaz','marcela.diaz@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(21,'Francisco','Ramos','francisco.ramos@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(22,'Isabela','Morales','isabela.morales@clsu2.edu.ph','$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',NULL,'student','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-01 22:55:09','','',NULL,''),(23,'Pedro','Reyes','pedro1.reyes@sicms.local','$2y$10$4oHf7SzpAzSI45B/n4F6Ju7Ueyzc3TjegMLxM.f9BKmJsWQ.yvEOO',NULL,'sdru-staff','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:57:41','2026-09-01 22:57:41','','',NULL,''),(24,'KWIN JHERANE','OCAREZA','kwinjherane.ocareza@clsu2.edu.ph','$2y$10$zZpVczLnM3iUTZ/MUSEczep1igSLuE.K9m9PjgH8ZliysnIN/xOey','google','student','active',NULL,'23-2620','College of Engineering','Bachelor of Science in Information Technology (BSIT)','4-2','2026-09-02 04:17:03','2026-09-02 04:20:04','09544834569','Female',NULL,'Sto. Nino 2nd, San Jose City, Nueva Ecija\r\nZone 6');
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
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:26:47'),(2,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:48:50'),(3,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:53:30'),(4,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:55:22'),(5,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:55:46'),(6,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:56:28'),(7,23,'Pedro Reyes','sdru-staff','User Creation','User account created for pedro1.reyes@sicms.local.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:57:41'),(8,2,'Ricardo Santos','head-of-sdru','User Creation','Created SDRU Staff account for pedro1.reyes@sicms.local.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 22:57:41'),(9,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:01:11'),(10,23,'Pedro Reyes','sdru-staff','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:01:18'),(11,23,'Pedro Reyes','sdru-staff','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:04:32'),(12,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:04:54'),(13,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:19:04'),(14,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:19:37'),(15,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Elena Torres.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:14'),(16,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Jose Flores.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:18'),(17,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Ana Bautista.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:49'),(18,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Ana Bautista.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:50'),(19,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:52'),(20,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:20:59'),(21,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:21:02'),(22,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:23:47'),(23,3,'Maria Cruz','coordinator','Screenshot Attempt','PrintScreen key detected while viewing Case Management.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:32:00'),(24,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Marcela Diaz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:48:01'),(25,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Juan Dela Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:49:15'),(26,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:53:07'),(27,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:53:12'),(28,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:54:36'),(29,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Rosa Cruz.',NULL,'','2026-09-01 23:56:12'),(30,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Rosa Cruz.',NULL,'','2026-09-01 23:56:22'),(31,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:58:27'),(32,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Ana Bautista.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:58:30'),(33,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Marcela Diaz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:58:34'),(34,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Rosa Cruz.',NULL,'','2026-09-01 23:59:20'),(35,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:59:48'),(36,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with Juan Dela Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 23:59:56'),(37,3,'Maria Cruz','coordinator','Messages Sent','Sent a direct message to Rosa Cruz.',NULL,'','2026-09-02 00:02:48'),(38,3,'Maria Cruz','coordinator','Messages Sent','Sent a direct message to Juan Dela Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:04:04'),(39,3,'Maria Cruz','coordinator','Messages Sent','Sent a direct message to System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:04:08'),(40,3,'Maria Cruz','coordinator','Messages Deleted','Deleted their copy of the conversation with Juan Dela Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:04:14'),(41,3,'Maria Cruz','coordinator','Conversations Started','Started a conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:13'),(42,3,'Maria Cruz','coordinator','Messages Sent','Sent a direct message to System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:21'),(43,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:25'),(44,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:34'),(45,2,'Ricardo Santos','head-of-sdru','Conversations Started','Started a conversation with Maria Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:42'),(46,2,'Ricardo Santos','head-of-sdru','Messages Sent','Sent a direct message to Maria Cruz.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:44'),(47,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:46'),(48,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:50'),(49,3,'Maria Cruz','coordinator','Messages Deleted','Deleted their copy of the conversation with System Administrator.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:06:57'),(50,3,'Maria Cruz','coordinator','Case Verification','Verified complaint 2026-013.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:19:34'),(51,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:28:59'),(52,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:06'),(53,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:36'),(54,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:40'),(55,3,'Maria Cruz','coordinator','Messages Sent','Sent a direct message to Ricardo Santos.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:46'),(56,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:48'),(57,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:29:51'),(58,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-02 00:30:09'),(59,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 02:43:46'),(60,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Case Details.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 03:40:41'),(61,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Case Details.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 03:41:54'),(62,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Case Details.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 03:44:26'),(63,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Users.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 03:53:49'),(64,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Chat & Messaging.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 03:57:34'),(65,2,'Ricardo Santos','head-of-sdru','Legacy Entry Created','Added SDRU in-charge Ricardo Santos to the legacy page.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:01:16'),(66,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Dashboard.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:01:40'),(67,2,'Ricardo Santos','head-of-sdru','Screenshot Attempt','PrintScreen key detected while viewing Hearings.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:11:48'),(68,2,'Ricardo Santos','head-of-sdru','Hearing Updates','Marked hearing for 2026-016 as completed.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:12:16'),(69,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:12:40'),(70,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:12:53'),(71,3,'Maria Cruz','coordinator','Screenshot Attempt','PrintScreen key detected while viewing Case Details.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:14:52'),(72,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:15:13'),(73,6,'Pedro Reyes','sdru-staff','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:15:40'),(74,6,'Pedro Reyes','sdru-staff','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:16:44'),(75,24,'KWIN JHERANE OCAREZA','student','Screenshot Attempt','PrintScreen key detected while viewing Dashboard.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:17:06'),(76,24,'KWIN JHERANE OCAREZA','student','Screenshot Attempt','PrintScreen key detected while viewing Submit Complaint.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-02 04:21:00'),(77,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-03 02:48:21'),(78,2,'Ricardo Santos','head-of-sdru','Messages Sent','Sent a direct message to KWIN JHERANE OCAREZA.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-03 02:48:29'),(79,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-03 02:48:33'),(80,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-17 15:19:29'),(81,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-17 16:26:10'),(82,3,'Maria Cruz','coordinator','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-17 16:26:36'),(83,3,'Maria Cruz','coordinator','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-17 16:33:00'),(84,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-17 16:33:22'),(85,2,'Ricardo Santos','head-of-sdru','User Login','User logged in successfully.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 13:55:02'),(86,2,'Ricardo Santos','head-of-sdru','Case Verification','Verified complaint 2026-015.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 14:22:05'),(87,2,'Ricardo Santos','head-of-sdru','Messages Sent','Sent a direct message to KWIN JHERANE OCAREZA.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-19 14:23:33');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
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
  `created_by_account_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `idx_history_complaint` (`complaint_id`),
  KEY `idx_history_created_by` (`created_by_account_id`),
  KEY `idx_history_coordinator` (`assigned_coordinator_account_id`),
  CONSTRAINT `fk_history_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_coordinator` FOREIGN KEY (`assigned_coordinator_account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_history_creator` FOREIGN KEY (`created_by_account_id`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_history`
--

LOCK TABLES `case_history` WRITE;
/*!40000 ALTER TABLE `case_history` DISABLE KEYS */;
INSERT INTO `case_history` VALUES (1,13,'Verified Complaint','Submitted','Verified','Okay. Please wait for another update.',NULL,NULL,3,'2026-09-02 00:19:34'),(2,31,'Complaint Submitted',NULL,'Submitted','Complaint filed via online submission by the complainant.',NULL,NULL,8,'2026-09-01 10:00:00'),(3,31,'Complaint Verified','Submitted','Verified','Complaint reviewed and verified by SDRU staff. All information confirmed accurate.',NULL,NULL,6,'2026-09-01 14:00:00'),(4,31,'Coordinator Assigned',NULL,NULL,'Case assigned to Coordinator Maria Cruz for investigation and mediation.',NULL,NULL,6,'2026-09-02 09:00:00'),(5,31,'Hearing Scheduled',NULL,NULL,'Initial hearing scheduled for September 15, 2026 at 10:00 AM at SDRU Conference Room 301.',NULL,NULL,3,'2026-09-02 09:30:00'),(6,15,'Verified Complaint','Submitted','Verified','',NULL,NULL,2,'2026-09-19 14:22:05');
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_messages`
--

LOCK TABLES `case_messages` WRITE;
/*!40000 ALTER TABLE `case_messages` DISABLE KEYS */;
INSERT INTO `case_messages` VALUES (10,0,3,1,'HI',NULL,0,NULL,'2026-09-02 00:06:21','2026-09-02 00:06:21'),(11,0,2,3,'Hi',NULL,1,'2026-09-02 00:06:52','2026-09-02 00:06:44','2026-09-02 00:06:52'),(12,0,3,2,'hi',NULL,1,'2026-09-02 00:30:02','2026-09-02 00:29:46','2026-09-02 00:30:02'),(13,0,24,2,'hi',NULL,1,'2026-09-03 02:48:24','2026-09-02 04:18:12','2026-09-03 02:48:24'),(14,0,2,24,'HI',NULL,1,'2026-09-03 02:48:42','2026-09-03 02:48:28','2026-09-03 02:48:42'),(15,0,24,2,'HI',NULL,1,'2026-09-17 16:37:15','2026-09-03 02:48:48','2026-09-17 16:37:15'),(16,0,2,24,'hi',NULL,1,'2026-09-19 14:23:35','2026-09-19 14:23:33','2026-09-19 14:23:35');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_updates`
--

LOCK TABLES `case_updates` WRITE;
/*!40000 ALTER TABLE `case_updates` DISABLE KEYS */;
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
  CONSTRAINT `fk_evidence_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evidence_update` FOREIGN KEY (`update_id`) REFERENCES `case_updates` (`update_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaint_evidence`
--

LOCK TABLES `complaint_evidence` WRITE;
/*!40000 ALTER TABLE `complaint_evidence` DISABLE KEYS */;
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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`respondent_id`),
  KEY `idx_respondents_complaint` (`complaint_id`),
  CONSTRAINT `fk_respondents_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaint_respondents`
--

LOCK TABLES `complaint_respondents` WRITE;
/*!40000 ALTER TABLE `complaint_respondents` DISABLE KEYS */;
INSERT INTO `complaint_respondents` VALUES (1,31,'Student','Maria Santos','Female','2024-00002',NULL,'College of Engineering',NULL,'BS Computer Science | B',NULL,NULL,'maria.santos@clsu2.edu.ph','Respondent allegedly initiated the verbal confrontation by making disparaging remarks about the complainant\'s academic performance.','2026-09-02 03:34:39');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaint_witnesses`
--

LOCK TABLES `complaint_witnesses` WRITE;
/*!40000 ALTER TABLE `complaint_witnesses` DISABLE KEYS */;
INSERT INTO `complaint_witnesses` VALUES (1,31,'Student','Pedro Garcia','Male','2022-00003','pedro.garcia@clsu2.edu.ph','I was sitting at a nearby table when the argument started. Maria approached Juan first and said something about his grades. Juan responded angrily and things escalated. Both parties were shouting within minutes.',NULL,NULL,'College of Agriculture',NULL,NULL,NULL,NULL,'4th Year','BS Agriculture | A','2026-09-02 03:34:39');
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
  `status` varchar(50) NOT NULL DEFAULT 'Submitted',
  `assigned_coordinator_account_id` int(10) unsigned DEFAULT NULL,
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
  CONSTRAINT `fk_complaints_coordinator` FOREIGN KEY (`assigned_coordinator_account_id`) REFERENCES `accounts` (`account_id`),
  CONSTRAINT `fk_complaints_submitter` FOREIGN KEY (`submitted_by_account_id`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaints`
--

LOCK TABLES `complaints` WRITE;
/*!40000 ALTER TABLE `complaints` DISABLE KEYS */;
INSERT INTO `complaints` VALUES (1,'2026-001','Plagiarism in Research Paper',8,'Juan Dela Cruz','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00001','juan.delacruz@clsu2.edu.ph',NULL,'College of Science','BS Biology','3rd Year','A',NULL,'Academic Dishonesty','2026-01-15 09:30:00','Room 201, Science Building','Submitted a research paper with significant portions copied from published sources without proper citation.','Submitted',3,'2026-01-15 10:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,'2026-002','Cheating During Midterm Examination',9,'Maria Santos','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2024-00002','maria.santos@clsu2.edu.ph',NULL,'College of Engineering','BS Computer Science','2nd Year','B',NULL,'Academic Dishonesty','2026-01-28 10:00:00','Room 105, Engineering Building','Caught using unauthorized notes during the midterm examination in Data Structures.','Verified',4,'2026-01-28 11:30:00','2026-09-01 22:25:52','2026-09-01 22:51:15','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,'2026-003','Unauthorized Collaboration on Assignment',10,'Pedro Garcia','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-00003','pedro.garcia@clsu2.edu.ph',NULL,'College of Agriculture','BS Agriculture','4th Year','A',NULL,'Academic Dishonesty','2026-02-10 14:00:00','Agriculture Lab 3','Submitted an assignment nearly identical to another students work, indicating unauthorized collaboration.','Submitted',5,'2026-02-10 15:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,'2026-004','Fabrication of Laboratory Results',11,'Ana Reyes','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00004','ana.reyes@clsu2.edu.ph',NULL,'College of Science','BS Chemistry','3rd Year','B',NULL,'Academic Dishonesty','2026-02-22 08:00:00','Chemistry Laboratory 2','Lab report contained fabricated data points that were physically impossible to obtain during the experiment.','Verified',3,'2026-02-22 09:00:00','2026-09-01 22:25:52','2026-09-01 22:51:15','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,'2026-005','Disruptive Behavior in Class',12,'Carmen Mendoza','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2025-00005','carmen.mendoza@clsu2.edu.ph',NULL,'College of Education','BS Education','1st Year','A',NULL,'Behavioral Misconduct','2026-03-05 11:00:00','Room 302, Education Building','Repeatedly disrupted class proceedings by talking loudly and using phone despite multiple warnings.','Submitted',4,'2026-03-05 12:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,'2026-006','Academic Dishonesty in Online Quiz',13,'Luis Bautista','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2024-00006','luis.bautista@clsu2.edu.ph',NULL,'College of Business','BS Business Administration','2nd Year','A',NULL,'Academic Dishonesty','2026-03-18 09:00:00','Online - Canvas LMS','System logs show suspicious activity during online quiz including multiple tab switches and external website access.','Verified',5,'2026-03-18 10:30:00','2026-09-01 22:25:52','2026-09-01 22:51:15','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,'2026-007','Vandalism of School Property',14,'Rosa Cruz','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00007','rosa.cruz@clsu2.edu.ph',NULL,'College of Engineering','BS Mechanical Engineering','3rd Year','A',NULL,'Property Damage','2026-04-02 16:00:00','Mechanical Engineering Workshop','Deliberately damaged laboratory equipment worth approximately Php 15,000 during an unsupervised session.','Verified',3,'2026-04-02 17:00:00','2026-09-01 22:25:52','2026-09-01 22:51:15','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,'2026-008','Harassment of Classmate',15,'Miguel Rivera','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-00008','miguel.rivera@clsu2.edu.ph',NULL,'College of Arts','AB Psychology','4th Year','A',NULL,'Harassment','2026-04-15 13:00:00','College of Arts Commons','Made threatening remarks and intimidating gestures toward a classmate during a group discussion.','Resolved',4,'2026-04-15 14:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,'2026-009','Theft of Personal Belongings',16,'Elena Torres','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2024-00009','elena.torres@clsu2.edu.ph',NULL,'College of Science','BS Biology','2nd Year','B',NULL,'Theft','2026-05-01 07:30:00','Science Building Locker Room','Personal laptop was stolen from the locker room. Security footage is being reviewed.','Submitted',5,'2026-05-01 08:30:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,'2026-010','Violation of Dress Code Policy',17,'Jose Flores','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2025-00010','jose.flores@clsu2.edu.ph',NULL,'College of Agriculture','BS Agriculture','1st Year','B',NULL,'Policy Violation','2026-05-14 08:00:00','College of Agriculture Main Hall','Repeatedly violated the university dress code policy despite previous verbal warnings from faculty.','Verified',3,'2026-05-14 09:00:00','2026-09-01 22:25:52','2026-09-01 22:51:15','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,'2026-011','Cyberbullying on Social Media',18,'Patricia Gomez','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00011','patricia.gomez@clsu2.edu.ph',NULL,'College of Education','BS Education','3rd Year','A',NULL,'Harassment','2026-06-03 20:00:00','Online - Social Media','Posted derogatory and humiliating content about a fellow student on social media platforms.','Verified',4,'2026-06-04 08:00:00','2026-09-01 22:25:52','2026-09-01 22:51:15','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(12,'2026-012','Forgery of Academic Documents',19,'Daniel Lopez','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-00012','daniel.lopez@clsu2.edu.ph',NULL,'College of Business','BS Business Administration','4th Year','A',NULL,'Fraud','2026-06-20 10:00:00','Registrar Office','Submitted a falsified transcript of records with altered grades for graduate school admission.','Resolved',5,'2026-06-20 11:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(13,'2026-013','Attendance Fraud',20,'Marcela Diaz','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2024-00013','marcela.diaz@clsu2.edu.ph',NULL,'College of Science','BS Chemistry','2nd Year','A',NULL,'Academic Dishonesty','2026-07-10 07:00:00','Chemistry Lecture Hall','Used another students ID to mark attendance on multiple occasions throughout the semester.','Verified',3,'2026-07-10 08:00:00','2026-09-01 22:25:52','2026-09-02 00:19:34','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(14,'2026-014','Destruction of Library Materials',21,'Francisco Ramos','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00014','francisco.ramos@clsu2.edu.ph',NULL,'College of Arts','AB English','3rd Year','A',NULL,'Property Damage','2026-08-05 14:00:00','University Library','Deliberately tore pages from reference books and damaged library equipment during study session.','Submitted',4,'2026-08-05 15:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(15,'2026-015','Verbal Abuse of Faculty Member',22,'Isabela Morales','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2025-00015','isabela.morales@clsu2.edu.ph',NULL,'College of Engineering','BS Computer Science','1st Year','A',NULL,'Behavioral Misconduct','2026-09-01 11:00:00','Room 108, Engineering Building','Used offensive and disrespectful language toward a faculty member during a grade consultation.','Verified',5,'2026-09-01 12:00:00','2026-09-01 22:25:52','2026-09-19 14:22:05','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(16,'2024-001','Legacy: Cheating in Final Examination',1,'Rica Aquino','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2020-01001','rica.aquino@clsu2.edu.ph',NULL,'College of Science','BS Biology','4th Year','A',NULL,'Academic Dishonesty','2024-01-15 08:00:00','Science Auditorium','Caught copying answers from a neighbor during the final examination in Genetics.','Resolved',NULL,'2024-01-15 09:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2024-01-15','Suspended for one semester','Student served suspension for one semester.','2024-02-28',NULL,'Legacy System','Suspended for one semester effective second semester AY 2023-2024.'),(17,'2024-002','Legacy: Plagiarism in Undergraduate Thesis',1,'Ricardo Vergara','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2020-01002','ricardo.vergara@clsu2.edu.ph',NULL,'College of Engineering','BS Computer Science','4th Year','A',NULL,'Academic Dishonesty','2024-02-20 10:00:00','Engineering Faculty Office','Thesis document found to contain large sections copied from previously published theses without attribution.','Resolved',NULL,'2024-02-20 11:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2024-02-20','Probation for one academic year','Placed on academic probation for the remainder of the program.','2024-04-10',NULL,'Legacy System','Probation for one academic year with mandatory ethics seminar.'),(18,'2024-003','Legacy: Unauthorized Use of AI in Essay',1,'Marissa Arceo','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-02003','marissa.arceo@clsu2.edu.ph',NULL,'College of Business','BS Business Administration','3rd Year','B',NULL,'Academic Dishonesty','2024-03-10 14:00:00','Online Submission','Essay submitted was determined to be entirely generated by artificial intelligence tools.','Resolved',NULL,'2024-03-10 15:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2024-03-10','Written warning','Issued formal written warning and required to resubmit original work.','2024-03-25',NULL,'Legacy System','Written warning with requirement to complete academic integrity workshop.'),(19,'2024-004','Legacy: Destruction of Laboratory Equipment',1,'Enrico Salazar','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-02004','enrico.salazar@clsu2.edu.ph',NULL,'College of Science','BS Chemistry','3rd Year','A',NULL,'Property Damage','2024-04-05 09:00:00','Chemistry Laboratory 1','Intentionally broke expensive laboratory glassware and equipment during a practical exam.','Resolved',NULL,'2024-04-05 10:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2024-04-05','Dismissed from program','Removed from the BS Chemistry program and transferred to general studies.','2024-05-15',NULL,'Legacy System','Dismissed from BS Chemistry program; allowed to enroll in other programs.'),(20,'2024-005','Legacy: Bullying Incident',1,'Teresa Magsaysay','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-03005','teresa.magsaysay@clsu2.edu.ph',NULL,'College of Education','BS Education','2nd Year','A',NULL,'Harassment','2024-05-18 11:00:00','Education Building Corridor','Physically and verbally bullied a younger student on multiple occasions.','Resolved',NULL,'2024-05-18 12:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2024-05-18','Probation for one semester','Placed on behavioral probation with mandatory counseling sessions.','2024-06-20',NULL,'Legacy System','Probation for one semester with mandatory counseling.'),(21,'2024-006','Legacy: Theft from Dormitory',1,'Alfredo Manalo','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-04006','alfredo.manalo@clsu2.edu.ph',NULL,'College of Agriculture','BS Agriculture','3rd Year','B',NULL,'Theft','2024-06-22 22:00:00','University Dormitory Room 412','Stole personal belongings including electronic devices from roommates desk.','Resolved',NULL,'2024-06-23 08:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2024-06-22','Suspended for two semesters','Suspended from university for two semesters with conditions for readmission.','2024-08-10',NULL,'Legacy System','Suspended for two semesters; must complete community service before readmission.'),(22,'2024-007','Legacy: Academic Fraud',1,'Violeta Sevilla','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2020-05007','violeta.sevilla@clsu2.edu.ph',NULL,'College of Arts','AB Psychology','4th Year','A',NULL,'Fraud','2024-08-10 09:00:00','Registrar Office','Submitted fraudulent documents claiming completion of required internship hours.','Resolved',NULL,'2024-08-10 10:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2024-08-10','Dismissed from university','Permanently dismissed from the university for systematic academic fraud.','2024-09-15',NULL,'Legacy System','Permanent dismissal from the university.'),(23,'2024-008','Legacy: Vandalism of Campus Facilities',1,'Fernando Lacson','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-06008','fernando.lacson@clsu2.edu.ph',NULL,'College of Engineering','BS Mechanical Engineering','2nd Year','A',NULL,'Property Damage','2024-09-15 17:00:00','Engineering Building Restroom','Graffiti and deliberate damage to fixtures in the engineering building restroom.','Resolved',NULL,'2024-09-16 08:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2024-09-15','Community service and written warning','Required 40 hours of community service and issued formal warning.','2024-10-20',NULL,'Legacy System','40 hours community service and written warning.'),(24,'2025-001','Legacy: Sexual Harassment Complaint',1,'Gloriosa Pascual','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-07009','gloriosa.pascual@clsu2.edu.ph',NULL,'College of Science','BS Biology','3rd Year','B',NULL,'Harassment','2025-01-12 10:00:00','Science Building Hallway','Made unwanted advances and inappropriate comments toward a fellow student.','Resolved',NULL,'2025-01-12 11:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2025-01-12','Probation for one academic year','Placed on strict behavioral probation with no-contact order.','2025-02-28',NULL,'Legacy System','Probation for one academic year with mandatory behavioral program.'),(25,'2025-002','Legacy: Examination Leak Involvement',1,'Edgardo Suarez','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2020-08010','edgardo.suarez@clsu2.edu.ph',NULL,'College of Business','BS Business Administration','4th Year','A',NULL,'Fraud','2025-02-28 08:00:00','Business Faculty Office','Distributed confidential examination materials to other students prior to scheduled exam.','Resolved',NULL,'2025-02-28 09:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2025-02-28','Suspended for one semester','Suspended for one semester; barred from honors list.','2025-04-10',NULL,'Legacy System','Suspended for one semester and removed from honors consideration.'),(26,'2025-003','Legacy: Falsification of Academic Records',1,'Margarita Villanueva','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-09011','margarita.villanueva@clsu2.edu.ph',NULL,'College of Education','BS Education','4th Year','A',NULL,'Fraud','2025-03-15 09:00:00','Registrar Office','Altered grades on official transcript using sophisticated forgery techniques.','Resolved',NULL,'2025-03-15 10:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2025-03-15','Dismissed from program','Dismissed from the Education program for record falsification.','2025-04-20',NULL,'Legacy System','Dismissed from BS Education program.'),(27,'2025-004','Legacy: Property Damage in Dormitory',1,'Rogelio Esguerra','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-10012','rogelio.esguerra@clsu2.edu.ph',NULL,'College of Agriculture','BS Agriculture','2nd Year','B',NULL,'Property Damage','2025-04-20 21:00:00','University Dormitory Common Area','Damaged common area furniture and appliances during a party.','Resolved',NULL,'2025-04-21 08:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2025-04-20','Restitution and written warning','Required to pay for damages and issued formal warning.','2025-05-15',NULL,'Legacy System','Full restitution of Php 8,500 and written warning.'),(28,'2025-005','Legacy: Verbal Threat to Fellow Student',1,'Emilia Rendon','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-11013','emilia.rendon@clsu2.edu.ph',NULL,'College of Arts','AB English','3rd Year','B',NULL,'Behavioral Misconduct','2025-05-10 15:00:00','Arts Building Cafeteria','Made repeated verbal threats of physical harm toward another student over a personal dispute.','Resolved',NULL,'2025-05-10 16:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2025-05-10','Probation for one semester','Behavioral probation with mandatory anger management counseling.','2025-06-10',NULL,'Legacy System','Probation for one semester with anger management program.'),(29,'2025-006','Legacy: Plagiarism in Research Essay',1,'Armando Cuatreras','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-12014','armando.cuatreras@clsu2.edu.ph',NULL,'College of Science','BS Chemistry','2nd Year','A',NULL,'Academic Dishonesty','2025-07-05 10:00:00','Science Faculty Office','Submitted a research essay with 78% similarity to published work detected through Turnitin.','Resolved',NULL,'2025-07-05 11:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2025-07-05','Suspended for one semester','Suspended for one semester with mandatory academic integrity seminar.','2025-08-15',NULL,'Legacy System','Suspended for one semester; must complete academic integrity course.'),(30,'2025-007','Legacy: Cheating in Laboratory Examination',1,'Carlota Oliva','Female','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-13015','carlota.oliva@clsu2.edu.ph',NULL,'College of Engineering','BS Computer Science','3rd Year','A',NULL,'Academic Dishonesty','2025-08-18 14:00:00','Computer Laboratory 4','Used hidden notes and a mobile phone during the laboratory examination despite warnings.','Resolved',NULL,'2025-08-18 15:00:00','2026-09-01 22:25:52','2026-09-01 22:25:52','Legacy','2025-08-18','Exonerated','Investigation concluded evidence was insufficient; student exonerated.','2025-09-10',NULL,'Legacy System','Exonerated due to insufficient evidence.'),(31,'2026-016','Verbal Altercation with Fellow Student',8,'Juan Dela Cruz','Male','Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-00001','juan.delacruz@clsu2.edu.ph',NULL,'College of Science','BS Biology','3rd Year','A',NULL,'Behavioral Misconduct','2026-08-28 14:30:00','College of Science Cafeteria','Engaged in a heated verbal altercation with a fellow student during lunch break. The argument escalated to personal insults and threatening language. Multiple witnesses were present and the incident disrupted the peace of the surrounding area.','Verified',3,'2026-09-01 10:00:00','2026-09-02 03:34:39','2026-09-02 03:34:39','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversation_participants`
--

LOCK TABLES `conversation_participants` WRITE;
/*!40000 ALTER TABLE `conversation_participants` DISABLE KEYS */;
INSERT INTO `conversation_participants` VALUES (49,0,3,1,'2026-09-02 00:06:12'),(50,0,1,3,'2026-09-02 00:06:12'),(53,0,2,3,'2026-09-02 00:06:42'),(54,0,3,2,'2026-09-02 00:06:42'),(58,0,24,2,'2026-09-02 04:18:12'),(59,0,2,24,'2026-09-02 04:18:12');
/*!40000 ALTER TABLE `conversation_participants` ENABLE KEYS */;
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
INSERT INTO `hearings` VALUES (1,31,3,'2026-09-15 10:00:00','SDRU Conference Room 301',NULL,NULL,'Both parties and the witness are required to attend. Please bring any supporting evidence or written statements.','Completed','2026-09-02 03:34:39','2026-09-02 04:12:16');
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
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hidden_conversations`
--

LOCK TABLES `hidden_conversations` WRITE;
/*!40000 ALTER TABLE `hidden_conversations` DISABLE KEYS */;
INSERT INTO `hidden_conversations` VALUES (50,0,3,1,'2026-09-02 00:06:57',0),(51,0,1,3,'1000-01-01 00:00:00',1),(54,0,2,3,'1000-01-01 00:00:00',1),(55,0,3,2,'1000-01-01 00:00:00',1),(60,0,24,2,'2026-09-02 04:18:16',1),(61,0,2,24,'1000-01-01 00:00:00',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,14,'message_received','New Message Received','You received a new message from Maria Cruz.','web/views/messages/index.php?conversation_id=3',0,NULL,'2026-09-02 00:02:48'),(2,8,'message_received','New Message Received','You received a new message from Maria Cruz.','web/views/messages/index.php?conversation_id=3',0,NULL,'2026-09-02 00:04:04'),(3,1,'message_received','New Message Received','You received a new message from Maria Cruz.','web/views/messages/index.php?conversation_id=3',0,NULL,'2026-09-02 00:04:08'),(4,1,'message_received','New Message Received','You received a new message from Maria Cruz.','web/views/messages/index.php?conversation_id=3',0,NULL,'2026-09-02 00:06:21'),(5,3,'message_received','New Message Received','You received a new message from Ricardo Santos.','web/views/messages/index.php?conversation_id=2',1,'2026-09-02 00:07:05','2026-09-02 00:06:44'),(6,20,'complaint_verified','Complaint Verified','Case 2026-013 is now Verified.','web/views/complaints/case_details.php?id=13',0,NULL,'2026-09-02 00:19:34'),(7,2,'message_received','New Message Received','You received a new message from Maria Cruz.','web/views/messages/index.php?conversation_id=3',1,'2026-09-02 00:30:02','2026-09-02 00:29:46'),(8,8,'hearing_completed','Hearing Completed','The hearing for case 2026-016 was marked as Completed.','web/views/complaints/case_details.php?id=31',0,NULL,'2026-09-02 04:12:16'),(9,2,'message_received','New Message Received','You received a new message from KWIN JHERANE OCAREZA.','web/views/messages/index.php?conversation_id=24',0,NULL,'2026-09-02 04:18:12'),(10,24,'message_received','New Message Received','You received a new message from Ricardo Santos.','web/views/messages/index.php?conversation_id=2',0,NULL,'2026-09-03 02:48:29'),(11,2,'message_received','New Message Received','You received a new message from KWIN JHERANE OCAREZA.','web/views/messages/index.php?conversation_id=24',0,NULL,'2026-09-03 02:48:48'),(12,22,'complaint_verified','Complaint Verified','Case 2026-015 is now Verified.','web/views/complaints/case_details.php?id=15',0,NULL,'2026-09-19 14:22:05'),(13,24,'message_received','New Message Received','You received a new message from Ricardo Santos.','web/views/messages/index.php?conversation_id=2',0,NULL,'2026-09-19 14:23:33');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
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
-- Dumping routines for database 'sicms'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-19 14:25:45
