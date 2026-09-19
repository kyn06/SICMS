-- Backfill complainant age for existing online-submitted cases whose submitting
-- student account already has a Date of Birth in the profile.
UPDATE complaints c
JOIN accounts a ON c.submitted_by_account_id = a.account_id
SET c.complainant_age = TIMESTAMPDIFF(YEAR, a.birthday, CURDATE())
    - (DATE_ADD(a.birthday, INTERVAL TIMESTAMPDIFF(YEAR, a.birthday, CURDATE()) YEAR) > CURDATE())
WHERE c.complainant_age IS NULL
  AND a.birthday IS NOT NULL
  AND a.birthday <= CURDATE()
  AND COALESCE(c.case_source, 'Online Submission') <> 'Legacy';