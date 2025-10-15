<?php
session_start();
// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: /cmsApp/frontend/auth/login/login.php');
    exit();
}

// Get lotId from URL
$lotId = isset($_GET['lotId']) ? $_GET['lotId'] : null;
$reservationInfo = [];
if ($lotId) {
    require_once __DIR__ . '/../../../../cms.api/db_connect.php'; // adjust path as needed

    $stmt = $conn->prepare("SELECT lotId, block, lotNumber, rowNumber, type FROM lots WHERE lotId = ?");
    $stmt->bind_param("i", $lotId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $reservationInfo = [
            'lotId' => $row['lotId'],
            'block' => $row['block'],
            'lotNumber' => $row['lotNumber'],
            'rowNumber' => $row['rowNumber'],
            'type' => $row['type'],
            // You may need to fetch amount_due and reservation_ref from another table if needed
            'amount_due' => '25000.00', // Placeholder, replace with actual value if available
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
</header>

    <main class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="bg-white rounded-4 shadow-sm border p-4">
                    <h2 class="fs-4 fw-semibold mb-3">Pay for Your Reservation</h2>
                    <!-- Reservation Details Placeholder -->
                    <div class="mb-4" id="reservation-details">
                        <p class="mb-1"><strong>Lot Reserved:</strong> <span id="lot-info">Block 5, Lot 12</span></p>
                    </div>
                    <form id="paymentForm" action="../../../cms.api/save_payment.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="paymentAmount" class="form-label">Amount</label>
                            <input type="number" class="form-control" id="paymentAmount" name="amount" min="0" step="0.01" required readonly value="<?php echo isset($reservationInfo['amount_due']) ? htmlspecialchars($reservationInfo['amount_due']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="paymentMethod" class="form-label">Payment Method</label>
                            <select class="form-select" id="paymentMethod" name="method" required>
                                <option value="">Select method</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="gcash">GCash</option>
                                <option value="cash">Cash</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="receipt" class="form-label">Upload Payment Receipt</label>
                            <input class="form-control" type="file" id="receipt" name="receipt" accept="image/*,application/pdf" required>
                        </div>
                        <input type="hidden" name="reservation_id" id="reservationIdInput" value="<?php echo htmlspecialchars($lotId); ?>">
                        <button type="submit" class="btn btn-success w-100">Submit Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
