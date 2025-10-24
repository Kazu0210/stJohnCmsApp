<?php
// fetchClientReservedLots.php
// Returns reserved lots for a given userId (accepts GET or POST 'userId')
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Start session so we can fall back to logged in user if userId not provided
session_start();

// Read userId from POST or GET; if missing, use session user_id or client_id
$userId = null;
if (isset($_POST['userId'])) {
	$userId = $_POST['userId'];
} elseif (isset($_GET['userId'])) {
	$userId = $_GET['userId'];
} elseif (isset($_SESSION['user_id'])) {
	$userId = $_SESSION['user_id'];
} elseif (isset($_SESSION['client_id'])) {
	$userId = $_SESSION['client_id'];
}

if ($userId === null || $userId === '') {
	echo json_encode(['status' => 'error', 'message' => 'Missing userId parameter and no user session found', 'data' => []]);
	exit;
}

// Validate integer
if (!ctype_digit(strval($userId))) {
	echo json_encode(['status' => 'error', 'message' => 'Invalid userId parameter', 'data' => []]);
	exit;
}

$userId = (int)$userId;

// Connect to DB using existing db_connect.php (mysqli)
require_once 'db_connect.php';

$response = ['status' => 'error', 'message' => '', 'data' => []];

// Build SQL to fetch reservation details with lot type info
$sql = "SELECT 
			r.reservationId,
			r.clientName,
			r.area,
			r.block,
			r.rowNumber,
			r.lotNumber,
			r.lotTypeId,
			COALESCE(lt.typeName, '') AS lot_type_name,
			COALESCE(lt.price, 0) AS price,
			COALESCE(lt.monthlyPayment, 0) AS monthlyPayment,
			r.status,
			r.reservationDate
		FROM reservations r
		LEFT JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
		WHERE r.userId = ?
		ORDER BY r.clientName, lt.typeName, r.area, r.block, r.rowNumber, r.lotNumber";

$stmt = $conn->prepare($sql);
if (!$stmt) {
	$response['message'] = 'DB prepare failed: ' . $conn->error;
	echo json_encode($response);
	$conn->close();
	exit;
}

$stmt->bind_param('i', $userId);
if (!$stmt->execute()) {
	$response['message'] = 'DB execute failed: ' . $stmt->error;
	echo json_encode($response);
	$stmt->close();
	$conn->close();
	exit;
}

$result = $stmt->get_result();
$lots = [];
while ($row = $result->fetch_assoc()) {
	// Normalize numeric fields
	$row['price'] = isset($row['price']) ? (float)$row['price'] : 0.0;
	$row['monthlyPayment'] = isset($row['monthlyPayment']) ? (float)$row['monthlyPayment'] : 0.0;

	// Safe defaults
	$row['lot_type_name'] = $row['lot_type_name'] ?? '';
	$row['reservationDate'] = $row['reservationDate'] ?? null;

	$lots[] = $row;
}

$stmt->close();
$conn->close();

$response['status'] = 'success';
$response['data'] = $lots;
echo json_encode($response, JSON_PRETTY_PRINT);

// Shutdown handler to output JSON on fatal error
register_shutdown_function(function () {
	$err = error_get_last();
	if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
		if (!headers_sent()) header('Content-Type: application/json');
		echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $err['message']]);
	}
});

?>
