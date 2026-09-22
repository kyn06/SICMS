<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../models/CounterStatement.php';
require_once __DIR__ . '/../models/Hearing.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../services/FileUploadService.php';
require_once __DIR__ . '/../helpers/Security.php';

class RespondentController {
    private $database;
    private $db;
    private $user;

    public function __construct() {
        Security::startSession();

        if (!isset($_SESSION['email'])) {
            header('Location: ../auth/login.php');
            exit;
        }

        $this->database = new Database();
        $this->db = $this->database->getConnection();

        User::setConnection($this->db);
        CaseRecord::setConnection($this->db);
        CounterStatement::setConnection($this->db);
        Hearing::setConnection($this->db);
        Notification::setConnection($this->db);
        AuditLog::setConnection($this->db);

        $this->user = User::findByEmail($_SESSION['email']);

        if (!$this->user || $this->user['status'] !== 'active') {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }
    }

    public function cases() {
        if (!$this->isRespondent()) {
            $this->deny();
        }

        $caseRows = CaseRecord::casesForRespondent((int) $this->user['account_id']);
        $cases = [];

        foreach ($caseRows as $caseRow) {
            $statement = CounterStatement::forRespondentCase(
                (int) $caseRow['complaint_id'],
                (int) $caseRow['respondent_id']
            );
            $cases[] = [
                'complaint_id' => (int) $caseRow['complaint_id'],
                'case_number' => $caseRow['case_number'],
                'case_classification' => $caseRow['case_classification'],
                'status' => $caseRow['status'],
                'submitted_at' => $caseRow['submitted_at'],
                'respondent_id' => (int) $caseRow['respondent_id'],
                'is_released' => !empty($caseRow['respondent_released_at']),
                'statement_status' => $statement ? $statement['status'] : null,
                'statement_submitted_at' => $statement ? $statement['submitted_at'] : null,
            ];
        }

        return [
            'user' => $this->user,
            'cases' => $cases,
        ];
    }

    public function caseShow($complaintId) {
        $complaintId = (int) $complaintId;

        if (!$this->isRespondent() && !CaseRecord::isAccountRespondentForCase($complaintId, (int) $this->user['account_id'])) {
            $this->deny();
        }
        $case = CaseRecord::findCase($complaintId);

        if (!$case || ($case['case_source'] ?? '') === 'Legacy'
            || !CaseRecord::isAccountRespondentForCase($complaintId, (int) $this->user['account_id'])) {
            $this->deny();
        }

        if (!CaseRecord::respondentReleased($complaintId)) {
            $this->deny();
        }

        $link = CaseRecord::respondentLink($complaintId, (int) $this->user['account_id']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->handleAction($complaintId, $link);
        }

        $caseActive = in_array($case['status'], ['Under Investigation', 'Returned for Revision'], true);
        $statement = CounterStatement::forRespondentCase($complaintId, (int) $link['respondent_id']);

        if ($caseActive && (!$statement || $statement['status'] !== 'Submitted')) {
            $statement = CounterStatement::ensureDraft($complaintId, (int) $link['respondent_id'], (int) $this->user['account_id']);
        }

        $attachments = $statement
            ? CounterStatement::attachments((int) $statement['counter_statement_id'])
            : [];

        return [
            'user' => $this->user,
            'case' => $case,
            'link' => $link,
            'visibility' => CaseRecord::respondentVisibility($complaintId),
            'statement' => $statement,
            'attachments' => $attachments,
            'counterErrors' => $_SESSION['counter_errors'] ?? [],
            'counterInfo' => $_SESSION['counter_info'] ?? [],
            'hearings' => Hearing::forComplaint($complaintId),
            'timeline' => CaseRecord::respondentHistory($complaintId, (int) $this->user['account_id']),
            'caseActive' => $caseActive,
        ];
    }

