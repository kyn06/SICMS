<?php

class Security {
    public static function startSession(array $options = []) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::ensureCsrfToken();
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $defaults = [
            'cookie_lifetime' => 86400,
            'cookie_httponly' => true,
            'cookie_secure' => $isHttps,
            'cookie_samesite' => 'Lax',
        ];

        session_start(array_merge($defaults, $options));
        self::ensureCsrfToken();
    }

    public static function ensureCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function csrfToken() {
        self::ensureCsrfToken();
        return $_SESSION['csrf_token'];
    }

    public static function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validateCsrfToken($token) {
        return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function requireCsrfToken() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        if (!self::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $isAjax = strtolower(trim((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''))) === 'xmlhttprequest'
                || strpos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;

            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => 'Your session expired or this page is outdated. Please refresh the page and try again.']);
            } else {
                http_response_code(403);
                echo 'Invalid or missing CSRF token.';
            }
            exit;
        }
    }

    public static function normalizeRole($role) {
        return strtolower(str_replace(['_', ' '], '-', (string) $role));
    }

    public static function loginAttemptKey($email) {
        $email = strtolower(trim((string) $email));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        return hash('sha256', $email . '|' . $ip);
    }

    public static function isLoginLocked($email) {
        $key = self::loginAttemptKey($email);
        $attempt = $_SESSION['login_attempts'][$key] ?? null;

        return $attempt && !empty($attempt['locked_until']) && time() < (int) $attempt['locked_until'];
    }

    public static function loginLockRemaining($email) {
        $key = self::loginAttemptKey($email);
        $attempt = $_SESSION['login_attempts'][$key] ?? null;

        if (!$attempt || empty($attempt['locked_until'])) {
            return 0;
        }

        return max(0, (int) $attempt['locked_until'] - time());
    }

    public static function recordFailedLogin($email) {
        $key = self::loginAttemptKey($email);
        $attempt = $_SESSION['login_attempts'][$key] ?? [
            'count' => 0,
            'locked_until' => 0,
        ];

        if (!empty($attempt['locked_until']) && time() >= (int) $attempt['locked_until']) {
            $attempt = ['count' => 0, 'locked_until' => 0];
        }

        $attempt['count']++;

        if ($attempt['count'] >= 5) {
            $attempt['locked_until'] = time() + (15 * 60);
        }

        $_SESSION['login_attempts'][$key] = $attempt;
    }

    public static function clearLoginAttempts($email) {
        $key = self::loginAttemptKey($email);
        unset($_SESSION['login_attempts'][$key]);
    }

    public static function strongPasswordErrors($password) {
        $errors = [];

        if (strlen((string) $password) < 12) {
            $errors[] = 'Password must be at least 12 characters.';
        }

        if (!preg_match('/[A-Z]/', (string) $password)) {
            $errors[] = 'Password must include an uppercase letter.';
        }

        if (!preg_match('/[a-z]/', (string) $password)) {
            $errors[] = 'Password must include a lowercase letter.';
        }

        if (!preg_match('/\d/', (string) $password)) {
            $errors[] = 'Password must include a number.';
        }

        if (!preg_match('/[^A-Za-z0-9]/', (string) $password)) {
            $errors[] = 'Password must include a symbol.';
        }

        return $errors;
    }
}
