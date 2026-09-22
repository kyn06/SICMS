<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
) {
    header('Content-Type: application/json; charset=utf-8');
    require_once __DIR__ . '/../../chatbot/config/chatbot.php';

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $message = trim((string)($data['message'] ?? ''));

    if ($message === '') {
        echo json_encode([
            'success' => true,
            'answer' => 'Please type a question so I can help you.',
            'suggestions' => Chatbot::defaultSuggestions()
        ]);
        exit;
    }

    $result = Chatbot::respond($message);
    echo json_encode(['success' => true] + $result);
    exit;
}

function app_base_path() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    return rtrim(preg_replace('#/web/views/auth/login\.php$#', '', $script), '/');
}

$LOGIN_BASE = app_base_path();

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

    $email = strtolower(trim($_POST['email']));
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
    <link rel="stylesheet" href="<?= $LOGIN_BASE ?>/web/views/layout/style.css">
    <style>
:root {
    --sicms-green-950: #0b2f16;
    --sicms-green-900: #123c1b;
    --sicms-green-700: #167a22;
    --sicms-green-600: #1A9D00;
    --sicms-green-100: #eaf7e8;
    --sicms-page: #f4f7f2;
    --sicms-surface: #ffffff;
    --sicms-line: #dbe7d8;
    --sicms-text: #172017;
    --sicms-muted: #5f6f5c;
}

.sdruchat-launcher {
    position: fixed;
    right: 28px;
    bottom: 28px;
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 218px;
    padding: 11px 13px 11px 11px;
    border: 1px solid rgba(255, 255, 255, .22);
    border-radius: 18px;
    background: linear-gradient(135deg, var(--sicms-green-900), #1d6328);
    color: #fff;
    font-family: 'Poppins', sans-serif;
    text-align: left;
    cursor: pointer;
    box-shadow: 0 12px 30px rgba(18, 60, 27, .22);
    transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
    animation: sdruchatFloat 3.5s ease-in-out infinite;
}

.sdruchat-launcher:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 36px rgba(18, 60, 27, .28);
    filter: brightness(1.04);
    animation-play-state: paused;
}

.sdruchat-launcher:focus-visible {
    outline: 3px solid rgba(26, 157, 0, .22);
    outline-offset: 3px;
}

.sdruchat-launcher-icon {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: #ffffff;
    color: var(--sicms-green-900);
    font-size: 19px;
    font-weight: 800;
    box-shadow: 0 5px 14px rgba(0, 0, 0, .12);
}

.sdruchat-launcher-copy {
    min-width: 0;
    flex: 1;
}

.sdruchat-launcher-copy strong {
    display: block;
    font-size: 13px;
    line-height: 1.2;
    letter-spacing: .01em;
}

.sdruchat-launcher-copy small {
    display: block;
    margin-top: 3px;
    color: rgba(255, 255, 255, .78);
    font-size: 10px;
    line-height: 1.25;
}

.sdruchat-launcher-arrow {
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, .11);
    color: #fff;
    font-size: 14px;
    transition: transform .18s ease, background .18s ease;
}

.sdruchat-launcher:hover .sdruchat-launcher-arrow {
    transform: translate(2px, -2px);
    background: rgba(255, 255, 255, .18);
}

@keyframes sdruchatFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-2px); }
}

.sdruchat-panel {
    position: fixed;
    right: 28px;
    bottom: 98px;
    width: min(400px, calc(100vw - 32px));
    height: min(610px, calc(100vh - 130px));
    z-index: 999;
    display: none;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--sicms-line);
    border-radius: 20px;
    background: var(--sicms-surface);
    box-shadow: 0 24px 70px rgba(11, 47, 22, .22);
    font-family: 'Poppins', sans-serif;
}

.sdruchat-panel.open {
    display: flex;
    animation: sdruchatOpen .18s ease-out;
}

