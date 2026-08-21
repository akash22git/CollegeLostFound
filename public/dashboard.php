<?php

session_start();

require_once __DIR__ . '/../app/auth.php';

requireLogin();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard - College Lost & Found</title>

</head>

<body>

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
        <a href="/logout.php">Logout</a>
    </p>

</body>

</html>