<?php

session_start();

require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdmin();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
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
    exit('Invalid item ID.');
}


if ($lostItemId === $foundItemId) {
    exit('Lost and found item cannot be the same report.');
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
    exit('Invalid lost report.');
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
    exit('Invalid found report.');
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

} catch (PDOException $e) {

    exit('This match may already exist.');
}


header(
    'Location: /admin/find-matches.php?id=' . $lostItemId
);

exit;