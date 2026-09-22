<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/Hearing.php';
require_once __DIR__ . '/../models/CaseUpdate.php';
require_once __DIR__ . '/../models/ReformationRecord.php';
require_once __DIR__ . '/../models/ReformationReport.php';
require_once __DIR__ . '/../models/CaseApproval.php';
require_once __DIR__ . '/../models/CounterStatement.php';
require_once __DIR__ . '/../services/FileUploadService.php';
require_once __DIR__ . '/../services/Mailer.php';
require_once __DIR__ . '/../services/UserGoogleMailer.php';
require_once __DIR__ . '/../helpers/Security.php';

class CaseController {
    private $database;
    private $db;
    private $user;
    private $staffRoles = ['admin', 'sdr staff', 'sdr-staff', 'sdru-staff', 'coordinator', 'reformation-coordinator', 'head-of-sdru', 'sdru-head', 'head of sdru'];
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
        $isReformationCoordinator = $this->roleKey() === 'reformation-coordinator';
        $showOnline = $this->showOnlineCases($filters);
        $showMigrated = $this->showMigratedCases($filters);

        $assignedCases = $isCoordinator
            ? ($showOnline
                ? CaseRecord::listCases(array_merge($filters, ['assigned_coordinator_account_id' => (int) $this->user['account_id']]))
                : [])
            : ($isReformationCoordinator
                ? ($showOnline
                    ? CaseRecord::listCases(array_merge($filters, ['assigned_reformation_coordinator_account_id' => (int) $this->user['account_id']]))
                    : [])
                : []);

        $filterError = $_SESSION['case_filter_error'] ?? '';
        unset($_SESSION['case_filter_error']);

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
            'filterError' => $filterError,
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
            $isReformationCoordinator = $this->roleKey() === 'reformation-coordinator';
            $showOnline = $this->showOnlineCases($filters);
            $showMigrated = $this->showMigratedCases($filters);

            $cases = $showOnline ? CaseRecord::listCases($filters) : [];
            $migratedCases = $showMigrated ? CaseRecord::listLegacyCases($this->legacyFilters($filters)) : [];
            $assignedCases = $isCoordinator
                ? ($showOnline
                    ? CaseRecord::listCases(array_merge($filters, ['assigned_coordinator_account_id' => (int) $this->user['account_id']]))
                    : [])
                : ($isReformationCoordinator
                    ? ($showOnline
                        ? CaseRecord::listCases(array_merge($filters, ['assigned_reformation_coordinator_account_id' => (int) $this->user['account_id']]))
                        : [])
                    : []);

            $filterError = $_SESSION['case_filter_error'] ?? '';
            unset($_SESSION['case_filter_error']);

