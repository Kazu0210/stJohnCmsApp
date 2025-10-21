<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

$response = array();

try {
    $sql = "SELECT * FROM payments";
    $result = $conn->query($sql);

    $payments = [];
    $totalReceived = 0;
    $outstandingBalance = 0;
    $upcomingDue = 0;

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
            // Payments Received: sum of confirmed payments
            if ($row['status'] === 'Confirmed') {
                $totalReceived += floatval($row['amount']);
            }
            // Outstanding Balances: sum of pending payments
            if ($row['status'] === 'Pending') {
                $outstandingBalance += floatval($row['amount']);
            }
            // Upcoming Due: count of pending payments with a due date in the future
            if ($row['status'] === 'Pending' && isset($row['datePaid']) && strtotime($row['datePaid']) > time()) {
                $upcomingDue++;
            }
        }
        $response['success'] = true;
        $response['data'] = $payments;
        $response['summary'] = [
            'paymentsReceived' => $totalReceived,
            'outstandingBalances' => $outstandingBalance,
            'upcomingDue' => $upcomingDue
        ];
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);