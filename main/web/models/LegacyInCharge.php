<?php

require_once __DIR__ . '/Model.php';

class LegacyInCharge extends Model {
    protected static $table = 'legacy_incharges';
    protected static $primaryKey = 'legacy_id';

    public static function orderedAll() {
        $sql = "SELECT * FROM " . static::$table . " ORDER BY legacy_id DESC";
        $stmt = mysqli_prepare(self::$conn, $sql);

        if (!$stmt) {
            return [];
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    public static function validate(array $input): array {
        $errors = [];

        $fullName = trim((string) ($input['full_name'] ?? ''));
        $position = trim((string) ($input['position'] ?? ''));

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        } elseif (mb_strlen($fullName) > 150) {
            $errors[] = 'Full name must not exceed 150 characters.';
        }

        if ($position === '') {
            $errors[] = 'Position is required.';
        } elseif (mb_strlen($position) > 100) {
            $errors[] = 'Position must not exceed 100 characters.';
        }

        if (mb_strlen(trim((string) ($input['tenure'] ?? ''))) > 50) {
            $errors[] = 'Tenure must not exceed 50 characters.';
        }

        return $errors;
    }
}
