<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/navigation.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid request. Please try again.');
    }

    $email = trim($_POST['email'] ?? '');

    // This response is deliberately the same whether or not the account exists.
    $message = 'If that email is registered, a password-reset link has been sent.';

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $pdo->prepare('DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL')
                ->execute([$user['id']]);

            $stmt = $pdo->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
            );
            $stmt->execute([$user['id'], $tokenHash]);

            $appUrl = rtrim($env['APP_URL'] ?? '', '/');
            $fromAddress = $env['MAIL_FROM'] ?? '';

            if (filter_var($appUrl, FILTER_VALIDATE_URL) && filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
                $resetUrl = $appUrl . '/reset-password.php?token=' . rawurlencode($token);
                $subject = 'Reset your College Lost & Found password';
                $body = "Hello {$user['name']},\n\n"
                    . "Use this link to set a new password:\n{$resetUrl}\n\n"
                    . "This link expires in one hour and can only be used once.\n"
                    . "If you did not request this, you can ignore this email.";
                $headers = "From: {$fromAddress}\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n";

                if (!mail($user['email'], $subject, $body, $headers)) {
                    error_log('Password-reset email could not be sent.');
                }
            } else {
                error_log('Password-reset email not sent: APP_URL or MAIL_FROM is not configured.');
            }
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
    <title>Forgot Password - College Lost & Found</title>
</head>
<body>
    <?php renderNavigation(); ?>

    <h1>College Lost & Found</h1>
    <h2>Forgot Password</h2>

    <p>Enter your registered email address and we will send a reset link.</p>

    <?php if ($message !== ''): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

        <label for="email">Email</label><br>
        <input type="email" id="email" name="email" required autocomplete="email"><br><br>

        <button type="submit">Send Reset Link</button>
    </form>

    <p><a href="/login.php">Back to Login</a></p>
</body>
</html>
