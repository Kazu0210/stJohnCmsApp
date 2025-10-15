<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login/login.php');
    exit();
}
?>
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

    <main class="container py-4">
        <form id="payment-form" method="POST" action="save_payment.php" enctype="multipart/form-data" class="p-4 rounded-3">
            <h2 class="text-center mb-4">Payment Portal</h2>

            <div class="card mb-4 p-4 border-0">
                <h3 class="h5 mb-3 text-center">Lot Payment Summary</h3>
                <div class="row text-center">
                    <div class="col-md-3 mb-3"> 
    <h5 class="text-muted mb-2">Monthly Payment</h5>
    <strong id="monthly-payment" class="text-primary" style="font-size:1.8rem;">₱0.00</strong>
<p id="monthly-payment-description" class="text-muted mt-1" style="font-size:0.9rem;"></p>
</div>

                    <div class="col-md-3 mb-3">
                        <h5 class="text-muted mb-2">Total Lot Price</h5>
   <strong id="lot-price" class="text-dark" style="font-size:1.8rem;">₱0.00</strong>
                    </div>
                    <div class="col-md-3 mb-3">
                        <h5 class="text-muted mb-2">Total Paid</h5>
                        <strong id="total-paid" class="text-success" style="font-size:1.8rem;">₱0.00</strong>
                    </div>
                    <div class="col-md-3 mb-3">
                        <h5 class="text-muted mb-2">Remaining Balance</h5>
                        <strong id="remaining-balance" class="text-danger" style="font-size:1.8rem;">₱0.00</strong>
                    </div>
                </div>
            </div>
            <!-- Payment Method Section -->
            <div class="payment-section card mb-4 p-3 border-0">
                <h3 class="h5 mb-3 text-center">Choose Payment Method</h3>
                <div class="payment-methods d-flex flex-wrap justify-content-center gap-3">
                    <div class="payment-method card p-3 flex-fill text-center" onclick="selectMethod(this,'gcash')">
                        <i class="fas fa-mobile-alt fa-2x mb-2 text-primary"></i>
                        <span>GCash</span>
                    </div>
                    <div class="payment-method card p-3 flex-fill text-center" onclick="selectMethod(this,'bank')">
                        <i class="fas fa-university fa-2x mb-2 text-success"></i>
                        <span>Bank Transfer</span>
                    </div>
                    <div class="payment-method card p-3 flex-fill text-center" onclick="selectMethod(this,'cash')">
                        <i class="fas fa-money-bill-wave fa-2x mb-2 text-secondary"></i>
                        <span>Cash (Secretary)</span>
                    </div>
                </div>
            </div>
            <!-- Payment options section removed as requested -->
                <h3 class="h5 mb-3 text-center">Cash Payment</h3>
                <div class="alert alert-info text-center">
                    Please pay the secretary directly at the office. The secretary will log your payment in the system and provide you with a receipt.<br>
                    <strong>Your reservation will be confirmed after secretary input.</strong>
                </div>
            </div>
                <div id="gcash-details" class="card p-4 mb-4" style="display:none;">
                    <h3 class="h5 mb-3 text-center">GCash Details</h3>
                    <div class="qr-code-section mb-3 text-center">
                        <img src="gcashqr.jpg" alt="GCash QR" class="payment-qr-code mb-3">
                        <p class="qr-label">Scan to Pay</p>
                    </div>
                    <p class="text-center m-0"><strong>Blessed Saint John Memorial</strong></p>
                    <p class="text-center"><strong>0997 844 2421</strong></p>
                    <div class="form-group mb-3">
                        <label for="gcash-ref" class="form-label">Transaction Reference Number</label>
                        <input id="gcash-ref" name="gcash-ref" class="form-control" placeholder="Enter GCash reference number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Upload Proof of Payment</label>
                        <div class="custom-file-input-container">
                            <input type="file" id="gcash-proof" name="gcash-proof" accept="image/*,application/pdf" hidden>
                            <div class="file-input-display d-flex align-items-center gap-2 p-2 border rounded">
                                <button type="button" class="btn btn-sm file-upload-icon flex-shrink-0" data-target="gcash-proof">Choose File</button>
                                <span class="file-name flex-grow-1 text-muted" id="gcash-proof-filename">No file chosen</span>
                                <button type="button" class="btn btn-sm view-icon flex-shrink-0" data-target="gcash-proof"><i class="fas fa-eye"></i></button>
                            </div>
                            <small class="form-text text-muted mt-2 d-block">Accepted formats: PDF, JPG, PNG.</small>
                        </div>
                    </div>
                </div>

                <div id="bank-details" class="card p-4 mb-4" style="display:none;">
                    <h3 class="h5 mb-3 text-center">Bank Transfer Details</h3>
                    <div class="qr-code-section mb-3 text-center">
                        <img src="bankqr.jpg" alt="Bank QR" class="payment-qr-code mb-3">
                        <p class="qr-label">Scan to Pay</p>
                    </div>
                    <p class="text-center m-0"><strong>Bank:</strong> BDO</p>
                    <p class="text-center m-0"><strong>Account Name:</strong> Blessed Saint John Memorial</p>
                    <p class="text-center"><strong>Account Number:</strong> 9876 5432 1098</p>
                    <div class="form-group mb-3">
                        <label for="bank-ref" class="form-label">Transaction Reference Number</label>
                        <input id="bank-ref" name="bank-ref" class="form-control" placeholder="Enter bank reference number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Upload Proof of Payment</label>
                        <div class="custom-file-input-container">
                            <input type="file" id="bank-proof" name="bank-proof" accept="image/*,application/pdf" hidden>
                            <div class="file-input-display d-flex align-items-center gap-2 p-2 border rounded">
                                <button type="button" class="btn btn-sm file-upload-icon flex-shrink-0" data-target="bank-proof">Choose File</button>
                                <span class="file-name flex-grow-1 text-muted" id="bank-proof-filename">No file chosen</span>
                                <button type="button" class="btn btn-sm view-icon flex-shrink-0" data-target="bank-proof"><i class="fas fa-eye"></i></button>
                            </div>
                            <small class="form-text text-muted mt-2 d-block">Accepted formats: PDF, JPG, PNG.</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="payment-section card p-4 mb-4">
                <h3 class="h5 mb-3 text-center">Payment Schedule</h3>
                <div class="form-group mb-3">
                    <label for="payment-type" class="form-label">Payment Type</label>
                    <select id="payment-type" name="payment-type" class="form-select">
                        <option value="exact">Exact Monthly Payment</option>
                        <option value="advance">Advance Payment</option>
                        <option value="unable">Unable to Pay</option>
                    </select>
                </div>
                <div id="advance-payment-options" style="display:none;">
                    <div id="months-to-pay-group" class="form-group mb-3">
                        <label for="months-to-pay" class="form-label">Months to Pay</label>
                        <input id="months-to-pay" name="months-to-pay" type="number" min="1" value="1" class="form-control">
                    </div>
                    <div id="custom-amount-group" class="form-group mb-3">
                        <label for="custom-amount" class="form-label">Custom Amount</label>
                        <input id="custom-amount" name="custom-amount" type="number" step="0.01" class="form-control" placeholder="Optional">
                    </div>
                </div>
                <div class="form-group">
                    <label for="calculated-amount" class="form-label">Calculated Amount</label>
                    <input id="calculated-amount" name="calculated-amount" type="text" class="form-control" readonly value="₱0.00">
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary">Submit Payment</button>
            </div>
        </form>

        <div class="payment-section card p-4 my-4 border-0">
            <h3 class="h5 mb-3 text-center">How to Pay — Quick Instructions</h3>
            <ol class="text-start" style="padding-left: 1.5rem;">
                <li>Select your <strong>Lot</strong> from the dropdown above.</li>
                <li>Choose your <strong>Payment Method</strong> (GCash or Bank Transfer).</li>
                <li>If paying online, complete the transfer and copy the transaction reference number.</li>
                <li>Click <strong>Choose File</strong> to upload proof of payment (PDF or image).</li>
                <li>Optionally, click the **Eye Icon** (<i class="fas fa-eye"></i>) to preview the uploaded file.</li>
                <li>Select the payment type and click <strong>Submit Payment</strong>.</li>
            </ol>
        </div>

        <div class="payment-section card p-4 my-4 border-0">
            <h3 class="h5 mb-3 text-center">Payment History</h3>
            <div class="table-responsive">
                <table class="payment-history-table table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Date Paid</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Document</th>
                        </tr>
                    </thead>
                    <tbody id="paymentHistoryBody">
                    </tbody>
                </table>
            </div>
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
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Payment method selection logic
    function selectMethod(el, method) {
        document.querySelectorAll('.payment-method').forEach(e => e.classList.remove('border-primary', 'border-success', 'border-secondary', 'selected'));
        el.classList.add('selected');
        if (method === 'gcash') {
            document.getElementById('online-payment-fields').style.display = '';
            document.getElementById('gcash-details').style.display = '';
            document.getElementById('bank-details').style.display = 'none';
            document.getElementById('cash-payment-fields').style.display = 'none';
        } else if (method === 'bank') {
            document.getElementById('online-payment-fields').style.display = '';
            document.getElementById('gcash-details').style.display = 'none';
            document.getElementById('bank-details').style.display = '';
            document.getElementById('cash-payment-fields').style.display = 'none';
        } else if (method === 'cash') {
            document.getElementById('online-payment-fields').style.display = 'none';
            document.getElementById('gcash-details').style.display = 'none';
            document.getElementById('bank-details').style.display = 'none';
            document.getElementById('cash-payment-fields').style.display = '';
        }
        // Store selected method in a hidden input for form submission
        let methodInput = document.getElementById('payment-method');
        if (!methodInput) {
            methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.id = 'payment-method';
            methodInput.name = 'payment-method';
            document.getElementById('payment-form').appendChild(methodInput);
        }
        methodInput.value = method;
    }
    </script>
    <script src="payment.js"></script>
</body>
</html>
