<?php
// Start session first, before any output
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user, log them instead

// Start output buffering to prevent any accidental output
ob_start();

require_once "db_connect.php";
require_once "auth_helper.php";

// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Clean any output buffer
ob_clean();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authentication required"]);
    exit;
}

// Check if user has admin or secretary role
$userRole = $_SESSION['role'] ?? '';
if (!in_array($userRole, ['Admin', 'Secretary'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access denied. Admin or Secretary role required"]);
    exit;
}

// Get request parameters
$reportType = $_GET['type'] ?? $_POST['type'] ?? '';
$startDate = $_GET['start'] ?? $_POST['start'] ?? '';
$endDate = $_GET['end'] ?? $_POST['end'] ?? '';
$format = $_GET['format'] ?? $_POST['format'] ?? 'pdf';

// Validate parameters
if (empty($reportType) || empty($startDate) || empty($endDate)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required parameters: type, start, end"]);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid date format. Use YYYY-MM-DD"]);
    exit;
}

// Validate date range
if (strtotime($startDate) > strtotime($endDate)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Start date cannot be after end date"]);
    exit;
}

// Validate format
if (!in_array($format, ['pdf', 'excel'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid format. Use 'pdf' or 'excel'"]);
    exit;
}

try {
    $reportData = [];
    
    // Log the request for debugging
    error_log("Report request: type=$reportType, start=$startDate, end=$endDate, format=$format");
    
    switch ($reportType) {
        case 'financial':
            $reportData = generateFinancialReport($conn, $startDate, $endDate);
            break;
        case 'reservation':
            $reportData = generateReservationReport($conn, $startDate, $endDate);
            break;
        case 'maintenance':
            $reportData = generateMaintenanceReport($conn, $startDate, $endDate);
            break;
        case 'burial':
            $reportData = generateBurialReport($conn, $startDate, $endDate);
            break;
        case 'clients':
            $reportData = generateClientsReport($conn, $startDate, $endDate);
            break;
        case 'logs':
            $reportData = generateLogsReport($conn, $startDate, $endDate);
            break;
        default:
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid report type"]);
            exit;
    }
    
    // Calculate total records properly
    $totalRecords = 0;
    if (is_array($reportData)) {
        if (isset($reportData['summary'])) {
            $totalRecords = $reportData['summary']['totalRecords'] ?? 0;
        } else {
            $totalRecords = count($reportData);
        }
    }
    
    // For now, return JSON data. In production, you would generate actual PDF/Excel files
    echo json_encode([
        "status" => "success",
        "message" => ucfirst($reportType) . " report generated successfully",
        "data" => $reportData,
        "metadata" => [
            "reportType" => $reportType,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "format" => $format,
            "generatedAt" => date('Y-m-d H:i:s'),
            "totalRecords" => $totalRecords
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Report generation error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error generating report: " . $e->getMessage()]);
} catch (Error $e) {
    // Log PHP errors
    error_log("PHP Error in report generation: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error occurred while generating report"]);
}

function generateFinancialReport($conn, $startDate, $endDate) {
    $sql = "SELECT 
                p.paymentId,
                p.userId,
                u.firstName,
                u.lastName,
                p.amount,
                p.status,
                p.datePaid,
                p.createdAt,
                p.reference,
                p.document,
                r.area,
                r.block,
                r.lotNumber
            FROM payments p
            LEFT JOIN user u ON p.userId = u.userId
            LEFT JOIN reservations r ON p.reservationId = r.reservationId
            WHERE DATE(p.createdAt) BETWEEN ? AND ?
            ORDER BY p.createdAt DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $totalAmount = 0;
    $confirmedPayments = 0;
    $pendingPayments = 0;
    $rejectedPayments = 0;
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totalAmount += floatval($row['amount']);
        
        if ($row['status'] === 'Confirmed') {
            $confirmedPayments++;
        } elseif ($row['status'] === 'Pending') {
            $pendingPayments++;
        } elseif ($row['status'] === 'Rejected') {
            $rejectedPayments++;
        }
    }
    
    return [
        'payments' => $data,
        'summary' => [
            'totalAmount' => $totalAmount,
            'confirmedPayments' => $confirmedPayments,
            'pendingPayments' => $pendingPayments,
            'rejectedPayments' => $rejectedPayments,
            'totalRecords' => count($data)
        ]
    ];
}

function generateReservationReport($conn, $startDate, $endDate) {
    $sql = "SELECT 
                r.reservationId,
                r.userId,
                u.firstName,
                u.lastName,
                r.area,
                r.block,
                r.rowNumber,
                r.lotNumber,
                lt.typeName as lotType,
                r.status,
                r.createdAt,
                r.updatedAt
            FROM reservations r
            LEFT JOIN user u ON r.userId = u.userId
            LEFT JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
            WHERE DATE(r.createdAt) BETWEEN ? AND ?
            ORDER BY r.createdAt DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $areaStats = [];
    $blockStats = [];
    $statusStats = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        
        // Count by area
        $area = $row['area'];
        $areaStats[$area] = ($areaStats[$area] ?? 0) + 1;
        
        // Count by block
        $block = $row['block'];
        $blockStats[$block] = ($blockStats[$block] ?? 0) + 1;
        
        // Count by status
        $status = $row['status'];
        $statusStats[$status] = ($statusStats[$status] ?? 0) + 1;
    }
    
    return [
        'reservations' => $data,
        'summary' => [
            'areaStats' => $areaStats,
            'blockStats' => $blockStats,
            'statusStats' => $statusStats,
            'totalRecords' => count($data)
        ]
    ];
}

function generateMaintenanceReport($conn, $startDate, $endDate) {
    $sql = "SELECT 
                m.requestId,
                m.userId,
                u.firstName,
                u.lastName,
                m.serviceType,
                m.status,
                m.requestedDate,
                m.notes,
                m.createdAt,
                r.area,
                r.block,
                r.lotNumber
            FROM maintenancerequest m
            LEFT JOIN user u ON m.userId = u.userId
            LEFT JOIN reservations r ON m.reservationId = r.reservationId
            WHERE DATE(m.createdAt) BETWEEN ? AND ?
            ORDER BY m.createdAt DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $statusStats = [];
    $serviceTypeStats = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        
        // Count by status
        $status = $row['status'];
        $statusStats[$status] = ($statusStats[$status] ?? 0) + 1;
        
        // Count by service type
        $serviceType = $row['serviceType'];
        $serviceTypeStats[$serviceType] = ($serviceTypeStats[$serviceType] ?? 0) + 1;
    }
    
    return [
        'maintenanceRequests' => $data,
        'summary' => [
            'statusStats' => $statusStats,
            'serviceTypeStats' => $serviceTypeStats,
            'totalRecords' => count($data)
        ]
    ];
}

function generateBurialReport($conn, $startDate, $endDate) {
    $sql = "SELECT 
                b.burialId,
                b.userId,
                u.firstName,
                u.lastName,
                b.deceasedName,
                b.burialDate,
                b.area,
                b.block,
                b.rowNumber,
                b.lotNumber,
                b.lotType,
                b.createdAt,
                r.reservationId
            FROM burials b
            LEFT JOIN user u ON b.userId = u.userId
            LEFT JOIN reservations r ON b.reservationId = r.reservationId
            WHERE DATE(b.createdAt) BETWEEN ? AND ?
            ORDER BY b.createdAt DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $areaStats = [];
    $blockStats = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        
        // Count by area
        $area = $row['area'];
        $areaStats[$area] = ($areaStats[$area] ?? 0) + 1;
        
        // Count by block
        $block = $row['block'];
        $blockStats[$block] = ($blockStats[$block] ?? 0) + 1;
    }
    
    return [
        'burials' => $data,
        'summary' => [
            'areaStats' => $areaStats,
            'blockStats' => $blockStats,
            'totalRecords' => count($data)
        ]
    ];
}

