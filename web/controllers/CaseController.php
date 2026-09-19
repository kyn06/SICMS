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
require_once __DIR__ . '/../services/FileUploadService.php';
require_once __DIR__ . '/../helpers/Security.php';

class CaseController {
    private $database;
    private $db;
    private $user;
    private $staffRoles = ['super-admin', 'admin', 'sdr staff', 'sdr-staff', 'sdru-staff', 'coordinator', 'reformation-coordinator', 'head-of-sdru', 'sdru-head', 'head of sdru'];
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
            'pendingApproval' => in_array($this->roleKey(), ['super-admin', 'head-of-sdru', 'sdru-head'], true)
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
                $headRoles = ['super-admin', 'head-of-sdru', 'sdru-head'];
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
                    'respondent_gender' => 'gender',
                    'respondent_student_no' => 'student_no', 'respondent_employee_no' => 'employee_no',
                    'respondent_college' => 'college', 'respondent_department' => 'office_department',
                    'respondent_course_year' => 'course_year', 'respondent_position' => 'position',
                    'respondent_affiliation' => 'affiliation', 'respondent_contact' => 'contact_info',
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
                if ($approvalExecution) {
                    if (!$existing) {
                        $newRespondent = [
                            'respondent_type' => $respondentType,
                            'full_name' => $respondentName,
                            'gender' => trim((string) ($_POST['respondent_gender'] ?? '')),
                            'student_no' => trim((string) ($_POST['respondent_student_no'] ?? '')),
                            'employee_no' => trim((string) ($_POST['respondent_employee_no'] ?? '')),
                            'college' => trim((string) ($_POST['respondent_college'] ?? '')),
                            'office_department' => trim((string) ($_POST['respondent_department'] ?? '')),
                            'course_year' => trim((string) ($_POST['respondent_course_year'] ?? '')),
                            'position' => trim((string) ($_POST['respondent_position'] ?? '')),
                            'affiliation' => trim((string) ($_POST['respondent_affiliation'] ?? '')),
                            'contact_info' => trim((string) ($_POST['respondent_contact'] ?? '')),
                            'details' => trim((string) ($_POST['respondent_details'] ?? '')),
                        ];
                        CaseRecord::createRespondent($complaintId, $newRespondent);
                    } else {
                        CaseRecord::updateRespondent($respondentId, $complaintId, $changes);
                    }
                    $_POST['details'] = 'Added the following respondent details: ' . implode(', ', $changedLabels) . '.';
                } else {
                    $_POST['details'] = 'Added the following respondent details: ' . implode(', ', $changedLabels) . '.';
                }
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
                $isHeadRole = in_array($this->roleKey(), ['super-admin', 'head-of-sdru', 'sdru-head'], true);

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
