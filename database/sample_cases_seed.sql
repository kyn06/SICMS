-- SICMS sample case records for demo/testing.
-- Run this in phpMyAdmin after the accounts, complaints, case_history,
-- hearings, notifications, and case_messages tables already exist.
--
-- The script picks an existing student account and an existing SDRU staff/coordinator/head account.
-- If you have no student account yet, create one in the system first.

START TRANSACTION;

SET @student_id := (
    SELECT account_id
    FROM accounts
    WHERE LOWER(REPLACE(REPLACE(role, '_', '-'), ' ', '-')) = 'student'
      AND status = 'active'
    ORDER BY account_id
    LIMIT 1
);

SET @staff_id := (
    SELECT account_id
    FROM accounts
    WHERE LOWER(REPLACE(REPLACE(role, '_', '-'), ' ', '-')) IN ('coordinator', 'sdru-staff', 'sdr-staff', 'head-of-sdru', 'sdru-head', 'admin', 'super-admin')
      AND status = 'active'
    ORDER BY FIELD(LOWER(REPLACE(REPLACE(role, '_', '-'), ' ', '-')), 'coordinator', 'sdru-staff', 'sdr-staff', 'head-of-sdru', 'sdru-head', 'admin', 'super-admin'), account_id
    LIMIT 1
);

SET @actor_id := COALESCE(@staff_id, @student_id);

DELETE FROM case_messages
WHERE complaint_id IN (
    SELECT complaint_id
    FROM complaints
    WHERE case_number IN ('SDRU-2026-S001', 'SDRU-2026-S002', 'SDRU-2026-S003', 'SDRU-2026-S004', 'SDRU-2026-S005', 'SDRU-2026-S006')
);

DELETE FROM notifications
WHERE message LIKE '%SDRU-2026-S00%';

DELETE FROM complaints
WHERE case_number IN ('SDRU-2026-S001', 'SDRU-2026-S002', 'SDRU-2026-S003', 'SDRU-2026-S004', 'SDRU-2026-S005', 'SDRU-2026-S006');

INSERT IGNORE INTO complaints (
    case_number,
    submitted_by_account_id,
    complainant_name,
    complainant_student_no,
    complainant_email,
    complainant_contact,
    complainant_college,
    complainant_course_year,
    case_classification,
    incident_datetime,
    incident_location,
    complaint_details,
    status,
    assigned_coordinator_account_id,
    submitted_at,
    created_at,
    updated_at
) VALUES
('SDRU-2026-S001', @student_id, 'Maria Santos', '2022-00123', 'maria.santos@student.example', '09171230001', 'College of Engineering', 'BSIT 3rd Year', 'Bullying', '2026-07-01 10:30:00', 'Main Library hallway', 'The respondent repeatedly mocked and intimidated the complainant after class.', 'Submitted', NULL, '2026-07-01 14:10:00', '2026-07-01 14:10:00', '2026-07-01 14:10:00'),
('SDRU-2026-S002', @student_id, 'Maria Santos', '2022-00123', 'maria.santos@student.example', '09171230001', 'College of Engineering', 'BSIT 3rd Year', 'Harassment', '2026-06-24 15:20:00', 'Student Center', 'The complainant reported repeated unwanted messages and confrontations.', 'Verified', @staff_id, '2026-06-25 09:00:00', '2026-06-25 09:00:00', '2026-06-26 11:30:00'),
('SDRU-2026-S003', @student_id, 'Maria Santos', '2022-00123', 'maria.santos@student.example', '09171230001', 'College of Engineering', 'BSIT 3rd Year', 'Physical Misconduct', '2026-06-10 12:10:00', 'Gymnasium entrance', 'A pushing incident occurred during an argument near the gymnasium entrance.', 'Resolved', @staff_id, '2026-06-10 16:45:00', '2026-06-10 16:45:00', '2026-06-30 13:00:00'),
('SDRU-2026-S004', @student_id, 'Maria Santos', '2022-00123', 'maria.santos@student.example', '09171230001', 'College of Engineering', 'BSIT 3rd Year', 'Academic Dishonesty', '2026-05-18 08:00:00', 'Room CE-204', 'A classmate allegedly submitted copied work and involved the complainant without consent.', 'Returned for Revision', NULL, '2026-05-18 11:20:00', '2026-05-18 11:20:00', '2026-05-19 10:10:00'),
('SDRU-2026-S005', @student_id, 'Maria Santos', '2022-00123', 'maria.santos@student.example', '09171230001', 'College of Engineering', 'BSIT 3rd Year', 'Property Damage', '2026-04-29 17:40:00', 'Dormitory study area', 'The complainant reported damage to a borrowed laptop charger.', 'Rejected', NULL, '2026-04-30 08:50:00', '2026-04-30 08:50:00', '2026-05-02 09:15:00'),
('SDRU-2026-S006', @student_id, 'Maria Santos', '2022-00123', 'maria.santos@student.example', '09171230001', 'College of Engineering', 'BSIT 3rd Year', 'Verbal Misconduct', '2026-07-07 13:15:00', 'Cafeteria', 'The respondent allegedly shouted offensive remarks in front of other students.', 'Verified', @staff_id, '2026-07-07 15:30:00', '2026-07-07 15:30:00', '2026-07-08 10:40:00');

