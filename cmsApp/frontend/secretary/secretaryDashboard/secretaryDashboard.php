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
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard - BSJM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="secretaryDashboard.css">
  <style>
    .card-header-clickable .fa-chevron-up {
      transition: transform 0.3s ease;
    }
    .card-header-clickable[aria-expanded="false"] .fa-chevron-up {
      transform: rotate(180deg);
    }
  </style>
</head>
<body>
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg fixed-top navbar-custom">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="#">Blessed Saint John Memorial</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
        data-bs-target="#navCollapse" aria-controls="navCollapse" aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navCollapse">
        <ul class="navbar-nav mx-auto">
          <li class="nav-item"><a class="nav-link active" href="../secretaryDashboard/secretaryDashboard.php">Home</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="managementDropdown" role="button"
              data-bs-toggle="dropdown" aria-expanded="false">Management</a>
            <ul class="dropdown-menu" aria-labelledby="managementDropdown">
              <li><a class="dropdown-item" href="../secretaryAppointment/secretaryAppointment.php">Appointment Setup</a></li>
              <li><a class="dropdown-item" href="../secretaryCemeteryMap/secretaryCemeteryMap.php">Cemetery Map Status</a></li>
              <li><a class="dropdown-item" href="../secretaryReservation/secretaryReservation.php">Reservation Processing</a></li>
              <li><a class="dropdown-item" href="../secretaryPayment/secretaryPayment.php">Payment Recording</a></li>
              <li><a class="dropdown-item" href="../secretaryBurial/secretaryBurial.php">Burial Record Entry</a></li>
              <li><a class="dropdown-item" href="../secretaryMaintenance/secretaryMaintenance.php">Maintenance and Request</a></li>
            </ul>
          </li>
        </ul>

        <div class="dropdown d-none d-lg-flex">
          <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button"
            data-bs-toggle="dropdown" aria-expanded="false">
            <span><?php echo htmlspecialchars($userName); ?></span>
            <small class="text-muted ms-2">(<?php echo htmlspecialchars($userRole); ?>)</small>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="../../../cms.api/logout.php" id="logoutLinkDesktop"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <main class="main-content mt-4">
    <div class="container-fluid">

      <!-- DASHBOARD STATS -->
      <section class="dashboard-overview row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2"><div class="panel metric-card text-center p-3 shadow-sm bg-white rounded"><h6 class="text-muted small">Reservations Today</h6><p class="fw-bold fs-5">2</p></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="panel metric-card text-center p-3 shadow-sm bg-white rounded"><h6 class="text-muted small">Reservations This Week</h6><p class="fw-bold fs-5">10</p></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="panel metric-card text-center p-3 shadow-sm bg-white rounded"><h6 class="text-muted small">Reservations This Month</h6><p class="fw-bold fs-5">25</p></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="panel metric-card text-center p-3 shadow-sm bg-white rounded"><h6 class="text-muted small">Payments Recorded</h6><p class="fw-bold fs-5 text-success">₱45,000</p></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="panel metric-card text-center p-3 shadow-sm bg-white rounded"><h6 class="text-muted small">Pending Service Requests</h6><p class="fw-bold fs-5 text-warning">3</p></div></div>
        <div class="col-6 col-md-4 col-lg-2"><div class="panel metric-card text-center p-3 shadow-sm bg-white rounded"><h6 class="text-muted small">Due Balances</h6><p class="fw-bold fs-5 text-danger">₱17,000</p></div></div>
      </section>

      <div class="row g-4">

        <!-- LEFT COLUMN -->
        <div class="col-lg-6">

          <!-- ONGOING RESERVATIONS -->
          <section class="mb-4">
            <div class="card shadow-sm h-100">
              <div class="card-header d-flex justify-content-between align-items-center card-header-clickable"
                   data-bs-toggle="collapse" data-bs-target="#reservationsCollapse"
                   aria-expanded="true" aria-controls="reservationsCollapse">
                Ongoing Reservations
                <div class="d-flex align-items-center">
                  <a href="../secretaryReservation/secretaryReservation.php" class="btn btn-sm btn-link p-0 me-3">View All</a>
                  <i class="fas fa-chevron-up"></i>
                </div>
              </div>
              <div class="collapse show card-list-scroll" id="reservationsCollapse">
                <div class="list-group list-group-flush">
                  <div class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1"><p class="mb-0 fw-bold">Juan Dela Cruz</p><small class="text-muted">Lot A-10 | Requested: Apr 10, 2025</small></div>
                    <span class="status-badge status-pending">Pending</span>
                  </div>
                  <div class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1"><p class="mb-0 fw-bold">Maria Santos</p><small class="text-muted">Lot B-5 | Approved: Apr 8, 2025</small></div>
                    <span class="status-badge status-reserved">Approved</span>
                  </div>
                  <div class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1"><p class="mb-0 fw-bold">Carlos Reyes</p><small class="text-muted">Lot A-12 | Declined: Apr 5, 2025</small></div>
                    <span class="status-badge status-cancelled">Declined</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- CLIENT SERVICE REQUESTS -->
          <section class="mb-4 mb-lg-0">
            <div class="card shadow-sm h-100">
              <div class="card-header d-flex justify-content-between align-items-center card-header-clickable"
                   data-bs-toggle="collapse" data-bs-target="#requestsCollapse"
                   aria-expanded="true" aria-controls="requestsCollapse">
                Client Service Requests
                <div class="d-flex align-items-center">
                  <a href="../secretaryMaintenance/secretaryMaintenance.php" class="btn btn-sm btn-link p-0 me-3">Manage</a>
                  <i class="fas fa-chevron-up"></i>
                </div>
              </div>
              <div class="collapse show card-list-scroll" id="requestsCollapse">
                <div class="list-group list-group-flush">
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1"><p class="mb-0 fw-bold">Joey Bautista</p><small class="text-muted">Request: Cleaning</small></div>
                    <span class="status-badge status-pending">New</span>
                  </div>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1"><p class="mb-0 fw-bold">Elaine Cruz</p><small class="text-muted">Request: Maintenance</small></div>
                    <span class="status-badge status-reserved">In Progress</span>
                  </div>
                  <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1"><p class="mb-0 fw-bold">Gerald Tan</p><small class="text-muted">Request: Trimming</small></div>
                    <span class="status-badge status-archived">Completed</span>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-lg-6">

          <!-- PAYMENTS -->
          <section class="mb-4">
            <div class="card shadow-sm h-100">
              <div class="card-header d-flex justify-content-between align-items-center card-header-clickable"
                   data-bs-toggle="collapse" data-bs-target="#paymentsCollapse"
                   aria-expanded="true" aria-controls="paymentsCollapse">
                Payments Due / In Progress
                <div class="d-flex align-items-center">
                  <a href="../secretaryPayment/secretaryPayment.php" class="btn btn-sm btn-link p-0 me-3">View All</a>
                  <i class="fas fa-chevron-up"></i>
                </div>
              </div>
              <div class="collapse show card-list-scroll" id="paymentsCollapse">
                <div class="list-group list-group-flush">
                  <div class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1"><p class="mb-0 fw-bold">Ana Lopez</p><small class="text-warning">Balance: ₱5,000 | Due: May 10, 2025</small></div>
                    <span class="status-badge status-pending">Partial</span>
                  </div>
                  <div class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1"><p class="mb-0 fw-bold">Roberto Cruz</p><small class="text-success">Balance: ₱0.00 | Paid in Full</small></div>
                    <span class="status-badge status-reserved">Paid</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- BURIAL RECORDS -->
          <section class="mb-4">
            <div class="card shadow-sm h-100">
              <div class="card-header d-flex justify-content-between align-items-center card-header-clickable"
                   data-bs-toggle="collapse" data-bs-target="#burialCollapse"
                   aria-expanded="true" aria-controls="burialCollapse">
                Recent Burial Records
                <div class="d-flex align-items-center">
                  <a href="../secretaryBurial/secretaryBurial.php" class="btn btn-sm btn-link p-0 me-3">View All</a>
                  <i class="fas fa-chevron-up"></i>
                </div>
              </div>
              <div class="collapse show card-list-scroll" id="burialCollapse">
                <ul class="list-group list-group-flush">
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1"><p class="mb-0 fw-bold">Decedent: Emilio Aguinaldo</p><small class="text-muted">Lot B-01 | Date: Oct 4, 2025</small></div>
                    <button class="btn btn-info btn-sm">View</button>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1"><p class="mb-0 fw-bold">Decedent: Jose Rizal</p><small class="text-muted">Lot A-01 | Date: Sep 20, 2025</small></div>
                    <button class="btn btn-info btn-sm">View</button>
                  </li>
                </ul>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </main>

  <!-- FOOTER -->
  <footer class="footer text-center py-3 mt-3">
    <div class="container d-flex flex-column flex-md-row justify-content-center align-items-center">
      <p class="m-0">
        <strong>Blessed Saint John Memorial</strong> |
        <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
        <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
      </p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="secretaryDashboard.js"></script>
</body>
</html>
