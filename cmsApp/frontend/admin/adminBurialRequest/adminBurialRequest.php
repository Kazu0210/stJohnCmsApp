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
    <!-- Bootstrap CSS (optional, for styling) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Burial Requests - Blessed Saint John Memorial</title>

    <!-- Burial Request Page CSS -->
    <link rel="stylesheet" href="adminBurialRequest.css">
</head>
<body>
    <?php include '../components/adminNavbar.php'; ?>
    <main class="main-content">
        <div class="container-fluid mt-4">
            <h2 class="mb-4">Burial Requests</h2>
            <div class="table-responsive">
                <table id="burialRequestsTable" class="display table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>User ID</th>
                            <th>Lot ID</th>
                            <th>Reservation ID</th>
                            <th>Deceased Name</th>
                            <th>Burial Date</th>
                            <th>Deceased Valid ID</th>
                            <th>Death Certificate</th>
                            <th>Burial Permit</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Table rows will be populated by JavaScript using AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>
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