<?php

session_start();

require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query(
    'SELECT
        id,
        type,
        item_name,
        category,
        description,
        location,
        item_date,
        image,
        status,
        created_at
     FROM items
     WHERE status = "active"
     ORDER BY created_at DESC'
);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Lost & Found Items</title>

</head>

<body>

    <h1>College Lost & Found</h1>

    <h2>Lost & Found Items</h2>


    <?php if (count($items) === 0): ?>

        <p>
            No active items found.
        </p>

    <?php else: ?>


        <?php foreach ($items as $item): ?>

            <article>

                <?php if (!empty($item['image'])): ?>

                    <img
                        src="/uploads/<?= htmlspecialchars($item['image']) ?>"
                        alt="<?= htmlspecialchars($item['item_name']) ?>"
                        width="200"
                    >

                    <br>

                <?php endif; ?>


                <h3>
                    <?= htmlspecialchars($item['item_name']) ?>
                </h3>


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


                <?php if (!empty($item['description'])): ?>

                    <p>
                        <strong>Description:</strong>
                        <?= htmlspecialchars($item['description']) ?>
                    </p>

                    <p>
                        <a href="/item.php?id=<?= (int) $item['id'] ?>">
                             View Details
                        </a>
                    </p>

                <?php endif; ?>


                <hr>

            </article>

        <?php endforeach; ?>


    <?php endif; ?>

</body>

</html>