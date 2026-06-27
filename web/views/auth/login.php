<?php
require '../layout/header.php';
require '../../config/Database.php';
require '../../models/User.php';

session_start([
    'cookie_lifetime' => 86400,
]);

$database = new Database();
$db = $database->getConnection();

User::setConnection($db);

if (isset($_SESSION['email'])) {
    header('Location: ../../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (User::login($email, $password)) {
        header('Location: ../../../index.php');
        exit;
    } else {
        $_SESSION['error'] = 'Invalid email or password.';
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
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../layout/style.css">
</head>

<body>

    <img class="seal" src="../../../public/assets/clsulogo.png" alt="clsu logo">
    <p class="org-name">Office of Student Affairs - Student<br>Discipline and Reformation Unit</p>
    <form class="form-wrap" action="login.php" method="POST">

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <input
            type="email"
            class="pill-input <?= isset($_SESSION['error']) ? 'is-invalid' : '' ?>"
            id="email"
            name="email"
            placeholder="Email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
        >

        <?php if (isset($_SESSION['error'])): ?>
            <p class="error-msg"><?= htmlspecialchars($_SESSION['error']) ?></p>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <input
            type="password"
            class="pill-input"
            id="password"
            name="password"
            placeholder="Password"
        >

        <a class="create-link" href="create_acc.php">Create an account.</a>  <!--ref wala pa --->

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

    <?php include '../layout/footer.php'; ?>

</body>

</html>