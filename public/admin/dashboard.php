<?php

session_start();

require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/csrf.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/navigation.php';

requireAdmin();

// Total users
$stmt = $pdo->query('SELECT COUNT(*) FROM users');
$totalUsers = (int) $stmt->fetchColumn();

// Total lost reports
$stmt = $pdo->query('SELECT COUNT(*) FROM items WHERE type = "lost"');
$totalLost = (int) $stmt->fetchColumn();

// Total found reports
$stmt = $pdo->query('SELECT COUNT(*) FROM items WHERE type = "found"');
$totalFound = (int) $stmt->fetchColumn();

// Active reports
$stmt = $pdo->query('SELECT COUNT(*) FROM items WHERE status = "active"');
$totalActive = (int) $stmt->fetchColumn();

// Resolved reports
$stmt = $pdo->query('SELECT COUNT(*) FROM items WHERE status = "resolved"');
$totalResolved = (int) $stmt->fetchColumn();

// Pending matches
$stmt = $pdo->query('SELECT COUNT(*) FROM item_matches WHERE status = "pending"');
$totalPendingMatches = (int) $stmt->fetchColumn();

// Fetch Match Queue
$matchesStmt = $pdo->query(
    'SELECT
        item_matches.id AS match_id,
        item_matches.status AS match_status,
        item_matches.created_at AS match_created_at,
        lost_item.id AS lost_id,
        lost_item.item_name AS lost_name,
        lost_item.category AS lost_category,
        lost_user.name AS lost_reporter,
        found_item.id AS found_id,
        found_item.item_name AS found_name,
        found_item.category AS found_category,
        found_user.name AS found_reporter
     FROM item_matches
     INNER JOIN items AS lost_item ON item_matches.lost_item_id = lost_item.id
     INNER JOIN users AS lost_user ON lost_item.user_id = lost_user.id
     INNER JOIN items AS found_item ON item_matches.found_item_id = found_item.id
     INNER JOIN users AS found_user ON found_item.user_id = found_user.id
     ORDER BY (item_matches.status = "pending") DESC, item_matches.created_at DESC
     LIMIT 20'
);
$itemMatches = $matchesStmt->fetchAll(PDO::FETCH_ASSOC);

// Show only the 10 newest reports.
$stmt = $pdo->query(
    'SELECT
        items.id,
        items.type,
        items.item_name,
        items.category,
        items.location,
        items.status,
        items.created_at,
        users.name AS reporter_name
     FROM items
     INNER JOIN users ON items.user_id = users.id
     ORDER BY items.created_at DESC
     LIMIT 10'
);
$recentReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Feedback messages
$msgKey = $_GET['msg'] ?? '';
$alertMessage = '';
$alertClass = 'alert-info';

