<?php
require_once "db_connect.php";
session_start();

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
    // Get all users from the users table
    $sql = "SELECT userId, firstName, lastName, email, contactNumber, 
                   role, status, emergencyContactName, emergencyContactNumber, createdAt, updatedAt
            FROM user
            ORDER BY userId DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    $todayCount = 0;
    $weekCount = 0;
    $adminCount = 0;
    $secretaryCount = 0;
    $clientCount = 0;
    $activeCount = 0;
    $inactiveCount = 0;
    $archivedCount = 0;
    
    $today = date('Y-m-d'); // Get today's date in YYYY-MM-DD format
    
    // Calculate start of this week (Monday)
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
    
    while ($row = $result->fetch_assoc()) {
        // Remove sensitive information from the response
        unset($row['password']); // Remove password if it exists in the query
        
        $users[] = $row;
        
        // Count users by role
        if (isset($row['role'])) {
            switch ($row['role']) {
                case 'Admin':
                    $adminCount++;
                    break;
                case 'Secretary':
                    $secretaryCount++;
                    break;
                case 'Client':
                    $clientCount++;
                    break;
                default:
                    // Handle empty role or other values
                    break;
            }
        }
        
        // Count users by status
        if (isset($row['status'])) {
            switch ($row['status']) {
                case 'Active':
                    $activeCount++;
                    break;
                case 'Inactive':
                    $inactiveCount++;
                    break;
                case 'Archived':
                    $archivedCount++;
                    break;
                default:
                    // Handle empty status or other values
                    break;
            }
        }
        
        // Check if user was created today or this week
        if ($row['createdAt']) {
            $userCreatedDate = date('Y-m-d', strtotime($row['createdAt']));
            
            // Count today's new users
            if ($userCreatedDate === $today) {
                $todayCount++;
            }
            
            // Count this week's new users (Monday to Sunday)
            if ($userCreatedDate >= $startOfWeek && $userCreatedDate <= $endOfWeek) {
                $weekCount++;
            }
        }
    }

    // Return structured response for dashboard
    $response = [
        'success' => true,
        'totalCount' => count($users),
        'todayCount' => $todayCount,
        'weekCount' => $weekCount,
        'adminCount' => $adminCount,
        'secretaryCount' => $secretaryCount,
        'clientCount' => $clientCount,
        'activeCount' => $activeCount,
        'inactiveCount' => $inactiveCount,
        'archivedCount' => $archivedCount,
        'today' => $today,
        'weekRange' => $startOfWeek . ' to ' . $endOfWeek,
        'data' => $users
    ];

    echo json_encode($response);

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'totalCount' => 0,
        'todayCount' => 0,
        'weekCount' => 0,
        'adminCount' => 0,
        'secretaryCount' => 0,
        'clientCount' => 0,
        'activeCount' => 0,
        'inactiveCount' => 0,
        'archivedCount' => 0,
        'data' => []
    ]);
}

$conn->close();
?>
