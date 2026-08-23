<?php

require_once __DIR__ . '/../app/navigation.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php renderPageAssets(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Lost &amp; Found</title>
</head>
<body>
    <?php renderNavigation(); ?>

    <h1>College Lost &amp; Found</h1>
    <p>Report lost or found belongings and help return them to their owners.</p>

    <p>
        <a href="/items.php">Browse Lost &amp; Found Reports</a>
    </p>

    <?php if (isset($_SESSION['user_id'])): ?>
        <p>
            <a href="/lost-item.php">Report a Lost Item</a> |
            <a href="/found-item.php">Report a Found Item</a>
        </p>
    <?php else: ?>
        <p>
            <a href="/register.php">Create an Account</a> |
            <a href="/login.php">Login</a>
        </p>
    <?php endif; ?>
</body>
</html>
