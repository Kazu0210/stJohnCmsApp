<?php
// fetchAllBurialRequests.php - For admin/secretary to fetch all burial requests
header('Content-Type: application/json');
session_start();

require_once 'db_connect.php';

// Optionally, check for admin/secretary role here if needed

$sql = "SELECT br.*, CONCAT(u.firstName, ' ', u.lastName) AS userName FROM burial_request br LEFT JOIN user u ON br.userId = u.userId ORDER BY br.createdAt DESC";
$result = $conn->query($sql);

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

$conn->close();

echo json_encode(['requests' => $requests]);
?>
