<?php
session_start();
require_once "db_connect.php";
require_once "auth_helper.php";

// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if user has admin or secretary role
if (!isAdminOrSecretary()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin or Secretary role required.']);
    exit;
}

try {
    // Fetch financial data
    $financialData = getFinancialData($conn);
    
    // Fetch lot statistics
    $lotStats = getLotStatistics($conn);
    
    // Combine all data
    $response = [
        'success' => true,
        'data' => [
            'financial' => $financialData,
            'lotStats' => $lotStats
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching dashboard data: ' . $e->getMessage()
    ]);
}

function getFinancialData($conn) {
    // Get total payments received (confirmed payments)
    $sql = "SELECT 
                SUM(amount) as totalReceived,
                COUNT(*) as totalPayments
            FROM payments 
            WHERE status = 'Confirmed'";
    
    $result = $conn->query($sql);
    $financial = $result->fetch_assoc();
    
    // Get outstanding balances (pending payments)
    $sql = "SELECT 
                SUM(amount) as outstandingAmount,
                COUNT(*) as outstandingCount
            FROM payments 
            WHERE status IN ('Pending', 'Deferred')";
    
    $result = $conn->query($sql);
    $outstanding = $result->fetch_assoc();
    
    // Get upcoming due payments (this month)
    $currentMonth = date('Y-m');
    $sql = "SELECT COUNT(*) as upcomingCount
            FROM payments 
            WHERE DATE_FORMAT(createdAt, '%Y-%m') = ? 
            AND status IN ('Pending', 'Deferred')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $currentMonth);
    $stmt->execute();
    $result = $stmt->get_result();
    $upcoming = $result->fetch_assoc();
    
    return [
        'paymentsReceived' => $financial['totalReceived'] ?? 0,
        'totalPayments' => $financial['totalPayments'] ?? 0,
        'outstandingBalances' => $outstanding['outstandingAmount'] ?? 0,
        'outstandingCount' => $outstanding['outstandingCount'] ?? 0,
        'upcomingDue' => $upcoming['upcomingCount'] ?? 0
    ];
}

function getLotStatistics($conn) {
    // Get total lots
    $sql = "SELECT COUNT(*) as totalLots FROM lots";
    $result = $conn->query($sql);
    $totalLots = $result->fetch_assoc()['totalLots'];
    
    // Get reserved lots
    $sql = "SELECT COUNT(*) as reservedLots FROM reservations WHERE status = 'Active'";
    $result = $conn->query($sql);
    $reservedLots = $result->fetch_assoc()['reservedLots'];
    
    // Get occupied lots (lots with burials)
    $sql = "SELECT COUNT(DISTINCT reservationId) as occupiedLots FROM burials";
    $result = $conn->query($sql);
    $occupiedLots = $result->fetch_assoc()['occupiedLots'];
    
    // Calculate available lots
    $availableLots = $totalLots - $reservedLots - $occupiedLots;
    
    return [
        'totalLots' => $totalLots,
        'availableLots' => max(0, $availableLots), // Ensure non-negative
        'reservedLots' => $reservedLots,
        'occupiedLots' => $occupiedLots
    ];
}

$conn->close();
?>
