<?php
// Database credentials

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cmsdb";

// Create and check connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