@keyframes sdruchatOpen {
    from { opacity: 0; transform: translateY(8px) scale(.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.sdruchat-head {
    padding: 18px 18px 16px;
    background: linear-gradient(135deg, var(--sicms-green-900), #1d6328);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sdruchat-title {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.sdruchat-title strong {
    display: block;
    font-size: 14px;
    line-height: 1.2;
}

.sdruchat-avatar {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 14px;
    background: var(--sicms-green-100);
    color: var(--sicms-green-900);
    border: 1px solid rgba(255,255,255,.25);
    display: grid;
    place-items: center;
    font-size: 12px;
    font-weight: 800;
}

.sdruchat-head small {
    display: block;
    opacity: .82;
    margin-top: 3px;
    font-size: 10px;
    line-height: 1.25;
}

.sdruchat-close {
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 10px;
    background: rgba(255,255,255,.08);
    color: #fff;
    font-size: 23px;
    line-height: 1;
    cursor: pointer;
    transition: background .15s ease;
}

.sdruchat-close:hover {
    background: rgba(255,255,255,.16);
}

.sdruchat-notice {
    margin: 12px 12px 8px;
    padding: 11px 12px;
    border: 1px solid var(--sicms-line);
    border-radius: 12px;
    background: #f7faf6;
    color: var(--sicms-muted);
    font-size: 10.5px;
    line-height: 1.5;
}

.sdruchat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 6px 14px 14px;
    background: var(--sicms-page);
    scrollbar-width: thin;
}

.sdruchat-msg {
    max-width: 86%;
    margin: 10px 0;
    padding: 10px 12px;
    border: 1px solid transparent;
    border-radius: 13px;
    font-size: 12.5px;
    line-height: 1.55;
    white-space: pre-wrap;
}

.sdruchat-msg.bot {
    background: var(--sicms-green-100);
    color: var(--sicms-green-900);
    border-color: #d6e9d2;
    border-top-left-radius: 5px;
}

.sdruchat-msg.user {
    margin-left: auto;
    background: var(--sicms-green-900);
    color: #fff;
    border-top-right-radius: 5px;
}

.sdruchat-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    padding: 0 14px 10px;
    background: var(--sicms-page);
}

.sdruchat-suggestion {
    border: 1px solid #cddcc9;
    background: #fff;
    color: var(--sicms-green-900);
    border-radius: 999px;
    padding: 7px 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 10.5px;
    line-height: 1.3;
    cursor: pointer;
    transition: background .15s ease, border-color .15s ease, transform .15s ease;
}

.sdruchat-suggestion:hover {
    background: var(--sicms-green-100);
    border-color: #a9c7a4;
    transform: translateY(-1px);
}

.sdruchat-input {
    display: flex;
    gap: 8px;
    padding: 12px;
    border-top: 1px solid var(--sicms-line);
    background: #fff;
}

.sdruchat-input textarea {
    flex: 1;
    resize: none;
    min-height: 42px;
    max-height: 100px;
    border: 1px solid #b9cbb6;
    border-radius: 10px;
    padding: 10px 12px;
    outline: none;
    background: #fbfdfb;
    color: var(--sicms-text);
    font-family: 'Poppins', sans-serif;
    font-size: 12px;
}

.sdruchat-input textarea::placeholder {
    color: #7b8878;
}

.sdruchat-input textarea:focus {
    border-color: var(--sicms-green-600);
    box-shadow: 0 0 0 3px rgba(26, 157, 0, .10);
    background: #fff;
}

.sdruchat-send {
    width: 44px;
    flex: 0 0 44px;
    border: 0;
    border-radius: 10px;
    background: var(--sicms-green-600);
    color: #fff;
    font-size: 17px;
    cursor: pointer;
    box-shadow: 0 7px 16px rgba(26, 157, 0, .18);
    transition: background .15s ease, transform .1s ease;
}

.sdruchat-send:hover {
    background: var(--sicms-green-700);
}

.sdruchat-send:active {
    transform: scale(.96);
}

.sdruchat-typing {
    opacity: .6;
    font-style: italic;
}

@media (prefers-reduced-motion: reduce) {
    .sdruchat-launcher,
    .sdruchat-panel {
        animation: none;
    }
}

@media (max-width: 600px) {
    .sdruchat-launcher {
        right: 16px;
        bottom: 16px;
        min-width: 205px;
    }

    .sdruchat-panel {
        right: 16px;
        bottom: 86px;
        height: min(620px, calc(100vh - 105px));
        border-radius: 16px;
    }
}
    </style>
</head>

<body style="background-image:linear-gradient(rgba(11,47,22,.58),rgba(11,47,22,.66)),url('<?= htmlspecialchars($LOGIN_BASE, ENT_QUOTES) ?>/public/assets/bg_login.jpg');background-size:cover;background-position:center;background-repeat:no-repeat;background-attachment:fixed;">

    <!-- Cookie Consent Banner -->
    <div id="cookieConsent" class="cookie-banner" style="display:none;">
        <div class="cookie-banner-inner">
            <div class="cookie-banner-text">
                <div class="cookie-banner-title"><i class="bi bi-cookie"></i> Cookie Consent</div>
                <p>SICMS uses cookies to ensure the system works properly. These include:</p>
                <ul>
                    <li><strong>Session cookies</strong> &mdash; Keep you logged in and maintain your session state.</li>
                    <li><strong>CSRF tokens</strong> &mdash; Protect forms from cross-site request forgery attacks.</li>
                </ul>
                <p>These are strictly necessary for the system to function and are always active. No tracking or analytics cookies are currently used.</p>
                <p class="cookie-banner-note">Your preference is saved for 12 months. You can change it anytime by clearing your browser cookies.</p>
            </div>
            <div class="cookie-banner-actions">
                <button type="button" class="cookie-btn cookie-deny" onclick="setCookieConsent('denied')">Deny</button>
                <button type="button" class="cookie-btn cookie-accept" onclick="setCookieConsent('accepted')">Accept All</button>
            </div>
        </div>
    </div>

    <img class="seal" src="<?= $LOGIN_BASE ?>/public/assets/clsulogo.png" alt="clsu logo">
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

        <button type="submit" class="btn-login">Log In</button>

        <div class="divider">
            <span class="divider-line"></span>
            <span class="divider-text">or continue with</span>
            <span class="divider-line"></span>
        </div>

         <a href="google_signin.php" class="btn-google"> 
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
                <p>SICMS uses strictly necessary cookies to maintain your logged-in state and protect against cross-site request forgery. A cookie consent banner is displayed on your first visit to inform you of cookie usage and to record your preference. Your consent choice is stored in a cookie that expires after 12 months.</p>
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
        window.SDRU_CHAT_API = <?= json_encode($LOGIN_BASE . '/web/views/auth/login.php') ?>;
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const launcher = document.getElementById('sdruchatLauncher');
    const panel = document.getElementById('sdruchatPanel');
    const closeButton = document.getElementById('sdruchatClose');
    const form = document.getElementById('sdruchatForm');
    const input = document.getElementById('sdruchatInput');
    const sendButton = document.getElementById('sdruchatSend');
    const messages = document.getElementById('sdruchatMessages');
    const suggestions = document.getElementById('sdruchatSuggestions');

    if (!launcher || !panel || !input || !messages) {
        return;
    }

    let isSending = false;

    function openChat() {
        panel.classList.add('open');
        launcher.setAttribute('aria-expanded', 'true');

        setTimeout(function () {
            input.focus();
        }, 100);
    }

    function closeChat() {
        panel.classList.remove('open');
        launcher.setAttribute('aria-expanded', 'false');
    }

    launcher.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        if (panel.classList.contains('open')) {
            closeChat();
        } else {
            openChat();
        }
    });

    if (closeButton) {
        closeButton.addEventListener('click', function (event) {
            event.preventDefault();
            closeChat();
        });
    }

    function addMessage(text, sender) {
        const message = document.createElement('div');

        message.className =
            sender === 'user'
                ? 'sdruchat-msg user'
                : 'sdruchat-msg bot';

        message.textContent = text;

        messages.appendChild(message);
        messages.scrollTop = messages.scrollHeight;
    }

    function showTyping() {
        const typing = document.createElement('div');

        typing.id = 'sdruchatTyping';
        typing.className = 'sdruchat-msg bot sdruchat-typing';
        typing.textContent = 'Typing...';

        messages.appendChild(typing);
        messages.scrollTop = messages.scrollHeight;
    }

    function removeTyping() {
        const typing = document.getElementById('sdruchatTyping');

        if (typing) {
            typing.remove();
        }
    }

    async function sendMessage(textFromSuggestion = null) {
        const text = (
            textFromSuggestion !== null
                ? textFromSuggestion
                : input.value
        ).trim();

        if (!text || isSending) {
            return;
        }

        isSending = true;
        input.value = '';

        addMessage(text, 'user');
        showTyping();

        try {
            const apiUrl =
                window.SDRU_CHAT_API ||
                'login.php';

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: text
                })
            });

            const contentType = response.headers.get('content-type') || '';

            if (!contentType.includes('application/json')) {
                throw new Error('Unexpected response (' + response.status + '): ' + contentType);
            }

            const data = await response.json();

            removeTyping();

            if (data.success) {
                addMessage(data.answer, 'bot');

                if (suggestions && Array.isArray(data.suggestions)) {
                    suggestions.innerHTML = '';

                    data.suggestions.forEach(function (question) {
                        const button = document.createElement('button');

                        button.type = 'button';
                        button.className = 'sdruchat-suggestion';
                        button.textContent = question;

                        suggestions.appendChild(button);
                    });
                }
            } else {
                addMessage(
                    'Sorry, I could not process your question. Please try asking it another way.',
                    'bot'
                );
            }
        } catch (error) {
            console.error('Chatbot error:', error);

            removeTyping();

            addMessage(
                'Sorry, I am unable to answer at the moment. Please try again later.',
                'bot'
            );
        } finally {
            isSending = false;
        }
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            sendMessage();
        });
    }

    if (sendButton) {
        sendButton.addEventListener('click', function (event) {
            event.preventDefault();
            sendMessage();
        });
    }

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    if (suggestions) {
        suggestions.addEventListener('click', function (event) {
            const button =
                event.target.closest('.sdruchat-suggestion');

            if (!button || isSending) {
                return;
            }

            event.preventDefault();

            const question = button.textContent.trim();

            if (question) {
                sendMessage(question);
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && panel.classList.contains('open')) {
            closeChat();
        }
    });
});
    </script>

    <script>
    (function() {
        var banner = document.getElementById('cookieConsent');
        if (!banner) return;

        function getCookie(name) {
            var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return match ? match[2] : null;
        }

        if (!getCookie('sicms_cookie_consent')) {
            banner.style.display = 'block';
        }

        window.setCookieConsent = function(choice) {
            var date = new Date();
            date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
            document.cookie = 'sicms_cookie_consent=' + choice + ';expires=' + date.toUTCString() + ';path=/;SameSite=Lax';
            banner.style.display = 'none';
        };
    })();
    </script>
</body>

</html>
