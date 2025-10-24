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

    // Select reservation fields that exist in your schema and compute totals from related tables
    $sql = "SELECT r.reservationId,
                   r.area,
                   r.block,
                   r.lotNumber,
                   r.userId,
                   r.createdAt,
                   r.status,
                   COALESCE(lt.price, 0) AS total_amount,
                   (
                       SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                       WHERE p.reservationId = r.reservationId AND p.status IN ('Confirmed','Completed')
                   ) AS total_paid
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
        // Normalize numeric fields and provide camelCase aliases for compatibility
        $row['total_amount'] = isset($row['total_amount']) ? (float)$row['total_amount'] : 0.0;
        $row['totalPaid'] = isset($row['total_paid']) ? (float)$row['total_paid'] : 0.0; // keep camelCase used elsewhere
        $row['totalPaid_snake'] = isset($row['total_paid']) ? (float)$row['total_paid'] : 0.0;
        // Also expose camelCase totalAmount for older callers
        $row['totalAmount'] = $row['total_amount'];

        // Ensure reservationId and userId are integers
        if (isset($row['reservationId'])) $row['reservationId'] = (int)$row['reservationId'];
        if (isset($row['userId'])) $row['userId'] = (int)$row['userId'];

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
