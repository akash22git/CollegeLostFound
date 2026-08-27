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

$lostItemId = filter_input(
    INPUT_POST,
    'lost_item_id',
    FILTER_VALIDATE_INT
);

$foundItemId = filter_input(
    INPUT_POST,
    'found_item_id',
    FILTER_VALIDATE_INT
);

if (!$lostItemId || !$foundItemId) {
    http_response_code(400);
    exit('Invalid item ID.');
}

if ($lostItemId === $foundItemId) {
    header('Location: /admin/find-matches.php?id=' . $lostItemId . '&error=same_item');
    exit;
}

// Verify LOST report
$stmt = $pdo->prepare(
    'SELECT id
     FROM items
     WHERE id = ?
       AND type = "lost"
       AND status = "active"
     LIMIT 1'
);

$stmt->execute([$lostItemId]);

if (!$stmt->fetch()) {
    header('Location: /admin/dashboard.php?error=invalid_lost_report');
    exit;
}

// Verify FOUND report
$stmt = $pdo->prepare(
    'SELECT id
     FROM items
     WHERE id = ?
       AND type = "found"
       AND status = "active"
     LIMIT 1'
);

$stmt->execute([$foundItemId]);

if (!$stmt->fetch()) {
    header('Location: /admin/find-matches.php?id=' . $lostItemId . '&error=invalid_found_report');
    exit;
}

// Create the possible match
$stmt = $pdo->prepare(
    'INSERT INTO item_matches
        (lost_item_id, found_item_id, status)
     VALUES
        (?, ?, "pending")'
);

try {
    $stmt->execute([
        $lostItemId,
        $foundItemId
    ]);

    header('Location: /admin/find-matches.php?id=' . $lostItemId . '&msg=match_created');
    exit;
} catch (PDOException $e) {
    header('Location: /admin/find-matches.php?id=' . $lostItemId . '&msg=match_exists');
    exit;
}