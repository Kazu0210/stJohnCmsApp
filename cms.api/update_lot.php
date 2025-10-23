<?php
// update_lot.php - Corrected to include datePending logic
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require "db_connect.php";

// Check login and retrieve the current user's ID
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized: User not logged in."]);
    exit;
}
$sessionUserId = (int)$_SESSION['user_id']; 

// Decode JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['lotId'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "lotId is required"]);
    exit;
}

// Sanitize inputs
$lotId      = (int)($data['lotId']);
$block      = trim($data['block'] ?? '');
$area       = trim($data['area'] ?? '');
$rowNumber  = trim($data['rowNumber'] ?? '');
$lotNumber  = trim($data['lotNumber'] ?? '');
$lotTypeId  = isset($data['lotTypeId']) ? (int)$data['lotTypeId'] : null;
$buryDepth  = trim($data['buryDepth'] ?? '');
$status     = trim($data['status'] ?? 'Available');

// Enforce the authenticated user's ID for any reservation/update
$userId = $sessionUserId;

$allowedStatuses = ['Available','Pending','Reserved','Occupied'];
if (!in_array($status, $allowedStatuses)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid status"]);
    exit;
}

try {
    // 💡 CRITICAL: Conditional datePending logic 
    // Set to NOW() if status is 'Pending', otherwise set to NULL (cleared).
    $datePendingUpdate = ($status === 'Pending') ? ', datePending = NOW()' : ', datePending = NULL';

    $sql = "UPDATE lots SET 
        userId = ?, block = ?, area = ?, rowNumber = ?, lotNumber = ?, 
        lotTypeId = ?, buryDepth = ?, status = ?, updatedAt = NOW()
        " . $datePendingUpdate . "  
        WHERE lotId = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception($conn->error);

    // Bind parameters: "issssissi"
    // (i=userId, s=block, s=area, s=row, s=lot, i=lotTypeId, s=depth, s=status, i=lotId)
    $stmt->bind_param(
        "issssissi",
        $userId, 
        $block,
        $area,
        $rowNumber,
        $lotNumber,
        $lotTypeId,
        $buryDepth,
        $status,
        $lotId
    );

    if ($stmt->execute()) {
        if ($stmt->affected_rows >= 0) {
            echo json_encode([
                "success" => true,
                "message" => "Lot updated successfully",
                "lotId" => $lotId
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "No rows updated"
            ]);
        }
    } else {
        echo json_encode(["success" => false, "message" => $stmt->error]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Exception: " . $e->getMessage()]);
} finally {
    $conn->close();
}