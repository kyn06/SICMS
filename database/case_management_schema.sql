ALTER TABLE complaints
    ADD COLUMN assigned_coordinator_account_id INT NULL AFTER status;

CREATE TABLE IF NOT EXISTS case_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    previous_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NULL,
    remarks TEXT NULL,
    revision_fields TEXT NULL,
    assigned_coordinator_account_id INT NULL,
    created_by_account_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_case_history_complaint
        FOREIGN KEY (complaint_id)
        REFERENCES complaints(complaint_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);
