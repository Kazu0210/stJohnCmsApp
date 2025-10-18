<?php
// adminNavbar.php
// Reusable Admin Navbar Component
if (!isset($userName)) $userName = '';
if (!isset($userRole)) $userRole = '';
?>
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Blessed Saint John Memorial</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item active"><a class="nav-link active" href="../adminDashboard/adminDashboard.php">Home</a></li>
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
                        <li><a class="dropdown-item" href="../adminMaintenance/adminMaintenance.php">Maintenance Management</a></li>
                        <li><a href="../adminBurialRequest/adminBurialRequest.php" class="dropdown-item">Burial Request Management</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" id="adminToolsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Admin Tools
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="adminToolsDropdown">
                        <li><a class="dropdown-item active" href="adminAuditLogs.php">Audit Logs</a></li>
                        <li><a class="dropdown-item" href="../adminUserManagement/adminUserManagement.php">User Management</a></li>
                        <li><a class="dropdown-item" href="../adminReports/adminReports.php">Reports Module</a></li>
                    </ul>
                </li>
            </ul>

            <div class="d-lg-none mt-3 pt-3 border-top border-dark-subtle">
                <div class="d-flex align-items-center mb-2">
                    <span id="user-name-display-mobile" class="fw-bold"><?php echo htmlspecialchars($userName); ?></span>
                    <small class="text-muted ms-2">(<?php echo htmlspecialchars($userRole); ?>)</small>
                </div>
                <a href="../../../../cms.api/logout.php" id="logoutLinkMobile" class="mobile-logout-link">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
        <div class="dropdown d-none d-lg-flex">
            <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span id="user-name-display-desktop"><?php echo htmlspecialchars($userName); ?></span>
                <small class="text-muted ms-2">(<?php echo htmlspecialchars($userRole); ?>)</small>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="../../../../cms.api/logout.php" id="logoutLinkDesktop"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>
