<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/navigation.php';

$itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$itemId || $itemId < 1) {
    http_response_code(404);
    exit('Item not found.');
}

$stmt = $pdo->prepare(
    'SELECT
        items.id,
        items.user_id,
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

$isLoggedIn = isset($_SESSION['user_id']);
$isOwner = $isLoggedIn && ((int) $_SESSION['user_id'] === (int) $item['user_id']);
$isAdmin = $isLoggedIn && (($_SESSION['user_role'] ?? '') === 'admin');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php renderPageAssets(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['item_name']) ?> - College Lost &amp; Found</title>
</head>

<body>
    <?php renderNavigation(); ?>

    <main class="page-container">
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <a href="/items.php" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to All Reports
            </a>
            <?php if ($isOwner): ?>
                <a href="/my-reports.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-journal-text"></i> My Reports
                </a>
            <?php endif; ?>
        </div>

        <?php if (($_GET['msg'] ?? '') === 'resolved'): ?>
            <div class="alert-custom alert-success mb-4" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>Report has been marked as Resolved!</span>
            </div>
        <?php endif; ?>

        <div class="custom-card">
            <div class="row g-4">
                <!-- Item Image Column -->
                <div class="col-lg-5 col-md-6">
                    <?php if (!empty($item['image'])): ?>
                        <div class="text-center">
                            <img
                                src="/uploads/<?= htmlspecialchars($item['image']) ?>"
                                alt="<?= htmlspecialchars($item['item_name']) ?>"
                                class="item-detail-image img-fluid shadow-sm"
                            >
                        </div>
                    <?php else: ?>
                        <div class="item-detail-image d-flex flex-column align-items-center justify-content-center bg-light text-muted p-5 text-center" style="min-height: 280px;">
                            <i class="bi bi-image" style="font-size: 4rem; opacity: 0.4;"></i>
                            <span class="mt-2 text-secondary">No image provided for this report</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Item Details Column -->
                <div class="col-lg-7 col-md-6 d-flex flex-column">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge-pill <?= $item['type'] === 'lost' ? 'badge-lost' : 'badge-found' ?>">
                            <i class="bi <?= $item['type'] === 'lost' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill' ?>"></i>
                            <?= htmlspecialchars(strtoupper($item['type'])) ?>
                        </span>
                        <span class="badge-pill badge-<?= strtolower($item['status']) ?>">
                            <?= htmlspecialchars(ucfirst($item['status'])) ?>
                        </span>
                    </div>

                    <h1 class="mb-3"><?= htmlspecialchars($item['item_name']) ?></h1>

                    <div class="table-responsive-wrapper mb-3">
                        <table class="custom-table mb-0">
                            <tbody>
                                <tr>
                                    <th style="width: 35%;"><i class="bi bi-folder-fill text-primary me-2"></i> Category</th>
                                    <td><?= htmlspecialchars($item['category']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="bi bi-geo-alt-fill text-danger me-2"></i> Location</th>
                                    <td><?= htmlspecialchars($item['location']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="bi bi-calendar-event text-secondary me-2"></i> Date</th>
                                    <td><?= htmlspecialchars($item['item_date']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="bi bi-person-fill text-info me-2"></i> Reported By</th>
                                    <td><?= htmlspecialchars($item['reporter_name']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($item['description'])): ?>
                        <div class="mb-4">
                            <h3 class="h6 text-uppercase fw-bold text-muted mb-2">Description</h3>
                            <p class="p-3 bg-light rounded-3 mb-0 text-dark" style="font-size: 0.98rem; line-height: 1.6;">
                                <?= nl2br(htmlspecialchars($item['description'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Owner Action Box -->
                    <?php if ($isOwner || $isAdmin): ?>
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <h4 class="h6 fw-bold mb-2 text-dark"><i class="bi bi-gear-fill me-1"></i> Report Management</h4>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($item['status'] === 'active'): ?>
                                    <form method="POST" action="/resolve-item.php" class="m-0">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                        <input type="hidden" name="redirect" value="item">
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Mark this report as resolved/recovered?');">
                                            <i class="bi bi-check2-circle"></i> <?= $item['type'] === 'lost' ? 'I Recovered This Item' : 'Mark as Handed Over' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" action="/delete-item.php" class="m-0">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="redirect" value="<?= $isAdmin ? 'admin' : 'my-reports' ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Permanently delete this report?');">
                                        <i class="bi bi-trash"></i> Delete Report
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($item['status'] === 'active' && !empty($item['reporter_phone']) && !$isOwner): ?>
                        <div class="contact-card mt-auto">
                            <div class="d-flex align-items-center gap-3">
                                <div class="feature-icon feature-icon-found m-0" style="width: 48px; height: 48px; font-size: 1.25rem;">
                                    <i class="bi bi-telephone-fill"></i>
                                </div>
                                <div>
                                    <h4 class="h6 fw-bold mb-1 text-success">Claim or Connect with Reporter</h4>
                                    <p class="mb-0 text-dark small">
                                        Contact <strong><?= htmlspecialchars($item['reporter_name']) ?></strong> at:
                                        <a href="tel:<?= htmlspecialchars($item['reporter_phone']) ?>" class="fw-bold text-success text-decoration-underline ms-1" style="font-size: 1.05rem;">
                                            <?= htmlspecialchars($item['reporter_phone']) ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php renderFooter(); ?>
</body>

</html>
