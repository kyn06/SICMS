CREATE TABLE IF NOT EXISTS audit_logs (
    audit_log_id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NULL,
    user_name VARCHAR(150) NULL,
    user_role VARCHAR(100) NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_audit_logs_account_id (account_id),
    INDEX idx_audit_logs_action (action),
    INDEX idx_audit_logs_user_role (user_role),
    INDEX idx_audit_logs_created_at (created_at)
);
