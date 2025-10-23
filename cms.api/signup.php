<?php
include("db_connect.php");

// Set proper headers
header('Content-Type: text/html; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Input validation and sanitization
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contactNumber = trim($_POST['contactNumber'] ?? '');
    $password = $_POST['password'] ?? '';
    $emergencyContactName = trim($_POST['emergencyContactName'] ?? '');
    $emergencyContactNumber = trim($_POST['emergencyContactNumber'] ?? '');
    $role = "Client"; // default role

    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($contactNumber) || 
        empty($password) || empty($emergencyContactName) || empty($emergencyContactNumber)) {
        http_response_code(400);
        echo '<div class="alert alert-danger" role="alert">All fields are required</div>';
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo '<div class="alert alert-danger" role="alert">Invalid email format</div>';
        exit;
    }

    // Validate password strength (minimum 8 characters)
    if (strlen($password) < 8) {
        http_response_code(400);
        echo '<div class="alert alert-danger" role="alert">Password must be at least 8 characters long</div>';
        exit;
    }

    // Hash the password securely
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists using prepared statement
    $checkEmailStmt = $conn->prepare("SELECT userId FROM user WHERE email = ?");
    if (!$checkEmailStmt) {
        http_response_code(500);
        echo '<div class="alert alert-danger" role="alert">Database error: ' . htmlspecialchars($conn->error) . '</div>';
        exit;
    }

    $checkEmailStmt->bind_param("s", $email);
    $checkEmailStmt->execute();
    $result = $checkEmailStmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(409);
        echo '<div class="alert alert-warning" role="alert">Email address already exists</div>';
        $checkEmailStmt->close();
        exit;
    }
    $checkEmailStmt->close();

    // Insert user with prepared statement to prevent SQL injection
    // Note: createdAt is auto-generated, status defaults to 'Active', updatedAt is NULL by default
    $insertStmt = $conn->prepare("INSERT INTO user (firstName, lastName, email, contactNumber, password, role, status, emergencyContactName, emergencyContactNumber) VALUES (?, ?, ?, ?, ?, ?, 'Active', ?, ?)");
    
    if (!$insertStmt) {
        http_response_code(500);
        echo '<div class="alert alert-danger" role="alert">Database error: ' . htmlspecialchars($conn->error) . '</div>';
        exit;
    }

    $insertStmt->bind_param("ssssssss", $firstName, $lastName, $email, $contactNumber, $hashedPassword, $role, $emergencyContactName, $emergencyContactNumber);

    if ($insertStmt->execute()) {
        http_response_code(201);
        echo '<div class="alert alert-success" role="alert">
                <strong>Success!</strong> Registration successful! You can now log in.
              </div>';
        echo '<script>
                setTimeout(function() {
                    window.location.href = "../cmsApp/frontend/auth/login/login.php";
                }, 3000);
              </script>';
    } else {
        http_response_code(500);
        echo '<div class="alert alert-danger" role="alert">Registration failed: ' . htmlspecialchars($insertStmt->error) . '</div>';
    }
    
    $insertStmt->close();
} else {
    http_response_code(405);
    echo '<div class="alert alert-danger" role="alert">Method not allowed</div>';
}

$conn->close();
?>