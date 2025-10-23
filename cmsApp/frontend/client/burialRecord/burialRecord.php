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
    <title>Client Dashboard - Burial Record</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="burialRecord.css">
</head>
<body>
    <?php include __DIR__ . '/../clientNavbar.php'; ?>

    <!-- ✅ MODIFIED: Changed to container-fluid to use more space -->
    <main class="container-fluid py-4">
        <section class="burial-record card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="m-0">Burial Record Search</h2>
                <button class="btn btn-sm btn-link text-dark text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#searchCollapse" aria-expanded="true" aria-controls="searchCollapse">
                    <i class="fas fa-chevron-down"></i>
                </button>
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
    <script src="burialRecord.js"></script>
</body>
</html>
