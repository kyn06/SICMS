<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/AuditLog.php';
require_once __DIR__ . '/../../../routes.php';

if (!isset($_SESSION['email'])) {
    header('Location: web/views/auth/login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

User::setConnection($db);
AuditLog::setConnection($db);

$user = User::findByEmail($_SESSION['email']);

if (!$user || $user['status'] !== 'active') {
    session_unset();
    session_destroy();
    header('Location: web/views/auth/login.php');
    exit;
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$success = null;
$errors = [];
$old = [];
$pwSuccess = null;
$pwErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrfToken();

    $formAction = $_POST['action'] ?? 'profile';

    if ($formAction === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword     = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $hasPassword = !empty($user['password_hash']);

        if ($hasPassword && $currentPassword === '') {
            $pwErrors[] = 'Please enter your current password.';
        }

        if ($newPassword === '') {
            $pwErrors[] = 'Please enter a new password.';
        } elseif (strlen($newPassword) < 8) {
            $pwErrors[] = 'New password must be at least 8 characters.';
        } else {
            if (!preg_match('/[A-Z]/', $newPassword)) {
                $pwErrors[] = 'New password must include at least one uppercase letter.';
            }

            if (!preg_match('/\d/', $newPassword)) {
                $pwErrors[] = 'New password must include at least one number.';
            }

            if (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
                $pwErrors[] = 'New password must include at least one symbol.';
            }
        }

        if ($newPassword !== '' && $newPassword !== $confirmPassword) {
            $pwErrors[] = 'New password and confirmation do not match.';
        }

        if (empty($pwErrors) && $hasPassword && !password_verify($currentPassword, $user['password_hash'])) {
            $pwErrors[] = 'Your current password is incorrect.';
        }

        if (empty($pwErrors) && $hasPassword && password_verify($newPassword, $user['password_hash'])) {
            $pwErrors[] = 'New password must be different from your current password.';
        }

        if (empty($pwErrors)) {
            $currentUser = User::find($user['account_id']);

            if ($currentUser && $currentUser->update([
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'updated_at'    => date('Y-m-d H:i:s'),
            ])) {
                session_regenerate_id(true);
                $pwSuccess = 'Password changed successfully.';
                $user = User::findByEmail($_SESSION['email']);
            } else {
                $pwErrors[] = 'Unable to change password. Please try again.';
            }
        }
    } else {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $phone     = trim($_POST['phone_number'] ?? '');
        $gender    = trim($_POST['gender'] ?? '');
        $address   = trim($_POST['address'] ?? '');

        if ($firstName === '' || $lastName === '') {
            $errors[] = 'First name and last name are required.';
        }

        if ($firstName !== '' && strlen($firstName) > 100) {
            $errors[] = 'First name must be 100 characters or fewer.';
        }

        if ($lastName !== '' && strlen($lastName) > 100) {
            $errors[] = 'Last name must be 100 characters or fewer.';
        }

        if ($phone !== '' && strlen($phone) > 20) {
            $errors[] = 'Phone number must be 20 characters or fewer.';
        }

        if (!empty($errors)) {
            $old = $_POST;
        } else {
            $currentUser = User::find($user['account_id']);
            if ($currentUser) {
                $result = $currentUser->update([
                    'first_name'   => $firstName,
                    'last_name'    => $lastName,
                    'phone_number' => $phone,
                    'gender'       => $gender,
                    'address'      => $address,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

                if ($result) {
                    $success = 'Profile updated successfully.';
                    $user = User::findByEmail($_SESSION['email']);
                } else {
                    $errors[] = 'Unable to update profile. Please try again.';
                    $old = $_POST;
                }
            }
        }
    }
}

$old = $old ?: $user;
$hasPassword = !empty($user['password_hash']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SICMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/sidebar.css">
    <link rel="stylesheet" href="../layout/system.css">
    <link rel="stylesheet" href="../layout/accounts.css">
    <link rel="stylesheet" href="../layout/settings.css">
</head>

<body>
    <div class="dashboard-shell">
        <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        <div class="app-content">
            <?php $pageTitle = 'Settings'; require __DIR__ . '/../layout/topbar.php'; ?>
            <section class="panel settings-panel">
                <div class="panel-heading">
                    <div>
                        <h2 class="panel-heading-title">Account Settings</h2>
                        <p class="panel-heading-description">Manage your personal information</p>
                    </div>
                </div>
                <div class="panel-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= h($success) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <div><?= h($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php" class="settings-form">
                        <?= Security::csrfField() ?>

                        <div class="settings-avatar-section">
                            <div class="settings-avatar">
                                <?= h(strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1))) ?>
                            </div>
                            <div class="settings-avatar-info">
                                <div class="settings-avatar-name"><?= h(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></div>
                                <div class="settings-avatar-email"><?= h($user['email'] ?? '') ?></div>
                                <div class="settings-avatar-role"><?= h(ucwords(str_replace(['-', '_'], ' ', $user['role'] ?? ''))) ?></div>
                            </div>
                        </div>

                        <div class="settings-fields">
                            <div class="settings-field-row">
                                <div class="settings-field">
                                    <label for="first_name">First Name <span class="required">*</span></label>
                                    <input type="text" id="first_name" name="first_name" value="<?= h($old['first_name'] ?? '') ?>" required maxlength="100">
                                </div>
                                <div class="settings-field">
                                    <label for="last_name">Last Name <span class="required">*</span></label>
                                    <input type="text" id="last_name" name="last_name" value="<?= h($old['last_name'] ?? '') ?>" required maxlength="100">
                                </div>
                            </div>

                            <div class="settings-field">
                                <label for="email">Email</label>
                                <input type="email" id="email" value="<?= h($user['email'] ?? '') ?>" disabled>
                                <span class="settings-field-note">Email cannot be changed.</span>
                            </div>

                            <div class="settings-field-row">
                                <div class="settings-field">
                                    <label for="phone_number">Phone Number</label>
                                    <input type="text" id="phone_number" name="phone_number" value="<?= h($old['phone_number'] ?? '') ?>" maxlength="20" placeholder="e.g. 09XXXXXXXXX">
                                </div>
                                <div class="settings-field">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender">
                                        <option value="Male" <?= ($old['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($old['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                    </select>
                                </div>
                            </div>

                            <div class="settings-field">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3" placeholder="Enter your address"><?= h($old['address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="settings-actions">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save Changes</button>
                        </div>
                    </form>

                    <form method="POST" action="index.php" class="settings-form settings-password-form">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="settings-section-header">
                            <h3 class="settings-section-title"><i class="bi bi-shield-lock"></i> Change Password</h3>
                            <p class="settings-section-description">
                                <?= $hasPassword
                                    ? 'Use your current password to set a new one.'
                                    : 'Your account was created with Google. Set a password to also log in with your email.' ?>
                            </p>
                        </div>

                        <?php if ($pwSuccess): ?>
                            <div class="alert alert-success"><?= h($pwSuccess) ?></div>
                        <?php endif; ?>

                        <?php if (!empty($pwErrors)): ?>
                            <div class="alert alert-error">
                                <?php foreach ($pwErrors as $error): ?>
                                    <div><?= h($error) ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="settings-fields">
                            <div class="settings-field">
                                <label for="current_password">Current Password <?= $hasPassword ? '<span class="required">*</span>' : '' ?></label>
                                <div class="password-wrapper">
                                    <input
                                        type="password"
                                        id="current_password"
                                        name="current_password"
                                        autocomplete="current-password"
                                        placeholder="Enter your current password"
                                        <?= $hasPassword ? 'required' : '' ?>
                                    >
                                    <button type="button" class="password-toggle" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="settings-field-row">
                                <div class="settings-field">
                                    <label for="new_password">New Password <span class="required">*</span></label>
                                    <div class="password-wrapper">
                                        <input
                                            type="password"
                                            id="new_password"
                                            name="new_password"
                                            autocomplete="new-password"
                                            placeholder="Min. 8 chars, uppercase, number & symbol"
                                            required
                                            minlength="8"
                                            pattern="(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}"
                                            title="At least 8 characters including an uppercase letter, a number, and a symbol."
                                        >
                                        <button type="button" class="password-toggle" aria-label="Show password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="settings-field">
                                    <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                                    <div class="password-wrapper">
                                        <input
                                            type="password"
                                            id="confirm_password"
                                            name="confirm_password"
                                            autocomplete="new-password"
                                            placeholder="Re-enter your new password"
                                            required
                                            minlength="8"
                                        >
                                        <button type="button" class="password-toggle" aria-label="Show password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-actions">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Confirm</button>
                        </div>
                    </form>
                </div> 
            </section>
        </div>
    </div>

    <script>
        document.querySelectorAll('.password-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = button.parentElement.querySelector('input');
                var isPassword = input.type === 'password';

                input.type = isPassword ? 'text' : 'password';

                button.innerHTML = isPassword
                    ? '<i class="bi bi-eye-slash"></i>'
                    : '<i class="bi bi-eye"></i>';

                button.setAttribute(
                    'aria-label',
                    isPassword ? 'Hide password' : 'Show password'
                );
            });
        });
    </script>
</body>

</html>
