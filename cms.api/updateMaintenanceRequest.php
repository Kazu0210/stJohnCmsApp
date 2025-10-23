<?php
session_start();
require_once "db_connect.php";

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

function sendResponse($success, $message = "", $data = []) {
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    sendResponse(false, "Unauthorized access. Admin login required.");
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendResponse(false, "Invalid JSON data received.");
}

$requestId = $input['requestId'] ?? null;
$status = $input['status'] ?? null;
$adminNotes = $input['adminNotes'] ?? '';

// Validate required fields
if (!$requestId || !$status) {
    sendResponse(false, "Request ID and status are required.");
}

// Validate status values
$validStatuses = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
if (!in_array($status, $validStatuses)) {
    sendResponse(false, "Invalid status value. Allowed: " . implode(', ', $validStatuses));
}

try {
    // Update the maintenance request
    $sql = "UPDATE maintenancerequest 
            SET status = ?, 
                updatedAt = CURRENT_TIMESTAMP 
            WHERE requestId = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendResponse(false, "Database error: " . $conn->error);
    }
    
    $stmt->bind_param("si", $status, $requestId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            sendResponse(true, "Maintenance request status updated successfully.", [
                'requestId' => $requestId,
                'status' => $status,
                'updatedAt' => date('Y-m-d H:i:s')
            ]);
        } else {
            sendResponse(false, "No maintenance request found with the given ID.");
        }
    } else {
        sendResponse(false, "Error updating maintenance request: " . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    sendResponse(false, "Error: " . $e->getMessage());
}

$conn->close();
?>