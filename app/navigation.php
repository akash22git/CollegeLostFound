<?php

function renderPageAssets(): void
{
    ?>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"
        defer
    ></script>
    <script src="/assets/js/app.js" defer></script>
    <?php
}

function renderNavigation(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $isLoggedIn = isset($_SESSION['user_id']);
    $isAdmin = $isLoggedIn && ($_SESSION['user_role'] ?? '') === 'admin';
    $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
    ?>
    <header class="site-header sticky-top">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary-gradient shadow-sm" aria-label="Main navigation">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="/index.php">
                    <span class="brand-icon"><i class="bi bi-box-seam-fill"></i></span>
                    <span>College Lost &amp; Found</span>
                </a>
                <button
                    class="navbar-toggler border-0 shadow-none"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mainNavigation"
                    aria-controls="mainNavigation"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNavigation">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="/index.php">
                                <i class="bi bi-house-door me-1"></i>Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'items.php' ? 'active' : '' ?>" href="/items.php">
                                <i class="bi bi-search me-1"></i>Browse Reports
                            </a>
                        </li>

                        <?php if ($isLoggedIn): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'lost-item.php' ? 'active' : '' ?>" href="/lost-item.php">
                                    <i class="bi bi-exclamation-diamond me-1"></i>Report Lost
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'found-item.php' ? 'active' : '' ?>" href="/found-item.php">
                                    <i class="bi bi-check-circle me-1"></i>Report Found
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'my-reports.php' ? 'active' : '' ?>" href="/my-reports.php">
                                    <i class="bi bi-journal-text me-1"></i>My Reports
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= in_array($currentPage, ['dashboard.php', 'find-matches.php'], true) ? 'active' : '' ?>" href="<?= $isAdmin ? '/admin/dashboard.php' : '/dashboard.php' ?>">
                                    <i class="bi bi-person-badge me-1"></i><?= $isAdmin ? 'Admin Dashboard' : 'Dashboard' ?>
                                </a>
                            </li>
                            <li class="nav-item ms-lg-2">
                                <a class="btn btn-outline-light btn-sm px-3 rounded-pill fw-medium" href="/logout.php">
                                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentPage === 'login.php' ? 'active' : '' ?>" href="/login.php">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                                </a>
                            </li>
                            <li class="nav-item ms-lg-2">
                                <a class="btn btn-light text-primary btn-sm px-3 rounded-pill fw-semibold shadow-sm" href="/register.php">
                                    <i class="bi bi-person-plus me-1"></i>Register
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <?php
}

function renderFooter(): void
{
    ?>
    <footer class="site-footer bg-dark text-white pt-5 pb-4 mt-auto">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="brand-icon bg-primary text-white rounded-3 p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-box-seam-fill fs-5"></i>
                        </span>
                        <span class="fw-bold fs-5 text-white">College Lost &amp; Found</span>
                    </div>
                    <p class="text-light-muted mb-3" style="font-size: 0.95rem; color: #94a3b8;">
                        Helping students, faculty, and staff quickly report, track, and reunite with lost belongings on campus.
                    </p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-medium">
                            <i class="bi bi-shield-check me-1"></i> Campus Verified
                        </span>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 ms-lg-auto">
                    <h6 class="fw-bold text-white mb-3 text-uppercase tracking-wider" style="font-size: 0.85rem; letter-spacing: 0.05em;">Quick Links</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2" style="font-size: 0.95rem;">
                        <li><a href="/index.php" class="text-decoration-none text-light-muted footer-link"><i class="bi bi-chevron-right small me-1"></i> Home</a></li>
                        <li><a href="/items.php" class="text-decoration-none text-light-muted footer-link"><i class="bi bi-chevron-right small me-1"></i> Browse Items</a></li>
                        <li><a href="/lost-item.php" class="text-decoration-none text-light-muted footer-link"><i class="bi bi-chevron-right small me-1"></i> Report Lost</a></li>
                        <li><a href="/found-item.php" class="text-decoration-none text-light-muted footer-link"><i class="bi bi-chevron-right small me-1"></i> Report Found</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold text-white mb-3 text-uppercase tracking-wider" style="font-size: 0.85rem; letter-spacing: 0.05em;">Account &amp; Access</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2" style="font-size: 0.95rem;">
                        <li><a href="/login.php" class="text-decoration-none text-light-muted footer-link"><i class="bi bi-chevron-right small me-1"></i> Login</a></li>
                        <li><a href="/register.php" class="text-decoration-none text-light-muted footer-link"><i class="bi bi-chevron-right small me-1"></i> Register Account</a></li>
                        <li><a href="/forgot-password.php" class="text-decoration-none text-light-muted footer-link"><i class="bi bi-chevron-right small me-1"></i> Forgot Password</a></li>
                        <li><a href="/dashboard.php" class="text-decoration-none text-light-muted footer-link"><i class="bi bi-chevron-right small me-1"></i> User Dashboard</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold text-white mb-3 text-uppercase tracking-wider" style="font-size: 0.85rem; letter-spacing: 0.05em;">Campus Support</h6>
                    <p class="text-light-muted mb-2" style="font-size: 0.9rem; color: #94a3b8;">
                        <i class="bi bi-building me-2 text-primary"></i> Main Administration Desk
                    </p>
                    <p class="text-light-muted mb-2" style="font-size: 0.9rem; color: #94a3b8;">
                        <i class="bi bi-envelope me-2 text-primary"></i> support@college.edu
                    </p>
                    <p class="text-light-muted mb-0" style="font-size: 0.9rem; color: #94a3b8;">
                        <i class="bi bi-clock me-2 text-primary"></i> Mon - Fri: 8:00 AM - 5:00 PM
                    </p>
                </div>
            </div>

            <hr class="border-secondary opacity-25 my-4">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 text-muted" style="font-size: 0.875rem;">
                <p class="mb-0 text-light-muted" style="color: #94a3b8;">
                    &copy; <?= date('Y') ?> College Lost &amp; Found. All rights reserved.
                </p>
                <div class="d-flex gap-3">
                    <span class="text-light-muted" style="color: #94a3b8;"><i class="bi bi-heart-fill text-danger me-1"></i> Designed for Campus Safety</span>
                </div>
            </div>
        </div>
    </footer>
    <?php
}
