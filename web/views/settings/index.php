<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/AuditLog.php';
require_once __DIR__ . '/../../../routes.php';
require_once __DIR__ . '/../../services/GoogleCalendarService.php';
require_once __DIR__ . '/../../helpers/ProfileCompletion.php';
require_once __DIR__ . '/../../helpers/Colleges.php';
require_once __DIR__ . '/../../helpers/Courses.php';

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

$isStudent = ProfileCompletion::isStudentAccount($user);

$success = null;
$errors = [];
$old = [];
$pwSuccess = null;
$pwErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrfToken();

    $formAction = $_POST['action'] ?? 'profile';

    if ($formAction === 'disconnect_calendar') {
        $service = GoogleCalendarService::instance($db);
        $service->disconnect();
        $calSuccess = 'Google Calendar disconnected. Hearing events will no longer be synced.';
    } else if ($formAction === 'change_password') {
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
        $studentNumber = trim($_POST['student_number'] ?? '');
        $college   = trim($_POST['college'] ?? '');
        $course    = trim($_POST['course'] ?? '');
        $section   = trim($_POST['section'] ?? '');

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

        if ($isStudent) {
            if ($studentNumber === '') {
                $errors[] = 'Student number is required.';
            } elseif (strlen($studentNumber) > 50) {
                $errors[] = 'Student number must be 50 characters or fewer.';
            }

            if ($college === '') {
                $errors[] = 'College is required.';
            } elseif (!Colleges::contains($college)) {
                $errors[] = 'Please select a valid college.';
            }

            if ($course === '') {
                $errors[] = 'Course is required.';
            } elseif (!in_array($course, Courses::all(), true)) {
                $errors[] = 'Please select a valid course.';
            }

            $validSections = array_merge(...array_values(Courses::sections()));
            if ($section === '') {
                $errors[] = 'Section is required.';
            } elseif (!in_array($section, $validSections, true)) {
                $errors[] = 'Please select a valid section.';
            }
        }

        if (!empty($errors)) {
            $old = $_POST;
        } else {
            $currentUser = User::find($user['account_id']);
            if ($currentUser) {
                $updateData = [
                    'first_name'   => $firstName,
                    'last_name'    => $lastName,
                    'phone_number' => $phone,
                    'gender'       => $gender,
                    'address'      => $address,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ];

                if ($isStudent) {
                    $updateData['student_number'] = $studentNumber;
                    $updateData['college']        = $college;
                    $updateData['course']         = $course;
                    $updateData['section']        = $section;
                }

                $result = $currentUser->update($updateData);

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

$calSuccess = $calSuccess ?? null;
$calendarService = GoogleCalendarService::instance($db);
$calConnected = $calendarService->isConnected();
$calEmail = $calendarService->connectedEmail();
$calMessage = $_SESSION['cal_message'] ?? null;
$calError = $_SESSION['cal_error'] ?? null;
unset($_SESSION['cal_message'], $_SESSION['cal_error']);
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
    <link rel="stylesheet" href="../layout/system.css?v=2">
    <link rel="stylesheet" href="../layout/accounts.css?v=2">
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

                    <div class="settings-group">
                        <div class="settings-group-header">
                            <h3 class="settings-group-title"><i class="bi bi-person"></i> Personal Information</h3>
                            <p class="settings-group-desc">Update your personal details and contact information.</p>
                        </div>
                    <form method="POST" action="<?= h(app_url('web/views/settings/index.php')) ?>" class="settings-form">
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

                        <?php if ($isStudent): ?>
                            <div class="settings-section-header">
                                <h3 class="settings-section-title"><i class="bi bi-mortarboard"></i> Student Information</h3>
                                <p class="settings-section-description">
                                    These details are required so you can submit and track your complaints.
                                </p>
                            </div>
                            <div class="settings-fields">
                                <div class="settings-field">
                                    <label for="student_number">Student Number <span class="required">*</span></label>
                                    <input type="text" id="student_number" name="student_number" value="<?= h($old['student_number'] ?? '') ?>" maxlength="50" placeholder="e.g. 12-3456">
                                </div>
                                <div class="settings-field-row">
                                    <div class="settings-field">
                                        <label for="college">College <span class="required">*</span></label>
                                        <select id="college" name="college">
                                            <option value="">Select your college</option>
                                            <?php foreach (Colleges::all() as $collegeOption): ?>
                                                <option value="<?= h($collegeOption) ?>" <?= ($old['college'] ?? '') === $collegeOption ? 'selected' : '' ?>>
                                                    <?= h($collegeOption) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="settings-field">
                                        <label for="course">Course <span class="required">*</span></label>
                                        <select id="course" name="course">
                                            <option value="">Select your course</option>
                                            <?php foreach (Courses::all() as $courseOption): ?>
                                                <option value="<?= h($courseOption) ?>" <?= ($old['course'] ?? '') === $courseOption ? 'selected' : '' ?>>
                                                    <?= h($courseOption) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="settings-field">
                                    <label for="section">Section <span class="required">*</span></label>
                                    <select id="section" name="section">
                                        <option value="">Select your section</option>
                                        <?php foreach (Courses::sections() as $yearLabel => $sections): ?>
                                            <optgroup label="<?= h($yearLabel) ?>">
                                                <?php foreach ($sections as $sectionOption): ?>
                                                    <option value="<?= h($sectionOption) ?>" <?= ($old['section'] ?? '') === $sectionOption ? 'selected' : '' ?>>
                                                        <?= h($sectionOption) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="settings-actions">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save Changes</button>
                        </div>
                    </form>
                    </div>

                    <div class="settings-group">
                        <div class="settings-group-header">
                            <h3 class="settings-group-title"><i class="bi bi-shield-lock"></i> Change Password</h3>
                            <p class="settings-group-desc">
                                <?= $hasPassword
                                    ? 'Use your current password to set a new one.'
                                    : 'Your account was created with Google. Set a password to also log in with your email.' ?>
                            </p>
                        </div>
                    <?php if (!$hasPassword && $user['auth_provider'] === 'google'): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-key"></i>
                            Set a password below so you can also log in with your email and password. Your Google sign-in will keep working.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= h(app_url('web/views/settings/index.php')) ?>" class="settings-form settings-password-form">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="change_password">

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
                            <?php if ($hasPassword): ?>
                            <div class="settings-field">
                                <label for="current_password">Current Password <span class="required">*</span></label>
                                <div class="password-wrapper">
                                    <input
                                        type="password"
                                        id="current_password"
                                        name="current_password"
                                        autocomplete="current-password"
                                        placeholder="Enter your current password"
                                        required
                                    >
                                    <button type="button" class="password-toggle" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="settings-field-row">
                                <div class="settings-field">
                                    <label for="new_password"><?= $hasPassword ? 'New Password' : 'Add New Password' ?> <span class="required">*</span></label>
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

                    <?php if (!$isStudent): ?>
                    <div class="settings-group">
                        <div class="settings-group-header">
                            <h3 class="settings-group-title"><i class="bi bi-plug"></i> Integrations &amp; Data</h3>
                            <p class="settings-group-desc">Connect your Google Calendar so hearings are scheduled automatically, and access archived case records.</p>
                        </div>
                        <form method="POST" action="<?= h(app_url('web/views/settings/index.php')) ?>" class="settings-form settings-password-form settings-group-form" data-confirm="<?= $calConnected ? 'Disconnect Google Calendar? Existing calendar events will not be removed.' : '' ?>">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="disconnect_calendar">

                        <div class="settings-section-header settings-subsection">
                            <h3 class="settings-section-title"><i class="bi bi-google"></i> Google Calendar</h3>
                            <p class="settings-section-description">
                                Connect the office Google Calendar so scheduled hearings are created automatically.
                            </p>
                        </div>

                        <?php if ($calMessage): ?>
                            <div class="alert alert-success"><?= h($calMessage) ?></div>
                        <?php endif; ?>

                        <?php if ($calError): ?>
                            <div class="alert alert-error">
                                <?php foreach ((array) $calError as $calErrLine): ?>
                                    <div><?= h($calErrLine) ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($calSuccess): ?>
                            <div class="alert alert-success"><?= h($calSuccess) ?></div>
                        <?php endif; ?>

                        <div class="settings-fields">
                            <div class="settings-cal-connect">
                                <div class="settings-cal-info">
                                    <div class="settings-cal-state">
                                        <?php if ($calConnected): ?>
                                            <span class="cal-status-dot connected"></span> Connected as <strong><?= h($calEmail ?: 'Google account') ?></strong>
                                        <?php else: ?>
                                            <span class="cal-status-dot"></span> Not connected
                                        <?php endif; ?>
                                    </div>
                                    <span class="settings-field-note">
                                        <?php if ($calConnected): ?>
                                            New scheduled hearings are added to this calendar; cancelling a hearing removes its event.
                                        <?php else: ?>
                                            New hearings will keep working as before. Once connected, scheduled hearings are added to your calendar automatically.
                                            Need the exact redirect URI? <a href="../auth/google_connect_calendar.php?diag=1" target="_blank" rel="noopener">Open diagnostics</a>.
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if ($calConnected): ?>
                                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Disconnect</button>
                                <?php else: ?>
                                    <a class="btn btn-primary" href="../auth/google_connect_calendar.php"><i class="bi bi-google"></i> Connect Google Calendar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                <div class="settings-section-header settings-subsection settings-archive-section">
                    <h3 class="settings-section-title"><i class="bi bi-archive-fill"></i> Archived Cases</h3>
                    <p class="settings-section-description">
                        View cases that have been archived. Archived cases are still counted in statistics and reports.
                    </p>
                    <div class="settings-archive-action">
                        <a class="btn btn-primary" href="<?= h(app_route('archived_cases.index')) ?>"><i class="bi bi-archive-fill"></i> Open Archived Cases</a>
                    </div>
                </div>
                </div>
                <?php endif; ?>
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
