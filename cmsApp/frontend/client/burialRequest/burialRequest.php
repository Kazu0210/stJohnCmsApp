<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['client_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['email'])) {
    // User is not logged in, redirect to login page
    header("Location: ../../auth/login/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Burial Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

    <?php include __DIR__ . '/../clientNavbar.php'; ?>

    <div class="container mt-5 pt-4">
        <h2 class="mb-4">Burial Requests</h2>
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>
            <div class="col-md-4">
                <select id="statusFilter" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-end mb-3">
            <a href="/stJohnCmsApp/cmsApp/frontend/client/burialRequest/submitBurialRequest.php" class="btn btn-warning" id="submitBurialRequestBtn">
                <i class="fas fa-plus me-2"></i>Submit Burial Request
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="burialRequestTable">
                <thead style="background-color: #ffc107; color: #212529;">
                    <tr>
                        <th>#</th>
                        <th>Deceased Name</th>
                        <th>Burial Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Empty for now -->
                </tbody>
            </table>
        </div>
        <nav>
            <ul class="pagination justify-content-center" id="pagination">
                <!-- Pagination items will go here -->
            </ul>
        </nav>
    </div>

    <script>
        // Basic JS for search, filter, and pagination (empty table)
        document.getElementById('searchInput').addEventListener('input', function() {
            // Implement search logic here
        });
        document.getElementById('statusFilter').addEventListener('change', function() {
            // Implement filter logic here
        });
        // Pagination logic placeholder
    </script>

</body>
</html>