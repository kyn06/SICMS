<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/LegacyCase.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../models/Hearing.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Colleges.php';
require_once __DIR__ . '/../helpers/Courses.php';
require_once __DIR__ . '/../helpers/PhoneNumber.php';
require_once __DIR__ . '/../helpers/PersonName.php';

class LegacyCaseController {
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

    private const MAX_FILE_BYTES = 5 * 1024 * 1024;
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function __construct() {
        Security::startSession();
        $this->authenticate();
    }

    public function index() {
        $filters = $this->filters($_GET);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->handleAction();
        }

        return [
            'user' => $this->user,
            'cases' => LegacyCase::listCases($filters),
            'filters' => $filters,
            'statuses' => LegacyCase::statuses(),
            'classifications' => LegacyCase::classifications(),
            'allClassifications' => $this->classifications,
            'colleges' => Colleges::all(),
            'canEdit' => LegacyCase::canEdit($this->user),
            'message' => $_SESSION['legacy_case_message'] ?? null,
            'errors' => $_SESSION['legacy_case_errors'] ?? [],
            'old' => $_SESSION['legacy_case_old'] ?? [],
        ];
    }

    public function search() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $cases = LegacyCase::listCases($this->filters($_GET));
            echo json_encode(['success' => true, 'cases' => $cases, 'total' => count($cases)], JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to filter legacy cases right now.']);
        }

        exit;
    }

    public function createPage() {
        $this->requireCanAdd();
        return $this->formData();
    }

    public function show($complaintId) {
        $complaintId = (int) $complaintId;
        $case = LegacyCase::find($complaintId);

        if (!$case) {
            http_response_code(404);
            echo 'Legacy case not found.';
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->handleCaseAction($complaintId);
        }

        return [
            'user' => $this->user,
            'case' => $case,
            'respondents' => CaseRecord::getRespondents($complaintId),
            'witnesses' => CaseRecord::getWitnesses($complaintId),
            'evidence' => CaseRecord::getEvidence($complaintId),
            'history' => CaseRecord::getHistory($complaintId),
            'hearings' => Hearing::forComplaint($complaintId),
            'coordinators' => [],
            'canEdit' => LegacyCase::canEdit($this->user),
            'statuses' => LegacyCase::statuses(),
            'classifications' => $this->classifications,
            'colleges' => Colleges::all(),
            'message' => $_SESSION['legacy_case_message'] ?? null,
            'errors' => $_SESSION['legacy_case_errors'] ?? [],
        ];
    }

    public function edit($complaintId) {
        $this->requireCanEdit();
        $complaintId = (int) $complaintId;
        $case = LegacyCase::find($complaintId);

        if (!$case) {
            http_response_code(404);
            echo 'Legacy case not found.';
            exit;
        }

        $errors = $_SESSION['legacy_case_errors'] ?? [];
        unset($_SESSION['legacy_case_errors']);

        return [
            'user' => $this->user,
            'case' => $case,
            'respondents' => CaseRecord::getRespondents($complaintId),
            'witnesses' => CaseRecord::getWitnesses($complaintId),
            'evidence' => CaseRecord::getEvidence($complaintId),
            'hearings' => Hearing::forComplaint($complaintId),
            'canEdit' => true,
            'statuses' => LegacyCase::statuses(),
            'classifications' => $this->classifications,
            'colleges' => Colleges::all(),
            'errors' => $errors,
            'old' => $_SESSION['legacy_case_old'] ?? [],
        ];
    }

    public function clearFlash() {
        unset($_SESSION['legacy_case_message'], $_SESSION['legacy_case_errors'], $_SESSION['legacy_case_old']);
    }

    private function formData($complaintId = 0) {
        return [
            'user' => $this->user,
            'case' => null,
            'respondents' => [],
            'witnesses' => [],
            'evidence' => [],
            'hearings' => [],
            'canEdit' => true,
            'statuses' => LegacyCase::statuses(),
            'classifications' => $this->classifications,
            'colleges' => Colleges::all(),
            'errors' => $_SESSION['legacy_case_errors'] ?? [],
            'old' => $_SESSION['legacy_case_old'] ?? [],
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
        LegacyCase::setConnection($this->db);
        CaseRecord::setConnection($this->db);
        Hearing::setConnection($this->db);
        AuditLog::setConnection($this->db);

        $this->user = User::findByEmail($_SESSION['email']);

        if (!$this->user || $this->user['status'] !== 'active') {
            session_unset();
            session_destroy();
            header('Location: ../auth/login.php');
            exit;
        }

        if (!LegacyCase::canView($this->user)) {
            http_response_code(403);
            echo 'Access denied. You do not have permission to view legacy cases.';
            exit;
        }
    }

    private function requireCanEdit() {
        if (!LegacyCase::canEdit($this->user)) {
            http_response_code(403);
            echo 'Access denied. Only SDRU staff can modify legacy cases.';
            exit;
        }
    }

    private function requireCanAdd() {
        if (!LegacyCase::canAdd($this->user)) {
            http_response_code(403);
            echo 'Access denied. Only SDRU staff can add migrated cases.';
            exit;
        }
    }

    private function filters(array $input) {
        return [
            'status' => substr(trim((string) ($input['status'] ?? '')), 0, 50),
            'classification' => substr(trim((string) ($input['classification'] ?? '')), 0, 100),
            'college' => substr(trim((string) ($input['college'] ?? '')), 0, 255),
            'year' => preg_match('/^\d{4}$/', (string) ($input['year'] ?? '')) ? (int) $input['year'] : '',
            'case_number' => substr(trim((string) ($input['case_number'] ?? '')), 0, 100),
            'complainant_name' => substr(trim((string) ($input['complainant_name'] ?? '')), 0, 255),
        ];
    }

    private function handleAction() {
        $action = $_POST['legacy_action'] ?? '';

        if ($action === 'create') {
            $this->requireCanAdd();
            $this->store();
            return;
        }

        $this->requireCanEdit();

        switch ($action) {
            case 'update':
                $this->update();
                break;
            default:
                $_SESSION['legacy_case_errors'] = ['Unknown legacy case action.'];
                header('Location: index.php');
                exit;
        }
    }

    private function store() {
        $errors = $this->validateCase($_POST, $_FILES);

        if ($errors) {
            $_SESSION['legacy_case_errors'] = $errors;
            $_SESSION['legacy_case_old'] = $_POST;
            header('Location: create.php');
            exit;
        }

        $hasRespondents = $this->askYesNo('has_respondents');
        $hasWitnesses = $this->askYesNo('has_witnesses');
        $hasEvidence = $this->askYesNo('has_evidence');
        $hasHearings = $this->askYesNo('has_hearings');

        $savedFiles = $hasEvidence ? $this->collectUploads($_FILES['evidence'] ?? []) : [];
        $evidenceRecords = [];
        foreach ($savedFiles as $file) {
            $evidenceRecords[] = [
                'original_filename' => $file['original_filename'],
                'stored_filename' => $file['stored_filename'],
                'file_path' => $file['file_path'],
                'mime_type' => $file['mime_type'],
                'file_size' => $file['file_size'],
                'doc_type' => $file['doc_type'],
            ];
        }

        $complaint = $this->caseInput($_POST, 'create');
        $complaint['submitted_at'] = date('Y-m-d H:i:s');

        try {
            $created = CaseRecord::createLegacyCase(
                $complaint,
                $hasRespondents ? $this->normalizeRespondents($_POST) : [],
                $hasWitnesses ? $this->normalizeWitnesses($_POST) : [],
                $evidenceRecords,
                $hasHearings ? $this->normalizeHearings($_POST) : [],
                (int) $this->user['account_id']
            );

            AuditLog::record($this->user, 'Legacy Case Created', 'Digitized legacy case ' . $created['case_number'] . '.');
            $_SESSION['legacy_case_message'] = 'Legacy case ' . $created['case_number'] . ' created successfully.';
            header('Location: show.php?id=' . (int) $created['complaint_id']);
            exit;
        } catch (Throwable $exception) {
            $this->removeSavedFiles($savedFiles);
            $message = str_contains((string) $exception->getMessage(), 'Duplicate entry')
                ? 'A case with the original case number "' . $complaint['case_number'] . '" already exists.'
                : 'Unable to create the legacy case. Please try again.';
            $_SESSION['legacy_case_errors'] = [$message];
            $_SESSION['legacy_case_old'] = $_POST;
            header('Location: create.php');
            exit;
        }
    }

    private function update() {
        $complaintId = (int) ($_POST['complaint_id'] ?? 0);
        $case = LegacyCase::find($complaintId);

        if (!$case) {
            $_SESSION['legacy_case_errors'] = ['Legacy case not found.'];
            header('Location: edit.php?id=' . $complaintId);
            exit;
        }

        $errors = $this->validateCase($_POST, $_FILES, true);

        if ($errors) {
            $_SESSION['legacy_case_errors'] = $errors;
            $_SESSION['legacy_case_old'] = $_POST;
            header('Location: edit.php?id=' . $complaintId);
            exit;
        }

        $hasRespondents = $this->askYesNo('has_respondents', true);
        $hasWitnesses = $this->askYesNo('has_witnesses', true);
        $hasEvidence = $this->askYesNo('has_evidence', true);
        $hasHearings = $this->askYesNo('has_hearings', true);

        $savedFiles = $hasEvidence ? $this->collectUploads($_FILES['evidence'] ?? []) : [];
        $evidenceRecords = [];
        foreach ($savedFiles as $file) {
            $evidenceRecords[] = [
                'original_filename' => $file['original_filename'],
                'stored_filename' => $file['stored_filename'],
                'file_path' => $file['file_path'],
                'mime_type' => $file['mime_type'],
                'file_size' => $file['file_size'],
                'doc_type' => $file['doc_type'],
            ];
        }

        $existingEvidenceIds = array_map('intval', array_map(fn($e) => $e['evidence_id'], CaseRecord::getEvidence($complaintId)));
        $removeIds = array_values(array_intersect($existingEvidenceIds, array_map('intval', (array) ($_POST['remove_evidence'] ?? []))));
        if (!$hasEvidence) {
            $removeIds = $existingEvidenceIds;
        }

        $complaint = $this->caseInput($_POST, 'update');
        $complaint['legacy_previous_status'] = $case['status'];
        $complaint['legacy_update_remarks'] = $_POST['update_remarks'] ?? '';

        try {
            $removedPaths = CaseRecord::updateLegacyCase(
                $complaintId,
                $complaint,
                $hasRespondents ? $this->normalizeRespondents($_POST) : [],
                $hasWitnesses ? $this->normalizeWitnesses($_POST) : [],
                $evidenceRecords,
                $removeIds,
                $hasHearings ? $this->normalizeHearings($_POST) : [],
                (int) $this->user['account_id']
            );
            $this->removeSavedFiles(array_map(fn($path) => ['file_path' => $path], $removedPaths));

            AuditLog::record($this->user, 'Legacy Case Updated', 'Updated legacy case ' . $case['case_number'] . '.');
            $_SESSION['legacy_case_message'] = 'Legacy case updated successfully.';
            header('Location: show.php?id=' . $complaintId);
            exit;
        } catch (Throwable $exception) {
            $this->removeSavedFiles($savedFiles);
            $message = str_contains((string) $exception->getMessage(), 'Duplicate entry')
                ? 'A case with the original case number "' . $complaint['case_number'] . '" already exists.'
                : 'Unable to update the legacy case. Please try again.';
            $_SESSION['legacy_case_errors'] = [$message];
            $_SESSION['legacy_case_old'] = $_POST;
            header('Location: edit.php?id=' . $complaintId);
            exit;
        }
    }

    private function handleCaseAction($complaintId) {
        $case = LegacyCase::find($complaintId);

        if (!$case) {
            $_SESSION['legacy_case_errors'] = ['Legacy case not found.'];
            header('Location: index.php');
            exit;
        }

        $action = (string) ($_POST['legacy_action'] ?? '');

        $this->requireCanEdit();

        try {
            if ($action === 'status') {
                $newStatus = (string) ($_POST['status'] ?? '');
                if (!in_array($newStatus, LegacyCase::statuses(), true)) {
                    $_SESSION['legacy_case_errors'] = ['Please select a valid status.'];
                } else {
                    CaseRecord::updateStatus($complaintId, $newStatus, (string) ($_POST['remarks'] ?? ''), (int) $this->user['account_id']);
                    AuditLog::record($this->user, 'Legacy Case Status', 'Changed legacy case ' . $case['case_number'] . ' to ' . $newStatus . '.');
                    $_SESSION['legacy_case_message'] = 'Status updated to ' . $newStatus . '.';
                }
            } elseif ($action === 'outcome') {
                $stmt = $this->db->prepare("UPDATE complaints SET legacy_outcome = ?, action_taken = ?, resolution_date = ?, remarks_notes = ?, updated_at = ? WHERE complaint_id = ? AND case_source = 'Legacy'");
                $now = date('Y-m-d H:i:s');
                $outcome = trim((string) ($_POST['legacy_outcome'] ?? '')) ?: null;
                $actionTaken = trim((string) ($_POST['action_taken'] ?? '')) ?: null;
                $resolutionDate = $this->dateValue($_POST['resolution_date'] ?? '') ?: null;
                $remarks = trim((string) ($_POST['remarks_notes'] ?? '')) ?: null;
                $stmt->bind_param('sssssi', $outcome, $actionTaken, $resolutionDate, $remarks, $now, $complaintId);
                $stmt->execute();
                AuditLog::record($this->user, 'Legacy Case Outcome', 'Updated outcome details for legacy case ' . $case['case_number'] . '.');
                $_SESSION['legacy_case_message'] = 'Outcome and resolution details updated.';
            } elseif ($action === 'add_evidence') {
                $this->addEvidence($complaintId, $case);
            } else {
                $_SESSION['legacy_case_errors'] = ['Invalid legacy case action.'];
            }
        } catch (Throwable $exception) {
            $_SESSION['legacy_case_errors'] = ['Unable to update legacy case. Please try again.'];
        }

        header('Location: show.php?id=' . $complaintId);
        exit;
    }

    private function addEvidence($complaintId, $case) {
        $file = $_FILES['evidence_single'] ?? [];
        $docType = in_array($_POST['doc_type'] ?? '', ['supporting', 'resolution'], true) ? $_POST['doc_type'] : null;

        if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['legacy_case_errors'] = ['Please choose a file to upload.'];
            return;
        }

        $saved = $this->saveSingle($file, $docType);
        if ($saved === null) {
            $_SESSION['legacy_case_errors'] = ['The selected file could not be uploaded.'];
            return;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO complaint_evidence (complaint_id, original_filename, stored_filename, file_path, mime_type, file_size, doc_type, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $uploadedAt = date('Y-m-d H:i:s');
        $stmt->bind_param(
            'issssiss',
            $complaintId,
            $saved['original_filename'],
            $saved['stored_filename'],
            $saved['file_path'],
            $saved['mime_type'],
            $saved['file_size'],
            $saved['doc_type'],
            $uploadedAt
        );
        $stmt->execute();
        AuditLog::record($this->user, 'Legacy Evidence Uploaded', 'Uploaded ' . $saved['original_filename'] . ' for legacy case ' . $case['case_number'] . '.');
        $_SESSION['legacy_case_message'] = 'Document uploaded successfully.';
    }

    private function caseInput(array $post, string $mode) {
        $complainantType = in_array($post['complainant_type'] ?? '', ['Student', 'Employee', 'Private Individual', 'Others'], true) ? $post['complainant_type'] : 'Student';
        $isStudent = $complainantType === 'Student';
        $now = date('Y-m-d H:i:s');

        $input = [
            'case_number' => trim($post['case_number'] ?? ''),
            'complaint_title' => trim($post['case_classification'] ?? ''),
            'submitted_by_account_id' => (int) $this->user['account_id'],
            'complainant_type' => $complainantType,
            'complainant_name' => PersonName::normalize($post['complainant_name'] ?? ''),
            'complainant_gender' => $this->genderOrNull($post['complainant_gender'] ?? ''),
            'complainant_relationship' => $complainantType === 'Private Individual' ? trim($post['complainant_relationship'] ?? '') : null,
            'complainant_employee_no' => $complainantType === 'Employee' ? trim($post['complainant_employee_no'] ?? '') : null,
            'complainant_department' => $complainantType === 'Employee' ? trim($post['complainant_department'] ?? '') : null,
            'complainant_position' => $complainantType === 'Employee' ? trim($post['complainant_position'] ?? '') : null,
            'complainant_affiliation' => $complainantType === 'Others' ? trim($post['complainant_affiliation'] ?? '') : null,
            'complainant_purpose' => $complainantType === 'Others' ? trim($post['complainant_purpose'] ?? '') : null,
            'complainant_student_no' => $isStudent ? trim($post['complainant_student_no'] ?? '') : null,
            'complainant_email' => trim($post['complainant_email'] ?? '') ?: null,
            'complainant_contact' => PhoneNumber::normalize($post['complainant_contact'] ?? '') ?: null,
            'complainant_college' => $isStudent ? trim($post['complainant_college'] ?? '') : null,
            'complainant_course' => $isStudent ? trim($post['complainant_course'] ?? '') : null,
            'complainant_year_level' => $isStudent ? Courses::yearLevel(trim($post['complainant_section'] ?? '')) : '',
            'complainant_section' => $isStudent ? trim($post['complainant_section'] ?? '') : null,
            'complainant_course_year' => $isStudent ? trim($post['complainant_course_year'] ?? '') : null,
            'case_classification' => trim($post['case_classification'] ?? ''),
            'incident_datetime' => $this->datetimeValue($post['incident_datetime'] ?? ''),
            'incident_location' => trim($post['incident_location'] ?? '') ?: null,
            'complaint_details' => trim($post['complaint_details'] ?? '') ?: null,
            'status' => in_array($post['status'] ?? '', LegacyCase::statuses(), true) ? $post['status'] : 'Under Investigation',
            'original_case_date' => $this->dateValue($post['original_case_date'] ?? '') ?: date('Y-m-d'),
            'legacy_outcome' => trim($post['legacy_outcome'] ?? '') ?: null,
            'action_taken' => trim($post['action_taken'] ?? '') ?: null,
            'resolution_date' => $this->dateValue($post['resolution_date'] ?? '') ?: null,
            'remarks_notes' => trim($post['remarks_notes'] ?? '') ?: null,
            'legacy_entry_source' => trim($post['legacy_entry_source'] ?? '') ?: null,
        ];

        if ($mode === 'create') {
            $input['created_at'] = $now;
        }

        return $input;
    }

    private function validateCase(array $post, array $files, $isEdit = false) {
        $errors = [];

        $required = [
            'case_number' => 'Original case number is required.',
            'case_classification' => 'Case classification is required.',
            'complainant_name' => 'Complainant name is required.',
        ];
        foreach ($required as $field => $message) {
            if (trim((string) ($post[$field] ?? '')) === '') $errors[] = $message;
        }

        if (!empty($post['case_number']) && (strlen(trim($post['case_number'])) > 50)) $errors[] = 'Original case number must not exceed 50 characters.';

        if (!empty($post['original_case_date']) && !$this->dateValue($post['original_case_date'])) $errors[] = 'Original case date must be a valid date.';
        if (!empty($post['resolution_date']) && !$this->dateValue($post['resolution_date'])) $errors[] = 'Resolution date must be a valid date.';
        if (!empty($post['incident_datetime']) && !$this->datetimeValue($post['incident_datetime'])) $errors[] = 'Incident date must be a valid date.';

        if (!in_array($post['status'] ?? '', LegacyCase::statuses(), true)) $errors[] = 'Please select a valid status.';

        $complainantType = trim((string) ($post['complainant_type'] ?? ''));
        if (!in_array($complainantType, ['Student', 'Employee', 'Private Individual', 'Others'], true)) {
            $errors[] = 'Please select a valid complainant type.';
        }

        if (!empty($post['case_classification']) && !in_array($post['case_classification'], $this->classifications, true)) {
            $errors[] = 'Please select a valid case classification.';
        }

        if (!empty($post['complainant_email']) && !filter_var($post['complainant_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid complainant email address.';
        }
        if (!PhoneNumber::isValid($post['complainant_contact'] ?? '')) {
            $errors[] = PhoneNumber::ERROR_MESSAGE;
        }

        $errors = array_merge($errors, $this->validatePeople($_POST));
        $errors = array_merge($errors, $this->validateUploads($files['evidence'] ?? []));

        return $errors;
    }

    private function validatePeople(array $post) {
        $errors = [];

        foreach (($post['respondent_name'] ?? []) as $index => $name) {
            if (trim((string) $name) === '') continue;
            $type = trim((string) ($post['respondent_type'][$index] ?? ''));
            if ($type === '') $type = 'Student';
            if (!in_array($type, ['Student', 'Employee', 'Private Individual', 'Other'], true)) {
                $errors[] = 'Please select a valid respondent type.';
            }
            if (!PhoneNumber::isValid($post['respondent_contact'][$index] ?? '')) $errors[] = PhoneNumber::ERROR_MESSAGE;
        }

        foreach (($post['witness_name'] ?? []) as $index => $name) {
            if (trim((string) $name) === '') continue;
            $type = trim((string) ($post['witness_type'][$index] ?? ''));
            if ($type === '') $type = 'Student';
            if (!in_array($type, ['Student', 'Employee', 'Private Individual', 'Other'], true)) {
                $errors[] = 'Please select a valid witness type.';
            }
            if (!PhoneNumber::isValid($post['witness_contact'][$index] ?? '')) $errors[] = PhoneNumber::ERROR_MESSAGE;
        }

        foreach (($post['hearing_datetime'] ?? []) as $index => $datetime) {
            if (trim((string) $datetime) === '') continue;
            if (!$this->datetimeValue($datetime)) $errors[] = 'One of the hearing dates is not a valid date.';
        }

        return $errors;
    }

    private function validateUploads(array $files) {
        $errors = [];

        foreach (($files['name'] ?? []) as $index => $name) {
            if ($name === '') continue;
            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = $name . ' could not be uploaded.';
                continue;
            }
            if (empty($files['tmp_name'][$index]) || !is_uploaded_file($files['tmp_name'][$index])) {
                $errors[] = $name . ' is not a valid uploaded file.';
                continue;
            }
            if (($files['size'][$index] ?? 0) > self::MAX_FILE_BYTES) $errors[] = $name . ' exceeds the 5MB file limit.';
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $errors[] = $name . ' has an invalid file type.';
                continue;
            }
            $mimeType = mime_content_type($files['tmp_name'][$index]);
            if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) $errors[] = $name . ' has an invalid content type.';
        }

        return $errors;
    }

    private function collectUploads(array $files) {
        $saved = [];
        $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence';

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $docTypes = (array) ($_POST['evidence_doc_type'] ?? []);

        foreach (($files['name'] ?? []) as $index => $name) {
            if ($name === '') continue;
            if (empty($files['tmp_name'][$index]) || !is_uploaded_file($files['tmp_name'][$index])) {
                throw new Exception('Invalid uploaded file.');
            }
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $storedFilename = date('YmdHis') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
            $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedFilename;
            if (!move_uploaded_file($files['tmp_name'][$index], $destination)) {
                throw new Exception('Unable to save uploaded file.');
            }
            $docType = in_array($docTypes[$index] ?? '', ['supporting', 'resolution'], true) ? $docTypes[$index] : null;
            $saved[] = [
                'original_filename' => basename($name),
                'stored_filename' => $storedFilename,
                'file_path' => 'storage/evidence/' . $storedFilename,
                'mime_type' => mime_content_type($destination),
                'file_size' => (int) $files['size'][$index],
                'doc_type' => $docType,
            ];
        }

        return $saved;
    }

    private function saveSingle(array $file, $docType) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
        if (($file['size'] ?? 0) > self::MAX_FILE_BYTES) {
            $_SESSION['legacy_case_errors'] = $file['name'] . ' exceeds the 5MB file limit.';
            return null;
        }
        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $_SESSION['legacy_case_errors'] = $file['name'] . ' has an invalid file type.';
            return null;
        }
        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $_SESSION['legacy_case_errors'] = $file['name'] . ' has an invalid content type.';
            return null;
        }

        $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $storedFilename = date('YmdHis') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) return null;

        return [
            'original_filename' => basename($file['name']),
            'stored_filename' => $storedFilename,
            'file_path' => 'storage/evidence/' . $storedFilename,
            'mime_type' => $mimeType,
            'file_size' => (int) $file['size'],
            'doc_type' => $docType,
        ];
    }

    private function removeSavedFiles(array $files) {
        foreach ($files as $file) {
            $path = $file['file_path'] ?? null;
            if (!$path) continue;
            $absolute = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (is_file($absolute)) @unlink($absolute);
        }
    }

    private function normalizeRespondents(array $post) {
        $respondents = [];
        foreach (($post['respondent_name'] ?? []) as $index => $name) {
            $name = PersonName::normalize($name);
            if ($name === '') continue;
            $type = trim($post['respondent_type'][$index] ?? '');
            if (!in_array($type, ['Student', 'Employee', 'Private Individual', 'Other'], true)) $type = 'Student';
            $course = trim($post['respondent_course'][$index] ?? '');
            $section = trim($post['respondent_section'][$index] ?? '');
            $courseYear = Courses::combine($course, $section);
            if ($courseYear === '' && ($course !== '' || $section !== '')) $courseYear = trim($course . ' | ' . $section, ' |');
            $respondents[] = [
                'respondent_type' => $type,
                'full_name' => $name,
                'gender' => $this->genderOrNull($post['respondent_gender'][$index] ?? ''),
                'student_no' => trim($post['respondent_student_no'][$index] ?? '') ?: null,
                'employee_no' => trim($post['respondent_employee_no'][$index] ?? '') ?: null,
                'college' => trim($post['respondent_college'][$index] ?? '') ?: null,
                'office_department' => trim($post['respondent_department'][$index] ?? '') ?: null,
                'course_year' => $courseYear ?: null,
                'position' => trim($post['respondent_position'][$index] ?? '') ?: null,
                'affiliation' => trim($post['respondent_affiliation'][$index] ?? '') ?: null,
                'contact_info' => PhoneNumber::normalize($post['respondent_contact'][$index] ?? '') ?: null,
                'details' => trim($post['respondent_details'][$index] ?? '') ?: null,
            ];
        }
        return $respondents;
    }

    private function normalizeWitnesses(array $post) {
        $witnesses = [];
        foreach (($post['witness_name'] ?? []) as $index => $name) {
            $name = PersonName::normalize($name);
            if ($name === '') continue;
            $type = trim($post['witness_type'][$index] ?? '');
            if (!in_array($type, ['Student', 'Employee', 'Private Individual', 'Other'], true)) $type = 'Student';
            $course = trim($post['witness_course'][$index] ?? '');
            $section = trim($post['witness_section'][$index] ?? '');
            $courseYear = Courses::combine($course, $section);
            if ($courseYear === '' && ($course !== '' || $section !== '')) $courseYear = trim($course . ' | ' . $section, ' |');
            $witnesses[] = [
                'person_type' => $type,
                'full_name' => $name,
                'gender' => $this->genderOrNull($post['witness_gender'][$index] ?? ''),
                'student_no' => trim($post['witness_student_no'][$index] ?? '') ?: null,
                'contact_info' => PhoneNumber::normalize($post['witness_contact'][$index] ?? '') ?: null,
                'statement' => trim($post['witness_statement'][$index] ?? '') ?: null,
                'employee_no' => trim($post['witness_employee_no'][$index] ?? '') ?: null,
                'college' => trim($post['witness_college'][$index] ?? '') ?: null,
                'office_department' => trim($post['witness_department'][$index] ?? '') ?: null,
                'position' => trim($post['witness_position'][$index] ?? '') ?: null,
                'affiliation' => trim($post['witness_affiliation'][$index] ?? '') ?: null,
                'course_year' => $courseYear ?: null,
            ];
        }
        return $witnesses;
    }

    private function normalizeHearings(array $post) {
        $hearings = [];
        foreach (($post['hearing_datetime'] ?? []) as $index => $datetime) {
            $datetime = trim($datetime);
            if ($datetime === '') continue;
            $venue = trim($post['hearing_venue'][$index] ?? '');
            $remarks = trim($post['hearing_remarks'][$index] ?? '');
            $status = in_array($post['hearing_status'][$index] ?? '', ['Scheduled', 'Completed', 'Cancelled'], true) ? $post['hearing_status'][$index] : 'Completed';
            $hearings[] = [
                'scheduled_by_account_id' => (int) $this->user['account_id'],
                'hearing_datetime' => $this->datetimeValue($datetime),
                'venue' => $venue ?: null,
                'google_meet_link' => null,
                'google_event_id' => null,
                'remarks' => $remarks ?: null,
                'status' => $status,
            ];
        }
        return $hearings;
    }

    private function genderOrNull($value) {
        $value = trim((string) $value);
        return in_array($value, ['Male', 'Female'], true) ? $value : '';
    }

    private function askYesNo($key, $default = false): bool {
        if (!isset($_POST[$key])) return (bool) $default;
        return ($_POST[$key] ?? '') === 'yes';
    }

    private function dateValue($value) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
            if (checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4))) {
                return $value;
            }
        }
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) return '';
        return date('Y-m-d', $timestamp);
    }

    private function datetimeValue($value) {
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) return null;
        return date('Y-m-d H:i:s', $timestamp);
    }
}
