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
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">My Reservations</h1>
        </div>

        <!-- Alerts Section -->
        <section id="alerts" class="mb-3">
            <?php 
            // Show a short message when redirected after a payment 
            if (isset($_GET['payment']) && $_GET['payment'] === 'success') { 
                $pid = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; 
                echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>Payment submitted successfully." . ($pid ? " (Ref: #$pid)" : "") . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>"; 
            } elseif (isset($_GET['payment']) && $_GET['payment'] === 'error') { 
                $msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'Payment failed.'; 
                echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>" . $msg . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>"; 
            } 
            ?> 
        </section>

        <!-- Reservations Table Section -->
        <section>
            <div class="card shadow-sm">
                <div class="card-body">
                    <div id="reservationsContainer">
                        <p class="text-muted">Loading your reservations...</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="clientReservations.js"></script>
</body>
</html>
