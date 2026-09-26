<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

function activate_base_path() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    return rtrim(preg_replace('#/web/views/auth/activate\.php$#', '', $script), '/');
}

if (isset($_SESSION['email'])) {
    header('Location: ' . activate_base_path() . '/index.php');
    exit;
}

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Case.php';
require_once __DIR__ . '/../../models/Notification.php';
require_once __DIR__ . '/../../models/AuditLog.php';

$database = new Database();
$db = $database->getConnection();
User::setConnection($db);
CaseRecord::setConnection($db);
Notification::setConnection($db);
AuditLog::setConnection($db);

$token = trim((string) ($_GET['token'] ?? ($_POST['invitation_token'] ?? '')));

function find_invitation($db, $token) {
    $sql = "SELECT r.respondent_id, r.complaint_id, r.full_name, r.invitation_token, r.invited_at,
                   a.account_id, a.email, a.first_name, a.last_name, a.status AS account_status,
                   a.role AS account_role, c.case_number, c.case_source
            FROM complaint_respondents r
            INNER JOIN complaints c ON c.complaint_id = r.complaint_id
            INNER JOIN accounts a ON a.account_id = r.account_id
            WHERE r.invitation_token = ? AND a.role = 'student'
              AND (c.case_source IS NULL OR c.case_source <> 'Legacy')
            LIMIT 1";
    $stmt = $db->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    return $rows[0] ?? null;
}

$errorMessage = $_SESSION['activate_error'] ?? null;
$successMessage = $_SESSION['activate_success'] ?? null;
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$invitation = $token !== '' ? find_invitation($db, $token) : null;

if ($invitation && CaseRecord::invitationIsExpired((string) ($invitation['invited_at'] ?? ''))) {
    $_SESSION['activate_error'] = 'This activation link has expired. Please contact the SDRU office to have a new link sent.';
    $invitation = null;
}

if ($requestMethod !== 'POST') {
    unset($_SESSION['activate_error'], $_SESSION['activate_success']);
    session_write_close();
}

if ($requestMethod === 'POST') {
    Security::requireCsrfToken();

    if (!$token || !$invitation) {
        $_SESSION['activate_error'] = 'This activation link is invalid or has expired. Please contact the SDRU office to have a new link sent.';
        header('Location: activate.php');
        exit;
    }

    if (($invitation['account_status'] ?? '') === 'active') {
        $_SESSION['activate_error'] = 'This activation link has already been used. You can log in with your password.';
        header('Location: ../login.php');
        exit;
    }

    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if ($password !== $confirm) {
        $_SESSION['activate_error'] = 'Passwords do not match.';
        header('Location: activate.php?token=' . urlencode($token));
        exit;
    }

    $passwordErrors = Security::strongPasswordErrors($password);
    if ($passwordErrors) {
        $_SESSION['activate_error'] = implode(' ', $passwordErrors);
        header('Location: activate.php?token=' . urlencode($token));
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');

    $stmt = $db->prepare("UPDATE accounts SET password_hash = ?, status = 'active', updated_at = ? WHERE account_id = ? AND role = 'student'");
    $stmt->bind_param("ssi", $hash, $now, $invitation['account_id']);
    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        $_SESSION['activate_error'] = 'Could not activate your account. Please try again.';
        header('Location: activate.php?token=' . urlencode($token));
        exit;
    }

    $clearStmt = $db->prepare("UPDATE complaint_respondents SET invitation_token = NULL WHERE respondent_id = ? AND complaint_id = ?");
    $clearStmt->bind_param('ii', $invitation['respondent_id'], $invitation['complaint_id']);
    $clearStmt->execute();

    CaseRecord::recordCaseActivity(
        (int) $invitation['complaint_id'],
        'Respondent Account Activated',
        'Respondent ' . $invitation['full_name'] . ' activated their portal account via the invitation link.',
        (int) $invitation['account_id']
    );

    AuditLog::record(
        [
            'account_id' => (int) $invitation['account_id'],
            'email' => $invitation['email'],
            'first_name' => $invitation['first_name'],
            'last_name' => $invitation['last_name'],
            'role' => 'respondent',
        ],
        'Account Activation',
        'Respondent activated their account for case ' . $invitation['case_number'] . ' via invitation link.'
    );

    $_SESSION['activate_success'] = 'Your account has been activated. You can now log in with the password you set.';
    session_regenerate_id(true);
    header('Location: activate.php?success=1');
    exit;
}

if (isset($_GET['success'])) {
    $successMessage = $_SESSION['activate_success'] ?? 'Your account has been activated. You can now log in.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Your Respondent Account</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/style.css">
</head>

<body>
    <img class="seal" src="../../../public/assets/clsulogo.png" alt="clsu logo">
    <p class="org-name">Office of Student Affairs - Student<br>Discipline and Reformation Unit</p>

    <?php if ($successMessage): ?>
        <div class="form-wrap">
            <div class="alert-success">
                <?= htmlspecialchars($successMessage) ?>
            </div>
            <a class="btn-login" href="login.php">Proceed to Login</a>
        </div>
    <?php elseif ($invitation): ?>
        <form class="form-wrap" action="activate.php" method="POST">
            <?= Security::csrfField() ?>
            <input type="hidden" name="invitation_token" value="<?= htmlspecialchars($token) ?>">

            <h2 style="margin:0 0 4px;color:#123c1b">Activate Your Respondent Account</h2>
            <p class="muted" style="margin:0 0 18px">Case <?= htmlspecialchars($invitation['case_number']) ?><br>
                Hello, <?= htmlspecialchars(trim(($invitation['first_name'] ?? '') . ' ' . ($invitation['last_name'] ?? ''))) ?> — set a password to activate your account.</p>

            <?php if ($errorMessage): ?>
                <p class="error-msg"><?= htmlspecialchars($errorMessage) ?></p>
            <?php endif; ?>

            <div class="password-wrapper">
                <input type="password" class="pill-input" id="password" name="password" placeholder="New password (12+ characters)" required>
                <button type="button" class="password-toggle" id="toggle1" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>

            <div class="password-wrapper">
                <input type="password" class="pill-input" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                <button type="button" class="password-toggle" id="toggle2" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>

            <button type="submit" class="btn-login">Activate Account</button>
        </form>
    <?php else: ?>
        <div class="form-wrap">
            <?php if ($errorMessage): ?>
                <p class="error-msg"><?= htmlspecialchars($errorMessage) ?></p>
            <?php else: ?>
                <p class="error-msg">This activation link is invalid or has expired. Please contact the SDRU office to have a new link sent.</p>
            <?php endif; ?>
            <a class="create-link" href="login.php">Return to Login</a>
        </div>
    <?php endif; ?>

    <script>
        function wireToggle(inputId, toggleId) {
            var input = document.getElementById(inputId);
            var btn = document.getElementById(toggleId);
            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            });
        }
        wireToggle('password', 'toggle1');
        wireToggle('confirm_password', 'toggle2');
    </script>
</body>

</html>
