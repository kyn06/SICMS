<?php
require_once __DIR__ . '/../../controllers/ComplaintController.php';
require_once __DIR__ . '/../../helpers/Colleges.php';
require_once __DIR__ . '/../../helpers/Courses.php';
require_once __DIR__ . '/../../helpers/ProfileCompletion.php';

$controller = new ComplaintController();
$viewData = $controller->handleCreateRequest();

$user = $viewData['user'];
$errors = $viewData['errors'];
$fieldErrors = $viewData['fieldErrors'] ?? [];
$old = $viewData['old'];
$success = $viewData['success'];
$submissionToken = $viewData['submissionToken'];
$complaintDraft = $viewData['complaintDraft'] ?? null;
$restoringDraft = !empty($viewData['restoringDraft']);
$oldIncident = !empty($old['incident_datetime']) ? strtotime($old['incident_datetime']) : false;
$successCaseNumber = $success && preg_match('/Case Number:\s*([^\s]+)/', $success, $caseMatch) ? $caseMatch[1] : '';
$complainantProgram = Courses::split($old['complainant_course_year'] ?? '');
$complainantCourseSource = trim((string) ($old['complainant_course'] ?? ''));
if ($complainantCourseSource === '') $complainantCourseSource = trim((string) ($complainantProgram['course'] ?? ''));
if ($complainantCourseSource === '') $complainantCourseSource = trim((string) ($user['course'] ?? ''));
$complainantCourse = Courses::canonical($complainantCourseSource);
$complainantSection = trim((string) ($old['complainant_section'] ?? ''));
if ($complainantSection === '') $complainantSection = trim((string) ($complainantProgram['section'] ?? ''));
if ($complainantSection === '') $complainantSection = trim((string) ($user['section'] ?? ''));
$complainantType = $old['complainant_type'] ?? 'Student';
$complainantName = $complainantType === 'Student' ? trim($user['first_name'] . ' ' . $user['last_name']) : ($old['complainant_name'] ?? '');
$complainantGender = $old['complainant_gender'] ?? ($complainantType === 'Student' ? trim($user['gender'] ?? '') : '');
$complainantStudentNo = $old['complainant_student_no'] ?? ($user['student_number'] ?? '');
$complainantCollege = $old['complainant_college'] ?? ($user['college'] ?? '');
$complainantContact = $old['complainant_contact'] ?? ($user['phone_number'] ?? '');
$complainantAge = $old['complainant_age'] ?? null;
if ($complainantAge === '') {
    $complainantAge = null;
}
if ($complainantAge === null && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($user['birthday'] ?? ''))) {
    $birthday = DateTime::createFromFormat('Y-m-d', $user['birthday']);
    $today = new DateTime();
    if ($birthday && $birthday <= $today) {
        $complainantAge = (int) $today->diff($birthday)->y;
    }
}
$complainantAge = $complainantAge === null ? '' : (int) $complainantAge;

$profileIncomplete = ProfileCompletion::isStudentAccount($user) && !ProfileCompletion::isComplete($user);
$profileMissingFields = ProfileCompletion::isStudentAccount($user) ? ProfileCompletion::missingFields($user) : [];

function old_value($old, $key, $default = '') {
    return htmlspecialchars($old[$key] ?? $default);
}

function type_visible($types, $current) {
    return in_array($current, array_map('trim', explode(',', (string) $types)), true);
}

function type_field_hidden($types, $current) {
    return type_visible($types, $current) ? '' : 'hidden';
}

function type_field_disabled($types, $current) {
    return type_visible($types, $current) ? '' : 'disabled';
}

