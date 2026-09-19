-- SICMS Case Updates (internal SDRU records only)
-- Adds a dedicated table for staff/coordinator/head case updates so that
-- the original complaint and its evidence are never overwritten.

CREATE TABLE IF NOT EXISTS case_updates (
    update_id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id         INT UNSIGNED NOT NULL,
    author_account_id    INT UNSIGNED NOT NULL,
    update_type          VARCHAR(50)  NOT NULL,
    details              TEXT,
    case_status_snapshot VARCHAR(50)  NOT NULL,
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (update_id),
    KEY idx_case_updates_complaint (complaint_id),
    KEY idx_case_updates_author (author_account_id),
    CONSTRAINT fk_case_updates_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_case_updates_author
        FOREIGN KEY (author_account_id) REFERENCES accounts (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE complaint_evidence
    ADD COLUMN update_id INT UNSIGNED DEFAULT NULL AFTER complaint_id,
    ADD KEY idx_evidence_update (update_id),
    ADD CONSTRAINT fk_evidence_update
        FOREIGN KEY (update_id) REFERENCES case_updates (update_id)
        ON DELETE CASCADE;