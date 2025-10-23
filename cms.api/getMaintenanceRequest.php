<?php
// FILE: /cms.api/getMaintenanceRequest.php
require_once "db_connect.php";
session_start();
// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$userId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'Client';

// ✅ Admin sees all, Client sees only their own
if ($role === 'Admin') {
    $sql = "SELECT 
                m.requestId,
                m.userId,
                u.fullName AS clientName,
                m.serviceType,
                m.status,
                m.requestedDate,
                m.notes,
                r.area,
                r.block,
                r.rowNumber,
                r.lotNumber,
                lt.typeName AS lot_type_name
            FROM maintenancerequest m
            JOIN reservations r ON m.reservationId = r.reservationId
            JOIN users u ON m.userId = u.userId
            LEFT JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
            ORDER BY m.requestedDate DESC";
} else {
    $sql = "SELECT 
                m.requestId,
                m.serviceType,
                m.status,
                m.requestedDate,
                m.notes,
                r.area,
                r.block,
                r.rowNumber,
                r.lotNumber,
                lt.typeName AS lot_type_name
            FROM maintenancerequest m
            JOIN reservations r ON m.reservationId = r.reservationId
            LEFT JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
            WHERE m.userId = ?
            ORDER BY m.requestedDate DESC";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "SQL prepare failed: " . $conn->error, "data" => []]);
    exit;
}

if ($role !== 'Admin') {
    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $data
], JSON_PRETTY_PRINT);

$stmt->close();
$conn->close();
?>
