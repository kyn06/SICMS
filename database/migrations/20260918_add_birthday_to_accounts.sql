-- SICMS Birthday / Date of Birth
-- Adds an optional birthday column to the accounts table so students can
-- record and update their own date of birth from their profile settings.

ALTER TABLE accounts
    ADD COLUMN birthday DATE DEFAULT NULL AFTER gender;