switch ($msgKey) {
    case 'report_resolved':
        $alertMessage = 'Report was successfully marked as Resolved.';
        $alertClass = 'alert-success';
        break;
    case 'report_rejected':
        $alertMessage = 'Report was rejected and marked as inactive.';
        $alertClass = 'alert-warning';
        break;
    case 'match_approved':
        $alertMessage = 'Match approved! Both the lost and found reports have been automatically marked as Resolved.';
        $alertClass = 'alert-success';
        break;
    case 'match_rejected':
        $alertMessage = 'Match proposal has been marked as rejected.';
        $alertClass = 'alert-secondary';
        break;
    case 'match_deleted':
        $alertMessage = 'Match link was removed.';
        $alertClass = 'alert-info';
        break;
    case 'match_error':
        $alertMessage = 'An error occurred while updating the match.';
        $alertClass = 'alert-danger';
        break;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php renderPageAssets(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - College Lost &amp; Found</title>
</head>

<body>
    <?php renderNavigation(); ?>

    <main class="page-container">
        <!-- Dashboard Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h1 class="mb-1">Admin Dashboard</h1>
                <p class="page-subtitle mb-0">
                    Welcome back, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong> (Administrator)
                </p>
            </div>
            <a href="/items.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-search"></i> Browse All Reports
            </a>
        </div>

        <?php if ($alertMessage !== ''): ?>
            <div class="alert-custom <?= $alertClass ?> mb-4" role="alert">
                <i class="bi bi-info-circle-fill"></i>
                <span><?= htmlspecialchars($alertMessage) ?></span>
            </div>
        <?php endif; ?>

        <!-- Metric Statistics Grid -->
        <div class="stats-grid mb-5">
            <div class="stat-card">
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="stat-value"><?= $totalUsers ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #fee2e2; color: #dc2626;">
                    <i class="bi bi-exclamation-diamond-fill"></i>
                </div>
                <div>
                    <div class="stat-value"><?= $totalLost ?></div>
                    <div class="stat-label">Lost Reports</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="stat-value"><?= $totalFound ?></div>
                    <div class="stat-label">Found Reports</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="stat-value"><?= $totalActive ?></div>
                    <div class="stat-label">Active Reports</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #f0fdf4; color: #15803d;">
                    <i class="bi bi-check2-all"></i>
                </div>
                <div>
                    <div class="stat-value"><?= $totalResolved ?></div>
                    <div class="stat-label">Resolved Reports</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #fef3c7; color: #d97706;">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
                <div>
                    <div class="stat-value"><?= $totalPendingMatches ?></div>
                    <div class="stat-label">Pending Matches</div>
                </div>
            </div>
        </div>

        <!-- Matched Items Management Section -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 mb-1"><i class="bi bi-link-45deg text-primary"></i> Matched Items Resolution Queue</h2>
                <p class="text-muted small mb-0">Review potential lost and found item matches. Approving a match resolves both items.</p>
            </div>
            <span class="badge-pill bg-white text-dark border shadow-sm"><?= count($itemMatches) ?> Linked Match(es)</span>
        </div>

        <?php if (count($itemMatches) === 0): ?>
            <div class="custom-card text-center py-4 mb-5">
                <i class="bi bi-diagram-3 text-muted mb-2" style="font-size: 2.5rem; display: block;"></i>
                <p class="text-muted mb-1">No matched item pairs found.</p>
                <p class="text-muted small mb-0">Use the <strong>"Matches"</strong> button next to active lost items below to discover candidates.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive-wrapper mb-5">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Match ID</th>
                            <th>Lost Item (Owner)</th>
                            <th>Found Item (Finder)</th>
                            <th>Category</th>
                            <th>Match Status</th>
                            <th class="text-end">Match Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itemMatches as $m): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= (int) $m['match_id'] ?></td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <a href="/item.php?id=<?= (int) $m['lost_id'] ?>" target="_blank" class="text-decoration-none text-dark">
                                            <i class="bi bi-box-arrow-up-right small text-primary me-1"></i><?= htmlspecialchars($m['lost_name']) ?>
                                        </a>
                                    </div>
                                    <span class="text-muted small"><i class="bi bi-person me-1"></i><?= htmlspecialchars($m['lost_reporter']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <a href="/item.php?id=<?= (int) $m['found_id'] ?>" target="_blank" class="text-decoration-none text-dark">
                                            <i class="bi bi-box-arrow-up-right small text-success me-1"></i><?= htmlspecialchars($m['found_name']) ?>
                                        </a>
                                    </div>
                                    <span class="text-muted small"><i class="bi bi-person me-1"></i><?= htmlspecialchars($m['found_reporter']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($m['lost_category']) ?></span>
                                </td>
                                <td>
                                    <?php if ($m['match_status'] === 'pending'): ?>
                                        <span class="badge-pill bg-warning-subtle text-warning border border-warning-subtle">
                                            <i class="bi bi-clock-history me-1"></i> Pending Review
                                        </span>
                                    <?php elseif ($m['match_status'] === 'approved'): ?>
                                        <span class="badge-pill bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i> Approved &amp; Resolved
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-pill bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <i class="bi bi-x-circle me-1"></i> Rejected
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <?php if ($m['match_status'] === 'pending'): ?>
                                            <form method="POST" action="/admin/update-match.php" class="d-inline m-0 p-0">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                                <input type="hidden" name="match_id" value="<?= (int) $m['match_id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success btn-sm" title="Approve match and mark both items as resolved">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Approve &amp; Resolve
                                                </button>
                                            </form>

                                            <form method="POST" action="/admin/update-match.php" class="d-inline m-0 p-0">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                                <input type="hidden" name="match_id" value="<?= (int) $m['match_id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Reject this match proposal">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="/admin/update-match.php" class="d-inline m-0 p-0">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                                <input type="hidden" name="match_id" value="<?= (int) $m['match_id'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm" title="Delete match record" onclick="return confirm('Remove this match record?');">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Recent Reports Table -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 mb-1"><i class="bi bi-journal-text text-primary"></i> Recent Reports Queue</h2>
                <p class="text-muted small mb-0">Showing up to 10 latest submissions across all categories.</p>
            </div>
        </div>

        <?php if (count($recentReports) === 0): ?>
            <div class="custom-card text-center py-5">
                <i class="bi bi-inbox text-muted mb-2" style="font-size: 3rem; display: block;"></i>
                <p class="text-muted mb-0">No recent reports found in the system.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Reported By</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReports as $report): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= (int) $report['id'] ?></td>
                                <td class="fw-semibold text-dark">
                                    <?= htmlspecialchars($report['item_name']) ?>
                                </td>
                                <td>
                                    <span class="badge-pill <?= $report['type'] === 'lost' ? 'badge-lost' : 'badge-found' ?>">
                                        <?= htmlspecialchars(strtoupper($report['type'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($report['reporter_name']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($report['category']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($report['location']) ?></td>
                                <td>
                                    <span class="badge-pill badge-<?= strtolower($report['status']) ?>">
                                        <?= htmlspecialchars(ucfirst($report['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <a href="/item.php?id=<?= (int) $report['id'] ?>" class="btn btn-secondary btn-sm" title="View details">
                                            <i class="bi bi-eye"></i> View
                                        </a>

                                        <?php if ($report['type'] === 'lost' && $report['status'] === 'active'): ?>
                                            <a href="/admin/find-matches.php?id=<?= (int) $report['id'] ?>" class="btn btn-primary btn-sm" title="Auto find matching found items">
                                                <i class="bi bi-diagram-3"></i> Matches
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($report['status'] === 'active'): ?>
                                            <form method="POST" action="/admin/update-report.php" class="d-inline m-0 p-0">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $report['id'] ?>">
                                                <input type="hidden" name="action" value="resolve">
                                                <button type="submit" class="btn btn-success btn-sm" title="Mark this report as resolved">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>

                                            <form method="POST" action="/admin/update-report.php" class="d-inline m-0 p-0">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $report['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Reject invalid report">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <?php renderFooter(); ?>
</body>

</html>
