<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/navigation.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

// Fetch user's reports with match information if available
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
        matched_pair.match_id,
        matched_pair.match_status,
        matched_pair.matched_item_id,
        matched_pair.matched_item_name
     FROM items
     LEFT JOIN (
         SELECT
             im.id AS match_id,
             im.status AS match_status,
             im.lost_item_id AS my_item_id,
             found_i.id AS matched_item_id,
             found_i.item_name AS matched_item_name
         FROM item_matches im
         INNER JOIN items found_i ON im.found_item_id = found_i.id
         UNION ALL
         SELECT
             im.id AS match_id,
             im.status AS match_status,
             im.found_item_id AS my_item_id,
             lost_i.id AS matched_item_id,
             lost_i.item_name AS matched_item_name
         FROM item_matches im
         INNER JOIN items lost_i ON im.lost_item_id = lost_i.id
     ) AS matched_pair ON items.id = matched_pair.my_item_id
     WHERE items.user_id = ?
     ORDER BY items.created_at DESC'
);

$stmt->execute([$userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msgKey = $_GET['msg'] ?? '';
$alertText = '';
$alertClass = 'alert-success';

if ($msgKey === 'resolved') {
    $alertText = 'Item marked as resolved! It has been removed from the public search catalog.';
    $alertClass = 'alert-success';
} elseif ($msgKey === 'deleted') {
    $alertText = 'Report successfully removed from your personal history.';
    $alertClass = 'alert-info';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php renderPageAssets(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports - College Lost &amp; Found</title>
</head>

<body>
    <?php renderNavigation(); ?>

    <main class="page-container">
        <!-- Page Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h1 class="mb-1">My Reports &amp; Submissions</h1>
                <p class="page-subtitle mb-0">
                    Welcome back, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>! Track, update, and manage your reported items.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="/lost-item.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Report Lost
                </a>
                <a href="/found-item.php" class="btn btn-success btn-sm">
                    <i class="bi bi-plus-circle"></i> Report Found
                </a>
            </div>
        </div>

        <?php if ($alertText !== ''): ?>
            <div class="alert-custom <?= $alertClass ?> mb-4" role="alert">
                <i class="bi bi-info-circle-fill"></i>
                <span><?= htmlspecialchars($alertText) ?></span>
            </div>
        <?php endif; ?>

        <?php if (count($items) === 0): ?>
            <div class="custom-card text-center py-5">
                <i class="bi bi-folder2-open text-muted mb-3" style="font-size: 3.5rem; display: block;"></i>
                <h3>No Reports Submitted Yet</h3>
                <p class="text-muted mb-4">You have not submitted any lost or found item reports.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="/lost-item.php" class="btn btn-outline-primary">
                        <i class="bi bi-exclamation-diamond"></i> Report a Lost Item
                    </a>
                    <a href="/found-item.php" class="btn btn-outline-success">
                        <i class="bi bi-check-circle"></i> Report a Found Item
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="items-grid">
                <?php foreach ($items as $item): ?>
                    <article class="item-card">
                        <div class="item-card-image-wrap">
                            <div class="item-card-badge d-flex gap-1">
                                <span class="badge-pill <?= $item['type'] === 'lost' ? 'badge-lost' : 'badge-found' ?>">
                                    <i class="bi <?= $item['type'] === 'lost' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill' ?>"></i>
                                    <?= htmlspecialchars(strtoupper($item['type'])) ?>
                                </span>
                                <span class="badge-pill badge-<?= strtolower($item['status']) ?>">
                                    <?= htmlspecialchars(ucfirst($item['status'])) ?>
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
                            <h2 class="item-card-title mb-2">
                                <?= htmlspecialchars($item['item_name']) ?>
                            </h2>

                            <?php if (!empty($item['matched_item_id'])): ?>
                                <div class="p-2 bg-success-subtle rounded text-success small mb-3 border border-success-subtle">
                                    <i class="bi bi-stars text-success me-1"></i>
                                    <strong>Match Linked:</strong>
                                    <a href="/item.php?id=<?= (int) $item['matched_item_id'] ?>" class="text-success fw-bold text-decoration-underline ms-1">
                                        <?= htmlspecialchars($item['matched_item_name']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <ul class="item-meta-list">
                                <li class="item-meta-item">
                                    <i class="bi bi-folder-fill text-primary"></i>
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

                            <div class="item-card-footer d-flex flex-column gap-2 mt-auto">
                                <a href="/item.php?id=<?= (int) $item['id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                                    View Details <i class="bi bi-arrow-right"></i>
                                </a>

                                <?php if ($item['status'] === 'active'): ?>
                                    <form method="POST" action="/resolve-item.php" class="w-100 m-0">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                        <input type="hidden" name="redirect" value="my-reports">
                                        <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('Mark this report as resolved/recovered?');">
                                            <i class="bi bi-check2-circle"></i> <?= $item['type'] === 'lost' ? 'I Found My Item' : 'Mark as Handed Over' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="/delete-item.php" class="w-100 m-0">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                        <input type="hidden" name="redirect" value="my-reports">
                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100" onclick="return confirm('Delete this resolved report from your history?');">
                                            <i class="bi bi-trash"></i> Delete from History
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php renderFooter(); ?>
</body>

</html>
