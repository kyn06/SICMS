-- =============================================================
-- SICMS upgrade: Google Calendar integration for hearings
-- Run once against an existing database, in addition to the
-- changes already present in sicms.sql:
--   1. Adds google_event_id to the hearings table.
--   2. Creates the system_settings key/value table.
-- =============================================================

USE sicms;

ALTER TABLE hearings
    ADD COLUMN google_event_id VARCHAR(1024) DEFAULT NULL AFTER google_meet_link;

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key   VARCHAR(100) NOT NULL,
    setting_value TEXT,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;