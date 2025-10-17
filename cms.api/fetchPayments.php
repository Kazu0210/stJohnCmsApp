

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

$sql = "SELECT p.paymentId, p.reservationId, p.userId, p.paymentMethodId, p.month, p.amount, p.datePaid, p.reference, p.document, p.status, p.createdAt, p.updatedAt,
              u.displayName AS clientName,
              r.area, r.block, r.row, r.lotNumber
        FROM payments p
        LEFT JOIN users u ON p.userId = u.userId
        LEFT JOIN reservations r ON p.reservationId = r.reservationId
        ORDER BY p.createdAt DESC";

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
        'clientName' => $row['clientName'] ?? '',
        'area' => $row['area'] ?? '',
        'block' => $row['block'] ?? '',
        'row' => $row['row'] ?? '',
        'lotNumber' => $row['lotNumber'] ?? '',
        'month' => $row['month'],
        'amount' => $row['amount'],
        'datePaid' => $row['datePaid'],
        'reference' => $row['reference'],
        'document' => $row['document'],
        'status' => $row['status'],
        'createdAt' => $row['createdAt'],
        'updatedAt' => $row['updatedAt'],
        'paymentMethodId' => $row['paymentMethodId']
    ];
}

echo json_encode(['success' => true, 'data' => $payments]);
$conn->close();
