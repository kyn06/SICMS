<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/CounterStatement.php';
require_once __DIR__ . '/../models/ComplaintDraft.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Colleges.php';
require_once __DIR__ . '/../helpers/Courses.php';

class ComplaintController {
    private $database;
    private $db;
    private $user;
    private $classifications = [
        'Cyberbullying',
        'Physical Assault',
        'Intimidation, Threat and Harassment',
        'Forging, falsifying public documents, and misinterpretation of fact',
        'Sexual Harassment',
        'Bringing Intoxicating Beverages/Drinks within the University Premises',
        'Plagiarism',
        'Attempted Rape',
        'Consummated Rape',
        'Public Disturbance',
        'Hazing',
    ];

    public function __construct() {
        Security::startSession();

        $this->authenticate();
    }

    public function handleCreateRequest() {
        if (empty($_SESSION['complaint_submission_token'])) {
            $_SESSION['complaint_submission_token'] = bin2hex(random_bytes(24));
        }
        $submissionToken = (string) $_SESSION['complaint_submission_token'];
        $errors = $_SESSION['complaint_errors'] ?? [];
        $old = $_SESSION['complaint_old'] ?? [];
        $success = $_SESSION['complaint_success'] ?? null;

        $draft = ComplaintDraft::forAccount((int) $this->user['account_id']);
        $restoringDraft = $draft && ($_GET['draft'] ?? '') === 'continue';
        if ($restoringDraft && empty($old)) {
            $old = $draft['payload'];
        }

        unset($_SESSION['complaint_errors'], $_SESSION['complaint_old'], $_SESSION['complaint_success']);

        $fieldErrors = $this->takeSessionFieldErrors('complaint_field_errors');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->store();
        }

