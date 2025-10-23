<?php
// Include authentication helper
require_once '../../../../cms.api/auth_helper.php';

// Require authentication - redirect to login if not logged in
requireAuth('../../auth/login/login.php');

// Require admin or secretary role for this page
// NOTE: For an Admin page, you might want to change this to requireAdmin()
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="secretaryReservation.css"> 
  <title>Appointment Setup - BSJM</title>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top navbar-custom"> 
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">Blessed Saint John Memorial</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="../secretaryDashboard/secretaryDashboard.php">Home</a></li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="managementDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Management
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="managementDropdown">
                            <li><a class="dropdown-item active" href="../secretaryAppointment/secretaryAppointment.php">Appointment Setup</a></li>
                            <li><a class="dropdown-item" href="../secretaryCemeteryMap/secretaryCemeteryMap.php">Cemetery Map Status</a></li>
                            <li><a class="dropdown-item" href="../secretaryReservation/secretaryReservation.php">Reservation Processing</a></li>
                            <li><a class="dropdown-item" href="../secretaryPayment/secretaryPayment.php">Payment Recording</a></li>
                            <li><a class="dropdown-item" href="../secretaryBurial/secretaryBurial.php">Burial Record Entry</a></li>
                            <li><a class="dropdown-item" href="../secretaryMaintenance/secretaryMaintenance.php">Maintenance and Request</a></li>
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

                <div class="dropdown d-none d-lg-flex">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="user-name-display-desktop"><?php echo htmlspecialchars($userName); ?></span>
                        <small class="text-muted ms-2">(<?php echo htmlspecialchars($userRole); ?>)</small>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="../../../../cms.api/logout.php" id="logoutLinkDesktop"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

   <main class="main-content">
        <div class="card shadow-sm">
             <div class="card-header">Lot Reservation Management</div>
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 mb-4">
                    <div class="col-lg-8">
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search by Client Name, Lot, etc...">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" title="Clear Search"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <select id="statusFilter" class="form-select">
                            <option value="all" selected>Filter by Status (All)</option>
                            <option value="pending">Pending</option>
                            <option value="reserved">Reserved</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Address</th>
                                <th>Contact</th>
                                <th class="text-center">Client ID</th>
                                <th>Reservation Date</th>
                                <th>Area</th>
                                <th>Block</th>
                                <th>Row</th>
                                <th>Lot</th>
                                <th>Lot Type</th>
                                <th>Depth</th>
                                <th>Amount</th>
                                <th class="text-center">Status</th>
                                <th>Submitted On</th>
                                <th>Updated On</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="reservationTableBody"></tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="d-flex align-items-center">
                        <label for="entriesPerPage" class="form-label me-2 mb-0">Show:</label>
                        <select id="entriesPerPage" class="form-select form-select-sm" style="width: auto;">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="ms-2 text-muted">entries</span>
                    </div>
                    
                    <div id="paginationInfo" class="text-muted">
                        Showing 0 to 0 of 0 entries
                    </div>
                    
                    <nav aria-label="Reservation pagination">
                        <ul class="pagination pagination-sm mb-0" id="paginationControls">
                            <li class="page-item disabled">
                                <button class="page-link" id="prevPage" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </button>
                            </li>
                            <li class="page-item active">
                                <button class="page-link" data-page="1">1</button>
                            </li>
                            <li class="page-item disabled">
                                <button class="page-link" id="nextPage" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer text-center py-3">
        <div class="container d-flex flex-column flex-md-row justify-content-center align-items-center">
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>

    <div class="modal fade" id="docModal" tabindex="-1" aria-labelledby="docModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="docModalLabel">Client ID Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="docFilename" class="fw-bold text-center"></p>
                    <div class="text-center">
                        <img id="img-preview" class="img-fluid d-none" alt="Image Preview">
                        <canvas id="pdf-canvas" class="d-none border"></canvas>
                        <div id="no-image-placeholder" class="d-none text-muted p-4 border rounded">
                            <i class="fas fa-image fa-3x mb-3"></i>
                            <p class="mb-0">No image available</p>
                        </div>
                    </div>
                    <div id="pdfControls" class="text-center mt-3 d-none">
                        <button id="prevPage" class="btn btn-secondary btn-sm">Prev</button>
                        <span id="pageInfo" class="mx-3"></span>
                        <button id="nextPage" class="btn btn-secondary btn-sm">Next</button>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div>
                        <a id="downloadLink" href="#" target="_blank" download class="btn btn-primary me-2"><i class="fas fa-download"></i> Download</a>
                        <label for="replaceFileInput" class="btn btn-warning mb-0 me-2"><i class="fas fa-sync-alt"></i> Add</label>
                        <input type="file" id="replaceFileInput" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
                        <button type="button" class="btn btn-danger" id="deleteBtn"><i class="fas fa-trash-alt"></i> Delete</button>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editReservationModal" tabindex="-1" aria-labelledby="editReservationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editReservationModalLabel"><i class="fas fa-edit me-2"></i>Edit Reservation for <span id="editingClientName" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editReservationForm" class="row g-3">
                        <h6 class="col-12 mt-2">Client Information</h6>
                        <div class="col-md-6"><label for="editClientName" class="form-label">Client Name</label><input type="text" class="form-control" id="editClientName" required></div>
                        <div class="col-md-6"><label for="editClientAddress" class="form-label">Address</label><input type="text" class="form-control" id="editClientAddress"></div>
                        <div class="col-md-6"><label for="editClientContact" class="form-label">Contact Number</label><input type="text" class="form-control" id="editClientContact"></div>
                        
                        <h6 class="col-12 mt-4">Reservation Details</h6>
                        <div class="col-md-6"><label for="editReservationDate" class="form-label">Reservation Date</label><input type="date" class="form-control" id="editReservationDate"></div>
                        <div class="col-md-3"><label for="editArea" class="form-label">Area</label><input type="text" class="form-control" id="editArea"></div>
                        <div class="col-md-3"><label for="editBlock" class="form-label">Block</label><input type="text" class="form-control" id="editBlock"></div>
                        <div class="col-md-3"><label for="editRow" class="form-label">Row</label><input type="text" class="form-control" id="editRow"></div>
                        <div class="col-md-3"><label for="editLot" class="form-label">Lot</label><input type="text" class="form-control" id="editLot"></div>
                        
                        <div class="col-md-6">
                            <label for="editLotType" class="form-label">Preferred Lot Type</label>
                            <select id="editLotType" class="form-select" required>
                                <option value="1">Regular Lot (₱50,000)</option>
                                <option value="2">Regular Lot (₱60,000)</option>
                                <option value="3">Premium Lot (₱70,000)</option>
                                <option value="4">Mausoleum Inside (₱500,000)</option>
                                <option value="5">Mausoleum Roadside (₱600,000)</option>
                                <option value="6">4-Lot Package (₱300,000)</option>
                                <option value="7">Exhumation (₱15,000)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="editStatus" class="form-label">Reservation Status</label>
                            <select id="editStatus" class="form-select" required>
                                <option value="Pending">Pending</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Archived">Archived</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6" id="burialDepthField">
                            <label for="editBurialDepth" class="form-label">Burial Depth</label>
                            <select id="editBurialDepth" class="form-select" required>
                                <option value="4ft">4ft</option>
                                <option value="6ft">6ft</option>
                                <option value="N/A">N/A</option>
                            </select>
                        </div>
                    </form>
                    
                    <div id="editSuccessMessage" class="d-none text-center p-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4>Changes Saved!</h4>
                        <p>The reservation has been successfully updated.</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="editCancelBtn">Cancel</button>
                    <button type="submit" form="editReservationForm" class="btn btn-primary" id="saveEditBtn">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cancelReservationModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Confirm Cancellation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p id="cancelModalText"></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="button" id="confirmCancelBtn" class="btn btn-danger">Confirm Cancellation</button></div></div></div></div>
    <div class="modal fade" id="archiveOrDeleteModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="archiveModalTitle">Choose an Action</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p id="archiveModalText"></p></div><div class="modal-footer d-flex justify-content-between"><button type="button" class="btn btn-danger" id="confirmDeleteBtn"><i class="fas fa-trash-alt me-2"></i>Delete Permanently</button><button type="button" class="btn btn-secondary" id="confirmArchiveBtn"><i class="fas fa-archive me-2"></i>Archive</button></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script type="module" src="/stJohnCmsApp/cmsApp/frontend/environment.js"></script>

    <script src="secretaryReservation.js"></script>
</body>
</html>