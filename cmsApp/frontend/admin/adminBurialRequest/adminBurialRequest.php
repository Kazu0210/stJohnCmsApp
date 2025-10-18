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
            <!-- Visible message for empty table -->
            <div id="emptyTableMessage" class="alert alert-info d-none" role="alert">
                No burial requests to display.
            </div>
            <!-- Ajax error message -->
            <div id="ajaxErrorMessage" class="alert alert-danger d-none" role="alert">
                Error loading burial requests. See console for details.
            </div>
            <div class="table-responsive">
                <table id="burialRequestsTable" class="display table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Lot ID</th>
                            <th>Reservation ID</th>
                            <th>Deceased Name</th>
                            <th>Burial Date</th>
                            <th>Deceased Valid ID</th>
                            <th>Death Certificate</th>
                            <th>Burial Permit</th>
                            <th>Status</th>
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
        <!-- Bootstrap Modal for viewing documents -->
        <div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="documentModalLabel">View Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center" id="documentModalBody">
                        <!-- Content will be loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
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