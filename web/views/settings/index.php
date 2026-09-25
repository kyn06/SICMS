<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/AuditLog.php';
require_once __DIR__ . '/../../models/LoginSession.php';
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
LoginSession::setConnection($db);

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

function session_location($ipAddress) {
    $ipAddress = trim((string) $ipAddress);
    return in_array($ipAddress, ['', '::1', '127.0.0.1'], true) ? 'Localhost (this computer)' : $ipAddress;
}

function field_error_html($fieldErrors, $field) {
    $messages = $fieldErrors[$field] ?? [];
    if (is_array($messages)) {
        if (!$messages) return '';
        $items = array_map(fn($m) => htmlspecialchars((string) $m, ENT_QUOTES, 'UTF-8'), $messages);
        return '<div class="field-error" role="alert">' . implode('<br>', $items) . '</div>';
    }
    return $messages !== '' && $messages !== null
        ? '<div class="field-error" role="alert">' . htmlspecialchars((string) $messages, ENT_QUOTES, 'UTF-8') . '</div>'
        : '';
}

$isStudent = ProfileCompletion::isStudentAccount($user);
$roleKey = strtolower(str_replace(['_', ' '], '-', (string) ($user['role'] ?? '')));
$staffRoles = ['admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'reformation-coordinator', 'head-of-sdru', 'sdru-head'];
$isStaffView = in_array($roleKey, $staffRoles, true);

$success = null;
$errors = [];
$fieldErrors = [];
$pwErrors = [];
$pwFieldErrors = [];
$old = [];
$pwSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrfToken();

    $formAction = $_POST['action'] ?? 'profile';

    if ($formAction === 'disconnect_calendar') {
        if (!$isStaffView) {
            $errors[] = 'You do not have access to Google connections.';
        } else {
            $service = GoogleCalendarService::instance($db);
            $roleKey = strtolower(str_replace(['_', ' '], '-', (string) ($user['role'] ?? '')));
            if (in_array($roleKey, ['head-of-sdru', 'sdru-head', 'coordinator'], true) && (int) ($user['account_id'] ?? 0) > 0) {
                $service->userDisconnect((int) $user['account_id']);
                $calSuccess = 'Your Google connection was disconnected.';
            } else {
                $service->disconnect();
                $calSuccess = 'Your Google account was disconnected. Case emails will use the system mailbox; hearings will no longer be synced.';
            }
        }
    } elseif ($formAction === 'update_profile_pic') {
        $profilePicError = null;
        $dataUrl = (string) ($_POST['profile_pic_data_url'] ?? '');

        if ($dataUrl === '') {
            $profilePicError = 'No cropped image was received.';
        } else {
            $mimeMatch = preg_match('/^data:image\/(jpeg|png|webp);base64,(.*)$/s', $dataUrl, $matches);

            if (!$mimeMatch) {
                $profilePicError = 'Please upload a JPG, PNG, or WebP image.';
            } else {
                $imageType = $matches[1];
                $imageBytes = base64_decode($matches[2], true);

                if ($imageBytes === false || $imageBytes === '') {
                    $profilePicError = 'The cropped image could not be read.';
                } elseif (strlen($imageBytes) > (5 * 1024 * 1024)) {
                    $profilePicError = 'The image exceeds the 5MB limit.';
                } else {
                    $info = @getimagesizefromstring($imageBytes);
                    $validTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

                    if ($info === false || !in_array($info[2], $validTypes, true)) {
                        $profilePicError = 'Please upload a valid JPG, PNG, or WebP image.';
                    } else {
                        $uploadDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'profile_pics';

                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $storedFilename = 'pic_' . (int) $user['account_id'] . '_' . date('YmdHis') . '.' . ($imageType === 'png' ? 'png' : ($imageType === 'webp' ? 'webp' : 'jpg'));
                        $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedFilename;

                        if (file_put_contents($destination, $imageBytes) === false) {
                            $profilePicError = 'Unable to save the profile picture. Please try again.';
                        } else {
                            $currentUser = User::find($user['account_id']);

                            if ($currentUser && $currentUser->update([
                                'profile_pic' => 'storage/profile_pics/' . $storedFilename,
                                'updated_at'  => date('Y-m-d H:i:s'),
                            ])) {
                                if (!empty($user['profile_pic'])) {
                                    $oldPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $user['profile_pic']);
                                    if (str_starts_with(str_replace('\\', '/', (string) $user['profile_pic']), 'storage/profile_pics/') && is_file($oldPath)) {
                                        @unlink($oldPath);
                                    }
                                }
                                $success = 'Profile picture updated successfully.';
                                $user = User::findByEmail($_SESSION['email']);
                            } else {
                                @unlink($destination);
                                $profilePicError = 'Unable to save the profile picture. Please try again.';
                            }
                        }
                    }
                }
            }
        }

        if ($profilePicError) {
            $errors[] = $profilePicError;
        }
    } elseif ($formAction === 'remove_profile_pic') {
        if (!empty($user['profile_pic']) && str_starts_with(str_replace('\\', '/', (string) $user['profile_pic']), 'storage/profile_pics/')) {
            $oldPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $user['profile_pic']);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $currentUser = User::find($user['account_id']);
        if ($currentUser) {
            $currentUser->update(['profile_pic' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        $success = 'Profile picture removed.';
        $user = User::findByEmail($_SESSION['email']);
    } elseif ($formAction === 'logout_device') {
        $targetSession = trim((string) ($_POST['session_id'] ?? ''));
        if ($targetSession === session_id()) {
            $errors[] = 'Use the main Log Out link to end your current session.';
        } elseif (LoginSession::revoke($targetSession, (int) $user['account_id'])) {
            LoginSession::destroyPhpSession($targetSession);
            $success = 'The selected device was logged out.';
        } else {
            $errors[] = 'That device session is no longer active.';
        }
    } else if ($formAction === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword     = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $hasPassword = !empty($user['password_hash']);

        if ($hasPassword && $currentPassword === '') {
            $pwErrors[] = 'Please enter your current password.';
            $pwFieldErrors['current_password'][] = 'Please enter your current password.';
        }

        if ($newPassword === '') {
            $pwErrors[] = 'Please enter a new password.';
            $pwFieldErrors['new_password'][] = 'Please enter a new password.';
        } elseif (strlen($newPassword) < 8) {
            $pwErrors[] = 'New password must be at least 8 characters.';
            $pwFieldErrors['new_password'][] = 'New password must be at least 8 characters.';
        } else {
            if (!preg_match('/[A-Z]/', $newPassword)) {
                $pwErrors[] = 'New password must include at least one uppercase letter.';
                $pwFieldErrors['new_password'][] = 'New password must include at least one uppercase letter.';
            }

            if (!preg_match('/\d/', $newPassword)) {
                $pwErrors[] = 'New password must include at least one number.';
                $pwFieldErrors['new_password'][] = 'New password must include at least one number.';
            }

            if (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
                $pwErrors[] = 'New password must include at least one symbol.';
                $pwFieldErrors['new_password'][] = 'New password must include at least one symbol.';
            }
        }

        if ($newPassword !== '' && $newPassword !== $confirmPassword) {
            $pwErrors[] = 'New password and confirmation do not match.';
            $pwFieldErrors['confirm_password'][] = 'New password and confirmation do not match.';
        }

        if (empty($pwErrors) && $hasPassword && !password_verify($currentPassword, $user['password_hash'])) {
            $pwErrors[] = 'Your current password is incorrect.';
            $pwFieldErrors['current_password'][] = 'Your current password is incorrect.';
        }

        if (empty($pwErrors) && $hasPassword && password_verify($newPassword, $user['password_hash'])) {
            $pwErrors[] = 'New password must be different from your current password.';
            $pwFieldErrors['new_password'][] = 'New password must be different from your current password.';
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
                $pwFieldErrors['current_password'][] = 'Unable to change password. Please try again.';
            }
        }
    } else {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $email     = strtolower(trim((string) ($_POST['email'] ?? $user['email'] ?? '')));
        $phone     = trim($_POST['phone_number'] ?? '');
        $gender    = trim($_POST['gender'] ?? '');
        $address   = trim($_POST['address'] ?? '');
        $birthday  = trim($_POST['birthday'] ?? '');
        $studentNumber = trim($_POST['student_number'] ?? '');
        $college   = trim($_POST['college'] ?? '');
        $course    = Courses::canonical($_POST['course'] ?? '');
        $section   = trim($_POST['section'] ?? '');

        if ($firstName === '') {
            $fieldErrors['first_name'][] = 'First name is required.';
        } elseif (strlen($firstName) > 100) {
            $fieldErrors['first_name'][] = 'First name must be 100 characters or fewer.';
        }

        if ($lastName === '') {
            $fieldErrors['last_name'][] = 'Last name is required.';
        } elseif (strlen($lastName) > 100) {
            $fieldErrors['last_name'][] = 'Last name must be 100 characters or fewer.';
        }

        if ($isStaffView) {
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fieldErrors['email'][] = 'Please enter a valid email address.';
            } elseif (strlen($email) > 255) {
                $fieldErrors['email'][] = 'Email must be 255 characters or fewer.';
            } else {
                $existingEmail = User::findByEmailInexact($email);
                if ($existingEmail && (int) $existingEmail['account_id'] !== (int) $user['account_id']) {
                    $fieldErrors['email'][] = 'That email address is already in use.';
                }
            }
        }

        if ($phone !== '') {
            if (strlen($phone) > 20) {
                $fieldErrors['phone_number'][] = 'Phone number must be 20 characters or fewer.';
            } elseif (!preg_match('/^[0-9+()\-\s.]{7,20}$/', $phone)) {
                $fieldErrors['phone_number'][] = 'Please enter a valid phone number.';
            }
        }

        if ($gender !== '') {
            if (!in_array($gender, ['Male', 'Female', 'Prefer not to say'], true)) {
                $fieldErrors['gender'][] = 'Please select a valid gender.';
            }
        }

        if ($isStudent) {
            if ($birthday === '') {
                $fieldErrors['birthday'][] = 'Birthday is required.';
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) {
                $fieldErrors['birthday'][] = 'Please select a valid birthday (YYYY-MM-DD).';
            } elseif ($birthday > date('Y-m-d')) {
                $fieldErrors['birthday'][] = 'Birthday cannot be in the future.';
            }

            if ($studentNumber === '') {
                $fieldErrors['student_number'][] = 'Student number is required.';
            } elseif (strlen($studentNumber) > 50) {
                $fieldErrors['student_number'][] = 'Student number must be 50 characters or fewer.';
            }

            if ($college === '') {
                $fieldErrors['college'][] = 'College is required.';
            } elseif (!Colleges::contains($college)) {
                $fieldErrors['college'][] = 'Please select a valid college.';
            }

            if ($course === '') {
                $fieldErrors['course'][] = 'Course is required.';
            } elseif (!Courses::belongsToCollege($course, $college)) {
                $fieldErrors['course'][] = 'Please select a course offered by the selected college.';
            }

            $validSections = array_merge(...array_values(Courses::sections()));
            if ($section === '') {
                $fieldErrors['section'][] = 'Section is required.';
            } elseif (!in_array($section, $validSections, true)) {
                $fieldErrors['section'][] = 'Please select a valid section.';
            }
        }

        $errors = [];
        foreach ($fieldErrors as $fieldMessages) {
            foreach ($fieldMessages as $fieldMessage) {
                $errors[] = $fieldMessage;
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

                if ($isStaffView) {
                    $updateData['email'] = $email;
                }

                if ($isStudent) {
                    $updateData['student_number'] = $studentNumber;
                    $updateData['college']        = $college;
                    $updateData['course']         = $course;
                    $updateData['section']        = $section;
                    $updateData['birthday']       = $birthday !== '' ? $birthday : null;
                }

                $result = $currentUser->update($updateData);

                if ($result) {
                    if ($isStaffView) {
                        $_SESSION['email'] = $email;
                    }
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
$old['course'] = Courses::canonical($old['course'] ?? '');
$hasPassword = !empty($user['password_hash']);

$calSuccess = $calSuccess ?? null;
$calendarService = GoogleCalendarService::instance($db);
$roleKey = strtolower(str_replace(['_', ' '], '-', (string) ($user['role'] ?? '')));
$isPerUserCalendarRole = in_array($roleKey, ['head-of-sdru', 'sdru-head', 'coordinator'], true) && (int) ($user['account_id'] ?? 0) > 0;
$calConnected = $isPerUserCalendarRole ? $calendarService->userConnected((int) $user['account_id']) : $calendarService->isConnected();
$calEmail = $isPerUserCalendarRole ? $calendarService->userConnectedEmail((int) $user['account_id']) : $calendarService->connectedEmail();
$calMessage = $_SESSION['cal_message'] ?? null;
$calError = $_SESSION['cal_error'] ?? null;
unset($_SESSION['cal_message'], $_SESSION['cal_error']);
LoginSession::ensureCurrent(
    (int) $user['account_id'],
    session_id(),
    $_SERVER['HTTP_USER_AGENT'] ?? '',
    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
);
LoginSession::touch(session_id());
$loginSessions = LoginSession::forAccount((int) $user['account_id'], session_id());
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - DARIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
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
                            <h3 class="settings-group-title"><i class="bi bi-person-circle"></i> Profile Picture</h3>
                            <p class="settings-group-desc">Upload a photo and crop it to frame your profile picture.</p>
                        </div>
                        <div class="settings-avatar-section">
                            <div class="settings-avatar" id="settings-avatar">
                                <?php if (!empty($user['profile_pic'])): ?>
                                    <img src="<?= h(profile_pic_url($user)) ?>" alt="Profile photo">
                                <?php else: ?>
                                    <?= h(strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1))) ?>
                                <?php endif; ?>
                            </div>
                            <div class="settings-avatar-info">
                                <div class="settings-avatar-name"><?= h(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></div>
                                <div class="settings-avatar-email"><?= h($user['email'] ?? '') ?></div>
                                <div class="settings-avatar-role"><?= h(ucwords(str_replace(['-', '_'], ' ', $user['role'] ?? ''))) ?></div>
                            </div>
                        </div>

                        <label class="btn btn-secondary" style="cursor:pointer;width:fit-content;" for="settings-profile-pic-input">
                            <i class="bi bi-camera"></i> <?= !empty($user['profile_pic']) ? 'Change Photo' : 'Upload Photo' ?>
                        </label>
                        <?php if (!empty($user['profile_pic'])): ?>
                        <form method="POST" action="<?= h(app_url('web/views/settings/index.php')) ?>" data-no-ajax="true" style="display:inline-block;margin-left:8px;">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="remove_profile_pic">
                            <button class="btn btn-remove" type="submit" style="background:#eef1ee;color:#3f4c3e"><i class="bi bi-trash"></i> Remove Photo</button>
                        </form>
                        <?php endif; ?>
                        <input type="file" id="settings-profile-pic-input" accept="image/jpeg,image/png,image/webp" style="display:none;">

                        <div id="profile-pic-crop-modal" class="crop-modal" style="display:none;">
                            <div class="crop-modal-card">
                                <div class="crop-modal-header">
                                    <strong>Crop your photo</strong>
                                    <button type="button" class="crop-modal-close" data-crop-cancel aria-label="Close">&times;</button>
                                </div>
                                <div class="crop-modal-body">
                                    <img id="profile-pic-crop-image" alt="Photo to crop">
                                </div>
                                <div class="crop-modal-actions">
                                    <button type="button" class="btn btn-secondary" data-crop-cancel>Cancel</button>
                                    <button type="button" class="btn btn-primary" id="profile-pic-crop-apply"><i class="bi bi-check-lg"></i> Save Photo</button>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="<?= h(app_url('web/views/settings/index.php')) ?>" id="profile-pic-upload-form" class="settings-form" style="display:none;">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="update_profile_pic">
                            <input type="hidden" name="profile_pic_data_url" id="profile-pic-data-url">
                        </form>
                    </div>

                    <div class="settings-group">
                        <div class="settings-group-header">
                            <h3 class="settings-group-title"><i class="bi bi-person"></i> Personal Information</h3>
                            <p class="settings-group-desc">Update your personal details and contact information.</p>
                        </div>
                    <form method="POST" action="<?= h(app_url('web/views/settings/index.php')) ?>" class="settings-form" data-sicms-validate>
                        <?= Security::csrfField() ?>

                        <div class="settings-fields">
                            <div class="settings-field-row">
                                <div class="settings-field">
                                    <label for="first_name">First Name <span class="required">*</span></label>
                                    <input type="text" id="first_name" name="first_name" value="<?= h($old['first_name'] ?? '') ?>" required maxlength="100">
                                    <?= field_error_html($fieldErrors, 'first_name') ?>
                                </div>
                                <div class="settings-field">
                                    <label for="last_name">Last Name <span class="required">*</span></label>
                                    <input type="text" id="last_name" name="last_name" value="<?= h($old['last_name'] ?? '') ?>" required maxlength="100">
                                    <?= field_error_html($fieldErrors, 'last_name') ?>
                                </div>
                            </div>

                            <div class="settings-field">
                                <label for="email">Email<?= $isStaffView ? ' <span class="required">*</span>' : '' ?></label>
                                <?php if ($isStaffView): ?>
                                    <input type="email" id="email" name="email" value="<?= h($old['email'] ?? $user['email'] ?? '') ?>" required maxlength="255">
                                    <span class="settings-field-note">This updates only your own sign-in email.</span>
                                    <?= field_error_html($fieldErrors, 'email') ?>
                                <?php else: ?>
                                    <input type="email" id="email" value="<?= h($user['email'] ?? '') ?>" disabled>
                                    <span class="settings-field-note">Email cannot be changed.</span>
                                <?php endif; ?>
                            </div>

                            <div class="settings-field-row">
                                <div class="settings-field">
                                    <label for="phone_number">Phone Number</label>
                                    <input type="text" id="phone_number" name="phone_number" value="<?= h($old['phone_number'] ?? '') ?>" maxlength="20" placeholder="e.g. 09XXXXXXXXX" data-sicms-phone>
                                    <?= field_error_html($fieldErrors, 'phone_number') ?>
                                </div>
                                <div class="settings-field">
                                    <label for="gender">Gender <span class="required">*</span></label>
                                    <select id="gender" name="gender" required>
                                        <option value="" <?= ($old['gender'] ?? '') === '' ? 'selected' : '' ?>>Select gender</option>
                                        <option value="Male" <?= ($old['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($old['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Prefer not to say" <?= ($old['gender'] ?? '') === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
                                    </select>
                                    <?= field_error_html($fieldErrors, 'gender') ?>
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
                                    <input type="text" id="student_number" name="student_number" value="<?= h($old['student_number'] ?? '') ?>" maxlength="50" placeholder="e.g. 12-3456" required>
                                    <?= field_error_html($fieldErrors, 'student_number') ?>
                                </div>
                                <div class="settings-field-row">
                                    <div class="settings-field">
                                        <label for="college">College <span class="required">*</span></label>
                                        <select id="college" name="college" required>
                                            <option value="">Select College</option>
                                            <?php foreach (Colleges::all() as $collegeOption): ?>
                                                <option value="<?= h($collegeOption) ?>" <?= ($old['college'] ?? '') === $collegeOption ? 'selected' : '' ?>>
                                                    <?= h($collegeOption) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?= field_error_html($fieldErrors, 'college') ?>
                                    </div>
                                    <div class="settings-field">
                                        <label for="course">Course/Program <span class="required">*</span></label>
                                        <select id="course" name="course" required <?= empty($old['college']) ? 'disabled' : '' ?>>
                                            <option value=""><?= empty($old['college']) ? 'Select a college first' : 'Select Course/Program' ?></option>
                                            <?php foreach (Courses::forCollege($old['college'] ?? '') as $courseOption): ?>
                                                <option value="<?= h($courseOption) ?>" <?= ($old['course'] ?? '') === $courseOption ? 'selected' : '' ?>>
                                                    <?= h($courseOption) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?= field_error_html($fieldErrors, 'course') ?>
                                    </div>
                                </div>
                                <div class="settings-field-row">
                                    <div class="settings-field">
                                        <label for="section">Section <span class="required">*</span></label>
                                        <select id="section" name="section" required <?= empty($old['course']) ? 'disabled' : '' ?>>
                                            <option value="">Select Section</option>
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
                                        <?= field_error_html($fieldErrors, 'section') ?>
                                    </div>
                                    <div class="settings-field">
                                        <label for="birthday">Birthday / Date of Birth <span class="required">*</span></label>
                                        <input type="date" id="birthday" name="birthday" value="<?= h($old['birthday'] ?? '') ?>" max="<?= h(date('Y-m-d')) ?>" required>
                                        <span class="settings-field-note">Cannot be a future date.</span>
                                        <?= field_error_html($fieldErrors, 'birthday') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="settings-actions">
                            <button type="submit" class="btn btn-primary" data-sicms-processing-label="Saving Changes..." data-sicms-processing-modal="true"><i class="bi bi-check2"></i> Save Changes</button>
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

                    <form method="POST" action="<?= h(app_url('web/views/settings/index.php')) ?>" class="settings-form settings-password-form" data-sicms-validate>
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
                                <?= field_error_html($pwFieldErrors, 'current_password') ?>
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
                                    <?= field_error_html($pwFieldErrors, 'new_password') ?>
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
                                            data-sicms-match="#new_password"
                                        >
                                        <button type="button" class="password-toggle" aria-label="Show password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <?= field_error_html($pwFieldErrors, 'confirm_password') ?>
                                </div>
                            </div>
                        </div>

                        <div class="settings-actions">
                            <button type="submit" class="btn btn-primary" data-sicms-processing-label="Saving Changes..." data-sicms-processing-modal="true"><i class="bi bi-check2"></i> Confirm</button>
                        </div>
                    </form>
                    </div>

                    <?php if ($isStaffView): ?>
                    <div class="settings-group">
                        <div class="settings-group-header">
                            <h3 class="settings-group-title"><i class="bi bi-plug"></i> Integrations &amp; Data</h3>
                            <p class="settings-group-desc">Connect your Google account so hearings are scheduled automatically and case emails are sent from your own Gmail.</p>
                        </div>
                        <form method="POST" action="<?= h(app_url('web/views/settings/index.php')) ?>" class="settings-form settings-password-form settings-group-form" data-confirm-title="Disconnect Google account?" data-confirm="<?= $calConnected ? 'Existing calendar events will remain, but future synchronization and connected email features will stop.' : '' ?>" data-confirm-button="Yes, disconnect" data-confirm-icon="warning">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="disconnect_calendar">

                        <div class="settings-section-header settings-subsection">
                            <h3 class="settings-section-title"><i class="bi bi-google"></i> Google Connection</h3>
                            <p class="settings-section-description">
                                Connect your own Google account so scheduled hearings are added to your calendar and forwarded case emails are sent from your Gmail.
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
                                            New scheduled hearings are added to your calendar, and forwarded case emails are sent from your Gmail. Disconnecting reverts case emails to the system mailbox at CLSU.
                                        <?php else: ?>
                                            Case emails will keep working from the system mailbox. Once connected, scheduled hearings are added to your calendar automatically and forwarded case emails are sent from your Gmail. Need the exact redirect URI? <a href="../auth/google_connect_calendar.php?diag=1" target="_blank" rel="noopener">Open diagnostics</a>.
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if ($calConnected): ?>
                                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Disconnect</button>
                                <?php else: ?>
                                    <a class="btn btn-primary" href="../auth/google_connect_calendar.php"><i class="bi bi-google"></i> Connect Google</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>
                <?php endif; ?>
                <div class="settings-group">
                    <div class="settings-group-header">
                        <h3 class="settings-group-title"><i class="bi bi-laptop"></i> Logged-in Devices</h3>
                        <p class="settings-group-desc">Review where your account is signed in and end sessions you no longer recognize.</p>
                    </div>
                    <div class="settings-device-list" id="settings-device-list">
                        <?php $deviceIndex = 0; ?>
                        <?php foreach ($loginSessions as $loginSession): ?>
                        <div class="settings-device-row<?= $deviceIndex >= 3 ? ' settings-device-more' : '' ?>"<?= $deviceIndex >= 3 ? ' style="display:none"' : '' ?>>
                            <div class="settings-device-icon"><i class="bi <?= stripos($loginSession['device_type'], 'mobile') !== false ? 'bi-phone' : 'bi-display' ?>"></i></div>
                            <div class="settings-device-info">
                                <strong><?= h($loginSession['device_type']) ?></strong>
                                <?php if ((int) $loginSession['is_current'] === 1): ?><span class="settings-device-current"><i class="bi bi-check-circle-fill"></i> Currently logged in here</span><?php endif; ?>
                                <span>Location: <?= h($loginSession['location'] ?: session_location($loginSession['ip_address'])) ?><?= (int) $loginSession['is_current'] === 1 ? ' Â· Current connection' : '' ?></span>
                                <span>Last login: <?= h(date('M d, Y h:i A', strtotime($loginSession['last_login_at']))) ?></span>
                            </div>
                            <?php if ((int) $loginSession['is_current'] !== 1): ?>
                            <form method="POST" action="<?= h(app_url('web/views/settings/index.php')) ?>" data-no-ajax="true" data-confirm-title="Log out this device?" data-confirm="This DARIS session will be revoked on the selected device." data-confirm-button="Yes, log out device" data-confirm-icon="warning"><input type="hidden" name="csrf_token" value="<?= h(Security::csrfToken()) ?>"><input type="hidden" name="action" value="logout_device"><input type="hidden" name="session_id" value="<?= h($loginSession['session_id']) ?>"><button class="btn btn-danger" type="submit" data-sicms-processing-label="Logging out device..."><i class="bi bi-box-arrow-right"></i> Log Out</button></form>
                            <?php endif; ?>
                        </div>
                        <?php $deviceIndex++; endforeach; ?>
                        <?php if ($deviceIndex > 5): ?>
                        <button type="button" id="settings-device-load-more" class="btn btn-secondary" style="margin-top:10px;width:100%;"><i class="bi bi-chevron-down"></i> Load More Devices</button>
                        <?php endif; ?>
                        <?php if (empty($loginSessions)): ?><p class="settings-field-note">No active devices were found.</p><?php endif; ?>
                    </div>
                </div>
                </div> 
            </section>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function () {
            var college = document.getElementById('college');
            var course = document.getElementById('course');
            var section = document.getElementById('section');
            var programsByCollege = <?= json_encode(Courses::byCollege(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

            if (!college || !course || !section) return;

            function loadPrograms(preserveSelection) {
                var previous = preserveSelection ? course.value : '';
                var programs = programsByCollege[college.value] || [];
                course.replaceChildren(new Option(programs.length ? 'Select Course/Program' : 'Select a college first', ''));
                programs.forEach(function (program) {
                    course.add(new Option(program, program));
                });
                course.disabled = programs.length === 0;
                if (previous && programs.includes(previous)) course.value = previous;
            }

            college.addEventListener('change', function () {
                loadPrograms(false);
                section.value = '';
                section.disabled = true;
            });

            course.addEventListener('change', function () {
                section.value = '';
                section.disabled = course.value === '';
            });

            loadPrograms(true);
            section.disabled = course.value === '';
        })();

        var loadMoreBtn = document.getElementById('settings-device-load-more');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function () {
                var rows = document.querySelectorAll('.settings-device-more');
                var expanded = loadMoreBtn.getAttribute('data-expanded') === '1';

                rows.forEach(function (row) { row.style.display = expanded ? 'none' : ''; });

                if (expanded) {
                    loadMoreBtn.setAttribute('data-expanded', '0');
                    loadMoreBtn.innerHTML = '<i class="bi bi-chevron-down"></i> Load More Devices';
                } else {
                    loadMoreBtn.setAttribute('data-expanded', '1');
                    loadMoreBtn.innerHTML = '<i class="bi bi-chevron-up"></i> Show Less';
                }
            });
        }

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

        (function () {
            var fileInput = document.getElementById('settings-profile-pic-input');
            var cropImage = document.getElementById('profile-pic-crop-image');
            var cropModal = document.getElementById('profile-pic-crop-modal');
            var applyButton = document.getElementById('profile-pic-crop-apply');
            var dataUrlInput = document.getElementById('profile-pic-data-url');
            var uploadForm = document.getElementById('profile-pic-upload-form');
            var cropper = null;

            if (!fileInput || !cropImage || !cropModal || !applyButton || !dataUrlInput || !uploadForm) return;

            function openCrop(file) {
                var reader = new FileReader();

                reader.onload = function (event) {
                    cropImage.src = event.target.result;
                    cropModal.style.display = 'flex';
                    document.body.classList.add('crop-modal-open');

                    document.body.style.overflow = 'hidden';

                    if (cropper) cropper.destroy();
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        autoCropArea: 1,
                        responsive: true,
                        restore: false
                    });
                };

                reader.readAsDataURL(file);
            }

            function closeCrop() {
                cropModal.style.display = 'none';
                document.body.classList.remove('crop-modal-open');
                document.body.style.overflow = '';
                fileInput.value = '';
                if (cropper) { cropper.destroy(); cropper = null; }
            }

            fileInput.addEventListener('change', function () {
                if (!fileInput.files || fileInput.files.length === 0) return;

                var file = fileInput.files[0];

                if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
                    window.DARISAlert?.toast('warning', 'Invalid File', 'Please choose a JPG, PNG, or WebP image.');
                    fileInput.value = '';
                    return;
                }

                openCrop(file);
            });

            document.querySelectorAll('[data-crop-cancel]').forEach(function (button) {
                button.addEventListener('click', closeCrop);
            });

            cropModal.addEventListener('click', function (event) {
                if (event.target === cropModal) {
                    closeCrop();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && cropModal.style.display === 'flex') {
                    closeCrop();
                }
            });

            applyButton.addEventListener('click', function () {
                var canvas = cropper ? cropper.getCroppedCanvas({ width: 400, height: 400, imageSmoothingEnabled: true, imageSmoothingQuality: 'high' }) : null;

                if (!canvas) return;

                dataUrlInput.value = canvas.toDataURL('image/jpeg', 0.92);
                uploadForm.submit();
            });
        })();
    </script>
    <script>
    window.addEventListener('load', () => {
        <?php if ($success): ?>window.DARISAlert?.toast('success', 'Changes Saved', 'Your account settings have been updated successfully.');<?php endif; ?>
        <?php if (!empty($errors)): ?>window.DARISAlert?.toast('error', 'Unable to Save Changes', <?= json_encode(implode(' ', array_map('strval', $errors))) ?>, { timer: 7000 });<?php endif; ?>
        <?php if ($pwSuccess): ?>window.DARISAlert?.toast('success', 'Password Updated', <?= json_encode((string) $pwSuccess) ?>);<?php endif; ?>
        <?php if (!empty($pwErrors)): ?>window.DARISAlert?.toast('error', 'Unable to Update Password', <?= json_encode(implode(' ', array_map('strval', $pwErrors))) ?>, { timer: 7000 });<?php endif; ?>
        <?php if ($calMessage || $calSuccess): ?>window.DARISAlert?.toast('success', 'Calendar Settings Updated', <?= json_encode((string) ($calMessage ?: $calSuccess)) ?>);<?php endif; ?>
        <?php if ($calError): ?>window.DARISAlert?.toast('error', 'Unable to Update Calendar Settings', <?= json_encode(implode(' ', array_map('strval', (array) $calError))) ?>, { timer: 7000 });<?php endif; ?>
    }, { once: true });

    document.querySelectorAll('form.settings-form[data-sicms-validate]').forEach(form => {
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(button => {
            button.addEventListener('click', event => {
                const missing = Array.from(form.querySelectorAll('[required]')).find(control => !control.checkValidity());
                if (!missing) return;
                event.preventDefault();
                const label = form.querySelector(`label[for="${missing.id}"]`)?.textContent?.replace('*', '').trim();
                window.DARISAlert?.toast('warning', 'Required Information Missing', `Please complete ${label || 'the required account information'} before saving.`);
                missing.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(() => missing.focus(), 180);
            });
        });
    });
    </script>
</body>

</html>

