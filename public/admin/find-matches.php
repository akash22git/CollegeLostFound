<?php

session_start();

require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/csrf.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/navigation.php';

requireAdmin();

$lostItemId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$lostItemId || $lostItemId < 1) {
    header('Location: /admin/dashboard.php?error=invalid_id');
    exit;
}

// Get the LOST report
$stmt = $pdo->prepare(
    'SELECT
        items.id,
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
     INNER JOIN users ON items.user_id = users.id
     WHERE items.id = ?
       AND items.type = "lost"
     LIMIT 1'
);

$stmt->execute([$lostItemId]);
$lostItem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lostItem) {
    header('Location: /admin/dashboard.php?error=lost_not_found');
    exit;
}

// Fetch existing matches for this lost item
$stmt = $pdo->prepare(
    'SELECT found_item_id, status
     FROM item_matches
     WHERE lost_item_id = ?'
);
$stmt->execute([$lostItemId]);
$existingMatches = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Find active FOUND reports with the same category (ordering exact name matches first)
$stmt = $pdo->prepare(
    'SELECT
        items.id,
        items.item_name,
        items.category,
        items.description,
        items.location,
        items.item_date,
        items.image,
        items.status,
        items.created_at,
        users.name AS reporter_name
     FROM items
     INNER JOIN users ON items.user_id = users.id
     WHERE items.type = "found"
       AND items.status = "active"
       AND items.category = ?
     ORDER BY (LOWER(TRIM(items.item_name)) = LOWER(TRIM(?))) DESC, items.created_at DESC'
);

$stmt->execute([
    $lostItem['category'],
    $lostItem['item_name']
]);

$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$messageType = 'info';

