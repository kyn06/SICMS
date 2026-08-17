<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Security.php';

class CaseController {
    private $database;
    private $db;
    private $user;
    private $staffRoles = ['super-admin', 'admin', 'sdr staff', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head', 'head of sdru'];
    private $revisionFields = ['complaint_title', 'complaint_details', 'incident_date', 'incident_time', 'incident_location', 'respondents', 'witnesses', 'evidence'];

    public function __construct() {
        Security::startSession();

        $this->authenticateStaff();
    }

    public function index() {
        $filters = $this->filters($_GET);

        return [
            'user' => $this->user,
            'cases' => CaseRecord::listCases($filters),
            'filters' => $filters,
            'statuses' => CaseRecord::getStatuses(),
            'classifications' => CaseRecord::getClassifications(),
        ];
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

        Message::markCaseMessagesRead($complaintId, (int) $this->user['account_id']);

        return [
            'user' => $this->user,
            'case' => $case,
            'respondents' => CaseRecord::getRespondents($complaintId),
            'witnesses' => CaseRecord::getWitnesses($complaintId),
            'evidence' => CaseRecord::getEvidence($complaintId),
            'history' => CaseRecord::getHistory($complaintId),
            'coordinators' => CaseRecord::getCoordinators(),
            'messages' => Message::forCaseForUser($complaintId, (int) $this->user['account_id']),
            'messageRecipients' => Message::getRecipientsForCase($case, $this->user),
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
                CaseRecord::updateStatus($complaintId, 'Rejected', $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Complaint Updates', 'Rejected complaint ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Complaint rejected.';
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

    private function canAccessCaseRecord(array $case) {
        if ($this->roleKey() !== 'coordinator') {
            return true;
        }

        return !empty($case['assigned_coordinator_account_id']) && (int) $case['assigned_coordinator_account_id'] === (int) $this->user['account_id'];
    }
}
