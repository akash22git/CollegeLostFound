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
$stmt = $pdo->prepare('SELECT id, user_id, image FROM items WHERE id = ? LIMIT 1');
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
    exit('You do not have permission to delete this report.');
}

// Clean up uploaded image if exists
if (!empty($item['image'])) {
    $imageFilePath = __DIR__ . '/uploads/' . basename($item['image']);
    if (file_exists($imageFilePath)) {
        @unlink($imageFilePath);
    }
}

// Delete item (database cascade deletes matches)
$deleteStmt = $pdo->prepare('DELETE FROM items WHERE id = ?');
$deleteStmt->execute([$itemId]);

if ($redirect === 'admin') {
    header('Location: /admin/dashboard.php?msg=item_deleted');
} else {
    header('Location: /my-reports.php?msg=deleted');
}
exit;
