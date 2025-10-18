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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    $(document).ready(function() {
        let allRequests = [];
        let currentPage = 1;
        const pageSize = 10;

        function renderTable(requests) {
            const tbody = $('#burialRequestTable tbody');
            tbody.empty();
            if (requests.length === 0) {
                tbody.append('<tr><td colspan="5" class="text-center">No burial requests found.</td></tr>');
                return;
            }
            const start = (currentPage - 1) * pageSize;
            const end = Math.min(start + pageSize, requests.length);
            for (let i = start; i < end; i++) {
                const req = requests[i];
                tbody.append(`
                    <tr>
                        <td>${i + 1}</td>
                        <td>${req.deceasedName || ''}</td>
                        <td>${req.burialDate || ''}</td>
                        <td><span class="badge bg-${req.status === 'approved' ? 'success' : req.status === 'pending' ? 'warning' : 'danger'} text-dark">${req.status}</span></td>
                        <td>
                            <a href="#" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                `);
            }
        }

        function renderPagination(requests) {
            const totalPages = Math.ceil(requests.length / pageSize);
            const pagination = $('#pagination');
            pagination.empty();
            if (totalPages <= 1) return;
            for (let i = 1; i <= totalPages; i++) {
                pagination.append(`<li class="page-item${i === currentPage ? ' active' : ''}"><a class="page-link" href="#">${i}</a></li>`);
            }
        }

        function filterRequests() {
            const search = $('#searchInput').val().toLowerCase();
            const status = $('#statusFilter').val();
            let filtered = allRequests.filter(req => {
                let match = true;
                if (search) {
                    match = (req.deceasedName && req.deceasedName.toLowerCase().includes(search)) || (req.burialDate && req.burialDate.includes(search));
                }
                if (status) {
                    match = match && req.status === status;
                }
                return match;
            });
            renderTable(filtered);
            renderPagination(filtered);
        }

        // Fetch burial requests
        $.ajax({
            url: '/stJohnCmsApp/cms.api/fetchClientBurialRequests.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.requests) {
                    allRequests = response.requests;
                    filterRequests();
                }
            }
        });

        // Search and filter events
        $('#searchInput').on('input', function() {
            currentPage = 1;
            filterRequests();
        });
        $('#statusFilter').on('change', function() {
            currentPage = 1;
            filterRequests();
        });
        // Pagination click
        $('#pagination').on('click', 'a', function(e) {
            e.preventDefault();
            currentPage = parseInt($(this).text());
            filterRequests();
        });
    });
    </script>

</body>
</html>