<?php
// FILE: /cms.api/clientMaintenanceRequest.php
require_once "db_connect.php";
session_start();
// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $reservationId = $_POST['reservationId'] ?? null;
    $serviceType = $_POST['serviceType'] ?? null;
    $notes = $_POST['notes'] ?? "";
    $userId = $_SESSION['user_id'];

    if (empty($reservationId) || empty($serviceType)) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }

    // ✅ Step 1: Fetch the lotId and lot details directly from the reservation
    $reservationQuery = $conn->prepare("SELECT lotId, area, block, rowNumber, lotNumber FROM reservations WHERE reservationId = ?");
    $reservationQuery->bind_param("i", $reservationId);
    $reservationQuery->execute();
    $reservationResult = $reservationQuery->get_result();

    if ($reservationResult->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Invalid reservation ID — no reservation found."]);
        exit;
    }

    $reservationRow = $reservationResult->fetch_assoc();
    $lotId = $reservationRow['lotId'];
    $area = $reservationRow['area'];
    $block = $reservationRow['block'];
    $rowNumber = $reservationRow['rowNumber'];
    $lotNumber = $reservationRow['lotNumber'];
    $reservationQuery->close();

    // ✅ Step 2: Insert the maintenance request with full details
    $sql = "INSERT INTO maintenancerequest 
            (userId, reservationId, lotId, area, block, rowNumber, lotNumber, serviceType, requestedDate, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'Pending')";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "SQL Prepare Failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("iiississs", 
        $userId, 
        $reservationId, 
        $lotId, 
        $area, 
        $block, 
        $rowNumber, 
        $lotNumber, 
        $serviceType, 
        $notes
    );

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Maintenance request submitted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Submission Failed (DB Error): " . $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>
