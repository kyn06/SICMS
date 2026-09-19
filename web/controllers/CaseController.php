<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/Hearing.php';
require_once __DIR__ . '/../models/CaseUpdate.php';
require_once __DIR__ . '/../services/FileUploadService.php';
require_once __DIR__ . '/../helpers/Security.php';

class CaseController {
    private $database;
    private $db;
    private $user;
    private $staffRoles = ['super-admin', 'admin', 'sdr staff', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head', 'head of sdru'];
    private $revisionFields = ['complaint_details', 'incident_date', 'incident_time', 'incident_location', 'respondents', 'witnesses', 'evidence'];
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

        $this->authenticateStaff();
    }

    public function index() {
        $filters = $this->filters($_GET);

        $isCoordinator = $this->roleKey() === 'coordinator';
        $showOnline = $this->showOnlineCases($filters);
        $showMigrated = $this->showMigratedCases($filters);

        $assignedCases = $isCoordinator && $showOnline
            ? CaseRecord::listCases(array_merge($filters, ['assigned_coordinator_account_id' => (int) $this->user['account_id']]))
            : [];

        return [
            'user' => $this->user,
            'cases' => $showOnline ? CaseRecord::listCases($filters) : [],
            'assignedCases' => $assignedCases,
            'migratedCases' => $showMigrated ? CaseRecord::listLegacyCases($this->legacyFilters($filters)) : [],
            'canEditMigrated' => in_array($this->roleKey(), ['sdr-staff', 'sdru-staff'], true),
            'filters' => $filters,
            'statuses' => $this->activeStatuses(),
            'classifications' => CaseRecord::getClassifications(),
            'coordinators' => CaseRecord::getCoordinators(),
        ];
    }

    private function activeStatuses() {
        return array_values(array_filter(CaseRecord::getStatuses(), fn($status) => $status !== 'Archived'));
    }

    public function search() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $filters = $this->filters($_GET);

            $isCoordinator = $this->roleKey() === 'coordinator';
            $showOnline = $this->showOnlineCases($filters);
            $showMigrated = $this->showMigratedCases($filters);

            $cases = $showOnline ? CaseRecord::listCases($filters) : [];
            $migratedCases = $showMigrated ? CaseRecord::listLegacyCases($this->legacyFilters($filters)) : [];
            $assignedCases = $isCoordinator && $showOnline
                ? CaseRecord::listCases(array_merge($filters, ['assigned_coordinator_account_id' => (int) $this->user['account_id']]))
                : [];