function old_array_value($old, $key, $index) {
    return htmlspecialchars($old[$key][$index] ?? '');
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function field_error_html($fieldErrors, $field) {
    $message = $fieldErrors[$field] ?? '';
    return $message !== '' ? '<div class="field-error" role="alert">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>' : '';
}

function selected_if($value, $option) {
    return $value === $option ? 'selected' : '';
}

$respondentItem = function ($index = null, $old = []) {
    $program = Courses::split(is_int($index) ? ($old['respondent_course_year'][$index] ?? '') : '');
    $course = Courses::canonical(is_int($index) ? ($old['respondent_course'][$index] ?? $program['course']) : '');
    $section = is_int($index) ? ($old['respondent_section'][$index] ?? $program['section']) : '';
    $courseYear = Courses::combine($course, $section);
    $value = function ($key) use ($index, $old) {
        return is_int($index) ? ($old['respondent_' . $key][$index] ?? '') : '';
    };
    $respondentType = $value('type') ?: '';

    ob_start();
    ?>
    <div class="dynamic-item respondent-item">
        <div class="form-grid">
            <div class="field full">
                <label>Respondent Type <span class="required">*</span></label>
                <select name="respondent_type[]" aria-label="Respondent type" required>
                    <option value="">Select Respondent Type</option>
                    <option value="Student" <?= selected_if($respondentType, 'Student') ?>>Student</option>
                    <option value="Employee" <?= selected_if($respondentType, 'Employee') ?>>Employee</option>
                    <option value="Private Individual" <?= selected_if($respondentType, 'Private Individual') ?>>Private Individual</option>
                    <option value="Other" <?= selected_if($respondentType, 'Other') ?>>Other</option>
                </select>
            </div>
            <div class="field">
                <label>Full Name <span class="required">*</span></label>
                <input name="respondent_name[]" aria-label="Respondent full name" value="<?= h($value('name')) ?>" required>
            </div>
            <div class="field">
                <label>Age <span class="optional">if applicable</span></label>
                <input type="number" name="respondent_age[]" aria-label="Respondent age" min="1" max="120" value="<?= h($value('age')) ?>">
            </div>
            <div class="field">
                <label>Gender <span class="optional">if applicable</span></label>
                <select name="respondent_gender[]" aria-label="Respondent gender">
                    <option value="">Select Gender</option>
                    <option value="Male" <?= $value('gender') === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $value('gender') === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
            <div class="field" data-respondent-types="Student" <?= type_field_hidden('Student', $respondentType) ?>>
                <label>Student Number <span class="optional">if applicable</span></label>
                <input name="respondent_student_no[]" aria-label="Respondent student number" value="<?= h($value('student_no')) ?>" <?= type_field_disabled('Student', $respondentType) ?>>
            </div>
            <div class="field" data-respondent-types="Student" <?= type_field_hidden('Student', $respondentType) ?>>
                <label>College <span class="optional">if applicable</span></label>
                <select name="respondent_college[]" aria-label="Respondent college" <?= type_field_disabled('Student', $respondentType) ?>>
                    <option value="">Select College</option>
                    <?php foreach (Colleges::all() as $collegeOption): ?>
                        <option value="<?= h($collegeOption) ?>" <?= selected_if($value('college'), $collegeOption) ?>><?= h($collegeOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" data-respondent-types="Student" <?= type_field_hidden('Student', $respondentType) ?>>
                <label>Course/Program <span class="optional">if applicable</span></label>
                <select name="respondent_course[]" aria-label="Respondent course" disabled>
                    <option value="">Select a college first</option>
                </select>
            </div>
            <div class="field" data-respondent-types="Student" <?= type_field_hidden('Student', $respondentType) ?>>
                <label>Section <span class="optional">if applicable</span></label>
                <select name="respondent_section[]" aria-label="Respondent section" <?= type_field_disabled('Student', $respondentType) ?>>
                    <option value="">Select Section</option>
                    <?php foreach (Courses::sections() as $year => $sections): ?>
                        <optgroup label="<?= h($year) ?>">
                            <?php foreach ($sections as $sectionOption): ?>
                                <option value="<?= h($sectionOption) ?>" <?= selected_if($section, $sectionOption) ?>><?= h($sectionOption) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="respondent_course_year[]" value="<?= h($courseYear) ?>">
            </div>
            <div class="field" data-respondent-types="Employee" <?= type_field_hidden('Employee', $respondentType) ?>>
                <label>Employee Number <span class="optional">if applicable</span></label>
                <input name="respondent_employee_no[]" aria-label="Respondent employee number" value="<?= h($value('employee_no')) ?>" <?= type_field_disabled('Employee', $respondentType) ?>>
            </div>
            <div class="field" data-respondent-types="Employee" <?= type_field_hidden('Employee', $respondentType) ?>>
                <label>Position <span class="optional">if applicable</span></label>
                <input name="respondent_position[]" aria-label="Respondent position" value="<?= h($value('position')) ?>" placeholder="Example: Instructor, Administrative Assistant, Security Officer" <?= type_field_disabled('Employee', $respondentType) ?>>
            </div>
            <div class="field" data-respondent-types="Employee" <?= type_field_hidden('Employee', $respondentType) ?>>
                <label>College/Office/Department <span class="optional">if applicable</span></label>
                <input name="respondent_department[]" aria-label="Respondent college office or department" value="<?= h($value('department')) ?>" <?= type_field_disabled('Employee', $respondentType) ?>>
            </div>
            <div class="field" data-respondent-types="Other" <?= type_field_hidden('Other', $respondentType) ?>>
                <label>Affiliation/Organization <span class="optional">if applicable</span></label>
                <input name="respondent_affiliation[]" aria-label="Respondent affiliation or organization" value="<?= h($value('affiliation')) ?>" <?= type_field_disabled('Other', $respondentType) ?>>
            </div>
            <div class="field">
                <label>Contact Number <span class="optional">if applicable</span></label>
                <input name="respondent_contact[]" aria-label="Respondent contact number" value="<?= h($value('contact')) ?>">
            </div>
            <div class="field">
                <label>Email <span class="optional">if applicable</span></label>
                <input type="email" name="respondent_email[]" aria-label="Respondent email" value="<?= h($value('email')) ?>">
            </div>
            <div class="field">
                <label>Address <span class="optional">if applicable</span></label>
                <input name="respondent_address[]" aria-label="Respondent address" value="<?= h($value('address')) ?>" placeholder="Example: Barangay, City/Municipality">
            </div>
            <div class="field">
                <label>Details</label>
                <input name="respondent_details[]" aria-label="Respondent details" value="<?= h($value('details')) ?>" placeholder="Example: relationship, social media, or other relevant details.">
            </div>
        </div>
        <div class="dynamic-actions">
            <button type="button" class="btn-remove" data-remove-item><i class="bi bi-trash"></i> Remove</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
};

$witnessItem = function ($index = null, $old = []) {
    $value = function ($key) use ($index, $old) {
        return is_int($index) ? ($old['witness_' . $key][$index] ?? '') : '';
    };
    $witnessType = $value('type') ?: 'Student';
    $witnessCollege = (string) $value('college');
    $witnessCourse = Courses::canonical($value('course'));

    ob_start();
    ?>
    <div class="dynamic-item witness-item">
        <div class="form-grid">
            <div class="field full">
                <label>Witness Type <span class="required">*</span></label>
                <select name="witness_type[]" aria-label="Witness type" required>
                    <option value="">Select Witness Type</option>
                    <option value="Student" <?= selected_if($witnessType, 'Student') ?>>Student</option>
                    <option value="Employee" <?= selected_if($witnessType, 'Employee') ?>>Employee</option>
                    <option value="Private Individual" <?= selected_if($witnessType, 'Private Individual') ?>>Private Individual</option>
                    <option value="Other" <?= selected_if($witnessType, 'Other') ?>>Other</option>
                </select>
            </div>
            <div class="field">
                <label>Full Name <span class="required">*</span></label>
                <input name="witness_name[]" aria-label="Witness full name" value="<?= h($value('name')) ?>" required>
            </div>
            <div class="field">
                <label>Age <span class="optional">if applicable</span></label>
                <input type="number" name="witness_age[]" aria-label="Witness age" min="1" max="120" value="<?= h($value('age')) ?>">
            </div>
            <div class="field">
                <label>Gender <span class="optional">if applicable</span></label>
                <select name="witness_gender[]" aria-label="Witness gender">
                    <option value="">Select Gender</option>
                    <option value="Male" <?= $value('gender') === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $value('gender') === 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
            <div class="field" data-witness-types="Student" <?= type_field_hidden('Student', $witnessType) ?>>
                <label>Student Number <span class="optional">if applicable</span></label>
                <input name="witness_student_no[]" aria-label="Witness student number" value="<?= h($value('student_no')) ?>" <?= type_field_disabled('Student', $witnessType) ?>>
            </div>
            <div class="field" data-witness-types="Student" <?= type_field_hidden('Student', $witnessType) ?>>
                <label>College <span class="optional">if applicable</span></label>
                <select name="witness_college[]" aria-label="Witness college" <?= type_field_disabled('Student', $witnessType) ?>>
                    <option value="">Select College</option>
                    <?php foreach (Colleges::all() as $collegeOption): ?>
                        <option value="<?= h($collegeOption) ?>" <?= selected_if($value('college'), $collegeOption) ?>><?= h($collegeOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" data-witness-types="Student" <?= type_field_hidden('Student', $witnessType) ?>>
                <label>Course/Program <span class="optional">if applicable</span></label>
                <select name="witness_course[]" aria-label="Witness course" <?= type_field_disabled('Student', $witnessType) ?> <?= $witnessCollege === '' ? 'disabled' : '' ?>>
                    <option value=""><?= $witnessCollege === '' ? 'Select a college first' : 'Select Course/Program' ?></option>
                    <?php foreach (Courses::forCollege($witnessCollege) as $courseOption): ?>
                        <option value="<?= h($courseOption) ?>" <?= selected_if($witnessCourse, $courseOption) ?>><?= h($courseOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" data-witness-types="Student" <?= type_field_hidden('Student', $witnessType) ?>>
                <label>Section <span class="optional">if applicable</span></label>
                <select name="witness_section[]" aria-label="Witness section" <?= type_field_disabled('Student', $witnessType) ?>>
                    <option value="">Select Section</option>
                    <?php foreach (Courses::sections() as $year => $sections): ?>
                        <optgroup label="<?= h($year) ?>">
                            <?php foreach ($sections as $sectionOption): ?>
                                <option value="<?= h($sectionOption) ?>" <?= selected_if($value('section'), $sectionOption) ?>><?= h($sectionOption) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" data-witness-types="Employee" <?= type_field_hidden('Employee', $witnessType) ?>>
                <label>Employee Number <span class="optional">if applicable</span></label>
                <input name="witness_employee_no[]" aria-label="Witness employee number" value="<?= h($value('employee_no')) ?>" <?= type_field_disabled('Employee', $witnessType) ?>>
            </div>
            <div class="field" data-witness-types="Employee" <?= type_field_hidden('Employee', $witnessType) ?>>
                <label>Position <span class="optional">if applicable</span></label>
                <input name="witness_position[]" aria-label="Witness position" value="<?= h($value('position')) ?>" placeholder="Example: Instructor, Administrative Assistant, Security Officer" <?= type_field_disabled('Employee', $witnessType) ?>>
            </div>
            <div class="field" data-witness-types="Employee" <?= type_field_hidden('Employee', $witnessType) ?>>
                <label>College/Office or Department <span class="optional">if applicable</span></label>
                <input name="witness_department[]" aria-label="Witness college or office or department" value="<?= h($value('department')) ?>" <?= type_field_disabled('Employee', $witnessType) ?>>
            </div>
            <div class="field" data-witness-types="Other" <?= type_field_hidden('Other', $witnessType) ?>>
                <label>Affiliation/Organization <span class="optional">if applicable</span></label>
                <input name="witness_affiliation[]" aria-label="Witness affiliation or organization" value="<?= h($value('affiliation')) ?>" <?= type_field_disabled('Other', $witnessType) ?>>
            </div>
            <div class="field">
                <label>Contact Number <span class="optional">if applicable</span></label>
                <input name="witness_contact[]" aria-label="Witness contact number" value="<?= h($value('contact')) ?>">
            </div>
            <div class="field">
                <label>Email <span class="optional">if applicable</span></label>
                <input type="email" name="witness_email[]" aria-label="Witness email" value="<?= h($value('email')) ?>">
            </div>
            <div class="field">
                <label>Address <span class="optional">if applicable</span></label>
                <input name="witness_address[]" aria-label="Witness address" value="<?= h($value('address')) ?>" placeholder="Example: Barangay, City/Municipality">
            </div>
            <div class="field">
                <label>Statement</label>
                <input name="witness_statement[]" aria-label="Witness statement" value="<?= h($value('statement')) ?>" placeholder="Example: explain what the witness saw/heard during the incident.">
            </div>
        </div>
        <div class="dynamic-actions">
            <button type="button" class="btn-remove" data-remove-item><i class="bi bi-trash"></i> Remove</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint | DARIS</title>
    <link rel="stylesheet" href="../layout/style.css">
    <style>
        body {
            align-items: stretch;
            justify-content: flex-start;
            background: var(--bg-page, #f5f7f4);
            color: var(--text-primary, #172017);
            padding: 0;
        }

        .complaint-page {
            width: 100%;
            min-height: 100vh;
        }

        .complaint-header {
            background: var(--bg-sidebar, #123c1b);
            color: #fff;
            padding: 22px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .complaint-header h1 {
            font-size: 24px;
            margin-bottom: 4px;
        }

        .complaint-header p {
            font-size: 13px;
            color: rgba(255,255,255,0.8);
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .header-actions a,
        .btn-secondary,
        .btn-add,
        .btn-remove {
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            text-decoration: none;
        }

        .header-actions a,
        .btn-secondary {
            color: var(--text-secondary, #123c1b);
            background: var(--surface-primary, #fff);
            padding: 10px 14px;
        }

        .complaint-wrap {
            max-width: 100%;
            margin: 0 auto;
            padding: 24px;
        }

        .alert {
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .alert-error {
            border: 1px solid rgba(180, 35, 24, 0.5);
            background: var(--status-danger-bg, #fff5f5);
            color: var(--status-danger-text, #b42318);
        }

        .alert-success {
            border: 1px solid rgba(26, 157, 0, 0.35);
            background: rgba(26, 157, 0, 0.08);
            color: var(--status-success-text, #137500);
        }

        .complaint-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
            max-width: 100%;
        }

        .form-section {
            background: var(--surface-primary, #fff);
            border: 1px solid var(--border-primary, #dce5da);
            border-radius: 8px;
            padding: 20px;
        }

        .form-section h2 {
            font-size: 18px;
            color: var(--text-primary, #172017);
            margin-bottom: 16px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        [data-complainant-types][hidden],
        [data-respondent-types][hidden],
        [data-witness-types][hidden],
        #evidenceUploadField[hidden] { display: none !important; }

        label {
            font-size: 13px;
            color: var(--text-muted, #536052);
            font-weight: 500;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid var(--input-border, #b9c7b7);
            border-radius: 8px;
            padding: 11px 12px;
            font-family: inherit;
            font-size: 14px;
            color: var(--text-primary, #172017);
            background: var(--input-bg, #fff);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .dynamic-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .unknown-toggle {
            align-items: center;
            color: var(--text-muted, #4a5544);
            cursor: pointer;
            display: flex;
            font-size: 13px;
            gap: 8px;
            margin: 6px 0 2px;
        }

        .unknown-toggle input {
            accent-color: var(--accent, #1f6f43);
            height: 16px;
            width: 16px;
        }

        .unknown-toggle span {
            user-select: none;
        }

        .required-note {
            color: var(--warning, #8a6d3b);
            font-size: 12px;
        }

        .unknown-notice {
            background: var(--surface-secondary, #f7faf6);
            border: 1px dashed var(--input-border, #b9c7b7);
            border-radius: 8px;
            color: var(--text-muted, #536052);
            font-size: 13px;
            margin: 10px 0 2px;
            padding: 10px 12px;
        }

        .dynamic-item {
            border: 1px solid var(--border-primary, #dce5da);
            border-radius: 8px;
            padding: 14px;
            background: var(--surface-secondary, #fbfdfb);
        }

        .dynamic-actions,
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 14px;
        }

        .btn-add {
            background: rgba(26, 157, 0, 0.08);
            color: var(--text-secondary, #123c1b);
            padding: 10px 14px;
        }

        .btn-remove {
            background: var(--status-danger-bg, #fff5f5);
            color: var(--status-danger-text, #b42318);
            padding: 9px 12px;
        }

        .btn-submit {
            background: var(--accent, #1A9D00);
            color: #fff;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            padding: 12px 20px;
        }

        .file-note {
            color: var(--text-muted, #536052);
            font-size: 12px;
            margin-top: 6px;
        }

        .radio-group {
            display: flex;
            gap: 18px;
        }

        .radio-item {
            align-items: center;
            display: inline-flex;
            gap: 7px;
            font-size: 14px;
        }

        .radio-item input {
            accent-color: var(--accent, #1f6f43);
            height: 16px;
            width: 16px;
        }

        @media (max-width: 760px) {
            .complaint-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        .draft-toolbar { align-items:center; background:var(--surface-secondary, #f7faf6); border:1px solid var(--border-primary, #dce7d9); border-radius:8px; display:flex; flex-wrap:wrap; gap:10px; justify-content:space-between; margin-bottom:14px; padding:10px 12px; }
        .draft-status { color:var(--text-muted, #617060); font-size:12px; }
        .draft-status.is-error { color:var(--status-danger-text, #a92c23); }
        .draft-evidence-note { color:var(--text-muted, #657064); font-size:12px; margin:7px 0 0; }
    </style>
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/complaint-form.css?v=4">

    <style>
        .submission-modal-backdrop { position: fixed; inset: 0; z-index: 9999; display: none; align-items: center; justify-content: center; padding: 20px; background: rgba(3,9,5,.72); backdrop-filter: blur(3px); }
        .submission-modal-backdrop.show { display: flex; }
        .submission-modal { position: relative; width: min(460px,100%); background: var(--surface-elevated); border: 1px solid var(--border-primary); border-radius: 18px; color: var(--text-primary); padding: 34px 30px 28px; text-align: center; box-shadow: 0 24px 70px rgba(0,0,0,.28); animation: submissionPop .18s ease-out; }
        @keyframes submissionPop { from { opacity: 0; transform: translateY(10px) scale(.97); } to { opacity: 1; transform: none; } }
        .submission-modal-close { position: absolute; top: 10px; right: 14px; border: 0; border-radius: 8px; background: transparent; font-size: 28px; color: var(--text-muted); cursor: pointer; }
        .submission-modal-close:hover, .submission-modal-close:focus-visible { background: var(--surface-accent); color: var(--danger); outline: 2px solid var(--accent); outline-offset: 2px; }
        .submission-modal-icon { width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 50%; display: grid; place-items: center; background: var(--status-success-bg, var(--surface-accent)); color: var(--status-success-text, var(--success)); font-size: 30px; }
        .submission-modal.error .submission-modal-icon { background: var(--status-danger-bg, color-mix(in srgb, var(--danger) 12%, var(--surface-primary))); color: var(--status-danger-text, var(--danger)); }
        .submission-modal h2 { margin: 0 0 9px; color: var(--text-primary); font-size: 22px; }
        .submission-modal p { margin: 0 auto; max-width: 380px; color: var(--text-muted); line-height: 1.6; font-size: 14px; white-space: pre-line; }
        .submission-case-number { margin: 18px auto 0; width: fit-content; padding: 9px 14px; border: 1px solid var(--border-primary); border-radius: 9px; background: var(--surface-soft); color: var(--text-primary); font-weight: 700; }
        .submission-modal-actions { display: flex; gap: 10px; justify-content: center; margin-top: 22px; flex-wrap: wrap; }
        .submission-modal-actions .btn { min-width: 120px; }
        html[data-theme="dark"] .submission-modal p { color: var(--text-muted) !important; }
    </style>
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="complaint-page app-content">
        <div class="submission-modal-backdrop" id="submissionModal" aria-hidden="true">
            <div class="submission-modal" role="dialog" aria-modal="true" aria-labelledby="submissionModalTitle">
                <button type="button" class="submission-modal-close" id="submissionModalClose" aria-label="Close">&times;</button>
                <div class="submission-modal-icon" id="submissionModalIcon"><i class="bi bi-check-lg"></i></div>
                <h2 id="submissionModalTitle">Complaint Submitted Successfully</h2>
                <p id="submissionModalMessage">Your complaint has been successfully submitted to SDRU. You can track its status through Complaint Cases.</p>
                <div class="submission-case-number" id="submissionCaseNumber" hidden></div>
                <div class="submission-modal-actions">
                    <button type="button" class="btn btn-primary" id="submissionModalOkay">Okay</button>
                    <a class="btn btn-primary" id="submissionTrack" href="my_cases.php" hidden>Track My Cases</a>
                    <a class="btn btn-secondary" id="submissionDashboard" href="../../../index.php" hidden>Back to Dashboard</a>
                </div>
            </div>
        </div>

            <?php $pageTitle = 'Submit Complaint'; require __DIR__ . '/../layout/topbar.php'; ?>

        <main class="complaint-wrap complaint-submission-page">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <section class="submission-success" aria-live="polite">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    <div><h2>Complaint Submitted Successfully</h2><p>Your complaint has been successfully submitted to SDRU. You can track its status through Complaint Cases.</p><div class="success-meta"><span>Case Number <strong><?= h($successCaseNumber) ?></strong></span><span>Current Status <strong>Under Investigation</strong></span></div><a class="btn btn-primary" href="my_cases.php"><i class="bi bi-folder-check"></i> Track My Cases</a></div>
                </section>
            <?php endif; ?>

            <form class="complaint-form" id="complaintForm" action="create.php" method="POST" enctype="multipart/form-data" data-sicms-validate data-no-ajax="true" novalidate>
                <?= Security::csrfField() ?>
                <input type="hidden" name="submission_token" value="<?= h($submissionToken) ?>">
                <div class="draft-toolbar">
                    <span class="draft-status" id="complaintDraftStatus" role="status" aria-live="polite"><?= $restoringDraft ? 'Draft restored. Changes will save automatically.' : 'Draft not saved yet.' ?></span>
                    <button type="button" class="btn btn-secondary" id="saveComplaintDraft"><i class="bi bi-save"></i> Save as Draft</button>
                </div>
                <section class="form-section" id="complainantSection">
                    <div class="complaint-section-heading"><span><i class="bi bi-person-badge"></i></span><div><h2>Complainant Information</h2><p id="complainantHelp">Student or private-individual details</p></div></div>
                    <div class="form-grid">
                        <div class="field full">
                            <label for="complainant_type">Complainant Type <span class="required">*</span></label>
                            <select id="complainant_type" name="complainant_type" required>
                                <option value="">Select Complainant Type</option>
                                <option value="Student" <?= $complainantType === 'Student' ? 'selected' : '' ?>>Student</option>
                                <option value="Employee" <?= $complainantType === 'Employee' ? 'selected' : '' ?>>Employee</option>
                                <option value="Private Individual" <?= $complainantType === 'Private Individual' ? 'selected' : '' ?>>Private Individual</option>
                                <option value="Others" <?= $complainantType === 'Others' ? 'selected' : '' ?>>Others</option>
                            </select>
                            <?= field_error_html($fieldErrors, 'complainant_type') ?>
                        </div>
                        <div class="field">
                            <label for="complainant_name">Full Name <span class="required">*</span></label>
                            <input id="complainant_name" name="complainant_name" value="<?= h($complainantName) ?>" required>
                            <?= field_error_html($fieldErrors, 'complainant_name') ?>
                        </div>
                        <div class="field">
                            <label for="complainant_gender">Gender</label>
                            <select id="complainant_gender" name="complainant_gender">
                                <option value="">Select Gender</option>
                                <option value="Male" <?= $complainantGender === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $complainantGender === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                            <?= field_error_html($fieldErrors, 'complainant_gender') ?>
                        </div>
                        <div class="field">
                            <label for="complainant_age">Age <span class="required">*</span></label>
                            <input id="complainant_age" name="complainant_age" type="number" min="1" max="120" value="<?= h($complainantAge) ?>" required>
                            <?= field_error_html($fieldErrors, 'complainant_age') ?>
                        </div>
                        <div class="field" data-complainant-types="Student" <?= type_field_hidden('Student', $complainantType) ?>>
                            <label for="complainant_student_no">Student Number <span class="required">*</span></label>
                            <input id="complainant_student_no" name="complainant_student_no" value="<?= h($complainantStudentNo) ?>" required <?= type_field_disabled('Student', $complainantType) ?>>
                            <?= field_error_html($fieldErrors, 'complainant_student_no') ?>
                        </div>
                        <div class="field">
                            <label for="complainant_email">Email <span class="required">*</span></label>
                            <input id="complainant_email" type="email" name="complainant_email" value="<?= h($old['complainant_email'] ?? $user['email']) ?>" required>
                            <?= field_error_html($fieldErrors, 'complainant_email') ?>
                        </div>
                        <div class="field">
                            <label for="complainant_contact">Contact Number <span class="required">*</span></label>
                            <input id="complainant_contact" name="complainant_contact" value="<?= h($complainantContact) ?>" required data-sicms-phone>
                            <?= field_error_html($fieldErrors, 'complainant_contact') ?>
                        </div>
                        <div class="field" data-complainant-types="Student" <?= type_field_hidden('Student', $complainantType) ?>>
                            <label for="complainant_college">College <span class="required">*</span></label>
                            <select id="complainant_college" name="complainant_college" required <?= type_field_disabled('Student', $complainantType) ?>>
                                <option value="">Select College</option>
                                <?php foreach (Colleges::all() as $college): ?>
                                    <option value="<?= h($college) ?>" <?= $complainantCollege === $college ? 'selected' : '' ?>><?= h($college) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= field_error_html($fieldErrors, 'complainant_college') ?>
                        </div>
                        <div class="field" data-complainant-types="Student" <?= type_field_hidden('Student', $complainantType) ?>>
                            <label for="complainant_course">Course/Program <span class="required">*</span></label>
                            <select id="complainant_course" name="complainant_course" required <?= type_field_disabled('Student', $complainantType) ?> <?= $complainantCollege === '' ? 'disabled' : '' ?>>
                                <option value=""><?= $complainantCollege === '' ? 'Select a college first' : 'Select Course/Program' ?></option>
                                <?php foreach (Courses::forCollege($complainantCollege) as $course): ?>
                                    <option value="<?= h($course) ?>" <?= $complainantCourse === $course ? 'selected' : '' ?>><?= h($course) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= field_error_html($fieldErrors, 'complainant_course') ?>
                        </div>
                        <div class="field" data-complainant-types="Student" <?= type_field_hidden('Student', $complainantType) ?>>
                            <label for="complainant_section">Section <span class="required">*</span></label>
                            <select id="complainant_section" name="complainant_section" required <?= type_field_disabled('Student', $complainantType) ?>>
                                <option value="">Select Section</option>
                                <?php foreach (Courses::sections() as $year => $sections): ?>
                                    <optgroup label="<?= h($year) ?>">
                                        <?php foreach ($sections as $section): ?>
                                            <option value="<?= h($section) ?>" <?= $complainantSection === $section ? 'selected' : '' ?>><?= h($section) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <?= field_error_html($fieldErrors, 'complainant_section') ?>
                            <input id="complainant_course_year" type="hidden" name="complainant_course_year" value="<?= h(Courses::combine($complainantCourse, $complainantSection)) ?>">
                        </div>
                        <div class="field" data-complainant-types="Private Individual" <?= type_field_hidden('Private Individual', $complainantType) ?>>
                            <label for="complainant_relationship">Relationship to CLSU <span class="optional">Optional</span></label>
                            <select id="complainant_relationship" name="complainant_relationship" <?= type_field_disabled('Private Individual', $complainantType) ?>>
                                <option value="">Select Relationship</option>
                                <?php foreach (['Parent', 'Guardian', 'Visitor', 'Alumni', 'Community Member', 'Other'] as $relationship): ?>
                                    <option value="<?= h($relationship) ?>" <?= (($old['complainant_relationship'] ?? '') === $relationship) ? 'selected' : '' ?>><?= h($relationship) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= field_error_html($fieldErrors, 'complainant_relationship') ?>
                        </div>
                        <div class="field" data-complainant-types="Employee" <?= type_field_hidden('Employee', $complainantType) ?>>
                            <label for="complainant_employee_no">Employee Number <span class="required">*</span></label>
                            <input id="complainant_employee_no" name="complainant_employee_no" value="<?= old_value($old, 'complainant_employee_no') ?>" required <?= type_field_disabled('Employee', $complainantType) ?>>
                            <?= field_error_html($fieldErrors, 'complainant_employee_no') ?>
                        </div>
                        <div class="field" data-complainant-types="Employee" <?= type_field_hidden('Employee', $complainantType) ?>>
                            <label for="complainant_department">College / Office / Department <span class="required">*</span></label>
                            <input id="complainant_department" name="complainant_department" value="<?= old_value($old, 'complainant_department') ?>" required <?= type_field_disabled('Employee', $complainantType) ?>>
                            <?= field_error_html($fieldErrors, 'complainant_department') ?>
                        </div>
                        <div class="field" data-complainant-types="Employee" <?= type_field_hidden('Employee', $complainantType) ?>>
                            <label for="complainant_position">Position <span class="required">*</span></label>
                            <input id="complainant_position" name="complainant_position" value="<?= old_value($old, 'complainant_position') ?>" required <?= type_field_disabled('Employee', $complainantType) ?>>
                            <?= field_error_html($fieldErrors, 'complainant_position') ?>
                        </div>
                        <div class="field" data-complainant-types="Others" <?= type_field_hidden('Others', $complainantType) ?>>
                            <label for="complainant_affiliation">Affiliation / Organization <span class="optional">Optional</span></label>
                            <input id="complainant_affiliation" name="complainant_affiliation" value="<?= old_value($old, 'complainant_affiliation') ?>" <?= type_field_disabled('Others', $complainantType) ?>>
                        </div>
                        <div class="field" data-complainant-types="Others" <?= type_field_hidden('Others', $complainantType) ?>>
                            <label for="complainant_purpose">Relationship or Purpose <span class="optional">Optional</span></label>
                            <input id="complainant_purpose" name="complainant_purpose" value="<?= old_value($old, 'complainant_purpose') ?>" <?= type_field_disabled('Others', $complainantType) ?>>
                        </div>
                    </div>
                </section>

                <section class="form-section" id="complaintInformationSection">
                    <div class="complaint-section-heading"><span><i class="bi bi-file-earmark-text"></i></span><div><h2>Complaint Information</h2><p>Incident and complaint details</p></div></div>
                    <div class="form-grid">
                        <div class="field">
                            <label for="incident_date">Date of Incident <span class="required">*</span></label>
                            <input id="incident_date" type="date" value="<?= $oldIncident ? h(date('Y-m-d', $oldIncident)) : '' ?>" required max="<?= h(date('Y-m-d')) ?>" data-sicms-future="0">
                            <?= field_error_html($fieldErrors, 'incident_datetime') ?>
                        </div>
                        <div class="field">
                            <label for="incident_time">Time of Incident <span class="required">*</span></label>
                            <input id="incident_time" type="time" value="<?= $oldIncident ? h(date('H:i', $oldIncident)) : '' ?>" required>
                            <input id="incident_datetime" type="hidden" name="incident_datetime" value="<?= old_value($old, 'incident_datetime') ?>">
                        </div>
                        <div class="field full">
                            <label for="incident_location">Incident Location <span class="required">*</span></label>
                            <input id="incident_location" name="incident_location" value="<?= old_value($old, 'incident_location') ?>" placeholder="Example: College of Engineering, 2nd Floor, Room 204 / Near the Carabao Gate" required>
                            <?= field_error_html($fieldErrors, 'incident_location') ?>
                        </div>
                        <div class="field full">
                            <label for="complaint_details">Complaint Details <span class="required">*</span></label>
                            <textarea id="complaint_details" name="complaint_details" maxlength="5000" aria-describedby="complaintCounter" placeholder="Explain how the incident started, what happened during the incident, who was involved, and how the incident ended." required><?= old_value($old, 'complaint_details') ?></textarea>
                            <?= field_error_html($fieldErrors, 'complaint_details') ?>
                            <div class="character-counter" id="complaintCounter"><span id="complaintCharacterCount">0</span> / 5000 characters</div>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="complaint-section-heading"><span><i class="bi bi-people"></i></span><div><h2>Respondent Information</h2><p class="required-note">Required unless you don't know the respondent</p></div></div>
                    <label class="unknown-toggle">
                        <input id="respondent_unknown" type="checkbox" name="respondent_unknown" value="1" <?= !empty($old['respondent_unknown']) ? 'checked' : '' ?>>
                        <span>I don't know the respondent</span>
                    </label>
                    <p class="unknown-notice" <?= !empty($old['respondent_unknown']) ? '' : 'hidden' ?>>Respondent information marked as unknown. No respondent details were provided.</p>
                    <div id="respondent-information-fields" class="person-information-fields" <?= !empty($old['respondent_unknown']) ? 'hidden' : '' ?>>
                    <div id="respondent-list" class="dynamic-list">
                        <?php $respondentCount = count($old['respondent_name'] ?? []); ?>
                        <?php for ($index = 0; $index < $respondentCount; $index++): ?>
                            <?php
                                $respondentProgram = Courses::split($old['respondent_course_year'][$index] ?? '');
                                $respondentCourse = Courses::canonical($old['respondent_course'][$index] ?? $respondentProgram['course']);
                                $respondentSection = $old['respondent_section'][$index] ?? $respondentProgram['section'];
                                $respondentType = trim((string) ($old['respondent_type'][$index] ?? ''));
                                if (!in_array($respondentType, ['Student', 'Employee', 'Private Individual', 'Other'], true)) $respondentType = 'Student';
                            ?>
                            <div class="dynamic-item respondent-item">
                                <div class="form-grid">
                                    <div class="field full">
                                        <label>Respondent Type <span class="required">*</span></label>
                                        <select name="respondent_type[]" aria-label="Respondent type" required>
                                            <option value="">Select Respondent Type</option>
                                            <option value="Student" <?= $respondentType === 'Student' ? 'selected' : '' ?>>Student</option>
                                            <option value="Employee" <?= $respondentType === 'Employee' ? 'selected' : '' ?>>Employee</option>
                                            <option value="Private Individual" <?= $respondentType === 'Private Individual' ? 'selected' : '' ?>>Private Individual</option>
                                            <option value="Other" <?= $respondentType === 'Other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label>Full Name <span class="required">*</span></label>
                                        <input name="respondent_name[]" aria-label="Respondent full name" value="<?= old_array_value($old, 'respondent_name', $index) ?>" required>
                                    </div>
                                    <div class="field">
                                        <label>Age <span class="optional">if applicable</span></label>
                                        <input type="number" name="respondent_age[]" aria-label="Respondent age" min="1" max="120" value="<?= old_array_value($old, 'respondent_age', $index) ?>">
                                    </div>
                                    <div class="field">
                                        <label>Gender <span class="optional">if applicable</span></label>
                                        <select name="respondent_gender[]" aria-label="Respondent gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?= (($old['respondent_gender'][$index] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= (($old['respondent_gender'][$index] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="field" data-respondent-types="Student" <?= type_field_hidden('Student', $respondentType) ?>>
                                        <label>Student Number <span class="optional">if applicable</span></label>
                                        <input name="respondent_student_no[]" aria-label="Respondent student number" value="<?= old_array_value($old, 'respondent_student_no', $index) ?>" <?= type_field_disabled('Student', $respondentType) ?>>
                                    </div>
                                    <div class="field" data-respondent-types="Student" <?= type_field_hidden('Student', $respondentType) ?>>
                                        <label>College <span class="optional">if applicable</span></label>
                                        <select name="respondent_college[]" aria-label="Respondent college" <?= type_field_disabled('Student', $respondentType) ?>>
                                            <option value="">Select College</option>
                                            <?php foreach (Colleges::all() as $college): ?>
                                                <option value="<?= h($college) ?>" <?= (($old['respondent_college'][$index] ?? '') === $college) ? 'selected' : '' ?>><?= h($college) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field" data-respondent-types="Student" <?= type_field_hidden('Student', $respondentType) ?>>
                                        <label>Course/Program <span class="optional">if applicable</span></label>
                                        <?php $respondentCollege = (string) ($old['respondent_college'][$index] ?? ''); ?>
                                        <select name="respondent_course[]" aria-label="Respondent course" <?= type_field_disabled('Student', $respondentType) ?> <?= $respondentCollege === '' ? 'disabled' : '' ?>>
                                            <option value=""><?= $respondentCollege === '' ? 'Select a college first' : 'Select Course/Program' ?></option>
                                            <?php foreach (Courses::forCollege($respondentCollege) as $course): ?>
                                                <option value="<?= h($course) ?>" <?= $respondentCourse === $course ? 'selected' : '' ?>><?= h($course) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field" data-respondent-types="Student" <?= type_field_hidden('Student', $respondentType) ?>>
                                        <label>Section <span class="optional">if applicable</span></label>
                                        <select name="respondent_section[]" aria-label="Respondent section" <?= type_field_disabled('Student', $respondentType) ?>>
                                            <option value="">Select Section</option>
                                            <?php foreach (Courses::sections() as $year => $sections): ?>
                                                <optgroup label="<?= h($year) ?>">
                                                    <?php foreach ($sections as $section): ?>
                                                        <option value="<?= h($section) ?>" <?= $respondentSection === $section ? 'selected' : '' ?>><?= h($section) ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="respondent_course_year[]" value="<?= h(Courses::combine($respondentCourse, $respondentSection)) ?>">
                                    </div>
                                    <div class="field" data-respondent-types="Employee" <?= type_field_hidden('Employee', $respondentType) ?>>
                                        <label>Employee Number <span class="optional">if applicable</span></label>
                                        <input name="respondent_employee_no[]" aria-label="Respondent employee number" value="<?= old_array_value($old, 'respondent_employee_no', $index) ?>" <?= type_field_disabled('Employee', $respondentType) ?>>
                                    </div>
                                    <div class="field" data-respondent-types="Employee" <?= type_field_hidden('Employee', $respondentType) ?>>
                                        <label>Position <span class="optional">if applicable</span></label>
                                        <input name="respondent_position[]" aria-label="Respondent position" value="<?= old_array_value($old, 'respondent_position', $index) ?>" placeholder="Example: Instructor, Administrative Assistant, Security Officer" <?= type_field_disabled('Employee', $respondentType) ?>>
                                    </div>
                                    <div class="field" data-respondent-types="Employee" <?= type_field_hidden('Employee', $respondentType) ?>>
                                        <label>College/Office/Department <span class="optional">if applicable</span></label>
                                        <input name="respondent_department[]" aria-label="Respondent college office or department" value="<?= old_array_value($old, 'respondent_department', $index) ?>" <?= type_field_disabled('Employee', $respondentType) ?>>
                                    </div>
                                    <div class="field" data-respondent-types="Other" <?= type_field_hidden('Other', $respondentType) ?>>
                                        <label>Affiliation/Organization <span class="optional">if applicable</span></label>
                                        <input name="respondent_affiliation[]" aria-label="Respondent affiliation or organization" value="<?= old_array_value($old, 'respondent_affiliation', $index) ?>" <?= type_field_disabled('Other', $respondentType) ?>>
                                    </div>
                                    <div class="field">
                                        <label>Contact Number <span class="optional">if applicable</span></label>
                                        <input name="respondent_contact[]" aria-label="Respondent contact number" value="<?= old_array_value($old, 'respondent_contact', $index) ?>">
                                    </div>
                                    <div class="field">
                                        <label>Email <span class="optional">if applicable</span></label>
                                        <input type="email" name="respondent_email[]" aria-label="Respondent email" value="<?= old_array_value($old, 'respondent_email', $index) ?>">
                                    </div>
                                    <div class="field">
                                        <label>Address <span class="optional">if applicable</span></label>
                                        <input name="respondent_address[]" aria-label="Respondent address" value="<?= old_array_value($old, 'respondent_address', $index) ?>" placeholder="Example: Barangay, City/Municipality">
                                    </div>
                                    <div class="field">
                                        <label>Details</label>
                                        <input name="respondent_details[]" aria-label="Respondent details" value="<?= old_array_value($old, 'respondent_details', $index) ?>" placeholder="Example: relationship, social media, or other relevant details.">
                                    </div>
                                </div>
                                <div class="dynamic-actions">
                                    <button type="button" class="btn-remove" data-remove-item><i class="bi bi-trash"></i> Remove</button>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <template id="respondent-item-template"><?= $respondentItem() ?></template>
                    <div class="dynamic-actions">
                        <button type="button" class="btn-add" onclick="addRespondent()"><i class="bi bi-person-plus"></i> Add Respondent</button>
                    </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="complaint-section-heading"><span><i class="bi bi-person-lines-fill"></i></span><div><h2>Witness Information</h2><p class="required-note">Required unless you do not have a witness</p></div></div>
                    <label class="unknown-toggle">
                        <input id="witness_none" type="checkbox" name="witness_none" value="1" <?= !empty($old['witness_none']) ? 'checked' : '' ?>>
                        <span>There is no witness / I don't know the witness</span>
                    </label>
                    <p class="unknown-notice" <?= !empty($old['witness_none']) ? '' : 'hidden' ?>>Witness information marked as none. No witness details were provided.</p>
                    <div id="witness-information-fields" class="person-information-fields" <?= !empty($old['witness_none']) ? 'hidden' : '' ?>>
                    <div id="witness-list" class="dynamic-list">
                        <?php $witnessCount = count($old['witness_name'] ?? []); ?>
                        <?php for ($index = 0; $index < $witnessCount; $index++): ?>
                            <?= $witnessItem($index, $old) ?>
                        <?php endfor; ?>
                    </div>
                    <template id="witness-item-template"><?= $witnessItem() ?></template>
                    <div class="dynamic-actions">
                        <button type="button" class="btn-add" onclick="addWitness()"><i class="bi bi-person-plus"></i> Add Witness</button>
                    </div>
                    </div>
                </section>

                <section class="form-section">
                    <div class="complaint-section-heading"><span><i class="bi bi-paperclip"></i></span><div><h2>Supporting Evidence</h2><p>PDF, DOCX, JPG, JPEG, or PNG up to 5MB each</p></div></div>
                    <div class="field">
                        <label>Do you have supporting evidence to submit?</label>
                        <div class="radio-group">
                            <label class="radio-item"><input type="radio" name="has_evidence" value="yes" required <?= ($old['has_evidence'] ?? '') === 'yes' ? 'checked' : '' ?>> Yes</label>
                            <label class="radio-item"><input type="radio" name="has_evidence" value="no" <?= ($old['has_evidence'] ?? '') === 'no' ? 'checked' : '' ?>> No</label>
                        </div>
                    </div>
                    <div class="field" id="evidenceUploadField" hidden>
                        <label for="evidence">Upload Files</label>
                        <input id="evidence" type="file" name="evidence[]" accept=".pdf,.jpg,.jpeg,.png,.docx" multiple required data-sicms-size-mb="5" data-sicms-accept-ext=".pdf,.jpg,.jpeg,.png,.docx">
                        <p class="file-note">Accepted formats: PDF, JPG, PNG, DOCX. Maximum size: 5MB per file.</p>
                        <p class="draft-evidence-note"><i class="bi bi-info-circle"></i> Selected files are not included in auto-save. Please attach them again before submitting.</p>
                        <div class="field-error" id="evidenceError" role="alert"></div>
                        <div class="evidence-list" id="evidenceList"></div>
                    </div>
                </section>

                <div class="complaint-validation-notice" id="complaintValidationNotice" role="alert" hidden>Please complete the required fields before reviewing your complaint.</div>
                <div class="form-actions">
                    <div class="submission-progress hidden" id="submissionProgress"><span></span> Submitting Complaint...</div>
                    <button type="submit" class="btn-submit" id="reviewButton"><i class="bi bi-send-check"></i> Submit Complaint</button>
                    <a class="btn-secondary" href="../../../index.php"><i class="bi bi-x"></i> Cancel</a>
                </div>
            </form>

            <dialog class="review-dialog review-complaint-modal" id="reviewDialog" aria-labelledby="reviewTitle">
                <div class="review-dialog-header"><div><h2 id="reviewTitle">Review Complaint</h2><p>Carefully verify each section before submitting your complaint to SDRU.</p></div><button type="button" class="dialog-close" id="closeReview" aria-label="Close review"><i class="bi bi-x-lg"></i></button></div>
                <div class="review-content" id="reviewContent"></div>
                <div class="review-confirmation">
                    <label class="certification"><input id="certification" type="checkbox"> <span>I have reviewed my complaint and confirm that the information and evidence provided are complete and accurate.</span></label>
                </div>
                <div class="review-actions"><button class="btn btn-primary" id="confirmSubmit" type="button" disabled><i class="bi bi-send-check"></i> Submit Complaint</button><button class="btn btn-secondary" id="editComplaint" type="button">Continue Editing</button></div>
            </dialog>
        </main>
        </div>

        <?php
        $profileGateMode = 'auto';
        $blurTarget = '.complaint-page.app-content';
        $profileGateSettingsUrl = '../settings/index.php';
        require __DIR__ . '/../layout/profile_gate.php';
        ?>
    </div>

    <script>
        const complaintForm = document.getElementById('complaintForm');
        complaintForm.insertBefore(document.getElementById('complaintInformationSection'), document.getElementById('complainantSection'));
        const complaintDetails = document.getElementById('complaint_details');
        const complaintCharacterCount = document.getElementById('complaintCharacterCount');
        const incidentDate = document.getElementById('incident_date');
        const incidentTime = document.getElementById('incident_time');
        const incidentDatetime = document.getElementById('incident_datetime');
        const evidenceInput = document.getElementById('evidence');
        const evidenceList = document.getElementById('evidenceList');
        const evidenceError = document.getElementById('evidenceError');
        const reviewDialog = document.getElementById('reviewDialog');
        const reviewContent = document.getElementById('reviewContent');
        const certification = document.getElementById('certification');
        const confirmSubmit = document.getElementById('confirmSubmit');
        const reviewButton = document.getElementById('reviewButton');
        const complaintValidationNotice = document.getElementById('complaintValidationNotice');
        const complaintDraftStatus = document.getElementById('complaintDraftStatus');
        const saveComplaintDraftButton = document.getElementById('saveComplaintDraft');
        let complaintDraftVersion = <?= (int) ($complaintDraft['version'] ?? 0) ?>;
        let complaintDraftDirty = false;
        let complaintDraftSaving = false;
        let complaintDraftPending = false;
        let complaintDraftTimer = null;
        let complaintFinalizing = false;

        function complaintDraftPayload() {
            const payload = {};
            Array.from(complaintForm.elements).forEach(control => {
                if (!control.name || control.type === 'file' || ['csrf_token', 'submission_token'].includes(control.name) || control.id === 'certification') return;
                if ((control.type === 'checkbox' || control.type === 'radio') && !control.checked) return;
                const isArray = control.name.endsWith('[]');
                const key = isArray ? control.name.slice(0, -2) : control.name;
                if (isArray) {
                    if (!Array.isArray(payload[key])) payload[key] = [];
                    payload[key].push(control.value);
                } else {
                    payload[key] = control.value;
                }
            });
            return payload;
        }

        function setComplaintDraftStatus(message, isError = false) {
            complaintDraftStatus.textContent = message;
            complaintDraftStatus.classList.toggle('is-error', isError);
        }

        async function saveComplaintDraft(manual = false) {
            clearTimeout(complaintDraftTimer);
            if (complaintFinalizing || (!complaintDraftDirty && !manual)) return;
            if (complaintDraftSaving) { complaintDraftPending = true; return; }
            complaintDraftSaving = true;
            saveComplaintDraftButton.disabled = true;
            setComplaintDraftStatus('Saving draft…');
            const body = new FormData();
            body.set('csrf_token', complaintForm.elements.csrf_token.value);
            body.set('submission_token', complaintForm.elements.submission_token.value);
            body.set('draft_action', 'save');
            body.set('version', String(complaintDraftVersion));
            body.set('payload', JSON.stringify(complaintDraftPayload()));
            try {
                const response = await fetch('draft.php', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Draft could not be saved.');
                complaintDraftVersion = result.version;
                complaintDraftDirty = false;
                setComplaintDraftStatus('Draft saved ' + new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) + '.');
            } catch (error) {
                setComplaintDraftStatus(error.message || 'Draft could not be saved. Your changes remain on this page.', true);
            } finally {
                complaintDraftSaving = false;
                saveComplaintDraftButton.disabled = false;
                if (complaintDraftPending) { complaintDraftPending = false; complaintDraftDirty = true; saveComplaintDraft(); }
            }
        }

        function queueComplaintDraft() {
            if (complaintFinalizing) return;
            complaintDraftDirty = true;
            if (complaintDraftSaving) complaintDraftPending = true;
            setComplaintDraftStatus('Unsaved changes');
            clearTimeout(complaintDraftTimer);
            complaintDraftTimer = setTimeout(() => saveComplaintDraft(false), 4000);
        }

        complaintForm.addEventListener('input', queueComplaintDraft);
        complaintForm.addEventListener('change', queueComplaintDraft);
        saveComplaintDraftButton.addEventListener('click', () => { complaintDraftDirty = true; saveComplaintDraft(true); });
        window.addEventListener('beforeunload', event => {
            if (!complaintFinalizing && (complaintDraftDirty || complaintDraftSaving)) { event.preventDefault(); event.returnValue = ''; }
        });
        const submissionProgress = document.getElementById('submissionProgress');
        const complainantTypeInput = document.getElementById('complainant_type');
        const complainantNameInput = document.getElementById('complainant_name');
        const complainantGenderInput = document.getElementById('complainant_gender');
        const complainantEmailInput = document.getElementById('complainant_email');
        const complainantContactInput = document.getElementById('complainant_contact');
        const programsByCollege = <?= json_encode(Courses::byCollege(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const studentAccountName = <?= json_encode(trim($user['first_name'] . ' ' . $user['last_name'])) ?>;
        const studentAccountEmail = <?= json_encode($user['email']) ?>;
        const studentAccountGender = <?= json_encode(trim($user['gender'] ?? '')) ?>;
        const studentAccountStudentNo = <?= json_encode($user['student_number'] ?? '') ?>;
        const studentAccountCollege = <?= json_encode($user['college'] ?? '') ?>;
        const studentAccountCourse = <?= json_encode($user['course'] ?? '') ?>;
        const studentAccountSection = <?= json_encode($user['section'] ?? '') ?>;
        const studentAccountPhone = <?= json_encode($user['phone_number'] ?? '') ?>;
        const studentAccountBirthday = <?= json_encode($user['birthday'] ?? '') ?>;
        let studentAccountAge = '';
        if (/^\d{4}-\d{2}-\d{2}$/.test(studentAccountBirthday)) {
            const parts = studentAccountBirthday.split('-').map(Number);
            const birth = new Date(parts[0], parts[1] - 1, parts[2]);
            const now = new Date();
            if (birth <= now) {
                studentAccountAge = now.getFullYear() - birth.getFullYear();
                const monthDifference = now.getMonth() - birth.getMonth();
                if (monthDifference < 0 || (monthDifference === 0 && now.getDate() < birth.getDate())) studentAccountAge--;
            }
        }
        const complainantAgeInput = document.getElementById('complainant_age');
        let activeComplainantType = complainantTypeInput.value;
        const complainantIdentityCache = {};
        let confirmed = false;

        function removeItem(button) {
            const item = button.closest('.dynamic-item');
            item.remove();
        }

        document.addEventListener('click', event => {
            const button = event.target.closest('.btn-remove');
            if (!button) return;
            removeItem(button);
        });

        function toggleUnknown(kind, checkbox) {
            const isRespondent = kind === 'respondent';
            const container = document.getElementById(
                isRespondent ? 'respondent-information-fields' : 'witness-information-fields'
            );
            const list = document.getElementById(isRespondent ? 'respondent-list' : 'witness-list');
            const notice = checkbox.closest('.form-section')?.querySelector('.unknown-notice');
            if (!container || !list) return;

            container.hidden = checkbox.checked;
            container.querySelectorAll('input, select, textarea, button').forEach(control => {
                control.disabled = checkbox.checked;
            });
            if (notice) notice.hidden = !checkbox.checked;

            if (!checkbox.checked) {
                list.querySelectorAll(isRespondent ? '.respondent-item' : '.witness-item').forEach(item => {
                    if (isRespondent) updateRespondentFields(item);
                    else updateWitnessFields(item);
                });
            }
        }

        const respondentUnknown = document.getElementById('respondent_unknown');
        const witnessNone = document.getElementById('witness_none');
        respondentUnknown?.addEventListener('change', () => toggleUnknown('respondent', respondentUnknown));
        witnessNone?.addEventListener('change', () => toggleUnknown('witness', witnessNone));
        if (respondentUnknown) toggleUnknown('respondent', respondentUnknown);
        if (witnessNone) toggleUnknown('witness', witnessNone);

        function ensureDefaultPerson(kind) {
            const listId = kind === 'respondent' ? 'respondent-list' : 'witness-list';
            const list = document.getElementById(listId);
            if (!list || list.querySelector('.dynamic-item')) return;
            const skipped = document.querySelector(kind === 'respondent'
                ? 'input[name="respondent_unknown"]'
                : 'input[name="witness_none"]');
            if (skipped && skipped.checked) return;
            if (kind === 'respondent') {
                addRespondent();
            } else {
                addWitness();
            }
        }

        ensureDefaultPerson('respondent');
        ensureDefaultPerson('witness');

        function addPerson(listId, templateId) {
            const list = document.getElementById(listId);
            const template = document.getElementById(templateId);
            if (!list || !template) return;
            const item = template.content.cloneNode(true);
            const isSkipped = listId === 'respondent-list'
                ? document.querySelector('input[name="respondent_unknown"]')?.checked
                : document.querySelector('input[name="witness_none"]')?.checked;
            item.querySelectorAll('input, select, textarea, button').forEach(control => {
                control.disabled = !!isSkipped;
            });
            list.appendChild(item);
        }

        function addRespondent() {
            addPerson('respondent-list', 'respondent-item-template');
            const items = document.getElementById('respondent-list').querySelectorAll('.respondent-item');
            updateRespondentFields(items[items.length - 1]);
        }

        function addWitness() {
            addPerson('witness-list', 'witness-item-template');
            const items = document.getElementById('witness-list').querySelectorAll('.witness-item');
            updateWitnessFields(items[items.length - 1]);
        }

        function updateRespondentFields(item) {
            if (!item) return;
            const typeSelect = item.querySelector('[name="respondent_type[]"]');
            const type = typeSelect ? typeSelect.value : 'Student';
            if (!type) return;
            const requiredByType = {
                Student: [],
                Employee: [],
                'Private Individual': [],
                Other: []
            };
            const requiredNames = requiredByType[type] || [];
            item.querySelectorAll('[data-respondent-types]').forEach(field => {
                const visible = field.dataset.respondentTypes.split(',').includes(type);
                field.hidden = !visible;
                field.querySelectorAll('input, select').forEach(control => {
                    control.disabled = !visible;
                    control.required = visible && requiredNames.includes(control.name);
                });
            });
            syncRespondentPrograms(item, true);
        }

        function populatePrograms(collegeSelect, courseSelect, preserveSelection = false) {
            if (!collegeSelect || !courseSelect) return;
            const previous = preserveSelection ? courseSelect.value : '';
            const programs = programsByCollege[collegeSelect.value] || [];
            courseSelect.replaceChildren(new Option(
                programs.length ? 'Select Course/Program' : 'Select a college first',
                ''
            ));
            programs.forEach(program => courseSelect.add(new Option(program, program)));
            courseSelect.disabled = collegeSelect.disabled || programs.length === 0;
            if (previous && programs.includes(previous)) courseSelect.value = previous;
        }

        function syncRespondentPrograms(item, preserveSelection = false) {
            populatePrograms(
                item?.querySelector('[name="respondent_college[]"]'),
                item?.querySelector('[name="respondent_course[]"]'),
                preserveSelection
            );
            if (!preserveSelection) {
                const section = item?.querySelector('[name="respondent_section[]"]');
                const combined = item?.querySelector('[name="respondent_course_year[]"]');
                if (section) section.value = '';
                if (combined) combined.value = '';
            }
        }

        function syncComplainantPrograms(preserveSelection = false) {
            populatePrograms(
                document.getElementById('complainant_college'),
                document.getElementById('complainant_course'),
                preserveSelection
            );
            if (!preserveSelection) {
                document.getElementById('complainant_section').value = '';
                document.getElementById('complainant_course_year').value = '';
            }
        }

        function updateWitnessFields(item) {
            if (!item) return;
            const typeSelect = item.querySelector('[name="witness_type[]"]');
            const type = typeSelect ? typeSelect.value : 'Student';
            if (!type) return;
            const requiredByType = {
                Student: [],
                Employee: [],
                'Private Individual': [],
                Other: []
            };
            const requiredNames = requiredByType[type] || [];
            item.querySelectorAll('[data-witness-types]').forEach(field => {
                const visible = field.dataset.witnessTypes.split(',').includes(type);
                field.hidden = !visible;
                field.querySelectorAll('input, select').forEach(control => {
                    control.disabled = !visible;
                    control.required = visible && requiredNames.includes(control.name);
                });
            });
            syncWitnessPrograms(item, true);
        }

        function syncWitnessPrograms(item, preserveSelection = false) {
            populatePrograms(
                item?.querySelector('[name="witness_college[]"]'),
                item?.querySelector('[name="witness_course[]"]'),
                preserveSelection
            );
            if (!preserveSelection) {
                const section = item?.querySelector('[name="witness_section[]"]');
                if (section) section.value = '';
            }
        }

        function composeIncidentDatetime() {
            incidentDatetime.value = incidentDate.value && incidentTime.value ? `${incidentDate.value}T${incidentTime.value}` : '';
        }

        function composeCourseSections() {
            const course = document.getElementById('complainant_course').value;
            const section = document.getElementById('complainant_section').value;
            document.getElementById('complainant_course_year').value = course && section ? `${course} | ${section}` : '';

            document.querySelectorAll('.respondent-item').forEach(item => {
                const respondentCourse = item.querySelector('[name="respondent_course[]"]').value;
                const respondentSection = item.querySelector('[name="respondent_section[]"]').value;
                item.querySelector('[name="respondent_course_year[]"]').value = respondentCourse && respondentSection
                    ? `${respondentCourse} | ${respondentSection}`
                    : '';
            });
        }

        function yearLevelFromSection(section) {
            const mapping = {'1':'First Year','2':'Second Year','3':'Third Year','4':'Fourth Year','5':'Fifth Year','6':'Sixth Year'};
            return section ? (mapping[section.charAt(0)] || '') : '';
        }

        function updateComplainantFields(initial = false) {
            const selectedType = complainantTypeInput.value;
            const isStudent = selectedType === 'Student';
            if (!initial && activeComplainantType && activeComplainantType !== 'Student') {
                complainantIdentityCache[activeComplainantType] = {name: complainantNameInput.value, email: complainantEmailInput.value, contact: complainantContactInput.value};
            }
            document.querySelectorAll('[data-complainant-types]').forEach(field => {
                const visible = field.dataset.complainantTypes.split(',').includes(selectedType);
                field.hidden = !visible;
                field.querySelectorAll('input, select').forEach(control => {
                    if (control.dataset.typeRequired === undefined) control.dataset.typeRequired = control.required ? '1' : '0';
                    control.disabled = !visible;
                    control.required = visible && control.dataset.typeRequired === '1';
                });
            });
            if (isStudent) {
                complainantNameInput.value = studentAccountName;
                complainantEmailInput.value = studentAccountEmail;
                complainantNameInput.readOnly = true;
                complainantEmailInput.readOnly = true;
                if (!initial) complainantGenderInput.value = studentAccountGender;
                [
                    ['complainant_student_no', studentAccountStudentNo],
                    ['complainant_college', studentAccountCollege],
                    ['complainant_course', studentAccountCourse],
                    ['complainant_section', studentAccountSection],
                    ['complainant_contact', studentAccountPhone],
                ].forEach(([id, value]) => {
                    if (!value) return;
                    const control = document.getElementById(id);
                    if (control && !control.value.trim()) control.value = value;
                });
                if (studentAccountAge && !complainantAgeInput.value.trim()) complainantAgeInput.value = studentAccountAge;
            } else {
                complainantNameInput.readOnly = false;
                complainantEmailInput.readOnly = false;
                if (!initial) {
                    const cached = complainantIdentityCache[selectedType] || {};
                    complainantNameInput.value = cached.name || '';
                    complainantEmailInput.value = cached.email || '';
                    complainantContactInput.value = cached.contact || '';
                }
            }
            activeComplainantType = selectedType;
            document.getElementById('complainantHelp').textContent = isStudent ? 'Authenticated student information' : selectedType === 'Employee' ? 'Employee and department information' : 'Personal contact information';
            if (isStudent) syncComplainantPrograms(true);
            composeCourseSections();
        }

        function formatSize(bytes) {
            if (bytes < 1024) return `${bytes} B`;
            if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
            return `${(bytes / 1048576).toFixed(1)} MB`;
        }

        function hasEvidence() {
            const yes = document.querySelector('input[name="has_evidence"][value="yes"]');
            return !!(yes && yes.checked);
        }

        function validateEvidence() {
            if (!hasEvidence()) {
                evidenceInput.setCustomValidity('');
                evidenceError.textContent = '';
                return true;
            }
            const allowed = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
            const files = Array.from(evidenceInput.files);
            const invalid = files.find(file => file.size > 5 * 1024 * 1024 || !allowed.includes(file.name.split('.').pop().toLowerCase()));
            evidenceError.textContent = invalid ? `${invalid.name} must be an accepted file type and no larger than 5MB.` : '';
            evidenceInput.setCustomValidity('');
            return !invalid && files.length > 0;
        }

        function renderEvidence() {
            const files = Array.from(evidenceInput.files);
            evidenceList.replaceChildren();
            files.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'evidence-item';
                const details = document.createElement('div');
                details.innerHTML = '<i class="bi bi-file-earmark"></i>';
                const text = document.createElement('span');
                text.textContent = file.name;
                const size = document.createElement('small');
                size.textContent = formatSize(file.size);
                details.append(text, size);
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'evidence-remove';
                remove.setAttribute('aria-label', `Remove ${file.name}`);
                remove.innerHTML = '<i class="bi bi-x-lg"></i>';
                remove.addEventListener('click', () => {
                    const transfer = new DataTransfer();
                    files.filter((unused, fileIndex) => fileIndex !== index).forEach(remaining => transfer.items.add(remaining));
                    evidenceInput.files = transfer.files;
                    renderEvidence();
                    validateEvidence();
                });
                item.append(details, remove);
                evidenceList.appendChild(item);
            });
            validateEvidence();
        }

        function updateEvidenceField() {
            const field = document.getElementById('evidenceUploadField');
            const show = hasEvidence();
            field.hidden = !show;
            const controls = field.querySelectorAll('input, select');
            controls.forEach(control => {
                if (control.dataset.typeRequired === undefined) {
                    control.dataset.typeRequired = control.required ? '1' : '0';
                }
                control.disabled = !show;
                control.required = show && control.dataset.typeRequired === '1';
            });
            if (!show && evidenceInput.files.length > 0) {
                evidenceInput.value = '';
                renderEvidence();
            }
            validateEvidence();
        }

        function valueOf(id, fallback = 'Not provided') {
            const control = document.getElementById(id);
            if (!control) return fallback;
            const value = control.tagName === 'SELECT' ? control.options[control.selectedIndex]?.text : control.value;
            return String(value || '').trim() || fallback;
        }

        function addReviewGroup(title, rows) {
            const section = document.createElement('section');
            const heading = document.createElement('h3');
            heading.textContent = title;
            section.appendChild(heading);
            rows.forEach(([label, value]) => {
                const row = document.createElement('div');
                row.className = 'review-row';
                const name = document.createElement('span');
                name.textContent = label;
                const content = document.createElement('strong');
                content.textContent = value;
                row.append(name, content);
                section.appendChild(row);
            });
            reviewContent.appendChild(section);
        }

        function buildReview() {
            composeCourseSections();
            reviewContent.replaceChildren();
            addReviewGroup('Complainant Information', [
                ['Complainant Type', valueOf('complainant_type')],
                ['Full Name', valueOf('complainant_name')],
                ['Gender', document.getElementById('complainant_gender').value || 'Not provided'],
                ['Age', valueOf('complainant_age')],
                ...(complainantTypeInput.value === 'Student' ? [
                    ['Student Number', valueOf('complainant_student_no')],
                    ['College', valueOf('complainant_college')],
                    ['Course', valueOf('complainant_course')],
                    ['Year Level', yearLevelFromSection(document.getElementById('complainant_section').value) || 'Not provided'],
                    ['Section', valueOf('complainant_section')],
                ] : complainantTypeInput.value === 'Employee' ? [
                    ['Employee Number', valueOf('complainant_employee_no')],
                    ['College / Office / Department', valueOf('complainant_department')],
                    ['Position', valueOf('complainant_position')],
                ] : complainantTypeInput.value === 'Private Individual' ? [
                    ['Relationship to CLSU', valueOf('complainant_relationship')],
                ] : [
                    ['Affiliation / Organization', valueOf('complainant_affiliation')],
                    ['Relationship or Purpose', valueOf('complainant_purpose')],
                ]),
                ['Email', valueOf('complainant_email')],
                ['Contact', valueOf('complainant_contact')],
            ]);
            addReviewGroup('Incident and Complaint Details', [
                ['Incident Date and Time', `${valueOf('incident_date')} ${valueOf('incident_time')}`],
                ['Incident Location', valueOf('incident_location')],
                ['Complaint Statement', valueOf('complaint_details')],
            ]);
            const respondentNames = Array.from(document.querySelectorAll('.respondent-item')).map(item => {
                const input = item.querySelector('[name="respondent_name[]"]');
                const name = input.value.trim();
                if (!name) return '';
                const type = item.querySelector('[name="respondent_type[]"]').value;
                const typeLabel = type || 'Student';
                const details = type === 'Student'
                    ? [
                        item.querySelector('[name="respondent_student_no[]"]').value.trim(),
                        item.querySelector('[name="respondent_college[]"]')?.value.trim(),
                        item.querySelector('[name="respondent_course[]"]')?.value.trim(),
                        item.querySelector('[name="respondent_section[]"]')?.value.trim(),
                    ]
                    : type === 'Employee'
                        ? [
                            item.querySelector('[name="respondent_employee_no[]"]').value.trim(),
                            item.querySelector('[name="respondent_department[]"]')?.value.trim(),
                            item.querySelector('[name="respondent_position[]"]')?.value.trim(),
                        ]
                        : type === 'Other'
                            ? [item.querySelector('[name="respondent_affiliation[]"]').value.trim()]
                            : [];
                const contact = item.querySelector('[name="respondent_contact[]"]').value.trim();
                const email = item.querySelector('[name="respondent_email[]"]')?.value.trim();
                const parts = [typeLabel, ...details, contact, email].filter(Boolean);
                return name + (parts.length ? ` (${parts.join(', ')})` : '');
            }).filter(Boolean);
            const witnessNames = Array.from(document.querySelectorAll('.witness-item')).map(item => {
                const name = item.querySelector('[name="witness_name[]"]').value.trim();
                if (!name) return '';
                const type = item.querySelector('[name="witness_type[]"]').value;
                const typeLabel = type || 'Student';
                const details = type === 'Student'
                    ? [
                        item.querySelector('[name="witness_student_no[]"]').value.trim(),
                        item.querySelector('[name="witness_college[]"]')?.value.trim(),
                        item.querySelector('[name="witness_course[]"]')?.value.trim(),
                        item.querySelector('[name="witness_section[]"]')?.value.trim(),
                    ]
                    : type === 'Employee'
                        ? [
                            item.querySelector('[name="witness_employee_no[]"]').value.trim(),
                            item.querySelector('[name="witness_department[]"]')?.value.trim(),
                            item.querySelector('[name="witness_position[]"]')?.value.trim(),
                        ]
                        : type === 'Other'
                            ? [item.querySelector('[name="witness_affiliation[]"]').value.trim()]
                            : [];
                const contact = item.querySelector('[name="witness_contact[]"]').value.trim();
                const email = item.querySelector('[name="witness_email[]"]')?.value.trim();
                const parts = [typeLabel, ...details, contact, email].filter(Boolean);
                return name + (parts.length ? ` (${parts.join(', ')})` : '');
            }).filter(Boolean);
            const submittedStatements = [
                ...Array.from(document.querySelectorAll('.respondent-item')).map(item => {
                    const name = item.querySelector('[name="respondent_name[]"]')?.value.trim();
                    const details = item.querySelector('[name="respondent_details[]"]')?.value.trim();
                    return name && details ? `${name}: ${details}` : '';
                }),
                ...Array.from(document.querySelectorAll('.witness-item')).map(item => {
                    const name = item.querySelector('[name="witness_name[]"]')?.value.trim();
                    const statement = item.querySelector('[name="witness_statement[]"]')?.value.trim();
                    return name && statement ? `${name}: ${statement}` : '';
                }),
            ].filter(Boolean);
            const respondentUnknown = document.querySelector('input[name="respondent_unknown"]')?.checked;
            const witnessNone = document.querySelector('input[name="witness_none"]')?.checked;
            addReviewGroup('Respondent Information', [
                ['Respondents', respondentUnknown ? "I don't know the respondent" : (respondentNames.join('\n') || 'None provided')],
            ]);
            addReviewGroup('Witness Information', [
                ['Witnesses', witnessNone ? 'No witness provided' : (witnessNames.join('\n') || 'None provided')],
            ]);
            addReviewGroup('Statements and Other Information', [
                ['Additional Details / Statements', submittedStatements.join('\n') || 'None provided'],
            ]);
            addReviewGroup('Evidence and Attachments', [
                ['Supporting Evidence', hasEvidence() ? (Array.from(evidenceInput.files).map(file => file.name).join('\n') || 'Evidence indicated, but no files selected') : 'No evidence attached'],
            ]);
        }

        complaintDetails.addEventListener('input', () => { complaintCharacterCount.textContent = complaintDetails.value.length; });
        incidentDate.addEventListener('change', composeIncidentDatetime);
        incidentTime.addEventListener('change', composeIncidentDatetime);
        complainantTypeInput.addEventListener('change', () => updateComplainantFields(false));
        document.getElementById('complainant_college').addEventListener('change', () => syncComplainantPrograms(false));
        complaintForm.addEventListener('change', event => {
            if (event.target.matches('[name="complainant_course"], [name="complainant_section"], [name="respondent_course[]"], [name="respondent_section[]"]')) {
                composeCourseSections();
            }
        });
        document.getElementById('respondent-list').addEventListener('change', event => {
            if (event.target.matches('[name="respondent_type[]"]')) {
                updateRespondentFields(event.target.closest('.respondent-item'));
            } else if (event.target.matches('[name="respondent_college[]"]')) {
                syncRespondentPrograms(event.target.closest('.respondent-item'), false);
            }
        });
        document.querySelectorAll('.respondent-item').forEach(updateRespondentFields);
        document.getElementById('witness-list').addEventListener('change', event => {
            if (event.target.matches('[name="witness_type[]"]')) {
                updateWitnessFields(event.target.closest('.witness-item'));
            } else if (event.target.matches('[name="witness_college[]"]')) {
                syncWitnessPrograms(event.target.closest('.witness-item'), false);
            } else if (event.target.matches('[name="witness_course[]"]')) {
                const section = event.target.closest('.witness-item')?.querySelector('[name="witness_section[]"]');
                if (section) section.value = '';
            }
        });
        document.querySelectorAll('.witness-item').forEach(updateWitnessFields);
        evidenceInput.addEventListener('change', renderEvidence);
        document.querySelectorAll('input[name="has_evidence"]').forEach(radio => {
            radio.addEventListener('change', updateEvidenceField);
        });
        updateEvidenceField();
        certification.addEventListener('change', () => {
            confirmSubmit.disabled = !certification.checked;
        });
        document.getElementById('closeReview').addEventListener('click', () => reviewDialog.close());
        document.getElementById('editComplaint').addEventListener('click', () => reviewDialog.close());

        complaintForm.addEventListener('submit', event => {
            composeIncidentDatetime();
            composeCourseSections();
            validateEvidence();
            if (window.SICMSValidation && !SICMSValidation.run(complaintForm)) {
                event.preventDefault();
                complaintValidationNotice.hidden = false;
                return;
            }
            if (!complaintForm.checkValidity()) {
                event.preventDefault();
                complaintValidationNotice.hidden = false;
                return;
            }
            complaintValidationNotice.hidden = true;
            if (!confirmed) {
                event.preventDefault();
                certification.checked = false;
                confirmSubmit.disabled = true;
                buildReview();
                reviewDialog.showModal();
                return;
            }
            complaintFinalizing = true;
            clearTimeout(complaintDraftTimer);
            reviewButton.disabled = true;
            confirmSubmit.disabled = true;
            submissionProgress.classList.remove('hidden');
        });

        complaintForm.addEventListener('input', () => {
            complaintValidationNotice.hidden = true;
        });

        confirmSubmit.addEventListener('click', () => {
            if (!certification.checked || confirmSubmit.disabled) return;
            confirmSubmit.disabled = true;
            confirmSubmit.innerHTML = '<span class="sicms-processing-spinner" aria-hidden="true"></span> Submitting Complaint...';
            confirmed = true;
            complaintForm.requestSubmit(reviewButton);
        });

        complaintCharacterCount.textContent = complaintDetails.value.length;
        composeIncidentDatetime();
        composeCourseSections();
        updateComplainantFields(true);


        const submissionModalBackdrop = document.getElementById('submissionModal');
        const submissionModal = submissionModalBackdrop?.querySelector('.submission-modal');
        const submissionModalIcon = document.getElementById('submissionModalIcon');
        const submissionModalTitle = document.getElementById('submissionModalTitle');
        const submissionModalMessage = document.getElementById('submissionModalMessage');
        const submissionCaseNumber = document.getElementById('submissionCaseNumber');
        const submissionModalOkay = document.getElementById('submissionModalOkay');
        const submissionModalClose = document.getElementById('submissionModalClose');
        const submissionTrack = document.getElementById('submissionTrack');
        const submissionDashboard = document.getElementById('submissionDashboard');

        function closeSubmissionModal() {
            submissionModalBackdrop?.classList.remove('show');
            submissionModalBackdrop?.setAttribute('aria-hidden', 'true');
        }

        function showSubmissionModal(type, title, message, caseNumber = '') {
            if (!submissionModalBackdrop) return;
            submissionModal.classList.toggle('error', type === 'error');
            submissionModalIcon.innerHTML = type === 'error' ? '<i class="bi bi-exclamation-lg"></i>' : '<i class="bi bi-check-lg"></i>';
            submissionModalTitle.textContent = title;
            submissionModalMessage.textContent = message;
            submissionCaseNumber.hidden = !caseNumber;
            submissionCaseNumber.textContent = caseNumber ? 'Case Number: ' + caseNumber : '';
            submissionTrack.hidden = type !== 'success';
            submissionDashboard.hidden = type !== 'success';
            submissionModalBackdrop.classList.add('show');
            submissionModalBackdrop.setAttribute('aria-hidden', 'false');
        }

        submissionModalOkay?.addEventListener('click', closeSubmissionModal);
        submissionModalClose?.addEventListener('click', closeSubmissionModal);
        submissionModalBackdrop?.addEventListener('click', event => {
            if (event.target === submissionModalBackdrop) closeSubmissionModal();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeSubmissionModal();
        });

        <?php if ($success): ?>
        showSubmissionModal('success', 'Complaint Submitted Successfully', 'Your complaint has been successfully submitted to SDRU. You can track its status through Complaint Cases.', <?= json_encode($successCaseNumber) ?>);
        <?php elseif (!empty($errors)): ?>
        showSubmissionModal('error', 'Unable to Submit Complaint', <?= json_encode(implode("\n", $errors)) ?>);
        <?php endif; ?>

        <?php if ($complaintDraft && !$restoringDraft && !$success): ?>
        window.addEventListener('DOMContentLoaded', async () => {
            if (!window.Swal) return;
            const choice = await Swal.fire({
                icon: 'info',
                title: 'Continue your saved complaint?',
                text: 'A draft from <?= h(date('M d, Y h:i A', strtotime($complaintDraft['updated_at']))) ?> is available. Uploaded evidence is not stored with drafts.',
                showCancelButton: true,
                confirmButtonText: 'Continue Draft',
                cancelButtonText: 'Start New',
                reverseButtons: true,
                allowOutsideClick: false,
                backdrop: false
            });
            if (choice.isConfirmed) {
                location.replace('create.php?draft=continue');
                return;
            }
            const discard = await Swal.fire({
                icon: 'warning',
                title: 'Discard saved draft?',
                text: 'This cannot be undone.',
                showCancelButton: true,
                confirmButtonText: 'Discard Draft',
                cancelButtonText: 'Keep Draft',
                reverseButtons: true,
                backdrop: false
            });
            if (!discard.isConfirmed) return;
            const body = new FormData();
            body.set('csrf_token', complaintForm.elements.csrf_token.value);
            body.set('draft_action', 'discard');
            const response = await fetch('draft.php', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            if (response.ok) {
                complaintDraftVersion = 0;
                setComplaintDraftStatus('Draft discarded. Starting a new complaint.');
            } else {
                setComplaintDraftStatus('The saved draft could not be discarded. Please refresh and try again.', true);
            }
        });
        <?php endif; ?>

    </script>
</body>

</html>
