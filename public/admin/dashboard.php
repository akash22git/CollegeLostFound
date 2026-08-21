<?php

session_start();

require_once __DIR__ . '/../../app/auth.php';

requireAdmin();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard</title>

</head>

<body>

    <h1>College Lost & Found</h1>

    <h2>Admin Dashboard</h2>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION['user_name']) ?>!
    </p>

    <p>
        Role:
        <?= htmlspecialchars($_SESSION['user_role']) ?>
    </p>

    <hr>

    <h3>Admin Area</h3>

    <p>
        Only administrators can access this page.
    </p>

    <p>
        <a href="/dashboard.php">User Dashboard</a>
    </p>

    <p>
        <a href="/logout.php">Logout</a>
    </p>

</body>

</html>