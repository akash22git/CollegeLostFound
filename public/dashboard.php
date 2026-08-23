<?php

session_start();

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/navigation.php';

requireLogin();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <?php renderPageAssets(); ?>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard - College Lost & Found</title>

</head>

<body>

    <?php renderNavigation(); ?>

    <h1>College Lost & Found</h1>

    <h2>Dashboard</h2>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION['user_name']) ?>!
    </p>

    <p>
        Email:
        <?= htmlspecialchars($_SESSION['user_email']) ?>
    </p>

    <p>
        Role:
        <?= htmlspecialchars($_SESSION['user_role']) ?>
    </p>

    <p>
        <a href="/lost-item.php">Report a Lost Item</a>
    </p>

    <p>
        <a href="/found-item.php">Report a Found Item</a>
    </p>

    <p>
        <a href="/my-reports.php">View My Reports</a>
    </p>

    <p>
        <a href="/items.php">Browse All Reports</a>
    </p>

</body>

</html>
