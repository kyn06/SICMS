<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

if (isset($_SESSION['email'])) {
    header('Location: ../../../index.php');
    exit;
}

$errorMessage = $_SESSION['error'] ?? null;
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod !== 'POST') {
    unset($_SESSION['error']);
    session_write_close();
}

if ($requestMethod == 'POST') {
    Security::requireCsrfToken();
    require '../../config/Database.php';
    require '../../models/User.php';

    $database = new Database();
    $db = $database->getConnection();

    User::setConnection($db);

    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];
    $accept_terms = isset($_POST['accept_terms']);

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'All fields are required.';
        header('Location: create_acc.php');
        exit;
    }

    if (!$accept_terms) {
        $_SESSION['error'] = 'You must agree to the Terms of Service and Privacy Policy to create an account.';
        header('Location: create_acc.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address.';
        header('Location: create_acc.php');
        exit;
    }

    if ($password !== $confirm) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: create_acc.php');
        exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters.';
        header('Location: create_acc.php');
        exit;
    }

    // Check if email already exists
    $existing = User::findByEmail($email);
    if ($existing) {
        $_SESSION['error'] = 'An account with that email already exists.';
        header('Location: create_acc.php');
        exit;
    }

    // Create the user
    $result = User::create([
        'first_name'    => $first_name,
        'last_name'     => $last_name,
        'email'         => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role'          => 'student',
        'status'        => 'active',
        'created_at'    => date('Y-m-d H:i:s'),
        'updated_at'    => date('Y-m-d H:i:s'),
    ]);

    if ($result) {
        $_SESSION['success'] = 'Account created successfully. You can now log in.';
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['error'] = 'Something went wrong. Please try again.';
        header('Location: create_acc.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/style.css">
    <style>
        .name-row {
            display: flex;
            gap: 12px;
            width: 100%;
        }

        .name-row .pill-input {
            flex: 1;
            min-width: 0;
        }

        .back-link {
            font-size: 13px;
            color: #000;
            text-decoration: none;
            align-self: flex-start;
            margin-bottom: 10px;
            margin-left: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .terms-check {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 12px;
            color: #555;
            line-height: 1.5;
            align-self: flex-start;
            margin-bottom: 12px;
            margin-left: 4px;
            cursor: pointer;
        }

        .terms-check input[type="checkbox"] {
            margin-top: 2px;
            accent-color: #1A9D00;
            flex-shrink: 0;
        }

        .terms-check a {
            color: #1A9D00;
            text-decoration: none;
            cursor: pointer;
        }

        .terms-check a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <img class="seal" src="../../../public/assets/clsulogo.png" alt="CLSu logo">
    <p class="org-name">Office of Student Affairs - Student<br>Discipline and Reformation Unit</p>

    <form class="form-wrap" action="create_acc.php" method="POST">
        <?= Security::csrfField() ?>

        <?php if ($errorMessage): ?>
            <div class="error-banner">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <div class="name-row">
            <input
                type="text"
                class="pill-input"
                name="first_name"
                placeholder="First name"
                value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
            >
            <input
                type="text"
                class="pill-input"
                name="last_name"
                placeholder="Last name"
                value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
            >
        </div>

        <input
            type="email"
            class="pill-input"
            name="email"
            placeholder="Email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
        >

        <div class="password-wrapper">
            <input
                type="password"
                class="pill-input"
                name="password"
                placeholder="Password"
            >
            <button type="button" class="password-toggle" aria-label="Show password" onclick="const p=this.previousElementSibling;p.type=p.type==='password'?'text':'password';this.innerHTML=p.type==='password'?'<i class=\'bi bi-eye\'></i>':'<i class=\'bi bi-eye-slash\'></i>';this.setAttribute('aria-label',p.type==='password'?'Show password':'Hide password')"><i class="bi bi-eye"></i></button>
        </div>

        <div class="password-wrapper">
            <input
                type="password"
                class="pill-input"
                name="confirm_password"
                placeholder="Confirm password"
            >
            <button type="button" class="password-toggle" aria-label="Show password" onclick="const p=this.previousElementSibling;p.type=p.type==='password'?'text':'password';this.innerHTML=p.type==='password'?'<i class=\'bi bi-eye\'></i>':'<i class=\'bi bi-eye-slash\'></i>';this.setAttribute('aria-label',p.type==='password'?'Show password':'Hide password')"><i class="bi bi-eye"></i></button>
        </div>

        <label class="terms-check">
            <input type="checkbox" name="accept_terms" value="1" <?= isset($_POST['accept_terms']) ? 'checked' : '' ?>>
            <span>I agree to the <a onclick="openModal('termsModal')">Terms of Service</a> and <a onclick="openModal('privacyModal')">Privacy Policy</a></span>
        </label>

        <a class="back-link" href="login.php">&#8592; Back to log in</a>

        <button type="submit" class="btn-login">Create Account</button>

    </form>

    <div class="auth-footer">
        <a onclick="openModal('termsModal')">Terms of Service</a> &middot;
        <a onclick="openModal('privacyModal')">Privacy Policy</a>
    </div>

    <!-- Terms of Service Modal -->
    <div id="termsModal" class="legal-modal" onclick="if(event.target===this)closeModal('termsModal')">
        <div class="legal-modal-content">
            <div class="legal-modal-header">
                <h2>Terms of Service</h2>
                <button type="button" class="legal-modal-close" onclick="closeModal('termsModal')">&times;</button>
            </div>
            <div class="legal-modal-body">
                <p class="legal-updated">Last updated: August 19, 2026</p>

                <h3>1. Acceptance of Terms</h3>
                <p>By accessing or using the Student Complaint Management System (SICMS), you agree to be bound by these Terms of Service. If you do not agree to these terms, you may not use the system.</p>

                <h3>2. Purpose of the System</h3>
                <p>SICMS is an online platform operated by the Office of Student Affairs - Student Discipline and Reformation Unit (SDRU) of CLSU. It is designed to facilitate the filing, tracking, and resolution of student complaints and disciplinary matters.</p>

                <h3>3. Eligibility</h3>
                <p>The system is available exclusively to currently enrolled CLSU students, authorized SDRU personnel, and designated administrators. Creating an account requires a valid CLSU email address.</p>

                <h3>4. User Responsibilities</h3>
                <ul>
                    <li>You must provide accurate and truthful information when filing complaints or creating an account.</li>
                    <li>You are responsible for maintaining the confidentiality of your account credentials.</li>
                    <li>You must not use the system to file false, malicious, or frivolous complaints.</li>
                    <li>You must not attempt to access, modify, or disrupt other users' accounts or system data.</li>
                    <li>You must comply with all applicable CLSU policies and regulations while using the system.</li>
                </ul>

                <h3>5. Account Registration</h3>
                <p>When creating an account, you agree to:</p>
                <ul>
                    <li>Provide truthful registration information.</li>
                    <li>Maintain the security of your password and account.</li>
                    <li>Promptly notify SDRU of any unauthorized use of your account.</li>
                </ul>
                <p>SDRU reserves the right to suspend or terminate accounts that violate these terms.</p>

                <h3>6. Complaint Filing and Processing</h3>
                <ul>
                    <li>All complaints filed through SICMS are subject to review and evaluation by authorized SDRU personnel.</li>
                    <li>Filing a complaint does not guarantee that action will be taken or that a hearing will be scheduled.</li>
                    <li>Users will be notified of case updates through the system's notification features.</li>
                    <li>Users must attend scheduled hearings and respond to requests for information in a timely manner.</li>
                </ul>

                <h3>7. Confidentiality</h3>
                <p>All complaint information is treated with strict confidentiality. Case details are accessible only to the complainant, respondent, and authorized SDRU personnel involved in the case. Unauthorized disclosure of case information is prohibited.</p>

                <h3>8. Prohibited Conduct</h3>
                <p>Users shall not:</p>
                <ul>
                    <li>Use the system for any unlawful purpose.</li>
                    <li>Impersonate another person or misrepresent their identity.</li>
                    <li>Submit complaints that are knowingly false or misleading.</li>
                    <li>Interfere with the proper operation of the system.</li>
                    <li>Attempt to circumvent system security measures.</li>
                </ul>

                <h3>9. Intellectual Property</h3>
                <p>All content, design, and functionality of SICMS are the property of CLSU and are protected by applicable intellectual property laws. You may not reproduce, distribute, or create derivative works from any part of the system without prior written consent.</p>

                <h3>10. Limitation of Liability</h3>
                <p>CLSU and SDRU shall not be held liable for any indirect, incidental, or consequential damages arising from the use of or inability to use SICMS. The system is provided "as is" without warranties of any kind.</p>

                <h3>11. Modifications to Terms</h3>
                <p>SDRU reserves the right to modify these Terms of Service at any time. Users will be notified of significant changes through the system. Continued use of SICMS after changes constitutes acceptance of the modified terms.</p>

                <h3>12. Contact Information</h3>
                <p>For questions or concerns about these Terms of Service, please contact the Student Discipline and Reformation Unit through the messaging feature in SICMS or visit the SDRU office during business hours.</p>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div id="privacyModal" class="legal-modal" onclick="if(event.target===this)closeModal('privacyModal')">
        <div class="legal-modal-content">
            <div class="legal-modal-header">
                <h2>Privacy Policy</h2>
                <button type="button" class="legal-modal-close" onclick="closeModal('privacyModal')">&times;</button>
            </div>
            <div class="legal-modal-body">
                <p class="legal-updated">Last updated: August 19, 2026</p>

                <h3>1. Introduction</h3>
                <p>The Student Discipline and Reformation Unit (SDRU) of CLSU is committed to protecting the privacy of users of the Student Complaint Management System (SICMS). This Privacy Policy explains how we collect, use, store, and protect your personal information.</p>

                <h3>2. Information We Collect</h3>
                <p>When you use SICMS, we collect the following information:</p>
                <ul>
                    <li><strong>Account Information:</strong> Full name, email address, role, and account creation date.</li>
                    <li><strong>Complaint Data:</strong> Details submitted in complaint forms, including descriptions, evidence, and related communications.</li>
                    <li><strong>System Activity:</strong> Login timestamps, actions performed within the system, and audit log entries.</li>
                    <li><strong>Device Information:</strong> IP address and browser user-agent string for security and audit purposes.</li>
                </ul>

                <h3>3. How We Use Your Information</h3>
                <p>We use the collected information for the following purposes:</p>
                <ul>
                    <li>To process and manage student complaints and disciplinary cases.</li>
                    <li>To authenticate users and manage account access.</li>
                    <li>To send notifications related to case updates and system activity.</li>
                    <li>To maintain audit logs for security and accountability.</li>
                    <li>To generate anonymized reports for institutional planning.</li>
                    <li>To comply with institutional policies and regulatory requirements.</li>
                </ul>

                <h3>4. Information Sharing</h3>
                <p>Your personal information is shared only with:</p>
                <ul>
                    <li><strong>Authorized SDRU Personnel:</strong> Staff directly involved in processing your complaint or case.</li>
                    <li><strong>System Administrators:</strong> Personnel responsible for maintaining and securing the system.</li>
                    <li><strong>Institutional Authorities:</strong> When required by CLSU policy, legal obligation, or for the protection of rights and safety.</li>
                </ul>
                <p>We do not sell, rent, or share your personal information with third parties for commercial purposes.</p>

                <h3>5. Data Security</h3>
                <p>We implement industry-standard security measures to protect your data, including:</p>
                <ul>
                    <li>Encrypted password storage using bcrypt hashing.</li>
                    <li>CSRF protection on all form submissions.</li>
                    <li>Role-based access control limiting data visibility to authorized personnel.</li>
                    <li>Secure session management with automatic timeout.</li>
                    <li>Audit logging of all system activities.</li>
                </ul>

                <h3>6. Data Retention</h3>
                <p>Account and complaint data are retained for the duration of the user's association with CLSU and for a period required by institutional records retention policies. Audit logs are maintained for security and compliance purposes.</p>

                <h3>7. Your Rights</h3>
                <p>As a user of SICMS, you have the right to:</p>
                <ul>
                    <li>Access the personal information associated with your account.</li>
                    <li>Request corrections to inaccurate information.</li>
                    <li>Request deletion of your account, subject to institutional retention requirements.</li>
                    <li>Receive notifications about your case status and system updates.</li>
                </ul>

                <h3>8. Cookies and Session Data</h3>
                <p>SICMS uses session cookies to maintain your logged-in state and ensure secure navigation. These cookies are temporary, stored only for the duration of your session, and are automatically deleted when you log out or your session expires.</p>

                <h3>9. Children's Privacy</h3>
                <p>SICMS is designed for use by college students who are 18 years of age or older. We do not knowingly collect personal information from individuals under the age of 18.</p>

                <h3>10. Changes to This Policy</h3>
                <p>We may update this Privacy Policy from time to time. Significant changes will be communicated through the system. Your continued use of SICMS after changes are posted constitutes acceptance of the updated policy.</p>

                <h3>11. Contact Information</h3>
                <p>For questions, concerns, or requests regarding your personal data or this Privacy Policy, please contact the Student Discipline and Reformation Unit through the messaging feature in SICMS or visit the SDRU office during business hours.</p>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.legal-modal.active').forEach(function(m) {
                    m.classList.remove('active');
                });
                document.body.style.overflow = '';
            }
        });
    </script>
</body>

</html>
