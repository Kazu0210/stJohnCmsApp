<?php
require_once 'db_connect.php'; // Adjust path if needed

function getTotalPayments($conn) {
    $sql = "SELECT SUM(amount) AS total_amount FROM payments";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['total_amount'];
    } else {
        return 0;
    }
}

function getOutstandingBalances($conn) {
    $sql = "SELECT SUM(total_amount - amount_paid) AS outstanding_balance FROM reservations WHERE status = 'Reserved'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['outstanding_balance'];
    } else {
        return 0;
    }
}

$total = getTotalPayments($conn);
$outstanding = getOutstandingBalances($conn);
echo json_encode([
    'total_amount' => $total,
    'outstanding_balance' => $outstanding
]);

$conn->close();
?>