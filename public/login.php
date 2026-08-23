<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/navigation.php';

$message = '';

if (($_GET['reset'] ?? '') === 'success') {
    $message = 'Your password has been reset. Please log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $message = 'Please enter your email and password.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = 'Please enter a valid email address.';

    } else {

        $stmt = $pdo->prepare(
            'SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: /admin/dashboard.php');
                exit;
            }

            header('Location: /dashboard.php');
            exit;

        } else {

            $message = 'Invalid email or password.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <?php renderPageAssets(); ?>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login - College Lost & Found</title>

</head>

<body>

    <?php renderNavigation(); ?>

    <h1>College Lost & Found</h1>

    <h2>Login</h2>

    <?php if ($message !== ''): ?>

        <p>
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>

    <form method="POST">

        <label for="email">
            Email
        </label>
        <br>

        <input
            type="email"
            id="email"
            name="email"
            required
        >

        <br><br>

        <label for="password">
            Password
        </label>
        <br>

        <input
            type="password"
            id="password"
            name="password"
            required
        >

        <br><br>

        <button type="submit">
            Login
        </button>

    </form>

    <p>
        <a href="/forgot-password.php">Forgot Password?</a>
    </p>

    <p>
        <a href="/register.php">Create an account</a>
    </p>

</body>

</html>
