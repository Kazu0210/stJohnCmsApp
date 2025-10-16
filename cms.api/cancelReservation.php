<?php
// cancelReservation.php
// Dedicated endpoint to update a reservation's status to 'cancelled'.

header('Content-Type: application/json');
require 'db_connect.php'; // Ensure this file establishes the $conn variable

// 1. Check for POST request and get JSON data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// 2. Validate input
if (!isset($data['reservationID'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameter: reservationID.']);
    exit;
}

$reservationID = (int)$data['reservationID'];
$newStatus = 'cancelled'; // Hardcoded status for this specific endpoint

try {
    // 3. Prepare the SQL statement to update the status and updatedAt
    $sql = "
        UPDATE reservations
        SET status = ?, 
            updatedAt = NOW()
        WHERE reservationID = ?
    ";

    $stmt = $conn->prepare($sql);
    
    // 4. Execute the statement
    // Use transaction to update reservation and free the lot
    $conn->begin_transaction();

    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('si', $newStatus, $reservationID);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // find the lotId for this reservation
        $sel = $conn->prepare("SELECT lotId FROM reservations WHERE reservationID = ? LIMIT 1");
        if ($sel) {
            $sel->bind_param('i', $reservationID);
            $sel->execute();
            $res = $sel->get_result();
            $row = $res->fetch_assoc();
            $sel->close();
            if ($row && isset($row['lotId']) && is_numeric($row['lotId'])) {
                $lotId = (int)$row['lotId'];
                $upd = $conn->prepare("UPDATE lots SET status = 'Available' WHERE lotId = ?");
                if ($upd) {
                    $upd->bind_param('i', $lotId);
                    $upd->execute();
                    $upd->close();
                }
            }
        }
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Reservation successfully cancelled.']);
    } else {
        $conn->rollback();
        // This is likely caused by the reservation not existing or already being cancelled
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Reservation not found or status already cancelled.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>