<?php
// clientNavbar.php
// Updated client navbar component with Bootstrap and user dropdown
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<nav class="navbar navbar-expand-lg fixed-top shadow-sm">
  <div class="container-fluid">
  <a class="navbar-brand d-flex align-items-center gap-2" href="/stJohnCmsApp/cmsApp/frontend/client/clientDashboard/clientDashboard.php">
      <span class="fw-bold">Cemetery Management System</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link active" aria-current="page" href="/stJohnCmsApp/cmsApp/frontend/client/clientDashboard/clientDashboard.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="/stJohnCmsApp/cmsApp/frontend/client/cemeteryMap/cemeteryMap.php">Cemetery Map</a></li>
        <li class="nav-item"><a class="nav-link" href="/stJohnCmsApp/cmsApp/frontend/client/lotReservation/lotReservation.php">Lot Reservation</a></li>
        <!-- <li class="nav-item"><a class="nav-link" href="/stJohnCmsApp/cmsApp/frontend/client/payment/payment.php">Payment</a></li> -->
        <li class="nav-item"><a class="nav-link" href="/stJohnCmsApp/cmsApp/frontend/client/burialRecord/burialRecord.php">Burial Record</a></li>
        <li class="nav-item"><a class="nav-link" href="/stJohnCmsApp/cmsApp/frontend/client/maintenanceServiceRequest/maintenanceServiceRequest.php">Maintenance Request</a></li>
      </ul>
      <div class="dropdown d-none d-lg-block">
        <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <span id="user-name-display-desktop">
            <?php 
            // Display user name from session, fallback to email if name not available
            if (isset($_SESSION['firstName']) && isset($_SESSION['lastName'])) {
              echo htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName']);
            } elseif (isset($_SESSION['username'])) {
              echo htmlspecialchars($_SESSION['username']);
            } elseif (isset($_SESSION['email'])) {
              echo htmlspecialchars($_SESSION['email']);
            } else {
              echo 'Client';
            }
            ?>
          </span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
          <li><a class="dropdown-item" href="/stJohnCmsApp/cms.api/logout.php" id="logoutLinkDesktop">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
          </a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
