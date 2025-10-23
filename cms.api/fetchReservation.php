<?php
// fetchReservations.php
// Fetch all reservations for the admin dashboard with lot type and user details.

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require 'db_connect.php';

try {
    // ✅ Correct SQL query using proper JOINs and aliases
    $sql = "
        SELECT 
            u.firstName,
            u.lastName,
            r.*,
            lt.*
        FROM reservations r
        INNER JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
        INNER JOIN user u ON r.userId = u.userId
        ORDER BY r.createdAt DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($data = $result->fetch_assoc()) {
        $rows[] = $data;
    }

    echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
?>