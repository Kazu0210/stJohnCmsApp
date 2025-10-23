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
    <title>Audit Logs - Blessed Saint John Memorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="adminAuditLogs.css">
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
                            <li><a class="dropdown-item" href="../adminMaintenance/adminMaintenance.php">Maintenance Management</a></li>
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

    <main class="main-content">
        <div class="card shadow-sm">
            <div class="card-header"><i class="fas fa-clipboard-list me-2"></i>Audit Logs</div>
            <div class="card-body p-3 p-md-4">
                
                <div class="row g-3 mb-4 align-items-center">
                    <div class="col-lg-3 col-md-6">
                        <div class="input-group">
                            <input type="text" id="logSearch" class="form-control rounded-start" placeholder="Search by User ID/Name or Description..." oninput="filterLogs()">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" title="Clear Search" onclick="clearSearch()"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <select id="moduleFilter" class="form-select" onchange="filterLogs()">
                            <option value="All" selected>Module (All)</option>
                            <option value="Users">Users</option>
                            <option value="Reservations">Reservations</option>
                            <option value="Payments">Payments</option>
                            <option value="Burials">Burials</option>
                            <option value="Appointments">Appointments</option>
                            <option value="CemeteryMap">Cemetery Map</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Financials">Financials</option>
                            <option value="System">System/Login</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <select id="actionTypeFilter" class="form-select" onchange="filterLogs()">
                            <option value="All" selected>Action (All)</option>
                            <option value="CREATE">CREATE</option>
                            <option value="UPDATE">UPDATE</option>
                            <option value="DELETE">DELETE</option>
                            <option value="LOGIN">LOGIN</option>
                            <option value="PAYMENT">PAYMENT</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="input-group">
                            <input type="date" id="startDateFilter" class="form-control" title="Start Date" onchange="filterLogs()">
                            <input type="date" id="endDateFilter" class="form-control" title="End Date" onchange="filterLogs()">
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-12">
                        <button class="btn btn-sm filter-btn w-100" id="clearFiltersBtn" onclick="clearFilters()"><i class="fas fa-undo me-1"></i> Clear Filters</button>
                    </div>
                </div>

                <div class="table-responsive rounded">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User ID</th>
                                <th>User Name</th>
                                <th>Module</th>
                                <th>Action Type</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody id="logTableBody">
                            </tbody>
                    </table>
                    <p id="noLogsMessage" class="text-center p-4 d-none">No audit logs found matching your criteria.</p>
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    <button id="prevPageBtn" class="btn btn-sm btn-outline-secondary me-2 rounded-pill" disabled onclick="changePage(-1)">&laquo; Previous</button>
                    <span id="pageInfo" class="align-self-center fw-bold">Page 1 of 1</span>
                    <button id="nextPageBtn" class="btn btn-sm btn-outline-secondary ms-2 rounded-pill" disabled onclick="changePage(1)">Next &raquo;</button>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer text-center py-3">
        <div class="container d-flex flex-column flex-md-row justify-content-center align-items-center">
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="/stJohnCmsApp/cmsApp/frontend/environment.js"></script>
    <script src="adminAuditLogs.js" type="module"></script>
</body>
</html>
