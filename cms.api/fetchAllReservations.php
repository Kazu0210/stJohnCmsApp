<?php
require_once "db_connect.php";
session_start();

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Check if user is admin (you may need to adjust this based on your user roles)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Fetch all reservations with user information for admin dashboard
$sql = "SELECT r.reservationId, r.area, r.block, r.lotNumber, r.userId, 
               r.created_at, u.username, u.email
        FROM reservations r
        LEFT JOIN users u ON r.userId = u.id
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $reservations[] = [
        'reservationId' => $row['reservationId'],
        'area' => $row['area'],
        'block' => $row['block'],
        'lotNumber' => $row['lotNumber'],
        'userId' => $row['userId'],
        'username' => $row['username'] ?? 'Unknown',
        'email' => $row['email'] ?? 'No email',
        'created_at' => $row['created_at']
    ];
}

echo json_encode($reservations);

$stmt->close();
$conn->close();
?>