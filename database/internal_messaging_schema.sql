CREATE TABLE IF NOT EXISTS case_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    sender_account_id INT NOT NULL,
    receiver_account_id INT NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
);
