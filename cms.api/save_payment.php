<?php
// FILE: /cms.api/save_payment.php
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', 'false');
session_start();

require_once "db_connect.php";

// --- Dynamically determine the application's base URL ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$appRoot = dirname($_SERVER['SCRIPT_NAME'], 2); 
$baseUrl = rtrim($protocol . $host . $appRoot, '/') . '/';


// ✅ GET Mode: Retrieve Payments (Unchanged)
if (isset($_GET['mode']) && $_GET['mode'] === 'getPayments') {
    // ... (GET logic is unchanged and omitted for brevity) ...
    $reservationId = intval($_GET['reservationId'] ?? 0);
    $userId = intval($_SESSION['user_id'] ?? 0);

    if (!$reservationId || !$userId) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing or invalid reservation or user ID."]);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            SELECT 
                paymentId, reservationId, userId, paymentMethodId, month, 
                amount, datePaid, reference, document, status
            FROM payments
            WHERE reservationId = ? AND userId = ?
            ORDER BY datePaid DESC
        ");
        $stmt->bind_param("ii", $reservationId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $payments = $result->fetch_all(MYSQLI_ASSOC);

        foreach ($payments as &$payment) { 
            if (!empty($payment['document'])) {
                $payment['document'] = $baseUrl . $payment['document'];
            }
        }
        unset($payment); 

        echo json_encode(["status" => "success", "data" => $payments]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        exit;
    }
}


// ✅ POST Mode: Save Payment, Check Burial, and Update Lot Status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = intval($_POST['reservationId'] ?? 0);
    $userId = intval($_SESSION['user_id'] ?? 0);
    $paymentMethodId = intval($_POST['paymentMethodId'] ?? 0);
    $month = $_POST['month'] ?? 'N/A';
    $amount = floatval($_POST['amount'] ?? 0);
    $reference = trim($_POST['reference'] ?? '');
    
    $paymentStatus = ($paymentMethodId == 3) ? 'Confirmed' : 'Pending'; 
    $documentPath = null;

    if ($userId === 0) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "User not logged in."]);
        exit;
    }
    
    // --- File Upload Logic (Unchanged) ---
    if (isset($_FILES['proofFile']) && $_FILES['proofFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../uploads/payments/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = $userId . "_" . time() . "_" . basename($_FILES['proofFile']['name']);
        $targetFilePath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['proofFile']['tmp_name'], $targetFilePath)) {
            $documentPath = "uploads/payments/" . $fileName;
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to move uploaded file."]);
            exit;
        }
    }

    // ✅ FULL TRANSACTION: Payment Insertion & Lot Status Synchronization
    try {
        $conn->begin_transaction();
        
        // --- PART 1: Insert the Payment Record ---
        $stmt = $conn->prepare("
            INSERT INTO payments (reservationId, userId, paymentMethodId, month, amount, reference, document, status, datePaid)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iiisdsss", $reservationId, $userId, $paymentMethodId, $month, $amount, $reference, $documentPath, $paymentStatus);

        if (!$stmt->execute()) {
             throw new Exception("Payment insert failed: " . $stmt->error);
        }
        $newPaymentId = $conn->insert_id;
        $stmt->close();
        
        // --- PART 2: Get LotId and Check for Burial Record ---
        $sql_select = "SELECT lotId, userId AS clientUserId, deceasedName 
                       FROM reservations 
                       WHERE reservationId = ?";
        $stmt_select = $conn->prepare($sql_select);
        if (!$stmt_select) throw new Exception("Prepare failed on SELECT: " . $conn->error);
        
        $stmt_select->bind_param("i", $reservationId);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        $reservation = $result->fetch_assoc();
        $stmt_select->close();

        if (!$reservation) {
            throw new Exception("Reservation not found for status update.");
        }
        
        $lotId = (int)$reservation['lotId'];
        $clientUserId = (int)$reservation['clientUserId'];

        // IMPORTANT SECURITY CHECK
        if ($clientUserId !== $userId) {
            throw new Exception("Security mismatch: Reservation does not belong to the logged-in user.");
        }
        
        // 🌟 Determine Final Status based on deceasedName (Burial Check) 🌟
        // Sets status to 'Occupied' if burial data exists, otherwise 'Reserved'.
        $finalLotStatus = !empty(trim($reservation['deceasedName'])) ? 'Occupied' : 'Reserved';
        
        // --- PART 3: Update the Reservations table status (only if currently 'Pending') ---
        // This ONLY updates if the current status is Pending, as requested.
        $sql_update_reservation = "
            UPDATE reservations 
            SET status = ?, updatedAt = NOW() 
            WHERE reservationId = ? AND status = 'Pending'
        ";
        
        $stmt_update_reservation = $conn->prepare($sql_update_reservation);
        if (!$stmt_update_reservation) throw new Exception("Prepare failed on UPDATE reservations: " . $conn->error);
        
        $stmt_update_reservation->bind_param("si", $finalLotStatus, $reservationId);
        $stmt_update_reservation->execute();
        $stmt_update_reservation->close();

        
        // --- PART 4: Update the Lots table (Synchronization) ---
        // This updates regardless of current lot status, ensuring the lot is 'Occupied' if burial data is present.
        $datePendingValue = NULL; 
        
        $sql_update_lot = "
            UPDATE lots 
            SET status = ?, 
                userId = ?, 
                datePending = ?, 
                updatedAt = NOW()
            WHERE lotId = ? AND (status = 'Pending' OR status = 'Reserved') 
        ";

        $stmt_update_lot = $conn->prepare($sql_update_lot);
        if (!$stmt_update_lot) throw new Exception("Prepare failed on UPDATE lots: " . $conn->error);
        
        $stmt_update_lot->bind_param("sisi", $finalLotStatus, $clientUserId, $datePendingValue, $lotId);
        $stmt_update_lot->execute();
        $stmt_update_lot->close();

        // --- PART 5 & 6: Prepare response data and commit (Unchanged) ---
        $selectStmt = $conn->prepare("SELECT * FROM payments WHERE paymentId = ?");
        $selectStmt->bind_param("i", $newPaymentId);
        $selectStmt->execute();
        $newPaymentData = $selectStmt->get_result()->fetch_assoc();
        $selectStmt->close();
        
        $lotStatusStmt = $conn->prepare("SELECT status FROM reservations WHERE reservationId = ?");
        $lotStatusStmt->bind_param("i", $reservationId);
        $lotStatusStmt->execute();
        $lotStatusResult = $lotStatusStmt->get_result()->fetch_assoc();
        $lotStatusStmt->close();
        
        if ($newPaymentData && !empty($newPaymentData['document'])) {
            $newPaymentData['document'] = $baseUrl . $newPaymentData['document'];
        }
        
        $conn->commit();

        echo json_encode([
            "status" => "success", 
            "message" => "Payment saved. Lot is now **{$finalLotStatus}**.",
            "data" => $newPaymentData,
            "lot_status" => $lotStatusResult['status'] ?? $finalLotStatus
        ]);

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Transaction failed: " . $e->getMessage()]);
    }
} else if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
}
?>