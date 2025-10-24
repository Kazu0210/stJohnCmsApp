<?php
// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

// Include database connection
require_once 'db_connect.php';

// Function to send JSON response
function sendResponse($success, $message, $data = null) {
	$response = array(
		'success' => $success,
		'message' => $message
	);
	if ($data !== null) {
		$response['data'] = $data;
	}
	echo json_encode($response);
	exit();
}

// Validate database connection
if ($conn->connect_error) {
	sendResponse(false, "Database connection failed: " . $conn->connect_error);
}

// Fetch all appointments
$sql = "SELECT appointmentId, clientId, clientName, clientAddress, clientContactNumber, dateRequested, time, start_time, end_time, purpose, statusId, status, approvedById, createdAt, updatedAt FROM appointments ORDER BY dateRequested DESC, start_time DESC, time DESC";
$result = $conn->query($sql);
if ($result === false) {
	sendResponse(false, "Error executing query: " . $conn->error);
}

$appointments = array();
while ($row = $result->fetch_assoc()) {
	$appointments[] = $row;
}

sendResponse(true, "All appointments fetched successfully", $appointments);

// Close connection
$conn->close();

