<?php
// updatePayment.php - API endpoint for updating payment records
require_once "db_connect.php";
require_once "auth_helper.php";

// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authentication required"]);
    exit;
}

// Check if user has admin or secretary role
if (!isAdminOrSecretary()) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access denied. Admin or Secretary role required"]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

try {
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid JSON input: " . json_last_error_msg()]);
        exit;
    }
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "No input data provided"]);
        exit;
    }
    
    // Validate required fields
    $paymentId = $input['paymentId'] ?? null;
    $status = $input['status'] ?? null;
    $amountPaid = $input['amountPaid'] ?? null;
    $paymentMethod = $input['paymentMethod'] ?? null;
    $reference = $input['reference'] ?? null;
    
    if (!$paymentId || !$status) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Payment ID and status are required"]);
        exit;
    }
    
    // Map payment method to ID
    $paymentMethodId = null;
    switch (strtolower($paymentMethod)) {
        case 'gcash':
            $paymentMethodId = 1;
            break;
        case 'bank transfer':
            $paymentMethodId = 2;
            break;
        case 'cash':
            $paymentMethodId = 3;
            break;
        default:
            $paymentMethodId = null;
    }
    
    // Validate status - map frontend statuses to database statuses
    $statusMapping = [
        'Pending' => 'Pending',
        'Paid' => 'Confirmed',
        'Partially Paid' => 'Confirmed', 
        'Completed' => 'Confirmed',
        'Deferred' => 'Pending',
        'Cancelled' => 'Rejected'
    ];
    
    if (!isset($statusMapping[$status])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid status"]);
        exit;
    }
    
    // Map the frontend status to database status
    $dbStatus = $statusMapping[$status];
    
    // Validate amount
    if ($amountPaid !== null && (!is_numeric($amountPaid) || $amountPaid < 0)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid amount"]);
        exit;
    }
    
    // Check if payment exists
    $checkSql = "SELECT paymentId FROM payments WHERE paymentId = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $paymentId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Payment record not found"]);
        exit;
    }
    
    // Build update query
    $updateFields = [];
    $updateValues = [];
    $types = "";
    
    $updateFields[] = "status = ?";
    $updateValues[] = $dbStatus;
    $types .= "s";
    
    if ($amountPaid !== null) {
        $updateFields[] = "amount = ?";
        $updateValues[] = $amountPaid;
        $types .= "d";
    }
    
    if ($paymentMethodId !== null) {
        $updateFields[] = "paymentMethodId = ?";
        $updateValues[] = $paymentMethodId;
        $types .= "i";
    }
    
    if ($reference !== null) {
        $updateFields[] = "reference = ?";
        $updateValues[] = $reference;
        $types .= "s";
    }
    
    // Add updated timestamp
    $updateFields[] = "updatedAt = NOW()";
    
    // Add payment ID to values for WHERE clause
    $updateValues[] = $paymentId;
    $types .= "i";
    
    $sql = "UPDATE payments SET " . implode(", ", $updateFields) . " WHERE paymentId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$updateValues);
    
    if ($stmt->execute()) {
        // Log the update in audit logs
        $userId = getCurrentUserId();
        $userName = getCurrentUserName();
        $logMessage = "Updated payment record ID $paymentId: Status changed to '$status'";
        if ($amountPaid !== null) {
            $logMessage .= ", Amount: $amountPaid";
        }
        if ($paymentMethod !== null) {
            $logMessage .= ", Method: $paymentMethod";
        }
        if ($reference !== null) {
            $logMessage .= ", Reference: $reference";
        }
        
        // Get the lotId from the payment record for audit logging
        $lotSql = "SELECT r.lotId FROM payments p JOIN reservations r ON p.reservationId = r.reservationId WHERE p.paymentId = ?";
        $lotStmt = $conn->prepare($lotSql);
        $lotStmt->bind_param("i", $paymentId);
        $lotStmt->execute();
        $lotResult = $lotStmt->get_result();
        $lotId = $lotResult->fetch_assoc()['lotId'] ?? 0;
        
        // Insert audit log with new table structure
        $auditSql = "INSERT INTO auditlogs (lotId, userId, module, actionType, description, createdAt) VALUES (?, ?, 'PAYMENT', 'UPDATE', ?, NOW())";
        $auditStmt = $conn->prepare($auditSql);
        $auditStmt->bind_param("iis", $lotId, $userId, $logMessage);
        $auditStmt->execute();
        
        echo json_encode([
            "status" => "success",
            "message" => "Payment record updated successfully",
            "data" => [
                "paymentId" => $paymentId,
                "status" => $status,
                "amountPaid" => $amountPaid,
                "paymentMethod" => $paymentMethod,
                "reference" => $reference
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to update payment record"]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
