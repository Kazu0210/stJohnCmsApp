<?php
// TEMPORARY: Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';
header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}


$sql = "SELECT paymentId, reservationId, userId, paymentMethodId, month, amount, datePaid, reference, document, status, createdAt, updatedAt
    FROM payments
    ORDER BY createdAt DESC";

$result = $conn->query($sql);
if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Query error', 'error' => $conn->error, 'sql' => $sql]);
    $conn->close();
    exit;
}

$payments = [];

while ($row = $result->fetch_assoc()) {
    $payments[] = [
        'paymentId' => $row['paymentId'],
        'reservationId' => $row['reservationId'],
        'userId' => $row['userId'],
        'paymentMethodId' => $row['paymentMethodId'],
        'month' => $row['month'],
        'amount' => $row['amount'],
        'datePaid' => $row['datePaid'],
        'reference' => $row['reference'],
        'document' => $row['document'],
        'status' => $row['status'],
        'createdAt' => $row['createdAt'],
        'updatedAt' => $row['updatedAt']
    ];
}

echo json_encode(['success' => true, 'data' => $payments]);
$conn->close();
