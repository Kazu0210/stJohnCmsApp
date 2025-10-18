<?php
// fetchClientBurialRequests.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'db_connect.php';

$userId = $_SESSION['user_id'];

$sql = "SELECT * FROM burial_request WHERE userId = ? ORDER BY createdAt DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode(['requests' => $requests]);
?>
