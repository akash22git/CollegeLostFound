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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - College Lost &amp; Found</title>
</head>

<body>
    <?php renderNavigation(); ?>

    <main class="page-container-sm">
        <div class="custom-card">
            <div class="page-header text-center mb-4">
                <h1>Welcome Back</h1>
                <p class="page-subtitle">Sign in to your College Lost &amp; Found account</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert-custom <?= str_contains($message, 'reset') ? 'alert-success' : 'alert-danger' ?>" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="e.g. yourname@college.edu"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label mb-0">Password</label>
                        <a href="/forgot-password.php" class="text-sm" style="font-size: 0.875rem;">Forgot Password?</a>
                    </div>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <p class="mb-0 text-muted" style="font-size: 0.95rem;">
                    Don't have an account? <a href="/register.php" class="fw-semibold">Create an account</a>
                </p>
            </div>
        </div>
    </main>
    <?php renderFooter(); ?>
</body>

</html>
