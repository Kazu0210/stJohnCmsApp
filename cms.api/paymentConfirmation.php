<?php
// paymentConfirmation.php - Admin/Secretary Payment Confirmation API
session_start();
require_once "db_connect.php";

// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Check if user is admin or secretary
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Secretary'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Get pending payments for confirmation
    try {
        $stmt = $conn->prepare("
            SELECT 
                p.paymentId,
                p.reservationId,
                p.userId,
                p.paymentMethodId,
                p.amount,
                p.reference,
                p.document,
                p.status,
                p.datePaid,
                r.clientName,
                r.area,
                r.block,
                r.lotNumber,
                lt.typeName as lotType,
                u.firstName,
                u.lastName
            FROM payments p
            LEFT JOIN reservations r ON p.reservationId = r.reservationId
            LEFT JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
            LEFT JOIN user u ON p.userId = u.userId
            WHERE p.status = 'Pending'
            ORDER BY p.datePaid ASC
        ");
        
        $stmt->execute();
        $result = $stmt->get_result();
        $payments = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "data" => $payments
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
    
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Confirm or reject payment
    $input = json_decode(file_get_contents("php://input"), true);
    
    $paymentId = intval($input['paymentId'] ?? 0);
    $action = $input['action'] ?? ''; // 'confirm' or 'reject'
    $notes = trim($input['notes'] ?? '');
    
    if (!$paymentId || !in_array($action, ['confirm', 'reject'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        $newStatus = $action === 'confirm' ? 'Confirmed' : 'Rejected';
        
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = ?, 
                confirmedBy = ?, 
                confirmedAt = NOW(),
                confirmationNotes = ?
            WHERE paymentId = ?
        ");
        
        $confirmedBy = $_SESSION['user_id'];
        $stmt->bind_param("sisi", $newStatus, $confirmedBy, $notes, $paymentId);
        
        if ($stmt->execute()) {
            // If confirmed, update lot status to reserved (yellow)
            if ($action === 'confirm') {
                $updateLotStmt = $conn->prepare("
                    UPDATE lots l
                    INNER JOIN reservations r ON l.lotId = CONCAT(r.area, '-', r.block, '-', r.rowNumber, '-', r.lotNumber)
                    SET l.status = 'Reserved'
                    WHERE r.reservationId = (SELECT reservationId FROM payments WHERE paymentId = ?)
                ");
                $updateLotStmt->bind_param("i", $paymentId);
                $updateLotStmt->execute();
                $updateLotStmt->close();
            }
            
            $conn->commit();
            echo json_encode([
                "status" => "success",
                "message" => "Payment " . $action . "ed successfully"
            ]);
        } else {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to update payment status"]);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}

$conn->close();
?>