            echo json_encode([
                'success' => true,
                'cases' => $cases,
                'assignedCases' => $assignedCases,
                'migratedCases' => $migratedCases,
                'total' => count($cases),
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Unable to filter cases right now.',
            ]);
        }

        exit;
    }

    private function showOnlineCases(array $filters) {
        return in_array(strtolower((string) ($filters['case_source'] ?? '')), ['', 'online'], true);
    }

    private function showMigratedCases(array $filters) {
        $source = strtolower((string) ($filters['case_source'] ?? ''));

        if (!in_array($source, ['', 'migrated', 'legacy'], true)) {
            return false;
        }

        if (!empty($filters['assigned_coordinator_account_id'])) {
            return false;
        }

        return true;
    }

    private function legacyFilters(array $filters) {
        return [
            'search' => $filters['search'] ?? '',
            'status' => $filters['status'] ?? '',
            'classification' => $filters['classification'] ?? '',
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
            'month' => $filters['month'] ?? 0,
            'year' => $filters['year'] ?? 0,
        ];
    }

    public function archivedIndex() {
        return [
            'user' => $this->user,
            'cases' => CaseRecord::listArchivedCases($this->archivedFilters($_GET)),
            'filters' => $this->archivedFilters($_GET),
        ];
    }

    public function archivedSearch() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $cases = CaseRecord::listArchivedCases($this->archivedFilters($_GET));
            echo json_encode([
                'success' => true,
                'cases' => $cases,
                'total' => count($cases),
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Unable to filter archived cases right now.',
            ]);
        }

        exit;
    }

    public function show($complaintId) {
        $case = CaseRecord::findCase($complaintId);

        if (!$case) {
            http_response_code(404);
            echo 'Case not found.';
            exit;
        }

        if (!$this->canAccessCaseRecord($case)) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->handleAction($complaintId);
        }

        if (!Message::isStaffRole($this->user['role'] ?? '')
            && !Message::canAccessCaseMessages($case, $this->user)) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }

        $messageReceiver = Message::defaultCounterpartForCase($case, $this->user);

        if ($messageReceiver) {
            Message::markPairMessagesRead((int) $this->user['account_id'], (int) $messageReceiver['account_id']);
        }

        return [
            'user' => $this->user,
            'case' => $case,
            'respondents' => CaseRecord::getRespondents($complaintId),
            'witnesses' => CaseRecord::getWitnesses($complaintId),
            'evidence' => CaseRecord::getEvidence($complaintId),
            'history' => CaseRecord::getHistory($complaintId),
            'coordinators' => CaseRecord::getCoordinators(),
            'resubmission' => CaseRecord::getLatestRevisionSubmission($complaintId),
            'hearings' => Hearing::forComplaint($complaintId),
            'messages' => $messageReceiver
                ? Message::forPair((int) $this->user['account_id'], (int) $messageReceiver['account_id'])
                : [],
            'messageReceiver' => $messageReceiver,
            'message' => $_SESSION['case_message'] ?? null,
            'errors' => $_SESSION['case_errors'] ?? [],
            'updates' => CaseUpdate::forCase($complaintId),
            'classificationOptions' => $this->classificationOptions(),
        ];
    }

    private function classificationOptions() {
        $existing = is_array(CaseRecord::getClassifications()) ? CaseRecord::getClassifications() : [];
        return array_values(array_unique(array_merge($this->classifications, $existing)));
    }

    public function clearFlash() {
        unset($_SESSION['case_message'], $_SESSION['case_errors']);
    }

    private function authenticateStaff() {
        if (!isset($_SESSION['email'])) {
            header('Location: ../auth/login.php');
            exit;
        }

        $this->database = new Database();
        $this->db = $this->database->getConnection();

        User::setConnection($this->db);
        CaseRecord::setConnection($this->db);
        Hearing::setConnection($this->db);
        Notification::setConnection($this->db);
        Message::setConnection($this->db);
        AuditLog::setConnection($this->db);
        CaseUpdate::setConnection($this->db);

        $this->user = User::findByEmail($_SESSION['email']);
        $roleKey = strtolower(str_replace(['_', ' '], '-', $this->user['role'] ?? ''));

        if (!$this->user || $this->user['status'] !== 'active' || !$this->isStaffRole($roleKey)) {
            http_response_code(403);
            echo 'Access denied. Staff account required.';
            exit;
        }
    }

    private function handleAction($complaintId) {
        $action = $_POST['case_action'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');
        $actorAccountId = (int) $this->user['account_id'];

        try {
            $case = CaseRecord::findCase($complaintId);
            $caseLabel = $case['case_number'] ?? ('Case #' . $complaintId);
            $caseStatus = $case['status'] ?? '';
            $isClosed = in_array($caseStatus, ['Resolved', 'Escalated', 'Archived'], true);

            if ($this->roleKey() === 'coordinator'
                && (int) ($case['assigned_coordinator_account_id'] ?? 0) !== (int) $this->user['account_id']) {
                $_SESSION['case_errors'] = ['You can only manage cases assigned to you.'];
                header('Location: show.php?id=' . $complaintId);
                exit;
            }

            if (in_array($action, ['reject', 'return', 'assign', 'classify', 'escalate'], true) && $isClosed) {
                $_SESSION['case_errors'] = ['This case is already closed and can no longer be modified.'];
                header('Location: show.php?id=' . $complaintId);
                exit;
            }

            if ($action === 'classify') {
                $selected = trim((string) ($_POST['classification'] ?? ''));
                $classification = $selected;

                if ($selected === 'Others') {
                    $classification = trim((string) ($_POST['classification_other'] ?? ''));
                }

                if ($classification === '') {
                    $_SESSION['case_errors'] = ['Please select or specify a case classification.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                if ($selected !== 'Others' && !in_array($selected, $this->classifications, true)) {
                    $_SESSION['case_errors'] = ['Please select a valid case classification.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                if (strlen($classification) > 100) {
                    $_SESSION['case_errors'] = ['The case classification is too long (maximum 100 characters).'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::classifyCase($complaintId, $classification, $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Case Classification', 'Classified ' . $caseLabel . ' as ' . $classification . '.');
                $_SESSION['case_message'] = 'Case classification saved.';
            } elseif ($action === 'return') {
                $revisionFields = array_values(array_intersect($this->revisionFields, (array) ($_POST['revision_fields'] ?? [])));

                if ($remarks === '' || empty($revisionFields)) {
                    $_SESSION['case_errors'] = ['Revision remarks and at least one field to revise are required.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::updateStatus($complaintId, 'Returned for Revision', $remarks, $actorAccountId, $revisionFields);
                AuditLog::record($this->user, 'Complaint Updates', 'Returned complaint ' . $caseLabel . ' for revision. Fields: ' . implode(', ', $revisionFields) . '.');
                $_SESSION['case_message'] = 'Complaint returned for revision.';
            } elseif ($action === 'reject') {
                if ($remarks === '') {
                    $_SESSION['case_errors'] = ['Please provide a rejection note explaining why the complaint is being rejected.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::updateStatus($complaintId, 'Rejected', $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Complaint Updates', 'Rejected complaint ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Complaint rejected.';
            } elseif ($action === 'resolve') {
                if ($caseStatus !== 'Under Investigation') {
                    $_SESSION['case_errors'] = ['Only cases under investigation can be marked as resolved.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                $outcome = trim((string) ($_POST['outcome'] ?? ''));

                if ($outcome === '') {
                    $_SESSION['case_errors'] = ['Please provide the outcome/resolution before marking the case as resolved.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::resolveCase($complaintId, $remarks, $outcome, $actorAccountId);

                $actorName = trim(($this->user['first_name'] ?? '') . ' ' . ($this->user['last_name'] ?? '')) ?: 'SDRU';
                $closureMessage = 'Case ' . $caseLabel . ' has been resolved and is now closed. Thank you for your cooperation. Please contact the SDRU office if you have further concerns.';
                $closureRecipients = array_unique(array_filter([
                    (int) ($case['submitted_by_account_id'] ?? 0),
                    (int) ($case['assigned_coordinator_account_id'] ?? 0),
                ], fn($accountId) => $accountId > 0 && $accountId !== $actorAccountId));

                foreach ($closureRecipients as $recipientId) {
                    Message::createMessage($actorAccountId, $recipientId, $closureMessage);
                    Notification::notifyNewMessage(
                        $recipientId,
                        $actorName,
                        'web/views/messages/index.php?conversation_id=' . $actorAccountId
                    );
                }

                AuditLog::record($this->user, 'Case Resolution', 'Marked case ' . $caseLabel . ' as resolved.');
                $_SESSION['case_message'] = 'Case marked as resolved. A closure notice was sent to the complainant' . ((int) ($case['assigned_coordinator_account_id'] ?? 0) > 0 ? ' and the assigned coordinator' : '') . '.';
            } elseif ($action === 'escalate') {
                if ($caseStatus !== 'Under Investigation') {
                    $_SESSION['case_errors'] = ['Only cases under investigation can be marked as escalated.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                if ($remarks === '') {
                    $_SESSION['case_errors'] = ['Please provide the reason for escalation before marking the case as escalated.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::escalateCase($complaintId, $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Case Escalation', 'Marked case ' . $caseLabel . ' as escalated.');
                $_SESSION['case_message'] = 'Case marked as escalated. All case actions are now locked except archiving.';
            } elseif ($action === 'withdraw_escalation') {
                if (($case['status'] ?? '') !== 'Escalated') {
                    $_SESSION['case_errors'] = ['Only escalated cases can have their escalation withdrawn.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::updateStatus($complaintId, 'Under Investigation', $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Case Escalation Withdrawn', 'Withdrew escalation for ' . $caseLabel . '. Status returned to Under Investigation.');
                $_SESSION['case_message'] = 'Escalation withdrawn. The case is now under investigation again.';
            } elseif ($action === 'archive') {
                $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

                if (!in_array($case['status'] ?? '', ['Resolved', 'Escalated'], true)) {
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'errors' => ['Only resolved or escalated cases can be archived.']]);
                        exit;
                    }

                    $_SESSION['case_errors'] = ['Only resolved or escalated cases can be archived.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::updateStatus($complaintId, 'Archived', $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Case Archival', 'Archived case ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Case archived.';

                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'message' => 'This case has been moved to Archived Cases.', 'case_number' => $caseLabel]);
                    exit;
                }
            } elseif ($action === 'unarchive') {
                if (($case['status'] ?? '') !== 'Archived') {
                    $_SESSION['case_errors'] = ['Only archived cases can be returned to active cases.'];
                    header('Location: ../archived_cases/index.php');
                    exit;
                }

                CaseRecord::unarchiveCase($complaintId, $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Case Unarchival', 'Returned archived case ' . $caseLabel . ' to active cases.');
                $_SESSION['case_message'] = 'Case unarchived and returned to active cases.';
            } elseif ($action === 'reopen') {
                if (($case['status'] ?? '') !== 'Resolved') {
                    $_SESSION['case_errors'] = ['Only resolved cases can be opened again.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::updateStatus($complaintId, 'Under Investigation', $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Case Reopened', 'Reopened case ' . $caseLabel . '. Status returned to Under Investigation.');
                $_SESSION['case_message'] = 'Case opened again. Its status is now Under Investigation.';
            } elseif ($action === 'assign') {
                $coordinatorId = (int) ($_POST['coordinator_account_id'] ?? 0);

                if ($coordinatorId <= 0) {
                    $_SESSION['case_errors'] = ['Please select a coordinator.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::assignCoordinator($complaintId, $coordinatorId, $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Case Assignment', 'Assigned coordinator for ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Coordinator assigned.';
            } elseif ($action === 'case_update') {
                $updateType = trim((string) ($_POST['update_type'] ?? ''));
                $details = trim((string) ($_POST['details'] ?? ''));
                $allowedTypes = array_keys(CaseUpdate::updateTypes());

                if (!in_array($updateType, $allowedTypes, true)) {
                    $_SESSION['case_errors'] = ['Please select a valid update type.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                if ($details === '') {
                    $_SESSION['case_errors'] = ['Please provide the details of this case update.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                $attachmentErrors = FileUploadService::validateFiles($_FILES['attachments'] ?? []);
                $hasAttachments = !empty(array_values(array_filter(($_FILES['attachments']['name'] ?? []), fn($name) => $name !== '')));

                if (!empty($attachmentErrors)) {
                    $_SESSION['case_errors'] = ['Unable to attach evidence: ' . implode(' ', $attachmentErrors)];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                $savedFiles = $hasAttachments ? FileUploadService::saveToEvidence($_FILES['attachments']) : [];
                CaseUpdate::add($complaintId, $actorAccountId, $updateType, $details, $caseStatus, $savedFiles);

                $stageLabel = CaseUpdate::stageLabel($caseStatus);
                $typeLabel = CaseUpdate::updateTypes()[$updateType] ?? $updateType;
                AuditLog::record($this->user, 'Case Update', 'Added ' . $stageLabel . ' (' . $typeLabel . ') to ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Case update added. The case status was not changed.';
            } else {
                $_SESSION['case_errors'] = ['Invalid case action.'];
            }
        } catch (Throwable $exception) {
            $_SESSION['case_errors'] = ['Unable to update case. Please check the case management database schema.'];
        }

        header('Location: show.php?id=' . $complaintId);
        exit;
    }

    private function isStaffRole($roleKey) {
        foreach ($this->staffRoles as $role) {
            if ($roleKey === strtolower(str_replace(['_', ' '], '-', $role))) {
                return true;
            }
        }

        return false;
    }

    private function roleKey() {
        return strtolower(str_replace(['_', ' '], '-', $this->user['role'] ?? ''));
    }

    private function filters(array $input) {
        return [
            'search' => substr(trim((string) ($input['search'] ?? '')), 0, 255),
            'status' => substr(trim((string) ($input['status'] ?? '')), 0, 50),
            'classification' => substr(trim((string) ($input['classification'] ?? '')), 0, 100),
            'case_source' => substr(trim((string) ($input['case_source'] ?? '')), 0, 50),
            'date_from' => substr(trim((string) ($input['date_from'] ?? '')), 0, 10),
            'date_to' => substr(trim((string) ($input['date_to'] ?? '')), 0, 10),
            'month' => ($month = (int) ($input['month'] ?? 0)) >= 1 && $month <= 12 ? $month : '',
            'year' => ($year = (int) ($input['year'] ?? 0)) >= 2000 && $year <= 2100 ? $year : '',
            'coordinator' => (int) ($input['coordinator'] ?? 0),
            'assigned_coordinator_account_id' => (int) ($input['coordinator'] ?? 0),
        ];
    }

    private function archivedFilters(array $input) {
        return [
            'case_number' => substr(trim((string) ($input['case_number'] ?? '')), 0, 100),
            'student_name' => substr(trim((string) ($input['student_name'] ?? '')), 0, 255),
            'search' => substr(trim((string) ($input['search'] ?? '')), 0, 255),
        ];
    }

    private function canAccessCaseRecord(array $case) {
        return true;
    }
}
