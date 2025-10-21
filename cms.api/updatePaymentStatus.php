<?php
// updatePaymentStatus.php
require_once 'db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$paymentId = isset($_POST['paymentId']) ? intval($_POST['paymentId']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (!$paymentId || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing paymentId or status']);
    exit;
}

$sql = "UPDATE payments SET status = ?, updatedAt = NOW() WHERE paymentId = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed', 'error' => $conn->error]);
    exit;
}
$stmt->bind_param('si', $status, $paymentId);

if ($stmt->execute()) {
    // If status is Confirmed, update amount_paid and amount_due in reservations
    if (strtolower($status) === 'confirmed') {
        // Get reservationId and amount from this payment
        $sql2 = "SELECT reservationId, amount FROM payments WHERE paymentId = ? LIMIT 1";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param('i', $paymentId);
        $stmt2->execute();
        $stmt2->bind_result($reservationId, $amount);
        if ($stmt2->fetch() && $reservationId) {
            $stmt2->close();
            // Update amount_paid: add all confirmed payments for this reservation
            $sql3 = "SELECT SUM(amount) FROM payments WHERE reservationId = ? AND status = 'Confirmed'";
            $stmt3 = $conn->prepare($sql3);
            $stmt3->bind_param('i', $reservationId);
            $stmt3->execute();
            $stmt3->bind_result($totalPaid);
            $stmt3->fetch();
            $stmt3->close();
            // Get amount_due from reservations
            $sql4 = "SELECT amount_due FROM reservations WHERE reservationId = ?";
            $stmt4 = $conn->prepare($sql4);
            $stmt4->bind_param('i', $reservationId);
            $stmt4->execute();
            $stmt4->bind_result($amountDue);
            $stmt4->fetch();
            $stmt4->close();
            // Calculate new amount_due (if amount_due exists)
            $newAmountDue = ($amountDue !== null) ? max(0, $amountDue - $totalPaid) : 0;
            // Update reservation
            $sql5 = "UPDATE reservations SET amount_paid = ?, amount_due = ? WHERE reservationId = ?";
            $stmt5 = $conn->prepare($sql5);
            $stmt5->bind_param('ddi', $totalPaid, $newAmountDue, $reservationId);
            $stmt5->execute();
            $stmt5->close();
        } else {
            $stmt2->close();
        }
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed', 'error' => $stmt->error]);
}
$stmt->close();
$conn->close();
