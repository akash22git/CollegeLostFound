<?php

function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();

    if ($_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        exit('Access denied.');
    }
}