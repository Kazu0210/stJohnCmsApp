<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = array();

if (!isset($_GET['userId'])) {
	echo json_encode(['status' => 'error', 'message' => 'Missing userId parameter.']);
	exit();
}

$userId = intval($_GET['userId']);


$sql = "SELECT * FROM reservations WHERE userId = ? AND (status = 'Reserved' OR status = 'Completed')";
$stmt = $conn->prepare($sql);
if ($stmt) {
	$stmt->bind_param('i', $userId);
	$stmt->execute();
	$result = $stmt->get_result();
	$reservations = [];
	while ($row = $result->fetch_assoc()) {
		$reservations[] = $row;
	}
	$response['status'] = 'success';
	$response['data'] = $reservations;
	$stmt->close();
} else {
	$response['status'] = 'error';
	$response['message'] = 'Query preparation failed.';
}

$conn->close();
echo json_encode($response);
