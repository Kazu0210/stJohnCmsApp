<?php
// Handle payment POST securely
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
	exit;
}

require_once __DIR__ . '/db_connect.php';

// Basic input validation and sanitization
 $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$method = isset($_POST['method']) ? trim($_POST['method']) : '';
$reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
// Optionally handle file upload (receipt)
$receipt_path = '';
if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
	$upload_dir = __DIR__ . '/uploads/payments/';
	if (!is_dir($upload_dir)) {
		mkdir($upload_dir, 0777, true);
	}
	$filename = uniqid('receipt_', true) . '_' . basename($_FILES['receipt']['name']);
	$target_file = $upload_dir . $filename;
	if (move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
		$receipt_path = 'uploads/payments/' . $filename;
	}
}

if ($amount <= 0 || !$method || !$reservation_id || !$user_id) {
	echo json_encode(['status' => 'error', 'message' => 'Missing or invalid payment data']);
	exit;
}

// Insert payment record securely
$stmt = $conn->prepare("INSERT INTO payments (user_id, reservation_id, amount, method, receipt_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
if (!$stmt) {
	echo json_encode(['status' => 'error', 'message' => 'Database error: prepare failed']);
	exit;
}

$stmt->bind_param('iidss', $user_id, $reservation_id, $amount, $method, $receipt_path);
if ($stmt->execute()) {
	echo json_encode(['status' => 'success', 'message' => 'Payment recorded successfully']);
} else {
	echo json_encode(['status' => 'error', 'message' => 'Failed to record payment']);
}
$stmt->close();
$conn->close();
