<?php
// Start session to access $_SESSION['user_id']
session_start();

// NOTE: Ensure your db_connect.php file is in the same directory as this file
require_once "db_connect.php"; 

// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// 1. Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // FIX: Clear status to prevent front-end from crashing if credentials fail
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in or session expired.",
        "data" => []
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

// 2. SQL Query to fetch reserved lots and their types
$sql = "SELECT 
            r.reservationId, 
            r.clientName, 
            r.area, 
            r.block, 
            r.rowNumber,
            r.lotNumber, 
            r.lotTypeId,
            lt.typeName AS lot_type_name
            -- Removed price/payment columns as they are unused in the dropdown/table display logic
        FROM reservations r
        LEFT JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
        WHERE r.userId = ?
        ORDER BY r.area, r.block, r.lotNumber"; // Simplified sort order

$stmt = $conn->prepare($sql);

if (!$stmt) {
    // FIX: Return a clear SQL preparation error
    echo json_encode([
        "status" => "error",
        "message" => "SQL prepare failed: " . $conn->error,
        "data" => []
    ]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$lots = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Ensure data is ready for the front-end
        $lots[] = $row;
    }
}

$stmt->close();
$conn->close();

if (count($lots) > 0) {
    // Success response with data
    echo json_encode([
        "status" => "success",
        "message" => "Lots loaded successfully.",
        "data" => $lots
    ], JSON_PRETTY_PRINT);
} else {
    // Success response, but no data found for the user
    echo json_encode([
        "status" => "success", // IMPORTANT: Treat as success so front-end shows "No lots reserved"
        "message" => "No reserved lots found for this user.",
        "data" => []
    ], JSON_PRETTY_PRINT);
}

?>