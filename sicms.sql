-- =============================================================
-- SICMS - Student Integrity Case Management System
-- Complete database schema
-- Engine: MySQL  (connection settings in web/config/Database.php)
--   host: 127.0.0.1  port: 3307  db: sicms
-- =============================================================

CREATE DATABASE IF NOT EXISTS sicms
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sicms;

-- -------------------------------------------------------------
-- accounts
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS accounts (
    account_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name   VARCHAR(100) NOT NULL,
    last_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    auth_provider VARCHAR(50)  DEFAULT NULL,
    role         VARCHAR(50)  NOT NULL DEFAULT 'student',
    status       VARCHAR(20)  NOT NULL DEFAULT 'active',
    college_id   INT UNSIGNED DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    phone_number VARCHAR(20)  NOT NULL DEFAULT '',
    gender       VARCHAR(20)  NOT NULL DEFAULT '',
    address      TEXT         NOT NULL,
    PRIMARY KEY (account_id),
    UNIQUE KEY uq_accounts_email (email),
    KEY idx_accounts_role (role),
    KEY idx_accounts_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE accounts
    MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Bring older installations in line with the current timestamp behavior.
ALTER TABLE accounts
    MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- -------------------------------------------------------------
-- complaints
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaints (
    complaint_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    case_number     VARCHAR(50)  NOT NULL,
    complaint_title VARCHAR(255) NOT NULL,
    submitted_by_account_id INT UNSIGNED NOT NULL,
    complainant_name        VARCHAR(255) NOT NULL,
    complainant_type        VARCHAR(50)  NOT NULL DEFAULT 'Student',
    complainant_relationship VARCHAR(100) DEFAULT NULL,
    complainant_employee_no VARCHAR(100) DEFAULT NULL,
    complainant_department  VARCHAR(255) DEFAULT NULL,
    complainant_position    VARCHAR(255) DEFAULT NULL,
    complainant_affiliation VARCHAR(255) DEFAULT NULL,
    complainant_purpose     VARCHAR(255) DEFAULT NULL,
    complainant_student_no  VARCHAR(50)  DEFAULT NULL,
    complainant_email       VARCHAR(255) DEFAULT NULL,
    complainant_contact     VARCHAR(50)  DEFAULT NULL,
    complainant_college     VARCHAR(255) DEFAULT NULL,
    complainant_course      VARCHAR(255) DEFAULT NULL,
    complainant_year_level  VARCHAR(50)  DEFAULT NULL,
    complainant_section     VARCHAR(50)  DEFAULT NULL,
    complainant_course_year VARCHAR(100) DEFAULT NULL,
    case_classification     VARCHAR(100) NOT NULL,
    incident_datetime       DATETIME     DEFAULT NULL,
    incident_location       VARCHAR(255) DEFAULT NULL,
    complaint_details       TEXT,
    status                  VARCHAR(50)  NOT NULL DEFAULT 'Submitted',
    assigned_coordinator_account_id INT UNSIGNED DEFAULT NULL,
    submitted_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (complaint_id),
    UNIQUE KEY uq_complaints_case_number (case_number),
    KEY idx_complaints_submitted_by (submitted_by_account_id),
    KEY idx_complaints_coordinator (assigned_coordinator_account_id),
    KEY idx_complaints_status (status),
    KEY idx_complaints_classification (case_classification),
    KEY idx_complaints_submitted_at (submitted_at),
    CONSTRAINT fk_complaints_submitter
        FOREIGN KEY (submitted_by_account_id) REFERENCES accounts (account_id),
    CONSTRAINT fk_complaints_coordinator
        FOREIGN KEY (assigned_coordinator_account_id) REFERENCES accounts (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- complaint_respondents
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaint_respondents (
    respondent_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id  INT UNSIGNED NOT NULL,
    full_name     VARCHAR(255) NOT NULL,
    student_no    VARCHAR(50)  DEFAULT NULL,
    college       VARCHAR(255) DEFAULT NULL,
    course_year   VARCHAR(100) DEFAULT NULL,
    contact_info  VARCHAR(255) DEFAULT NULL,
    details       TEXT,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (respondent_id),
    KEY idx_respondents_complaint (complaint_id),
    CONSTRAINT fk_respondents_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- complaint_witnesses
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaint_witnesses (
    witness_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id INT UNSIGNED NOT NULL,
    full_name    VARCHAR(255) NOT NULL,
    student_no   VARCHAR(50)  DEFAULT NULL,
    contact_info VARCHAR(255) DEFAULT NULL,
    statement    TEXT,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (witness_id),
    KEY idx_witnesses_complaint (complaint_id),
    CONSTRAINT fk_witnesses_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- complaint_evidence
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaint_evidence (
    evidence_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id       INT UNSIGNED NOT NULL,
    original_filename  VARCHAR(255) NOT NULL,
    stored_filename    VARCHAR(255) NOT NULL,
    file_path          VARCHAR(255) NOT NULL,
    mime_type          VARCHAR(100) DEFAULT NULL,
    file_size          BIGINT UNSIGNED DEFAULT NULL,
    uploaded_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (evidence_id),
    KEY idx_evidence_complaint (complaint_id),
    CONSTRAINT fk_evidence_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- case_history
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS case_history (
    history_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id INT UNSIGNED NOT NULL,
    action       VARCHAR(100) DEFAULT NULL,
    previous_status VARCHAR(50) DEFAULT NULL,
    new_status      VARCHAR(50) DEFAULT NULL,
    remarks      TEXT,
    revision_fields TEXT DEFAULT NULL,
    assigned_coordinator_account_id INT UNSIGNED DEFAULT NULL,
    created_by_account_id INT UNSIGNED DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (history_id),
    KEY idx_history_complaint (complaint_id),
    KEY idx_history_created_by (created_by_account_id),
    KEY idx_history_coordinator (assigned_coordinator_account_id),
    CONSTRAINT fk_history_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_history_creator
        FOREIGN KEY (created_by_account_id) REFERENCES accounts (account_id),
    CONSTRAINT fk_history_coordinator
        FOREIGN KEY (assigned_coordinator_account_id) REFERENCES accounts (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- hearings
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hearings (
    hearing_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id      INT UNSIGNED NOT NULL,
    scheduled_by_account_id INT UNSIGNED DEFAULT NULL,
    hearing_datetime  DATETIME     DEFAULT NULL,
    venue             VARCHAR(255) DEFAULT NULL,
    google_meet_link  VARCHAR(500) DEFAULT NULL,
    remarks           TEXT,
    status            VARCHAR(50)  NOT NULL DEFAULT 'Scheduled',
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (hearing_id),
    KEY idx_hearings_complaint (complaint_id),
    KEY idx_hearings_scheduled_by (scheduled_by_account_id),
    KEY idx_hearings_status (status),
    KEY idx_hearings_datetime (hearing_datetime),
    CONSTRAINT fk_hearings_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_hearings_scheduler
        FOREIGN KEY (scheduled_by_account_id) REFERENCES accounts (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- case_messages
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS case_messages (
    message_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id     INT UNSIGNED NOT NULL,
    sender_account_id   INT UNSIGNED NOT NULL,
    receiver_account_id INT UNSIGNED NOT NULL,
    message          TEXT,
    attachment       VARCHAR(255) DEFAULT NULL,
    is_read          TINYINT(1)   NOT NULL DEFAULT 0,
    read_at          DATETIME     DEFAULT NULL,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id),
    KEY idx_messages_complaint (complaint_id),
    KEY idx_messages_sender (sender_account_id),
    KEY idx_messages_receiver (receiver_account_id),
    KEY idx_messages_complaint_unread (complaint_id, receiver_account_id, is_read),
    CONSTRAINT fk_messages_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender
        FOREIGN KEY (sender_account_id) REFERENCES accounts (account_id),
    CONSTRAINT fk_messages_receiver
        FOREIGN KEY (receiver_account_id) REFERENCES accounts (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- conversation_participants
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversation_participants (
    participant_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id          INT UNSIGNED NOT NULL,
    account_id            INT UNSIGNED NOT NULL,
    counterpart_account_id INT UNSIGNED NOT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (participant_id),
    UNIQUE KEY uniq_participant_pair (complaint_id, account_id, counterpart_account_id),
    KEY idx_participant_account (account_id),
    CONSTRAINT fk_participant_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_participant_account
        FOREIGN KEY (account_id) REFERENCES accounts (account_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_participant_counterpart
        FOREIGN KEY (counterpart_account_id) REFERENCES accounts (account_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- hidden_conversations
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hidden_conversations (
    hidden_id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id          INT UNSIGNED NOT NULL,
    account_id            INT UNSIGNED NOT NULL,
    counterpart_account_id INT UNSIGNED NOT NULL,
    hidden_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    visible               TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (hidden_id),
    UNIQUE KEY uniq_hidden_pair (complaint_id, account_id, counterpart_account_id),
    CONSTRAINT fk_hidden_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_hidden_account
        FOREIGN KEY (account_id) REFERENCES accounts (account_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_hidden_counterpart
        FOREIGN KEY (counterpart_account_id) REFERENCES accounts (account_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- notifications
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id      INT UNSIGNED NOT NULL,
    type            VARCHAR(50)  DEFAULT NULL,
    title           VARCHAR(255) DEFAULT NULL,
    message         TEXT,
    link            VARCHAR(255) DEFAULT NULL,
    is_read         TINYINT(1)   NOT NULL DEFAULT 0,
    read_at         DATETIME     DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (notification_id),
    KEY idx_notifications_account (account_id),
    KEY idx_notifications_account_unread (account_id, is_read),
    CONSTRAINT fk_notifications_account
        FOREIGN KEY (account_id) REFERENCES accounts (account_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- audit_logs
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    audit_log_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id   INT UNSIGNED DEFAULT NULL,
    user_name    VARCHAR(255) DEFAULT NULL,
    user_role    VARCHAR(50)  DEFAULT NULL,
    action       VARCHAR(100) DEFAULT NULL,
    description  TEXT,
    ip_address   VARCHAR(45)  DEFAULT NULL,
    user_agent   VARCHAR(255) DEFAULT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (audit_log_id),
    KEY idx_audit_logs_created_at (created_at),
    KEY idx_audit_logs_account (account_id),
    KEY idx_audit_logs_action (action),
    CONSTRAINT fk_audit_logs_account
        FOREIGN KEY (account_id) REFERENCES accounts (account_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- legacy_incharges
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS legacy_incharges (
    legacy_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name   VARCHAR(150) NOT NULL,
    position    VARCHAR(100) NOT NULL,
    tenure      VARCHAR(50)  DEFAULT NULL,
    description TEXT,
    photo_path  VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (legacy_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Seed data
-- =============================================================

-- Default super-admin. Password: Admin@1234
INSERT INTO accounts (first_name, last_name, email, password_hash, role, status, created_at, updated_at)
VALUES ('System', 'Administrator', 'admin@sicms.local',
        '$2y$10$ChGPw1PJZIQ6FDv/OnXdHOgemvVdJHgJq82JGoca508dR7RTrc6WO',
        'super-admin', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE email = email;

-- Default head of SDRU. Password: HeadSdru@1234
INSERT INTO accounts (first_name, last_name, email, password_hash, role, status, created_at, updated_at)
VALUES ('Head', 'SDRU', 'headsdru@sicms.local',
        '$2y$10$kRqeDblG9N/GiGK9SdZeKegxDC72mZ85uQ1AhNpXraKpOZ1LtQqjC',
        'head-of-sdru', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE email = email;
