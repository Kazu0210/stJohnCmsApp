<?php
// fetchUserReservationsApi.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'db_connect.php';

$userId = $_SESSION['user_id'];

$sql = "SELECT * FROM reservations WHERE userId = ? AND amount_paid > 0";
$stmt = $conn->prepare($sql);
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
