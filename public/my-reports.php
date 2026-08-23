<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/navigation.php';

requireLogin();

$stmt = $pdo->prepare(
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
     WHERE user_id = ?
     ORDER BY created_at DESC'
);

$stmt->execute([
    $_SESSION['user_id']
]);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    <title>My Reports</title>

</head>

<body>

    <?php renderNavigation(); ?>

    <h1>College Lost & Found</h1>

    <h2>My Reports</h2>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION['user_name']) ?>
    </p>


    <?php if (count($items) === 0): ?>

        <p>
            You have not submitted any reports yet.
        </p>

    <?php else: ?>

        <p>
            <?= count($items) ?> report(s) found.
        </p>


        <?php foreach ($items as $item): ?>

            <article>

                <?php if (!empty($item['image'])): ?>

                    <img
                        src="/uploads/<?= htmlspecialchars($item['image']) ?>"
                        alt="<?= htmlspecialchars($item['item_name']) ?>"
                        width="200"
                    >

                    <br><br>

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


                <p>
                    <strong>Status:</strong>
                    <?= htmlspecialchars($item['status']) ?>
                </p>


                <?php if (!empty($item['description'])): ?>

                    <p>
                        <strong>Description:</strong>
                        <?= htmlspecialchars($item['description']) ?>
                    </p>

                <?php endif; ?>


                <p>
                    <a href="/item.php?id=<?= (int) $item['id'] ?>">
                        View Details
                    </a>
                </p>

                <hr>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>


    <p>
        <a href="/items.php">
            View All Lost & Found Items
        </a>
    </p>

    <p>
        <a href="/lost-item.php">Report a Lost Item</a> |
        <a href="/found-item.php">Report a Found Item</a>
    </p>

</body>

</html>
