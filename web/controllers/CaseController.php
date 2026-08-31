<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/Hearing.php';
require_once __DIR__ . '/../helpers/Security.php';

class CaseController {
    private $database;
    private $db;
    private $user;
    private $staffRoles = ['super-admin', 'admin', 'sdr staff', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head', 'head of sdru'];
    private $revisionFields = ['complaint_details', 'incident_date', 'incident_time', 'incident_location', 'respondents', 'witnesses', 'evidence'];

    public function __construct() {
        Security::startSession();

        $this->authenticateStaff();
    }

    public function index() {
        $filters = $this->filters($_GET);

        return [
            'user' => $this->user,
            'cases' => CaseRecord::listCases($filters),
            'migratedCases' => CaseRecord::listLegacyCases([]),
            'canEditMigrated' => in_array($this->roleKey(), ['sdr-staff', 'sdru-staff'], true),
            'filters' => $filters,
            'statuses' => $this->activeStatuses(),
            'classifications' => CaseRecord::getClassifications(),
        ];
    }

    private function activeStatuses() {
        return array_values(array_filter(CaseRecord::getStatuses(), fn($status) => $status !== 'Archived'));
    }

    public function search() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $cases = CaseRecord::listCases($this->filters($_GET));
            echo json_encode([
                'success' => true,
                'cases' => $cases,
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

        if (!Message::canAccessCaseMessages($case, $this->user)) {
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
        ];
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
            $isClosed = in_array($caseStatus, ['Resolved', 'Archived'], true);

            if (in_array($action, ['verify', 'reject', 'return', 'assign'], true) && $isClosed) {
                $_SESSION['case_errors'] = ['This case is already closed and can no longer be modified.'];
                header('Location: show.php?id=' . $complaintId);
                exit;
            }

            if ($action === 'verify') {
                CaseRecord::updateStatus($complaintId, 'Verified', $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Case Verification', 'Verified complaint ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Complaint verified.';
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
                if ($caseStatus !== 'Verified') {
                    $_SESSION['case_errors'] = ['Only verified cases can be marked as resolved.'];
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
            } elseif ($action === 'archive') {
                if (($case['status'] ?? '') !== 'Resolved') {
                    $_SESSION['case_errors'] = ['Only resolved cases can be archived.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::updateStatus($complaintId, 'Archived', $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Case Archival', 'Archived case ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Case archived.';
            } elseif ($action === 'reopen') {
                if (($case['status'] ?? '') !== 'Resolved') {
                    $_SESSION['case_errors'] = ['Only resolved cases can be opened again.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::updateStatus($complaintId, 'Verified', $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Case Reopened', 'Reopened case ' . $caseLabel . '. Status returned to Verified.');
                $_SESSION['case_message'] = 'Case opened again. Its status is now Verified.';
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
        $filters = [
            'status' => substr(trim((string) ($input['status'] ?? '')), 0, 50),
            'classification' => substr(trim((string) ($input['classification'] ?? '')), 0, 100),
            'case_number' => substr(trim((string) ($input['case_number'] ?? '')), 0, 100),
            'student_name' => substr(trim((string) ($input['student_name'] ?? '')), 0, 255),
        ];

        if ($this->roleKey() === 'coordinator') {
            $filters['assigned_coordinator_account_id'] = (int) $this->user['account_id'];
        }

        return $filters;
    }

    private function archivedFilters(array $input) {
        $filters = [
            'case_number' => substr(trim((string) ($input['case_number'] ?? '')), 0, 100),
            'student_name' => substr(trim((string) ($input['student_name'] ?? '')), 0, 255),
        ];

        if ($this->roleKey() === 'coordinator') {
            $filters['assigned_coordinator_account_id'] = (int) $this->user['account_id'];
        }

        return $filters;
    }

    private function canAccessCaseRecord(array $case) {
        if ($this->roleKey() !== 'coordinator') {
            return true;
        }

        return !empty($case['assigned_coordinator_account_id']) && (int) $case['assigned_coordinator_account_id'] === (int) $this->user['account_id'];
    }
}
