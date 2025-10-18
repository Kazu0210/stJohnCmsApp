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
    <?php include_once __DIR__ . '/../components/adminNavbar.php'; ?>
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