        return [
            'user' => $this->user,
            'classifications' => $this->classifications,
            'errors' => $errors,
            'fieldErrors' => $fieldErrors,
            'old' => $old,
            'success' => $success,
            'submissionToken' => $submissionToken,
            'complaintDraft' => $draft,
            'restoringDraft' => $restoringDraft,
        ];
    }

    private function takeSessionFieldErrors($key) {
        $fieldErrors = $_SESSION[$key] ?? [];
        unset($_SESSION[$key]);
        return is_array($fieldErrors) ? $fieldErrors : [];
    }

    public function handleTrackingRequest() {
        $filters = $this->trackingFilters($_GET);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 8;
        $total = Complaint::countForStudent((int) $this->user['account_id'], $filters);

        return [
            'user' => $this->user,
            'filters' => $filters,
            'cases' => Complaint::forStudent((int) $this->user['account_id'], $perPage, $filters, ($page - 1) * $perPage),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'statuses' => ['Under Investigation', 'Returned for Revision', 'Rejected', 'Resolved', 'Reformation in Progress', 'Reformation Completed', 'Escalated', 'Archived'],
            'classifications' => $this->classifications,
        ];
    }

    public function handleStudentCaseDetails($complaintId) {
        $complaintId = (int) $complaintId;
        $case = Complaint::findForStudent($complaintId, (int) $this->user['account_id']);

        if (!$case) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->handleComplaintResponseToCounterStatement($complaintId, $case);
        }

        $responseError = $_SESSION['complaint_response_error'] ?? null;
        $responseInfo = $_SESSION['complaint_response_info'] ?? null;
        unset($_SESSION['complaint_response_error'], $_SESSION['complaint_response_info']);

        Message::markAllRead((int) $this->user['account_id']);

        $messagePeer = Message::defaultCounterpartForCase($case, $this->user);

        $forwardedStatement = CounterStatement::forwardedForComplaint($complaintId);

        return [
            'user' => $this->user,
            'case' => $case,
            'respondents' => CaseRecord::getRespondents($complaintId),
            'witnesses' => CaseRecord::getWitnesses($complaintId),
            'evidence' => $this->studentVisibleEvidence($complaintId),
            'history' => CaseRecord::getHistory($complaintId),
            'hearings' => Complaint::hearingsForStudentCase($complaintId, (int) $this->user['account_id']),
            'messages' => $messagePeer
                ? Message::forPair((int) $this->user['account_id'], (int) $messagePeer['account_id'])
                : [],
            'messageReceiver' => $messagePeer,
            'counterStatement' => $forwardedStatement,
            'complaintResponse' => $forwardedStatement ? [
                'content' => (string) ($forwardedStatement['complaint_response_content'] ?? ''),
                'status' => !empty($forwardedStatement['complaint_response_submitted_at'])
                    ? 'Submitted'
                    : (trim((string) ($forwardedStatement['complaint_response_content'] ?? '')) !== '' ? 'Draft' : ''),
                'submitted_at' => $forwardedStatement['complaint_response_submitted_at'] ?? null,
                'forwarded_at' => $forwardedStatement['forwarded_at'] ?? null,
            ] : null,
            'complaintResponseClosed' => !in_array($case['status'] ?? '', ['Under Investigation'], true),
            'complaintResponseError' => $responseError,
            'complaintResponseInfo' => $responseInfo,
        ];
    }

    private function handleComplaintResponseToCounterStatement($complaintId, $case) {
        $accountId = (int) $this->user['account_id'];
        $caseLabel = $case['case_number'] ?? ('Case #' . $complaintId);
        $action = $_POST['case_action'] ?? '';

        $back = function () use ($complaintId) {
            header('Location: case_details.php?id=' . $complaintId);
            exit;
        };

        if (!in_array($action, ['save_complaint_response', 'submit_complaint_response'], true)) {
            $_SESSION['complaint_response_error'] = 'Invalid response action.';
            $back();
        }

        if (!in_array($case['status'] ?? '', ['Under Investigation'], true)) {
            $_SESSION['complaint_response_error'] = 'This case is closed. Responses can no longer be submitted.';
            $back();
        }

        $statement = CounterStatement::forwardedForComplaint($complaintId);
        if (!$statement) {
            $_SESSION['complaint_response_error'] = 'The SDRU has not forwarded the respondent counter-statement to you on this case.';
            $back();
        }

        if (!empty($statement['complaint_response_submitted_at'])) {
            $_SESSION['complaint_response_error'] = 'You have already submitted your response to the respondent counter-statement.';
            $back();
        }

        $content = trim((string) ($_POST['complaint_response'] ?? ''));
        if ($content === '') {
            $_SESSION['complaint_response_error'] = 'Please write your response before saving.';
            $back();
        }

        if ($action === 'save_complaint_response') {
            CounterStatement::saveComplaintResponse((int) $statement['counter_statement_id'], $content);
            $_SESSION['complaint_response_info'] = 'Draft response saved.';
        } else {
            CounterStatement::submitComplaintResponse((int) $statement['counter_statement_id'], $content);
            CaseRecord::recordCaseActivity(
                $complaintId,
                'Complaint Response Submitted',
                'The complainant submitted a response to the counter-statement on ' . $caseLabel . '.',
                $accountId
            );
            $_SESSION['complaint_response_info'] = 'Your response was submitted. The SDRU will review it and continue the investigation.';
        }

        $back();
    }

    public function handleRevisionRequest($complaintId) {
        $complaintId = (int) $complaintId;
        $case = Complaint::findForStudent($complaintId, (int) $this->user['account_id']);

        if (!$case || $case['status'] !== 'Returned for Revision') {
            http_response_code(403);
            echo 'This complaint is not available for revision.';
            exit;
        }

        $revision = CaseRecord::getLatestRevisionRequest($complaintId);
        if (!$revision || empty($revision['revision_fields'])) {
            http_response_code(409);
            echo 'Revision instructions are incomplete. Please contact SDRU.';
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->storeRevision($case, $revision);
        }

        $errors = $_SESSION['revision_errors'] ?? [];
        $old = $_SESSION['revision_old'] ?? [];
        unset($_SESSION['revision_errors'], $_SESSION['revision_old']);

        $fieldErrors = $this->takeSessionFieldErrors('revision_field_errors');

        return [
            'user' => $this->user,
            'case' => $case,
            'revision' => $revision,
            'respondents' => CaseRecord::getRespondents($complaintId),
            'witnesses' => CaseRecord::getWitnesses($complaintId),
            'evidence' => $this->studentVisibleEvidence($complaintId),
            'errors' => $errors,
            'fieldErrors' => $fieldErrors,
            'old' => $old,
        ];
    }

    private function studentVisibleEvidence($complaintId) {
        $evidence = CaseRecord::getEvidence($complaintId);
        return array_values(array_filter($evidence, function ($file) {
            return empty($file['counter_statement_id']);
        }));
    }

    private function authenticate() {
        if (!isset($_SESSION['email'])) {
            header('Location: ../auth/login.php');
            exit;
        }

        $this->database = new Database();
        $this->db = $this->database->getConnection();

        User::setConnection($this->db);
        Complaint::setConnection($this->db);
        CaseRecord::setConnection($this->db);
        Message::setConnection($this->db);
        Notification::setConnection($this->db);
        AuditLog::setConnection($this->db);
        CounterStatement::setConnection($this->db);
        ComplaintDraft::setConnection($this->db);

        $this->user = User::findByEmail($_SESSION['email']);

        if (!$this->user || $this->user['status'] !== 'active') {
            session_unset();
            session_destroy();
            header('Location: ../auth/login.php');
            exit;
        }

        if ($this->normalizeRole($this->user['role']) !== 'student') {
            http_response_code(403);
            echo 'Access denied. Complaint submission is only available to student accounts.';
            exit;
        }
    }

    private function normalizeRole($role) {
        return strtolower(str_replace(['_', ' '], '-', $role));
    }

    private function trackingFilters(array $input) {
        $sort = $input['sort'] ?? 'newest';

        return [
            'case_number' => trim($input['case_number'] ?? ''),
            'status' => trim($input['status'] ?? ''),
            'classification' => trim($input['classification'] ?? ''),
            'date_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($input['date_from'] ?? '')) ? $input['date_from'] : '',
            'date_to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($input['date_to'] ?? '')) ? $input['date_to'] : '',
            'academic_year' => preg_match('/^\d{4}$/', (string) ($input['academic_year'] ?? '')) ? $input['academic_year'] : '',
            'sort' => in_array($sort, ['newest', 'oldest', 'updated'], true) ? $sort : 'newest',
        ];
    }

    private function store() {
        $submissionToken = (string) ($_POST['submission_token'] ?? '');
        $sessionToken = (string) ($_SESSION['complaint_submission_token'] ?? '');
        $processedTokens = (array) ($_SESSION['processed_complaint_tokens'] ?? []);
        if ($submissionToken === '' || !hash_equals($sessionToken, $submissionToken)) {
            $_SESSION['complaint_errors'] = ['This complaint form is outdated. Please review it and submit again.'];
            header('Location: create.php');
            exit;
        }
        if (isset($processedTokens[$submissionToken])) {
            $_SESSION['complaint_success'] = $processedTokens[$submissionToken];
            header('Location: create.php');
            exit;
        }

        $validation = $this->validate($_POST, $_FILES);
        $errors = $validation['errors'];
        $fieldErrors = $validation['fields'];

        if (!empty($errors) || !empty($fieldErrors)) {
            $_SESSION['complaint_errors'] = $errors;
            $_SESSION['complaint_field_errors'] = $fieldErrors;
            $_SESSION['complaint_old'] = $_POST;
            header('Location: create.php');
            exit;
        }

        $savedFiles = [];

        try {
            if (trim((string) ($_POST['has_evidence'] ?? '')) === 'yes') {
                $savedFiles = $this->saveEvidenceFiles($_FILES['evidence']);
            }
            $now = date('Y-m-d H:i:s');
            $complainantType = in_array($_POST['complainant_type'] ?? '', ['Student', 'Employee', 'Private Individual', 'Others'], true) ? $_POST['complainant_type'] : 'Student';
            $isStudentComplainant = $complainantType === 'Student';

            $complaint = [
                'complaint_title' => 'Student Complaint',
                'submitted_by_account_id' => (int) $this->user['account_id'],
                'complainant_type' => $complainantType,
                'complainant_name' => $isStudentComplainant ? trim($this->user['first_name'] . ' ' . $this->user['last_name']) : trim($_POST['complainant_name']),
                'complainant_gender' => $this->normalizedGender($_POST['complainant_gender'] ?? ($isStudentComplainant ? ($this->user['gender'] ?? '') : '')),
                'complainant_age' => (int) $_POST['complainant_age'],
                'complainant_relationship' => $complainantType === 'Private Individual' ? trim($_POST['complainant_relationship'] ?? '') : '',
                'complainant_employee_no' => $complainantType === 'Employee' ? trim($_POST['complainant_employee_no'] ?? '') : '',
                'complainant_department' => $complainantType === 'Employee' ? trim($_POST['complainant_department'] ?? '') : '',
                'complainant_position' => $complainantType === 'Employee' ? trim($_POST['complainant_position'] ?? '') : '',
                'complainant_affiliation' => $complainantType === 'Others' ? trim($_POST['complainant_affiliation'] ?? '') : '',
                'complainant_purpose' => $complainantType === 'Others' ? trim($_POST['complainant_purpose'] ?? '') : '',
                'complainant_student_no' => $isStudentComplainant ? trim($_POST['complainant_student_no'] ?? '') : '',
                'complainant_email' => $isStudentComplainant ? trim($this->user['email']) : trim($_POST['complainant_email'] ?? ''),
                'complainant_contact' => trim($_POST['complainant_contact']),
                'complainant_college' => $isStudentComplainant ? trim($_POST['complainant_college'] ?? '') : '',
                'complainant_course' => $isStudentComplainant ? Courses::canonical($_POST['complainant_course'] ?? '') : '',
                'complainant_year_level' => $isStudentComplainant ? Courses::yearLevel(trim($_POST['complainant_section'] ?? '')) : '',
                'complainant_section' => $isStudentComplainant ? trim($_POST['complainant_section'] ?? '') : '',
                'complainant_course_year' => $isStudentComplainant ? trim($_POST['complainant_course_year'] ?? '') : '',
                'case_classification' => 'Unclassified',
                'incident_datetime' => date('Y-m-d H:i:s', strtotime($_POST['incident_datetime'])),
                'incident_location' => trim($_POST['incident_location']),
                'complaint_details' => trim($_POST['complaint_details']),
                'status' => 'Under Investigation',
                'submitted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $createdComplaint = Complaint::createComplaint(
                $complaint,
                $this->normalizeRespondents($_POST),
                $this->normalizeWitnesses($_POST),
                $savedFiles
            );

            $_SESSION['complaint_success'] = 'Complaint submitted successfully. Case Number: ' . $createdComplaint['case_number'];
            $_SESSION['processed_complaint_tokens'][$submissionToken] = $_SESSION['complaint_success'];
            $_SESSION['processed_complaint_tokens'] = array_slice($_SESSION['processed_complaint_tokens'], -5, null, true);
            ComplaintDraft::deleteForAccount((int) $this->user['account_id']);
            unset($_SESSION['complaint_submission_token']);

            Notification::createForUser(
                (int) $this->user['account_id'],
                'complaint_submitted',
                'Complaint Submitted',
                'Your complaint was submitted successfully. Case Number: ' . $createdComplaint['case_number'],
                'web/views/complaints/create.php'
            );

            Notification::createForStaff(
                'complaint_submitted',
                'New Complaint Submitted',
                $createdComplaint['case_number'] . ' was submitted by ' . $complaint['complainant_name'] . '.',
                'web/views/cases/show.php?id=' . $createdComplaint['complaint_id']
            );

            AuditLog::record(
                $this->user,
                'Complaint Submission',
                'Submitted complaint ' . $createdComplaint['case_number'] . '.'
            );

            header('Location: create.php');
            exit;
        } catch (Throwable $exception) {
            foreach ($savedFiles as $file) {
                $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $file['file_path'];
                if (is_file($absolutePath)) {
                    unlink($absolutePath);
                }
            }

            error_log('[SICMS Complaint Submission] ' . get_class($exception) . ': ' . $exception->getMessage());
            $_SESSION['complaint_errors'] = [
                'Unable to submit complaint. Please try again.',
            ];
            $_SESSION['complaint_old'] = $_POST;
            header('Location: create.php');
            exit;
        }
    }

    private function validate(array $post, array $files) {
        $errors = [];
        $fieldErrors = [];
        $field = function (string $key, string $message) use (&$fieldErrors) {
            $fieldErrors[$key] = $message;
        };
        $complainantType = trim((string) ($post['complainant_type'] ?? ''));

        if (!in_array($complainantType, ['Student', 'Employee', 'Private Individual', 'Others'], true)) {
            $field('complainant_type', 'Please select a valid complainant type.');
        }

        $required = [
            'complainant_name' => 'Complainant name is required.',
            'complainant_email' => 'Complainant email is required.',
            'complainant_contact' => 'Complainant contact number is required.',
            'incident_datetime' => 'Incident date and time is required.',
            'incident_location' => 'Incident location is required.',
            'complaint_details' => 'Complaint details are required.',
        ];

        foreach ($required as $fieldName => $message) {
            if (empty(trim($post[$fieldName] ?? ''))) {
                $field($fieldName, $message);
            }
        }

        $contact = trim((string) ($post['complainant_contact'] ?? ''));
        if ($contact !== '' && !preg_match('/^[0-9+()\-\s.]{7,20}$/', $contact)) {
            $field('complainant_contact', 'Please enter a valid contact number using digits, spaces, +, -, or parentheses (7 to 20 characters).');
        }

        $incident = trim((string) ($post['incident_datetime'] ?? ''));
        if ($incident !== '' && strtotime($incident) !== false && strtotime($incident) > time()) {
            $field('incident_datetime', 'Incident date cannot be in the future.');
        }

        if ($complainantType === 'Student') {
            foreach (['complainant_student_no' => 'Student number', 'complainant_college' => 'College', 'complainant_course' => 'Course', 'complainant_section' => 'Section'] as $fieldName => $label) {
                if (trim((string) ($post[$fieldName] ?? '')) === '') $field($fieldName, $label . ' is required for student complainants.');
            }
            $validSections = array_merge(...array_values(Courses::sections()));
            if (!Colleges::contains($post['complainant_college'] ?? '')) $field('complainant_college', 'Please select a valid college.');
            if (!Courses::belongsToCollege($post['complainant_course'] ?? '', $post['complainant_college'] ?? '')) {
                $field('complainant_course', 'Please select a course offered by the selected college.');
            }
            if (!in_array($post['complainant_section'] ?? '', $validSections, true)) $field('complainant_section', 'Please select a valid section.');
        } elseif ($complainantType === 'Employee') {
            foreach (['complainant_employee_no' => 'Employee number', 'complainant_department' => 'College, office, or department', 'complainant_position' => 'Position'] as $fieldName => $label) {
                if (trim((string) ($post[$fieldName] ?? '')) === '') $field($fieldName, $label . ' is required for employee complainants.');
            }
        } elseif ($complainantType === 'Private Individual' && !in_array(trim((string) ($post['complainant_relationship'] ?? '')), ['', 'Parent', 'Guardian', 'Alumni', 'Visitor', 'Community Member', 'Other'], true)) {
            $field('complainant_relationship', 'Please select a valid relationship to CLSU.');
        }

        if (!empty($post['complainant_gender']) && !in_array($post['complainant_gender'], ['Male', 'Female'], true)) {
            $field('complainant_gender', 'Please select a valid complainant gender.');
        }

        $age = trim((string) ($post['complainant_age'] ?? ''));
        if ($age === '') {
            $field('complainant_age', 'Complainant age is required.');
        } elseif (!ctype_digit($age) || (int) $age < 1 || (int) $age > 120) {
            $field('complainant_age', 'Please enter a valid complainant age (1 to 120).');
        }

        if (!empty($post['complainant_email']) && !filter_var($post['complainant_email'], FILTER_VALIDATE_EMAIL)) {
            $field('complainant_email', 'Please enter a valid complainant email address.');
        }

        $hasEvidence = trim((string) ($post['has_evidence'] ?? '')) === 'yes';
        if (!in_array(trim((string) ($post['has_evidence'] ?? '')), ['yes', 'no'], true)) {
            $errors[] = 'Please indicate whether you have supporting evidence to submit.';
        }

        $evidenceFiles = $files['evidence'] ?? [];
        if (!$hasEvidence) {
            $evidenceFiles = [];
        } elseif (empty($evidenceFiles['name'][0])) {
            $errors[] = 'At least one supporting evidence file is required.';
        }

        $errors = array_merge($errors, (array) $this->validateRespondents($post));
        $errors = array_merge($errors, (array) $this->validateWitnesses($post));

        return ['errors' => array_merge($errors, $this->validateEvidenceFiles($evidenceFiles)), 'fields' => $fieldErrors];
    }

    private function storeRevision(array $case, array $revision) {
        $allowed = array_values(array_intersect(
            ['complaint_details', 'incident_date', 'incident_time', 'incident_location', 'respondents', 'witnesses', 'evidence'],
            $revision['revision_fields']
        ));
        $errors = [];
        $fieldErrors = [];
        $field = function (string $key, string $message) use (&$fieldErrors) {
            $fieldErrors[$key] = $message;
        };

        if (empty($_POST['revision_confirmation'])) $errors[] = 'Please confirm that you completed all requested revisions.';
        foreach (['complaint_details', 'incident_date', 'incident_time', 'incident_location'] as $fieldName) {
            if (in_array($fieldName, $allowed, true) && trim((string) ($_POST[$fieldName] ?? '')) === '') {
                $field($fieldName, ucwords(str_replace('_', ' ', $fieldName)) . ' is required.');
            }
        }

        $respondents = null;
        $witnesses = null;
        if (in_array('respondents', $allowed, true)) {
            if (!empty($_POST['respondent_unknown'])) {
                $respondents = [];
            } else {
                $respondents = $this->normalizeRespondents($_POST);
                if (!$respondents) $errors[] = 'At least one respondent is required.';
                $errors = array_merge($errors, $this->validateRespondents($_POST));
            }
        }
        if (in_array('witnesses', $allowed, true)) {
            if (!empty($_POST['witness_none'])) {
                $witnesses = [];
            } else {
                $witnesses = $this->normalizeWitnesses($_POST);
                if (!$witnesses) $errors[] = 'At least one witness is required.';
                $errors = array_merge($errors, $this->validateWitnesses($_POST));
            }
        }

        $newFiles = $_FILES['evidence'] ?? [];
        if (in_array('evidence', $allowed, true) && !empty($newFiles['name'][0])) {
            $errors = array_merge($errors, $this->validateEvidenceFiles($newFiles));
        }
        if (in_array('evidence', $allowed, true)) {
            $existingEvidence = CaseRecord::getEvidence((int) $case['complaint_id']);
            $existingIds = array_map('intval', array_column($existingEvidence, 'evidence_id'));
            $removeIds = array_intersect($existingIds, array_map('intval', (array) ($_POST['remove_evidence'] ?? [])));
            $newFileCount = count(array_filter((array) ($newFiles['name'] ?? [])));
            if (count($existingIds) - count($removeIds) + $newFileCount < 1) $errors[] = 'At least one supporting evidence file must remain.';
        }

        if ($errors) {
            $_SESSION['revision_errors'] = $errors;
            $_SESSION['revision_field_errors'] = $fieldErrors;
            $_SESSION['revision_old'] = $_POST;
            header('Location: revise.php?id=' . (int) $case['complaint_id']);
            exit;
        }

        $updates = [];
        foreach (['complaint_details', 'incident_location'] as $field) {
            if (in_array($field, $allowed, true)) $updates[$field] = trim($_POST[$field]);
        }
        if (in_array('incident_date', $allowed, true) || in_array('incident_time', $allowed, true)) {
            $existing = strtotime($case['incident_datetime']);
            $date = in_array('incident_date', $allowed, true) ? $_POST['incident_date'] : date('Y-m-d', $existing);
            $time = in_array('incident_time', $allowed, true) ? $_POST['incident_time'] : date('H:i', $existing);
            $timestamp = strtotime($date . ' ' . $time);
            if (!$timestamp) {
                $_SESSION['revision_errors'] = ['Please provide a valid incident date and time.'];
                $_SESSION['revision_old'] = $_POST;
                header('Location: revise.php?id=' . (int) $case['complaint_id']);
                exit;
            }
            if ($timestamp > time()) {
                $_SESSION['revision_errors'] = [];
                $_SESSION['revision_field_errors'] = ['incident_date' => 'Incident date cannot be in the future.'];
                $_SESSION['revision_old'] = $_POST;
                header('Location: revise.php?id=' . (int) $case['complaint_id']);
                exit;
            }
            $updates['incident_datetime'] = date('Y-m-d H:i:s', $timestamp);
        }

        $savedFiles = [];
        try {
            if (in_array('evidence', $allowed, true) && !empty($newFiles['name'][0])) $savedFiles = $this->saveEvidenceFiles($newFiles);
            $removeIds = in_array('evidence', $allowed, true) ? array_map('intval', (array) ($_POST['remove_evidence'] ?? [])) : [];
            $removedPaths = Complaint::submitRevision(
                (int) $case['complaint_id'], (int) $this->user['account_id'], $updates,
                $respondents, $witnesses, $savedFiles, $removeIds
            );
            foreach ($removedPaths as $path) {
                $absolute = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
                if (is_file($absolute)) unlink($absolute);
            }

            try {
                $recipientIds = array_unique(array_filter([
                    (int) ($revision['created_by_account_id'] ?? 0),
                    (int) ($case['assigned_coordinator_account_id'] ?? 0),
                ]));
                foreach ($recipientIds as $recipientId) {
                    Notification::createForUser($recipientId, 'complaint_revised', 'Revised Complaint Submitted',
                        $case['case_number'] . ' was revised and submitted for verification.',
                        'web/views/cases/show.php?id=' . (int) $case['complaint_id']);
                }
                AuditLog::record($this->user, 'Complaint Revision', 'Submitted revisions for ' . $case['case_number'] . '. Fields: ' . implode(', ', $allowed) . '.');
            } catch (Throwable $notificationException) {
                // The committed revision remains valid if a secondary notification cannot be created.
            }
            $_SESSION['complaint_success'] = 'Complaint Revised Successfully. Your revised complaint is awaiting SDRU verification.';
            header('Location: my_cases.php');
            exit;
        } catch (Throwable $exception) {
            foreach ($savedFiles as $file) {
                $absolute = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file['file_path']);
                if (is_file($absolute)) unlink($absolute);
            }
            $_SESSION['revision_errors'] = ['Unable to submit the revised complaint. Please try again.'];
            $_SESSION['revision_old'] = $_POST;
            header('Location: revise.php?id=' . (int) $case['complaint_id']);
            exit;
        }
    }

    private function normalizeRespondents(array $post) {
        $respondents = [];
        if (!empty($post['respondent_unknown'])) {
            return $respondents;
        }
        $names = $post['respondent_name'] ?? [];
        $validTypes = ['Student', 'Employee', 'Private Individual', 'Other'];

        foreach ($names as $index => $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $type = trim($post['respondent_type'][$index] ?? '');
            if (!in_array($type, $validTypes, true)) $type = 'Student';

            $isStudent = $type === 'Student';
            $course = $isStudent ? trim($post['respondent_course'][$index] ?? '') : '';
            $section = $isStudent ? trim($post['respondent_section'][$index] ?? '') : '';
            $courseYear = Courses::combine($course, $section);
            if ($courseYear === '' && ($course !== '' || $section !== '')) {
                $courseYear = trim($course . ' | ' . $section, ' |');
            }

            $respondents[] = [
                'respondent_type' => $type,
                'full_name' => $name,
                'gender' => $this->normalizedGender($post['respondent_gender'][$index] ?? ''),
                'age' => $this->personAge($post['respondent_age'][$index] ?? ''),
                'student_no' => $isStudent ? trim($post['respondent_student_no'][$index] ?? '') : '',
                'employee_no' => trim($post['respondent_employee_no'][$index] ?? ''),
                'college' => $isStudent ? trim($post['respondent_college'][$index] ?? '') : '',
                'office_department' => trim($post['respondent_department'][$index] ?? ''),
                'course_year' => $courseYear,
                'position' => trim($post['respondent_position'][$index] ?? ''),
                'affiliation' => trim($post['respondent_affiliation'][$index] ?? ''),
                'contact_info' => trim($post['respondent_contact'][$index] ?? ''),
                'email' => trim($post['respondent_email'][$index] ?? ''),
                'address' => trim($post['respondent_address'][$index] ?? ''),
                'details' => trim($post['respondent_details'][$index] ?? ''),
            ];
        }

        return $respondents;
    }

    private function validateRespondents(array $post) {
        $errors = [];

        if (!empty($post['respondent_unknown'])) {
            return $errors;
        }

        $respondentNames = array_filter(array_map('trim', (array) ($post['respondent_name'] ?? [])), 'strlen');
        if (!$respondentNames) {
            $errors[] = 'At least one respondent is required, or select "I don\'t know the respondent".';
            return $errors;
        }

        foreach (($post['respondent_name'] ?? []) as $index => $name) {
            if (trim((string) $name) === '') {
                continue;
            }

            $type = trim((string) ($post['respondent_type'][$index] ?? ''));
            if ($type === '') $type = 'Student';
            if (!in_array($type, ['Student', 'Employee', 'Private Individual', 'Other'], true)) {
                $errors[] = 'Please select a valid respondent type.';
                continue;
            }

            $errors = array_merge($errors, $this->validatePerson($post, 'respondent', $index, ucfirst(strtolower($type)) . ' respondent'));
        }

        return $errors;
    }

    private function validateWitnesses(array $post) {
        $errors = [];

        if (!empty($post['witness_none'])) {
            return $errors;
        }

        $witnessNames = array_filter(array_map('trim', (array) ($post['witness_name'] ?? [])), 'strlen');
        if (!$witnessNames) {
            $errors[] = 'At least one witness is required, or select "I don\'t have a witness".';
            return $errors;
        }

        foreach (($post['witness_name'] ?? []) as $index => $name) {
            if (trim((string) $name) === '') {
                continue;
            }

            $type = trim((string) ($post['witness_type'][$index] ?? ''));
            if ($type === '') $type = 'Student';
            if (!in_array($type, ['Student', 'Employee', 'Private Individual', 'Other'], true)) {
                $errors[] = 'Please select a valid witness type.';
                continue;
            }

            $errors = array_merge($errors, $this->validatePerson($post, 'witness', $index, ucfirst(strtolower($type)) . ' witness'));
        }

        return $errors;
    }

    private function validatePerson(array $post, $prefix, $index, $label) {
        $errors = [];

        $typeField = $prefix === 'witness' ? 'witness_type' : 'respondent_type';
        $type = trim((string) ($post[$typeField][$index] ?? ''));
        if (in_array($prefix, ['respondent', 'witness'], true) && $type === 'Student') {
            $college = trim((string) ($post[$prefix . '_college'][$index] ?? ''));
            $course = trim((string) ($post[$prefix . '_course'][$index] ?? ''));
            if ($college !== '' && !Colleges::contains($college)) {
                $errors[] = 'Please select a valid college for each ' . $label . '.';
            }
            if ($course !== '' && !Courses::belongsToCollege($course, $college)) {
                $errors[] = 'Please select a course offered by the selected college for each ' . $label . '.';
            }
        }

        $gender = trim((string) ($post[$prefix . '_gender'][$index] ?? ''));
        if ($gender !== '' && !in_array($gender, ['Male', 'Female'], true)) {
            $errors[] = 'Please select a valid gender for each ' . $label . '.';
        }

        $age = trim((string) ($post[$prefix . '_age'][$index] ?? ''));
        if ($age !== '' && $this->personAge($age) === null) {
            $errors[] = 'Please enter a valid age (1 to 120) for each ' . $label . '.';
        }

        $email = trim((string) ($post[$prefix . '_email'][$index] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address for each ' . $label . '.';
        }

        return $errors;
    }

    private function personAge($value) {
        $value = trim((string) $value);
        if (!ctype_digit($value)) {
            return null;
        }
        $age = (int) $value;
        return ($age >= 1 && $age <= 120) ? $age : null;
    }

    private function normalizedGender($value) {
        $value = trim((string) $value);
        return in_array($value, ['Male', 'Female'], true) ? $value : '';
    }

    private function normalizeWitnesses(array $post) {
        $witnesses = [];
        if (!empty($post['witness_none'])) {
            return $witnesses;
        }
        $names = $post['witness_name'] ?? [];
        $validTypes = ['Student', 'Employee', 'Private Individual', 'Other'];

        foreach ($names as $index => $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $type = trim($post['witness_type'][$index] ?? '');
            if (!in_array($type, $validTypes, true)) $type = 'Student';

            $isStudent = $type === 'Student';
            $course = $isStudent ? trim($post['witness_course'][$index] ?? '') : '';
            $section = $isStudent ? trim($post['witness_section'][$index] ?? '') : '';
            $courseYear = Courses::combine($course, $section);
            if ($courseYear === '' && ($course !== '' || $section !== '')) {
                $courseYear = trim($course . ' | ' . $section, ' |');
            }

            $witnesses[] = [
                'person_type' => $type,
                'full_name' => $name,
                'gender' => $this->normalizedGender($post['witness_gender'][$index] ?? ''),
                'age' => $this->personAge($post['witness_age'][$index] ?? ''),
                'student_no' => $isStudent ? trim($post['witness_student_no'][$index] ?? '') : '',
                'contact_info' => trim($post['witness_contact'][$index] ?? ''),
                'email' => trim($post['witness_email'][$index] ?? ''),
                'address' => trim($post['witness_address'][$index] ?? ''),
                'statement' => trim($post['witness_statement'][$index] ?? ''),
                'employee_no' => trim($post['witness_employee_no'][$index] ?? ''),
                'college' => $isStudent ? trim($post['witness_college'][$index] ?? '') : '',
                'office_department' => trim($post['witness_department'][$index] ?? ''),
                'position' => trim($post['witness_position'][$index] ?? ''),
                'affiliation' => trim($post['witness_affiliation'][$index] ?? ''),
                'course_year' => $courseYear,
            ];
        }

        return $witnesses;
    }

    private function validateEvidenceFiles(array $files) {
        $errors = [];
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $maxBytes = 5 * 1024 * 1024;

        foreach (($files['name'] ?? []) as $index => $name) {
            if ($name === '') {
                continue;
            }

            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = $name . ' could not be uploaded.';
                continue;
            }

            if (empty($files['tmp_name'][$index]) || !is_uploaded_file($files['tmp_name'][$index])) {
                $errors[] = $name . ' is not a valid uploaded file.';
                continue;
            }

            if (($files['size'][$index] ?? 0) > $maxBytes) {
                $errors[] = $name . ' exceeds the 5MB file limit.';
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = $name . ' has an invalid file type.';
                continue;
            }

            $mimeType = mime_content_type($files['tmp_name'][$index]);

            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                $errors[] = $name . ' has an invalid file content type.';
            }
        }

        return $errors;
    }

    private function saveEvidenceFiles(array $files) {
        $savedFiles = [];
        $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($files['name'] as $index => $name) {
            if ($name === '') {
                continue;
            }

            if (empty($files['tmp_name'][$index]) || !is_uploaded_file($files['tmp_name'][$index])) {
                throw new Exception('Invalid uploaded file.');
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $storedFilename = date('YmdHis') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
            $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedFilename;

            if (!move_uploaded_file($files['tmp_name'][$index], $destination)) {
                throw new Exception('Unable to save uploaded file.');
            }

            $savedFiles[] = [
                'original_filename' => basename($name),
                'stored_filename' => $storedFilename,
                'file_path' => 'storage/evidence/' . $storedFilename,
                'mime_type' => mime_content_type($destination),
                'file_size' => (int) $files['size'][$index],
            ];
        }

        return $savedFiles;
    }
}
