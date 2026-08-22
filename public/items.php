<?php

require_once __DIR__ . '/../config/database.php';

$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$category = trim($_GET['category'] ?? '');
$location = trim($_GET['location'] ?? '');

$sql = '
    SELECT
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
    WHERE status = ?
';

$params = ['active'];

if ($search !== '') {

    $sql .= '
        AND (
            item_name LIKE ?
            OR description LIKE ?
            OR location LIKE ?
        )
    ';

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

if ($type !== '' && in_array($type, ['lost', 'found'], true)) {

    $sql .= ' AND type = ?';

    $params[] = $type;
}

if ($category !== '') {

    $sql .= ' AND category = ?';

    $params[] = $category;
}

if ($location !== '') {

    $sql .= ' AND location LIKE ?';

    $params[] = '%' . $location . '%';
}

$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

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

    <form method="GET">

    <label for="search">
        Search Item
    </label>
    <br>

    <input
        type="text"
        id="search"
        name="search"
        value="<?= htmlspecialchars($search) ?>"
        placeholder="Search item, description, or location"
    >

    <br><br>


    <label for="type">
        Type
    </label>
    <br>

    <select id="type" name="type">

        <option value="">
            All
        </option>

        <option
            value="lost"
            <?= $type === 'lost' ? 'selected' : '' ?>
        >
            Lost
        </option>

        <option
            value="found"
            <?= $type === 'found' ? 'selected' : '' ?>
        >
            Found
        </option>

    </select>

    <br><br>


    <label for="category">
        Category
    </label>
    <br>

    <select id="category" name="category">

        <option value="">
            All
        </option>

        <option
            value="Mobile"
            <?= $category === 'Mobile' ? 'selected' : '' ?>
        >
            Mobile
        </option>

        <option
            value="Wallet"
            <?= $category === 'Wallet' ? 'selected' : '' ?>
        >
            Wallet
        </option>

        <option
            value="ID Card"
            <?= $category === 'ID Card' ? 'selected' : '' ?>
        >
            ID Card
        </option>

        <option
            value="Book"
            <?= $category === 'Book' ? 'selected' : '' ?>
            >
            Book
        </option>

        <option
            value="Bag"
            <?= $category === 'Bag' ? 'selected' : '' ?>
        >
            Bag
        </option>

        <option
            value="Accessories"
            <?= $category === 'Accessories' ? 'selected' : '' ?>
        >
            Accessories
        </option>

        <option
            value="Other"
            <?= $category === 'Other' ? 'selected' : '' ?>
        >
            Other
        </option>

    </select>

    <br><br>

    <label for="location">
    Location
</label>
<br>

<input
    type="text"
    id="location"
    name="location"
    value="<?= htmlspecialchars($location) ?>"
    placeholder="Search by location"
>

<br><br>




    <button type="submit">
        Search
    </button>

    <a href="/items.php">
        Clear
    </a>

</form>

<p>
    <?= count($items) ?> item(s) found.
</p>    

<hr>


    <?php if (count($items) === 0): ?>

        <p>
            No items found matching your search.
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

</body>

</html>