<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/navigation.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$tokenHash = is_string($token) && ctype_xdigit($token) && strlen($token) === 64
    ? hash('sha256', $token)
    : null;
$message = '';
$validToken = false;

if ($tokenHash !== null) {
    $stmt = $pdo->prepare(
        'SELECT id, user_id
         FROM password_resets
         WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([$tokenHash]);
    $validToken = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid request. Please try again.';
    } elseif (!$validToken) {
        $message = 'This password-reset link is invalid or has expired.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT id, user_id
                 FROM password_resets
                 WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
                 LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$tokenHash]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                throw new RuntimeException('This password-reset link is invalid or has expired.');
            }

            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
                ->execute([$reset['id']]);
            $pdo->commit();

            header('Location: /login.php?reset=success');
            exit;
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = 'Unable to reset your password. Please request another link.';
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
    <title>Reset Password - College Lost &amp; Found</title>
</head>
<body>
    <?php renderNavigation(); ?>

    <main class="page-container-sm">
        <div class="custom-card">
            <div class="page-header text-center mb-4">
                <div class="feature-icon feature-icon-browse mx-auto mb-2" style="width: 50px; height: 50px; font-size: 1.4rem;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h1>Reset Password</h1>
                <p class="page-subtitle">Choose a new, secure password for your account</p>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert-custom alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="form-group">
                        <label for="password" class="form-label">New Password (min. 8 characters)</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter new password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Confirm new password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-2">
                        <i class="bi bi-check2-circle"></i> Save New Password
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center py-3">
                    <p class="text-danger fw-semibold">This password-reset link is invalid or has expired.</p>
                    <a href="/forgot-password.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-repeat"></i> Request a New Reset Link
                    </a>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4 pt-3 border-top">
                <a href="/login.php" class="text-muted small">
                    <i class="bi bi-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </main>
    <?php renderFooter(); ?>
</body>
</html>