            echo json_encode([
                'success' => true,
                'cases' => $cases,
                'assignedCases' => $assignedCases,
                'migratedCases' => $migratedCases,
                'total' => count($cases),
                'filterError' => $filterError,
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
            'reformationCoordinators' => CaseRecord::getCoordinators('reformation-coordinator'),
            'reformationRecords' => ReformationRecord::forCase($complaintId),
            'reformationReports' => ReformationReport::forCase($complaintId),
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
            'respondentAccounts' => CaseRecord::respondentsWithAccounts($complaintId),
            'forwardPending' => $_SESSION['case_forward_pending'] ?? null,
            'counterStatements' => CounterStatement::forCase($complaintId),
            'pendingApproval' => in_array($this->roleKey(), ['head-of-sdru', 'sdru-head'], true)
                ? CaseApproval::findForCase((int) ($_GET['approval_id'] ?? 0), $complaintId)
                : null,
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
        CaseApproval::setConnection($this->db);
        CounterStatement::setConnection($this->db);

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
        $approvalExecution = false;
        $approvalId = (int) ($_POST['approval_id'] ?? 0);

        try {
            if ($action === 'approval_decision') {
                $headRoles = ['head-of-sdru', 'sdru-head'];
                if (!in_array($this->roleKey(), $headRoles, true)) {
                    throw new Exception('Only the SDRU head can review case approvals.');
                }
                $approval = CaseApproval::findForCase($approvalId, $complaintId);
                $decision = $_POST['approval_decision'] ?? '';
                if (!$approval || $approval['status'] !== 'Pending' || !in_array($decision, ['approve', 'reject'], true)) {
                    throw new Exception('This approval request is no longer available.');
                }
                if ($decision === 'reject') {
                    CaseApproval::decide($approvalId, $actorAccountId, 'Rejected', trim($_POST['review_remarks'] ?? ''));
                    Notification::createForUser(
                        (int) $approval['requested_by_account_id'],
                        'case_action_rejected',
                        'Case Action Rejected',
                        'Your requested action "' . $approval['action_label'] . '" for case ' . $approval['case_number'] . ' was rejected by the head.',
                        'web/views/cases/show.php?id=' . $complaintId
                    );
                    $_SESSION['case_message'] = 'Approval request rejected. No case action was executed.';
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }
                $payload = json_decode($approval['payload'], true);
                if (!is_array($payload)) throw new Exception('Approval request payload is invalid.');
                if (str_starts_with($approval['action_type'], 'hearing_')) {
                    $hearingId = (int) ($payload['hearing_id'] ?? 0);
                    if ($approval['action_type'] === 'hearing_schedule') {
                        $now = date('Y-m-d H:i:s');
                        Hearing::schedule([
                            'complaint_id' => $complaintId,
                            'scheduled_by_account_id' => (int) $approval['requested_by_account_id'],
                            'hearing_datetime' => date('Y-m-d H:i:s', strtotime($payload['hearing_datetime'] ?? '')),
                            'venue' => trim($payload['venue'] ?? ''),
                            'google_meet_link' => trim($payload['google_meet_link'] ?? ''),
                            'remarks' => trim($payload['remarks'] ?? ''),
                            'status' => 'Scheduled',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    } elseif ($approval['action_type'] === 'hearing_update') {
                        Hearing::updateHearing($hearingId, [
                            'hearing_datetime' => date('Y-m-d H:i:s', strtotime($payload['hearing_datetime'] ?? '')),
                            'venue' => trim($payload['venue'] ?? ''),
                            'google_meet_link' => trim($payload['google_meet_link'] ?? ''),
                            'remarks' => trim($payload['remarks'] ?? ''),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    } else {
                        Hearing::updateStatus($hearingId, ($payload['hearing_action'] ?? '') === 'cancel' ? 'Cancelled' : 'Completed');
                    }
                    CaseApproval::decide($approvalId, $actorAccountId, 'Approved', null);
                    Notification::createForUser(
                        (int) $approval['requested_by_account_id'],
                        'case_action_approved',
                        'Case Action Approved',
                        'Your requested action "' . $approval['action_label'] . '" for case ' . $approval['case_number'] . ' was approved and executed.',
                        'web/views/cases/show.php?id=' . $complaintId
                    );
                    AuditLog::record($this->user, 'Case Activity Approval', 'Approved and executed ' . $approval['action_label'] . ' for ' . ($approval['case_number'] ?? ('case #' . $complaintId)) . '.');
                    $_SESSION['case_message'] = 'Approved hearing action executed successfully.';
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }
                if (empty($payload['case_action'])) throw new Exception('Approval request payload is invalid.');
                $_POST = array_merge($_POST, $payload, ['approval_execution' => '1']);
                $action = $payload['case_action'];
                $approvalExecution = true;
                $remarks = trim($_POST['remarks'] ?? '');
            }
            $case = CaseRecord::findCase($complaintId);
            $caseLabel = $case['case_number'] ?? ('Case #' . $complaintId);
            $caseStatus = $case['status'] ?? '';
            $isClosed = in_array($caseStatus, ['Resolved', 'Reformation in Progress', 'Reformation Completed', 'Escalated', 'Archived'], true);

            if (in_array($this->roleKey(), ['sdr-staff', 'sdru-staff'], true)) {
                $_SESSION['case_errors'] = ['Your role allows viewing cases only. Case actions are reserved for managerial staff.'];
                header('Location: show.php?id=' . $complaintId);
                exit;
            }

            if ($this->roleKey() === 'coordinator'
                && (int) ($case['assigned_coordinator_account_id'] ?? 0) !== (int) $this->user['account_id']) {
                $_SESSION['case_errors'] = ['You can only manage cases assigned to you.'];
                header('Location: show.php?id=' . $complaintId);
                exit;
            }

            $headRoles = ['head-of-sdru', 'sdru-head'];
            $respondentManagerRoles = ['coordinator', 'reformation-coordinator', 'head-of-sdru', 'sdru-head'];
            $respondentInvitationActions = ['create_respondent_account', 'link_respondent_account', 'resend_respondent_invite', 'toggle_respondent_account', 'forward_to_respondents', 'forward_case_to_respondent', 'confirm_forward_case_to_respondent', 'cancel_forward_case_to_respondent'];
            if (in_array($action, $respondentInvitationActions, true)
                && !in_array($this->roleKey(), $respondentManagerRoles, true)
            ) {
                $_SESSION['case_errors'] = ['Only the assigned coordinator, assigned reformation coordinator, or SDRU head can manage respondents or forward case information to them.'];
                header('Location: show.php?id=' . $complaintId);
                exit;
            }

            $coordinatorRoles = ['coordinator', 'reformation-coordinator'];
            $approvalActions = ['reject', 'return', 'assign', 'classify', 'escalate', 'withdraw_escalation', 'resolve', 'reopen', 'archive', 'unarchive', 'case_update', 'assign_reformation', 'reformation_activity', 'reformation_completed'];
            if (in_array($this->roleKey(), $coordinatorRoles, true)
                && in_array($action, $approvalActions, true)
                && !$approvalExecution
            ) {
                $payload = $_POST;
                unset($payload['csrf_token'], $payload['approval_id'], $payload['approval_execution']);
                $approvalLabel = $action === 'case_update' && ($payload['update_type'] ?? '') === 'additional_details'
                    ? 'Update Respondent Details'
                    : ucwords(str_replace('_', ' ', $action));
                $approval = CaseApproval::createForCase($complaintId, $actorAccountId, $action, $approvalLabel, $payload);
                Notification::createForHeads(
                    'case_action_approval_needed',
                    'Case Action Approval Needed',
                    'A coordinator submitted "' . ($approval['action_label'] ?? ucwords(str_replace('_', ' ', $action))) . '" for case ' . $caseLabel . ' and is waiting for your review.',
                    'web/views/cases/show.php?id=' . $complaintId . '&approval_id=' . (int) ($approval['approval_id'] ?? 0)
                );
                $_SESSION['case_message'] = 'Action submitted for head approval. The case was not changed.';
                header('Location: show.php?id=' . $complaintId);
                exit;
            }

            if ($this->roleKey() === 'reformation-coordinator'
                && (int) ($case['assigned_reformation_coordinator_account_id'] ?? 0) !== (int) $this->user['account_id']) {
                $_SESSION['case_errors'] = ['You can only manage cases assigned to you for reformation.'];
                header('Location: show.php?id=' . $complaintId);
                exit;
            }

            if (in_array($action, ['reject', 'return', 'assign', 'classify', 'escalate'], true) && $isClosed) {
                $_SESSION['case_errors'] = ['This case is already closed and can no longer be modified.'];
                header('Location: show.php?id=' . $complaintId);
                exit;
            }

            if ($action === 'case_update' && ($_POST['update_type'] ?? '') === 'additional_details') {
                $respondentId = (int) ($_POST['respondent_id'] ?? 0);
                $existingRespondents = CaseRecord::getRespondents($complaintId);
                if ($respondentId <= 0 && !empty($_POST['respondent_name'])) {
                    $matchingRespondents = array_values(array_filter(
                        $existingRespondents,
                        fn($respondent) => strcasecmp(trim((string) $respondent['full_name']), trim((string) $_POST['respondent_name'])) === 0
                    ));
                    if (count($matchingRespondents) === 1) {
                        $respondentId = (int) $matchingRespondents[0]['respondent_id'];
                    }
                }
                if ($respondentId <= 0 && count($existingRespondents) === 1) {
                    $respondentId = (int) $existingRespondents[0]['respondent_id'];
                }
                $existing = null;
                foreach ($existingRespondents as $respondent) {
                    if ((int) $respondent['respondent_id'] === $respondentId) {
                        $existing = $respondent;
                        break;
                    }
                }
                $respondentType = trim((string) ($_POST['respondent_type'] ?? ''));
                $respondentName = trim((string) ($_POST['respondent_name'] ?? ''));
                if (!$existing && empty($existingRespondents)) {
                    if ($respondentType === '' || $respondentName === '') {
                        $_SESSION['case_errors'] = ['Respondent type and full name are required to add the first respondent.'];
                        header('Location: show.php?id=' . $complaintId);
                        exit;
                    }
                } elseif (!$existing) {
                    $_SESSION['case_errors'] = ['Please select a valid respondent.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                $fieldMap = [
                    'respondent_type' => 'respondent_type', 'respondent_name' => 'full_name',
                    'respondent_gender' => 'gender', 'respondent_age' => 'age',
                    'respondent_student_no' => 'student_no', 'respondent_employee_no' => 'employee_no',
                    'respondent_college' => 'college', 'respondent_department' => 'office_department',
                    'respondent_course_year' => 'course_year', 'respondent_position' => 'position',
                    'respondent_affiliation' => 'affiliation', 'respondent_contact' => 'contact_info',
                    'respondent_email' => 'email', 'respondent_address' => 'address',
                    'respondent_details' => 'details',
                ];
                $course = trim((string) ($_POST['respondent_course'] ?? ''));
                $section = trim((string) ($_POST['respondent_section'] ?? ''));
                if ($course !== '' || $section !== '') {
                    $_POST['respondent_course_year'] = trim($course . ' | ' . $section, ' |');
                }
                $changes = [];
                $changedLabels = [];
                foreach ($fieldMap as $input => $column) {
                    $value = trim((string) ($_POST[$input] ?? ''));
                    if ($value !== '' && (string) ($existing[$column] ?? '') !== $value) {
                        $changes[$column] = $value;
                        $changedLabels[] = ucwords(str_replace('_', ' ', $column));
                    }
                }
                if (empty($changes)) {
                    $_SESSION['case_errors'] = ['Please provide at least one new respondent detail.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }
                if (!$existing) {
                    $newRespondent = [
                        'respondent_type' => $respondentType,
                        'full_name' => $respondentName,
                        'gender' => trim((string) ($_POST['respondent_gender'] ?? '')),
                        'age' => trim((string) ($_POST['respondent_age'] ?? '')),
                        'student_no' => trim((string) ($_POST['respondent_student_no'] ?? '')),
                        'employee_no' => trim((string) ($_POST['respondent_employee_no'] ?? '')),
                        'college' => trim((string) ($_POST['respondent_college'] ?? '')),
                        'office_department' => trim((string) ($_POST['respondent_department'] ?? '')),
                        'course_year' => trim((string) ($_POST['respondent_course_year'] ?? '')),
                        'position' => trim((string) ($_POST['respondent_position'] ?? '')),
                        'affiliation' => trim((string) ($_POST['respondent_affiliation'] ?? '')),
                        'contact_info' => trim((string) ($_POST['respondent_contact'] ?? '')),
                        'email' => trim((string) ($_POST['respondent_email'] ?? '')),
                        'address' => trim((string) ($_POST['respondent_address'] ?? '')),
                        'details' => trim((string) ($_POST['respondent_details'] ?? '')),
                    ];
                    $respondentId = CaseRecord::createRespondent($complaintId, $newRespondent);
                } else {
                    CaseRecord::updateRespondent($respondentId, $complaintId, $changes);
                }
                $_POST['details'] = 'Added the following respondent details: ' . implode(', ', $changedLabels) . '.';
                $this->autoLinkRespondent($complaintId, $respondentId, $caseLabel, (int) ($_POST['respondent_linked_account_id'] ?? 0));
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

                if ($selected !== 'Others' && !in_array($selected, $this->classificationOptions(), true)) {
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

                if (!in_array($case['status'] ?? '', ['Resolved', 'Reformation in Progress', 'Reformation Completed', 'Escalated'], true)) {
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
                if (!in_array(($case['status'] ?? ''), ['Resolved', 'Reformation in Progress', 'Reformation Completed'], true)) {
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
            } elseif ($action === 'assign_reformation') {
                $isHeadRole = in_array($this->roleKey(), ['head-of-sdru', 'sdru-head'], true);

                if (!$isHeadRole) {
                    $_SESSION['case_errors'] = ['Only the SDRU head can assign a reformation coordinator.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                if ($caseStatus !== 'Resolved') {
                    $_SESSION['case_errors'] = ['A case must be resolved before it can be assigned to a reformation coordinator.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                $reformationCoordinators = CaseRecord::getCoordinators('reformation-coordinator');
                $coordinatorId = (int) ($_POST['reformation_coordinator_account_id'] ?? 0);
                $hasReformationRole = $coordinatorId > 0 && in_array($coordinatorId, array_column($reformationCoordinators, 'account_id'), true);

                if (!$hasReformationRole) {
                    $_SESSION['case_errors'] = ['Please select a valid reformation coordinator.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::assignReformationCoordinator($complaintId, $coordinatorId, $remarks, $actorAccountId);
                AuditLog::record($this->user, 'Reformation Assignment', 'Assigned reformation coordinator for ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Reformation coordinator assigned.';
            } elseif ($action === 'reformation_activity') {
                $progressStatus = trim((string) ($_POST['progress_status'] ?? ''));
                $remarks = trim((string) ($_POST['remarks'] ?? ''));
                $allowedProgressStatuses = ['Ongoing', 'Completed', 'Needs Improvement', 'For Follow-up'];

                if (!in_array($progressStatus, $allowedProgressStatuses, true)) {
                    $_SESSION['case_errors'] = ['Please select a valid progress status.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                if ($remarks === '') {
                    $_SESSION['case_errors'] = ['Please provide progress notes.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                $attachmentErrors = FileUploadService::validateFiles($_FILES['progress_attachments'] ?? []);
                $hasAttachments = !empty(array_values(array_filter(($_FILES['progress_attachments']['name'] ?? []), fn($name) => $name !== '')));

                if (!empty($attachmentErrors)) {
                    $_SESSION['case_errors'] = ['Unable to attach progress files: ' . implode(' ', $attachmentErrors)];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                $savedFiles = $hasAttachments ? FileUploadService::saveToEvidence($_FILES['progress_attachments']) : [];
                ReformationRecord::add($complaintId, $actorAccountId, $progressStatus, $remarks, $savedFiles);
                Notification::createForHeads(
                    'reformation_progress_updated',
                    'Reformation Progress Updated',
                    'A reformation progress update was added for case ' . $caseLabel . '.',
                    'web/views/cases/show.php?id=' . $complaintId
                );
                AuditLog::record($this->user, 'Reformation Progress', 'Added a reformation progress update for ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Reformation progress update added.';
            } elseif ($action === 'reformation_completed') {
                if ($caseStatus !== 'Reformation in Progress') {
                    $_SESSION['case_errors'] = ['Reformation can only be completed once the case is in progress.'];
                    header('Location: show.php?id=' . $complaintId);
                    exit;
                }

                CaseRecord::markReformationCompleted($complaintId, $actorAccountId);
                AuditLog::record($this->user, 'Reformation Progress', 'Marked reformation as completed for ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Reformation marked as completed.';
            } elseif ($action === 'create_respondent_account') {
                $respondent = $this->respondentRowForAction($complaintId, (int) ($_POST['respondent_id'] ?? 0));
                if (!$respondent) {
                    $_SESSION['case_errors'] = ['Please select a valid respondent.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (!empty($respondent['linked_account_id'])) {
                    $_SESSION['case_errors'] = ['This respondent already has a linked account.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $email = strtolower(trim((string) ($_POST['account_email'] ?? ($respondent['email'] ?? ''))));
                if ($email === '') {
                    $_SESSION['case_errors'] = ['Respondent email is required before sending the invitation.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['case_errors'] = ['Please provide a valid email address for the respondent invitation.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (User::findByEmailInexact($email)) {
                    $_SESSION['case_errors'] = ['An account already exists with that email. Use "Link Existing Account" instead.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $firstName = trim((string) ($_POST['account_first_name'] ?? ''));
                $lastName = trim((string) ($_POST['account_last_name'] ?? ''));
                if ($firstName === '' || $lastName === '') {
                    $_SESSION['case_errors'] = ['First name and last name are required for the new account.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $accountData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password_hash' => null,
                    'role' => 'respondent',
                    'status' => 'inactive',
                    'phone_number' => (string) ($respondent['contact_info'] ?? '') !== '' ? substr($respondent['contact_info'], 0, 20) : '',
                    'gender' => (string) ($respondent['gender'] ?? '') !== '' ? $respondent['gender'] : '',
                    'birthday' => (string) ($respondent['birthday'] ?? '') !== '' ? $respondent['birthday'] : null,
                    'address' => (string) ($respondent['address'] ?? '') !== '' ? $respondent['address'] : '',
                    'student_number' => (string) ($respondent['student_no'] ?? '') !== '' ? $respondent['student_no'] : null,
                    'college' => (string) ($respondent['college'] ?? '') !== '' ? $respondent['college'] : null,
                    'course' => (string) ($respondent['course_year'] ?? '') !== '' ? $respondent['course_year'] : null,
                ];
                $createdAccount = User::create($accountData);
                if (!$createdAccount) {
                    $_SESSION['case_errors'] = ['Could not create the respondent account. Please try again.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                CaseRecord::linkRespondent($complaintId, $respondent['respondent_id'], (int) $createdAccount->account_id);

                $token = bin2hex(random_bytes(32));
                CaseRecord::storeRespondentInvitation($complaintId, $respondent['respondent_id'], $token);
                $activationUrl = $this->siteUrl('web/views/auth/activate.php?token=' . $token);
                $invitationSent = Mailer::send(
                    $email,
                    'Activate Your SICMS Respondent Account',
                    Mailer::invitationEmailBody($firstName . ' ' . $lastName, $activationUrl, $caseLabel)
                );
                AuditLog::record($this->user, 'Respondent Account Created', 'Created respondent account ' . $email . ' for ' . $respondent['full_name'] . ' on ' . $caseLabel . '.');
                $_SESSION['case_message'] = $invitationSent
                    ? 'Respondent account created. Activation invitation sent to ' . $email . '.'
                    : 'Respondent account created. Invitation prepared for ' . $email . '; delivery to a mail server could not be confirmed on this host. A copy is kept in storage/outbound_emails.';
            } elseif ($action === 'link_respondent_account') {
                $respondent = $this->respondentRowForAction($complaintId, (int) ($_POST['respondent_id'] ?? 0));
                if (!$respondent) {
                    $_SESSION['case_errors'] = ['Please select a valid respondent.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (!empty($respondent['linked_account_id'])) {
                    $_SESSION['case_errors'] = ['This respondent already has a linked account.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $query = trim((string) ($_POST['account_search'] ?? ''));
                if ($query === '') {
                    $_SESSION['case_errors'] = ['Please enter an account email or full name to search for.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $account = User::findByEmailInexact($query);
                if (!$account) {
                    $matches = array_values(array_filter(
                        User::searchAccounts($query, 10),
                        fn($candidate) => strcasecmp(trim(($candidate['first_name'] ?? '') . ' ' . ($candidate['last_name'] ?? '')), $query) === 0
                    ));
                    if (count($matches) !== 1) {
                        $_SESSION['case_errors'] = ['No unique existing account matched. Provide the exact account email.'];
                        header('Location: show.php?id=' . $complaintId); exit;
                    }
                    $account = $matches[0];
                }

                if (($account['status'] ?? '') !== 'active') {
                    $_SESSION['case_errors'] = ['Only active accounts can be linked to a respondent.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (strtolower((string) ($account['email'] ?? '')) !== strtolower((string) trim((string) ($respondent['email'] ?? '')))) {
                    $_SESSION['case_errors'] = ['The account email does not match this respondent\'s recorded email. Update the respondent\'s email first, or create a new account.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                CaseRecord::linkRespondent($complaintId, $respondent['respondent_id'], (int) $account['account_id']);
                AuditLog::record($this->user, 'Respondent Account Linked', 'Linked account ' . $account['email'] . ' to respondent ' . $respondent['full_name'] . ' on ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Existing account linked to the respondent.';
            } elseif ($action === 'resend_respondent_invite') {
                $respondent = $this->respondentRowForAction($complaintId, (int) ($_POST['respondent_id'] ?? 0));
                if (!$respondent) {
                    $_SESSION['case_errors'] = ['Please select a valid respondent.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (empty($respondent['linked_account_id'])) {
                    $_SESSION['case_errors'] = ['This respondent has no linked account to invite.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (($respondent['account_status'] ?? '') === 'active') {
                    $_SESSION['case_errors'] = ['This respondent\'s account is already active. No invitation is needed.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $token = (string) ($respondent['invitation_token'] ?? '');
                if ($token === '' || CaseRecord::invitationIsExpired((string) ($respondent['invited_at'] ?? ''))) {
                    $token = bin2hex(random_bytes(32));
                }
                CaseRecord::storeRespondentInvitation($complaintId, $respondent['respondent_id'], $token);
                $activationUrl = $this->siteUrl('web/views/auth/activate.php?token=' . $token);
                $invitationSent = Mailer::send(
                    $respondent['account_email'],
                    'Your SICMS Respondent Account Activation Link',
                    Mailer::invitationEmailBody(
                        trim(($respondent['account_first_name'] ?? '') . ' ' . ($respondent['account_last_name'] ?? '')),
                        $activationUrl,
                        $caseLabel
                    )
                );
                AuditLog::record($this->user, 'Respondent Account Invited', 'Re-sent activation invitation to ' . $respondent['account_email'] . ' for ' . $caseLabel . '.');
                $_SESSION['case_message'] = $invitationSent
                    ? 'A new activation invitation was sent to ' . $respondent['account_email'] . '.'
                    : 'Activation invitation re-prepared for ' . $respondent['account_email'] . '; delivery to a mail server could not be confirmed on this host. A copy is kept in storage/outbound_emails.';
            } elseif ($action === 'toggle_respondent_account') {
                $respondent = $this->respondentRowForAction($complaintId, (int) ($_POST['respondent_id'] ?? 0));
                if (!$respondent) {
                    $_SESSION['case_errors'] = ['Please select a valid respondent.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (empty($respondent['linked_account_id'])) {
                    $_SESSION['case_errors'] = ['This respondent has no linked account yet.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (($respondent['account_status'] ?? '') === 'active') {
                    $newStatus = 'inactive';
                    $actionLabel = 'Deactivated';
                } else {
                    $newStatus = 'active';
                    $actionLabel = 'Reactivated';
                }
                User::updateById((int) $respondent['linked_account_id'], [
                    'status' => $newStatus,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                Notification::createForUser(
                    (int) $respondent['linked_account_id'],
                    'account_status_changed',
                    'Account ' . $actionLabel,
                    'Your respondent account for case ' . $caseLabel . ' was ' . strtolower($actionLabel) . ' by the SDRU. Please contact the office if this is unexpected.',
                    'web/views/respondent/case_show.php?id=' . $complaintId
                );
                AuditLog::record($this->user, $actionLabel === 'Deactivated' ? 'Respondent Account Deactivated' : 'Respondent Account Reactivated', $actionLabel . ' account ' . $respondent['account_email'] . ' linked to respondent ' . $respondent['full_name'] . ' on ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Respondent account ' . strtolower($actionLabel) . '.';
            } elseif ($action === 'forward_case_to_respondent') {
                $respondent = $this->respondentRowForAction($complaintId, (int) ($_POST['respondent_id'] ?? 0));
                if (!$respondent) {
                    $_SESSION['case_errors'] = ['Please select a valid respondent.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (!empty($respondent['linked_account_id'])) {
                    $_SESSION['case_errors'] = ['This respondent already has a linked account.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $studentNo = trim((string) ($respondent['student_no'] ?? ''));
                $fullName = trim((string) ($respondent['full_name'] ?? ''));
                if ($studentNo === '') {
                    $_SESSION['case_errors'] = ['No Student Number is recorded for this respondent. Add the Student Number through "Update Respondent Details" before forwarding the case.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $matches = User::findComplainantsByStudentNo($studentNo, $fullName);
                if (empty($matches)) {
                    $_SESSION['case_errors'] = ['No existing account found.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $nameMatches = array_values(array_filter($matches, fn($match) => !empty($match['name_matches'])));
                $candidates = !empty($nameMatches) ? $nameMatches : $matches;

                $_SESSION['case_forward_pending'] = [
                    'complaint_id' => (int) $complaintId,
                    'respondent_id' => (int) $respondent['respondent_id'],
                    'candidates' => $candidates,
                ];
                $_SESSION['case_message'] = 'A matching existing account was found for this respondent. Review the account below and confirm to link it.';
            } elseif ($action === 'confirm_forward_case_to_respondent') {
                $pending = $_SESSION['case_forward_pending'] ?? null;
                $respondentId = (int) ($_POST['respondent_id'] ?? 0);
                $accountId = (int) ($_POST['account_id'] ?? 0);

                if (!$pending || (int) ($pending['complaint_id'] ?? 0) !== (int) $complaintId || (int) ($pending['respondent_id'] ?? 0) !== $respondentId) {
                    unset($_SESSION['case_forward_pending']);
                    $_SESSION['case_errors'] = ['The account search is no longer active. Run the search again before confirming.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                $respondent = $this->respondentRowForAction($complaintId, $respondentId);
                if (!$respondent) {
                    unset($_SESSION['case_forward_pending']);
                    $_SESSION['case_errors'] = ['Please select a valid respondent.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (!empty($respondent['linked_account_id'])) {
                    unset($_SESSION['case_forward_pending']);
                    $_SESSION['case_errors'] = ['This respondent already has a linked account.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $selected = null;
                foreach ($pending['candidates'] as $candidate) {
                    if ((int) $candidate['account_id'] === $accountId) {
                        $selected = $candidate;
                        break;
                    }
                }
                if (!$selected) {
                    unset($_SESSION['case_forward_pending']);
                    $_SESSION['case_errors'] = ['The selected account is no longer available. Run the search again.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                if (($selected['status'] ?? '') !== 'active') {
                    unset($_SESSION['case_forward_pending']);
                    $_SESSION['case_errors'] = ['Only active accounts can be linked to a respondent.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                unset($_SESSION['case_forward_pending']);
                CaseRecord::linkRespondent($complaintId, $respondentId, $accountId);
                AuditLog::record($this->user, 'Respondent Account Linked', 'Linked existing account ' . ($selected['email'] ?? '') . ' to respondent ' . $respondent['full_name'] . ' on ' . $caseLabel . '.');
                $_SESSION['case_message'] = 'Respondent linked to the existing account. You can now forward the case information to them using the "Forward Case Information to Respondent" section.';
            } elseif ($action === 'cancel_forward_case_to_respondent') {
                $pending = $_SESSION['case_forward_pending'] ?? null;
                if ($pending && (int) ($pending['complaint_id'] ?? 0) === (int) $complaintId) {
                    unset($_SESSION['case_forward_pending']);
                }
                $_SESSION['case_message'] = 'The account search was cancelled.';
            } elseif ($action === 'forward_to_respondents') {
                $linked = CaseRecord::linkedRespondentAccounts($complaintId);
                if (empty($linked)) {
                    $_SESSION['case_errors'] = ['Link at least one respondent account before forwarding the case to respondents.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                if ($caseStatus === 'Submitted') {
                    $_SESSION['case_errors'] = ['Assign a coordinator to this case before forwarding it to the respondents.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $selectedIds = array_filter(array_map('intval', (array) ($_POST['respondent_ids'] ?? [])), fn($id) => $id > 0);
                if (empty($selectedIds)) {
                    $_SESSION['case_errors'] = ['Select at least one linked respondent to forward the case to.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $linkedById = [];
                foreach ($linked as $account) {
                    $linkedById[(int) $account['account_id']] = $account;
                }
                $recipients = [];
                foreach ($selectedIds as $accountId) {
                    if (isset($linkedById[$accountId])) {
                        $recipients[$accountId] = $linkedById[$accountId];
                    }
                }
                if (empty($recipients)) {
                    $_SESSION['case_errors'] = ['The selected respondent is no longer linked to this case. Reload and try again.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                $visibleKeys = (array) ($_POST['respondent_visibility'] ?? []);
                $visibility = [];
                foreach (CaseRecord::RESPONDENT_VISIBILITY_KEYS as $key) {
                    $visibility[$key] = in_array($key, $visibleKeys, true);
                }

                CaseRecord::releaseToRespondents($complaintId, $actorAccountId, $visibility, array_keys($recipients));

                $caseUrl = $this->siteUrl('web/views/respondent/case_show.php?id=' . $complaintId);

                $toEmails = [];
                foreach ($recipients as $account) {
                    $em = trim((string) ($account['email'] ?? ''));
                    if ($em !== '') {
                        $toEmails[$em] = $em;
                    }
                }
                $typedEmail = trim((string) ($_POST['respondent_extra_email'] ?? ''));
                if ($typedEmail !== '' && filter_var($typedEmail, FILTER_VALIDATE_EMAIL)) {
                    $toEmails[$typedEmail] = $typedEmail;
                }

                $gmailer = UserGoogleMailer::instance($this->db);
                foreach ($toEmails as $em) {
                    $subject = 'Case Information Forwarded to You';
                    $body = Mailer::noticeEmailBody(
                        'Dear Respondent',
                        'The SDRU has forwarded the permitted details of case ' . $caseLabel . ' to you as a respondent. You can now review the case and file your counter-statement through SICMS.',
                        $caseUrl,
                        'View Case Details'
                    );

                    $sent = $gmailer->sendFromUser((int) $actorAccountId, $em, $subject, $body, 'SICMS');
                    if (empty($sent['sent'])) {
                        Mailer::send($em, $subject, $body);
                    }
                }

                $releasedNames = array_map(
                    fn($account) => trim(($account['first_name'] ?? '') . ' ' . ($account['last_name'] ?? '') ?: $account['email']),
                    $recipients
                );
                AuditLog::record($this->user, 'Case Forwarded to Respondent', 'Forwarded ' . $caseLabel . ' to respondent(s): ' . implode(', ', $releasedNames) . ' via email and in-app notification.');
                $_SESSION['case_message'] = 'Case information forwarded to the respondent(s). They can now review the permitted details and file their counter-statement.';
            } elseif ($action === 'request_counter_revision') {
                $counterStatementId = (int) ($_POST['counter_statement_id'] ?? 0);
                if ($counterStatementId <= 0) {
                    $_SESSION['case_errors'] = ['Please select a counter-statement.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }
                $statement = CounterStatement::find($counterStatementId);
                if (!$statement || (int) $statement['complaint_id'] !== (int) $complaintId || $statement['status'] !== 'Submitted') {
                    $_SESSION['case_errors'] = ['Only a submitted counter-statement on this case can be returned for revision.'];
                    header('Location: show.php?id=' . $complaintId); exit;
                }

                CounterStatement::returnForRevision($counterStatementId, (int) $statement['respondent_account_id']);
                Notification::createForUser(
                    (int) $statement['respondent_account_id'],
                    'counter_revision_requested',
                    'Counter-Statement Revision Requested',
                    'Your counter-statement for ' . $caseLabel . ' was returned for revision. Please edit and resubmit it.',
                    'web/views/respondent/case_show.php?id=' . $complaintId
                );

                $respondent = $this->respondentRowForAction($complaintId, (int) $statement['respondent_id']);
                if ($respondent && !empty($respondent['account_email'])) {
                    $respondentUrl = $this->siteUrl('web/views/respondent/case_show.php?id=' . $complaintId);
                    Mailer::send(
                        $respondent['account_email'],
                        'Counter-Statement Returned for Revision',
                        Mailer::noticeEmailBody(
                            'Dear ' . trim(($respondent['account_first_name'] ?? '') . ' ' . ($respondent['account_last_name'] ?? '')),
                            'Your counter-statement for case ' . $caseLabel . ' was returned for revision by the SDRU. Please log in to review the request and update your statement.',
                            $respondentUrl,
                            'Update My Counter-Statement'
                        )
                    );
                }

                AuditLog::record($this->user, 'Counter-Statement Revision', 'Returned the counter-statement for ' . ($respondent['full_name'] ?? ('respondent #' . (int) $statement['respondent_id'])) . ' on ' . $caseLabel . ' for revision.');
                $_SESSION['case_message'] = 'Counter-statement returned for revision. The respondent has been notified.';
            } elseif ($action === 'proceed_counter_statement' || $action === 'forward_counter_statement') {
                $this->counterStatementDecision(
                    $complaintId,
                    $caseLabel,
                    $actorAccountId,
                    $action === 'proceed_counter_statement' ? 'proceed_to_investigation' : 'forwarded_to_complainant'
                );
            } else {
                $_SESSION['case_errors'] = ['Invalid case action.'];
            }
            if ($approvalExecution) {
                CaseApproval::decide($approvalId, $actorAccountId, 'Approved', null);
                $approved = CaseApproval::findForCase($approvalId, $complaintId);
                if ($approved) {
                    Notification::createForUser(
                        (int) $approved['requested_by_account_id'],
                        'case_action_approved',
                        'Case Action Approved',
                        'Your requested action "' . $approved['action_label'] . '" for case ' . $approved['case_number'] . ' was approved and executed.',
                        'web/views/cases/show.php?id=' . $complaintId
                    );
                }
                $_SESSION['case_message'] = 'Approved action executed successfully.';
            }
        } catch (Throwable $exception) {
            error_log('SICMS case action "' . $action . '" failed for complaint #' . (int) $complaintId . ': ' . $exception->getMessage());
            $_SESSION['case_errors'] = ['Unable to update case. Please check the case management database schema.'];
        }

        header('Location: show.php?id=' . $complaintId);
        exit;
    }

    private function counterStatementDecision($complaintId, $caseLabel, $actorAccountId, $decision) {
        if (!in_array($this->roleKey(), ['coordinator', 'head-of-sdru', 'sdru-head'], true)) {
            $_SESSION['case_errors'] = ['Only the assigned coordinator (or the SDRU head) can take this action.'];
            header('Location: show.php?id=' . $complaintId);
            exit;
        }

        $counterStatementId = (int) ($_POST['counter_statement_id'] ?? 0);
        if ($counterStatementId <= 0) {
            $_SESSION['case_errors'] = ['Please select a counter-statement.'];
            header('Location: show.php?id=' . $complaintId);
            exit;
        }

        $statement = CounterStatement::find($counterStatementId);
        if (!$statement || (int) $statement['complaint_id'] !== (int) $complaintId || $statement['status'] !== 'Submitted') {
            $_SESSION['case_errors'] = ['Only a submitted counter-statement on this case can be reviewed.'];
            header('Location: show.php?id=' . $complaintId);
            exit;
        }

        if (!empty($statement['coordinator_action'])) {
            $alreadyForwarded = $statement['coordinator_action'] === 'forwarded_to_complainant';
            if ($decision === 'proceed_to_investigation' && $alreadyForwarded
                && !empty($statement['complaint_response_submitted_at'])) {
                // allowed: advancing a statement that was forwarded and answered
            } elseif ($decision === 'proceed_to_investigation' && $alreadyForwarded) {
                $_SESSION['case_errors'] = ['This counter-statement was forwarded for the complainant response. Wait for the response before proceeding to investigation.'];
                header('Location: show.php?id=' . $complaintId);
                exit;
            } else {
                $_SESSION['case_errors'] = ['This counter-statement has already been reviewed by the coordinator.'];
                header('Location: show.php?id=' . $complaintId);
                exit;
            }
        }

        if (!CounterStatement::markCoordinatorAction($counterStatementId, $decision, $actorAccountId)) {
            $_SESSION['case_errors'] = ['This counter-statement has already been reviewed by the coordinator.'];
            header('Location: show.php?id=' . $complaintId);
            exit;
        }

        $respondentName = trim((string) ($statement['respondent_full_name'] ?? ''));
        if ($respondentName === '') {
            $respondentName = 'respondent #' . (int) $statement['respondent_id'];
        }

        if ($decision === 'proceed_to_investigation') {
            CaseRecord::recordCaseActivity(
                $complaintId,
                'Case Proceeded to Investigation',
                'Proceeded to investigation after reviewing the counter-statement of ' . $respondentName . ' on ' . $caseLabel . '.',
                $actorAccountId
            );
            AuditLog::record($this->user, 'Case Proceeded to Investigation', 'Proceeded to investigation after reviewing the counter-statement of ' . $respondentName . ' on ' . $caseLabel . '.');
            $_SESSION['case_message'] = 'Counter-statement reviewed. The case will continue through the investigation workflow.';
            return;
        }

        CaseRecord::recordCaseActivity(
            $complaintId,
            'Counter-Statement Forwarded to Complainant',
            'Forwarded the counter-statement of ' . $respondentName . ' to the complainant for response on ' . $caseLabel . '.',
            $actorAccountId
        );
        AuditLog::record($this->user, 'Counter-Statement Forwarded to Complainant', 'Forwarded the counter-statement of ' . $respondentName . ' to the complainant for response on ' . $caseLabel . '.');
        $_SESSION['case_message'] = 'Counter-statement forwarded to the complainant. The complainant may now review it and submit a response.';
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
        $dateFrom = substr(trim((string) ($input['date_from'] ?? '')), 0, 10);
        $dateTo = substr(trim((string) ($input['date_to'] ?? '')), 0, 10);

        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            $_SESSION['case_filter_error'] = 'The start date must be on or before the end date.';
        }

        return [
            'search' => substr(trim((string) ($input['search'] ?? '')), 0, 255),
            'status' => substr(trim((string) ($input['status'] ?? '')), 0, 50),
            'classification' => substr(trim((string) ($input['classification'] ?? '')), 0, 100),
            'case_source' => substr(trim((string) ($input['case_source'] ?? '')), 0, 50),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
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

private function respondentRowForAction($complaintId, $respondentId) {
        if ($respondentId <= 0) return null;
        foreach (CaseRecord::respondentsWithAccounts($complaintId) as $row) {
            if ((int) $row['respondent_id'] === $respondentId) {
                return $row;
            }
        }
        return null;
    }

    /* Automatic account linking whenever a respondent's details are added or
     * updated. Links only when an active SICMS account already exists with the
     * exact recorded email or student number. Never auto-creates accounts:
     * if only an email is present and no account matches, the respondent is
     * sent an external notice that they are named on the case. When an account
     * id was explicitly chosen in the respondent details form, that account is
     * linked directly instead of being resolved by email or student number. */
    private function autoLinkRespondent($complaintId, $respondentId, $caseLabel, $preferredAccountId = 0) {
        if ($respondentId <= 0) return;
        $respondent = $this->respondentRowForAction($complaintId, $respondentId);
        if (!$respondent) return;

        if (!empty($respondent['linked_account_id'])) return;

        $account = null;

        if ((int) $preferredAccountId > 0) {
            $candidate = User::findRow((int) $preferredAccountId);
            if ($candidate && ($candidate['status'] ?? '') === 'active'
                && Security::normalizeRole($candidate['role'] ?? '') === 'student') {
                $account = $candidate;
            }
        }

        $email = strtolower(trim((string) ($respondent['email'] ?? '')));
        $studentNo = trim((string) ($respondent['student_no'] ?? ''));

        if (!$account && $email !== '') {
            $candidate = User::findByEmailInexact($email);
            if ($candidate && ($candidate['status'] ?? '') === 'active') {
                $account = $candidate;
            }
        }
        if (!$account && $studentNo !== '') {
            $candidate = User::findActiveByStudentNumber($studentNo);
            if ($candidate) {
                $account = $candidate;
            }
        }

        if (!$account) {
            if ($email !== '') {
                Mailer::send(
                    $email,
                    'You Are Named as a Respondent on ' . $caseLabel,
                    Mailer::respondentNoticeEmailBody((string) ($respondent['full_name'] ?? 'A Respondent'), $caseLabel)
                );
                AuditLog::record($this->user, 'Respondent Notice Sent', 'Sent external respondent notice to ' . $email . ' for ' . ($respondent['full_name'] ?? '') . ' on ' . $caseLabel . '. No matching active account was available for automatic linking.');
                $_POST['details'] = trim((string) ($_POST['details'] ?? '')) . ' An external notice was sent to ' . $email . ' (no matching account was available for automatic linking).';
            }
            return;
        }

        CaseRecord::linkRespondent($complaintId, $respondentId, (int) $account['account_id']);
        $accountName = trim(($account['first_name'] ?? '') . ' ' . ($account['last_name'] ?? ''));
        Notification::createForUser(
            (int) $account['account_id'],
            'case_respondent_linked',
            'Linked as Respondent on ' . $caseLabel,
            'Your SICMS account has been automatically linked to case ' . $caseLabel . ' as the respondent. You can now view the case and submit a response.',
            'web/views/respondent/case_show.php?id=' . $complaintId
        );
        if ((int) $this->user['account_id'] !== (int) $account['account_id']) {
            Message::createMessage(
                (int) $this->user['account_id'],
                (int) $account['account_id'],
                'Your SICMS account has been automatically linked to case ' . $caseLabel . ' as the respondent. You can view the case and submit a response there.'
            );
        }
        AuditLog::record($this->user, 'Respondent Account Auto-Linked', 'Automatically linked account ' . $accountName . ' (' . $account['email'] . ') to respondent ' . ($respondent['full_name'] ?? '') . ' on ' . $caseLabel . '.');
        $_POST['details'] = trim((string) ($_POST['details'] ?? '')) . ' The respondent was automatically linked to the active SICMS account ' . $account['email'] . '.';
    }

    private function siteUrl($path) {
        return Mailer::applicationUrl($path);
    }
}
