<?php
// Database connection
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "cmsdb";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_SESSION['user_id'];
$user = null;

$stmt = $conn->prepare("SELECT * FROM user WHERE userId = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
}
$stmt->close();
