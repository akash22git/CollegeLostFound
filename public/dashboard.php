<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/navigation.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

// Get user report stats
$statsStmt = $pdo->prepare(
    'SELECT
        COUNT(*) AS total_reports,
        SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) AS active_reports,
        SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) AS resolved_reports
     FROM items
     WHERE user_id = ?'
);
$statsStmt->execute([$userId]);
$userStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalMyReports = (int) ($userStats['total_reports'] ?? 0);
$activeMyReports = (int) ($userStats['active_reports'] ?? 0);
$resolvedMyReports = (int) ($userStats['resolved_reports'] ?? 0);

// Get matched items for this user (where user reported a lost item that has a match, or reported a found item that matched a lost item)
$matchesStmt = $pdo->prepare(
    'SELECT
        item_matches.id AS match_id,
        item_matches.status AS match_status,
        item_matches.created_at AS match_created_at,
        lost_item.id AS lost_id,
        lost_item.item_name AS lost_name,
        lost_item.category AS lost_category,
        lost_item.status AS lost_status,
        lost_user.id AS lost_user_id,
        lost_user.name AS lost_user_name,
        found_item.id AS found_id,
        found_item.item_name AS found_name,
        found_item.category AS found_category,
        found_item.location AS found_location,
        found_item.image AS found_image,
        found_item.status AS found_status,
        found_user.name AS finder_name,
        found_user.phone AS finder_phone
     FROM item_matches
     INNER JOIN items AS lost_item ON item_matches.lost_item_id = lost_item.id
     INNER JOIN users AS lost_user ON lost_item.user_id = lost_user.id
     INNER JOIN items AS found_item ON item_matches.found_item_id = found_item.id
     INNER JOIN users AS found_user ON found_item.user_id = found_user.id
     WHERE lost_item.user_id = ? OR found_item.user_id = ?
     ORDER BY (item_matches.status = "pending") DESC, item_matches.created_at DESC'
);
$matchesStmt->execute([$userId, $userId]);
$myMatches = $matchesStmt->fetchAll(PDO::FETCH_ASSOC);

