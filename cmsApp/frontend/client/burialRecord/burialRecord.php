<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Burial Record</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="burialRecord.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <span class="fw-bold">Blessed Saint John Memorial</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="../clientDashboard/clientDashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../cemeteryMap/cemeteryMap.php">Cemetery Map</a></li>
                    <li class="nav-item"><a class="nav-link" href="../lotReservation/lotReservation.php">Lot Reservation</a></li>
                    <li class="nav-item"><a class="nav-link" href="../payment/payment.php">Payment</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="burialRecord.php">Burial Record</a></li>
                    <li class="nav-item"><a class="nav-link" href="../maintenanceServiceRequest/maintenanceServiceRequest.php">Maintenance Request</a></li>
                </ul>
    
                <div class="d-lg-none mt-3 pt-3 border-top border-dark-subtle">
                     <div class="d-flex align-items-center mb-2">
                        <span id="user-name-display-mobile" class="fw-bold">User Name</span>
                    </div>
                    <a href="#" id="logoutLinkMobile" class="mobile-logout-link">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
            
            <div class="dropdown d-none d-lg-block">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="user-name-display-desktop">User Name</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="../../../cms.api/logout.php" id="logoutLinkDesktop">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ✅ MODIFIED: Changed to container-fluid to use more space -->
    <main class="container-fluid py-4">
        <section class="burial-record card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="m-0">Burial Record Search</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#burialRequestModal">
                        <i class="fas fa-plus me-1"></i>Request Burial
                    </button>
                    <button class="btn btn-sm btn-link text-dark text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#searchCollapse" aria-expanded="true" aria-controls="searchCollapse">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
            
            <div class="collapse show" id="searchCollapse">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <label for="searchName" class="form-label">Name</label>
                        <input id="searchName" type="text" class="form-control" placeholder="Type to search name...">
                    </div>
                    <div class="col-6 col-lg-1">
                        <label for="filterArea" class="form-label">Area</label>
                        <input id="filterArea" type="text" class="form-control" placeholder="Area">
                    </div>
                    <div class="col-6 col-lg-1">
                        <label for="filterBlock" class="form-label">Block</label>
                        <input id="filterBlock" type="text" class="form-control" placeholder="Block">
                    </div>
                    <div class="col-6 col-lg-1">
                        <label for="filterRow" class="form-label">Row</label>
                        <input id="filterRow" type="text" class="form-control" placeholder="Row">
                    </div>
                    <div class="col-6 col-lg-1">
                        <label for="filterLot" class="form-label">Lot</label>
                        <input id="filterLot" type="text" class="form-control" placeholder="Lot">
                    </div>
                    <div class="col-12 col-lg-2 ms-auto">
                         <label class="form-label">&nbsp;</label>
                        <button id="clearBtn" class="btn w-100">Clear</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="burial-results card p-4">
            <div class="table-responsive">
                <table id="resultsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Deceased Name</th>
                            <th>Burial Date</th>
                            <th>Area</th>
                            <th>Block</th>
                            <th>Row</th>
                            <th>Lot</th>
                            <th>Valid ID</th>
                            <th>Death Certificate</th>
                            <th>Burial Permit</th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody">
                        <tr><td colspan="9" class="text-center">Loading records...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- ✅ MODIFIED: Updated Modal to be controlled by Bootstrap JS -->
    <div class="modal fade" id="docModal" tabindex="-1" aria-labelledby="docModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="docModalLabel">Document for <span id="modalDeceasedName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="image-container" class="text-center" style="display: none;"></div>
                    <canvas id="pdf-canvas" class="img-fluid"></canvas>
                    <div id="pdfControls" class="d-flex justify-content-center align-items-center mt-2" style="display:none;">
                        <button id="prevPage" class="btn btn-secondary btn-sm me-2">Prev</button>
                        <span id="pageInfo" class="me-2"></span>
                        <button id="nextPage" class="btn btn-secondary btn-sm">Next</button>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <a id="downloadLink" href="#" target="_blank" download class="btn btn-primary">
                        <i class="fas fa-download me-2"></i> Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Burial Request Modal -->
    <div class="modal fade" id="burialRequestModal" tabindex="-1" aria-labelledby="burialRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="burialRequestModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Request Burial for Reserved Lot
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="lot-selection-section">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Select your reserved lot:</strong> Choose the lot where you want to add a deceased person.
                        </div>
                        <div class="mb-3">
                            <label for="reservedLotSelect" class="form-label">Reserved Lot</label>
                            <select id="reservedLotSelect" class="form-select" required>
                                <option value="">Loading your reserved lots...</option>
                            </select>
                        </div>
                        <div id="selectedLotInfo" class="card p-3 bg-light" style="display: none;">
                            <h6 class="card-title">Selected Lot Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Area:</strong> <span id="lotArea">-</span></p>
                                    <p class="mb-1"><strong>Block:</strong> <span id="lotBlock">-</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Row:</strong> <span id="lotRow">-</span></p>
                                    <p class="mb-1"><strong>Lot:</strong> <span id="lotNumber">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="burialRequestForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="deceasedName" class="form-label">Deceased Name *</label>
                                <input type="text" class="form-control" id="deceasedName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="burialDate" class="form-label">Burial Date *</label>
                                <input type="date" class="form-control" id="burialDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="deceasedValidId" class="form-label">Deceased Valid ID *</label>
                                <input type="file" class="form-control" id="deceasedValidId" accept=".pdf,.jpg,.jpeg,.png" required>
                                <div class="form-text">Upload deceased person's valid ID (PDF, JPG, PNG)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="deathCertificate" class="form-label">Death Certificate *</label>
                                <input type="file" class="form-control" id="deathCertificate" accept=".pdf,.jpg,.jpeg,.png" required>
                                <div class="form-text">Upload death certificate (PDF, JPG, PNG)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="burialPermit" class="form-label">Burial Permit *</label>
                                <input type="file" class="form-control" id="burialPermit" accept=".pdf,.jpg,.jpeg,.png" required>
                                <div class="form-text">Upload burial permit (PDF, JPG, PNG)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="burialDepth" class="form-label">Burial Depth *</label>
                                <select class="form-select" id="burialDepth" required>
                                    <option value="">Select depth</option>
                                    <option value="4ft">4 feet</option>
                                    <option value="6ft">6 feet</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="burialNotes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="burialNotes" rows="3" placeholder="Any additional information about the burial..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitBurialRequest">
                        <i class="fas fa-paper-plane me-1"></i>Submit Request
                    </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="burialRecord.js?v=<?php echo time(); ?>"></script>
</body>
</html>
