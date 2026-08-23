<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/navigation.php';

$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$category = trim($_GET['category'] ?? '');
$location = trim($_GET['location'] ?? '');

$where = ['status = ?'];
$params = ['active'];

if ($search !== '') {

    $where[] = '(
            item_name LIKE ?
            OR description LIKE ?
            OR location LIKE ?
        )';

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

if ($type !== '' && in_array($type, ['lost', 'found'], true)) {

    $where[] = 'type = ?';

    $params[] = $type;
}

if ($category !== '') {

    $where[] = 'category = ?';

    $params[] = $category;
}

if ($location !== '') {

    $where[] = 'location LIKE ?';

    $params[] = '%' . $location . '%';
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE {$whereSql}");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();

$itemsPerPage = 10;
$totalPages = max(1, (int) ceil($totalItems / $itemsPerPage));
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$page = max(1, min($page, $totalPages));
$offset = ($page - 1) * $itemsPerPage;

$sql = "
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
    WHERE {$whereSql}
    ORDER BY created_at DESC
    LIMIT {$itemsPerPage} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

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

    <title>Lost & Found Items</title>

</head>

<body>

    <?php renderNavigation(); ?>

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
    <?= $totalItems ?> item(s) found.
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

    <?php if ($totalItems > $itemsPerPage): ?>

        <?php
        $paginationParams = array_filter([
            'search' => $search,
            'type' => $type,
            'category' => $category,
            'location' => $location,
        ], static fn ($value) => $value !== '');
        ?>

        <nav aria-label="Report pagination">
            <p>Page <?= $page ?> of <?= $totalPages ?></p>

            <?php if ($page > 1): ?>
                <a href="/items.php?<?= htmlspecialchars(http_build_query(array_merge($paginationParams, ['page' => $page - 1]))) ?>">
                    Previous
                </a>
            <?php endif; ?>

            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                <?php if ($pageNumber === $page): ?>
                    <strong><?= $pageNumber ?></strong>
                <?php else: ?>
                    <a href="/items.php?<?= htmlspecialchars(http_build_query(array_merge($paginationParams, ['page' => $pageNumber]))) ?>">
                        <?= $pageNumber ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="/items.php?<?= htmlspecialchars(http_build_query(array_merge($paginationParams, ['page' => $page + 1]))) ?>">
                    Next
                </a>
            <?php endif; ?>
        </nav>

    <?php endif; ?>

</body>

</html>
