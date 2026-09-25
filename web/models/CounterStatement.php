<?php

require_once 'Model.php';
require_once 'Case.php';
require_once 'AuditLog.php';

/*
 * Counter statements submitted by respondents on a case.
 *
 * Access model:
 *  - A statement belongs to exactly one respondent (complaint_respondents.respondent_id)
 *    and one respondent account (respondent_account_id).
 *  - `status` is 'Draft' (editable) or 'Submitted' (locked). A coordinator may
 *    "return" a submitted statement for revision, flipping it back to 'Draft'.
 */
class CounterStatement extends Model {
    protected static $table = 'counter_statements';

    private static $draftVersionReady = false;

    public static function ensureDraftVersionColumn() {
        if (self::$draftVersionReady) return true;
        $result = self::$conn->query("SHOW COLUMNS FROM counter_statements LIKE 'draft_version'");
        if ($result && $result->num_rows > 0) {
            self::$draftVersionReady = true;
            return true;
        }
        $ok = self::$conn->query("ALTER TABLE counter_statements ADD COLUMN draft_version INT UNSIGNED NOT NULL DEFAULT 0 AFTER content");
        self::$draftVersionReady = (bool) $ok;
        return self::$draftVersionReady;
    }
    protected static $primaryKey = 'counter_statement_id';