    private function handleAction($complaintId, array $link) {
        $respondentId = (int) $link['respondent_id'];
        $accountId = (int) $this->user['account_id'];
        $action = $_POST['case_action'] ?? '';
        $case = CaseRecord::findCase($complaintId);
        $caseActive = $case && in_array($case['status'], ['Under Investigation', 'Returned for Revision'], true);
        $caseLabel = $case['case_number'] ?? ('Case #' . $complaintId);

        try {
            if (!$caseActive) {
                $_SESSION['counter_errors'] = ['This case is closed. Counter-statements can no longer be edited.'];
                header('Location: case_show.php?id=' . $complaintId);
                exit;
            }

            $statement = CounterStatement::forRespondentCase($complaintId, $respondentId);

            if ($action === 'save_draft' || $action === 'submit_counter_statement') {
                if (!$statement || $statement['status'] !== 'Draft') {
                    $_SESSION['counter_errors'] = ['There is no editable counter-statement draft on this case.'];
                    header('Location: case_show.php?id=' . $complaintId);
                    exit;
                }

                $content = trim((string) ($_POST['statement_content'] ?? ''));
                if ($content === '') {
                    $_SESSION['counter_errors'] = ['Please write your counter-statement before saving.'];
                    header('Location: case_show.php?id=' . $complaintId);
                    exit;
                }

                if ($action === 'save_draft') {
                    CounterStatement::saveDraft((int) $statement['counter_statement_id'], $accountId, $content);
                    AuditLog::record($this->user, 'Counter-Statement Draft', 'Saved a draft counter-statement on ' . $caseLabel . '.');
                    $_SESSION['counter_errors'] = [];
                    $_SESSION['counter_info'] = 'Draft counter-statement saved.';
                } else {
                    CounterStatement::saveDraft((int) $statement['counter_statement_id'], $accountId, $content);
                    $submitted = CounterStatement::submit((int) $statement['counter_statement_id'], $accountId);
                    if (!$submitted) {
                        $_SESSION['counter_errors'] = ['Your counter-statement could not be submitted. Please try again.'];
                        header('Location: case_show.php?id=' . $complaintId);
                        exit;
                    }
                    CaseRecord::recordCaseActivity(
                        $complaintId,
                        'Counter-Statement Submitted',
                        'Respondent submitted their counter-statement for case ' . $caseLabel . '.',
                        $accountId
                    );
                    $assignedCoordinatorId = (int) ($case['assigned_coordinator_account_id'] ?? 0);
                    if ($assignedCoordinatorId > 0) {
                        Notification::createForUser(
                            $assignedCoordinatorId,
                            'counter_statement_submitted',
                            'Counter-Statement Submitted',
                            'A counter-statement was submitted for ' . $caseLabel . '.',
                            'web/views/cases/show.php?id=' . $complaintId
                        );
                    }
                    Notification::createForHeads(
                        'counter_statement_submitted',
                        'Counter-Statement Submitted',
                        'A counter-statement was submitted for ' . $caseLabel . '.',
                        'web/views/cases/show.php?id=' . $complaintId
                    );
                    AuditLog::record($this->user, 'Counter-Statement Submitted', 'Submitted the counter-statement on ' . $caseLabel . '.');
                    $_SESSION['counter_errors'] = [];
                    $_SESSION['counter_info'] = 'Counter-Statement Submitted. Your counter-statement for ' . $caseLabel . ' was submitted for review on ' . date('M d, Y h:i A') . '. Current status: ' . ($case['status'] ?? 'Under Review') . '.';
                }
            } elseif ($action === 'upload_counter_evidence') {
                if (!$statement || $statement['status'] !== 'Draft') {
                    $_SESSION['counter_errors'] = ['Evidence can only be attached while the counter-statement is a draft.'];
                    header('Location: case_show.php?id=' . $complaintId);
                    exit;
                }

                $validationErrors = FileUploadService::validateFiles($_FILES['counter_evidence'] ?? []);
                $hasAttachments = !empty(array_values(array_filter(($_FILES['counter_evidence']['name'] ?? []), fn($name) => $name !== '')));

                if (!empty($validationErrors)) {
                    $_SESSION['counter_errors'] = ['Unable to attach evidence: ' . implode(' ', $validationErrors)];
                    header('Location: case_show.php?id=' . $complaintId);
                    exit;
                }

                if (!$hasAttachments) {
                    $_SESSION['counter_errors'] = ['Please choose at least one file to attach.'];
                    header('Location: case_show.php?id=' . $complaintId);
                    exit;
                }

                $savedFiles = FileUploadService::saveToEvidence($_FILES['counter_evidence']);
                foreach ($savedFiles as $savedFile) {
                    CounterStatement::addAttachment($complaintId, (int) $statement['counter_statement_id'], $savedFile);
                }
                AuditLog::record($this->user, 'Counter-Statement Evidence', 'Attached ' . count($savedFiles) . ' supporting file(s) to the counter-statement on ' . $caseLabel . '.');
                $_SESSION['counter_errors'] = [];
                $_SESSION['counter_info'] = 'Supporting evidence attached to your counter-statement.';
            } elseif ($action === 'remove_counter_evidence') {
                $evidenceId = (int) ($_POST['evidence_id'] ?? 0);
                if (!$statement || $evidenceId <= 0) {
                    $_SESSION['counter_errors'] = ['Please select valid evidence to remove.'];
                    header('Location: case_show.php?id=' . $complaintId);
                    exit;
                }
                if (CounterStatement::removeAttachment($evidenceId, (int) $statement['counter_statement_id'], $accountId)) {
                    AuditLog::record($this->user, 'Counter-Statement Evidence', 'Removed a supporting file from the counter-statement on ' . $caseLabel . '.');
                    $_SESSION['counter_errors'] = [];
                    $_SESSION['counter_info'] = 'Supporting evidence removed.';
                } else {
                    $_SESSION['counter_errors'] = ['That file can no longer be removed.'];
                }
            } else {
                $_SESSION['counter_errors'] = ['Invalid action.'];
            }
        } catch (Throwable $exception) {
            $_SESSION['counter_errors'] = ['Unable to update your counter-statement. Please try again.'];
        }

        header('Location: case_show.php?id=' . $complaintId);
        exit;
    }

    public function clearFlash() {
        unset($_SESSION['counter_errors'], $_SESSION['counter_info']);
    }

    private function isRespondent() {
        return isset($this->user['role'])
            && strtolower(str_replace(['_', ' '], '-', (string) $this->user['role'])) === 'respondent';
    }

    private function deny() {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
}
