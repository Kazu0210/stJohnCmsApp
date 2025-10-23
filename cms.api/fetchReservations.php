<?php
require_once "db_connect.php";
session_start();

header('Content-Type: application/json');
// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// For admin dashboard, get ALL reservations (remove session check)
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode([]);
//     exit;
// }

try {
    // Get all reservations for admin dashboard
    $sql = "SELECT reservationId, area, block, lotNumber, userId, createdAt
            FROM reservations
            ORDER BY reservationId DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservations = [];
    $todayCount = 0;
    $weekCount = 0;
    $today = date('Y-m-d'); // Get today's date in YYYY-MM-DD format
    
    // Calculate start of this week (Monday)
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
    
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
        
        // Check if reservation was made today or this week
        if ($row['createdAt']) {
            $reservationDate = date('Y-m-d', strtotime($row['createdAt']));
            
            // Count today's reservations
            if ($reservationDate === $today) {
                $todayCount++;
            }
            
            // Count this week's reservations (Monday to Sunday)
            if ($reservationDate >= $startOfWeek && $reservationDate <= $endOfWeek) {
                $weekCount++;
            }
        }
    }

    // Return structured response for dashboard
    $response = [
        'success' => true,
        'totalCount' => count($reservations),
        'todayCount' => $todayCount,
        'weekCount' => $weekCount,
        'today' => $today,
        'weekRange' => $startOfWeek . ' to ' . $endOfWeek,
        'data' => $reservations
    ];

    echo json_encode($response);

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'totalCount' => 0,
        'todayCount' => 0,
        'data' => []
    ]);
}

$conn->close();
?>
