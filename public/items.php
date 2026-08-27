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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Reports - College Lost &amp; Found</title>
</head>

<body>
    <?php renderNavigation(); ?>

    <main class="page-container">
        <!-- Page Title -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h1 class="mb-1">Lost &amp; Found Catalog</h1>
                <p class="page-subtitle mb-0">Browse and search through active reports across campus</p>
            </div>
            <span class="badge-pill bg-white text-dark border shadow-sm px-3 py-2" style="font-size: 0.95rem;">
                <i class="bi bi-collection-fill text-primary me-1"></i> <?= $totalItems ?> Active Report(s)
            </span>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="filter-card mb-4">
            <form method="GET" class="p-0 bg-transparent border-0 shadow-none">
                <div class="row g-3">
                    <div class="col-lg-4 col-md-6">
                        <label for="search" class="form-label"><i class="bi bi-search me-1"></i> Keyword Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="Item name, description, etc."
                        >
                    </div>

                    <div class="col-lg-2 col-md-6 col-sm-6">
                        <label for="type" class="form-label"><i class="bi bi-tag me-1"></i> Report Type</label>
                        <select id="type" name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="lost" <?= $type === 'lost' ? 'selected' : '' ?>>Lost</option>
                            <option value="found" <?= $type === 'found' ? 'selected' : '' ?>>Found</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <label for="category" class="form-label"><i class="bi bi-folder me-1"></i> Category</label>
                        <select id="category" name="category" class="form-select">
                            <option value="">All Categories</option>
                            <option value="Mobile" <?= $category === 'Mobile' ? 'selected' : '' ?>>Mobile</option>
                            <option value="Wallet" <?= $category === 'Wallet' ? 'selected' : '' ?>>Wallet</option>
                            <option value="ID Card" <?= $category === 'ID Card' ? 'selected' : '' ?>>ID Card</option>
                            <option value="Book" <?= $category === 'Book' ? 'selected' : '' ?>>Book</option>
                            <option value="Bag" <?= $category === 'Bag' ? 'selected' : '' ?>>Bag</option>
                            <option value="Accessories" <?= $category === 'Accessories' ? 'selected' : '' ?>>Accessories</option>
                            <option value="Other" <?= $category === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label for="location" class="form-label"><i class="bi bi-geo-alt me-1"></i> Location</label>
                        <input
                            type="text"
                            id="location"
                            name="location"
                            class="form-control"
                            value="<?= htmlspecialchars($location) ?>"
                            placeholder="e.g. Library, Lab"
                        >
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                        <a href="/items.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Clear Filters
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel-fill"></i> Filter Reports
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Items Listing Grid -->
        <?php if (count($items) === 0): ?>
            <div class="custom-card text-center py-5">
                <i class="bi bi-inbox text-muted mb-3" style="font-size: 3rem; display: block;"></i>
                <h3>No Items Found</h3>
                <p class="text-muted">No reports match your current search filters.</p>
                <a href="/items.php" class="btn btn-outline-primary btn-sm mt-2">
                    <i class="bi bi-arrow-repeat"></i> Reset Search
                </a>
            </div>
        <?php else: ?>
            <div class="items-grid">
                <?php foreach ($items as $item): ?>
                    <article class="item-card">
                        <div class="item-card-image-wrap">
                            <div class="item-card-badge">
                                <span class="badge-pill <?= $item['type'] === 'lost' ? 'badge-lost' : 'badge-found' ?>">
                                    <i class="bi <?= $item['type'] === 'lost' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill' ?>"></i>
                                    <?= htmlspecialchars(strtoupper($item['type'])) ?>
                                </span>
                            </div>

                            <?php if (!empty($item['image'])): ?>
                                <img
                                    src="/uploads/<?= htmlspecialchars($item['image']) ?>"
                                    alt="<?= htmlspecialchars($item['item_name']) ?>"
                                    class="item-card-image"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <div class="item-card-image-placeholder">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="item-card-body">
                            <h2 class="item-card-title">
                                <?= htmlspecialchars($item['item_name']) ?>
                            </h2>

                            <ul class="item-meta-list">
                                <li class="item-meta-item">
                                    <i class="bi bi-tag-fill text-primary"></i>
                                    <span><?= htmlspecialchars($item['category']) ?></span>
                                </li>
                                <li class="item-meta-item">
                                    <i class="bi bi-geo-alt-fill text-danger"></i>
                                    <span><?= htmlspecialchars($item['location']) ?></span>
                                </li>
                                <li class="item-meta-item">
                                    <i class="bi bi-calendar-event text-secondary"></i>
                                    <span><?= htmlspecialchars($item['item_date']) ?></span>
                                </li>
                            </ul>

                            <?php if (!empty($item['description'])): ?>
                                <p class="text-muted" style="font-size: 0.925rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars($item['description']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="item-card-footer">
                                <a href="/item.php?id=<?= (int) $item['id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                                    View Full Details <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Modern Pagination -->
        <?php if ($totalItems > $itemsPerPage): ?>
            <?php
            $paginationParams = array_filter([
                'search' => $search,
                'type' => $type,
                'category' => $category,
                'location' => $location,
            ], static fn ($value) => $value !== '');
            ?>

            <nav aria-label="Report pagination" class="pagination-container">
                <?php if ($page > 1): ?>
                    <a
                        href="/items.php?<?= htmlspecialchars(http_build_query(array_merge($paginationParams, ['page' => $page - 1]))) ?>"
                        class="page-btn"
                        aria-label="Previous page"
                    >
                        <i class="bi bi-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                    <a
                        href="/items.php?<?= htmlspecialchars(http_build_query(array_merge($paginationParams, ['page' => $pageNumber]))) ?>"
                        class="page-btn <?= $pageNumber === $page ? 'active' : '' ?>"
                    >
                        <?= $pageNumber ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a
                        href="/items.php?<?= htmlspecialchars(http_build_query(array_merge($paginationParams, ['page' => $page + 1]))) ?>"
                        class="page-btn"
                        aria-label="Next page"
                    >
                        <i class="bi bi-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </main>
    <?php renderFooter(); ?>
</body>

</html>
