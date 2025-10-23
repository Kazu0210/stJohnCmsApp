<?php

header('Content-Type: application/json');
require_once 'db_connect.php'; // Adjust path if needed
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$requestId = isset($_POST['requestId']) ? intval($_POST['requestId']) : 0;

if ($requestId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
    exit;
}

// Check if request exists and is pending

$stmt = $conn->prepare("SELECT status FROM burial_request WHERE requestId = ?");
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}
$stmt->bind_param("i", $requestId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Burial request not found.']);
    exit;
}

$row = $result->fetch_assoc();
if ($row['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Only pending requests can be deleted.']);
    exit;
}

// Delete the request

$delStmt = $conn->prepare("DELETE FROM burial_request WHERE requestId = ?");
if ($delStmt === false) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}
$delStmt->bind_param("i", $requestId);
if ($delStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $delStmt->error]);
}
?>