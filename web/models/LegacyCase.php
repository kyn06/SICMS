<?php

require_once 'Model.php';
require_once 'Case.php';

/*
 * LegacyCase - supporting service for the Legacy Cases module.
 *
 * Digitized/historical cases are stored in the existing `complaints`
 * table (flagged `case_source = 'Legacy'` and dated by
 * `original_case_date`), so this class mostly provides shared config
 * and validation helpers plus thin wrappers over CaseRecord. It does
 * not maintain a duplicate case table.
 */
class LegacyCase extends Model {

    public const SOURCE_ONLINE = 'Online Submission';
    public const SOURCE_LEGACY = 'Legacy';

    // Roles that may VIEW the Legacy Cases module.
    public const VIEW_ROLES = ['sdr-staff', 'sdru-staff', 'admin', 'head-of-sdru', 'coordinator', 'reformation-coordinator'];

    // Roles that may CREATE / EDIT / UPLOAD legacy cases (staff + SDRU head).
    public const EDIT_ROLES = ['sdr-staff', 'sdru-staff', 'head-of-sdru', 'sdru-head'];

    // Roles that may ADD / DIGITIZE new legacy cases (staff only).
    public const ADD_ROLES = ['sdr-staff', 'sdru-staff'];

    public static function statuses(): array {
        return ['Under Investigation', 'Returned for Revision', 'Rejected', 'Resolved', 'Escalated', 'Archived'];
    }

    public static function canView(array $user): bool {
        return in_array(self::roleKey($user['role'] ?? ''), self::VIEW_ROLES, true);
    }

    public static function canEdit(array $user): bool {
        return in_array(self::roleKey($user['role'] ?? ''), self::EDIT_ROLES, true);
    }

    public static function canAdd(array $user): bool {
        return in_array(self::roleKey($user['role'] ?? ''), self::ADD_ROLES, true);
    }

    public static function roleKey($role): string {
        return strtolower(str_replace(['_', ' '], '-', (string) $role));
    }

    public static function listCases(array $filters) {
        return CaseRecord::listLegacyCases($filters);
    }

    public static function find($complaintId) {
        return CaseRecord::findLegacyCase((int) $complaintId);
    }

    public static function classifications() {
        return CaseRecord::legacyClassifications();
    }

    public static function statusCounts(array $filters = []) {
        $sql = "SELECT status, COUNT(*) AS total FROM complaints WHERE case_source = 'Legacy'";
        $params = [];
        $types = '';

        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(COALESCE(original_case_date, submitted_at)) = ?";
            $params[] = (int) $filters['year'];
            $types .= 'i';
        }

        $sql .= " GROUP BY status";
        $stmt = self::$conn->prepare($sql);
        if (!$stmt) return [];

        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
