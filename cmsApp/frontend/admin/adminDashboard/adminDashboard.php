<?php
// Include authentication helper
require_once '../../../../cms.api/auth_helper.php';

// Require authentication - redirect to login if not logged in
requireAuth('../../auth/login/login.php');

// Require admin or secretary role for this page
requireAdminOrSecretary('../../auth/login/login.php');

// Get current user information
$userId = getCurrentUserId();
$userName = getCurrentUserName();
$userRole = getCurrentUserRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Blessed Saint John Memorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="adminDashboard.css"> 
</head>
<body>
    <?php include_once __DIR__ . '/../components/adminNavbar.php'; ?>

    <div class="main-content">
        <h1 class="mb-4">Admin Dashboard</h1>

        <section class="overview">
            <h2 class="h4 mb-4 text-muted">System-Wide Summary</h2>

            <div class="row g-4">
                
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm dashboard-card" onclick="location.href='../adminReservation/adminReservation.php'">
                        <div class="card-body">
                            <h3 class="card-title text-primary"><i class="fas fa-file-contract me-2"></i>Reservation Metrics</h3>
                            <hr>
                            <p class="m-0"><strong>Total Reservations: <span class="fw-bold" id="total-reservations">0</span></strong></p>
                            <p class="m-0">Today: <span id="today-reservations">0</span></p>
                            <p class="m-0">This Week: <span id="week-reservations">0</span></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm dashboard-card" onclick="location.href='../adminFinancial/adminFinancial.php'">
                        <div class="card-body">
                            <h3 class="card-title text-success"><i class="fas fa-money-bill-wave me-2"></i>Payment Summary</h3>
                            <hr>
                            <p class="m-0"><strong>Payments Received:</strong> <span class="fw-bold text-success">₱,000</span></p>
                            <p class="m-0"><strong>Outstanding Balances:</strong> <span class="fw-bold text-danger">₱50,000</span></p>
                            <p class="m-0">Upcoming Due: 8</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm dashboard-card" onclick="location.href='../adminMaintenance/adminMaintenance.php'">
                        <div class="card-body">
                            <h3 class="card-title text-info"><i class="fas fa-tools me-2"></i>Maintenance Requests</h3>
                            <hr>
                            <p class="m-0">Pending: <span id="pending-maintenance-count" class="fw-bold">0</span></p>
                            <p class="m-0">In Progress: <span id="inprogress-maintenance-count">0</span></p>
                            <p class="m-0">Completed: <span id="completed-maintenance-count">0</span></span></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm dashboard-card" onclick="location.href='../adminAppointment/adminAppointment.php'">
                        <div class="card-body">
                            <h3 class="card-title text-warning"><i class="fas fa-calendar-alt me-2"></i>Appointment Statuses</h3>
                            <hr>
                            <p class="m-0">Scheduled: <span id="scheduled-appointment-count">0</span></p>
                            <p class="m-0">Completed: <span id="completed-appointment-count">0</span></p>
                            <p class="m-0">Cancelled: <span id="cancelled-appointment-count">0</span></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm dashboard-card" onclick="location.href='../adminCemeteryMap/adminCemeteryMap.php'">
                        <div class="card-body">
                            <h3 class="card-title text-secondary"><i class="fas fa-box-open me-2"></i>Lot Classification</h3>
                            <hr>
                            <p class="m-0">Available: <span class="fw-bold text-success" id="available-lot-count"></span></p>
                            <p class="m-0">Reserved: <span class="fw-bold text-warning" id="reserved-lot-count"></span></p>
                            <p class="m-0">Occupied: <span class="fw-bold text-danger" id="occupied-lot-count"></span></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm dashboard-card" onclick="location.href='../adminUserManagement/adminUserManagement.php'">
                        <div class="card-body">
                            <h3 class="card-title text-dark"><i class="fas fa-users me-2"></i>Users & Clients</h3>
                            <hr>
                            <p class="m-0"><strong>Registered Clients: </strong><span id="registered-clients-count">0</span></p>
                            <p class="m-0">Active users (24h): <span id="active-users-count">0</span></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm dashboard-card" onclick="location.href='../adminAuditLogs/adminAuditLogs.php'">
                        <div class="card-body">
                            <h3 class="card-title text-info"><i class="fas fa-clock me-2"></i>Recent Activity</h3>
                            <hr>
                            <div id="recent-activity-content">
                                <p class="m-0"><i class="fas fa-spinner fa-spin"></i> Loading recent activities...</p>
                            </div>
                            <p class="m-0 text-muted small" id="last-activity-time">Loading...</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-12">
                    <div class="card shadow-sm wide-card" onclick="location.href='../adminCemeteryMap/adminCemeteryMap.php'">
                         <div class="card-header bg-white"><h3 class="h5 m-0 text-secondary">Cemetery Map Status</h3></div>
                        <div class="card-body text-center p-2">
                            <img src="cemetery-map-placeholder.png" alt="Cemetery Map Status" class="img-fluid" style="max-height: 100%; width: auto; object-fit: contain;">
                        </div>
                    </div>
                </div>

            </div>
        </section>
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
    <script src="adminDashboard.js"></script>
</body>
</html>
