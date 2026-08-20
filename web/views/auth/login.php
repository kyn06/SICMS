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

    <script>
        window.SDRU_CHAT_API = <?= json_encode(app_base_path() . '/web/chatbot/api/chat.php') ?>;
    </script>
    <script src="../layout/login-chatbot.js"></script>
</body>

</html>
