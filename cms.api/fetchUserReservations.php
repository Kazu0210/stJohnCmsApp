<?php
require_once "db_connect.php";
session_start();

header('Content-Type: application/json');

try {
    // Determine userId: prefer session, fallback to GET param
    $userId = null;
    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
    } elseif (isset($_GET['userId']) && is_numeric($_GET['userId'])) {
        $userId = (int)$_GET['userId'];
    }

    if ($userId === null) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing or invalid userId',
            'totalCount' => 0,
            'data' => []
        ]);
        exit;
    }

    // Include total price for the lot (totalAmount) and totalPaid from payments table
    $sql = "SELECT r.reservationId, r.area, r.block, r.lotNumber, r.userId, r.createdAt, r.status, r.payment_type,
                   COALESCE(r.total_amount, lt.price, 0) AS total_amount,
                   (
                       SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                       WHERE p.reservationId = r.reservationId AND p.status IN ('Confirmed','Completed')
                   ) AS total_paid,
                   COALESCE(r.amount_paid, 0) AS amount_paid,
                   COALESCE(r.amount_due, 0) AS amount_due
            FROM reservations r
            LEFT JOIN lots l ON r.lotId = l.lotId
            LEFT JOIN lot_types lt ON l.lotTypeId = lt.lotTypeId
            WHERE r.userId = ? AND r.status IN ('Reserved')
            ORDER BY r.reservationId DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        // Cast numeric fields to proper types
        $row['totalAmount'] = isset($row['totalAmount']) ? (float)$row['totalAmount'] : 0.0;
        $row['totalPaid'] = isset($row['totalPaid']) ? (float)$row['totalPaid'] : 0.0;
        $reservations[] = $row;
    }

    echo json_encode([
        'success' => true,
        'totalCount' => count($reservations),
        'data' => $reservations
    ]);

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'totalCount' => 0,
        'data' => []
    ]);
}

$conn->close();
?>
