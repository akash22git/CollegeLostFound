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
    <title>Reset Password - College Lost & Found</title>
</head>
<body>
    <?php renderNavigation(); ?>

    <h1>College Lost & Found</h1>
    <h2>Reset Password</h2>

    <?php if ($message !== ''): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($validToken): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <label for="password">New Password</label><br>
            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"><br><br>

            <label for="confirm_password">Confirm New Password</label><br>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password"><br><br>

            <button type="submit">Reset Password</button>
        </form>
    <?php else: ?>
        <p>This password-reset link is invalid or has expired.</p>
        <p><a href="/forgot-password.php">Request a new reset link</a></p>
    <?php endif; ?>
</body>
</html>
