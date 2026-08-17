-- Selective complaint revision metadata. Run once against the SICMS database.
ALTER TABLE complaints
    ADD COLUMN complaint_title VARCHAR(255) NULL AFTER case_number;

ALTER TABLE case_history
    ADD COLUMN revision_fields TEXT NULL AFTER remarks;

-- Preserve revisability for return events created before selective fields existed.
UPDATE case_history
SET revision_fields = '["complaint_title","complaint_details","incident_date","incident_time","incident_location","respondents","witnesses","evidence"]'
WHERE new_status = 'Returned for Revision' AND revision_fields IS NULL;