if (($_GET['msg'] ?? '') === 'match_created') {
    $message = 'Match proposal created successfully. You can manage and approve it from the Admin Dashboard.';
    $messageType = 'success';
} elseif (($_GET['msg'] ?? '') === 'match_exists') {
    $message = 'A match between these items has already been recorded.';
    $messageType = 'warning';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php renderPageAssets(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Discovery - College Lost &amp; Found</title>
</head>

<body>
    <?php renderNavigation(); ?>

    <main class="page-container">
        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h1 class="mb-1">Match Discovery Tool</h1>
                <p class="page-subtitle mb-0">Cross-referencing Lost report #<?= (int) $lostItem['id'] ?> against active Found items</p>
            </div>
            <a href="/admin/dashboard.php" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert-custom alert-<?= htmlspecialchars($messageType) ?> mb-4" role="alert">
                <i class="bi bi-info-circle-fill"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Target Lost Item Card -->
        <div class="custom-card mb-4 border-primary">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-pill badge-lost">
                        <i class="bi bi-exclamation-circle-fill"></i> Lost Report (#<?= (int) $lostItem['id'] ?>)
                    </span>
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($lostItem['category']) ?></span>
                    <span class="badge-pill badge-<?= strtolower($lostItem['status']) ?>">
                        <?= htmlspecialchars(ucfirst($lostItem['status'])) ?>
                    </span>
                </div>
                <span class="text-muted small">
                    Reported by: <strong><?= htmlspecialchars($lostItem['reporter_name']) ?></strong>
                </span>
            </div>

            <div class="row g-4 align-items-center">
                <?php if (!empty($lostItem['image'])): ?>
                    <div class="col-md-3 text-center">
                        <img
                            src="/uploads/<?= htmlspecialchars($lostItem['image']) ?>"
                            alt="<?= htmlspecialchars($lostItem['item_name']) ?>"
                            class="img-fluid rounded-3 shadow-sm"
                            style="max-height: 140px; object-fit: cover;"
                        >
                    </div>
                <?php endif; ?>

                <div class="<?= !empty($lostItem['image']) ? 'col-md-9' : 'col-12' ?>">
                    <h2 class="h3 mb-2"><?= htmlspecialchars($lostItem['item_name']) ?></h2>
                    <div class="row g-2 mb-2 text-muted small">
                        <div class="col-sm-6">
                            <span><i class="bi bi-geo-alt text-danger me-1"></i> <strong>Location:</strong> <?= htmlspecialchars($lostItem['location']) ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span><i class="bi bi-calendar-event text-secondary me-1"></i> <strong>Date Lost:</strong> <?= htmlspecialchars($lostItem['item_date']) ?></span>
                        </div>
                    </div>
                    <?php if (!empty($lostItem['description'])): ?>
                        <div class="p-2 bg-light rounded text-dark small">
                            <strong>Description:</strong> <?= htmlspecialchars($lostItem['description']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Candidate Found Matches -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Candidate Found Reports in "<?= htmlspecialchars($lostItem['category']) ?>"</h2>
            <span class="badge-pill bg-white text-dark border shadow-sm"><?= count($matches) ?> Candidate(s) Found</span>
        </div>

        <?php if (count($matches) === 0): ?>
            <div class="custom-card text-center py-5">
                <i class="bi bi-search text-muted mb-3" style="font-size: 3rem; display: block;"></i>
                <h3>No Matching Found Items</h3>
                <p class="text-muted mb-0">No active found reports are currently listed under category "<?= htmlspecialchars($lostItem['category']) ?>".</p>
            </div>
        <?php else: ?>
            <div class="items-grid">
                <?php foreach ($matches as $match): ?>
                    <?php
                    $isExactName = strtolower(trim($match['item_name'])) === strtolower(trim($lostItem['item_name']));
                    $matchStatus = $existingMatches[$match['id']] ?? null;
                    ?>
                    <article class="item-card <?= $isExactName ? 'border-success' : '' ?>">
                        <div class="item-card-image-wrap">
                            <div class="item-card-badge d-flex gap-1">
                                <span class="badge-pill badge-found">
                                    <i class="bi bi-check-circle-fill"></i> Found (#<?= (int) $match['id'] ?>)
                                </span>
                                <?php if ($isExactName): ?>
                                    <span class="badge-pill bg-success text-white">
                                        <i class="bi bi-stars"></i> Name Match
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($match['image'])): ?>
                                <img
                                    src="/uploads/<?= htmlspecialchars($match['image']) ?>"
                                    alt="<?= htmlspecialchars($match['item_name']) ?>"
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
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($match['category']) ?></span>
                                <span class="text-muted small">Finder: <?= htmlspecialchars($match['reporter_name']) ?></span>
                            </div>

                            <h3 class="item-card-title mb-2">
                                <?= htmlspecialchars($match['item_name']) ?>
                            </h3>

                            <ul class="item-meta-list mb-3">
                                <li class="item-meta-item">
                                    <i class="bi bi-geo-alt-fill text-danger"></i>
                                    <span><?= htmlspecialchars($match['location']) ?></span>
                                </li>
                                <li class="item-meta-item">
                                    <i class="bi bi-calendar-event text-secondary"></i>
                                    <span><?= htmlspecialchars($match['item_date']) ?></span>
                                </li>
                            </ul>

                            <?php if (!empty($match['description'])): ?>
                                <p class="text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars($match['description']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="item-card-footer d-flex gap-2">
                                <a href="/item.php?id=<?= (int) $match['id'] ?>" class="btn btn-outline-primary btn-sm flex-fill" target="_blank">
                                    <i class="bi bi-eye"></i> View
                                </a>

                                <?php if ($matchStatus === 'pending'): ?>
                                    <span class="badge-pill bg-warning-subtle text-warning border border-warning-subtle d-flex align-items-center justify-content-center flex-fill py-1 small fw-semibold">
                                        <i class="bi bi-clock-history me-1"></i> Match Pending
                                    </span>
                                <?php elseif ($matchStatus === 'approved'): ?>
                                    <span class="badge-pill bg-success-subtle text-success border border-success-subtle d-flex align-items-center justify-content-center flex-fill py-1 small fw-semibold">
                                        <i class="bi bi-check-circle-fill me-1"></i> Match Approved
                                    </span>
                                <?php else: ?>
                                    <form method="POST" action="/admin/create-match.php" class="flex-fill m-0 p-0">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                        <input type="hidden" name="lost_item_id" value="<?= (int) $lostItem['id'] ?>">
                                        <input type="hidden" name="found_item_id" value="<?= (int) $match['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm w-100">
                                            <i class="bi bi-link-45deg"></i> Link Match
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
