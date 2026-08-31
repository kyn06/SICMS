-- SICMS test cases for manual testing of Resolve, Unresolve, Archive,
-- Return for Revision, Assign Coordinator, and the Archive success popup.
-- Submitter: student account_id 3   |  Actor/staff: account_id 4 (sdru-staff)
-- Run: mysql --host=127.0.0.1 --port=3307 --user=root sicms < seed_test_cases.sql

SET NAMES utf8mb4;

-- -------------------------------------------------------------------------
-- Case A: Verified  -> test Resolve, Return for Revision, Assign Coordinator
-- -------------------------------------------------------------------------
INSERT INTO complaints (
    case_number, complaint_title, submitted_by_account_id, complainant_name,
    complainant_gender, complainant_type, complainant_student_no, complainant_email,
    complainant_contact, complainant_college, complainant_course, complainant_year_level,
    complainant_section, case_classification, incident_datetime, incident_location,
    complaint_details, status, submitted_at, created_at, updated_at, case_source
) VALUES (
    'SDRU-20260831-0001', 'Academic Dishonesty in Major Exam', 3, 'Juan Dela Cruz',
    'Male', 'Student', '20-1234', 'juan.delacruz@clsu2.edu.ph', '09171234567',
    'College of Engineering', 'BS Civil Engineering', '3', '3A',
    'Academic Dishonesty', '2026-08-30 09:00:00', 'CE Building, Room 204',
    'Student was observed using unauthorized notes during a major examination. Proctor reported the incident and submitted a written report.',
    'Verified', '2026-08-31 08:00:00', '2026-08-31 08:00:00', '2026-08-31 08:30:00', 'Online Submission'
);

SET @caseA = LAST_INSERT_ID();

INSERT INTO case_history (complaint_id, action, previous_status, new_status, remarks, created_by_account_id, created_at)
VALUES
    (@caseA, 'Complaint Submitted', NULL, 'Submitted', 'Complaint filed online by the student.', 3, '2026-08-31 08:00:00'),
    (@caseA, 'Verified Complaint', 'Submitted', 'Verified', 'Evidence reviewed and complaint verified by SDRU staff.', 4, '2026-08-31 08:30:00');

-- -------------------------------------------------------------------------
-- Case B: Verified  -> test Resolve and Assign Coordinator
-- -------------------------------------------------------------------------
INSERT INTO complaints (
    case_number, complaint_title, submitted_by_account_id, complainant_name,
    complainant_gender, complainant_type, complainant_student_no, complainant_email,
    complainant_contact, complainant_college, complainant_course, complainant_year_level,
    complainant_section, case_classification, incident_datetime, incident_location,
    complaint_details, status, submitted_at, created_at, updated_at, case_source
) VALUES (
    'SDRU-20260831-0002', 'Physical Altercation in Cafeteria', 3, 'Maria Santos',
    'Female', 'Student', '19-5678', 'maria.santos@clsu2.edu.ph', '09172345678',
    'College of Agriculture', 'BS Agriculture', '4', '4B',
    'Physical Altercation', '2026-08-29 12:30:00', 'Main Cafeteria',
    'Two students engaged in a physical altercation near the cafeteria entrance. Security personnel intervened and separated the parties.',
    'Verified', '2026-08-31 09:15:00', '2026-08-31 09:15:00', '2026-08-31 09:45:00', 'Online Submission'
);

SET @caseB = LAST_INSERT_ID();

INSERT INTO case_history (complaint_id, action, previous_status, new_status, remarks, created_by_account_id, created_at)
VALUES
    (@caseB, 'Complaint Submitted', NULL, 'Submitted', 'Complaint filed online by the student.', 3, '2026-08-31 09:15:00'),
    (@caseB, 'Verified Complaint', 'Submitted', 'Verified', 'CCTV footage and witness statements reviewed.', 4, '2026-08-31 09:45:00');

-- -------------------------------------------------------------------------
-- Case C: Resolved  -> test Unresolve (has outcome displayed)
-- -------------------------------------------------------------------------
INSERT INTO complaints (
    case_number, complaint_title, submitted_by_account_id, complainant_name,
    complainant_gender, complainant_type, complainant_student_no, complainant_email,
    complainant_contact, complainant_college, complainant_course, complainant_year_level,
    complainant_section, case_classification, incident_datetime, incident_location,
    complaint_details, status, outcome, submitted_at, created_at, updated_at, case_source
) VALUES (
    'SDRU-20260831-0003', 'Theft of Personal Property', 3, 'Pedro Reyes',
    'Male', 'Student', '18-9012', 'pedro.reyes@clsu2.edu.ph', '09173456789',
    'College of Veterinary Medicine', 'BS Veterinary Medicine', '2', '2C',
    'Theft', '2026-08-28 16:00:00', 'Library, 2nd Floor',
    'Student reported a missing backpack containing personal items while studying in the library.',
    'Resolved', 'The recovered property was returned to the complainant. The respondent issued a formal written apology and agreed to restitution. Both parties signed a settlement agreement.',
    '2026-08-31 10:00:00', '2026-08-31 10:00:00', '2026-08-31 11:20:00', 'Online Submission'
);

