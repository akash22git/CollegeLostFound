<?php

session_start();

require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/csrf.php';
require_once __DIR__ . '/../../config/database.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid security token.');
}

$itemId = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

$action = $_POST['action'] ?? '';

if (!$itemId || $itemId < 1) {
    http_response_code(400);
    exit('Invalid report ID.');
}

$allowedActions = [
    'resolve' => 'resolved',
    'reject' => 'rejected'
];

if (!isset($allowedActions[$action])) {
    http_response_code(400);
    exit('Invalid action.');
}

$newStatus = $allowedActions[$action];

$stmt = $pdo->prepare(
    'UPDATE items
     SET status = ?
     WHERE id = ?'
);

$stmt->execute([
    $newStatus,
    $itemId
]);

header('Location: /admin/dashboard.php?msg=report_' . $newStatus);
exit;