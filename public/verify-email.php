<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/mailer.php';
require_once __DIR__ . '/../app/navigation.php';

// If no pending registration exists, send user back to register
if (!isset($_SESSION['pending_registration']) || !is_array($_SESSION['pending_registration'])) {
    header('Location: /register.php');
    exit;
}

$pending = &$_SESSION['pending_registration'];
$message = '';
$messageType = 'danger';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid request token. Please refresh and try again.');
    }

    $action = $_POST['action'] ?? 'verify';

    // 1. Resend Code Action
    if ($action === 'resend') {
        $now = time();
        if ($now < ($pending['resend_available_at'] ?? 0)) {
            $secondsLeft = $pending['resend_available_at'] - $now;
            $message = "Please wait {$secondsLeft} more seconds before requesting a new code.";
            $messageType = 'warning';
        } else {
            // Generate a fresh OTP
            $newOtp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $pending['otp'] = $newOtp;
            $pending['expires_at'] = time() + 600; // 10 minutes
            $pending['resend_available_at'] = time() + 30; // 30s cooldown
            $pending['attempts'] = 0;

            $subject = "Your New Verification Code: {$newOtp} - College Lost & Found";
            $bodyText = "Hello {$pending['name']},\n\nYour new 6-digit email verification code is: {$newOtp}\n\nThis code expires in 10 minutes.";
            $bodyHtml = "
                <div style='font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff;'>
                    <h2 style='color: #2563eb; margin-top: 0;'>College Lost &amp; Found</h2>
                    <p>Hello <strong>" . htmlspecialchars($pending['name']) . "</strong>,</p>
                    <p>Here is your new 6-digit verification code to complete your registration:</p>
                    <div style='text-align: center; margin: 28px 0;'>
                        <div style='display: inline-block; font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #1e293b; background: #f8fafc; padding: 14px 28px; border-radius: 8px; border: 2px dashed #3b82f6;'>
                            {$newOtp}
                        </div>
                    </div>
                    <p style='color: #64748b; font-size: 0.9rem;'>This code is valid for <strong>10 minutes</strong>.</p>
                </div>
            ";

            sendApplicationMail(
                $pending['email'],
                $pending['name'],
                $subject,
                $bodyHtml,
                $bodyText,
                $env
            );

            $message = 'A new 6-digit code has been sent to your email!';
            $messageType = 'success';
        }
    }

    // 2. Verify OTP Action
    elseif ($action === 'verify') {
        $enteredOtp = trim($_POST['otp'] ?? '');

        if ($enteredOtp === '') {
            $message = 'Please enter the 6-digit code from your email.';
            $messageType = 'danger';
        } elseif (time() > ($pending['expires_at'] ?? 0)) {
            $message = 'Your verification code has expired. Please click "Resend Code" below.';
            $messageType = 'danger';
        } elseif ($enteredOtp !== (string) ($pending['otp'] ?? '')) {
            $pending['attempts'] = ($pending['attempts'] ?? 0) + 1;
            if ($pending['attempts'] >= 5) {
                unset($_SESSION['pending_registration']);
                $_SESSION['flash_error'] = 'Too many failed verification attempts. Please start registration again.';
                header('Location: /register.php');
                exit;
            }
            $remaining = 5 - $pending['attempts'];
            $message = "Incorrect code. Please try again ({$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining).";
            $messageType = 'danger';
        } else {
            // Check once more whether email already exists (concurrency safety)
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$pending['email']]);
            if ($stmt->fetch()) {
                unset($_SESSION['pending_registration']);
                $message = 'An account with this email was already created. Please log in.';
                $messageType = 'danger';
            } else {
                // Insert verified user into database
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password, phone, role)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $pending['name'],
                    $pending['email'],
                    $pending['password'],
                    $pending['phone'],
                    'student'
                ]);

                $newUserId = (int) $pdo->lastInsertId();

                // Start authenticated session
                session_regenerate_id(true);
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $pending['name'];
                $_SESSION['user_email'] = $pending['email'];
                $_SESSION['user_role'] = 'student';

                unset($_SESSION['pending_registration']);

                header('Location: /dashboard.php?welcome=1');
                exit;
            }
        }
    }
}

// Local development simulation helper
$appUrl = $env['APP_URL'] ?? 'http://localhost:8000';
$isLocal = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php renderPageAssets(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - College Lost &amp; Found</title>
</head>
<body>
    <?php renderNavigation(); ?>

    <main class="page-container-sm">
        <div class="custom-card">
            <div class="page-header text-center mb-4">
                <div class="feature-icon feature-icon-found mx-auto mb-2" style="width: 54px; height: 54px; font-size: 1.5rem;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h1>Check Your Email</h1>
                <p class="page-subtitle">
                    We sent a 6-digit code to <br>
                    <strong class="text-primary" style="word-break: break-all;"><?= htmlspecialchars($pending['email']) ?></strong>
                </p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert-custom alert-<?= htmlspecialchars($messageType) ?> mb-3" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($isLocal && isset($pending['otp'])): ?>
                <div class="alert-custom alert-success mb-3" style="background: #f0fdf4; border-color: #86efac; color: #166534;" role="alert">
                    <i class="bi bi-laptop text-success"></i>
                    <div>
                        <strong>Local Testing Mode:</strong><br>
                        <span class="small">Your generated OTP is: <strong style="letter-spacing: 2px;"><?= htmlspecialchars($pending['otp']) ?></strong></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="action" value="verify">

                <div class="form-group mb-4">
                    <label for="otp" class="form-label text-center d-block">Enter 6-Digit Code</label>
                    <input
                        type="text"
                        id="otp"
                        name="otp"
                        class="form-control form-control-lg text-center fw-bold"
                        placeholder="••••••"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        minlength="6"
                        maxlength="6"
                        required
                        autofocus
                        autocomplete="one-time-code"
                        style="letter-spacing: 8px; font-size: 1.75rem;"
                    >
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg">
                    <i class="bi bi-check-circle-fill"></i> Verify &amp; Complete Sign Up
                </button>
            </form>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <form method="POST" class="d-inline" id="resendForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="btn btn-sm btn-outline-secondary" id="resendBtn">
                        <i class="bi bi-arrow-clockwise"></i> Resend Code
                    </button>
                </form>

                <a href="/register" class="text-sm text-muted" style="font-size: 0.9rem;">
                    <i class="bi bi-pencil-square"></i> Change Email
                </a>
            </div>
        </div>
    </main>

    <?php renderFooter(); ?>

    <script>
        // Automatic OTP input auto-focus & numeric filtering
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }
    </script>
</body>
</html>
