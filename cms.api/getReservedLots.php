<?php
ini_set('display_errors', 0); // hide warnings from breaking JSON
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ob_clean();

header('Content-Type: application/json');
session_start();
require_once "db_connect.php";

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in",
        "data" => []
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

// ✅ Fetch reserved lots with joined lot type info
$sql = "SELECT 
            r.reservationId, 
            r.clientName, 
            r.area, 
            r.block, 
            r.rowNumber,
            r.lotNumber, 
            r.lotTypeId,
            lt.typeName AS lot_type_name,
            COALESCE(lt.price, 0) AS price,
            COALESCE(lt.monthlyPayment, 0) AS monthlyPayment,
            COALESCE(lt.description, '') AS description
        FROM reservations r
        LEFT JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
        WHERE r.userId = ?
        ORDER BY r.clientName, lt.typeName, r.area, r.block, r.rowNumber, r.lotNumber";

$stmt = $conn->prepare($sql);

if (!$stmt) {
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
while ($row = $result->fetch_assoc()) {
    $row['price'] = (float) $row['price'];
    $row['monthlyPayment'] = (float) $row['monthlyPayment'];
    $row['description'] = $row['description'] ?? '';

    $lots[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $lots
], JSON_PRETTY_PRINT);

$stmt->close();
$conn->close();

// ✅ Catch any fatal errors and output JSON instead of HTML
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        echo json_encode([
            "status" => "error",
            "message" => "Server error: " . $error['message']
        ]);
    }
});
?>