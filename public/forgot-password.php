<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/mailer.php';
require_once __DIR__ . '/../app/navigation.php';

$message = '';
$devResetUrl = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid request. Please try again.');
    }

    $email = trim($_POST['email'] ?? '');

    // Standard secure message regardless of email existence to prevent user enumeration
    $message = 'If that email is registered, a password-reset link has been sent.';

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            // Invalidate any previous unused tokens for this user
            $pdo->prepare('DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL')
                ->execute([$user['id']]);

            // Save new 1-hour expiration token
            $stmt = $pdo->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
            );
            $stmt->execute([$user['id'], $tokenHash]);

            // Construct Reset URL
            $appUrl = rtrim($env['APP_URL'] ?? 'http://localhost:8000', '/');
            if (empty($appUrl) || $appUrl === 'http://localhost') {
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $appUrl = "{$scheme}://{$host}";
            }

            $resetUrl = $appUrl . '/reset-password.php?token=' . rawurlencode($token);

            // Prepare email content
            $subject = 'Reset your College Lost & Found password';
            $bodyText = "Hello {$user['name']},\n\n"
                . "Use this link to set a new password for your account:\n{$resetUrl}\n\n"
                . "This link expires in 1 hour and can only be used once.\n"
                . "If you did not request this, please ignore this email.";

            $bodyHtml = "
                <div style='font-family: Arial, sans-serif; max-width: 540px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                    <h2 style='color: #2563eb; margin-top: 0;'>College Lost &amp; Found</h2>
                    <p>Hello <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
                    <p>We received a request to reset your password. Click the button below to choose a new password:</p>
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='{$resetUrl}' style='background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Reset Password</a>
                    </div>
                    <p style='color: #64748b; font-size: 0.9rem;'>Or copy and paste this URL into your browser:<br><a href='{$resetUrl}'>{$resetUrl}</a></p>
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                    <p style='color: #94a3b8; font-size: 0.8rem; margin-bottom: 0;'>This link will expire in 1 hour and can only be used once. If you did not make this request, you can safely ignore this email.</p>
                </div>
            ";

            sendApplicationMail(
                $user['email'],
                $user['name'],
                $subject,
                $bodyHtml,
                $bodyText,
                $env
            );

            // Local development simulation helper (enabled on localhost / 127.0.0.1)
            $isLocal = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1');
            if ($isLocal) {
                $devResetUrl = $resetUrl;
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
    <title>Forgot Password - College Lost &amp; Found</title>
</head>
<body>
    <?php renderNavigation(); ?>

    <main class="page-container-sm">
        <div class="custom-card">
            <div class="page-header text-center mb-4">
                <div class="feature-icon feature-icon-browse mx-auto mb-2" style="width: 50px; height: 50px; font-size: 1.4rem;">
                    <i class="bi bi-key-fill"></i>
                </div>
                <h1>Forgot Password</h1>
                <p class="page-subtitle">Enter your registered email address and we'll send you a password recovery link.</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert-custom alert-info mb-3" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($devResetUrl !== null): ?>
                <div class="alert-custom alert-success mb-3" style="background: #f0fdf4; border-color: #86efac; color: #166534;" role="alert">
                    <i class="bi bi-laptop text-success"></i>
                    <div>
                        <strong>Local Testing Link Generated:</strong><br>
                        <span class="small">Since you are testing locally on localhost, here is your generated reset link:</span><br>
                        <a href="<?= htmlspecialchars($devResetUrl) ?>" class="btn btn-sm btn-success mt-2">
                            <i class="bi bi-arrow-right-circle me-1"></i> Open Reset Password Page
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

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

                <button type="submit" class="btn btn-primary w-100 mt-2">
                    <i class="bi bi-envelope-paper"></i> Send Reset Link
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <p class="mb-0 text-muted" style="font-size: 0.95rem;">
                    Remembered your password? <a href="/login.php" class="fw-semibold">Back to Login</a>
                </p>
            </div>
        </div>
    </main>
    <?php renderFooter(); ?>
</body>
</html>
