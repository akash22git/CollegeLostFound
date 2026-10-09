<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/mailer.php';
require_once __DIR__ . '/../app/navigation.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid request. Please refresh and try again.');
    }

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
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $message = 'An account with this email already exists. Please login instead.';
        } else {
            // Generate 6-digit verification code
            $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $_SESSION['pending_registration'] = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $hashedPassword,
                'otp' => $otp,
                'expires_at' => time() + 600, // 10 minutes
                'resend_available_at' => time() + 30, // 30 seconds cooldown
                'attempts' => 0
            ];

            // Send Verification Code via Email
            $subject = "Your Verification Code: {$otp} - College Lost & Found";
            $bodyText = "Hello {$name},\n\nYour 6-digit email verification code for College Lost & Found is: {$otp}\n\nThis code will expire in 10 minutes.\n\nIf you did not register for this account, please ignore this email.";

            $bodyHtml = "
                <div style='font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff;'>
                    <h2 style='color: #2563eb; margin-top: 0;'>College Lost &amp; Found</h2>
                    <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
                    <p>Thank you for signing up! Enter this 6-digit verification code to activate your account:</p>
                    <div style='text-align: center; margin: 28px 0;'>
                        <div style='display: inline-block; font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #1e293b; background: #f8fafc; padding: 14px 28px; border-radius: 8px; border: 2px dashed #3b82f6;'>
                            {$otp}
                        </div>
                    </div>
                    <p style='color: #64748b; font-size: 0.9rem; margin-bottom: 5px;'>This verification code is valid for <strong>10 minutes</strong>.</p>
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                    <p style='color: #94a3b8; font-size: 0.8rem; margin: 0;'>If you did not make this request, you can safely ignore this email.</p>
                </div>
            ";

            sendApplicationMail(
                $email,
                $name,
                $subject,
                $bodyHtml,
                $bodyText,
                $env
            );

            header('Location: /verify-email.php');
            exit;
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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
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
                    <div class="email-field-wrapper">
                        <div class="email-input-container">
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="e.g. ram@gmail.com"
                                required
                                autocomplete="email"
                            >
                            <span class="email-validation-icon" aria-hidden="true">
                                <i class="bi bi-check-circle-fill"></i>
                            </span>
                        </div>
                        <div class="email-typo-suggestion" style="display: none;"></div>
                    </div>
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
                        placeholder="e.g. 4500000098"
                        title="Enter a 10-digit phone number"
                        required
                        autocomplete="tel"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password (min. 8 characters)</label>
                    <div class="password-field-wrapper">
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
                        <button
                            type="button"
                            class="password-toggle-btn"
                            data-target="password"
                            aria-label="Show password"
                            tabindex="-1"
                        >
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-field-wrapper">
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
                        <button
                            type="button"
                            class="password-toggle-btn"
                            data-target="confirm_password"
                            aria-label="Show password"
                            tabindex="-1"
                        >
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3">
                    <i class="bi bi-person-plus-fill"></i> Create Account
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <p class="mb-0 text-muted" style="font-size: 0.95rem;">
                    Already have an account? <a href="/login" class="fw-semibold">Login</a>
                </p>
            </div>
        </div>
    </main>
    <?php renderFooter(); ?>
</body>

</html>
