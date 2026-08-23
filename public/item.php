<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/navigation.php';

$itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$itemId || $itemId < 1) {
    http_response_code(404);
    exit('Item not found.');
}

$stmt = $pdo->prepare(
    'SELECT
        items.id,
        items.type,
        items.item_name,
        items.category,
        items.description,
        items.location,
        items.item_date,
        items.image,
        items.status,
        items.created_at,
        users.name AS reporter_name,
        users.phone AS reporter_phone
     FROM items
     INNER JOIN users
        ON items.user_id = users.id
     WHERE items.id = ?
     LIMIT 1'
);

$stmt->execute([$itemId]);

$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    http_response_code(404);
    exit('Item not found.');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <?php renderPageAssets(); ?>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($item['item_name']) ?>
        - College Lost & Found
    </title>

</head>

<body>

    <?php renderNavigation(); ?>

    <h1>College Lost & Found</h1>

    <h2>
        <?= htmlspecialchars($item['item_name']) ?>
    </h2>

    <?php if (!empty($item['image'])): ?>

        <img
            src="/uploads/<?= htmlspecialchars($item['image']) ?>"
            alt="<?= htmlspecialchars($item['item_name']) ?>"
            width="400"
        >

        <br><br>

    <?php endif; ?>


    <p>
        <strong>Type:</strong>
        <?= htmlspecialchars(strtoupper($item['type'])) ?>
    </p>


    <p>
        <strong>Category:</strong>
        <?= htmlspecialchars($item['category']) ?>
    </p>


    <p>
        <strong>Location:</strong>
        <?= htmlspecialchars($item['location']) ?>
    </p>


    <p>
        <strong>Date:</strong>
        <?= htmlspecialchars($item['item_date']) ?>
    </p>


    <p>
        <strong>Status:</strong>
        <?= htmlspecialchars($item['status']) ?>
    </p>

    <?php if ($item['status'] === 'active' && !empty($item['reporter_phone'])): ?>

        <h3>Contact Reporter</h3>

        <p>
            If you have found or want to claim this item, contact
            <?= htmlspecialchars($item['reporter_name']) ?>:
            <a href="tel:<?= htmlspecialchars($item['reporter_phone']) ?>">
                <?= htmlspecialchars($item['reporter_phone']) ?>
            </a>
        </p>

    <?php endif; ?>


    <?php if (!empty($item['description'])): ?>
        <h3>Description</h3>

        <p>
            <?= nl2br(htmlspecialchars($item['description'])) ?>
        </p>

    <?php endif; ?>


    <p>
        <a href="/items.php">
            ← Back to all items
        </a>
    </p>

</body>

</html>
