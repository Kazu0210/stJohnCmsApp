<?php
session_start();
require_once "db_connect.php";

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle different request types based on action parameter
$action = $_GET['action'] ?? 'fetch_all';

function sendResponse($success, $message = "", $data = []) {
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

try {
    switch ($action) {
        case 'fetch_all':
            fetchAllMaintenanceRequests($conn);
            break;
        case 'count_by_status':
            countMaintenanceByStatus($conn);
            break;
        default:
            fetchAllMaintenanceRequests($conn);
            break;
    }
} catch (Exception $e) {
    sendResponse(false, "Error: " . $e->getMessage());
}

function fetchAllMaintenanceRequests($conn) {
    $sql = "SELECT 
                m.requestId,
                m.userId,
                m.reservationId,
                m.serviceType,
                m.status,
                m.requestedDate,
                m.notes,
                m.createdAt,
                m.updatedAt,
                m.area,
                m.block,
                m.lotNumber,
                r.clientName,
                r.contactNumber,
                CONCAT(u.firstName, ' ', u.lastName) as username,
                u.email
            FROM maintenancerequest m
            LEFT JOIN reservations r ON m.reservationId = r.reservationId
            LEFT JOIN user u ON m.userId = u.userId
            ORDER BY m.requestedDate DESC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        sendResponse(false, "Error fetching maintenance requests: " . $conn->error);
    }
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    sendResponse(true, "Maintenance requests fetched successfully", $requests);
}

function countMaintenanceByStatus($conn) {
    $sql = "SELECT 
                status,
                COUNT(*) as count
            FROM maintenancerequest 
            GROUP BY status";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        sendResponse(false, "Error counting maintenance requests: " . $conn->error);
    }
    
    $counts = [
        'pending' => 0,
        'in_progress' => 0,
        'completed' => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $status = strtolower(trim($row['status']));
        $count = (int)$row['count'];
        
        switch ($status) {
            case 'pending':
                $counts['pending'] = $count;
                break;
            case 'in progress':
            case 'in_progress':
            case 'inprogress':
                $counts['in_progress'] = $count;
                break;
            case 'completed':
            case 'complete':
                $counts['completed'] = $count;
                break;
        }
    }
    
    sendResponse(true, "Maintenance counts fetched successfully", $counts);
}

$conn->close();
?>