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
    <style>
        :root {
            --gold: #EFBF04;
        }
        .navbar.bg-gold {
            background-color: var(--gold) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .navbar.bg-gold,
        .navbar.bg-gold .navbar-nav .nav-link,
        .navbar.bg-gold .navbar-brand,
        .navbar.bg-gold .dropdown-menu,
        .navbar.bg-gold .dropdown-item {
            color: #000 !important;
        }
        .navbar.bg-gold .dropdown-menu {
            background-color: var(--gold) !important;
        }
        .navbar.bg-gold .dropdown-item.active,
        .navbar.bg-gold .dropdown-item:active,
        .navbar.bg-gold .dropdown-item:focus,
        .navbar.bg-gold .dropdown-item:hover {
            background-color: #fff8d6 !important;
            color: #000 !important;
        }
    </style>
    <!-- Bootstrap CSS (optional, for styling) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Burial Requests - Blessed Saint John Memorial</title>
</head>
<body>
</body>
    <div class="container mt-5">
        <h2 class="mb-4">Burial Requests</h2>
        <table id="burialRequestsTable" class="display table table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Name</th>
                    <th>Deceased Name</th>
                    <th>Date Requested</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch burial requests from API
                $apiUrl = "../../../../cms.api/fetchAllBurialRequests.php";
                $response = @file_get_contents($apiUrl);
                $requests = [];
                if ($response !== false) {
                    $json = json_decode($response, true);
                    if (isset($json['requests'])) {
                        $requests = $json['requests'];
                    }
                }
                foreach ($requests as $row):
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['requestId']) ?></td>
                    <td><?= htmlspecialchars($row['userId']) ?></td>
                    <td><?= htmlspecialchars($row['deceasedName']) ?></td>
                    <td><?= htmlspecialchars($row['burialDate']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm">View</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark bg-gold fixed-top">
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
                        <a class="nav-link dropdown-toggle" href="#" id="adminToolsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Admin Tools
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="adminToolsDropdown">
                            <li><a class="dropdown-item" href="../adminAuditLogs/adminAuditLogs.php">Audit Logs</a></li>
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
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!-- Bootstrap JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom DataTable JS -->
    <script src="adminBurialRequest.js"></script>
</body>
</html>