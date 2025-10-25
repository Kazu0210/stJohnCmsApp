<?php
// deleteReservation.php
// Handles permanent deletion of a reservation record.

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require 'db_connect.php'; 

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$reservationID = (int)($data['reservationID'] ?? 0);

if ($reservationID <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing reservation ID.']);
    exit;
}

try {
    // --- 1. Get clientValidId to delete the associated file ---
    $fileSql = "SELECT clientValidId FROM reservations WHERE reservationID = ?";
    $fileStmt = $conn->prepare($fileSql);
    $fileStmt->bind_param("i", $reservationID);
    $fileStmt->execute();
    $fileResult = $fileStmt->get_result();
    $reservation = $fileResult->fetch_assoc();
    $fileStmt->close();

    $fileName = $reservation['clientValidId'] ?? null;
    $uploadDir = 'reservations/uploads/client_ids/'; // Must match path in uploadClientId.php

    // --- 2. Delete the database record ---
    $deleteSql = "DELETE FROM reservations WHERE reservationId = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $reservationID);
    $deleteStmt->execute();
    
    $affectedRows = $deleteStmt->affected_rows;
    $deleteStmt->close();

    if ($affectedRows > 0) {
        // --- 3. Delete the physical file (after successful DB deletion) ---
        if ($fileName && file_exists($uploadDir . $fileName)) {
            unlink($uploadDir . $fileName);
        }
        // --- 4. Audit log ---
        require_once 'audit_helper.php';
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
        $action = "Delete Reservation";
        $details = "Reservation ID {$reservationID} permanently deleted by User ID {$user_id}";
        try {
            log_audit($user_id, $action, $details);
        } catch (Exception $e) {
            error_log('Audit log failed: ' . $e->getMessage());
        }
        echo json_encode(['status' => 'success', 'message' => "Reservation ID $reservationID permanently deleted."]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Reservation not found or already deleted.']);
    }
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>