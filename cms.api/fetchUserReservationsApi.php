<?php
// fetchUserReservationsApi.php
header('Content-Type: application/json');
// Hide non-fatal PHP warnings to avoid corrupting JSON output
ini_set('display_errors', 0);
session_start();

// Accept either 'user_id' (admin/regular) or 'client_id' (client session)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['client_id'])) {
    // Return an empty reservations array (frontend will show "No reservations found")
    echo json_encode(['reservations' => []]);
    exit();
}

require_once 'db_connect.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['client_id'];

$sql = "SELECT * FROM reservations WHERE userId = ? AND amount_paid > 0";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Return empty list on SQL prepare failure with helpful message
    echo json_encode(['reservations' => [], 'error' => 'DB prepare failed']);
    $conn->close();
    exit();
}

$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['reservations' => $reservations]);
?>
