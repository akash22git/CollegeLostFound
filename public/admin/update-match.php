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

$matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';

if (!$matchId || $matchId < 1) {
    http_response_code(400);
    exit('Invalid match ID.');
}

// Fetch the match details
$stmt = $pdo->prepare('SELECT * FROM item_matches WHERE id = ? LIMIT 1');
$stmt->execute([$matchId]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    http_response_code(404);
    exit('Match record not found.');
}

if ($action === 'approve') {
    try {
        $pdo->beginTransaction();

        // 1. Update match status to approved
        $stmt = $pdo->prepare(
            'UPDATE item_matches
             SET status = "approved", resolved_by = ?
             WHERE id = ?'
        );
        $stmt->execute([$_SESSION['user_id'], $matchId]);

        // 2. Mark both lost and found items as resolved
        $stmt = $pdo->prepare(
            'UPDATE items
             SET status = "resolved"
             WHERE id IN (?, ?)'
        );
        $stmt->execute([$match['lost_item_id'], $match['found_item_id']]);

        $pdo->commit();
        header('Location: /admin/dashboard.php?msg=match_approved');
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Location: /admin/dashboard.php?msg=match_error');
        exit;
    }
} elseif ($action === 'reject') {
    $stmt = $pdo->prepare(
        'UPDATE item_matches
         SET status = "rejected", resolved_by = ?
         WHERE id = ?'
    );
    $stmt->execute([$_SESSION['user_id'], $matchId]);

    header('Location: /admin/dashboard.php?msg=match_rejected');
    exit;
} elseif ($action === 'delete') {
    $stmt = $pdo->prepare('DELETE FROM item_matches WHERE id = ?');
    $stmt->execute([$matchId]);

    header('Location: /admin/dashboard.php?msg=match_deleted');
    exit;
} else {
    http_response_code(400);
    exit('Invalid match action.');
}
