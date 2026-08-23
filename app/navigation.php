<?php

function renderPageAssets(): void
{
    ?>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
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
    ?>
    <header class="site-header">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm" aria-label="Main navigation">
            <div class="container">
                <a class="navbar-brand fw-bold" href="/index.php">College Lost &amp; Found</a>
                <button
                    class="navbar-toggler"
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
                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        <li class="nav-item"><a class="nav-link" href="/index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="/items.php">Browse Reports</a></li>

                        <?php if ($isLoggedIn): ?>
                            <li class="nav-item"><a class="nav-link" href="/lost-item.php">Report Lost</a></li>
                            <li class="nav-item"><a class="nav-link" href="/found-item.php">Report Found</a></li>
                            <li class="nav-item"><a class="nav-link" href="/my-reports.php">My Reports</a></li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $isAdmin ? '/admin/dashboard.php' : '/dashboard.php' ?>">
                                    <?= $isAdmin ? 'Admin Dashboard' : 'Dashboard' ?>
                                </a>
                            </li>
                            <li class="nav-item ms-lg-2"><a class="btn btn-light btn-sm px-3" href="/logout.php">Logout</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="/login.php">Login</a></li>
                            <li class="nav-item ms-lg-2"><a class="btn btn-light btn-sm px-3" href="/register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <?php
}
