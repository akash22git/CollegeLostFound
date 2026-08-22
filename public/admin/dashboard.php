<?php

session_start();

require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdmin();


// Total users
$stmt = $pdo->query(
    'SELECT COUNT(*) FROM users'
);

$totalUsers = (int) $stmt->fetchColumn();


// Total lost reports
$stmt = $pdo->query(
    'SELECT COUNT(*) FROM items WHERE type = "lost"'
);

$totalLost = (int) $stmt->fetchColumn();


// Total found reports
$stmt = $pdo->query(
    'SELECT COUNT(*) FROM items WHERE type = "found"'
);

$totalFound = (int) $stmt->fetchColumn();


// Active reports
$stmt = $pdo->query(
    'SELECT COUNT(*) FROM items WHERE status = "active"'
);

$totalActive = (int) $stmt->fetchColumn();


// Resolved reports
$stmt = $pdo->query(
    'SELECT COUNT(*) FROM items WHERE status = "resolved"'
);

$totalResolved = (int) $stmt->fetchColumn();

// Show only the 10 newest reports. When a new report is submitted, the oldest
// report automatically drops out of this dashboard list (LIFO display).
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
     INNER JOIN users
         ON items.user_id = users.id
     ORDER BY items.created_at DESC
     LIMIT 10'
);

$recentReports = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard</title>

</head>

<body>

    <h1>College Lost & Found</h1>

    <h2>Admin Dashboard</h2>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION['user_name']) ?>!
    </p>

    <p>
        Role:
        <?= htmlspecialchars($_SESSION['user_role']) ?>
    </p>

    <hr>

    <h3>Dashboard Statistics</h3>

<p>
    <strong>Total Users:</strong>
    <?= $totalUsers ?>
</p>

<p>
    <strong>Total Lost Reports:</strong>
    <?= $totalLost ?>
</p>

<p>
    <strong>Total Found Reports:</strong>
    <?= $totalFound ?>
</p>

<p>
    <strong>Active Reports:</strong>
    <?= $totalActive ?>
</p>

<p>
    <strong>Resolved Reports:</strong>
    <?= $totalResolved ?>
</p>

<hr>

<h3>Recent Reports</h3>

<?php if (count($recentReports) === 0): ?>

    <p>No reports found.</p>

<?php else: ?>

    <table border="1" cellpadding="8">

        <thead>

            <tr>
    <th>ID</th>
    <th>Item</th>
    <th>Type</th>
    <th>Reported By</th>
    <th>Category</th>
    <th>Location</th>
    <th>Status</th>
    <th>Action</th>
</tr>

        </thead>

        <tbody>

            <?php foreach ($recentReports as $report): ?>

                <tr>

                    <td>
                         <?= (int) $report['id'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($report['item_name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(strtoupper($report['type'])) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($report['reporter_name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($report['category']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($report['location']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($report['status']) ?>
                    </td>

                    <td>

    <a href="/item.php?id=<?= (int) $report['id'] ?>">
        View
    </a>

    <?php if ($report['type'] === 'lost' && $report['status'] === 'active'): ?>

    <a href="/admin/find-matches.php?id=<?= (int) $report['id'] ?>">
        Find Matches
    </a>

<?php endif; ?>

    <?php if ($report['status'] === 'active'): ?>

        <form
            method="POST"
            action="/admin/update-report.php"
            style="display:inline;"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int) $report['id'] ?>"
            >

            <input
                type="hidden"
                name="action"
                value="resolve"
            >

            <button type="submit">
                Mark Resolved
            </button>

        </form>


        <form
            method="POST"
            action="/admin/update-report.php"
            style="display:inline;"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int) $report['id'] ?>"
            >

            <input
                type="hidden"
                name="action"
                value="reject"
            >

            <button type="submit">
                Reject
            </button>

        </form>

    <?php endif; ?>

</td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

<?php endif; ?>

<p>
    <a href="/dashboard.php">
        User Dashboard
    </a>
</p>

<p>
    <a href="/logout.php">
        Logout
    </a>
</p>

</body>

</html>
