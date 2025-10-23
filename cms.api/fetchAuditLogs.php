<?php
require_once "db_connect.php";
session_start();

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
    // Get recent audit logs (limit to last 10 entries)
    $sql = "SELECT al.logId, al.userId, al.module, al.actionType, al.description, al.createdAt, u.displayName 
            FROM auditlogs al 
            LEFT JOIN users u ON al.userId = u.userId 
            ORDER BY al.createdAt DESC 
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $auditLogs = [];
    
    while ($row = $result->fetch_assoc()) {
        // Format the timestamp for display
        $timeAgo = '';
        if ($row['createdAt']) {
            $createdTime = strtotime($row['createdAt']);
            $currentTime = time();
            $timeDiff = $currentTime - $createdTime;
            
            if ($timeDiff < 60) {
                $timeAgo = 'Just now';
            } elseif ($timeDiff < 3600) {
                $minutes = floor($timeDiff / 60);
                $timeAgo = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
            } elseif ($timeDiff < 86400) {
                $hours = floor($timeDiff / 3600);
                $timeAgo = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
            } else {
                $days = floor($timeDiff / 86400);
                $timeAgo = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
            }
        }
        
        $auditLogs[] = [
            'logId' => $row['logId'],
            'userId' => $row['userId'],
            'displayName' => $row['displayName'] ?? 'Unknown User',
            'module' => $row['module'],
            'actionType' => $row['actionType'],
            'description' => $row['description'],
            'createdAt' => $row['createdAt'],
            'timeAgo' => $timeAgo
        ];
    }

    // Get the most recent 3 activities for the dashboard card
    $recentActivities = array_slice($auditLogs, 0, 3);

    echo json_encode([
        'success' => true,
        'data' => $auditLogs,
        'recentActivities' => $recentActivities,
        'count' => count($auditLogs)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>