<?php

require_once __DIR__ . '/../app/navigation.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php renderPageAssets(); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Lost &amp; Found Portal</title>
</head>
<body>
    <?php renderNavigation(); ?>

    <main class="page-container">
        <!-- Hero Section -->
        <section class="hero-card">
            <h1 class="hero-title">Recover &amp; Report Campus Items</h1>
            <p class="hero-subtitle">
                A secure and centralized portal for students and staff to quickly report misplaced belongings and connect with finders across campus.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="/items.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-search"></i> Browse All Reports
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/lost-item.php" class="btn btn-secondary btn-lg">
                        <i class="bi bi-exclamation-diamond"></i> Report Lost
                    </a>
                <?php else: ?>
                    <a href="/register.php" class="btn btn-secondary btn-lg">
                        <i class="bi bi-person-plus"></i> Join Portal
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <!-- Feature Cards -->
        <div class="feature-cards-grid">
            <div class="feature-card">
                <div class="feature-icon feature-icon-lost">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h3>Lost an Item?</h3>
                <p>Post a lost report with details, category, location, and photo to notify campus finders.</p>
                <a href="<?= isset($_SESSION['user_id']) ? '/lost-item.php' : '/login.php' ?>" class="btn btn-outline-primary btn-sm">
                    Report Lost Item <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="feature-card">
                <div class="feature-icon feature-icon-found">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h3>Found Something?</h3>
                <p>Help a fellow student or colleague recover their missing belongings by reporting what you found.</p>
                <a href="<?= isset($_SESSION['user_id']) ? '/found-item.php' : '/login.php' ?>" class="btn btn-outline-primary btn-sm">
                    Report Found Item <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="feature-card">
                <div class="feature-icon feature-icon-browse">
                    <i class="bi bi-grid-fill"></i>
                </div>
                <h3>Search Catalog</h3>
                <p>Filter by location, category, date, and keyword to easily track down matched items.</p>
                <a href="/items.php" class="btn btn-outline-primary btn-sm">
                    Browse Catalog <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </main>
    <?php renderFooter(); ?>
</body>
</html>
