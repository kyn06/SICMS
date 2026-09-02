-- =============================================================
-- SICMS - Student Integrity Case Management System
-- Complete database schema and seed data (single-file import)
-- -------------------------------------------------------------
--
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
    student_number VARCHAR(50) DEFAULT NULL,
    college      VARCHAR(255) DEFAULT NULL,
    course       VARCHAR(255) DEFAULT NULL,
    section      VARCHAR(50)  DEFAULT NULL,
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

-- -------------------------------------------------------------
-- complaints
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaints (
    complaint_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    case_number     VARCHAR(50)  NOT NULL,
    complaint_title VARCHAR(255) NOT NULL,
    submitted_by_account_id INT UNSIGNED NOT NULL,
    complainant_name        VARCHAR(255) NOT NULL,
    complainant_gender      VARCHAR(20)  DEFAULT NULL,
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
    case_source             VARCHAR(20)  NOT NULL DEFAULT 'Online Submission',
    original_case_date      DATE         DEFAULT NULL,
    legacy_outcome          VARCHAR(255) DEFAULT NULL,
    action_taken            TEXT         DEFAULT NULL,
    resolution_date         DATE         DEFAULT NULL,
    remarks_notes           TEXT        DEFAULT NULL,
    legacy_entry_source     VARCHAR(100) DEFAULT NULL,
    outcome                 TEXT        DEFAULT NULL,
    PRIMARY KEY (complaint_id),
    UNIQUE KEY uq_complaints_case_number (case_number),
    KEY idx_complaints_submitted_by (submitted_by_account_id),
    KEY idx_complaints_coordinator (assigned_coordinator_account_id),
    KEY idx_complaints_status (status),
    KEY idx_complaints_classification (case_classification),
    KEY idx_complaints_submitted_at (submitted_at),
    KEY idx_complaints_source (case_source),
    KEY idx_complaints_source_orig_year (case_source, original_case_date),
    CONSTRAINT fk_complaints_submitter
        FOREIGN KEY (submitted_by_account_id) REFERENCES accounts (account_id),
    CONSTRAINT fk_complaints_coordinator
        FOREIGN KEY (assigned_coordinator_account_id) REFERENCES accounts (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- complaint_respondents
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaint_respondents (
    respondent_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id      INT UNSIGNED NOT NULL,
    respondent_type   VARCHAR(50)  NOT NULL DEFAULT 'Student',
    full_name         VARCHAR(255) NOT NULL,
    gender            VARCHAR(20)  DEFAULT NULL,
    student_no        VARCHAR(50)  DEFAULT NULL,
    employee_no       VARCHAR(100) DEFAULT NULL,
    college           VARCHAR(255) DEFAULT NULL,
    office_department VARCHAR(255) DEFAULT NULL,
    course_year       VARCHAR(100) DEFAULT NULL,
    position          VARCHAR(255) DEFAULT NULL,
    affiliation       VARCHAR(255) DEFAULT NULL,
    contact_info      VARCHAR(255) DEFAULT NULL,
    details           TEXT,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
    witness_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id    INT UNSIGNED NOT NULL,
    person_type     VARCHAR(50)  NOT NULL DEFAULT 'Student',
    full_name       VARCHAR(255) NOT NULL,
    gender          VARCHAR(20)  DEFAULT NULL,
    student_no      VARCHAR(50)  DEFAULT NULL,
    contact_info    VARCHAR(255) DEFAULT NULL,
    statement       TEXT,
    email           VARCHAR(255) DEFAULT NULL,
    employee_no     VARCHAR(100) DEFAULT NULL,
    college         VARCHAR(255) DEFAULT NULL,
    office_department VARCHAR(255) DEFAULT NULL,
    position        VARCHAR(255) DEFAULT NULL,
    affiliation     VARCHAR(255) DEFAULT NULL,
    address         VARCHAR(255) DEFAULT NULL,
    year_level      VARCHAR(50)  DEFAULT NULL,
    course_year     VARCHAR(100) DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
    doc_type           VARCHAR(50)  DEFAULT NULL,
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
    google_event_id   VARCHAR(1024) DEFAULT NULL,
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

-- -------------------------------------------------------------
-- system_settings
-- Key/value store used for Google Calendar integration
-- (google_calendar_refresh_token, google_calendar_email, ...)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key   VARCHAR(100) NOT NULL,
    setting_value TEXT,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





-- =============================================================
-- SEED DATA
-- =============================================================
--
-- Account passwords (bcrypt):
--   super-admin          : Admin@1234
--   head-of-sdru         : HeadSdru@1234
--   coordinators         : Coord@1234
--   staff                : Staff@1234
--   students (clsu2)     : Student@1234
--
-- Account IDs (after insert):
--   1  System Administrator  (super-admin)
--   2  Ricardo Santos        (head-of-sdru)
--   3  Maria Cruz            (coordinator)
--   4  Juan Dela Pena        (coordinator)
--   5  Ana Bautista          (coordinator)
--   6  Pedro Reyes           (staff)
--   7  Carmen Garcia         (staff)
--   8-22 Student accounts    (for new complaints)
-- =============================================================

-- -------------------------------------------------------------
-- 1. System administrator
-- -------------------------------------------------------------
INSERT INTO accounts (first_name, last_name, email, password_hash, role, status, created_at, updated_at)
VALUES ('System', 'Administrator', 'admin@sicms.local',
        '$2y$10$ChGPw1PJZIQ6FDv/OnXdHOgemvVdJHgJq82JGoca508dR7RTrc6WO',
        'super-admin', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE email = email;

-- -------------------------------------------------------------
-- 2. Head of SDRU
-- -------------------------------------------------------------
INSERT INTO accounts (first_name, last_name, email, password_hash, role, status, created_at, updated_at)
VALUES ('Ricardo', 'Santos', 'ricardo.santos@sicms.local',
        '$2y$10$kRqeDblG9N/GiGK9SdZeKegxDC72mZ85uQ1AhNpXraKpOZ1LtQqjC',
        'head-of-sdru', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE email = email;

-- -------------------------------------------------------------
-- 3. Coordinators (3)
-- -------------------------------------------------------------
INSERT INTO accounts (first_name, last_name, email, password_hash, role, status, created_at, updated_at)
VALUES
('Maria', 'Cruz', 'maria.cruz@sicms.local',
 '$2y$10$UKA/3LfF8SCLMR1z4c9QQOXN93UGHrc6ZvinlVXpwWtkavWKMBla.',
 'coordinator', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Juan', 'Dela Pena', 'juan.delapena@sicms.local',
 '$2y$10$UKA/3LfF8SCLMR1z4c9QQOXN93UGHrc6ZvinlVXpwWtkavWKMBla.',
 'coordinator', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Ana', 'Bautista', 'ana.bautista@sicms.local',
 '$2y$10$UKA/3LfF8SCLMR1z4c9QQOXN93UGHrc6ZvinlVXpwWtkavWKMBla.',
 'coordinator', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE email = email;

-- -------------------------------------------------------------
-- 4. Staff (2)
-- -------------------------------------------------------------
INSERT INTO accounts (first_name, last_name, email, password_hash, role, status, created_at, updated_at)
VALUES
('Pedro', 'Reyes', 'pedro.reyes@sicms.local',
 '$2y$10$klOygz/8utMnfFnPZ/usLu535PzlnKhPUkEYSpvnTn9AP4OFGGdZq',
 'sdru-staff', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Carmen', 'Garcia', 'carmen.garcia@sicms.local',
 '$2y$10$klOygz/8utMnfFnPZ/usLu535PzlnKhPUkEYSpvnTn9AP4OFGGdZq',
 'sdru-staff', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE email = email;

-- -------------------------------------------------------------
-- 5. Student accounts for new complaints (15)
--    email format: firstname.lastname@clsu2.edu.ph
-- -------------------------------------------------------------
INSERT INTO accounts (first_name, last_name, email, password_hash, role, status, created_at, updated_at)
VALUES
('Juan', 'Dela Cruz', 'juan.delacruz@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Maria', 'Santos', 'maria.santos@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Pedro', 'Garcia', 'pedro.garcia@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Ana', 'Reyes', 'ana.reyes@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Carmen', 'Mendoza', 'carmen.mendoza@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Luis', 'Bautista', 'luis.bautista@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Rosa', 'Cruz', 'rosa.cruz@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Miguel', 'Rivera', 'miguel.rivera@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Elena', 'Torres', 'elena.torres@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Jose', 'Flores', 'jose.flores@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Patricia', 'Gomez', 'patricia.gomez@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Daniel', 'Lopez', 'daniel.lopez@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Marcela', 'Diaz', 'marcela.diaz@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Francisco', 'Ramos', 'francisco.ramos@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Isabela', 'Morales', 'isabela.morales@clsu2.edu.ph',
 '$2y$10$irvnsjlF9PQIJAy8.vBHmewGBh/C.ibuRFFuwjAgFfj6JDHw5Lh1O',
 'student', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE email = email;

-- =============================================================
-- COMPLAINTS - NEW (15)   dates: Jan 2026 - Sep 2026
--
-- Coordinator IDs: 3 = Maria Cruz, 4 = Juan Dela Pena, 5 = Ana Bautista
-- Student IDs:     8 = Juan Dela Cruz ... 22 = Isabela Morales
-- =============================================================
INSERT INTO complaints (
    case_number, complaint_title, submitted_by_account_id,
    complainant_name, complainant_gender, complainant_type,
    complainant_student_no, complainant_email, complainant_college,
    complainant_course, complainant_year_level, complainant_section,
    case_classification, incident_datetime, incident_location,
    complaint_details, status, assigned_coordinator_account_id,
    submitted_at, case_source
) VALUES
('2026-001', 'Plagiarism in Research Paper', 8,
 'Juan Dela Cruz', 'Male', 'Student',
 '2023-00001', 'juan.delacruz@clsu2.edu.ph', 'College of Science',
 'BS Biology', '3rd Year', 'A',
 'Academic Dishonesty', '2026-01-15 09:30:00', 'Room 201, Science Building',
 'Submitted a research paper with significant portions copied from published sources without proper citation.',
 'Submitted', 3, '2026-01-15 10:00:00', 'Online Submission'),

('2026-002', 'Cheating During Midterm Examination', 9,
 'Maria Santos', 'Female', 'Student',
 '2024-00002', 'maria.santos@clsu2.edu.ph', 'College of Engineering',
 'BS Computer Science', '2nd Year', 'B',
 'Academic Dishonesty', '2026-01-28 10:00:00', 'Room 105, Engineering Building',
 'Caught using unauthorized notes during the midterm examination in Data Structures.',
 'Verified', 4, '2026-01-28 11:30:00', 'Online Submission'),

('2026-003', 'Unauthorized Collaboration on Assignment', 10,
 'Pedro Garcia', 'Male', 'Student',
 '2022-00003', 'pedro.garcia@clsu2.edu.ph', 'College of Agriculture',
 'BS Agriculture', '4th Year', 'A',
 'Academic Dishonesty', '2026-02-10 14:00:00', 'Agriculture Lab 3',
 'Submitted an assignment nearly identical to another students work, indicating unauthorized collaboration.',
 'Submitted', 5, '2026-02-10 15:00:00', 'Online Submission'),

('2026-004', 'Fabrication of Laboratory Results', 11,
 'Ana Reyes', 'Female', 'Student',
 '2023-00004', 'ana.reyes@clsu2.edu.ph', 'College of Science',
 'BS Chemistry', '3rd Year', 'B',
 'Academic Dishonesty', '2026-02-22 08:00:00', 'Chemistry Laboratory 2',
 'Lab report contained fabricated data points that were physically impossible to obtain during the experiment.',
 'Verified', 3, '2026-02-22 09:00:00', 'Online Submission'),

('2026-005', 'Disruptive Behavior in Class', 12,
 'Carmen Mendoza', 'Female', 'Student',
 '2025-00005', 'carmen.mendoza@clsu2.edu.ph', 'College of Education',
 'BS Education', '1st Year', 'A',
 'Behavioral Misconduct', '2026-03-05 11:00:00', 'Room 302, Education Building',
 'Repeatedly disrupted class proceedings by talking loudly and using phone despite multiple warnings.',
 'Submitted', 4, '2026-03-05 12:00:00', 'Online Submission'),

('2026-006', 'Academic Dishonesty in Online Quiz', 13,
 'Luis Bautista', 'Male', 'Student',
 '2024-00006', 'luis.bautista@clsu2.edu.ph', 'College of Business',
 'BS Business Administration', '2nd Year', 'A',
 'Academic Dishonesty', '2026-03-18 09:00:00', 'Online - Canvas LMS',
 'System logs show suspicious activity during online quiz including multiple tab switches and external website access.',
 'Verified', 5, '2026-03-18 10:30:00', 'Online Submission'),

('2026-007', 'Vandalism of School Property', 14,
 'Rosa Cruz', 'Female', 'Student',
 '2023-00007', 'rosa.cruz@clsu2.edu.ph', 'College of Engineering',
 'BS Mechanical Engineering', '3rd Year', 'A',
 'Property Damage', '2026-04-02 16:00:00', 'Mechanical Engineering Workshop',
 'Deliberately damaged laboratory equipment worth approximately Php 15,000 during an unsupervised session.',
 'Verified', 3, '2026-04-02 17:00:00', 'Online Submission'),

('2026-008', 'Harassment of Classmate', 15,
 'Miguel Rivera', 'Male', 'Student',
 '2022-00008', 'miguel.rivera@clsu2.edu.ph', 'College of Arts',
 'AB Psychology', '4th Year', 'A',
 'Harassment', '2026-04-15 13:00:00', 'College of Arts Commons',
 'Made threatening remarks and intimidating gestures toward a classmate during a group discussion.',
 'Resolved', 4, '2026-04-15 14:00:00', 'Online Submission'),

('2026-009', 'Theft of Personal Belongings', 16,
 'Elena Torres', 'Female', 'Student',
 '2024-00009', 'elena.torres@clsu2.edu.ph', 'College of Science',
 'BS Biology', '2nd Year', 'B',
 'Theft', '2026-05-01 07:30:00', 'Science Building Locker Room',
 'Personal laptop was stolen from the locker room. Security footage is being reviewed.',
 'Submitted', 5, '2026-05-01 08:30:00', 'Online Submission'),

('2026-010', 'Violation of Dress Code Policy', 17,
 'Jose Flores', 'Male', 'Student',
 '2025-00010', 'jose.flores@clsu2.edu.ph', 'College of Agriculture',
 'BS Agriculture', '1st Year', 'B',
 'Policy Violation', '2026-05-14 08:00:00', 'College of Agriculture Main Hall',
 'Repeatedly violated the university dress code policy despite previous verbal warnings from faculty.',
 'Verified', 3, '2026-05-14 09:00:00', 'Online Submission'),

('2026-011', 'Cyberbullying on Social Media', 18,
 'Patricia Gomez', 'Female', 'Student',
 '2023-00011', 'patricia.gomez@clsu2.edu.ph', 'College of Education',
 'BS Education', '3rd Year', 'A',
 'Harassment', '2026-06-03 20:00:00', 'Online - Social Media',
 'Posted derogatory and humiliating content about a fellow student on social media platforms.',
 'Verified', 4, '2026-06-04 08:00:00', 'Online Submission'),

('2026-012', 'Forgery of Academic Documents', 19,
 'Daniel Lopez', 'Male', 'Student',
 '2022-00012', 'daniel.lopez@clsu2.edu.ph', 'College of Business',
 'BS Business Administration', '4th Year', 'A',
 'Fraud', '2026-06-20 10:00:00', 'Registrar Office',
 'Submitted a falsified transcript of records with altered grades for graduate school admission.',
 'Resolved', 5, '2026-06-20 11:00:00', 'Online Submission'),

('2026-013', 'Attendance Fraud', 20,
 'Marcela Diaz', 'Female', 'Student',
 '2024-00013', 'marcela.diaz@clsu2.edu.ph', 'College of Science',
 'BS Chemistry', '2nd Year', 'A',
 'Academic Dishonesty', '2026-07-10 07:00:00', 'Chemistry Lecture Hall',
 'Used another students ID to mark attendance on multiple occasions throughout the semester.',
 'Submitted', 3, '2026-07-10 08:00:00', 'Online Submission'),

('2026-014', 'Destruction of Library Materials', 21,
 'Francisco Ramos', 'Male', 'Student',
 '2023-00014', 'francisco.ramos@clsu2.edu.ph', 'College of Arts',
 'AB English', '3rd Year', 'A',
 'Property Damage', '2026-08-05 14:00:00', 'University Library',
 'Deliberately tore pages from reference books and damaged library equipment during study session.',
 'Submitted', 4, '2026-08-05 15:00:00', 'Online Submission'),

('2026-015', 'Verbal Abuse of Faculty Member', 22,
 'Isabela Morales', 'Female', 'Student',
 '2025-00015', 'isabela.morales@clsu2.edu.ph', 'College of Engineering',
 'BS Computer Science', '1st Year', 'A',
 'Behavioral Misconduct', '2026-09-01 11:00:00', 'Room 108, Engineering Building',
 'Used offensive and disrespectful language toward a faculty member during a grade consultation.',
 'Submitted', 5, '2026-09-01 12:00:00', 'Online Submission')
ON DUPLICATE KEY UPDATE case_number = case_number;

-- =============================================================
-- COMPLAINTS - OLD / MIGRATED (15)   dates: 2024 - 2025
--
-- submitted_by is admin (id 1) - no student account linked.
-- =============================================================
INSERT INTO complaints (
    case_number, complaint_title, submitted_by_account_id,
    complainant_name, complainant_gender, complainant_type,
    complainant_student_no, complainant_email, complainant_college,
    complainant_course, complainant_year_level, complainant_section,
    case_classification, incident_datetime, incident_location,
    complaint_details, status, assigned_coordinator_account_id,
    submitted_at, case_source, original_case_date,
    legacy_outcome, action_taken, resolution_date,
    legacy_entry_source, outcome
) VALUES
('2024-001', 'Legacy: Cheating in Final Examination', 1,
 'Rica Aquino', 'Female', 'Student',
 '2020-01001', 'rica.aquino@clsu2.edu.ph', 'College of Science',
 'BS Biology', '4th Year', 'A',
 'Academic Dishonesty', '2024-01-15 08:00:00', 'Science Auditorium',
 'Caught copying answers from a neighbor during the final examination in Genetics.',
 'Resolved', NULL, '2024-01-15 09:00:00', 'Legacy', '2024-01-15',
 'Suspended for one semester', 'Student served suspension for one semester.', '2024-02-28',
 'Legacy System', 'Suspended for one semester effective second semester AY 2023-2024.'),

('2024-002', 'Legacy: Plagiarism in Undergraduate Thesis', 1,
 'Ricardo Vergara', 'Male', 'Student',
 '2020-01002', 'ricardo.vergara@clsu2.edu.ph', 'College of Engineering',
 'BS Computer Science', '4th Year', 'A',
 'Academic Dishonesty', '2024-02-20 10:00:00', 'Engineering Faculty Office',
 'Thesis document found to contain large sections copied from previously published theses without attribution.',
 'Resolved', NULL, '2024-02-20 11:00:00', 'Legacy', '2024-02-20',
 'Probation for one academic year', 'Placed on academic probation for the remainder of the program.', '2024-04-10',
 'Legacy System', 'Probation for one academic year with mandatory ethics seminar.'),

('2024-003', 'Legacy: Unauthorized Use of AI in Essay', 1,
 'Marissa Arceo', 'Female', 'Student',
 '2021-02003', 'marissa.arceo@clsu2.edu.ph', 'College of Business',
 'BS Business Administration', '3rd Year', 'B',
 'Academic Dishonesty', '2024-03-10 14:00:00', 'Online Submission',
 'Essay submitted was determined to be entirely generated by artificial intelligence tools.',
 'Resolved', NULL, '2024-03-10 15:00:00', 'Legacy', '2024-03-10',
 'Written warning', 'Issued formal written warning and required to resubmit original work.', '2024-03-25',
 'Legacy System', 'Written warning with requirement to complete academic integrity workshop.'),

('2024-004', 'Legacy: Destruction of Laboratory Equipment', 1,
 'Enrico Salazar', 'Male', 'Student',
 '2021-02004', 'enrico.salazar@clsu2.edu.ph', 'College of Science',
 'BS Chemistry', '3rd Year', 'A',
 'Property Damage', '2024-04-05 09:00:00', 'Chemistry Laboratory 1',
 'Intentionally broke expensive laboratory glassware and equipment during a practical exam.',
 'Resolved', NULL, '2024-04-05 10:00:00', 'Legacy', '2024-04-05',
 'Dismissed from program', 'Removed from the BS Chemistry program and transferred to general studies.', '2024-05-15',
 'Legacy System', 'Dismissed from BS Chemistry program; allowed to enroll in other programs.'),

('2024-005', 'Legacy: Bullying Incident', 1,
 'Teresa Magsaysay', 'Female', 'Student',
 '2022-03005', 'teresa.magsaysay@clsu2.edu.ph', 'College of Education',
 'BS Education', '2nd Year', 'A',
 'Harassment', '2024-05-18 11:00:00', 'Education Building Corridor',
 'Physically and verbally bullied a younger student on multiple occasions.',
 'Resolved', NULL, '2024-05-18 12:00:00', 'Legacy', '2024-05-18',
 'Probation for one semester', 'Placed on behavioral probation with mandatory counseling sessions.', '2024-06-20',
 'Legacy System', 'Probation for one semester with mandatory counseling.'),

('2024-006', 'Legacy: Theft from Dormitory', 1,
 'Alfredo Manalo', 'Male', 'Student',
 '2021-04006', 'alfredo.manalo@clsu2.edu.ph', 'College of Agriculture',
 'BS Agriculture', '3rd Year', 'B',
 'Theft', '2024-06-22 22:00:00', 'University Dormitory Room 412',
 'Stole personal belongings including electronic devices from roommates desk.',
 'Resolved', NULL, '2024-06-23 08:00:00', 'Legacy', '2024-06-22',
 'Suspended for two semesters', 'Suspended from university for two semesters with conditions for readmission.', '2024-08-10',
 'Legacy System', 'Suspended for two semesters; must complete community service before readmission.'),

('2024-007', 'Legacy: Academic Fraud', 1,
 'Violeta Sevilla', 'Female', 'Student',
 '2020-05007', 'violeta.sevilla@clsu2.edu.ph', 'College of Arts',
 'AB Psychology', '4th Year', 'A',
 'Fraud', '2024-08-10 09:00:00', 'Registrar Office',
 'Submitted fraudulent documents claiming completion of required internship hours.',
 'Resolved', NULL, '2024-08-10 10:00:00', 'Legacy', '2024-08-10',
 'Dismissed from university', 'Permanently dismissed from the university for systematic academic fraud.', '2024-09-15',
 'Legacy System', 'Permanent dismissal from the university.'),

('2024-008', 'Legacy: Vandalism of Campus Facilities', 1,
 'Fernando Lacson', 'Male', 'Student',
 '2022-06008', 'fernando.lacson@clsu2.edu.ph', 'College of Engineering',
 'BS Mechanical Engineering', '2nd Year', 'A',
 'Property Damage', '2024-09-15 17:00:00', 'Engineering Building Restroom',
 'Graffiti and deliberate damage to fixtures in the engineering building restroom.',
 'Resolved', NULL, '2024-09-16 08:00:00', 'Legacy', '2024-09-15',
 'Community service and written warning', 'Required 40 hours of community service and issued formal warning.', '2024-10-20',
 'Legacy System', '40 hours community service and written warning.'),

('2025-001', 'Legacy: Sexual Harassment Complaint', 1,
 'Gloriosa Pascual', 'Female', 'Student',
 '2021-07009', 'gloriosa.pascual@clsu2.edu.ph', 'College of Science',
 'BS Biology', '3rd Year', 'B',
 'Harassment', '2025-01-12 10:00:00', 'Science Building Hallway',
 'Made unwanted advances and inappropriate comments toward a fellow student.',
 'Resolved', NULL, '2025-01-12 11:00:00', 'Legacy', '2025-01-12',
 'Probation for one academic year', 'Placed on strict behavioral probation with no-contact order.', '2025-02-28',
 'Legacy System', 'Probation for one academic year with mandatory behavioral program.'),

('2025-002', 'Legacy: Examination Leak Involvement', 1,
 'Edgardo Suarez', 'Male', 'Student',
 '2020-08010', 'edgardo.suarez@clsu2.edu.ph', 'College of Business',
 'BS Business Administration', '4th Year', 'A',
 'Fraud', '2025-02-28 08:00:00', 'Business Faculty Office',
 'Distributed confidential examination materials to other students prior to scheduled exam.',
 'Resolved', NULL, '2025-02-28 09:00:00', 'Legacy', '2025-02-28',
 'Suspended for one semester', 'Suspended for one semester; barred from honors list.', '2025-04-10',
 'Legacy System', 'Suspended for one semester and removed from honors consideration.'),

('2025-003', 'Legacy: Falsification of Academic Records', 1,
 'Margarita Villanueva', 'Female', 'Student',
 '2021-09011', 'margarita.villanueva@clsu2.edu.ph', 'College of Education',
 'BS Education', '4th Year', 'A',
 'Fraud', '2025-03-15 09:00:00', 'Registrar Office',
 'Altered grades on official transcript using sophisticated forgery techniques.',
 'Resolved', NULL, '2025-03-15 10:00:00', 'Legacy', '2025-03-15',
 'Dismissed from program', 'Dismissed from the Education program for record falsification.', '2025-04-20',
 'Legacy System', 'Dismissed from BS Education program.'),

('2025-004', 'Legacy: Property Damage in Dormitory', 1,
 'Rogelio Esguerra', 'Male', 'Student',
 '2022-10012', 'rogelio.esguerra@clsu2.edu.ph', 'College of Agriculture',
 'BS Agriculture', '2nd Year', 'B',
 'Property Damage', '2025-04-20 21:00:00', 'University Dormitory Common Area',
 'Damaged common area furniture and appliances during a party.',
 'Resolved', NULL, '2025-04-21 08:00:00', 'Legacy', '2025-04-20',
 'Restitution and written warning', 'Required to pay for damages and issued formal warning.', '2025-05-15',
 'Legacy System', 'Full restitution of Php 8,500 and written warning.'),

('2025-005', 'Legacy: Verbal Threat to Fellow Student', 1,
 'Emilia Rendon', 'Female', 'Student',
 '2021-11013', 'emilia.rendon@clsu2.edu.ph', 'College of Arts',
 'AB English', '3rd Year', 'B',
 'Behavioral Misconduct', '2025-05-10 15:00:00', 'Arts Building Cafeteria',
 'Made repeated verbal threats of physical harm toward another student over a personal dispute.',
 'Resolved', NULL, '2025-05-10 16:00:00', 'Legacy', '2025-05-10',
 'Probation for one semester', 'Behavioral probation with mandatory anger management counseling.', '2025-06-10',
 'Legacy System', 'Probation for one semester with anger management program.'),

('2025-006', 'Legacy: Plagiarism in Research Essay', 1,
 'Armando Cuatreras', 'Male', 'Student',
 '2022-12014', 'armando.cuatreras@clsu2.edu.ph', 'College of Science',
 'BS Chemistry', '2nd Year', 'A',
 'Academic Dishonesty', '2025-07-05 10:00:00', 'Science Faculty Office',
 'Submitted a research essay with 78% similarity to published work detected through Turnitin.',
 'Resolved', NULL, '2025-07-05 11:00:00', 'Legacy', '2025-07-05',
 'Suspended for one semester', 'Suspended for one semester with mandatory academic integrity seminar.', '2025-08-15',
 'Legacy System', 'Suspended for one semester; must complete academic integrity course.'),

('2025-007', 'Legacy: Cheating in Laboratory Examination', 1,
 'Carlota Oliva', 'Female', 'Student',
 '2021-13015', 'carlota.oliva@clsu2.edu.ph', 'College of Engineering',
 'BS Computer Science', '3rd Year', 'A',
 'Academic Dishonesty', '2025-08-18 14:00:00', 'Computer Laboratory 4',
 'Used hidden notes and a mobile phone during the laboratory examination despite warnings.',
 'Resolved', NULL, '2025-08-18 15:00:00', 'Legacy', '2025-08-18',
 'Exonerated', 'Investigation concluded evidence was insufficient; student exonerated.', '2025-09-10',
 'Legacy System', 'Exonerated due to insufficient evidence.')
ON DUPLICATE KEY UPDATE case_number = case_number;