function generateClientsReport($conn, $startDate, $endDate) {
    $sql = "SELECT 
                u.userId,
                u.firstName,
                u.lastName,
                u.email,
                u.contactNumber,
                u.role,
                u.status,
                u.emergencyContactName,
                u.emergencyContactNumber,
                u.createdAt,
                u.updatedAt,
                COUNT(r.reservationId) as reservationCount
            FROM user u
            LEFT JOIN reservations r ON u.userId = r.userId
            WHERE DATE(u.createdAt) BETWEEN ? AND ?
            GROUP BY u.userId
            ORDER BY u.createdAt DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $roleStats = [];
    $statusStats = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        
        // Count by role
        $role = $row['role'];
        $roleStats[$role] = ($roleStats[$role] ?? 0) + 1;
        
        // Count by status
        $status = $row['status'];
        $statusStats[$status] = ($statusStats[$status] ?? 0) + 1;
    }
    
    return [
        'clients' => $data,
        'summary' => [
            'roleStats' => $roleStats,
            'statusStats' => $statusStats,
            'totalRecords' => count($data)
        ]
    ];
}

function generateLogsReport($conn, $startDate, $endDate) {
    $sql = "SELECT 
                a.lotId,
                a.userId,
                u.firstName,
                u.lastName,
                a.module,
                a.actionType,
                a.description,
                a.createdAt
            FROM auditlogs a
            LEFT JOIN user u ON a.userId = u.userId
            WHERE DATE(a.createdAt) BETWEEN ? AND ?
            ORDER BY a.createdAt DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $moduleStats = [];
    $actionStats = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        
        // Count by module
        $module = $row['module'];
        $moduleStats[$module] = ($moduleStats[$module] ?? 0) + 1;
        
        // Count by action type
        $actionType = $row['actionType'];
        $actionStats[$actionType] = ($actionStats[$actionType] ?? 0) + 1;
    }
    
    return [
        'logs' => $data,
        'summary' => [
            'moduleStats' => $moduleStats,
            'actionStats' => $actionStats,
            'totalRecords' => count($data)
        ]
    ];
}
?>
