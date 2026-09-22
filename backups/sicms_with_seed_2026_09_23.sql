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
INSERT INTO `accounts` VALUES (2,'Ricardo','Santos','ricardo.santos@sicms.local','$2y$10$kRqeDblG9N/GiGK9SdZeKegxDC72mZ85uQ1AhNpXraKpOZ1LtQqjC',NULL,'head-of-sdru','active',NULL,NULL,NULL,NULL,NULL,'2026-09-01 22:25:52','2026-09-23 02:57:24','','',NULL,'',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=216 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (215,2,'Ricardo Santos','head-of-sdru','User Logout','User logged out.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 03:05:47');
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
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_history`
--

LOCK TABLES `case_history` WRITE;
/*!40000 ALTER TABLE `case_history` DISABLE KEYS */;
INSERT INTO `case_history` VALUES (65,40,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2026-01-12 09:15:00'),(66,40,'Case Verification','Submitted','Verified',NULL,NULL,NULL,NULL,2,'2026-01-15 09:15:00'),(67,40,'Case Classification',NULL,'Under Investigation','Classified as Cyberbullying.',NULL,NULL,NULL,2,'2026-01-17 09:15:00'),(68,41,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2026-02-08 10:00:00'),(69,41,'Case Verification','Submitted','Verified',NULL,NULL,NULL,NULL,2,'2026-02-11 10:00:00'),(70,41,'Case Classification',NULL,'Under Investigation','Classified as Physical Assault.',NULL,NULL,NULL,2,'2026-02-13 10:00:00'),(71,42,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2026-03-01 08:30:00'),(72,42,'Case Verification','Submitted','Verified',NULL,NULL,NULL,NULL,2,'2026-03-04 08:30:00'),(73,42,'Case Classification',NULL,'Under Investigation','Classified as Unauthorized Account Access.',NULL,NULL,NULL,2,'2026-03-06 08:30:00'),(74,43,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2026-03-25 14:00:00'),(75,43,'Case Verification','Submitted','Verified',NULL,NULL,NULL,NULL,2,'2026-03-28 14:00:00'),(76,43,'Case Classification',NULL,'Under Investigation','Classified as Plagiarism.',NULL,NULL,NULL,2,'2026-03-30 14:00:00'),(77,44,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2026-04-10 09:00:00'),(78,44,'Case Verification','Submitted','Verified',NULL,NULL,NULL,NULL,2,'2026-04-13 09:00:00'),(79,44,'Case Classification',NULL,'Under Investigation','Classified as Intimidation, Threat and Harassment.',NULL,NULL,NULL,2,'2026-04-15 09:00:00'),(80,45,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2026-05-06 11:30:00'),(81,45,'Case Verification','Submitted','Verified',NULL,NULL,NULL,NULL,2,'2026-05-09 11:30:00'),(82,45,'Case Classification',NULL,'Under Investigation','Classified as Property Damage.',NULL,NULL,NULL,2,'2026-05-11 11:30:00'),(83,46,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2026-06-01 08:45:00'),(84,46,'Case Verification','Submitted','Verified',NULL,NULL,NULL,NULL,2,'2026-06-04 08:45:00'),(85,46,'Case Classification',NULL,'Under Investigation','Classified as Theft.',NULL,NULL,NULL,2,'2026-06-06 08:45:00'),(86,47,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2026-07-03 09:20:00'),(87,47,'Case Verification','Submitted','Verified',NULL,NULL,NULL,NULL,2,'2026-07-06 09:20:00'),(88,47,'Case Classification',NULL,'Under Investigation','Classified as Breach of Confidentiality.',NULL,NULL,NULL,2,'2026-07-08 09:20:00'),(89,48,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2026-08-05 10:30:00'),(90,48,'Case Verification','Submitted','Verified',NULL,NULL,NULL,NULL,2,'2026-08-08 10:30:00'),(91,48,'Case Classification',NULL,'Under Investigation','Classified as Disruptive Behavior.',NULL,NULL,NULL,2,'2026-08-10 10:30:00'),(92,49,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2026-09-01 08:00:00'),(93,49,'Case Verification','Submitted','Verified',NULL,NULL,NULL,NULL,2,'2026-09-04 08:00:00'),(94,49,'Case Classification',NULL,'Under Investigation','Classified as Property Damage.',NULL,NULL,NULL,2,'2026-09-06 08:00:00'),(95,50,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2020-03-04 09:00:00'),(96,50,'Case Resolution','Under Investigation','Resolved','Migrated record marked as resolved per registry ledger.',NULL,NULL,NULL,2,'2020-04-10 15:00:00'),(97,51,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2020-06-20 15:00:00'),(98,51,'Case Resolution','Under Investigation','Resolved','Migrated record marked as resolved per registry ledger.',NULL,NULL,NULL,2,'2020-07-05 15:00:00'),(99,52,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2021-03-19 08:00:00'),(100,52,'Case Resolution','Under Investigation','Resolved','Migrated record marked as resolved per registry ledger.',NULL,NULL,NULL,2,'2021-05-15 15:00:00'),(101,53,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2021-09-11 09:30:00'),(102,53,'Case Resolution','Under Investigation','Resolved','Migrated record marked as resolved per registry ledger.',NULL,NULL,NULL,2,'2021-10-20 15:00:00'),(103,54,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2022-04-13 10:00:00'),(104,54,'Case Resolution','Under Investigation','Resolved','Migrated record marked as resolved per registry ledger.',NULL,NULL,NULL,2,'2022-06-01 15:00:00'),(105,55,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2022-08-15 10:30:00'),(106,55,'Case Resolution','Under Investigation','Resolved','Migrated record marked as resolved per registry ledger.',NULL,NULL,NULL,2,'2022-09-30 15:00:00'),(107,56,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2023-05-06 08:00:00'),(108,56,'Case Resolution','Under Investigation','Resolved','Migrated record marked as resolved per registry ledger.',NULL,NULL,NULL,2,'2023-06-20 15:00:00'),(109,57,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2023-10-11 09:00:00'),(110,57,'Case Resolution','Under Investigation','Resolved','Migrated record marked as resolved per registry ledger.',NULL,NULL,NULL,2,'2023-11-25 15:00:00'),(111,58,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2024-02-26 08:30:00'),(112,58,'Case Resolution','Under Investigation','Resolved','Migrated record marked as resolved per registry ledger.',NULL,NULL,NULL,2,'2024-04-05 15:00:00'),(113,59,'Complaint Submission',NULL,'Submitted',NULL,NULL,NULL,NULL,2,'2024-06-15 09:00:00'),(114,59,'Case Resolution','Under Investigation','Resolved','Migrated record marked as resolved per registry ledger.',NULL,NULL,NULL,2,'2024-07-30 15:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaint_respondents`
--

LOCK TABLES `complaint_respondents` WRITE;
/*!40000 ALTER TABLE `complaint_respondents` DISABLE KEYS */;
INSERT INTO `complaint_respondents` VALUES (9,40,'Student','Kevin Navarro','Male','23-5001',NULL,'College of Engineering',NULL,'BSCE | 4-1',NULL,NULL,'0917123422','Prowling post and mocking photos in the group chat.',19,NULL,'kevin.navarro@clsu2.edu.ph','Residence Hall, College of Engineering, CLSU Campus, Science City of Muñoz, Nueva Ecija','Fourth Year',NULL,NULL,NULL,'2026-01-12 09:15:00'),(10,41,'Student','Ryan Cabrera','Male','23-5002',NULL,'College of Engineering',NULL,'BSIT | 4-2',NULL,NULL,'0917123421','Caught on CCTV during the physical altercation near the canteen.',19,NULL,'ryan.cabrera@clsu2.edu.ph','Residence Hall, College of Engineering, CLSU Campus, Science City of Muñoz, Nueva Ecija','Fourth Year',NULL,NULL,NULL,'2026-02-08 10:00:00'),(11,42,'Student','Jasmine Santos','Female','23-5003',NULL,'College of Business and Accountancy',NULL,'BSAc | 2-2',NULL,NULL,'0917123419','Alleged to have accessed the complainant\'s portal credentials.',20,NULL,'jasmine.santos@clsu2.edu.ph','Residence Hall, College of Business and Accountancy, CLSU Campus, Science City of Muñoz, Nueva Ecija','Second Year',NULL,NULL,NULL,'2026-03-01 08:30:00'),(12,43,'Student','Miguel Castillo','Male','23-5004',NULL,'College of Science',NULL,'BSBio | 4-2',NULL,NULL,'0917123423','Submitted plagiarized research proposal as his own.',20,NULL,'miguel.castillo@clsu2.edu.ph','Residence Hall, College of Science, CLSU Campus, Science City of Muñoz, Nueva Ecija','Fourth Year',NULL,NULL,NULL,'2026-03-25 14:00:00'),(13,44,'Student','Angel Torres','Female','23-5005',NULL,'College of Education',NULL,'BSEd | 3-1',NULL,NULL,'0917123420','Repeated verbal harassment of the complainant in class.',21,NULL,'angel.torres@clsu2.edu.ph','Residence Hall, College of Education, CLSU Campus, Science City of Muñoz, Nueva Ecija','Third Year',NULL,NULL,NULL,'2026-04-10 09:00:00'),(14,45,'Student','Joshua Reyes','Male','23-5006',NULL,'College of Veterinary Science and Medicine',NULL,'DVM | 4-2',NULL,NULL,'0917123421','Last seen near the damaged laboratory equipment.',21,NULL,'joshua.reyes@clsu2.edu.ph','Residence Hall, College of Veterinary Science and Medicine, CLSU Campus, Science City of Muñoz, Nueva Ecija','Fourth Year',NULL,NULL,NULL,'2026-05-06 11:30:00'),(15,46,'Student','Althea Domingo','Female','23-5007',NULL,'College of Hospitality Management',NULL,'BSHM | 2-2',NULL,NULL,'0917123420','Roommate present when the theft was discovered.',22,NULL,'althea.domingo@clsu2.edu.ph','Residence Hall, College of Hospitality Management, CLSU Campus, Science City of Muñoz, Nueva Ecija','Second Year',NULL,NULL,NULL,'2026-06-01 08:45:00'),(16,47,'Student','Marvin Salazar','Male','23-5008',NULL,'College of Agriculture',NULL,'BSA | 3-2',NULL,NULL,'0917123420','Shared grades and personal information without consent.',22,NULL,'marvin.salazar@clsu2.edu.ph','Residence Hall, College of Agriculture, CLSU Campus, Science City of Muñoz, Nueva Ecija','Third Year',NULL,NULL,NULL,'2026-07-03 09:20:00'),(17,48,'Student','Kaye Ocampo','Female','23-5009',NULL,'College of Fisheries',NULL,'BSF | 3-1',NULL,NULL,'0917123420','Disrupted the examination and attempted to copy answers.',23,NULL,'kaye.ocampo@clsu2.edu.ph','Residence Hall, College of Fisheries, CLSU Campus, Science City of Muñoz, Nueva Ecija','Third Year',NULL,NULL,NULL,'2026-08-05 10:30:00'),(18,49,'Student','Brix Manalo','Male','23-5010',NULL,'College of Arts and Social Sciences',NULL,'BASS | 4-2',NULL,NULL,'0917123421','Identified near the vandalized campus wall in security footage.',23,NULL,'brix.manalo@clsu2.edu.ph','Residence Hall, College of Arts and Social Sciences, CLSU Campus, Science City of Muñoz, Nueva Ecija','Fourth Year',NULL,NULL,NULL,'2026-09-01 08:00:00'),(19,50,'Student','Carlos Ilagan','Male','2019-0601',NULL,'College of Science',NULL,'BS Biology | A',NULL,NULL,'09171110301','Migrated legacy entry from the SDRU Registry Ledger 2020.',20,NULL,'carlos.ilagan@legacy.sicms','Residence Hall, College of Science, CLSU Campus, Science City of Muñoz, Nueva Ecija','3rd Year',NULL,NULL,NULL,'2020-03-04 09:00:00'),(20,51,'Student','Sheena Lozano','Female','2019-0602',NULL,'College of Engineering',NULL,'BS Civil Engineering | B',NULL,NULL,'09171110302','Migrated legacy entry from the SDRU Registry Ledger 2020.',19,NULL,'sheena.lozano@legacy.sicms','Residence Hall, College of Engineering, CLSU Campus, Science City of Muñoz, Nueva Ecija','2nd Year',NULL,NULL,NULL,'2020-06-20 15:00:00'),(21,52,'Student','Vincent Mariano','Male','2020-0701',NULL,'College of Science',NULL,'BS Chemistry | A',NULL,NULL,'09171110303','Migrated legacy entry from the SDRU Registry Ledger 2021.',21,NULL,'vincent.mariano@legacy.sicms','Residence Hall, College of Science, CLSU Campus, Science City of Muñoz, Nueva Ecija','3rd Year',NULL,NULL,NULL,'2021-03-19 08:00:00'),(22,53,'Student','Patricia Rubio','Female','2020-0702',NULL,'College of Business and Accountancy',NULL,'BS Accountancy | B',NULL,NULL,'09171110304','Migrated legacy entry from the SDRU Registry Ledger 2021.',20,NULL,'patricia.rubio@legacy.sicms','Residence Hall, College of Business and Accountancy, CLSU Campus, Science City of Muñoz, Nueva Ecija','2nd Year',NULL,NULL,NULL,'2021-09-11 09:30:00'),(23,54,'Student','Aldrich Soriano','Male','2021-0801',NULL,'College of Education',NULL,'BS Elementary Education | A',NULL,NULL,'09171110305','Migrated legacy entry from the SDRU Registry Ledger 2022.',17,NULL,'aldrich.soriano@legacy.sicms','Residence Hall, College of Education, CLSU Campus, Science City of Muñoz, Nueva Ecija','1st Year',NULL,NULL,NULL,'2022-04-13 10:00:00'),(24,55,'Student','Melanie Tagle','Female','2021-0802',NULL,'College of Science',NULL,'BS Computer Science | A',NULL,NULL,'09171110306','Migrated legacy entry from the SDRU Registry Ledger 2022.',22,NULL,'melanie.tagle@legacy.sicms','Residence Hall, College of Science, CLSU Campus, Science City of Muñoz, Nueva Ecija','3rd Year',NULL,NULL,NULL,'2022-08-15 10:30:00'),(25,56,'Student','Ronald Escalante','Male','2022-0901',NULL,'College of Arts and Social Sciences',NULL,'AB Psychology | B',NULL,NULL,'09171110307','Migrated legacy entry from the SDRU Registry Ledger 2023.',20,NULL,'ronald.escalante@legacy.sicms','Residence Hall, College of Arts and Social Sciences, CLSU Campus, Science City of Muñoz, Nueva Ecija','2nd Year',NULL,NULL,NULL,'2023-05-06 08:00:00'),(26,57,'Student','Isabela Del Rosario','Female','2022-0902',NULL,'College of Engineering',NULL,'BS Mechanical Engineering | A',NULL,NULL,'09171110308','Migrated legacy entry from the SDRU Registry Ledger 2023.',21,NULL,'isabela.delrosario@legacy.sicms','Residence Hall, College of Engineering, CLSU Campus, Science City of Muñoz, Nueva Ecija','3rd Year',NULL,NULL,NULL,'2023-10-11 09:00:00'),(27,58,'Student','Fernando Quijano','Male','2023-1001',NULL,'College of Science',NULL,'BS Environmental Science | B',NULL,NULL,'09171110309','Migrated legacy entry from the SDRU Registry Ledger 2024.',20,NULL,'fernando.quijano@legacy.sicms','Residence Hall, College of Science, CLSU Campus, Science City of Muñoz, Nueva Ecija','2nd Year',NULL,NULL,NULL,'2024-02-26 08:30:00'),(28,59,'Student','Marilyn Castro','Female','2023-1002',NULL,'College of Agriculture',NULL,'BS Agriculture | A',NULL,NULL,'09171110310','Migrated legacy entry from the SDRU Registry Ledger 2024.',21,NULL,'marilyn.castro@legacy.sicms','Residence Hall, College of Agriculture, CLSU Campus, Science City of Muñoz, Nueva Ecija','3rd Year',NULL,NULL,NULL,'2024-06-15 09:00:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaints`
--

LOCK TABLES `complaints` WRITE;
/*!40000 ALTER TABLE `complaints` DISABLE KEYS */;
INSERT INTO `complaints` VALUES (40,'SDRU-20260112-1001','Cyberbullying in Section Group Chat',2,'Angelica de Guzman','Female',20,'Brgy. Poblacion, Science City of Muñoz, Nueva Ecija','Student',NULL,NULL,NULL,NULL,NULL,NULL,'23-4101','angelica.deguzman@clsu2.edu.ph','09171234101','College of Engineering','Bachelor of Science in Information Technology (BSIT)','Fourth Year','4-2','Bachelor of Science in Information Technology (BSIT) | 4-2','Cyberbullying','2026-01-08 21:40:00','Online - Section group chat (Facebook Messenger)','Complainant reported receiving repeated derogatory and insulting messages posted about her in the official section group chat, including mocked photos and false accusations. She requested that the SDRU take action to stop the online harassment.','Under Investigation',NULL,NULL,NULL,NULL,NULL,NULL,'2026-01-12 09:15:00','2026-01-12 09:15:00','2026-02-03 14:20:00','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(41,'SDRU-20260208-1002','Physical Altercation in the Canteen',2,'Mark Anthony Villanueva','Male',21,'Brgy. San Isidro, Cabanatuan City, Nueva Ecija','Student',NULL,NULL,NULL,NULL,NULL,NULL,'22-4102','markanthony.villanueva@clsu2.edu.ph','09171234102','College of Science','Bachelor of Science in Computer Science (BSCS)','Third Year','3-1','Bachelor of Science in Computer Science (BSCS) | 3-1','Physical Assault','2026-02-05 12:30:00','University Canteen, main food section','A heated argument over a seat escalated into a physical altercation. The respondent allegedly shoved the complainant and threw a plate, causing minor injuries. Security personnel intervened and separated the parties.','Under Investigation',NULL,NULL,NULL,NULL,NULL,NULL,'2026-02-08 10:00:00','2026-02-08 10:00:00','2026-03-01 09:45:00','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(42,'SDRU-20260301-1003','Unauthorized Use of Student Portal Account',2,'Justine Marie Ramos','Female',19,'University Dormitory, CLSU Campus, Science City of Muñoz','Student',NULL,NULL,NULL,NULL,NULL,NULL,'25-4103','justinemarie.ramos@clsu2.edu.ph','09171234103','College of Business and Accountancy','Bachelor of Science in Accountancy (BSAc)','Second Year','2-3','Bachelor of Science in Accountancy (BSAc) | 2-3','Unauthorized Account Access','2026-02-26 19:10:00','Online - CLSU student portal','Complainant discovered that someone logged in to her student portal account without permission, changed her class schedule, and forwarded sensitive documents to an unknown email address. She suspects a classmate who knew her password.','Under Investigation',NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-01 08:30:00','2026-03-01 08:30:00','2026-03-20 11:00:00','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(43,'SDRU-20260325-1004','Plagiarism in Research Proposal',2,'Rafael Bautista','Male',22,'Brgy. Poblacion Sur, Talavera, Nueva Ecija','Student',NULL,NULL,NULL,NULL,NULL,NULL,'22-4104','rafael.bautista@clsu2.edu.ph','09171234104','College of Science','Bachelor of Science in Biology (BSBio)','Fourth Year','4-1','Bachelor of Science in Biology (BSBio) | 4-1','Plagiarism','2026-03-20 15:00:00','College of Science, Research Laboratory','The complainant\'s group research proposal was copied almost verbatim by another group and submitted under their names. Similarity report confirmed more than 85% overlap. The complainant seeks proper credit restoration.','Under Investigation',NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-25 14:00:00','2026-03-25 14:00:00','2026-04-05 10:30:00','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(44,'SDRU-20260410-1005','Verbal Harassment of a Classmate',2,'Kathleen Mercado','Female',20,'Brgy. Balibago, San Jose City, Nueva Ecija','Student',NULL,NULL,NULL,NULL,NULL,NULL,'24-4105','kathleen.mercado@clsu2.edu.ph','09171234105','College of Education','Bachelor of Secondary Education (BSEd)','Third Year','3-2','Bachelor of Secondary Education (BSEd) | 3-2','Intimidation, Threat and Harassment','2026-04-06 16:20:00','College of Education building, 2nd floor corridor','Complainant alleges repeated verbal harassment and name-calling by a classmate during and after class sessions. The behavior caused her emotional distress and fear of attending class.','Under Investigation',NULL,NULL,NULL,NULL,NULL,NULL,'2026-04-10 09:00:00','2026-04-10 09:00:00','2026-05-02 13:15:00','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(45,'SDRU-20260506-1006','Damaged Laboratory Equipment',2,'Christian Lopez','Male',21,'Brgy. San Fabian, Santo Domingo, Nueva Ecija','Student',NULL,NULL,NULL,NULL,NULL,NULL,'23-4106','christian.lopez@clsu2.edu.ph','09171234106','College of Veterinary Science and Medicine','Doctor of Veterinary Medicine (DVM)','Fourth Year','4-1','Doctor of Veterinary Medicine (DVM) | 4-1','Property Damage','2026-05-02 10:45:00','Veterinary Medicine Laboratory 2','A microscope and several glass slides were deliberately damaged after an unsupervised laboratory session. The complainant identified the respondent as being last seen near the equipment before the damage was discovered.','Under Investigation',NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-06 11:30:00','2026-05-06 11:30:00','2026-05-12 15:00:00','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(46,'SDRU-20260601-1007','Theft of Personal Belongings',2,'Samantha Cruz','Female',19,'University Dormitory, CLSU Campus, Science City of Muñoz','Student',NULL,NULL,NULL,NULL,NULL,NULL,'25-4107','samantha.cruz@clsu2.edu.ph','09171234107','College of Hospitality Management','Bachelor of Science in Hospitality Management (BSHM)','Second Year','2-1','Bachelor of Science in Hospitality Management (BSHM) | 2-1','Theft','2026-05-28 07:50:00','University Dormitory, Room 204','A mobile phone and cash amounting to PHP 1,500 were stolen from the complainant\'s locker. The respondent, a roommate, was present during the time the theft was believed to have occurred.','Under Investigation',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-01 08:45:00','2026-06-01 08:45:00','2026-06-10 10:00:00','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(47,'SDRU-20260703-1008','Unauthorized Discussion of Grades',2,'Paolo Mendoza','Male',20,'Brgy. Central, Guimba, Nueva Ecija','Student',NULL,NULL,NULL,NULL,NULL,NULL,'24-4108','paolo.mendoza@clsu2.edu.ph','09171234108','College of Agriculture','Bachelor of Science in Agriculture (BSA)','Third Year','3-1','Bachelor of Science in Agriculture (BSA) | 3-1','Breach of Confidentiality','2026-06-29 13:05:00','College of Agriculture, Room 105','A class representative allegedly accessed and shared the complainant\'s grades and personal information without consent through a group chat. The complainant requests an investigation into the breach.','Under Investigation',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-03 09:20:00','2026-07-03 09:20:00','2026-07-18 14:00:00','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(48,'SDRU-20260805-1009','Disruptive Behavior During Examination',2,'Erica Tolentino','Female',20,'Brgy. Poblacion, Palayan City, Nueva Ecija','Student',NULL,NULL,NULL,NULL,NULL,NULL,'24-4109','erica.tolentino@clsu2.edu.ph','09171234109','College of Fisheries','Bachelor of Science in Fisheries (BSF)','Third Year','3-2','Bachelor of Science in Fisheries (BSF) | 3-2','Disruptive Behavior','2026-08-01 08:00:00','College of Fisheries, Examination Room','During a major examination, the respondent repeatedly talked loudly, tapped on the desk, and attempted to copy answers, disrupting the complainant and other examinees. The proctor asked him to leave but he refused initially.','Under Investigation',NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-05 10:30:00','2026-08-05 10:30:00','2026-08-20 09:00:00','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(49,'SDRU-20260901-1010','Graffiti Vandalism on Campus Wall',2,'Denise Rivera','Female',21,'Brgy. San Roque, Gapan City, Nueva Ecija','Student',NULL,NULL,NULL,NULL,NULL,NULL,'23-4110','denise.rivera@clsu2.edu.ph','09171234110','College of Arts and Social Sciences','Bachelor of Arts in Social Sciences (BASS)','Fourth Year','4-1','Bachelor of Arts in Social Sciences (BASS) | 4-1','Property Damage','2026-08-28 22:15:00','College of Arts and Social Sciences, outer wall','The complainant, an officer of the college student council, reported that offensive graffiti was painted on the college building wall. Security camera footage identified the respondent near the scene at the reported time.','Under Investigation',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-01 08:00:00','2026-09-01 08:00:00','2026-09-10 09:30:00','Online Submission',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(50,'MIG-2020-0101','Legacy: Cheating During Final Examination',2,'Alicia Fernando','Female',20,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2019-0101','alicia.fernando@legacy.sicms','09171110101','College of Science','BS Biology','3rd Year','A','BS Biology | A','Academic Dishonesty','2020-03-04 08:00:00','Science Auditorium','Legacy record: respondent was caught copying answers during the final examination in Genetics. Proctor reported the incident to the SDRU.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2020-03-04 09:00:00','2020-03-04 09:00:00','2020-04-10 15:00:00','Legacy','2020-03-04','Suspended for one semester.','Suspension for one semester; mandatory academic integrity seminar.','2020-04-10',NULL,'SDRU Registry Ledger 2020','Suspended for one semester effective SY 2019-2020.'),(51,'MIG-2020-0102','Legacy: Vandalism of Library Property',2,'Benjamin Santiago','Male',19,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2019-0102','benjamin.santiago@legacy.sicms','09171110102','College of Engineering','BS Civil Engineering','2nd Year','B','BS Civil Engineering | B','Property Damage','2020-06-20 14:30:00','University Library, 3rd floor','Legacy record: respondent defaced several library reference books with marker pen and carved initials onto a reading table.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2020-06-20 15:00:00','2020-06-20 15:00:00','2020-07-05 10:00:00','Legacy','2020-06-20','Restitution of library books and community service.','Replaced damaged books and rendered 20 hours of community service.','2020-07-05',NULL,'SDRU Registry Ledger 2020','Books replaced and community service completed.'),(52,'MIG-2021-0201','Legacy: Theft in the Dormitory',2,'Christine Aquino','Female',21,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2020-0201','christine.aquino@legacy.sicms','09171110201','College of Science','BS Chemistry','3rd Year','A','BS Chemistry | A','Theft','2021-03-18 21:00:00','University Dormitory, Building C','Legacy record: respondent stole a laptop and other electronic devices from the complainant\'s dormitory room.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2021-03-19 08:00:00','2021-03-19 08:00:00','2021-05-15 11:00:00','Legacy','2021-03-19','Suspended for two semesters with conditions for readmission.','Two-semester suspension and supervised return of stolen items.','2021-05-15',NULL,'SDRU Registry Ledger 2021','Suspension served; stolen items returned.'),(53,'MIG-2021-0202','Legacy: Cyber Defamation',2,'Daniel Francisco','Male',20,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2020-0202','daniel.francisco@legacy.sicms','09171110202','College of Business and Accountancy','BS Accountancy','2nd Year','B','BS Accountancy | B','Cyberbullying','2021-09-10 22:15:00','Online - social media platform','Legacy record: respondent posted defamatory statements about the complainant on a public social media account, causing reputational harm.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2021-09-11 09:30:00','2021-09-11 09:30:00','2021-10-20 14:00:00','Legacy','2021-09-11','Written apology and removal of defamatory posts.','Public retraction and written apology; post deleted.','2021-10-20',NULL,'SDRU Registry Ledger 2021','Apology issued and posts removed.'),(54,'MIG-2022-0301','Legacy: Physical Bullying',2,'Eduardo Marquez','Male',17,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-0301','eduardo.marquez@legacy.sicms','09171110301','College of Education','BS Elementary Education','1st Year','A','BS Elementary Education | A','Physical Assault','2022-04-12 16:45:00','Education Building corridor','Legacy record: respondent repeatedly shoved and verbally harassed the complainant, a younger student, over several weeks.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2022-04-13 10:00:00','2022-04-13 10:00:00','2022-06-01 09:00:00','Legacy','2022-04-13','Probation for one semester with counseling.','Behavioral probation and mandatory counseling sessions.','2022-06-01',NULL,'SDRU Registry Ledger 2022','Probation served; no repeat incidents.'),(55,'MIG-2022-0302','Legacy: Unauthorized Grade Changes',2,'Fiona Reyes','Female',22,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2021-0302','fiona.reyes@legacy.sicms','09171110302','College of Science','BS Computer Science','3rd Year','A','BS Computer Science | A','Fraud','2022-08-15 09:20:00','Registrar\'s Office','Legacy record: respondent was found to have tampered with course grade records through a registrar assistant.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2022-08-15 10:30:00','2022-08-15 10:30:00','2022-09-30 13:00:00','Legacy','2022-08-15','Dismissed from the program.','Permanent dismissal from the BS Computer Science program.','2022-09-30',NULL,'SDRU Registry Ledger 2022','Dismissed from program; records restored.'),(56,'MIG-2023-0401','Legacy: Harassment of Female Student',2,'Ginalyn Bautista','Female',20,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-0401','ginalyn.bautista@legacy.sicms','09171110401','College of Arts and Social Sciences','AB Psychology','2nd Year','B','AB Psychology | B','Sexual Harassment','2023-05-05 17:30:00','College of Arts and Social Sciences, lobby','Legacy record: respondent made repeated inappropriate advances and remarks toward the complainant.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2023-05-06 08:00:00','2023-05-06 08:00:00','2023-06-20 10:00:00','Legacy','2023-05-06','Probation for one academic year with no-contact order.','Behavioral probation and no-contact order issued.','2023-06-20',NULL,'SDRU Registry Ledger 2023','Probation enforced; no further incidents.'),(57,'MIG-2023-0402','Legacy: Academic Plagiarism',2,'Hector Villamor','Male',21,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2022-0402','hector.villamor@legacy.sicms','09171110402','College of Engineering','BS Mechanical Engineering','3rd Year','A','BS Mechanical Engineering | A','Plagiarism','2023-10-10 10:00:00','Engineering Faculty Office','Legacy record: respondent submitted a term paper with more than 80% copied content from online sources.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2023-10-11 09:00:00','2023-10-11 09:00:00','2023-11-25 15:00:00','Legacy','2023-10-11','Written warning and academic integrity seminar.','Formal written warning; resubmission of original work.','2023-11-25',NULL,'SDRU Registry Ledger 2023','Warning issued; original work resubmitted.'),(58,'MIG-2024-0501','Legacy: Forgery of Consent Form',2,'Irene Dela Pena','Female',20,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-0501','irene.delapena@legacy.sicms','09171110501','College of Science','BS Environmental Science','2nd Year','B','BS Environmental Science | B','Forgery','2024-02-25 09:00:00','College of Science, Faculty Room','Legacy record: respondent forged the complainant\'s signature on a dormitory consent form and an academic waiver.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2024-02-26 08:30:00','2024-02-26 08:30:00','2024-04-05 14:00:00','Legacy','2024-02-26','Suspended for one semester.','One-semester suspension and written apology.','2024-04-05',NULL,'SDRU Registry Ledger 2024','Suspension served; apology delivered.'),(59,'MIG-2024-0502','Legacy: Violent Disruption of School Event',2,'Jerome Pascual','Male',21,NULL,'Student',NULL,NULL,NULL,NULL,NULL,NULL,'2023-0502','jerome.pascual@legacy.sicms','09171110502','College of Agriculture','BS Agriculture','3rd Year','A','BS Agriculture | A','Disruptive Behavior','2024-06-14 19:00:00','University Grandstand, foundation day','Legacy record: respondent forcibly disrupted a school foundation day program, pushing staff and damaging stage decorations.','Resolved',NULL,NULL,NULL,NULL,NULL,NULL,'2024-06-15 09:00:00','2024-06-15 09:00:00','2024-07-30 11:00:00','Legacy','2024-06-15','Community service and written warning.','40 hours of community service and formal written warning.','2024-07-30',NULL,'SDRU Registry Ledger 2024','Community service completed; warning on record.');
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
INSERT INTO `login_sessions` VALUES ('4sjtsffore01rm58qb99nveo7v',2,'Desktop browser','::1','Localhost (this computer)','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-23 03:01:43','2026-09-23 03:01:58',NULL);
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
INSERT INTO `notifications` VALUES (7,2,'message_received','New Message Received','You received a new message from Maria Cruz.','web/views/messages/index.php?conversation_id=3',1,'2026-09-02 00:30:02','2026-09-02 00:29:46'),(9,2,'message_received','New Message Received','You received a new message from KWIN JHERANE OCAREZA.','web/views/messages/index.php?conversation_id=24',1,'2026-09-19 14:37:37','2026-09-02 04:18:12'),(11,2,'message_received','New Message Received','You received a new message from KWIN JHERANE OCAREZA.','web/views/messages/index.php?conversation_id=24',1,'2026-09-19 14:37:37','2026-09-03 02:48:48'),(41,2,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0001 was submitted by ALEXA ANGELA GABRIEL.','web/views/cases/show.php?id=35',1,'2026-09-22 00:24:33','2026-09-22 00:23:29'),(49,2,'case_action_approval_needed','Case Action Approval Needed','A coordinator submitted \"Return\" for case SDRU-20260922-0001 and is waiting for your review.','web/views/cases/show.php?id=35&approval_id=1',1,'2026-09-22 03:12:14','2026-09-22 00:26:28'),(52,2,'complaint_submitted','New Complaint Submitted','SDRU-20260922-0002 was submitted by Maria Santos.','web/views/cases/show.php?id=36',1,'2026-09-22 04:10:32','2026-09-22 04:08:28'),(60,2,'case_action_approval_needed','Case Action Approval Needed','A coordinator submitted \"Classify\" for case SDRU-20260922-0002 and is waiting for your review.','web/views/cases/show.php?id=36&approval_id=2',1,'2026-09-22 17:08:42','2026-09-22 04:16:28'),(61,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-714E0F580A was released to the respondent(s).','web/views/cases/show.php?id=37',1,'2026-09-22 17:08:42','2026-09-22 06:35:19'),(63,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-714E0F580A was released to the respondent(s).','web/views/cases/show.php?id=37',1,'2026-09-22 17:08:42','2026-09-22 06:35:28'),(65,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-060A33E636 was released to the respondent(s).','web/views/cases/show.php?id=38',1,'2026-09-22 17:08:42','2026-09-22 06:35:47'),(67,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-060A33E636 was released to the respondent(s).','web/views/cases/show.php?id=38',1,'2026-09-22 17:08:42','2026-09-22 06:35:55'),(69,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-287887F390 was released to the respondent(s).','web/views/cases/show.php?id=39',1,'2026-09-22 17:08:42','2026-09-22 06:36:16'),(71,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for VERIFY-287887F390 was released to the respondent(s).','web/views/cases/show.php?id=39',1,'2026-09-22 17:08:42','2026-09-22 06:36:25'),(73,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for SDRU-20260922-0002 was released to the respondent(s).','web/views/cases/show.php?id=36',1,'2026-09-22 17:08:42','2026-09-22 08:30:58'),(75,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',1,'2026-09-22 17:08:42','2026-09-22 08:33:51'),(82,2,'case_action_approval_needed','Case Action Approval Needed','A coordinator submitted \"Classify\" for case SDRU-20260922-0002 and is waiting for your review.','web/views/cases/show.php?id=36&approval_id=3',1,'2026-09-22 17:08:42','2026-09-22 08:39:27'),(83,2,'case_action_approval_needed','Case Action Approval Needed','A coordinator submitted \"Classify\" for case SDRU-20260922-0002 and is waiting for your review.','web/views/cases/show.php?id=36&approval_id=4',1,'2026-09-22 17:08:42','2026-09-22 08:49:37'),(85,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for SDRU-20260922-0002.','web/views/cases/show.php?id=36',1,'2026-09-22 17:08:42','2026-09-22 08:51:25'),(92,2,'case_action_approval_needed','Case Action Approval Needed','A coordinator submitted \"Classify\" for case SDRU-20260922-0002 and is waiting for your review.','web/views/cases/show.php?id=36&approval_id=5',1,'2026-09-22 17:08:42','2026-09-22 09:21:25'),(93,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for SDRU-20260922-0002 was released to the respondent(s).','web/views/cases/show.php?id=36',1,'2026-09-22 17:22:51','2026-09-22 17:22:36'),(95,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for SDRU-20260922-0002 was released to the respondent(s).','web/views/cases/show.php?id=36',1,'2026-09-22 18:22:54','2026-09-22 18:06:06'),(97,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for SDRU-20260922-0002 was released to the respondent(s).','web/views/cases/show.php?id=36',1,'2026-09-22 18:22:54','2026-09-22 18:17:25'),(99,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for SDRU-20260922-0002 was released to the respondent(s).','web/views/cases/show.php?id=36',1,'2026-09-22 20:08:07','2026-09-22 20:07:33'),(107,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for 2026-013 was released to the respondent(s).','web/views/cases/show.php?id=13',1,'2026-09-22 22:01:43','2026-09-22 22:01:26'),(109,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for 2026-013 was released to the respondent(s).','web/views/cases/show.php?id=13',1,'2026-09-22 22:09:58','2026-09-22 22:09:38'),(112,2,'respondent_case_released','Case Forwarded to Respondent','The permitted case information for 2026-011 was released to the respondent(s).','web/views/cases/show.php?id=11',1,'2026-09-22 22:21:48','2026-09-22 22:11:38'),(114,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',1,'2026-09-23 01:54:29','2026-09-23 01:54:10'),(126,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',1,'2026-09-23 02:29:15','2026-09-23 02:03:12'),(129,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',1,'2026-09-23 02:29:15','2026-09-23 02:07:36'),(132,2,'counter_statement_submitted','Counter-Statement Submitted','A counter-statement was submitted for 2026-013.','web/views/cases/show.php?id=13',1,'2026-09-23 02:41:40','2026-09-23 02:30:21');
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

-- Dump completed on 2026-09-23  3:32:52
