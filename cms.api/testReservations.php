<?php
require_once "db_connect.php";

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
    // Query to get ALL reservations
    $sql = "SELECT reservationId, area, block, lotNumber, userId, createdAt
            FROM reservations
            ORDER BY reservationId DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservations = [];
    $todayCount = 0;
    $today = date('Y-m-d'); // Get today's date in YYYY-MM-DD format
    
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
        
        // Check if reservation was made today
        $reservationDate = date('Y-m-d', strtotime($row['created_at']));
        if ($reservationDate === $today) {
            $todayCount++;
        }
    }

    // Response with both total and today's count
    $response = [
        'success' => true,
        'totalCount' => count($reservations),
        'todayCount' => $todayCount,
        'today' => $today,
        'data' => $reservations,
        'debug' => [
            'query_executed' => true,
            'table_exists' => true
        ]
    ];

    echo json_encode($response);

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'query_executed' => false,
            'error_details' => $e->getTrace()
        ]
    ]);
}

$conn->close();
?>