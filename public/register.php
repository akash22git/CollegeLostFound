<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/navigation.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Check required fields
    if ($name === '' || $email === '' || $phone === '' || $password === '') {
        $message = 'Please fill in all required fields.';
    }

    // Check email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    }

    // Require a 10-digit contact number for item claims.
    elseif (!preg_match('/^\d{10}$/', $phone)) {
        $message = 'Phone number must contain exactly 10 digits.';
    }

    // Check password length
    elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
    }

    // Check passwords match
    elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
    }

    else {

        // Check whether email already exists
        $stmt = $pdo->prepare(
            'SELECT id FROM users WHERE email = ? LIMIT 1'
        );

        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $message = 'An account with this email already exists.';
        } else {

            // Hash password
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert user
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password, phone)
                 VALUES (?, ?, ?, ?)'
            );

            $stmt->execute([
                $name,
                $email,
                $hashedPassword,
                $phone
            ]);

            $message = 'Registration successful!';
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
    <title>Register - College Lost &amp; Found</title>
</head>

<body>
    <?php renderNavigation(); ?>

    <main class="page-container-sm">
        <div class="custom-card">
            <div class="page-header text-center mb-4">
                <h1>Create Account</h1>
                <p class="page-subtitle">Join the campus Lost &amp; Found community</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert-custom <?= str_contains($message, 'successful') ? 'alert-success' : 'alert-danger' ?>" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        placeholder="e.g. Ram Harsh"
                        required
                        autocomplete="name"
                    >
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="e.g. ram@college.edu"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number (10 digits)</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="form-control"
                        inputmode="numeric"
                        pattern="[0-9]{10}"
                        minlength="10"
                        maxlength="10"
                        placeholder="e.g. 9876543210"
                        title="Enter a 10-digit phone number"
                        required
                        autocomplete="tel"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password (min. 8 characters)</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Create a strong password"
                        minlength="8"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Repeat your password"
                        minlength="8"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3">
                    <i class="bi bi-person-plus-fill"></i> Create Account
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <p class="mb-0 text-muted" style="font-size: 0.95rem;">
                    Already have an account? <a href="/login.php" class="fw-semibold">Login</a>
                </p>
            </div>
        </div>
    </main>
    <?php renderFooter(); ?>
</body>

</html>
