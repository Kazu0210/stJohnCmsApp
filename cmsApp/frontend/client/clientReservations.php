<?php
require_once '../../../cms.api/auth_helper.php';
requireAuth('../auth/login/login.php');

$userName = getCurrentUserName();
$userRole = getCurrentUserRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - Blessed Saint John Memorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="clientReservations.css">
</head>
<body>
    <?php include __DIR__ . '/clientNavbar.php'; ?>

    <main class="container main-content mt-5 pt-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4">My Reservations</h1>
            <div class="text-muted">Logged in as <strong><?php echo htmlspecialchars($userName); ?></strong></div>
        </div>

        <div id="alerts"></div>

        <div class="card">
            <div class="card-body">
                <div id="reservationsContainer">
                    <p class="text-muted">Loading your reservations...</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer text-center py-3 mt-4">
        <div class="container"><small>&copy; 2025 Blessed Saint John Memorial</small></div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="clientReservations.js"></script>
</body>
</html>