$pendingMatchesCount = 0;
foreach ($myMatches as $m) {
    if ($m['match_status'] === 'pending') {
        $pendingMatchesCount++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php renderPageAssets(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - College Lost &amp; Found</title>
</head>

<body>
    <?php renderNavigation(); ?>

    <main class="page-container">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <h1 class="mb-1">Student / Staff Dashboard</h1>
            <p class="page-subtitle">Manage your campus Lost &amp; Found account, submissions, and match updates</p>
        </div>

        <?php if ($pendingMatchesCount > 0): ?>
            <div class="alert-custom alert-success mb-4 p-3 shadow-sm" style="background: #f0fdf4; border-color: #86efac; color: #166534;" role="alert">
                <i class="bi bi-stars fs-4 text-success"></i>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center w-100 gap-2">
                    <div>
                        <strong class="fs-6">Match Alert! Potential match found for your item</strong>
                        <p class="mb-0 small text-dark">
                            The administration or campus community has cross-referenced and linked <?= $pendingMatchesCount ?> potential match(es) for your submitted reports.
                        </p>
                    </div>
                    <a href="#matched-items" class="btn btn-success btn-sm text-nowrap align-self-start align-self-md-center">
                        <i class="bi bi-arrow-down-circle me-1"></i> View Matches
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <!-- User Profile Summary Card -->
            <div class="col-lg-4 col-md-5">
                <div class="custom-card text-center h-100">
                    <div class="feature-icon feature-icon-browse mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h2 class="h4 mb-1"><?= htmlspecialchars($_SESSION['user_name']) ?></h2>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($_SESSION['user_email']) ?></p>
                    <span class="badge-pill bg-light text-primary border mb-4">
                        <i class="bi bi-shield-check me-1"></i> Role: <?= htmlspecialchars(ucfirst($_SESSION['user_role'] ?? 'Student')) ?>
                    </span>

                    <div class="row g-2 mb-3 text-start">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded text-center">
                                <div class="fw-bold fs-5 text-primary"><?= $activeMyReports ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;">Active Reports</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded text-center">
                                <div class="fw-bold fs-5 text-success"><?= $resolvedMyReports ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;">Resolved Items</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 border-top pt-3 text-start">
                        <a href="/my-reports.php" class="btn btn-outline-primary btn-sm justify-content-start">
                            <i class="bi bi-journal-text me-2"></i> View My Reports
                        </a>
                        <a href="/logout.php" class="btn btn-secondary btn-sm justify-content-start text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Log Out
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Action Cards Grid -->
            <div class="col-lg-8 col-md-7">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="feature-card h-100 text-start">
                            <div class="feature-icon feature-icon-lost ms-0 mb-3" style="width: 48px; height: 48px; font-size: 1.3rem;">
                                <i class="bi bi-exclamation-diamond-fill"></i>
                            </div>
                            <h3 class="h5 mb-2">Report a Lost Item</h3>
                            <p class="text-muted small mb-3">Misplaced something on campus? Post a report immediately.</p>
                            <a href="/lost-item.php" class="btn btn-primary btn-sm">
                                Report Lost <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="feature-card h-100 text-start">
                            <div class="feature-icon feature-icon-found ms-0 mb-3" style="width: 48px; height: 48px; font-size: 1.3rem;">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h3 class="h5 mb-2">Report a Found Item</h3>
                            <p class="text-muted small mb-3">Found keys, a wallet, or a device? Help it find its owner.</p>
                            <a href="/found-item.php" class="btn btn-success btn-sm">
                                Report Found <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="feature-card h-100 text-start">
                            <div class="feature-icon feature-icon-browse ms-0 mb-3" style="width: 48px; height: 48px; font-size: 1.3rem;">
                                <i class="bi bi-search"></i>
                            </div>
                            <h3 class="h5 mb-2">Browse All Reports</h3>
                            <p class="text-muted small mb-3">Search through all active lost &amp; found items on campus.</p>
                            <a href="/items.php" class="btn btn-outline-primary btn-sm">
                                Browse Catalog <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="feature-card h-100 text-start">
                            <div class="feature-icon bg-light text-secondary ms-0 mb-3" style="width: 48px; height: 48px; font-size: 1.3rem;">
                                <i class="bi bi-card-checklist"></i>
                            </div>
                            <h3 class="h5 mb-2">My Submissions</h3>
                            <p class="text-muted small mb-3">View and check resolution status of items you reported.</p>
                            <a href="/my-reports.php" class="btn btn-outline-primary btn-sm">
                                My Reports <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Matches Section -->
        <?php if (count($myMatches) > 0): ?>
            <section id="matched-items" class="mb-5 pt-2">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h4 mb-1"><i class="bi bi-stars text-success"></i> My Matched Items &amp; Updates</h2>
                        <p class="text-muted small mb-0">Active matches discovered between your submissions and reported items.</p>
                    </div>
                    <span class="badge-pill bg-white text-dark border shadow-sm"><?= count($myMatches) ?> Match Update(s)</span>
                </div>

                <div class="items-grid">
                    <?php foreach ($myMatches as $match): ?>
                        <?php
                        $isMyLostItem = (int) $match['lost_user_id'] === $userId;
                        $otherItemTitle = $isMyLostItem ? $match['found_name'] : $match['lost_name'];
                        $otherItemId = $isMyLostItem ? $match['found_id'] : $match['lost_id'];
                        $myReportTitle = $isMyLostItem ? $match['lost_name'] : $match['found_name'];
                        $myReportId = $isMyLostItem ? $match['lost_id'] : $match['found_id'];
                        $myStatus = $isMyLostItem ? $match['lost_status'] : $match['found_status'];
                        ?>
                        <article class="item-card border-success">
                            <div class="item-card-image-wrap">
                                <div class="item-card-badge d-flex gap-1">
                                    <?php if ($match['match_status'] === 'approved'): ?>
                                        <span class="badge-pill bg-success text-white">
                                            <i class="bi bi-check-circle-fill"></i> Match Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-pill bg-warning-subtle text-warning border border-warning-subtle">
                                            <i class="bi bi-stars"></i> Potential Match Linked
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($match['found_image'])): ?>
                                    <img
                                        src="/uploads/<?= htmlspecialchars($match['found_image']) ?>"
                                        alt="<?= htmlspecialchars($match['found_name']) ?>"
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
                                <div class="mb-2">
                                    <span class="text-muted small">For your report:</span>
                                    <h4 class="h6 fw-bold mb-1 text-primary">"<?= htmlspecialchars($myReportTitle) ?>"</h4>
                                </div>

                                <div class="p-2 bg-light rounded-3 mb-3 border">
                                    <div class="text-muted small mb-1">
                                        <i class="bi <?= $isMyLostItem ? 'bi-check-circle-fill text-success' : 'bi-exclamation-circle-fill text-danger' ?> me-1"></i>
                                        Matched with <?= $isMyLostItem ? 'Found' : 'Lost' ?> Item:
                                    </div>
                                    <div class="fw-semibold text-dark mb-1"><?= htmlspecialchars($otherItemTitle) ?></div>
                                    <div class="small text-muted mb-1"><i class="bi bi-geo-alt text-danger me-1"></i> <?= htmlspecialchars($match['found_location']) ?></div>
                                    <?php if (!empty($match['finder_phone'])): ?>
                                        <div class="small text-dark mt-2 pt-1 border-top">
                                            <span>Contact <strong><?= htmlspecialchars($match['finder_name']) ?></strong>:</span>
                                            <a href="tel:<?= htmlspecialchars($match['finder_phone']) ?>" class="fw-bold text-success text-decoration-underline ms-1">
                                                <?= htmlspecialchars($match['finder_phone']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="item-card-footer d-flex flex-column gap-2">
                                    <a href="/item.php?id=<?= (int) $otherItemId ?>" class="btn btn-outline-primary btn-sm w-100">
                                        <i class="bi bi-eye"></i> View Matched Report
                                    </a>

                                    <?php if ($myStatus === 'active'): ?>
                                        <form method="POST" action="/resolve-item.php" class="w-100 m-0">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                            <input type="hidden" name="item_id" value="<?= (int) $myReportId ?>">
                                            <input type="hidden" name="redirect" value="dashboard">
                                            <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('Confirm that you have recovered/resolved this item?');">
                                                <i class="bi bi-check2-circle"></i> <?= $isMyLostItem ? 'I Recovered My Item' : 'Mark as Resolved' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <?php renderFooter(); ?>
</body>

</html>
