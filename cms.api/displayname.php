<?php
// Start session at the very top
session_start();
header('Content-Type: application/json');

// Include DB connection
require 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // ✅ correct table name: user (not users)
    $stmt = $conn->prepare("SELECT firstName, lastName, role FROM user WHERE userId = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "status" => "success",
            "fullName" => $row['firstName'] . " " . $row['lastName'],
            "role"    => $row['role']
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "User not found in database"
        ]);
    }

    $stmt->close();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "No user logged in"
    ]);
}

$conn->close();