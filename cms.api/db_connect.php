<?php
// Database credentials

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cmsdb";

// Create and check connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
?>
