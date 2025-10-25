<?php
// paymentConfirmation.php - Admin/Secretary Payment Confirmation API
session_start();
require_once "db_connect.php";
require_once "audit_helper.php";

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get JSON input
    $data = json_decode(file_get_contents("php://input"), true);
    $paymentId = $data['paymentId'] ?? null;
    $action = $data['action'] ?? null; // 'confirm' or 'reject'
    $notes = $data['notes'] ?? '';

    if (!$paymentId || !in_array($action, ['confirm', 'reject'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
        exit;
    }

    try {
        $conn->begin_transaction();

        $newStatus = $action === 'confirm' ? 'Confirmed' : 'Rejected';
        $confirmedBy = $_SESSION['user_id'];

        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = ?, 
                confirmedBy = ?, 
                confirmedAt = NOW(),
                confirmationNotes = ?
            WHERE paymentId = ?
        ");
        $stmt->bind_param("sisi", $newStatus, $confirmedBy, $notes, $paymentId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // If confirmed, update lot status to 'Reserved'
            if ($action === 'confirm') {
                $updateLotStmt = $conn->prepare("
                    UPDATE lots l
                    INNER JOIN reservations r ON 
                        l.lotId = CONCAT(r.area, '-', r.block, '-', r.rowNumber, '-', r.lotNumber)
                    SET l.status = 'Reserved'
                    WHERE r.reservationId = (
                        SELECT reservationId FROM payments WHERE paymentId = ?
                    )
                ");
                $updateLotStmt->bind_param("i", $paymentId);
                $updateLotStmt->execute();
                $updateLotStmt->close();
            }

            // Log audit
            $auditAction = $action === 'confirm' ? 'Approve Payment' : 'Reject Payment';
            $auditDetails = "Payment ID: $paymentId, Notes: $notes";
            log_audit($confirmedBy, $auditAction, $auditDetails);

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
