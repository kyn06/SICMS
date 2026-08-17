-- Complainant metadata for student and private-individual submissions.
ALTER TABLE complaints
    ADD COLUMN complainant_type VARCHAR(30) NOT NULL DEFAULT 'Student' AFTER submitted_by_account_id,
    ADD COLUMN complainant_relationship VARCHAR(100) NULL AFTER complainant_name,
    ADD COLUMN complainant_course VARCHAR(255) NULL AFTER complainant_college,
    ADD COLUMN complainant_year_level VARCHAR(30) NULL AFTER complainant_course,
    ADD COLUMN complainant_section VARCHAR(30) NULL AFTER complainant_year_level;

ALTER TABLE complaints
    ADD COLUMN complainant_employee_no VARCHAR(50) NULL AFTER complainant_relationship,
    ADD COLUMN complainant_department VARCHAR(150) NULL AFTER complainant_employee_no,
    ADD COLUMN complainant_position VARCHAR(100) NULL AFTER complainant_department,
    ADD COLUMN complainant_affiliation VARCHAR(150) NULL AFTER complainant_position,
    ADD COLUMN complainant_purpose VARCHAR(255) NULL AFTER complainant_affiliation;

-- Existing complaints were submitted through the student-only form.
UPDATE complaints SET complainant_type = 'Student' WHERE complainant_type IS NULL OR complainant_type = '';
