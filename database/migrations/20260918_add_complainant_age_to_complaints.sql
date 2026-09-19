-- Add complainant age to the complaints table for the revised complaint form.
ALTER TABLE complaints
    ADD COLUMN complainant_age TINYINT UNSIGNED DEFAULT NULL AFTER complainant_gender;