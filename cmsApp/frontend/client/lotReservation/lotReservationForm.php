<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    // User is not logged in, redirect to login page
    header("Location: ../../auth/login/login.php");
    exit();
}

// Optional: You can also check user role if needed
// if ($_SESSION['role'] !== 'client') {
//     header("Location: ../../auth/login/login.php");
//     exit();
// }

// Get package details from URL parameters if available
$selectedPackage = isset($_GET['package']) ? htmlspecialchars($_GET['package']) : '';
$selectedPrice = isset($_GET['price']) ? htmlspecialchars($_GET['price']) : '';
$selectedMonthly = isset($_GET['monthly']) ? htmlspecialchars($_GET['monthly']) : '';
$selectedDetails = isset($_GET['details']) ? htmlspecialchars($_GET['details']) : '';
$selectedLotType = isset($_GET['lotType']) ? htmlspecialchars($_GET['lotType']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lot Reservation Form - Blessed Saint John Memorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="lotReservation.css">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
</head>

<body class="bg-light" style="min-height:100vh;">

    <?php include dirname(__DIR__) . '/clientNavbar.php'; ?>


    <main class="main-content container-fluid px-0 px-md-2">
        <div class="row justify-content-center mt-4 mx-0">
            <div class="col-12 col-md-11 col-lg-8 px-1 px-sm-2">
                <?php if ($selectedPackage): ?>
                <div class="alert alert-info mb-4 mx-1 mx-sm-0">
                    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Selected Package</h5>
                    <p class="mb-2"><strong><?php echo $selectedPackage; ?></strong></p>
                    <?php
                    $typeLabel = '';
                    $burialKeywords = ['Regular Lot', '4-Lot Package', 'Exhumation'];
                    $mausoleumKeywords = ['Mausoleum'];
                    foreach ($burialKeywords as $kw) {
                        if (stripos($selectedPackage, $kw) !== false) {
                            $typeLabel = 'Burial Lot';
                            break;
                        }
                    }
                    foreach ($mausoleumKeywords as $kw) {
                        if (stripos($selectedPackage, $kw) !== false) {
                            $typeLabel = 'Mausoleum';
                            break;
                        }
                    }
                    ?>
                    <?php if ($typeLabel): ?>
                    <p class="mb-2">Type: <span class="badge bg-primary"><?php echo $typeLabel; ?></span></p>
                    <?php endif; ?>
                    <p class="mb-2">Price: <strong><?php echo $selectedPrice; ?></strong></p>
                    <p class="mb-2">Monthly Payment: <strong><?php echo $selectedMonthly; ?></strong></p>
                    <p class="mb-0">Details: <?php echo $selectedDetails; ?></p>
                </div>
                <?php endif; ?>
                <div class="card p-3 p-md-4 mb-4 mx-1 mx-sm-0">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">Lot Reservation Form</h2>
                        <a href="lotReservation.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Packages
                        </a>
                    </div>
                    <form id="reservationForm" class="row g-3">
                        <h5 class="mb-3">Client Information</h5>
                        <!-- ...existing form fields... -->
                        <!-- You can add helper text or icons here for clarity -->
                    </form>
                </div>
                <div class="card p-2 p-md-3 mx-1 mx-sm-0">
                    <h4 class="mb-3">Available Lots</h4>
                    <div class="mb-2">
                        <span class="fw-semibold text-primary"><i class="fas fa-mouse-pointer me-1"></i>Tap a row to select a lot and auto-fill the reservation form above.</span>
                    </div>
                    <div class="table-responsive mb-2" style="overflow-x:auto;">
                        <div id="availableLotsContainer">
                            <div class="text-muted">Loading available lots...</div>
                        </div>
                    </div>
                    <div id="selectedLotMsg" class="alert alert-success py-2 px-3 d-none" style="font-size:0.95rem;"></div>
                </div>
                <!-- Payment Option Modal moved outside card for valid HTML structure -->
            </div>
        </div>
        <!-- Payment Option Modal -->
        <div class="modal fade" id="paymentOptionModal" tabindex="-1" aria-labelledby="paymentOptionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 pb-0">
                        <div class="w-100">
                            <h5 class="modal-title mb-0 fw-bold" id="paymentOptionModalLabel">Select Payment Method</h5>
                            <small class="text-muted">How would you like to pay for your reservation?</small>
                        </div>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <div id="selectedLotDetails" class="alert alert-info py-2 px-3 mb-3 d-none"></div>
                        <div class="d-grid gap-2 gap-md-3 my-3">
                            <button type="button" class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 py-3 fs-6 fs-md-5" id="payGcash">
                                <i class="fas fa-mobile-alt fa-lg"></i>
                                <span class="fw-semibold">Gcash</span>
                            </button>
                            <button type="button" class="btn btn-outline-success d-flex align-items-center justify-content-center gap-2 py-3 fs-6 fs-md-5" id="payBank">
                                <i class="fas fa-university fa-lg"></i>
                                <span class="fw-semibold">Bank Transfer</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2 py-3 fs-6 fs-md-5" id="payCash">
                                <i class="fas fa-money-bill-wave fa-lg"></i>
                                <span class="fw-semibold">Cash</span>
                            </button>
                        </div>
                        <form id="paymentProofForm" class="d-none mt-3">
                            <div class="text-center mb-3" id="qrCodeContainer"></div>
                            <div class="mb-3">
                                <label for="referenceNumber" class="form-label">Reference Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="referenceNumber" name="referenceNumber" required>
                            </div>
                            <div class="mb-3">
                                <label for="paymentProof" class="form-label">Upload Proof of Payment <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="paymentProof" name="paymentProof" accept="image/*,application/pdf" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Submit Payment Information</button>
                        </form>
                        <div class="text-center text-muted mt-2" style="font-size:0.97rem;">
                            <hr class="my-3">
                            <span><i class="fas fa-info-circle me-1"></i>You can change your payment method later if needed.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Determine the type from the selected package (PHP to JS)
        let selectedType = '';
        <?php
        $typeLabel = '';
        $burialKeywords = ['Regular Lot', '4-Lot Package', 'Exhumation'];
        $mausoleumKeywords = ['Mausoleum'];
        foreach ($burialKeywords as $kw) {
            if (stripos($selectedPackage, $kw) !== false) {
                $typeLabel = 'BurialLot';
                break;
            }
        }
        foreach ($mausoleumKeywords as $kw) {
            if (stripos($selectedPackage, $kw) !== false) {
                $typeLabel = 'Mausoleum';
                break;
            }
        }
        ?>
        selectedType = '<?php echo $typeLabel; ?>';
        fetch('/stJohnCmsApp/cms.api/get_lots.php?limit=1000')
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error('Failed to fetch lots');
                const lots = data.data.filter(lot => lot.type === selectedType);
                const container = document.getElementById('availableLotsContainer');
                if (lots.length === 0) {
                    container.innerHTML = `<div class="text-danger">No available lots found for type: <b>${selectedType || 'N/A'}</b>.</div>`;
                    return;
                }
                let html = '<table id="availableLotsTable" class="table table-bordered table-sm lot-table-enhanced"><thead><tr><th>Block</th><th>Area</th><th>Row</th><th>Lot No.</th><th>Type</th><th>Status</th></tr></thead><tbody>';
                lots.forEach((lot, idx) => {
                    html += `<tr data-idx="${idx}"><td>${lot.block}</td><td>${lot.area}</td><td>${lot.rowNumber}</td><td>${lot.lotNumber}</td><td>${lot.type}</td><td>${lot.status}</td></tr>`;
                });
                html += '</tbody></table>';
                container.innerHTML = html;

                // Add row interactivity
                const table = document.getElementById('availableLotsTable');
                let selectedRow = null;
                table.querySelectorAll('tbody tr').forEach((row, idx) => {
                    row.style.cursor = 'pointer';
                    row.addEventListener('mouseenter', function() {
                        if (row !== selectedRow) row.classList.add('table-active');
                    });
                    row.addEventListener('mouseleave', function() {
                        if (row !== selectedRow) row.classList.remove('table-active');
                    });
                    row.addEventListener('click', function() {
                        if (selectedRow) selectedRow.classList.remove('table-success');
                        if (selectedRow && selectedRow !== row) selectedRow.classList.remove('table-active');
                        row.classList.add('table-success');
                        selectedRow = row;
                        // Show message
                        const msg = document.getElementById('selectedLotMsg');
                        msg.textContent = `Selected Lot: Block ${lots[idx].block}, Area ${lots[idx].area}, Row ${lots[idx].rowNumber}, Lot No. ${lots[idx].lotNumber}`;
                        msg.classList.remove('d-none');
                        // Show lot details in modal
                        const lotDetails = document.getElementById('selectedLotDetails');
                        lotDetails.innerHTML = `<strong>Selected Lot Details:</strong><br>Block: <b>${lots[idx].block}</b> &nbsp; | &nbsp; Area: <b>${lots[idx].area}</b> &nbsp; | &nbsp; Row: <b>${lots[idx].rowNumber}</b> &nbsp; | &nbsp; Lot No.: <b>${lots[idx].lotNumber}</b> &nbsp; | &nbsp; Type: <b>${lots[idx].type}</b> &nbsp; | &nbsp; Status: <b>${lots[idx].status}</b>`;
                        lotDetails.classList.remove('d-none');
                        // Reset payment form and QR
                        document.getElementById('paymentProofForm').classList.add('d-none');
                        document.getElementById('qrCodeContainer').innerHTML = '';
                        // Show payment modal
                        var paymentModal = new bootstrap.Modal(document.getElementById('paymentOptionModal'));
                        paymentModal.show();
                        // TODO: Auto-fill form fields here if needed
                    });
                });

                // Payment method logic
                function showPaymentForm(method) {
                    const form = document.getElementById('paymentProofForm');
                    const qr = document.getElementById('qrCodeContainer');
                    form.reset && form.reset();
                    form.classList.remove('d-none');
                    let qrImg = '';
                    if (method === 'gcash') {
                        qrImg = '<img src="/stJohnCmsApp/cmsApp/frontend/client/lotReservation/gcash-qr.png" alt="Gcash QR Code" class="img-fluid rounded mb-2" style="max-width:180px;">';
                        qr.innerHTML = `<div class='mb-2'><strong>Scan to pay with Gcash:</strong></div>` + qrImg;
                    } else if (method === 'bank') {
                        qrImg = '<img src="/stJohnCmsApp/cmsApp/frontend/client/lotReservation/bank-qr.png" alt="Bank Transfer QR Code" class="img-fluid rounded mb-2" style="max-width:180px;">';
                        qr.innerHTML = `<div class='mb-2'><strong>Scan to pay via Bank Transfer:</strong></div>` + qrImg;
                    } else {
                        form.classList.add('d-none');
                        qr.innerHTML = '';
                    }
                }
                document.getElementById('payGcash').onclick = function() { showPaymentForm('gcash'); };
                document.getElementById('payGcash').onclick = function() {
                    // Redirect to payment.php with details
                    redirectToPayment('gcash');
                };
                document.getElementById('payBank').onclick = function() {
                    redirectToPayment('bank');
                };
                document.getElementById('payCash').onclick = function() {
                    redirectToPayment('cash');
                };

                function redirectToPayment(method) {
                    // Get selected lot details
                    let lotDetails = selectedRow ? selectedRow.children : null;
                    if (!lotDetails) {
                        alert('Please select a lot first.');
                        return;
                    }
                    // Get package details from PHP variables
                    const params = new URLSearchParams({
                        package: '<?php echo rawurlencode($selectedPackage); ?>',
                        price: '<?php echo rawurlencode($selectedPrice); ?>',
                        monthly: '<?php echo rawurlencode($selectedMonthly); ?>',
                        details: '<?php echo rawurlencode($selectedDetails); ?>',
                        lotType: '<?php echo rawurlencode($selectedLotType); ?>',
                        paymentMethod: method,
                        block: lotDetails[0].textContent,
                        area: lotDetails[1].textContent,
                        row: lotDetails[2].textContent,
                        lotNumber: lotDetails[3].textContent,
                        type: lotDetails[4].textContent
                    });
                    window.location.href = '../payment/payment.php?' + params.toString();
                }

                // Optional: handle payment form submission
                document.getElementById('paymentProofForm').onsubmit = function(e) {
                    e.preventDefault();
                    // You can add AJAX here to submit the payment info
                    alert('Payment information submitted!');
                    var paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentOptionModal'));
                    paymentModal && paymentModal.hide();
                };
            })
            .catch(err => {
                document.getElementById('availableLotsContainer').innerHTML = '<div class="text-danger">Error loading lots.</div>';
            });
    });
    </script>

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
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables integration script -->
    <script src="lotReservationForm-datatables.js"></script>
</body>
</html>