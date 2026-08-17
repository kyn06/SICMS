CREATE TABLE IF NOT EXISTS hearings (
    hearing_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    scheduled_by_account_id INT NOT NULL,
    hearing_datetime DATETIME NOT NULL,
    venue VARCHAR(255) NOT NULL,
    google_meet_link VARCHAR(255) NULL,
    remarks TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Scheduled',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_hearings_complaint
        FOREIGN KEY (complaint_id)
        REFERENCES complaints(complaint_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);
