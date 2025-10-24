<?php
// submitAppointment.php: Store appointment data to the appointments table
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once(__DIR__ . '/../../../../cms.api/db_connect.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function sendResponse($success, $message, $extra = null) {
    $resp = ['success' => $success, 'message' => $message];
    if ($extra !== null) $resp['extra'] = $extra;
    echo json_encode($resp);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    sendResponse(false, 'No data received.');
}

// Validate required fields
$required = ['user_name', 'user_email', 'user_address', 'user_phone', 'appointment_date', 'appointment_start_time', 'appointment_end_time', 'appointment_purpose'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        sendResponse(false, "Missing required field: $field");
    }
}

$stmt = $conn->prepare("INSERT INTO appointments (clientName, clientAddress, clientContactNumber, dateRequested, start_time, end_time, purpose, statusId, status, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'pending', NOW())");
if (!$stmt) {
    sendResponse(false, 'Database error: ' . $conn->error);
}
$stmt->bind_param(
    'sssssss',
    $data['user_name'],
    $data['user_address'],
    $data['user_phone'],
    $data['appointment_date'],
    $data['appointment_start_time'],
    $data['appointment_end_time'],
    $data['appointment_purpose']
);

if ($stmt->execute()) {
    sendResponse(true, 'Appointment submitted successfully.');
} else {
    sendResponse(false, 'Failed to submit appointment: ' . $stmt->error);
}
$stmt->close();
$conn->close();