    /* Full statement incl. complainant/case auth columns for access checks. */
    public static function findOwn($counterStatementId, $accountId) {
        $sql = "SELECT cs.*, r.full_name AS respondent_full_name, r.complaint_id,
                       c.case_number, c.submitted_by_account_id,
                       c.assigned_coordinator_account_id,
                       c.assigned_reformation_coordinator_account_id,
                       c.case_source
                FROM counter_statements cs
                INNER JOIN complaint_respondents r ON r.respondent_id = cs.respondent_id
                INNER JOIN complaints c ON c.complaint_id = cs.complaint_id
                WHERE cs.counter_statement_id = ? AND cs.respondent_account_id = ?
                LIMIT 1";
        $stmt = self::$conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param('ii', $counterStatementId, $accountId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        return $rows[0] ?? null;
    }

    /* Latest statement for a respondent's link to a case (or null). */
    public static function forRespondentCase($complaintId, $respondentId) {
        $sql = "SELECT cs.*, r.full_name AS respondent_full_name
                FROM counter_statements cs
                INNER JOIN complaint_respondents r ON r.respondent_id = cs.respondent_id
                WHERE cs.complaint_id = ? AND cs.respondent_id = ?
                ORDER BY cs.counter_statement_id DESC
                LIMIT 1";
        $stmt = self::$conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param('ii', $complaintId, $respondentId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        return $rows[0] ?? null;
    }

    /* Every statement on a case (staff view), newest first. */
    public static function forCase($complaintId) {
        $sql = "SELECT cs.*, r.full_name AS respondent_full_name,
                       acct.first_name AS account_first_name, acct.last_name AS account_last_name
                FROM counter_statements cs
                INNER JOIN complaint_respondents r ON r.respondent_id = cs.respondent_id
                LEFT JOIN accounts acct ON acct.account_id = cs.respondent_account_id
                WHERE cs.complaint_id = ?
                ORDER BY cs.counter_statement_id DESC";
        $stmt = self::$conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('i', $complaintId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    }

    public static function attachments($counterStatementId) {
        $stmt = self::$conn->prepare(
            "SELECT * FROM complaint_evidence WHERE counter_statement_id = ? ORDER BY uploaded_at DESC, evidence_id DESC"
        );
        if (!$stmt) return [];
        $stmt->bind_param('i', $counterStatementId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    }

    public static function addAttachment($complaintId, $counterStatementId, array $file) {
        $stmt = self::$conn->prepare(
            "INSERT INTO complaint_evidence
                (complaint_id, counter_statement_id, original_filename, stored_filename, file_path, mime_type, file_size, uploaded_at, doc_type)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) return false;
        $now = date('Y-m-d H:i:s');
        $docType = strtolower(pathinfo((string) $file['original_filename'], PATHINFO_EXTENSION));
        $stmt->bind_param(
            'iissssiss',
            $complaintId,
            $counterStatementId,
            $file['original_filename'],
            $file['stored_filename'],
            $file['file_path'],
            $file['mime_type'],
            $file['file_size'],
            $now,
            $docType
        );
        return $stmt->execute();
    }

    /* Remove an attachment from a Draft statement owned by the given account. */
    public static function removeAttachment($evidenceId, $counterStatementId, $respondentAccountId) {
        $stmt = self::$conn->prepare(
            "SELECT e.file_path, cs.status
             FROM complaint_evidence e
             INNER JOIN counter_statements cs ON cs.counter_statement_id = e.counter_statement_id
             WHERE e.evidence_id = ? AND e.counter_statement_id = ? AND cs.respondent_account_id = ?
             LIMIT 1"
        );
        if (!$stmt) return false;
        $stmt->bind_param('iii', $evidenceId, $counterStatementId, $respondentAccountId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $row = $rows[0] ?? null;
        if (!$row || $row['status'] !== 'Draft') return false;

        $delStmt = self::$conn->prepare(
            "DELETE FROM complaint_evidence WHERE evidence_id = ? AND counter_statement_id = ?"
        );
        if (!$delStmt) return false;
        $delStmt->bind_param('ii', $evidenceId, $counterStatementId);
        if (!$delStmt->execute() || $delStmt->affected_rows <= 0) return false;

        $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $row['file_path']);
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        return true;
    }

    /* Draft-or-later for a respondent link; creates the row on first access. */
    public static function ensureDraft($complaintId, $respondentId, $respondentAccountId) {
        self::ensureDraftVersionColumn();
        $existing = self::forRespondentCase($complaintId, $respondentId);
        if ($existing && $existing['status'] === 'Draft') {
            return $existing;
        }

        $now = date('Y-m-d H:i:s');

        if ($existing && $existing['status'] !== 'Draft') {
            return null;
        }

        $stmt = self::$conn->prepare(
            "INSERT INTO counter_statements
                (complaint_id, respondent_id, respondent_account_id, content, status, created_at, updated_at)
             VALUES (?, ?, ?, '', 'Draft', ?, ?)"
        );
        if (!$stmt) return null;
        $stmt->bind_param('iiiss', $complaintId, $respondentId, $respondentAccountId, $now, $now);
        if (!$stmt->execute()) return null;

        return self::forRespondentCase($complaintId, $respondentId);
    }

    public static function saveDraft($counterStatementId, $respondentAccountId, $content) {
        $stmt = self::$conn->prepare(
            "UPDATE counter_statements
             SET content = ?, updated_at = ?
             WHERE counter_statement_id = ? AND respondent_account_id = ? AND status = 'Draft'"
        );
        if (!$stmt) return false;
        $updatedAt = date('Y-m-d H:i:s');
        $stmt->bind_param('ssii', $content, $updatedAt, $counterStatementId, $respondentAccountId);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    public static function saveDraftVersioned($counterStatementId, $respondentAccountId, $content, $expectedVersion) {
        if (!self::ensureDraftVersionColumn()) return ['status' => 'error'];
        $stmt = self::$conn->prepare(
            "UPDATE counter_statements
             SET content = ?, draft_version = draft_version + 1, updated_at = NOW()
             WHERE counter_statement_id = ? AND respondent_account_id = ? AND status = 'Draft' AND draft_version = ?"
        );
        if (!$stmt) return ['status' => 'error'];
        $expectedVersion = (int) $expectedVersion;
        $stmt->bind_param('siii', $content, $counterStatementId, $respondentAccountId, $expectedVersion);
        if (!$stmt->execute()) return ['status' => 'error'];
        if ($stmt->affected_rows !== 1) {
            $current = self::find($counterStatementId);
            return ['status' => 'conflict', 'version' => (int) ($current['draft_version'] ?? 0)];
        }
        return ['status' => 'saved', 'version' => $expectedVersion + 1, 'updated_at' => date('Y-m-d H:i:s')];
    }

    public static function submit($counterStatementId, $respondentAccountId) {
        $wantsSubmit = false;
        $stmt = self::$conn->prepare(
            "UPDATE counter_statements
             SET status = 'Submitted', submitted_at = COALESCE(submitted_at, ?), updated_at = ?
             WHERE counter_statement_id = ? AND respondent_account_id = ? AND status = 'Draft'"
        );
        if (!$stmt) return false;
        $now = date('Y-m-d H:i:s');
        $stmt->bind_param('ssii', $now, $now, $counterStatementId, $respondentAccountId);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        if (!$ok) return false;

        return self::find($counterStatementId);
    }

    /* Staff action: send a submitted statement back to Draft for revision. */
    public static function returnForRevision($counterStatementId, $respondentAccountId) {
        $now = date('Y-m-d H:i:s');
        $stmt = self::$conn->prepare(
            "UPDATE counter_statements
             SET status = 'Draft', updated_at = ?
             WHERE counter_statement_id = ? AND status = 'Submitted'"
        );
        if (!$stmt) return false;
        $stmt->bind_param('si', $now, $counterStatementId);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    public static function respondentAccountId($counterStatementId) {
        $stmt = self::$conn->prepare(
            "SELECT respondent_account_id, complaint_id FROM counter_statements WHERE counter_statement_id = ? LIMIT 1"
        );
        if (!$stmt) return null;
        $stmt->bind_param('i', $counterStatementId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        return $rows[0] ?? null;
    }

    /*
     * Coordinator decision on a submitted counter-statement.
     * $action is 'proceed_to_investigation' or 'forwarded_to_complainant'.
     * The decision is recorded once per statement (workflow action, not a case status).
     * A statement forwarded for a response may later be advanced to investigation
     * once the complainant has submitted their response.
     */
    public static function markCoordinatorAction($counterStatementId, $action, $accountId) {
        $now = date('Y-m-d H:i:s');
        $allowed = ($action === 'proceed_to_investigation')
            ? "(coordinator_action IS NULL OR coordinator_action = 'forwarded_to_complainant')"
            : "coordinator_action IS NULL";
        $stmt = self::$conn->prepare(
            "UPDATE counter_statements
             SET coordinator_action = ?,
                 coordinator_action_by_account_id = ?,
                 coordinator_action_at = ?,
                 forwarded_at = CASE WHEN ? = 'forwarded_to_complainant' THEN ? ELSE forwarded_at END,
                 updated_at = ?
             WHERE counter_statement_id = ? AND status = 'Submitted' AND $allowed"
        );
        if (!$stmt) return false;
        $stmt->bind_param('sissssi', $action, $accountId, $now, $action, $now, $now, $counterStatementId);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    /* The statement the coordinator forwarded to the complainant on a case, if any.
     * Remains visible (read-only) to the complainant even after the case is advanced. */
    public static function forwardedForComplaint($complaintId) {
        $stmt = self::$conn->prepare(
            "SELECT cs.*, r.full_name AS respondent_full_name
             FROM counter_statements cs
             INNER JOIN complaint_respondents r ON r.respondent_id = cs.respondent_id
             WHERE cs.complaint_id = ? AND cs.status = 'Submitted'
               AND cs.coordinator_action IN ('forwarded_to_complainant', 'proceed_to_investigation')
               AND cs.forwarded_at IS NOT NULL
             ORDER BY cs.counter_statement_id DESC
             LIMIT 1"
        );
        if (!$stmt) return null;
        $stmt->bind_param('i', $complaintId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        return $rows[0] ?? null;
    }

    /* Save a draft of the complainant's response to the forwarded statement. */
    public static function saveComplaintResponse($counterStatementId, $content) {
        $now = date('Y-m-d H:i:s');
        $stmt = self::$conn->prepare(
            "UPDATE counter_statements
             SET complaint_response_content = ?, complaint_response_updated_at = ?
             WHERE counter_statement_id = ? AND coordinator_action = 'forwarded_to_complainant'
               AND complaint_response_submitted_at IS NULL"
        );
        if (!$stmt) return false;
        $stmt->bind_param('ssi', $content, $now, $counterStatementId);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    /* Submit the complainant's response (locks it; no further edits). */
    public static function submitComplaintResponse($counterStatementId, $content) {
        $now = date('Y-m-d H:i:s');
        $stmt = self::$conn->prepare(
            "UPDATE counter_statements
             SET complaint_response_content = ?,
                 complaint_response_submitted_at = ?,
                 complaint_response_updated_at = ?
             WHERE counter_statement_id = ? AND coordinator_action = 'forwarded_to_complainant'
               AND complaint_response_submitted_at IS NULL"
        );
        if (!$stmt) return false;
        $stmt->bind_param('sssi', $content, $now, $now, $counterStatementId);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }
}