SET @case1 := (SELECT complaint_id FROM complaints WHERE case_number = 'SDRU-2026-S001' LIMIT 1);
SET @case2 := (SELECT complaint_id FROM complaints WHERE case_number = 'SDRU-2026-S002' LIMIT 1);
SET @case3 := (SELECT complaint_id FROM complaints WHERE case_number = 'SDRU-2026-S003' LIMIT 1);
SET @case4 := (SELECT complaint_id FROM complaints WHERE case_number = 'SDRU-2026-S004' LIMIT 1);
SET @case5 := (SELECT complaint_id FROM complaints WHERE case_number = 'SDRU-2026-S005' LIMIT 1);
SET @case6 := (SELECT complaint_id FROM complaints WHERE case_number = 'SDRU-2026-S006' LIMIT 1);

INSERT INTO complaint_respondents (complaint_id, full_name, student_no, college, course_year, contact_info, details, created_at)
SELECT @case1, 'Juan Dela Cruz', '2021-00456', 'College of Engineering', 'BSIT 4th Year', 'juan.dc@student.example', 'Primary respondent named by complainant.', '2026-07-01 14:10:00' WHERE @case1 IS NOT NULL
UNION ALL SELECT @case2, 'Carlo Reyes', '2020-00219', 'College of Arts and Social Sciences', 'BA Communication 4th Year', 'carlo.reyes@student.example', 'Reported sender of repeated messages.', '2026-06-25 09:00:00' WHERE @case2 IS NOT NULL
UNION ALL SELECT @case3, 'Paolo Mendoza', '2021-00888', 'College of Education', 'BSEd 3rd Year', 'paolo.mendoza@student.example', 'Involved in physical altercation.', '2026-06-10 16:45:00' WHERE @case3 IS NOT NULL
UNION ALL SELECT @case4, 'Ana Villanueva', '2022-00611', 'College of Engineering', 'BSIT 3rd Year', 'ana.v@student.example', 'Classmate identified in academic complaint.', '2026-05-18 11:20:00' WHERE @case4 IS NOT NULL
UNION ALL SELECT @case5, 'Miguel Ramos', '2023-00105', 'College of Business and Accountancy', 'BSBA 2nd Year', 'miguel.r@student.example', 'Borrowed item before reported damage.', '2026-04-30 08:50:00' WHERE @case5 IS NOT NULL
UNION ALL SELECT @case6, 'Lara Gomez', '2021-00730', 'College of Agriculture', 'BSA 4th Year', 'lara.g@student.example', 'Named in verbal misconduct report.', '2026-07-07 15:30:00' WHERE @case6 IS NOT NULL;

