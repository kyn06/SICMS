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
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_history`
--

LOCK TABLES `case_history` WRITE;
/*!40000 ALTER TABLE `case_history` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaint_respondents`
--

LOCK TABLES `complaint_respondents` WRITE;
/*!40000 ALTER TABLE `complaint_respondents` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaints`
--

LOCK TABLES `complaints` WRITE;
/*!40000 ALTER TABLE `complaints` DISABLE KEYS */;
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

-- Dump completed on 2026-09-23  3:16:11
