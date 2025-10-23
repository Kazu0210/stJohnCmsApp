<?php
// confirmReservationAndLot.php - Handles conditional status update for reservation and lot synchronization.
session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require 'db_connect.php'; // Ensure this path is correct

// 1. Authentication Check (Admin/Staff performing the action)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: User not logged in.']);
    exit;
}
$sessionUserId = (int)$_SESSION['user_id']; // This is the ADMIN/STAFF ID, used only for auditing/logging if needed.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!isset($data['reservationID'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameter: reservationID.']);
    exit;
}

$reservationID = (int)$data['reservationID'];

try {
    // Start Transaction for atomic updates
    $conn->begin_transaction();

    // STEP 1: Get Reservation details (lotId, deceasedName, and ORIGINAL userId)
    $sql_select = "SELECT lotId, deceasedName, userId AS clientUserId 
                   FROM reservations 
                   WHERE reservationId = ?";
    
    $stmt_select = $conn->prepare($sql_select);
    if (!$stmt_select) throw new Exception("Prepare failed on SELECT: " . $conn->error);
    
    $stmt_select->bind_param("i", $reservationID);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $reservation = $result->fetch_assoc();
    $stmt_select->close();

    if (!$reservation) {
        throw new Exception("Reservation not found.");
    }

    $lotId = (int)$reservation['lotId'];
    $clientUserId = (int)$reservation['clientUserId']; // <-- Retrieved the original client ID
    
    if ($lotId === 0) {
        // This is a safety check. Every reservation should link to a lot.
        throw new Exception("Lot ID is missing for this reservation. Cannot synchronize lot status.");
    }
    
    // STEP 2: Determine Final Status
    $finalStatus = !empty(trim($reservation['deceasedName'])) ? 'Occupied' : 'Reserved';
    
    // STEP 3: Update the Reservations table status
    // NOTE: We do NOT update userId here, keeping the client's original ID.
    $sql_update_reservation = "
        UPDATE reservations 
        SET status = ?, updatedAt = NOW() 
        WHERE reservationId = ? AND status = 'Pending'
    ";
    
    $stmt_update_reservation = $conn->prepare($sql_update_reservation);
    if (!$stmt_update_reservation) throw new Exception("Prepare failed on UPDATE reservations: " . $conn->error);
    
    $stmt_update_reservation->bind_param("si", $finalStatus, $reservationID);
    $stmt_update_reservation->execute();
    $stmt_update_reservation->close();

    
    // STEP 4: Update the Lots table (Synchronization)
    
    // Determine the datePending value for binding (NULL if status is Reserved/Occupied)
    $datePendingValue = ($finalStatus === 'Pending') ? date('Y-m-d H:i:s') : NULL;
    
    // Use the ORIGINAL client's userId for lot ownership
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
    
    // Bind parameters: "sisi" (s=status, i=userId, s=datePending, i=lotId)
    $stmt_update_lot->bind_param(
        "sisi",
        $finalStatus,       // 1. String
        $clientUserId,      // 2. Integer (Use the CLIENT'S ID)
        $datePendingValue,  // 3. String/NULL
        $lotId              // 4. Integer
    );

    $stmt_update_lot->execute();
    $stmt_update_lot->close();

    // Commit Transaction
    $conn->commit();

    // STEP 5: Respond to the JavaScript
    echo json_encode([
        "status" => "success",
        "message" => "Reservation and Lot confirmed. Ownership (User ID {$clientUserId}) maintained. Final status: **{$finalStatus}**.",
        "finalStatus" => $finalStatus
    ]);

} catch (Exception $e) {
    // Rollback Transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    http_response_code(500);
    // Send a JSON error response
    echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
    
} finally {
    // Ensure connection is closed even if an exception occurs
    if (isset($conn)) {
        $conn->close();
    }
}
?>