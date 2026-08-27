<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid security token.');
}

$itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
$redirect = $_POST['redirect'] ?? 'my-reports';

if (!$itemId || $itemId < 1) {
    http_response_code(400);
    exit('Invalid item ID.');
}

// Check ownership or admin status
$stmt = $pdo->prepare('SELECT id, user_id, type, status FROM items WHERE id = ? LIMIT 1');
$stmt->execute([$itemId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    http_response_code(404);
    exit('Item report not found.');
}

$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$isOwner = (int) $item['user_id'] === (int) $_SESSION['user_id'];

if (!$isOwner && !$isAdmin) {
    http_response_code(403);
    exit('You do not have permission to resolve this report.');
}

// Mark item as resolved
$updateStmt = $pdo->prepare('UPDATE items SET status = "resolved" WHERE id = ?');
$updateStmt->execute([$itemId]);

// If there's an associated pending match for this item, mark it as approved
$matchStmt = $pdo->prepare(
    'UPDATE item_matches
     SET status = "approved", resolved_by = ?
     WHERE (lost_item_id = ? OR found_item_id = ?) AND status = "pending"'
);
$matchStmt->execute([$_SESSION['user_id'], $itemId, $itemId]);

if ($redirect === 'item') {
    header('Location: /item.php?id=' . $itemId . '&msg=resolved');
} else {
    header('Location: /my-reports.php?msg=resolved');
}
exit;
