<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="payment.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <style>
        /* Custom CSS for maximizing space and yellow progress bar */
        body {
            padding-top: 56px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
        }
        main {
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
        /* Custom yellow-based progress bar */
        .progress-bar-yellow {
            background-color: #ffc107 !important; /* Bootstrap yellow color */
        }
    </style>
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
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="../payment/payment.php">Payment</a></li>
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

    <main class="container-fluid py-4">
        <form id="payment-form" method="POST" action="/stJohnCmsApp/cms.api/save_payment.php" enctype="multipart/form-data" class="p-4 rounded-3 bg-white">
            <h2 class="text-center mb-4">Payment Portal</h2>
            <div class="card mb-4 p-4 border-0">
                <h3 class="h5 mb-3 text-center">Lot Payment Summary</h3>
                <div class="row text-center mb-4">
                    <div class="col-lg-3 col-6 mb-3"> 
                        <h5 class="text-muted mb-2 small">Monthly Payment</h5>
                        <strong id="monthly-payment" class="text-primary h4 d-block">₱0.00</strong>
                        <p id="monthly-payment-description" class="text-muted mt-1 small"></p>
                    </div>

                    <div class="col-lg-3 col-6 mb-3">
                        <h5 class="text-muted mb-2 small">Total Lot Price</h5>
                        <strong id="lot-price" class="text-dark h4 d-block">₱0.00</strong>
                    </div>
                    <div class="col-lg-3 col-6 mb-3">
                        <h5 class="text-muted mb-2 small">Total Paid</h5>
                        <strong id="total-paid" class="text-success h4 d-block">₱0.00</strong>
                    </div>
                    <div class="col-lg-3 col-6 mb-3">
                        <h5 class="text-muted mb-2 small">Remaining Balance</h5>
                        <strong id="remaining-balance" class="text-danger h4 d-block">₱0.00</strong>
                    </div>
                </div>

                <div class="p-2 border rounded">
                    <p class="text-center fw-bold m-0 small mb-2">Payment Progress</p>
                    <div class="progress mb-2" style="height: 30px;">
                        <div id="paymentProgressBar" class="progress-bar progress-bar-striped progress-bar-yellow" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="text-center fw-bold m-0">
                        <span id="paymentProgressText">₱0.00 Paid (Total: ₱0.00)</span> 
                        
                    </p>
                </div>
                </div>

            <div class="payment-section card mb-4 p-3 border-0 bg-light">
                <h3 class="h5 mb-3 text-center">Select Lot</h3>
                <div class="form-group mb-3">
                    <label for="lot-select" class="form-label visually-hidden">Select Lot:</label>
                    <select id="lot-select" name="lot-select" class="form-select form-select-lg" required>
                        <option value="">-- Select a Lot --</option>
                    </select>
                </div>
            </div>

            <div class="payment-section card mb-4 p-3 border-0 bg-light">
                <h3 class="h5 mb-3 text-center">Choose Payment Method</h3>
                <div class="payment-methods d-flex flex-wrap justify-content-center gap-3">
                    <div class="payment-method card p-3 flex-fill text-center border" onclick="selectMethod(this,'gcash')">
                        <i class="fas fa-mobile-alt fa-2x mb-2 text-primary"></i>
                        <span class="fw-bold">GCash</span>
                    </div>
                    <div class="payment-method card p-3 flex-fill text-center border" onclick="selectMethod(this,'bank')">
                        <i class="fas fa-university fa-2x mb-2 text-success"></i>
                        <span class="fw-bold">Bank Transfer</span>
                    </div>
                </div>
            </div>

            <div id="online-payment-fields" class="payment-section">
                <div id="gcash-details" class="card p-4 mb-4" style="display:none;">
                    <h3 class="h5 mb-3 text-center">GCash Details</h3>
                    <div class="row">
                        <div class="col-md-6 text-center">
                            <div class="qr-code-section mb-3">
                                <img src="gcashqr.jpg" alt="GCash QR" class="img-fluid payment-qr-code mb-3 border rounded">
                                <p class="qr-label fw-bold">Scan to Pay</p>
                            </div>
                            <p class="m-0 small"><strong>Blessed Saint John Memorial</strong></p>
                            <p class="small"><strong>0997 844 2421</strong></p>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="gcash-ref" class="form-label">Transaction Reference Number</label>
                                <input id="gcash-ref" name="gcash-ref" class="form-control" placeholder="Enter GCash reference number">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Upload Proof of Payment</label>
                                <div class="custom-file-input-container">
                                    <input type="file" id="gcash-proof" name="gcash-proof" accept="image/*,application/pdf" hidden>
                                    <div class="file-input-display d-flex align-items-center gap-2 p-2 border rounded bg-white">
                                        <button type="button" class="btn btn-sm btn-outline-primary file-upload-icon flex-shrink-0" data-target="gcash-proof">Choose File</button>
                                        <span class="file-name flex-grow-1 text-muted small" id="gcash-proof-filename">No file chosen</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary view-icon flex-shrink-0" data-target="gcash-proof"><i class="fas fa-eye"></i></button>
                                    </div>
                                    <small class="form-text text-muted mt-2 d-block">Accepted formats: PDF, JPG, PNG.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="bank-details" class="card p-4 mb-4" style="display:none;">
                    <h3 class="h5 mb-3 text-center">Bank Transfer Details</h3>
                    <div class="row">
                        <div class="col-md-6 text-center">
                            <div class="qr-code-section mb-3">
                                <img src="bankqr.jpg" alt="Bank QR" class="img-fluid payment-qr-code mb-3 border rounded">
                                <p class="qr-label fw-bold">Scan to Pay</p>
                            </div>
                            <p class="m-0 small"><strong>Bank:</strong> BDO</p>
                            <p class="m-0 small"><strong>Account Name:</strong> Blessed Saint John Memorial</p>
                            <p class="small"><strong>Account Number:</strong> 9876 5432 1098</p>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="bank-ref" class="form-label">Transaction Reference Number</label>
                                <input id="bank-ref" name="bank-ref" class="form-control" placeholder="Enter bank reference number">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Upload Proof of Payment</label>
                                <div class="custom-file-input-container">
                                    <input type="file" id="bank-proof" name="bank-proof" accept="image/*,application/pdf" hidden>
                                    <div class="file-input-display d-flex align-items-center gap-2 p-2 border rounded bg-white">
                                        <button type="button" class="btn btn-sm btn-outline-primary file-upload-icon flex-shrink-0" data-target="bank-proof">Choose File</button>
                                        <span class="file-name flex-grow-1 text-muted small" id="bank-proof-filename">No file chosen</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary view-icon flex-shrink-0" data-target="bank-proof"><i class="fas fa-eye"></i></button>
                                    </div>
                                    <small class="form-text text-muted mt-2 d-block">Accepted formats: PDF, JPG, PNG.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="payment-section card p-4 mb-4 bg-light">
                <h3 class="h5 mb-3 text-center">Payment Details</h3>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="payment-type" class="form-label">Payment Type</label>
                        <select id="payment-type" name="payment-type" class="form-select form-select-lg">
                            <option value="exact">Exact Monthly Payment</option>
                            <option value="advance">Advance Payment</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                         <label for="calculated-amount" class="form-label">Calculated Amount to Pay</label>
                        <input id="calculated-amount" name="calculated-amount" type="text" class="form-control form-control-lg" readonly value="₱0.00">
                    </div>
                </div>

                <div id="advance-payment-options" style="display:none;">
                    <div id="months-to-pay-group" class="form-group mb-3">
                        <label for="months-to-pay" class="form-label">Months to Pay</label>
                        <input id="months-to-pay" name="months-to-pay" type="number" min="1" value="1" class="form-control form-control-lg">
                        <small id="months-to-pay-limit-text" class="form-text text-danger"></small>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg w-75">Submit Payment</button>
            </div>
        </form>

        <div class="payment-section card p-4 my-4 border-0 bg-white">
            <h3 class="h5 mb-3 text-center">How to Pay — Quick Instructions</h3>
            <ol class="text-start small" style="padding-left: 1.5rem;">
                <li>Select your **Lot** from the dropdown above.</li>
                <li>Choose your **Payment Method** (GCash or Bank Transfer).</li>
                <li>If paying online, complete the transfer and copy the transaction reference number.</li>
                <li>Click **Choose File** to upload proof of payment (PDF or image).</li>
                <li>Optionally, click the **Eye Icon** (<i class="fas fa-eye"></i>) to preview the uploaded file.</li>
                <li>Select the payment type and click **Submit Payment**.</li>
            </ol>
        </div>

        <div class="payment-section card p-4 my-4 border-0 bg-white">
            <h3 class="h5 mb-3 text-center">Payment History</h3>
            <div class="table-responsive">
                <table class="payment-history-table table table-striped table-hover small">
                    <thead>
                        <tr>
                            <th>Date Paid</th> 
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Document</th>
                        </tr>
                    </thead>
                    <tbody id="paymentHistoryBody">
                    </tbody>
                </table>
            </div>
            <nav>
                <ul id="paymentHistoryPagination" class="pagination justify-content-center">
                </ul>
            </nav>
        </div>
    </main>

    <div class="modal fade" id="docModal" tabindex="-1" aria-labelledby="docModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="docModalLabel">Document Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="docFilename" class="fw-bold"></p>
                    <img id="img-preview" class="img-fluid" style="display:none;" alt="Image Preview">
                    <canvas id="pdf-canvas" style="display:none;"></canvas>
                    <div id="pdfControls" class="d-flex justify-content-center align-items-center gap-2 mt-2" style="display:none;">
                        <button id="prevPage" class="btn btn-secondary">Prev</button>
                        <span id="pageInfo"></span>
                        <button id="nextPage" class="btn btn-secondary">Next</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <label for="replaceFileInput" class="btn btn-warning mb-0">
                        <i class="fas fa-sync-alt"></i> Replace
                    </label>
                    <input type="file" id="replaceFileInput" hidden accept="image/*,application/pdf">
                    <button type="button" class="btn btn-danger" id="deleteBtn">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

   <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 2000;">
 <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
   <div class="toast-header">
     <img src="9623b9f2-40b2-4f6c-b8a5-e20ffdad3f86.jpg" class="rounded me-2" alt="St John CMS" style="width:24px;height:24px;">
     <strong class="me-auto">St John CMS</strong>
     <small>Just now</small>
     <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
   </div>
   <div class="toast-body">
     You have 24 hours to pay this reservation!
   </div>
 </div>
</div>


    <footer class="footer text-center py-3">
        <div class="container d-flex flex-column flex-md-row justify-content-center align-items-center">
            <p class="m-0 small">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="payment.js"></script>
</body>
</html>