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
    <title>Financial Tracking | Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <!-- jQuery and DataTables CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="adminFinancial.css">
    
    <style>
        /* CSS to ensure custom variables are available for chart/JS use */
        :root {
            --gold: #EFBF04;
            --info: #0d6efd;
            --success: #198754;
            --pending: #ffc107;
            --danger: #dc3545;
        }
        #monthlyIncomeChart { max-height: 350px; }
        /* Adjusted width for the new four-part lot column */
        .table thead th:nth-child(3) { min-width: 120px; }
    </style>
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
                            <li><a class="dropdown-item active" href="adminFinancial.php">Financial Tracking</a></li>
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
                        <span id="user-name-display-mobile" class="fw-bold">Admin</span>
                    </div>
                    <a href="../../../frontend/auth/login/login.php" id="logoutLinkMobile" class="mobile-logout-link">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
            
            <div class="dropdown d-none d-lg-flex">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="user-name-display-desktop">Admin User</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="../../../frontend/auth/login/login.php" id="logoutLinkDesktop"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-content container-fluid">
        <h2 class="mb-4">Financial Tracking & Payment Validation</h2>

        <div class="row mb-5 g-3">
            <div class="col-md-4">
                <div class="card card-income-summary shadow">
                    <div class="card-body">
                        <h5 class="card-title text-muted text-uppercase">Total Income (YTD)</h5>
                        <h3 class="card-text text-success" id="totalIncomeYTD">₱0.00</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-income-summary shadow">
                    <div class="card-body">
                        <h5 class="card-title text-muted text-uppercase">Income This Month</h5>
                        <h3 class="card-text text-info" id="incomeThisMonth">₱0.00</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-income-summary shadow">
                    <div class="card-body">
                        <h5 class="card-title text-muted text-uppercase">Attention Needed</h5>
                        <h3 class="card-text text-danger" id="attentionCount">0 Pending / 0 Deferred</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card shadow mb-5">
            <div class="card-header"><i class="fas fa-money-check-alt me-2"></i>Payments</div>
            <div class="card-body">
                <table id="paymentsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Lot</th>
                            <th>Amount Paid</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Reference/OR No.</th>
                            <th>Date Paid</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Empty table, will be populated by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="footer text-center">
        <div class="container d-flex flex-column flex-md-row justify-content-center align-items-center">
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>


    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel"><i class="fas fa-edit me-2"></i> Validate/Edit Payment Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="paymentForm">
                    <div class="modal-body">
                        <input type="hidden" id="editPaymentId">
                        
                        <p class="mb-1 text-muted">Record for: <strong id="recordClientName"></strong> (Lot: <strong id="recordLot"></strong>)</p>
                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="editMonthDue" class="form-label small">Month Due</label>
                                <input type="text" class="form-control" id="editMonthDue" disabled>
                            </div>
                            <div class="col-md-4">
                                <label for="editAmountPaid" class="form-label small">Amount Paid (₱)</label>
                                <input type="number" step="0.01" class="form-control" id="editAmountPaid" required>
                            </div>
                            <div class="col-md-4">
                                <label for="editStatus" class="form-label small">Update Status</label>
                                <select class="form-select" id="editStatus" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Partially Paid">Partially Paid</option>
                                    <option value="Deferred">Deferred</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editPaymentMethod" class="form-label small">Payment Method</label>
                                <select class="form-select" id="editPaymentMethod" required>
                                    <option value="GCash">GCash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cash">Cash</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editReference" class="form-label small">Reference/OR No.</label>
                                <input type="text" class="form-control" id="editReference">
                            </div>
                        </div>

                        <div class="row mb-3 align-items-center border-top pt-3">
                            <div class="col-md-8">
                                <label class="form-label small mb-0">Digital Proof Status</label>
                                <p id="proofStatusValue" class="fw-bold mb-0 text-truncate">None Uploaded</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="viewProofBtn" disabled>
                                    <i class="fas fa-file-alt me-1"></i> View Proof
                                </button>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes & Validate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="proofViewerModal" tabindex="-1" aria-labelledby="proofViewerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="proofViewerModalLabel"><i class="fas fa-eye me-2"></i> Digital Proof Viewer: <span id="proofClientName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    
                    <img id="proofImage" class="img-fluid d-none" alt="Proof of Payment" style="max-height: 80vh; margin: auto;"> 
                    
                    <canvas id="proofCanvas" class="d-none border" style="max-width: 100%; height: auto; margin: auto;"></canvas>
                    
                    <div id="proofLoadingMessage" class="d-none p-5">
                        <div class="spinner-border text-gold" role="status"></div>
                        <p class="mt-2 mb-0">Loading file preview...</p>
                    </div>
                    
                    <div id="proofPlaceholder" class="d-none text-muted p-5 border-bottom">
                        </div>
                    
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="#" class="btn btn-success" id="proofDownloadLink" download>
                        <i class="fas fa-download me-1"></i> Download Original File
                    </a>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="cancelReservationModal" tabindex="-1" aria-labelledby="cancelReservationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelReservationModalLabel"><i class="fas fa-trash-alt me-2"></i> Cancel Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This is a placeholder for the Cancel Reservation functionality.</p>
                    <p class="text-danger">Are you sure you want to cancel the lot reservation for this client?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="adminFinancial.js"></script>
    <script src="fetchPayments.js"></script>
</body>
</html>
