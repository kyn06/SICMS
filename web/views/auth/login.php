<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

function app_base_path() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    return rtrim(preg_replace('#/web/views/auth/login\.php$#', '', $script), '/');
}

if (isset($_SESSION['email'])) {
    header('Location: ' . app_base_path() . '/index.php');
    exit;
}

$successMessage = $_SESSION['success'] ?? null;
$errorMessage = $_SESSION['error'] ?? null;
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod !== 'POST') {
    unset($_SESSION['success'], $_SESSION['error']);
    session_write_close();
}

if ($requestMethod == 'POST') {
    Security::requireCsrfToken();
    require '../../config/Database.php';
    require '../../models/User.php';

    $database = new Database();
    $db = $database->getConnection();

    User::setConnection($db);

    $email = $_POST['email'];
    $password = $_POST['password'];

    if (Security::isLoginLocked($email)) {
        $remainingMinutes = max(1, (int) ceil(Security::loginLockRemaining($email) / 60));
        $_SESSION['error'] = 'Too many failed login attempts. Please try again in ' . $remainingMinutes . ' minute(s).';
        header('Location: login.php');
        exit;
    }

    if (User::login($email, $password)) {
        Security::clearLoginAttempts($email);
        header('Location: ' . app_base_path() . '/index.php');
        exit;
    } else {
        Security::recordFailedLogin($email);
        if (Security::isLoginLocked($email)) {
            $remainingMinutes = max(1, (int) ceil(Security::loginLockRemaining($email) / 60));
            $_SESSION['error'] = 'Too many failed login attempts. Please try again in ' . $remainingMinutes . ' minute(s).';
        } else {
            $_SESSION['error'] = 'Invalid email or password.';
        }
        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/style.css">
    <link rel="stylesheet" href="../layout/login-chatbot.css">
</head>

<body>

    <img class="seal" src="../../../public/assets/clsulogo.png" alt="clsu logo">
    <p class="org-name">Office of Student Affairs - Student<br>Discipline and Reformation Unit</p>
    <form class="form-wrap" action="login.php" method="POST">
        <?= Security::csrfField() ?>

        <?php if ($successMessage): ?>
            <div class="alert-success">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <input
            type="email"
            class="pill-input <?= $errorMessage ? 'is-invalid' : '' ?>"
            id="email"
            name="email"
            placeholder="Email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
        >

        <?php if ($errorMessage): ?>
            <p class="error-msg"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>

        <div class="password-wrapper">
            <input 
                type="password" 
                class="pill-input" 
                id="password" 
                name="password" 
                placeholder="Password"
            >

            <button 
                type="button" 
                class="password-toggle"
                id="passwordToggle"
                aria-label="Show password"
            >
                <i class="bi bi-eye"></i>
            </button>
        </div>

        <script>
            const password = document.getElementById("password");
            const passwordToggle = document.getElementById("passwordToggle");

            passwordToggle.addEventListener("click", function () {
                const isPassword = password.type === "password";

                password.type = isPassword ? "text" : "password";

                this.innerHTML = isPassword
                    ? '<i class="bi bi-eye-slash"></i>'
                    : '<i class="bi bi-eye"></i>';

                this.setAttribute(
                    "aria-label",
                    isPassword ? "Hide password" : "Show password"
                );
            });
        </script>

        <a class="create-link" href="create_acc.php">Create an account.</a>

        <button type="submit" class="btn-login">Log In</button>

        <div class="divider">
            <span class="divider-line"></span>
            <span class="divider-text">or continue with</span>
            <span class="divider-line"></span>
        </div>

         <a href="#" class="btn-google"> <!-- wala rin ref -->
            <svg class="google-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Sign Up with Google
        </a>

    </form>

    <!-- Public SDRU Procedural and Inquiry Chatbot -->
    <button type="button" id="sdruchatLauncher" class="sdruchat-launcher" aria-controls="sdruchatPanel" aria-expanded="false">
        <span class="sdruchat-launcher-icon">?</span>
        <span class="sdruchat-launcher-copy">
            <strong>Need help?</strong>
            <small>Ask the SDRU Assistant</small>
        </span>
        <span class="sdruchat-launcher-arrow">↗</span>
    </button>

    <section id="sdruchatPanel" class="sdruchat-panel" aria-label="SDRU Assistant chatbot">
        <div class="sdruchat-head">
            <div class="sdruchat-title">
                <div class="sdruchat-avatar">SD</div>
                <div>
                    <strong>SDRU Assistant</strong>
                    <small>Procedural &amp; Inquiry Assistant</small>
                </div>
            </div>
            <button type="button" id="sdruchatClose" class="sdruchat-close" aria-label="Close chatbot">&times;</button>
        </div>

        <div class="sdruchat-notice">
            Ask about complaint procedures, requirements, case workflow, hearings, evidence, notifications, messaging, and general SDRU services. Do not enter confidential case details here.
        </div>

        <div id="sdruchatMessages" class="sdruchat-messages" aria-live="polite">
            <div class="sdruchat-msg bot">Hello! I am the SDRU Assistant. How can I help you today?</div>
        </div>

        <div id="sdruchatSuggestions" class="sdruchat-suggestions">
            <button type="button" class="sdruchat-suggestion">How do I file a complaint?</button>
            <button type="button" class="sdruchat-suggestion">What are the requirements?</button>
            <button type="button" class="sdruchat-suggestion">How does the case process work?</button>
        </div>

        <div class="sdruchat-input">
            <textarea id="sdruchatInput" placeholder="Ask the SDRU Assistant..." aria-label="Chatbot question"></textarea>
            <button type="button" id="sdruchatSend" class="sdruchat-send" aria-label="Send question">&#10148;</button>
        </div>
    </section>

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
                <p>When creating an account, you agree to provide truthful registration information, maintain the security of your password and account, and promptly notify SDRU of any unauthorized use of your account. SDRU reserves the right to suspend or terminate accounts that violate these terms.</p>
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
                <p>Users shall not use the system for any unlawful purpose, impersonate another person, submit knowingly false complaints, interfere with the proper operation of the system, or attempt to circumvent system security measures.</p>
                <h3>9. Intellectual Property</h3>
                <p>All content, design, and functionality of SICMS are the property of CLSU and are protected by applicable intellectual property laws.</p>
                <h3>10. Limitation of Liability</h3>
                <p>CLSU and SDRU shall not be held liable for any indirect, incidental, or consequential damages arising from the use of or inability to use SICMS.</p>
                <h3>11. Modifications to Terms</h3>
                <p>SDRU reserves the right to modify these Terms of Service at any time. Continued use of SICMS after changes constitutes acceptance of the modified terms.</p>
                <h3>12. Contact Information</h3>
                <p>For questions or concerns, please contact the Student Discipline and Reformation Unit through the messaging feature in SICMS or visit the SDRU office during business hours.</p>
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
                <p>The Student Discipline and Reformation Unit (SDRU) of CLSU is committed to protecting the privacy of users of the Student Complaint Management System (SICMS).</p>
                <h3>2. Information We Collect</h3>
                <ul>
                    <li><strong>Account Information:</strong> Full name, email address, role, and account creation date.</li>
                    <li><strong>Complaint Data:</strong> Details submitted in complaint forms, including descriptions, evidence, and related communications.</li>
                    <li><strong>System Activity:</strong> Login timestamps, actions performed within the system, and audit log entries.</li>
                    <li><strong>Device Information:</strong> IP address and browser user-agent string for security and audit purposes.</li>
                </ul>
                <h3>3. How We Use Your Information</h3>
                <ul>
                    <li>To process and manage student complaints and disciplinary cases.</li>
                    <li>To authenticate users and manage account access.</li>
                    <li>To send notifications related to case updates and system activity.</li>
                    <li>To maintain audit logs for security and accountability.</li>
                    <li>To generate anonymized reports for institutional planning.</li>
                    <li>To comply with institutional policies and regulatory requirements.</li>
                </ul>
                <h3>4. Information Sharing</h3>
                <p>Your personal information is shared only with authorized SDRU personnel directly involved in processing your complaint, system administrators, and institutional authorities when required by policy or law. We do not sell, rent, or share your personal information with third parties for commercial purposes.</p>
                <h3>5. Data Security</h3>
                <p>We implement industry-standard security measures including encrypted password storage (bcrypt), CSRF protection, role-based access control, secure session management, and audit logging of all system activities.</p>
                <h3>6. Data Retention</h3>
                <p>Account and complaint data are retained for the duration of the user's association with CLSU and for a period required by institutional records retention policies.</p>
                <h3>7. Your Rights</h3>
                <p>As a user of SICMS, you have the right to access your personal information, request corrections, request account deletion (subject to retention requirements), and receive case status notifications.</p>
                <h3>8. Cookies and Session Data</h3>
                <p>SICMS uses session cookies to maintain your logged-in state. These cookies are temporary and automatically deleted when you log out.</p>
                <h3>9. Children's Privacy</h3>
                <p>SICMS is designed for college students 18 years of age or older. We do not knowingly collect personal information from individuals under 18.</p>
                <h3>10. Changes to This Policy</h3>
                <p>We may update this Privacy Policy from time to time. Significant changes will be communicated through the system.</p>
                <h3>11. Contact Information</h3>
                <p>For questions regarding your personal data or this Privacy Policy, please contact the SDRU through the messaging feature in SICMS or visit the SDRU office.</p>
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

    <script>
        window.SDRU_CHAT_API = <?= json_encode(app_base_path() . '/web/chatbot/api/chat.php') ?>;
    </script>
    <script src="../layout/login-chatbot.js"></script>
</body>

</html>
