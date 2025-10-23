<?php
// resetPassword.php - Dedicated Endpoint for Password Update
include("db_connect.php"); 

// Set proper headers to handle JSON input and responses
header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get the raw POST data (assuming JSON format from fetch)
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// Input validation and sanitization
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? ''; 

// Validate required fields
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email and password are required.']);
    exit;
}

// 1. Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
    exit;
}

// 2. Hash the password securely
// NOTE: Client-side JS already checked for strength, but always validate server-side.
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 3. Check if the user exists
$checkUserStmt = $conn->prepare("SELECT userId FROM user WHERE email = ?");
if (!$checkUserStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error preparing check: ' . htmlspecialchars($conn->error)]);
    exit;
}
$checkUserStmt->bind_param("s", $email);
$checkUserStmt->execute();
$result = $checkUserStmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found.']);
    $checkUserStmt->close();
    exit;
}
$checkUserStmt->close();

// 4. Update the user's password
$updateStmt = $conn->prepare("UPDATE user SET password = ?, updatedAt = NOW() WHERE email = ?");

if (!$updateStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error preparing update: ' . htmlspecialchars($conn->error)]);
    exit;
}

$updateStmt->bind_param("ss", $hashedPassword, $email);

if ($updateStmt->execute()) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Password reset successful.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Password update failed: ' . htmlspecialchars($updateStmt->error)]);
}

$updateStmt->close();
$conn->close();
?>