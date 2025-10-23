<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lot Reservation - Blessed Saint John Memorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="lotReservation.css">
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
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="lotReservation.php">Lot Reservation</a></li>
                    <li class="nav-item"><a class="nav-link" href="../payment/payment.php">Payment</a></li>
                    <li class="nav-item"><a class="nav-link" href="../burialRecord/burialRecord.php">Burial Record</a></li>
                    <li class="nav-item"><a class="nav-link" href="../maintenanceServiceRequest/maintenanceServiceRequest.php">Maintenance Request</a></li>
                </ul>

                <div class="d-lg-none mt-3 pt-3 border-top border-dark-subtle">
                     <div class="d-flex align-items-center mb-2">
                        <span id="user-name-display-mobile" class="fw-bold">User Name</span>
                    </div>
                    <a href="../../auth/login/login.php" id="logoutLinkMobile" class="mobile-logout-link">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
            
            <div class="dropdown d-none d-lg-block">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="user-name-display-desktop">User Name</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="../../auth/login/login.php" id="logoutLinkDesktop">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-content container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="pricing-container card p-4 mb-4">
                    <h2>Cemetery Lot and Mausoleum Options</h2>
                    <p class="payment-terms">All lots are payable within 4 years and 2 months (50 months) and can be paid monthly or in advance.</p>
                
                    <div class="pricing-section">
                        <h3>Burial Lots</h3>
                        <div class="table-responsive">
                            <table class="pricing-table table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Price</th>
                                        <th>Monthly Payment</th>
                                        <th>Depth Options</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td data-label="Type">Regular Lot</td>
                                        <td data-label="Price">₱50,000</td>
                                        <td data-label="Monthly Payment">₱1,000/month</td>
                                        <td data-label="Depth Options">4 feet or 6 feet</td>
                                    </tr>
                                    <tr>
                                        <td data-label="Type">Regular Lot</td>
                                        <td data-label="Price">₱60,000</td>
                                        <td data-label="Monthly Payment">₱1,200/month</td>
                                        <td data-label="Depth Options">4 feet or 6 feet</td>
                                    </tr>
                                    <tr>
                                        <td data-label="Type">Premium Lot</td>
                                        <td data-label="Price">₱70,000</td>
                                        <td data-label="Monthly Payment">₱1,400/month</td>
                                        <td data-label="Depth Options">4 feet or 6 feet</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="pricing-section">
                        <h3>Mausoleums</h3>
                        <div class="table-responsive">
                            <table class="pricing-table table">
                                <thead>
                                    <tr>
                                        <th>Dimensions</th>
                                        <th>Location</th>
                                        <th>Price</th>
                                        <th>Monthly Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td data-label="Dimensions">5x4 sqm</td>
                                        <td data-label="Location">Inside the cemetery</td>
                                        <td data-label="Price">₱500,000</td>
                                        <td data-label="Monthly Payment">₱10,000/month</td>
                                    </tr>
                                    <tr>
                                        <td data-label="Dimensions">5x4 sqm</td>
                                        <td data-label="Location">Along the road/main road</td>
                                        <td data-label="Price">₱600,000</td>
                                        <td data-label="Monthly Payment">₱12,000/month</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="pricing-section">
                        <h3>Special Packages</h3>
                        <div class="table-responsive">
                            <table class="pricing-table table">
                                <thead>
                                    <tr>
                                        <th>Option</th>
                                        <th>Price</th>
                                        <th>Monthly Payment</th>
                                        <th>Depth Options</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td data-label="Option">4-Lot Package</td>
                                        <td data-label="Price">₱300,000</td>
                                        <td data-label="Monthly Payment">₱6,000/month</td>
                                        <td data-label="Depth Options">4 feet or 6 feet</td>
                                    </tr>
                                    <tr>
                                        <td data-label="Option">Exhumation (per person)</td>
                                        <td data-label="Price">₱15,000</td>
                                        <td data-label="Monthly Payment">One-time payment</td>
                                        <td data-label="Depth Options">N/A</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="instructions card p-3 mt-4">
                        <h3>Important Notes:</h3>
                        <ul>
                            <li>All mausoleums measure 5 meters by 4 meters (5x4 sqm)</li>
                            <li>Burial lots offer depth options of either 4 feet or 6 feet</li>
                            <li>Monthly payment plans span 50 months (4 years and 2 months)</li>
                            <li>Advance payments are accepted</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <section class="lot-reservation-section card p-4 mb-4">
            <form class="lot-reservation-form row g-3" method="POST" action="/stJohnCmsApp/cms.api/clientLotReservation.php" enctype="multipart/form-data">
                <h3>Client Information</h3>
                <div class="col-md-6">
                    <label for="client_name" class="form-label">Client Name: <span class="text-danger">*</span></label>
                    <input type="text" id="client_name" name="client_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="client_address" class="form-label">Address: <span class="text-danger">*</span></label>
                    <input type="text" id="client_address" name="client_address" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="client_contact" class="form-label">Contact Number: <span class="text-danger">*</span></label>
                    <input type="text" id="client_contact" name="client_contact" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="client_id_upload" class="form-label">Upload Client's Valid ID: <span class="text-danger">*</span></label>
                    <div class="file-input-wrapper">
                        <label class="file-upload-label" for="client_id_upload">
                            <i class="fas fa-upload"></i> Choose File
                        </label>
                        <input type="file" id="client_id_upload" name="client_id_upload" accept="image/*,application/pdf" required>
                        <span class="file-name" id="client_id_upload_filename">No file chosen</span>
                        <div class="file-actions" data-target="client_id_upload">
                            <i class="fas fa-eye view-icon" title="View"></i>
                        </div>
                    </div>
                </div>

                <h3>Deceased Person's Information (Optional for advance reservation)</h3>
                <div class="col-md-6">
                    <label for="deceased_name" class="form-label">Deceased Person's Name:</label>
                    <input type="text" id="deceased_name" name="deceased_name" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="burial_date" class="form-label">Burial Date:</label>
                    <input type="date" id="burial_date" name="burial_date" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="death_certificate_upload" class="form-label">Upload Death Certificate:</label>
                    <div class="file-input-wrapper">
                        <label class="file-upload-label" for="death_certificate_upload">
                            <i class="fas fa-upload"></i> Choose File
                        </label>
                        <input type="file" id="death_certificate_upload" name="death_certificate_upload" accept="image/*,application/pdf">
                        <span class="file-name" id="death_certificate_upload_filename">No file chosen</span>
                        <div class="file-actions" data-target="death_certificate_upload">
                            <i class="fas fa-eye view-icon" title="View"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="deceased_id_upload" class="form-label">Upload Deceased's Valid ID (optional):</label>
                    <div class="file-input-wrapper">
                        <label class="file-upload-label" for="deceased_id_upload">
                            <i class="fas fa-upload"></i> Choose File
                        </label>
                        <input type="file" id="deceased_id_upload" name="deceased_id_upload" accept="image/*,application/pdf">
                        <span class="file-name" id="deceased_id_upload_filename">No file chosen</span>
                        <div class="file-actions" data-target="deceased_id_upload">
                            <i class="fas fa-eye view-icon" title="View"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="burial_permit_upload" class="form-label">Upload Burial Permit:</label>
                    <div class="file-input-wrapper">
                        <label class="file-upload-label" for="burial_permit_upload">
                            <i class="fas fa-upload"></i> Choose File
                        </label>
                        <input type="file" id="burial_permit_upload" name="burial_permit_upload" accept="image/*,application/pdf">
                        <span class="file-name" id="burial_permit_upload_filename">No file chosen</span>
                        <div class="file-actions" data-target="burial_permit_upload">
                            <i class="fas fa-eye view-icon" title="View"></i>
                        </div>
                    </div>
                </div>

                <h3>Reservation Details</h3>
                <div class="col-md-6">
                    <label for="reservation_date" class="form-label">Date of Reservation: <span class="text-danger">*</span></label>
                    <input type="date" id="reservation_date" name="reservation_date" class="form-control" required>
                </div>
                <input type="hidden" id="lotId" name="lotId" value="">
                <div class="col-md-6">
                    <label for="area" class="form-label">Area: </label>
                    <input type="text" id="area" name="area" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="block" class="form-label">Block:</label>
                    <input type="text" id="block" name="block" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="rowNumber" class="form-label">Row Number:</label>
                    <input type="text" id="rowNumber" name="rowNumber" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="lot_number" class="form-label">Lot Number:</label>
                    <input type="text" id="lot_number" name="lot_number" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="preferred_lot" class="form-label">Preferred Lot Type: <span class="text-danger">*</span></label>
                    <select id="preferred_lot" name="lotTypeId" class="form-select" required>
                        <option value="" selected disabled>-- Select Lot Type --</option>
                        <option value="1">Regular Lot (₱50,000)</option>
                        <option value="2">Regular Lot (₱60,000)</option>
                        <option value="3">Premium Lot (₱70,000)</option>
                        <option value="4">Mausoleum Inside (₱500,000)</option>
                        <option value="5">Mausoleum Roadside (₱600,000)</option>
                        <option value="6">4-Lot Package (₱300,000)</option>
                        <option value="7">Exhumation (₱15,000)</option>
                    </select>
                </div>
                <div class="col-md-6" id="depth_option">
                    <label for="burial_depth" class="form-label">Burial Depth: <span class="text-danger">*</span></label>
                    <select id="burial_depth" name="burial_depth" class="form-select">
                        <option value="4ft" selected>4 feet</option>
                        <option value="6ft">6 feet</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="additional_notes" class="form-label">Additional Notes:</label>
                    <textarea id="additional_notes" name="additional_notes" rows="3" class="form-control"></textarea>
                </div>
                <div class="col-12 text-center">
                    <button type="submit" class="submit-btn btn">Submit Reservation Request</button>
                </div>
            </form>
        </section>

        <section class="lot-history-section card p-4">
            <h3>Lot Reservation History</h3>
            <div class="table-responsive">
                <table class="lot-history-table table table-hover">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Address</th>
                            <th>Contact Number</th>
                            <th>Client Valid ID</th>
                            <th>Date of Reservation</th>
                            <th>Area</th>
                            <th>Block</th>
                            <th>Row Number</th>
                            <th>Lot Number</th>
                            <th>Preferred Lot Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- History rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div id="reservation-pagination">
                </div>
        </section>
    </main>

    <div class="modal" id="docModal" tabindex="-1" aria-labelledby="docModalLabel" aria-hidden="true" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="docModalLabel">Document Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="docFilename" style="font-weight:bold;"></p>
                    <img id="img-preview" class="img-fluid" style="display:none;" alt="Image Preview">
                    <canvas id="pdf-canvas" class="img-fluid"></canvas>
                    <!-- ✅ MODIFIED: Added d-flex and justify-content-center for centering -->
                    <div id="pdfControls" class="d-flex justify-content-center align-items-center" style="margin-top:10px; display:none;">
                        <button id="prevPage" class="btn btn-secondary btn-sm me-2">Prev</button>
                        <span id="pageInfo" class="me-2"></span>
                        <button id="nextPage" class="btn btn-secondary btn-sm">Next</button>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-left">
                    <a id="downloadLink" href="#" target="_blank" download class="btn btn-primary">
                        <i class="fas fa-download"></i> Download
                    </a>
                    <!-- Actions below are for form interaction, hidden for history view -->
                    <button type="button" class="btn btn-danger" id="deleteBtn">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                    <label for="replaceFileInput" class="btn btn-warning mb-0">
                        <i class="fas fa-sync-alt"></i> Replace
                    </label>
                    <input type="file" id="replaceFileInput" hidden>
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
    <script src="lotReservation.js" defer></script>
</body>
</html>