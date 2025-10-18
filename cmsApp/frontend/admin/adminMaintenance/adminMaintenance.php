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
    <title>Maintenance Management - Blessed Saint John Memorial (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="adminMaintenance.css"> 
</head>
<body>
    <?php include '../components/adminNavbar.php'; ?>
    <div class="main-content container-fluid">
        <h1 class="mb-4">Maintenance Service Management</h1>

        <div class="row g-3 mb-5 justify-content-center">
            <div class="col-sm-6 col-md-4">
                <div class="card text-center dashboard-card border-start border-warning border-5">
                    <div class="card-body">
                        <h5 class="card-title text-warning"><i class="fas fa-clock me-2"></i>Pending Requests</h5>
                        <p class="fs-2 fw-bold"><span id="pendingCount">0</span></p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="card text-center dashboard-card border-start border-success border-5">
                    <div class="card-body">
                        <h5 class="card-title text-success"><i class="fas fa-check-circle me-2"></i>Completed (Month)</h5>
                        <p class="fs-2 fw-bold"><span id="completedCount">0</span></p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="card text-center dashboard-card border-start border-danger border-5.php">
                    <div class="card-body">
                        <h5 class="card-title text-danger"><i class="fas fa-times-circle me-2"></i>Cancelled (Total)</h5>
                        <p class="fs-2 fw-bold"><span id="cancelledCount">0</span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 m-0"><i class="fas fa-list-alt me-2"></i>Service Request Records</h2>
            </div>
            
            <div class="card-body pt-3 pb-2">
                <div class="row g-3 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label for="searchClient" class="form-label visually-hidden">Search</label>
                        <input type="text" class="form-control auto-filter-trigger" id="searchClient" placeholder="Search by Client/Lot No.">
                    </div>
                    <div class="col-md-3">
                        <label for="filterServiceType" class="form-label visually-hidden">Service Type</label>
                        <select class="form-select auto-filter-trigger" id="filterServiceType">
                            <option value="" selected>Service Type (All)</option>
                            <option value="General Cleaning">General Cleaning</option>
                            <option value="Grass Trimming">Grass Trimming</option>
                            <option value="Minor Repair">Minor Repair</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterStatus" class="form-label visually-hidden">Status</label>
                        <select class="form-select auto-filter-trigger" id="filterStatus">
                            <option value="" selected>Status (All)</option>
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn clear-filter-btn w-100" type="button" id="clearFiltersBtn"><i class="fas fa-undo me-1"></i> Clear Filters</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="maintenanceTable">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th class="text-center">Area</th>
                                <th class="text-center">Block</th>
                                <th class="text-center">Row No.</th>
                                <th class="text-center">Lot No.</th>
                                <th>Requested Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="maintenanceTableBody">
                            <!-- Rows will be injected by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <span class="text-muted small">Showing <span id="recordStart">1</span> to <span id="recordEnd">10</span> of <span id="recordTotal">15</span> records</span>
                    <nav>
                        <ul class="pagination pagination-sm m-0">
                            <li class="page-item" id="prevPage"><a class="page-link" href="#" aria-label="Previous">Previous</a></li>
                            <li class="page-item" id="nextPage"><a class="page-link" href="#" aria-label="Next">Next</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer mt-5">
        <div class="container text-center">
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>
    
    <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="requestModalLabel">Service Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm">
                        <input type="hidden" id="modalRequestId">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Client Name:</strong> <span id="modalClientName"></span></p>
                                <p><strong>Contact:</strong> <span id="modalClientContact"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Lot Location:</strong> <span id="modalLotLocation"></span></p>
                                <p><strong>Service Type:</strong> <span id="modalServiceType"></span></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Requested Date:</strong> <span id="modalRequestedDateTime"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Submitted On:</strong> <span id="modalSubmittedOn"></span></p>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <p><strong>Client Additional Notes:</strong></p>
                            <p class="p-2 border rounded bg-light" id="modalClientNotes" class="fst-italic">No client notes provided.</p>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="updateStatus" class="form-label">Update Status</label>
                                <select class="form-select" id="updateStatus" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Completed">Completed (Admin Only)</option>
                                    <option value="Cancelled">Cancelled (Admin Only)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="scheduledDateTime" class="form-label">Scheduled Date/Time (For 'Pending' status update)</label>
                                <input type="datetime-local" class="form-control" id="scheduledDateTime">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="adminNotes" class="form-label">Admin/Secretary Internal Notes</label>
                            <textarea class="form-control" id="adminNotes" rows="3" placeholder="Enter notes regarding scheduling, assigned personnel, or completion details."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveStatusBtn"><i class="fas fa-save me-1"></i> Save Status Update</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="adminMaintenance.js"></script>
</body>
</html>