INSERT INTO complaint_witnesses (complaint_id, full_name, student_no, contact_info, statement, created_at)
SELECT @case1, 'Bianca Cruz', '2022-00991', 'bianca.cruz@student.example', 'Witnessed the respondent blocking the complainant in the hallway.', '2026-07-01 14:10:00' WHERE @case1 IS NOT NULL
UNION ALL SELECT @case2, 'Rafael Lim', '2021-00344', 'rafael.lim@student.example', 'Saw the confrontation near the Student Center entrance.', '2026-06-25 09:00:00' WHERE @case2 IS NOT NULL
UNION ALL SELECT @case3, 'Nina Torres', '2020-00555', 'nina.torres@student.example', 'Observed the argument before the pushing incident.', '2026-06-10 16:45:00' WHERE @case3 IS NOT NULL
UNION ALL SELECT @case4, 'Mark Aquino', '2022-00017', 'mark.aquino@student.example', 'Confirmed group work submission timeline.', '2026-05-18 11:20:00' WHERE @case4 IS NOT NULL
UNION ALL SELECT @case5, 'Erika Flores', '2023-00245', 'erika.flores@student.example', 'Was present when the item was returned.', '2026-04-30 08:50:00' WHERE @case5 IS NOT NULL
UNION ALL SELECT @case6, 'Trisha Lopez', '2021-00920', 'trisha.lopez@student.example', 'Heard offensive remarks in the cafeteria.', '2026-07-07 15:30:00' WHERE @case6 IS NOT NULL;

INSERT INTO complaint_evidence (complaint_id, original_filename, stored_filename, file_path, mime_type, file_size, uploaded_at)
SELECT @case1, 'hallway_statement.pdf', 'sample_hallway_statement.pdf', 'storage/evidence/sample_hallway_statement.pdf', 'application/pdf', 128000, '2026-07-01 14:10:00' WHERE @case1 IS NOT NULL
UNION ALL SELECT @case2, 'message_screenshot.png', 'sample_message_screenshot.png', 'storage/evidence/sample_message_screenshot.png', 'image/png', 342000, '2026-06-25 09:00:00' WHERE @case2 IS NOT NULL
UNION ALL SELECT @case3, 'gym_incident_photo.jpg', 'sample_gym_incident_photo.jpg', 'storage/evidence/sample_gym_incident_photo.jpg', 'image/jpeg', 455000, '2026-06-10 16:45:00' WHERE @case3 IS NOT NULL
UNION ALL SELECT @case4, 'submission_copy.docx', 'sample_submission_copy.docx', 'storage/evidence/sample_submission_copy.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 88000, '2026-05-18 11:20:00' WHERE @case4 IS NOT NULL
UNION ALL SELECT @case5, 'charger_photo.jpg', 'sample_charger_photo.jpg', 'storage/evidence/sample_charger_photo.jpg', 'image/jpeg', 219000, '2026-04-30 08:50:00' WHERE @case5 IS NOT NULL
UNION ALL SELECT @case6, 'cafeteria_statement.pdf', 'sample_cafeteria_statement.pdf', 'storage/evidence/sample_cafeteria_statement.pdf', 'application/pdf', 101000, '2026-07-07 15:30:00' WHERE @case6 IS NOT NULL;

INSERT INTO case_history (complaint_id, action, previous_status, new_status, remarks, assigned_coordinator_account_id, created_by_account_id, created_at)
SELECT @case1, 'Submitted Complaint', NULL, 'Submitted', 'Complaint submitted by student.', NULL, @student_id, '2026-07-01 14:10:00' WHERE @case1 IS NOT NULL
UNION ALL SELECT @case2, 'Submitted Complaint', NULL, 'Submitted', 'Complaint submitted by student.', NULL, @student_id, '2026-06-25 09:00:00' WHERE @case2 IS NOT NULL
UNION ALL SELECT @case2, 'Verified Complaint', 'Submitted', 'Verified', 'Initial review completed and complaint verified.', NULL, @actor_id, '2026-06-26 11:30:00' WHERE @case2 IS NOT NULL
UNION ALL SELECT @case2, 'Assigned Coordinator', 'Verified', 'Verified', 'Assigned for coordination and monitoring.', @staff_id, @actor_id, '2026-06-26 11:45:00' WHERE @case2 IS NOT NULL
UNION ALL SELECT @case3, 'Submitted Complaint', NULL, 'Submitted', 'Complaint submitted by student.', NULL, @student_id, '2026-06-10 16:45:00' WHERE @case3 IS NOT NULL
UNION ALL SELECT @case3, 'Verified Complaint', 'Submitted', 'Verified', 'Complaint verified for formal action.', NULL, @actor_id, '2026-06-11 09:20:00' WHERE @case3 IS NOT NULL
UNION ALL SELECT @case3, 'Resolved Case', 'Verified', 'Resolved', 'Parties completed conference and case was resolved.', @staff_id, @actor_id, '2026-06-30 13:00:00' WHERE @case3 IS NOT NULL
UNION ALL SELECT @case4, 'Returned for Revision', 'Submitted', 'Returned for Revision', 'Student was asked to clarify details and attach additional evidence.', NULL, @actor_id, '2026-05-19 10:10:00' WHERE @case4 IS NOT NULL
UNION ALL SELECT @case5, 'Rejected Complaint', 'Submitted', 'Rejected', 'Insufficient basis after initial review.', NULL, @actor_id, '2026-05-02 09:15:00' WHERE @case5 IS NOT NULL
UNION ALL SELECT @case6, 'Verified Complaint', 'Submitted', 'Verified', 'Complaint verified for conference scheduling.', @staff_id, @actor_id, '2026-07-08 10:40:00' WHERE @case6 IS NOT NULL;

