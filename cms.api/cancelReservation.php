<?php
// cancelReservation.php
// Dedicated endpoint to update a reservation's status to 'cancelled'.

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
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
    if ($stmt->execute([$newStatus, $reservationID])) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Reservation successfully cancelled.']);
        } else {
            // This is likely caused by the reservation not existing or already being cancelled
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Reservation not found or status already cancelled.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to execute database update.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>