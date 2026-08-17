ALTER TABLE case_messages
    ADD COLUMN attachment VARCHAR(255) NULL AFTER message,
    ADD COLUMN updated_at DATETIME NULL AFTER created_at;
