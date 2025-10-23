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
    <title>Burial Record Entry - Blessed Saint John Memorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="secretaryBurial.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">Blessed Saint John Memorial</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                            <li><a class="dropdown-item" href="../secretaryAppointment/secretaryAppointment.php">Appointment Setup</a></li>
                            <li><a class="dropdown-item" href="../secretaryCemeteryMap/secretaryCemeteryMap.php">Cemetery Map Status</a></li>
                            <li><a class="dropdown-item" href="../secretaryReservation/secretaryReservation.php">Reservation Processing</a></li>
                            <li><a class="dropdown-item" href="../secretaryPayment/secretaryPayment.php">Payment Recording</a></li>
                            <li><a class="dropdown-item active" href="../secretaryBurial/secretaryBurial.php">Burial Record Entry</a></li>
                            <li><a class="dropdown-item" href="../secretaryMaintenance/secretaryMaintenance.php">Maintenance and Request</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
             <div class="dropdown d-none d-lg-flex">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Secretary User
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="../../auth/login/login.php" id="logoutLinkDesktop"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

     <main class="main-content">
        <div class="card shadow-sm">
            <div class="card-header">Burial Record Management</div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-12 col-lg-8">
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" title="Clear Search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <select id="statusFilter" class="form-select">
                            <option value="all" selected>Filter by Status (All)</option>
                            <option value="active">Active</option>
                            <option value="exhumed">Exhumed</option>
                            <option value="archived">Archived</option>
                            <option value="deleted">Deleted</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Deceased Name</th>
                                <th>Date of Burial</th>
                                <th class="text-center">Death Cert.</th>
                                <th class="text-center">Burial Permit</th>
                                <th class="text-center">Valid ID</th>
                                <th>Area</th>
                                <th>Block</th>
                                <th>Row</th>
                                <th>Lot</th>
                                <th class="text-center">Status</th>
                                <th>Submitted On</th>
                                <th>Updated On</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="burialTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <div class="modal fade" id="docModal" tabindex="-1" aria-labelledby="docModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="docModalLabel">Document Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="currentDocRecordId">
                    <input type="hidden" id="currentDocType">
                    <p id="docFilename" class="fw-bold text-center"></p>
                    <div class="text-center" id="preview-container">
                        <img id="img-preview" class="img-fluid d-none" alt="Image Preview">
                        <canvas id="pdf-canvas" class="d-none border"></canvas>
                        <div id="no-doc-placeholder" class="d-none text-muted p-4 border rounded">
                            <i class="fas fa-file-excel fa-3x mb-3"></i>
                            <p class="mb-0">No document uploaded.</p>
                        </div>
                    </div>
                    <div id="pdfControls" class="text-center mt-3 d-none">
                        <button id="prevPageBtn" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Prev</button>
                        <span id="pageInfo" class="mx-3 user-select-none"></span>
                        <button id="nextPageBtn" class="btn btn-secondary btn-sm">Next <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div>
                        <button id="deleteDocBtn" type="button" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete</button>
                        <label for="docFileInput" id="uploadLabel" class="btn btn-warning mb-0"><i class="fas fa-sync-alt"></i> Add/ Replace</label>
                        <input type="file" id="docFileInput" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div>
                        <a id="downloadLink" href="#" target="_blank" download class="btn btn-primary"><i class="fas fa-download"></i> Download</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer text-center py-3">
        <div class="container d-flex flex-column flex-md-row justify-content-center align-items-center">
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>

    <div class="modal fade" id="editBurialModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit: <span id="editingRecordName" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editBurialForm">
                        <input type="hidden" id="editRecordId">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Deceased Name</label>
                            <input type="text" class="form-control" id="editName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editBurialDate" class="form-label">Date of Burial</label>
                            <input type="date" class="form-control" id="editBurialDate" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editArea" class="form-label">Area</label>
                                <input type="text" class="form-control" id="editArea" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editBlock" class="form-label">Block</label>
                                <input type="number" class="form-control" id="editBlock" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editRowNumber" class="form-label">Row Number</label>
                                <input type="number" class="form-control" id="editRowNumber" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editLotNumber" class="form-label">Lot Number</label>
                                <input type="number" class="form-control" id="editLotNumber" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Record Status</label>
                            <select id="editStatus" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="exhumed">Exhumed</option>
                                <option value="archived">Archived</option>
                                <option value="deleted">Deleted</option>
                            </select>
                        </div>
                    </form>
                    <div id="editSuccessMessage" class="d-none text-center p-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4>Changes Saved!</h4>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="editCancelBtn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exhumeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Exhumation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Mark **<span id="exhumeRecordName" class="fw-bold"></span>** as "Exhumed"?</p>
                    <input type="hidden" id="exhumeRecordId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info" id="confirmExhumeBtn"><i class="fas fa-box-open me-2"></i>Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="archiveOrDeleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Action for <span id="archiveRecordName" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Archive or delete permanently?</p>
                    <input type="hidden" id="archiveRecordId">
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn"><i class="fas fa-trash-alt me-2"></i>Delete</button>
                    <button type="button" class="btn btn-secondary" id="confirmArchiveBtn"><i class="fas fa-archive me-2"></i>Archive</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="secretaryBurial.js"></script>
</body>
</html>