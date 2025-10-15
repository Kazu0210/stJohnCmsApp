<?php
session_start();
// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: /cmsApp/frontend/auth/login/login.php');
    exit();
}

// Get lotId and lotTypeId from URL
$lotId = isset($_GET['lotId']) ? $_GET['lotId'] : null;
$lotTypeId = isset($_GET['lotTypeId']) ? $_GET['lotTypeId'] : null;
$reservationInfo = [];
if ($lotId) {
    require_once __DIR__ . '/../../../../cms.api/db_connect.php';

    // Get lot info
    $stmt = $conn->prepare("SELECT lotId, block, lotNumber, rowNumber, type, lotTypeId FROM lots WHERE lotId = ?");
    $stmt->bind_param("i", $lotId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $amount_due = '';
        $finalLotTypeId = $lotTypeId ? $lotTypeId : $row['lotTypeId'];
        // Fetch price from lot_types table using lotTypeId from URL if present, else from lots
        if (!empty($finalLotTypeId)) {
            $typeStmt = $conn->prepare("SELECT price FROM lot_types WHERE lotTypeId = ?");
            $typeStmt->bind_param("i", $finalLotTypeId);
            $typeStmt->execute();
            $typeResult = $typeStmt->get_result();
            if ($typeResult && $typeResult->num_rows > 0) {
                $typeRow = $typeResult->fetch_assoc();
                $amount_due = $typeRow['price'];
            }
            $typeStmt->close();
        }
        $reservationInfo = [
            'lotId' => $row['lotId'],
            'block' => $row['block'],
            'lotNumber' => $row['lotNumber'],
            'rowNumber' => $row['rowNumber'],
            'type' => $row['type'],
            'amount_due' => $amount_due,
            'reservation_ref' => 'RES-' . date('Y') . '-' . str_pad($row['lotId'], 5, '0', STR_PAD_LEFT)
        ];
    } else {
        // Handle not found
        $reservationInfo = [
            'lotId' => '',
            'block' => '',
            'lotNumber' => '',
            'rowNumber' => '',
            'type' => '',
            'amount_due' => '',
            'reservation_ref' => ''
        ];
    }
    $stmt->close();
    $conn->close();
} else {
    // Handle missing lotId
    $reservationInfo = [
        'lotId' => '',
        'block' => '',
        'lotNumber' => '',
        'rowNumber' => '',
        'type' => '',
        'amount_due' => '',
        'reservation_ref' => ''
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Transactions</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="payment.css">
        <style>
            body, h1, h2, h3, h4, h5, h6 {
                font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            }
        </style>
</head>
<body>
    <?php include_once __DIR__ . '/../clientNavbar.php'; ?>
    <header class="container mt-4 mb-4">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="text-center py-4 px-3 bg-white rounded-4 shadow-sm border">
                    <h1 class="fw-bold fs-3 mb-2" style="letter-spacing:0.5px;">Billing & Transactions</h1>
                    <p class="text-secondary mb-0" style="font-size:1.05rem;">Manage your payments, view history, and stay up to date.</p>
                </div>
            </div>
        </div>
    </header>
    <main class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="row g-4">
                    <!-- Payment Form Column -->
                    <div class="col-md-6">
                        <div class="bg-white rounded-4 shadow-sm border p-4 h-100">
                            <h2 class="fs-4 fw-semibold mb-3">Pay for Your Reservation</h2>
                            <!-- Reservation Details Placeholder -->
                            <div class="mb-4" id="reservation-details">
                                <p class="mb-1"><strong>Block:</strong> <span id="block-info"><?php echo htmlspecialchars($reservationInfo['block']); ?></span></p>
                                <p class="mb-1"><strong>Row Number:</strong> <span id="row-info"><?php echo htmlspecialchars($reservationInfo['rowNumber']); ?></span></p>
                                <p class="mb-1"><strong>Lot Number:</strong> <span id="lot-number-info"><?php echo htmlspecialchars($reservationInfo['lotNumber']); ?></span></p>
                                <p class="mb-1"><strong>Type:</strong> <span id="type-info"><?php echo htmlspecialchars($reservationInfo['type']); ?></span></p>
                            </div>
                            <form id="paymentForm" action="../../../../cms.api/save_payment.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="paymentAmount" class="form-label">Amount</label>
                                    <input type="text" class="form-control" id="paymentAmount" name="amount" required readonly value="<?php echo isset($reservationInfo['amount_due']) ? number_format((float)$reservationInfo['amount_due'], 2) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="paymentMethodId" class="form-label">Payment Method</label>
                                    <select class="form-select" id="paymentMethod" name="paymentMethodId" required>
                                        <option value="">Select method</option>
                                        <option value="1">GCash</option>
                                        <option value="2">Bank Transfer</option>
                                        <option value="3">Cash</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="month" class="form-label">Month</label>
                                    <input type="month" class="form-control" id="month" name="month" value="<?php echo date('Y-m'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="paymentType" class="form-label">Payment Type</label>
                                    <select class="form-select" id="paymentType" name="paymentType" required>
                                        <option value="">Select payment type</option>
                                        <option value="exact">Exact Installment Amount</option>
                                        <option value="advance">Advance Payment</option>
                                        <option value="deferred">Deferred Amount</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="referenceField" style="display:none;">
                                    <label for="reference" class="form-label">Reference</label>
                                    <input type="text" class="form-control" id="reference" name="reference" placeholder="Enter payment reference or transaction number">
                                </div>
                                <div class="mb-3">
                                    <label for="proofFile" class="form-label">Upload Payment Receipt</label>
                                    <input class="form-control" type="file" id="proofFile" name="proofFile" accept="image/*,application/pdf" required>
                                </div>
                                <input type="hidden" name="reservationId" id="reservationIdInput" value="<?php echo htmlspecialchars($lotId); ?>">
                                <button type="submit" class="btn btn-success w-100">Submit Payment</button>
                            </form>
                        </div>
                    </div>
                    <!-- QR Code Column -->
                    <div class="col-md-6">
                        <div class="bg-white rounded-4 shadow-sm border p-4 h-100 d-flex flex-column align-items-center justify-content-center">
                            <h2 class="fs-5 fw-semibold mb-3 text-center">Scan to Pay</h2>
                            <div class="row w-100 g-3">
                                <div class="col-12 d-flex flex-column align-items-center" id="gcashQrContainer" style="display:none;">
                                    <div class="mb-2 fw-semibold" style="display:none;">GCash</div>
                                    <img src="gcashqr.jpg" alt="GCash QR Code" class="img-fluid payment-qr-code mb-2" style="max-width:180px; max-height:180px; display:none;">
                                </div>
                                <div class="col-12 d-flex flex-column align-items-center" id="bankQrContainer" style="display:none;">
                                    <div class="mb-2 fw-semibold" style="display:none;">Bank Transfer</div>
                                    <img src="bankqr.jpg" alt="Bank Transfer QR Code" class="img-fluid payment-qr-code mb-2" style="max-width:180px; max-height:180px; display:none;">
                                </div>
                            </div>
                            <div class="mt-3 text-center text-muted" style="font-size:0.95rem;">Upload your payment receipt after scanning the QR code.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</main>
<script src="payment.js"></script>
<script>
// Show reference field only for bank or gcash
document.addEventListener('DOMContentLoaded', function() {
    var paymentMethod = document.getElementById('paymentMethod');
    var referenceField = document.getElementById('referenceField');
    var referenceInput = document.getElementById('reference');
    function toggleReferenceField() {
        if (paymentMethod.value === '1' || paymentMethod.value === '2') { // 1: GCash, 2: Bank
            referenceField.style.display = '';
            referenceInput.required = true;
        } else {
            referenceField.style.display = 'none';
            referenceInput.required = false;
            referenceInput.value = '';
        }
    }
    paymentMethod.addEventListener('change', toggleReferenceField);
    toggleReferenceField();
});
</script>
<!-- Bootstrap JS Bundle (for modal and Toast support) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
