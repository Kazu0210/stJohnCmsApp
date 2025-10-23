<?php
// FILE: /cms.api/getPayments.php
require_once "db_connect.php";
session_start();

// --- Enable CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// --- Ensure client is logged in ---
$userId = $_SESSION['user_id'] ?? null;
$reservationId = isset($_GET['reservationId']) ? (int)$_GET['reservationId'] : 0;

if (!$userId) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit;
}

if ($reservationId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid reservation ID"
    ]);
    exit;
}

try {
    // ✅ Fetch all payments for this reservation
    $sql = "
        SELECT 
            p.paymentId,
            p.reservationId,
            p.userId,
            pm.methodName AS paymentMethod,
            p.month,
            p.amount,
            p.datePaid,
            p.reference,
            p.document,
            p.status,
            p.createdAt,
            p.updatedAt
        FROM payments p
        LEFT JOIN payment_methods pm ON p.paymentMethodId = pm.paymentMethodId
        WHERE p.reservationId = ?
        ORDER BY p.createdAt ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    $result = $stmt->get_result();

    $payments = [];
    while ($row = $result->fetch_assoc()) {
        // Format data for clarity
        $payments[] = [
            "paymentId" => $row["paymentId"],
            "reservationId" => $row["reservationId"],
            "userId" => $row["userId"],
            "paymentMethod" => $row["paymentMethod"] ?? "Unknown",
            "month" => $row["month"],
            "amount" => (float)$row["amount"],
            "datePaid" => $row["datePaid"],
            "reference" => $row["reference"],
            "document" => $row["document"],
            "status" => $row["status"],
            "createdAt" => $row["createdAt"],
            "updatedAt" => $row["updatedAt"]
        ];
    }

    echo json_encode([
        "status" => "success",
        "data" => $payments
    ], JSON_PRETTY_PRINT);

    $stmt->close();
    $conn->close();
    exit;

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
    exit;
}
?>
