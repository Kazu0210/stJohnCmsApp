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
                        
                        <th>Lot ID</th>
                        <th>Deceased Name</th>
                        <th>Burial Date</th>
                        <th>Deceased Valid ID</th>
                        <th>Death Certificate</th>
                        <th>Burial Permit</th>
                        <th>Status</th>
                        <th>Submitted On</th>
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

                <!-- Bootstrap Modal for Document/Image Preview -->
                <div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="documentModalLabel">Document Preview</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center" id="documentModalBody">
                                <!-- Content will be injected by JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Burial Request Modal -->
                <div class="modal fade" id="editBurialModal" tabindex="-1" aria-labelledby="editBurialModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editBurialModalLabel">Edit Burial Request</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editBurialForm">
                                    <input type="hidden" id="editRequestId" name="requestId">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="editLotId" class="form-label">Lot ID</label>
                                            <input type="text" class="form-control" id="editLotId" name="lotId" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="editDeceasedName" class="form-label">Deceased Name</label>
                                            <input type="text" class="form-control" id="editDeceasedName" name="deceasedName">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="editBurialDate" class="form-label">Burial Date</label>
                                            <input type="date" class="form-control" id="editBurialDate" name="burialDate">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="editStatus" class="form-label">Status</label>
                                            <select class="form-select" id="editStatus" name="status" disabled>
                                                <option value="pending">Pending</option>
                                                <option value="approved">Approved</option>
                                                <option value="rejected">Rejected</option>
                                            </select>
                                                <input type="hidden" name="status" id="hiddenStatus" value="">
                                        </div>
                                        <div class="col-md-12">
                                            <hr>
                                            <h6 class="mb-3">Documents</h6>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="editDeceasedValidId" class="form-label">Deceased Valid ID</label>
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="editDeceasedValidId" name="deceasedValidId" accept=".jpg,.jpeg,.png,.pdf">
                                                <a href="#" id="editDeceasedValidIdLink" target="_blank" class="btn btn-outline-primary">View</a>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="editDeathCertificate" class="form-label">Death Certificate</label>
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="editDeathCertificate" name="deathCertificate" accept=".jpg,.jpeg,.png,.pdf">
                                                <a href="#" id="editDeathCertificateLink" target="_blank" class="btn btn-outline-primary">View</a>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="editBurialPermit" class="form-label">Burial Permit</label>
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="editBurialPermit" name="burialPermit" accept=".jpg,.jpeg,.png,.pdf">
                                                <a href="#" id="editBurialPermitLink" target="_blank" class="btn btn-outline-primary">View</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-warning">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    $(document).ready(function() {
        // Delete button click handler
        $(document).on('click', '.btn-delete-burial', function(e) {
            e.preventDefault();
            const requestId = $(this).data('id');
            if (confirm('Are you sure you want to delete this burial request?')) {
                $.ajax({
                    url: '/stJohnCmsApp/cms.api/deleteBurialRequest.php',
                    type: 'POST',
                    data: { requestId: requestId },
                    success: function(response) {
                        let res = response;
                        if (typeof response === 'string') {
                            try { res = JSON.parse(response); } catch {}
                        }
                        if (res.success) {
                            alert('Burial request deleted successfully!');
                            location.reload();
                        } else {
                            alert(res.message || 'Delete failed.');
                        }
                    },
                    error: function() {
                        alert('An error occurred while deleting.');
                    }
                });
            }
        });
        // Edit button click handler
        $(document).on('click', '.btn-edit-burial', function(e) {
            e.preventDefault();
            const row = $(this).closest('tr');
            // Get data from row
            const rowIndex = row.index();
            const req = allRequests[rowIndex];
            // Populate modal fields
            $('#editRequestId').val(req.requestId);
            $('#editLotId').val(req.lotId);
            $('#editDeceasedName').val(req.deceasedName);
            $('#editBurialDate').val(req.burialDate);
            $('#editDeceasedValidIdLink').attr('href', req.deceasedValidId ? `/stJohnCmsApp/uploads/burial_requests/${req.deceasedValidId.split('/').pop()}` : '#');
            $('#editDeathCertificateLink').attr('href', req.deathCertificate ? `/stJohnCmsApp/uploads/burial_requests/${req.deathCertificate.split('/').pop()}` : '#');
            $('#editBurialPermitLink').attr('href', req.burialPermit ? `/stJohnCmsApp/uploads/burial_requests/${req.burialPermit.split('/').pop()}` : '#');
            $('#editStatus').val(req.status);
            $('#editCreatedAt').val(req.createdAt);
            $('#editUpdatedAt').val(req.updatedAt);
            // Show modal
            var modal = new bootstrap.Modal(document.getElementById('editBurialModal'));
            modal.show();
        });
        // Save Changes button handler for edit modal
        $(document).on('click', '#editBurialModal .btn-warning', function(e) {
            e.preventDefault();
                // Sync hidden status value before submitting
                $('#hiddenStatus').val($('#editStatus').val());
                var form = $('#editBurialForm')[0];
                var formData = new FormData(form);
                // Optionally, add a burialRequestId if needed for backend
                // formData.append('burialRequestId', $('#editLotId').val());
            $.ajax({
                url: '/stJohnCmsApp/cms.api/updateBurialRequest.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // You may want to parse response and show a message
                    if (response.success) {
                        alert('Burial request updated successfully!');
                        // Optionally, refresh the table
                        location.reload();
                    } else {
                        alert(response.message || 'Update failed.');
                    }
                },
                error: function() {
                    alert('An error occurred while updating.');
                }
            });
        });
        let allRequests = [];
        let currentPage = 1;
        const pageSize = 10;

        function getFileType(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].includes(ext)) return "image";
            if (["pdf"].includes(ext)) return "pdf";
            return "other";
        }

        function renderTable(requests) {
            const tbody = $('#burialRequestTable tbody');
            tbody.empty();
            if (requests.length === 0) {
                tbody.append('<tr><td colspan="10" class="text-center">No burial requests found.</td></tr>');
                return;
            }
            const start = (currentPage - 1) * pageSize;
            const end = Math.min(start + pageSize, requests.length);
            for (let i = start; i < end; i++) {
                const req = requests[i];
                const deceasedValidIdUrl = req.deceasedValidId ? `/stJohnCmsApp/uploads/burial_requests/${req.deceasedValidId.split('/').pop()}` : '';
                const deathCertificateUrl = req.deathCertificate ? `/stJohnCmsApp/uploads/burial_requests/${req.deathCertificate.split('/').pop()}` : '';
                const burialPermitUrl = req.burialPermit ? `/stJohnCmsApp/uploads/burial_requests/${req.burialPermit.split('/').pop()}` : '';
                tbody.append(`
                    <tr>
                        <td>${req.lotId || ''}</td>
                        <td>${req.deceasedName || ''}</td>
                        <td>${req.burialDate || ''}</td>
                        <td>${req.deceasedValidId ? `<a href="#" class="view-doc" data-url="${deceasedValidIdUrl}" data-type="${getFileType(deceasedValidIdUrl)}">View</a>` : ''}</td>
                        <td>${req.deathCertificate ? `<a href="#" class="view-doc" data-url="${deathCertificateUrl}" data-type="${getFileType(deathCertificateUrl)}">View</a>` : ''}</td>
                        <td>${req.burialPermit ? `<a href="#" class="view-doc" data-url="${burialPermitUrl}" data-type="${getFileType(burialPermitUrl)}">View</a>` : ''}</td>
                        <td><span class="badge bg-${req.status === 'approved' ? 'success' : req.status === 'pending' ? 'warning' : 'danger'} text-dark">${req.status}</span></td>
                        <td>${req.createdAt || ''}</td>
                        <td>
                            <a href="#" class="btn btn-sm btn-warning me-1 btn-edit-burial" title="Edit"><i class="fas fa-edit"></i></a>
                            ${req.status === 'pending' ? `<a href="#" class="btn btn-sm btn-danger btn-delete-burial" data-id="${req.requestId}" title="Delete"><i class="fas fa-trash"></i></a>` : ''}
                        </td>
                    </tr>
                `); 
            }
        }
        // Handle document modal view
        $(document).on('click', '.view-doc', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            const type = $(this).data('type');
            let content = '';
            if (type === 'image') {
                content = `<img src="${url}" alt="Document" class="img-fluid" style="max-height:500px;">`;
            } else if (type === 'pdf') {
                content = `<iframe src="${url}" width="100%" height="500px"></iframe>`;
            } else {
                content = `<a href="${url}" target="_blank">Download File</a>`;
            }
            $('#documentModalBody').html(content);
            var modal = new bootstrap.Modal(document.getElementById('documentModal'));
            modal.show();
        });

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