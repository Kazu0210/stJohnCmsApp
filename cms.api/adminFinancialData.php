<?php
// adminFinancialData.php - API endpoint for admin financial page
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
    echo json_encode(["status" => "error", "message" => "Authentication required"]);
    exit;
}

// Check if user has admin or secretary role
if (!isAdminOrSecretary()) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access denied. Admin or Secretary role required"]);
    exit;
}

try {
    // Get all payment records with user and reservation information
    $sql = "SELECT 
                p.paymentId,
                p.reservationId,
                p.userId,
                u.firstName,
                u.lastName,
                p.month,
                p.amount,
                p.datePaid,
                p.reference,
                p.document,
                p.status,
                p.createdAt,
                r.area,
                r.block,
                r.rowNumber,
                r.lotNumber,
                CASE 
                    WHEN p.paymentMethodId = 1 THEN 'GCash'
                    WHEN p.paymentMethodId = 2 THEN 'Bank Transfer'
                    WHEN p.paymentMethodId = 3 THEN 'Cash'
                    ELSE 'N/A'
                END AS paymentMethod
            FROM payments p
            LEFT JOIN user u ON p.userId = u.userId
            LEFT JOIN reservations r ON p.reservationId = r.reservationId
            ORDER BY p.createdAt DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    $totalIncomeYTD = 0;
    $incomeThisMonth = 0;
    $pendingCount = 0;
    $deferredCount = 0;
    $monthlyData = [];
    
    $currentYear = date('Y');
    $currentMonth = date('n');
    
    // Initialize monthly data for chart
    for ($i = 11; $i >= 0; $i--) {
        $monthDate = new DateTime();
        $monthDate->modify("-$i months");
        $monthKey = $monthDate->format('M Y');
        $monthlyData[$monthKey] = 0;
    }
    
    while ($row = $result->fetch_assoc()) {
        // Map database statuses to frontend statuses
        $statusMapping = [
            'Pending' => 'Pending',
            'Confirmed' => 'Paid',
            'Rejected' => 'Cancelled'
        ];
        
        $frontendStatus = $statusMapping[$row['status']] ?? 'Pending';
        
        $payment = [
            'id' => $row['paymentId'],
            'clientName' => trim($row['firstName'] . ' ' . $row['lastName']),
            'lot' => $row['area'] . '-' . $row['block'] . '-' . $row['rowNumber'] . '-' . $row['lotNumber'],
            'monthDue' => $row['month'],
            'amountPaid' => floatval($row['amount']),
            'method' => $row['paymentMethod'],
            'reference' => $row['reference'],
            'status' => $frontendStatus,
            'date' => $row['createdAt'],
            'document' => $row['document']
        ];
        
        $payments[] = $payment;
        
        // Calculate statistics
        $paymentDate = new DateTime($row['createdAt']);
        $paymentYear = $paymentDate->format('Y');
        $paymentMonth = $paymentDate->format('n');
        
        // Count successful payments for income calculation
        if (in_array($row['status'], ['Paid', 'Partially Paid', 'Completed']) && $row['amount'] > 0) {
            // YTD income
            if ($paymentYear == $currentYear) {
                $totalIncomeYTD += floatval($row['amount']);
            }
            
            // This month income
            if ($paymentYear == $currentYear && $paymentMonth == $currentMonth) {
                $incomeThisMonth += floatval($row['amount']);
            }
            
            // Monthly chart data
            $monthKey = $paymentDate->format('M Y');
            if (isset($monthlyData[$monthKey])) {
                $monthlyData[$monthKey] += floatval($row['amount']);
            }
        }
        
        // Count pending and deferred
        if ($row['status'] === 'Pending') {
            $pendingCount++;
        } elseif ($row['status'] === 'Deferred') {
            $deferredCount++;
        }
    }
    
    // Prepare chart data
    $chartData = [];
    foreach ($monthlyData as $month => $amount) {
        $chartData[] = [
            'month' => $month,
            'amount' => $amount
        ];
    }
    
    echo json_encode([
        "status" => "success",
        "data" => [
            "payments" => $payments,
            "summary" => [
                "totalIncomeYTD" => $totalIncomeYTD,
                "incomeThisMonth" => $incomeThisMonth,
                "pendingCount" => $pendingCount,
                "deferredCount" => $deferredCount
            ],
            "chartData" => $chartData
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching financial data: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error fetching financial data: " . $e->getMessage()]);
}
?>