INSERT INTO hearings (complaint_id, scheduled_by_account_id, hearing_datetime, venue, google_meet_link, remarks, status, created_at, updated_at)
SELECT @case2, @actor_id, '2026-07-20 09:30:00', 'SDRU Conference Room', 'https://meet.google.com/sample-sdru-002', 'Initial conference with complainant and respondent.', 'Scheduled', NOW(), NOW() WHERE @case2 IS NOT NULL
UNION ALL SELECT @case3, @actor_id, '2026-06-18 13:30:00', 'OSA Meeting Room', NULL, 'Completed reformation conference.', 'Completed', '2026-06-12 10:00:00', '2026-06-18 15:00:00' WHERE @case3 IS NOT NULL
UNION ALL SELECT @case6, @actor_id, '2026-07-22 14:00:00', 'SDRU Conference Room', NULL, 'Clarificatory meeting for verbal misconduct complaint.', 'Scheduled', NOW(), NOW() WHERE @case6 IS NOT NULL;

INSERT INTO notifications (account_id, type, title, message, link, is_read, created_at)
SELECT @student_id, 'complaint_verified', 'Complaint Verified', 'Case SDRU-2026-S002 has been verified.', CONCAT('web/views/complaints/case_details.php?id=', @case2), 0, '2026-06-26 11:30:00' WHERE @case2 IS NOT NULL
UNION ALL SELECT @student_id, 'hearing_scheduled', 'Hearing Scheduled', 'A hearing has been scheduled for case SDRU-2026-S002.', CONCAT('web/views/complaints/case_details.php?id=', @case2), 0, NOW() WHERE @case2 IS NOT NULL
UNION ALL SELECT @student_id, 'case_resolved', 'Case Resolved', 'Case SDRU-2026-S003 has been resolved.', CONCAT('web/views/complaints/case_details.php?id=', @case3), 1, '2026-06-30 13:00:00' WHERE @case3 IS NOT NULL
UNION ALL SELECT @student_id, 'case_status_updated', 'Case Status Updated', 'Case SDRU-2026-S004 was returned for revision.', CONCAT('web/views/complaints/case_details.php?id=', @case4), 0, '2026-05-19 10:10:00' WHERE @case4 IS NOT NULL;

INSERT INTO case_messages (complaint_id, sender_account_id, receiver_account_id, message, attachment, is_read, created_at, updated_at)
SELECT @case2, @actor_id, @student_id, 'Good day. Your complaint has been verified. Please check the hearing schedule once posted.', NULL, 0, '2026-06-26 12:00:00', '2026-06-26 12:00:00' WHERE @case2 IS NOT NULL
UNION ALL SELECT @case2, @student_id, @actor_id, 'Thank you for the update. I will wait for the schedule.', NULL, 1, '2026-06-26 12:15:00', '2026-06-26 12:15:00' WHERE @case2 IS NOT NULL
UNION ALL SELECT @case6, @actor_id, @student_id, 'Please prepare your witness statement before the scheduled meeting.', NULL, 0, NOW(), NOW() WHERE @case6 IS NOT NULL;

COMMIT;
