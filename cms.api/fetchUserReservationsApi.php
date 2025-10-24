<?php
// fetchUserReservationsApi.php
// Returns reservations for the logged-in user (or a provided userId)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

session_start();

// Determine userId: prefer POST/GET, fallback to session (user_id or client_id)
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
	echo json_encode(['reservations' => []]);
	exit;
}

// Validate integer
if (!ctype_digit(strval($userId))) {
	echo json_encode(['reservations' => []]);
	exit;
}

$userId = (int)$userId;

require_once 'db_connect.php';

$response = ['reservations' => []];

// Select columns that match your provided reservations table structure
$sql = "SELECT 
			r.reservationId,
			r.userId,
			r.lotId,
			r.clientName,
			r.address,
			r.contactNumber,
			r.clientValidId,
			r.deceasedName,
			r.burialDate,
			r.deathCertificate,
			r.deceasedValidId,
			r.burialPermit,
			r.reservationDate,
			r.area,
			r.block,
			r.rowNumber,
			r.lotNumber,
			r.burialDepth,
			r.notes,
			r.status,
			r.createdAt,
			r.updatedAt,
			r.lotTypeId,
			COALESCE(lt.typeName, '') AS lotTypeName,
			COALESCE(lt.price, 0) AS lotPrice,
			COALESCE(lt.monthlyPayment, 0) AS lotMonthlyPayment
		FROM reservations r
		LEFT JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
		WHERE r.userId = ?
		ORDER BY r.reservationId DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
	echo json_encode($response);
	$conn->close();
	exit;
}

$stmt->bind_param('i', $userId);
if (!$stmt->execute()) {
	echo json_encode($response);
	$stmt->close();
	$conn->close();
	exit;
}

$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
	// Normalize types where appropriate
	$row['reservationId'] = isset($row['reservationId']) ? (int)$row['reservationId'] : null;
	$row['userId'] = isset($row['userId']) ? (int)$row['userId'] : null;
	$row['lotId'] = isset($row['lotId']) && $row['lotId'] !== null ? (int)$row['lotId'] : null;
	$row['lotTypeId'] = isset($row['lotTypeId']) ? (int)$row['lotTypeId'] : null;
	$row['lotPrice'] = isset($row['lotPrice']) ? (float)$row['lotPrice'] : 0.0;
	$row['lotMonthlyPayment'] = isset($row['lotMonthlyPayment']) ? (float)$row['lotMonthlyPayment'] : 0.0;

	// Ensure string/date fields exist
	$row['clientName'] = $row['clientName'] ?? '';
	$row['address'] = $row['address'] ?? '';
	$row['contactNumber'] = $row['contactNumber'] ?? '';
	$row['clientValidId'] = $row['clientValidId'] ?? '';
	$row['deceasedName'] = $row['deceasedName'] ?? null;
	$row['burialDate'] = $row['burialDate'] ?? null;
	$row['reservationDate'] = $row['reservationDate'] ?? null;
	$row['area'] = $row['area'] ?? '';
	$row['block'] = $row['block'] ?? '';
	$row['rowNumber'] = $row['rowNumber'] ?? '';
	$row['lotNumber'] = $row['lotNumber'] ?? '';
	$row['burialDepth'] = $row['burialDepth'] ?? '';
	$row['notes'] = $row['notes'] ?? '';
	$row['status'] = $row['status'] ?? '';
	$row['createdAt'] = $row['createdAt'] ?? null;
	$row['updatedAt'] = $row['updatedAt'] ?? null;

	$response['reservations'][] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($response);

?>

