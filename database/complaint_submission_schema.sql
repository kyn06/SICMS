CREATE TABLE IF NOT EXISTS complaints (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,
    case_number VARCHAR(30) NOT NULL UNIQUE,
    submitted_by_account_id INT NOT NULL,
    complainant_type VARCHAR(30) NOT NULL DEFAULT 'Student',
    complainant_name VARCHAR(150) NOT NULL,
    complainant_relationship VARCHAR(100) NULL,
    complainant_employee_no VARCHAR(50) NULL,
    complainant_department VARCHAR(150) NULL,
    complainant_position VARCHAR(100) NULL,
    complainant_affiliation VARCHAR(150) NULL,
    complainant_purpose VARCHAR(255) NULL,
    complainant_student_no VARCHAR(50) NULL,
    complainant_email VARCHAR(150) NOT NULL,
    complainant_contact VARCHAR(50) NOT NULL,
    complainant_college VARCHAR(150) NULL,
    complainant_course VARCHAR(255) NULL,
    complainant_year_level VARCHAR(30) NULL,
    complainant_section VARCHAR(30) NULL,
    complainant_course_year VARCHAR(150) NULL,
    case_classification VARCHAR(100) NOT NULL,
    incident_datetime DATETIME NOT NULL,
    incident_location VARCHAR(255) NOT NULL,
    complaint_details TEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Submitted',
    submitted_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_complaints_submitted_by
        FOREIGN KEY (submitted_by_account_id)
        REFERENCES accounts(account_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS complaint_respondents (
    respondent_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    student_no VARCHAR(50) NULL,
    college VARCHAR(150) NULL,
    course_year VARCHAR(150) NULL,
    contact_info VARCHAR(150) NULL,
    details TEXT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_complaint_respondents_complaint
        FOREIGN KEY (complaint_id)
        REFERENCES complaints(complaint_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS complaint_witnesses (
    witness_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    student_no VARCHAR(50) NULL,
    contact_info VARCHAR(150) NULL,
    statement TEXT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_complaint_witnesses_complaint
        FOREIGN KEY (complaint_id)
        REFERENCES complaints(complaint_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS complaint_evidence (
    evidence_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at DATETIME NOT NULL,
    CONSTRAINT fk_complaint_evidence_complaint
        FOREIGN KEY (complaint_id)
        REFERENCES complaints(complaint_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);
