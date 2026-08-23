<?php

session_start();

require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/navigation.php';

requireAdmin();


$lostItemId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$lostItemId || $lostItemId < 1) {
    exit('Invalid lost report ID.');
}


// Get the LOST report
$stmt = $pdo->prepare(
    'SELECT
        id,
        item_name,
        category,
        description,
        location,
        item_date,
        status
     FROM items
     WHERE id = ?
       AND type = "lost"
     LIMIT 1'
);

$stmt->execute([$lostItemId]);

$lostItem = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$lostItem) {
    exit('Lost report not found.');
}


// Find active FOUND reports
// with the same category and item name
$stmt = $pdo->prepare(
    'SELECT
        id,
        item_name,
        category,
        description,
        location,
        item_date,
        status
     FROM items
     WHERE type = "found"
       AND status = "active"
       AND category = ?
       AND LOWER(TRIM(item_name)) = LOWER(TRIM(?))
     ORDER BY created_at DESC'
);

$stmt->execute([
    $lostItem['category'],
    $lostItem['item_name']
]);

$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <?php renderPageAssets(); ?>

    <meta charset="UTF-8">

    <title>Possible Matches</title>

</head>

<body>

    <?php renderNavigation(); ?>

    <h1>College Lost & Found</h1>

    <h2>Possible Matches</h2>


    <h3>Lost Report</h3>

    <p>
        <strong>Item:</strong>
        <?= htmlspecialchars($lostItem['item_name']) ?>
    </p>

    <p>
        <strong>Category:</strong>
        <?= htmlspecialchars($lostItem['category']) ?>
    </p>

    <p>
        <strong>Location:</strong>
        <?= htmlspecialchars($lostItem['location']) ?>
    </p>


    <hr>


    <h3>Matching Found Reports</h3>


    <?php if (count($matches) === 0): ?>

        <p>
            No possible matches found.
        </p>

    <?php else: ?>

        <?php foreach ($matches as $match): ?>

            <article>

                <p>
                    <strong>Item:</strong>
                    <?= htmlspecialchars($match['item_name']) ?>
                </p>

                <p>
                    <strong>Category:</strong>
                    <?= htmlspecialchars($match['category']) ?>
                </p>

                <p>
                    <strong>Location:</strong>
                    <?= htmlspecialchars($match['location']) ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?= htmlspecialchars($match['item_date']) ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <?= htmlspecialchars($match['status']) ?>
                </p>

                <p>
                    <a href="/item.php?id=<?= (int) $match['id'] ?>">
                        View Found Report
                    </a>

                    <form
    method="POST"
    action="/admin/create-match.php"
>

    <input
        type="hidden"
        name="lost_item_id"
        value="<?= (int) $lostItem['id'] ?>"
    >

    <input
        type="hidden"
        name="found_item_id"
        value="<?= (int) $match['id'] ?>"
    >

    <button type="submit">
        Select This Match
    </button>

</form>
                </p>

                <hr>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>


    <p>
        <a href="/admin/dashboard.php">
            Back to Admin Dashboard
        </a>
    </p>

</body>

</html>
