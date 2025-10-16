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

    $sql = "SELECT reservationId, area, block, lotNumber, userId, createdAt, status
            FROM reservations
            WHERE userId = ?
            ORDER BY reservationId DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservations = [];
    while ($row = $result->fetch_assoc()) {
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