SET @caseC = LAST_INSERT_ID();

INSERT INTO case_history (complaint_id, action, previous_status, new_status, remarks, created_by_account_id, created_at)
VALUES
    (@caseC, 'Complaint Submitted', NULL, 'Submitted', 'Complaint filed online by the student.', 3, '2026-08-31 10:00:00'),
    (@caseC, 'Verified Complaint', 'Submitted', 'Verified', 'All checks completed.', 4, '2026-08-31 10:30:00'),
    (@caseC, 'Resolved Case', 'Verified', 'Resolved', 'Outcome: The recovered property was returned to the complainant. The respondent issued a formal written apology and agreed to restitution. Both parties signed a settlement agreement.', 4, '2026-08-31 11:20:00');

-- -------------------------------------------------------------------------
-- Case D: Resolved  -> test Archive (triggers success popup + redirect)
-- -------------------------------------------------------------------------
INSERT INTO complaints (
    case_number, complaint_title, submitted_by_account_id, complainant_name,
    complainant_gender, complainant_type, complainant_student_no, complainant_email,
    complainant_contact, complainant_college, complainant_course, complainant_year_level,
    complainant_section, case_classification, incident_datetime, incident_location,
    complaint_details, status, outcome, submitted_at, created_at, updated_at, case_source
) VALUES (
    'SDRU-20260831-0004', 'Verbal Harassment in Dormitory', 3, 'Ana Flores',
    'Female', 'Student', '21-3456', 'ana.flores@clsu2.edu.ph', '09174567890',
    'College of Arts and Sciences', 'BS Psychology', '1', '1A',
    'Intimidation, Threat and Harassment', '2026-08-27 21:00:00', 'Dormitory 2, Room 110',
    'Student reported repeated verbal harassment from a housemate within the dormitory.',
    'Resolved', 'The respondent was counselled by the SDRU and issued a formal warning. A no-contact arrangement was agreed upon between the parties.',
    '2026-08-31 11:00:00', '2026-08-31 11:00:00', '2026-08-31 12:10:00', 'Online Submission'
);

SET @caseD = LAST_INSERT_ID();

INSERT INTO case_history (complaint_id, action, previous_status, new_status, remarks, created_by_account_id, created_at)
VALUES
    (@caseD, 'Complaint Submitted', NULL, 'Submitted', 'Complaint filed online by the student.', 3, '2026-08-31 11:00:00'),
    (@caseD, 'Verified Complaint', 'Submitted', 'Verified', 'Interviews with dormitory staff completed.', 4, '2026-08-31 11:30:00'),
    (@caseD, 'Resolved Case', 'Verified', 'Resolved', 'Outcome: The respondent was counselled by the SDRU and issued a formal warning. A no-contact arrangement was agreed upon between the parties.', 4, '2026-08-31 12:10:00');

-- -------------------------------------------------------------------------
-- Case E: Submitted  -> test Return for Revision and Assign Coordinator
-- -------------------------------------------------------------------------
INSERT INTO complaints (
    case_number, complaint_title, submitted_by_account_id, complainant_name,
    complainant_gender, complainant_type, complainant_student_no, complainant_email,
    complainant_contact, complainant_college, complainant_course, complainant_year_level,
    complainant_section, case_classification, incident_datetime, incident_location,
    complaint_details, status, submitted_at, created_at, updated_at, case_source
) VALUES (
    'SDRU-20260831-0005', 'Unauthorized Use of School Equipment', 3, 'Ramon Garcia',
    'Male', 'Student', '17-7890', 'ramon.garcia@clsu2.edu.ph', '09175678901',
    'College of Information and Communications Technology', 'BS Information Technology', '3', '3D',
    'Property and Equipment', '2026-08-26 14:00:00', 'ICT Laboratory',
    'Student allegedly used laboratory equipment outside of approved hours without authorization.',
    'Submitted', '2026-08-31 13:00:00', '2026-08-31 13:00:00', '2026-08-31 13:00:00', 'Online Submission'
);

SET @caseE = LAST_INSERT_ID();

INSERT INTO case_history (complaint_id, action, previous_status, new_status, remarks, created_by_account_id, created_at)
VALUES
    (@caseE, 'Complaint Submitted', NULL, 'Submitted', 'Complaint filed online by the student.', 3, '2026-08-31 13:00:00');
