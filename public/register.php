<?php

require_once __DIR__ . '/../config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Check required fields
    if ($name === '' || $email === '' || $password === '') {
        $message = 'Please fill in all required fields.';
    }

    // Check email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
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

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Register - College Lost & Found</title>

</head>

<body>

    <h1>College Lost & Found</h1>

    <h2>Create Account</h2>

    <?php if ($message !== ''): ?>

        <p>
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <label for="name">
            Name
        </label>
        <br>

        <input
            type="text"
            id="name"
            name="name"
            required
        >

        <br><br>


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


        <label for="phone">
            Phone
        </label>
        <br>

        <input
            type="tel"
            id="phone"
            name="phone"
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


        <label for="confirm_password">
            Confirm Password
        </label>
        <br>

        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            required
        >

        <br><br>


        <button type="submit">
            Create Account
        </button>

    </form>

</body>

</html>