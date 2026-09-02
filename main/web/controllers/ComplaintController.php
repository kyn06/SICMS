<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/AuditLog.php';
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
        $errors = $_SESSION['complaint_errors'] ?? [];
        $old = $_SESSION['complaint_old'] ?? [];
        $success = $_SESSION['complaint_success'] ?? null;

        unset($_SESSION['complaint_errors'], $_SESSION['complaint_old'], $_SESSION['complaint_success']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->store();
        }

        return [
            'user' => $this->user,
            'classifications' => $this->classifications,
            'errors' => $errors,
            'old' => $old,
            'success' => $success,
        ];
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
            'statuses' => ['Submitted', 'Verified', 'Returned for Revision', 'Rejected', 'Resolved', 'Archived'],
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

        Message::markAllRead((int) $this->user['account_id']);

        $messagePeer = Message::defaultCounterpartForCase($case, $this->user);

        return [
            'user' => $this->user,
            'case' => $case,
            'respondents' => CaseRecord::getRespondents($complaintId),
            'witnesses' => CaseRecord::getWitnesses($complaintId),
            'evidence' => CaseRecord::getEvidence($complaintId),
            'history' => CaseRecord::getHistory($complaintId),
            'hearings' => Complaint::hearingsForStudentCase($complaintId, (int) $this->user['account_id']),
            'messages' => $messagePeer
                ? Message::forPair((int) $this->user['account_id'], (int) $messagePeer['account_id'])
                : [],
            'messageReceiver' => $messagePeer,
        ];
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

        return [
            'user' => $this->user,
            'case' => $case,
            'revision' => $revision,
            'respondents' => CaseRecord::getRespondents($complaintId),
            'witnesses' => CaseRecord::getWitnesses($complaintId),
            'evidence' => CaseRecord::getEvidence($complaintId),
            'errors' => $errors,
            'old' => $old,
        ];
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
        $errors = $this->validate($_POST, $_FILES);

        if (!empty($errors)) {
            $_SESSION['complaint_errors'] = $errors;
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
                'complaint_title' => trim($_POST['case_classification']),
                'submitted_by_account_id' => (int) $this->user['account_id'],
                'complainant_type' => $complainantType,
                'complainant_name' => $isStudentComplainant ? trim($this->user['first_name'] . ' ' . $this->user['last_name']) : trim($_POST['complainant_name']),
                'complainant_gender' => $this->normalizedGender($_POST['complainant_gender'] ?? ($isStudentComplainant ? ($this->user['gender'] ?? '') : '')),
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
                'complainant_course' => $isStudentComplainant ? trim($_POST['complainant_course'] ?? '') : '',
                'complainant_year_level' => $isStudentComplainant ? Courses::yearLevel(trim($_POST['complainant_section'] ?? '')) : '',
                'complainant_section' => $isStudentComplainant ? trim($_POST['complainant_section'] ?? '') : '',
                'complainant_course_year' => $isStudentComplainant ? trim($_POST['complainant_course_year'] ?? '') : '',
                'case_classification' => trim($_POST['case_classification']),
                'incident_datetime' => date('Y-m-d H:i:s', strtotime($_POST['incident_datetime'])),
                'incident_location' => trim($_POST['incident_location']),
                'complaint_details' => trim($_POST['complaint_details']),
                'status' => 'Submitted',
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

            $_SESSION['complaint_success'] = 'Complaint submitted successfully. Case Number: ' . $createdComplaint['case_number'];
            header('Location: create.php');
            exit;
        } catch (Throwable $exception) {
            foreach ($savedFiles as $file) {
                $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $file['file_path'];
                if (is_file($absolutePath)) {
                    unlink($absolutePath);
                }
            }

            $_SESSION['complaint_errors'] = ['Unable to submit complaint. Please try again.'];
            $_SESSION['complaint_old'] = $_POST;
            header('Location: create.php');
            exit;
        }
    }

    private function validate(array $post, array $files) {
        $errors = [];
        $complainantType = trim((string) ($post['complainant_type'] ?? ''));

        $required = [
            'complainant_name' => 'Complainant name is required.',
            'complainant_email' => 'Complainant email is required.',
            'complainant_contact' => 'Complainant contact number is required.',
            'case_classification' => 'Case classification is required.',
            'incident_datetime' => 'Incident date and time is required.',
            'incident_location' => 'Incident location is required.',
            'complaint_details' => 'Complaint details are required.',
        ];

        foreach ($required as $field => $message) {
            if (empty(trim($post[$field] ?? ''))) {
                $errors[] = $message;
            }
        }

        if (!in_array($complainantType, ['Student', 'Employee', 'Private Individual', 'Others'], true)) {
            $errors[] = 'Please select a valid complainant type.';
        } elseif ($complainantType === 'Student') {
            foreach (['complainant_student_no' => 'Student number', 'complainant_college' => 'College', 'complainant_course' => 'Course', 'complainant_section' => 'Section'] as $field => $label) {
                if (trim((string) ($post[$field] ?? '')) === '') $errors[] = $label . ' is required for student complainants.';
            }
            $validSections = array_merge(...array_values(Courses::sections()));
            if (!Colleges::contains($post['complainant_college'] ?? '')) $errors[] = 'Please select a valid college.';
            if (!in_array($post['complainant_course'] ?? '', Courses::all(), true)) $errors[] = 'Please select a valid course.';
            if (!in_array($post['complainant_section'] ?? '', $validSections, true)) $errors[] = 'Please select a valid section.';
        } elseif ($complainantType === 'Employee') {
            foreach (['complainant_employee_no' => 'Employee number', 'complainant_department' => 'College, office, or department', 'complainant_position' => 'Position'] as $field => $label) {
                if (trim((string) ($post[$field] ?? '')) === '') $errors[] = $label . ' is required for employee complainants.';
            }
        } elseif ($complainantType === 'Private Individual' && !in_array(trim((string) ($post['complainant_relationship'] ?? '')), ['', 'Parent', 'Guardian', 'Alumni', 'Visitor', 'Community Member', 'Other'], true)) {
            $errors[] = 'Please select a valid relationship to CLSU.';
        }

        if (!empty($post['complainant_gender']) && !in_array($post['complainant_gender'], ['Male', 'Female'], true)) {
            $errors[] = 'Please select a valid complainant gender.';
        }

        if (!empty($post['complainant_email']) && !filter_var($post['complainant_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid complainant email address.';
        }

        if (!empty($post['case_classification']) && !in_array($post['case_classification'], $this->classifications, true)) {
            $errors[] = 'Please select a valid case classification.';
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

        return array_merge($errors, $this->validateEvidenceFiles($evidenceFiles));
    }

    private function storeRevision(array $case, array $revision) {
        $allowed = array_values(array_intersect(
            ['complaint_details', 'incident_date', 'incident_time', 'incident_location', 'respondents', 'witnesses', 'evidence'],
            $revision['revision_fields']
        ));
        $errors = [];

        if (empty($_POST['revision_confirmation'])) $errors[] = 'Please confirm that you completed all requested revisions.';
        foreach (['complaint_details', 'incident_date', 'incident_time', 'incident_location'] as $field) {
            if (in_array($field, $allowed, true) && trim((string) ($_POST[$field] ?? '')) === '') {
                $errors[] = ucwords(str_replace('_', ' ', $field)) . ' is required.';
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
        $names = $post['respondent_name'] ?? [];
        $validTypes = ['Student', 'Employee', 'Private Individual', 'Other'];

        foreach ($names as $index => $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $type = trim($post['respondent_type'][$index] ?? '');
            if (!in_array($type, $validTypes, true)) $type = 'Student';

            $course = trim($post['respondent_course'][$index] ?? '');
            $section = trim($post['respondent_section'][$index] ?? '');
            $courseYear = Courses::combine($course, $section);
            if ($courseYear === '' && ($course !== '' || $section !== '')) {
                $courseYear = trim($course . ' | ' . $section, ' |');
            }

            $respondents[] = [
                'respondent_type' => $type,
                'full_name' => $name,
                'gender' => $this->normalizedGender($post['respondent_gender'][$index] ?? ''),
                'student_no' => trim($post['respondent_student_no'][$index] ?? ''),
                'employee_no' => trim($post['respondent_employee_no'][$index] ?? ''),
                'college' => trim($post['respondent_college'][$index] ?? ''),
                'office_department' => trim($post['respondent_department'][$index] ?? ''),
                'course_year' => $courseYear,
                'position' => trim($post['respondent_position'][$index] ?? ''),
                'affiliation' => trim($post['respondent_affiliation'][$index] ?? ''),
                'contact_info' => trim($post['respondent_contact'][$index] ?? ''),
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

            $required = $type === 'Employee'
                ? ['respondent_employee_no' => 'Employee number', 'respondent_position' => 'Position', 'respondent_department' => 'College/Office/Department']
                : ($type === 'Student'
                    ? ['respondent_student_no' => 'Student number', 'respondent_college' => 'College', 'respondent_course' => 'Course/Program', 'respondent_section' => 'Section']
                    : []);

            foreach ($required as $field => $label) {
                if (trim((string) ($post[$field][$index] ?? '')) === '') {
                    $errors[] = $label . ' is required for ' . strtolower($type) . ' respondents.';
                }
            }
        }

        return $errors;
    }

    private function normalizedGender($value) {
        $value = trim((string) $value);
        return in_array($value, ['Male', 'Female'], true) ? $value : '';
    }

    private function normalizeWitnesses(array $post) {
        $witnesses = [];
        $names = $post['witness_name'] ?? [];
        $validTypes = ['Student', 'Employee', 'Private Individual', 'Other'];

        foreach ($names as $index => $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $type = trim($post['witness_type'][$index] ?? '');
            if (!in_array($type, $validTypes, true)) $type = 'Student';

            $course = trim($post['witness_course'][$index] ?? '');
            $section = trim($post['witness_section'][$index] ?? '');
            $courseYear = Courses::combine($course, $section);
            if ($courseYear === '' && ($course !== '' || $section !== '')) {
                $courseYear = trim($course . ' | ' . $section, ' |');
            }

            $witnesses[] = [
                'person_type' => $type,
                'full_name' => $name,
                'gender' => $this->normalizedGender($post['witness_gender'][$index] ?? ''),
                'student_no' => trim($post['witness_student_no'][$index] ?? ''),
                'contact_info' => trim($post['witness_contact'][$index] ?? ''),
                'statement' => trim($post['witness_statement'][$index] ?? ''),
                'employee_no' => trim($post['witness_employee_no'][$index] ?? ''),
                'college' => trim($post['witness_college'][$index] ?? ''),
                'office_department' => trim($post['witness_department'][$index] ?? ''),
                'position' => trim($post['witness_position'][$index] ?? ''),
                'affiliation' => trim($post['witness_affiliation'][$index] ?? ''),
                'course_year' => $courseYear,
            ];
        }

        return $witnesses;
    }

    private function validateWitnesses(array $post) {
        $errors = [];

        if (!empty($post['witness_none'])) {
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

            $required = $type === 'Employee'
                ? ['witness_employee_no' => 'Employee number', 'witness_position' => 'Position', 'witness_department' => 'College/Office or Department']
                : ($type === 'Student'
                    ? ['witness_student_no' => 'Student number', 'witness_college' => 'College', 'witness_course' => 'Course/Program', 'witness_section' => 'Section']
                    : []);

            foreach ($required as $field => $label) {
                if (trim((string) ($post[$field][$index] ?? '')) === '') {
                    $errors[] = $label . ' is required for ' . strtolower($type) . ' witnesses.';
                }
            }
        }

        return $errors;
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
