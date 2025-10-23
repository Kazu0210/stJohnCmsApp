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
    <title>Reports Module - Blessed Saint John Memorial (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="../adminDashboard/adminDashboard.css"> 
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="../adminDashboard/adminDashboard.php">Blessed Saint John Memorial</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="../adminDashboard/adminDashboard.php">Home</a></li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="managementDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Management
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="managementDropdown">
                            <li><a class="dropdown-item" href="../adminAppointment/adminAppointment.php">Appointment Management</a></li>
                            <li><a class="dropdown-item" href="../adminCemeteryMap/adminCemeteryMap.php">Cemetery Map Management</a></li>
                            <li><a class="dropdown-item" href="../adminReservation/adminReservation.php">Lot Reservation Management</a></li>
                            <li><a class="dropdown-item" href="../adminBurial/adminBurial.php">Burial Record Management</a></li>
                            <li><a class="dropdown-item" href="../adminFinancial/adminFinancial.php">Financial Tracking</a></li>
                            <li class="nav-item"><a class="dropdown-item" href="../adminMaintenance/adminMaintenance.php">Maintenance Management</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="adminToolsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Admin Tools
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="adminToolsDropdown">
                            <li><a class="dropdown-item" href="../adminAuditLogs/adminAuditLogs.php">Audit Logs</a></li>
                            <li><a class="dropdown-item" href="../adminUserManagement/adminUserManagement.php">User Management</a></li>
                            <li class="nav-item"><a class="dropdown-item active" href="adminReports.php">Reports Module</a></li>
                        </ul>
                    </li>
                </ul>

                <div class="d-lg-none mt-3 pt-3 border-top border-dark-subtle">
                    <div class="d-flex align-items-center mb-2">
                        <span id="user-name-display-mobile" class="fw-bold">Admin</span>
                    </div>
                    <a href="../../../frontend/auth/login/login.php" id="logoutLinkMobile" class="mobile-logout-link">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
                </div>
            
            <div class="dropdown d-none d-lg-flex">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="user-name-display-desktop">Admin User</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="../../../frontend/auth/login/login.php" id="logoutLinkDesktop"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
            </div>
        </div>
    </nav>
    
    <div class="main-content container-fluid">
        <h1 class="mb-4">Reports & Analytics Module 📊</h1>

        <div class="card shadow-sm mb-5">
            <div class="card-header bg-gold text-black d-flex align-items-center">
                <h2 class="h5 m-0"><i class="fas fa-download me-2"></i> Select Report Type & Time Period</h2>
            </div>
            
            <div class="card-body">
                <div class="row g-4">
                    
                    <div class="col-md-6 col-lg-4">
                        <div class="report-card card h-100 border-start border-5 border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success"><i class="fas fa-dollar-sign me-2"></i> Payment Collections</h5>
                                <p class="card-text small text-muted">Collection summaries, payment history, and revenue tracking within a custom range.</p>
                                <form data-report-type="financial">
                                    <label for="financialTimeframeStart" class="form-label mt-2">Start Date</label>
                                    <input type="date" class="form-control mb-2" id="financialTimeframeStart" required>
                                    <label for="financialTimeframeEnd" class="form-label">End Date</label>
                                    <input type="date" class="form-control mb-3" id="financialTimeframeEnd" required>
                                    <button type="submit" class="btn btn-sm btn-outline-dark me-2 generate-report-btn" data-format="pdf"><i class="fas fa-file-pdf"></i> PDF</button>
                                    <button type="submit" class="btn btn-sm btn-outline-dark generate-report-btn" data-format="excel"><i class="fas fa-file-excel"></i> Excel</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="report-card card h-100 border-start border-5 border-info">
                            <div class="card-body">
                                <h5 class="card-title text-info"><i class="fas fa-map-marker-alt me-2"></i> Reservation Statistics</h5>
                                <p class="card-text small text-muted">Lot availability, reservation count by type (Area/Block), and occupancy rates.</p>
                                <form data-report-type="reservation">
                                    <label for="reservationTimeframeStart" class="form-label mt-2">Start Date</label>
                                    <input type="date" class="form-control mb-2" id="reservationTimeframeStart" required>
                                    <label for="reservationTimeframeEnd" class="form-label">End Date</label>
                                    <input type="date" class="form-control mb-3" id="reservationTimeframeEnd" required>
                                    <button type="submit" class="btn btn-sm btn-outline-dark me-2 generate-report-btn" data-format="pdf"><i class="fas fa-file-pdf"></i> PDF</button>
                                    <button type="submit" class="btn btn-sm btn-outline-dark generate-report-btn" data-format="excel"><i class="fas fa-file-excel"></i> Excel</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-4">
                        <div class="report-card card h-100 border-start border-5 border-warning">
                            <div class="card-body">
                                <h5 class="card-title text-warning"><i class="fas fa-tools me-2"></i> Maintenance Summaries</h5>
                                <p class="card-text small text-muted">Completed, pending, and cancelled service requests within a given period.</p>
                                <form data-report-type="maintenance">
                                    <label for="maintenanceTimeframeStart" class="form-label mt-2">Start Date</label>
                                    <input type="date" class="form-control mb-2" id="maintenanceTimeframeStart" required>
                                    <label for="maintenanceTimeframeEnd" class="form-label">End Date</label>
                                    <input type="date" class="form-control mb-3" id="maintenanceTimeframeEnd" required>
                                    <button type="submit" class="btn btn-sm btn-outline-dark me-2 generate-report-btn" data-format="pdf"><i class="fas fa-file-pdf"></i> PDF</button>
                                    <button type="submit" class="btn btn-sm btn-outline-dark generate-report-btn" data-format="excel"><i class="fas fa-file-excel"></i> Excel</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="report-card card h-100 border-start border-5 border-secondary">
                            <div class="card-body">
                                <h5 class="card-title text-secondary"><i class="fas fa-cross me-2"></i> Burial Transactions</h5>
                                <p class="card-text small text-muted">Detailed records of all burial transactions within a selected time period.</p>
                                <form data-report-type="burial">
                                    <label for="burialTimeframeStart" class="form-label mt-2">Start Date</label>
                                    <input type="date" class="form-control mb-2" id="burialTimeframeStart" required>
                                    <label for="burialTimeframeEnd" class="form-label">End Date</label>
                                    <input type="date" class="form-control mb-3" id="burialTimeframeEnd" required>
                                    <button type="submit" class="btn btn-sm btn-outline-dark me-2 generate-report-btn" data-format="pdf"><i class="fas fa-file-pdf"></i> PDF</button>
                                    <button type="submit" class="btn btn-sm btn-outline-dark generate-report-btn" data-format="excel"><i class="fas fa-file-excel"></i> Excel</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="report-card card h-100 border-start border-5 border-primary">
                            <div class="card-body">
                                <h5 class="card-title text-primary"><i class="fas fa-users me-2"></i> Registered Clients</h5>
                                <p class="card-text small text-muted">Data on all registered clients/lot owners within a selected registration period.</p>
                                <form data-report-type="clients">
                                    <label for="clientTimeframeStart" class="form-label mt-2">Start Date</label>
                                    <input type="date" class="form-control mb-2" id="clientTimeframeStart" required>
                                    <label for="clientTimeframeEnd" class="form-label">End Date</label>
                                    <input type="date" class="form-control mb-3" id="clientTimeframeEnd" required>
                                    <button type="submit" class="btn btn-sm btn-outline-dark me-2 generate-report-btn" data-format="pdf"><i class="fas fa-file-pdf"></i> PDF</button>
                                    <button type="submit" class="btn btn-sm btn-outline-dark generate-report-btn" data-format="excel"><i class="fas fa-file-excel"></i> Excel</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="report-card card h-100 border-start border-5 border-danger">
                            <div class="card-body">
                                <h5 class="card-title text-danger"><i class="fas fa-history me-2"></i> System Usage Logs</h5>
                                <p class="card-text small text-muted">Monitor overall system activity, user logins, and performance logs within a range.</p>
                                <form data-report-type="logs">
                                    <label for="logTimeframeStart" class="form-label mt-2">Start Date</label>
                                    <input type="date" class="form-control mb-2" id="logTimeframeStart" required>
                                    <label for="logTimeframeEnd" class="form-label">End Date</label>
                                    <input type="date" class="form-control mb-3" id="logTimeframeEnd" required>
                                    <button type="submit" class="btn btn-sm btn-outline-dark me-2 generate-report-btn" data-format="pdf"><i class="fas fa-file-pdf"></i> PDF</button>
                                    <button type="submit" class="btn btn-sm btn-outline-dark generate-report-btn" data-format="excel"><i class="fas fa-file-excel"></i> Excel</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body bg-light rounded">
                <p class="mb-0 small text-muted"><i class="fas fa-info-circle me-1"></i> **Note:** Generating large reports, especially those spanning long time periods, may take a moment to process. Please wait for the download to start after clicking the button.</p>
            </div>
        </div>
    </div>

    <footer class="footer mt-5">
        <div class="container text-center">
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="/stJohnCmsApp/cmsApp/frontend/environment.js?v=<?php echo time(); ?>"></script>
    <script src="adminReports.js?v=<?php echo time(); ?>"></script>
</body>
</html>
