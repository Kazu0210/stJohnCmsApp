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
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed', 'error' => $stmt->error]);
}
$stmt->close();
$conn->close();
