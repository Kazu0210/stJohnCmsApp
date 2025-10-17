<?php
// Secretary page
require_once '../../../cms.api/auth_helper.php';

// Require authentication and ensure user is admin or secretary
requireAuth('../auth/login/login.php');
requireAdminOrSecretary('../auth/login/login.php');

$userId = getCurrentUserId();
$userName = getCurrentUserName();
$userRole = getCurrentUserRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard - Blessed Saint John Memorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="secretary.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Blessed Saint John Memorial</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item active"><a class="nav-link active" href="secretary.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Appointments</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Reservations</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Maintenance</a></li>
                </ul>

                <div class="d-lg-none mt-3 pt-3 border-top border-dark-subtle">
                    <div class="d-flex align-items-center mb-2">
                        <span id="user-name-display-mobile" class="fw-bold"><?php echo htmlspecialchars($userName); ?></span>
                        <small class="text-muted ms-2"><?php echo htmlspecialchars($userRole); ?></small>
                    </div>
                    <a href="../../../cms.api/logout.php" id="logoutLinkMobile" class="mobile-logout-link">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>

            <div class="dropdown d-none d-lg-flex">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="user-name-display-desktop"><?php echo htmlspecialchars($userName); ?></span>
                    <small class="text-muted ms-2"><?php echo htmlspecialchars($userRole); ?></small>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="../../../cms.api/logout.php" id="logoutLinkDesktop"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-content mt-5 pt-5">
        <h1 class="mb-3">Secretary Dashboard</h1>
        <p class="text-muted">Welcome, <strong><?php echo htmlspecialchars($userName ?: 'Secretary'); ?></strong>. Use the menu to manage appointments, reservations, and requests.</p>

        <div class="row g-4 mt-4">
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm" onclick="location.href='#'" style="cursor:pointer;">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar-check me-2 text-warning"></i>Appointments</h5>
                        <p class="card-text small text-muted">View and manage client appointments.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm" onclick="location.href='#'" style="cursor:pointer;">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-file-contract me-2 text-primary"></i>Reservations</h5>
                        <p class="card-text small text-muted">Review and confirm lot reservations.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm" onclick="location.href='#'" style="cursor:pointer;">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-tools me-2 text-info"></i>Maintenance</h5>
                        <p class="card-text small text-muted">Track maintenance requests and schedules.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm" onclick="location.href='#'" style="cursor:pointer;">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users me-2 text-secondary"></i>Clients</h5>
                        <p class="card-text small text-muted">Search and contact clients.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <footer class="footer bg-dark text-white py-3 mt-5">
        <div class="container text-center">
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com" class="text-white">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421" class="text-white">+63 997 844 2421</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="secretary.js"></script>
</body>
</html>
