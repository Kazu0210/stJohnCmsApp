<?php
// FILE: /cms.api/get_payment_data.php
// ESSENTIAL HEADERS: These lines are REQUIRED for the JavaScript to communicate with this script.
header("Access-Control-Allow-Origin: *"); // For development only. Change '*' to your actual domain in production.
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_connect.php'; 
session_start();

// --- MOCK USER ID FOR TESTING --- //
// In production, you will get this from a real login. For now, we set it manually.
if (!isset($_SESSION['userId'])) {
    $_SESSION['userId'] = 1; // Manually set to user 1 for testing
}
$userId = $_SESSION['userId'];

try {
    // Fetch reservations including rowNumber
    $sql = "
        SELECT 
            r.reservationId, r.area, r.block, r.rowNumber, r.lotNumber, r.createdAt,
            lt.price AS totalPrice, lt.monthlyPayment
        FROM reservations r
        JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
        WHERE r.userId = ? AND r.status IN ('Reserved', 'Confirmed')
    ";
    $stmt_reservations = $conn->prepare($sql);
    $stmt_reservations->bind_param("i", $userId);
    $stmt_reservations->execute();
    $reservations = $stmt_reservations->get_result()->fetch_all(MYSQLI_ASSOC);

    $formatted_data = [];

    foreach ($reservations as $res) {
        $reservationId = $res['reservationId'];

        // Fetch payment history for this lot
        $sql_payments = "
            SELECT p.paymentMonth, p.paymentDate, p.amountPaid, pm.methodName, p.referenceNumber, p.status, p.proofOfPayment
            FROM payments p
            JOIN payment_methods pm ON p.paymentMethodId = pm.paymentMethodId
            WHERE p.reservationId = ? ORDER BY p.paymentMonth ASC
        ";
        $stmt_payments = $conn->prepare($sql_payments);
        $stmt_payments->bind_param("i", $reservationId);
        $stmt_payments->execute();
        $payments = $stmt_payments->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Calculate Next Due Date
        $deadline = null;
        if (count($payments) == 0) {
            $createdAt = new DateTime($res['createdAt']);
            $createdAt->add(new DateInterval('PT24H')); // 24 hours for first payment
            $deadline = $createdAt->format('Y-m-d H:i:s');
        } else {
            $sql_schedule = "SELECT dueDate FROM payment_schedules WHERE reservationId = ? AND status = 'Unpaid' ORDER BY dueDate ASC LIMIT 1";
            $stmt_schedule = $conn->prepare($sql_schedule);
            $stmt_schedule->bind_param("i", $reservationId);
            $stmt_schedule->execute();
            if ($next_due = $stmt_schedule->get_result()->fetch_assoc()) {
                $deadline = $next_due['dueDate'] . " 23:59:59"; // Due at end of the day
            }
        }

        // Format the lot name to include rowNumber
        $formatted_data[$reservationId] = [
            'id' => $reservationId,
            'name' => "{$res['area']}, Block {$res['block']}, Row {$res['rowNumber']}, Lot {$res['lotNumber']}",
            'totalPrice' => (float)$res['totalPrice'],
            'monthlyInstallment' => (float)$res['monthlyPayment'],
            'deadline' => $deadline,
            'paymentHistory' => array_map(function($p) {
                return [ 'month' => (int)$p['paymentMonth'], 'datePaid' => $p['paymentDate'], 'amount' => (float)$p['amountPaid'], 'method' => $p['methodName'], 'reference' => $p['referenceNumber'], 'status' => $p['status'], 'documentUrl' => $p['proofOfPayment'] ];
            }, $payments)
        ];
    }
    
    echo json_encode(["status" => "success", "data" => $formatted_data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "An error occurred: " . $e->getMessage()]);
}
$conn->close();
?>