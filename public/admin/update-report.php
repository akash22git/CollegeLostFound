<?php

session_start();

require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdmin();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}


$itemId = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

$action = $_POST['action'] ?? '';


if (!$itemId || $itemId < 1) {
    exit('Invalid report ID.');
}


$allowedActions = [
    'resolve' => 'resolved',
    'reject' => 'rejected'
];


if (!isset($allowedActions[$action])) {
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


header('Location: /admin/dashboard.php');

exit;