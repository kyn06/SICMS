-- Add person detail columns (age, birthday) to the complaint respondent and
-- witness tables for the revised complaint form.
-- NOTE: complaint_respondents.email/address already exist in the live schema.
ALTER TABLE complaint_respondents
    ADD COLUMN age TINYINT UNSIGNED DEFAULT NULL AFTER gender,
    ADD COLUMN birthday DATE DEFAULT NULL AFTER age;

ALTER TABLE complaint_witnesses
    ADD COLUMN age TINYINT UNSIGNED DEFAULT NULL AFTER gender,
    ADD COLUMN birthday DATE DEFAULT NULL AFTER age;