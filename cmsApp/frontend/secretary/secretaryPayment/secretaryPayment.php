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
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Payment Recording - BSJM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="secretaryPayment.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">Blessed Saint John Memorial</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navCollapse" aria-controls="navCollapse" aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navCollapse">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="../secretaryDashboard/secretaryDashboard.php">Home</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="managementDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">Management</a>
                        <ul class="dropdown-menu" aria-labelledby="managementDropdown">
                            <li><a class="dropdown-item" href="../secretaryAppointment/secretaryAppointment.php">Appointment Setup</a></li>
                            <li><a class="dropdown-item" href="../secretaryCemeteryMap/secretaryCemeteryMap.php">Cemetery Map Status</a></li>
                            <li><a class="dropdown-item" href="../secretaryReservation/secretaryReservation.php">Reservation Processing</a></li>
                            <li><a class="dropdown-item active" href="../secretaryPayment/secretaryPayment.php">Payment Recording</a></li>
                            <li><a class="dropdown-item" href="../secretaryBurial/secretaryBurial.php">Burial Record Entry</a></li>
                            <li><a class="dropdown-item" href="../secretaryMaintenance/secretaryMaintenance.php">Maintenance and Request</a></li>
                        </ul>
                    </li>
                </ul>

                <div class="dropdown d-none d-lg-flex">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">Secretary User</a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="../../auth/login/login.php" id="logoutLinkDesktop"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content container mt-5 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark"><i class="fas fa-money-check-alt me-2 text-warning"></i>Payment Recording</h2>
            <button class="btn btn-outline-secondary" onclick="applyFilters()"><i class="fas fa-sync-alt me-2"></i>Refresh List</button>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filterClient" class="form-label fw-semibold">Client Name / Lot</label>
                        <input type="text" class="form-control" id="filterClient" placeholder="Search client or lot...">
                    </div>
                    <div class="col-md-3">
                        <label for="filterStatus" class="form-label fw-semibold">Reservation Status</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">All</option>
                            <option value="Pending Reservation">Pending</option>
                            <option value="Reserved">Reserved (Installment)</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Completed">Completed (Paid Off)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterInstallment" class="form-label fw-semibold">Installment Status</label>
                        <select id="filterInstallment" class="form-select">
                            <option value="">All</option>
                            <option value="Pending">Pending Payment</option>
                            <option value="Paid">Installment Paid</option>
                            <option value="Deferred">Deferred</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2 align-items-end">
                        <button type="reset" class="btn btn-outline-secondary w-100"><i class="fas fa-undo me-2"></i>Clear Filters</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="fas fa-list me-2 text-secondary"></i>Payment Records</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-warning">
                            <tr>
                                <th>#</th>
                                <th>Client Name</th>
                                <th>Area</th>
                                <th>Block</th>
                                <th>Row</th>
                                <th>Lot No.</th>
                                <th>Monthly Installment Due</th>
                                <th>Date Paid</th>
                                <th>Amount Paid</th>
                                <th>Method</th> <th>Reference / OR No.</th>
                                <th>Res. Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="paymentTableBody">
                            </tbody>
                    </table>
                </div>
                
                <nav id="paginationContainer" class="d-flex justify-content-center align-items-center mt-4"></nav>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card shadow-sm mt-4">
              <div class="card-body">
                  <h5 class="fw-bold mb-3"><i class="fas fa-edit me-2 text-secondary"></i>Manual Cash Payment Entry</h5>
                  
                  <form id="manualPaymentForm" class="row g-3">
                      
                      <div class="col-md-3">
                          <label for="clientName" class="form-label fw-semibold">Client Name</label>
                          <input type="text" id="clientName" class="form-control" placeholder="Enter client name" required>
                      </div>
                      
                      <div class="col-md-2">
                          <label for="cashArea" class="form-label fw-semibold">Area</label>
                          <input type="text" id="cashArea" class="form-control" value=""> 
                      </div>
                      <div class="col-md-2">
                          <label for="cashBlock" class="form-label fw-semibold">Block</label>
                          <input type="text" id="cashBlock" class="form-control" value=""> 
                      </div>
                      <div class="col-md-2">
                          <label for="cashRow" class="form-label fw-semibold">Row</label>
                          <input type="text" id="cashRow" class="form-control" value=""> 
                      </div>
                      <div class="col-md-3">
                          <label for="cashLot" class="form-label fw-semibold">Lot No.</label>
                          <input type="text" id="cashLot" class="form-control" value=""> 
                      </div>

                      <div class="col-md-6">
                          <label for="amountPaid" class="form-label fw-semibold">Amount Paid</label>
                          <input type="number" id="amountPaid" class="form-control" placeholder="₱" required>
                      </div>
                      <div class="col-md-6">
                          <label for="paymentDate" class="form-label fw-semibold">Payment Date</label>
                          <input type="date" id="paymentDate" class="form-control" required>
                      </div>

                      <div class="col-12 mt-4">
                          <button type="submit" class="btn btn-warning text-white fw-semibold">
                              <i class="fas fa-save me-2"></i>Record Cash Payment
                          </button>
                      </div>
                  </form>
              </div>
          </div>
    </main>

    <div class="modal fade" id="manageModal" tabindex="-1" aria-labelledby="manageLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="manageLabel"><i class="fas fa-tasks me-2"></i>Payment Management for <span id="clientNameInModal"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="manageContent"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="documentLabel"><i class="fas fa-file-invoice me-2"></i>Proof of Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p id="docFilename" class="fw-bold fs-5 text-muted">Document Name.jpg</p>
                    <img id="docPreview" src="" alt="Proof of Payment" class="img-fluid border rounded" style="max-height: 70vh;">
                    <p class="mt-3 text-danger" id="docError" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>No proof of payment document available.
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a id="docDownloadLink" href="#" download class="btn btn-success disabled"><i class="fas fa-download me-2"></i>Download</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <div id="appToast" class="toast align-items-center text-bg-light border-0" role="alert" aria-live="polite" aria-atomic="true">
            <div class="d-flex">
                <div id="appToastBody" class="toast-body"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <footer class="footer text-center py-3 mt-3">
        <div class="container d-flex flex-column flex-md-row justify-content-center align-items-center">
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="secretaryPayment.js"></script>
</body>
</html>