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
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $_SESSION['error'] = 'All fields are required.';
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
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@400;500&display=swap" rel="stylesheet">
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
    </style>
</head>

<body>

    <img class="seal" src="../../../public/assets/clsulogo.png" alt="CLSu logo">
    <p class="org-name">Office of Student Affairs - Student<br>Discipline and Reformation Unit</p>

    <form class="form-wrap" action="create_acc.php" method="POST">

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-banner">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
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

        <input
            type="password"
            class="pill-input"
            name="password"
            placeholder="Password"
        >

        <input
            type="password"
            class="pill-input"
            name="confirm_password"
            placeholder="Confirm password"
        >

        <a class="back-link" href="login.php">&#8592; Back to log in</a>

        <button type="submit" class="btn-login">Create Account</button>

    </form>

    <?php include '../layout/footer.php'; ?>

</body>

</html>