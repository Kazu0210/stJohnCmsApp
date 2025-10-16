<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session first
session_start();

// Include DB connection
require 'db_connect.php';

// Set proper headers
header('Content-Type: application/json');

// Allow only POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Input validation and sanitization
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if (empty($email) || empty($password)) {
        echo json_encode([
            "status" => "error",
            "message" => "Please fill in all fields"
        ]);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid email format"
        ]);
        exit;
    }

    // Prepare statement to get user data including status
    $stmt = $conn->prepare("SELECT userId, firstName, lastName, email, password, role, status FROM user WHERE email = ?");
    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Database error"
        ]);
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Check account status
        if ($row['status'] !== 'Active') {
            echo json_encode([
                "status" => "error",
                "message" => "Account is not active. Please contact administrator."
            ]);
            $stmt->close();
            exit;
        }
        
        // Check if password is hashed (bcrypt hashes start with $2y$ and are 60 characters)
        $isHashed = (strlen($row['password']) == 60 && substr($row['password'], 0, 4) === '$2y$');
        
        // Verify password - handle both hashed and legacy plain text passwords
        $passwordMatch = false;
        if ($isHashed) {
            // Use password_verify for hashed passwords
            $passwordMatch = password_verify($password, $row['password']);
        } else {
            // Legacy support for plain text passwords (temporary - should be removed)
            $passwordMatch = ($password === $row['password']);
            
            // If plain text password matches, update it to hashed version
            if ($passwordMatch) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updatePasswordStmt = $conn->prepare("UPDATE user SET password = ?, updatedAt = NOW() WHERE userId = ?");
                if ($updatePasswordStmt) {
                    $updatePasswordStmt->bind_param("si", $hashedPassword, $row['userId']);
                    $updatePasswordStmt->execute();
                    $updatePasswordStmt->close();
                }
            }
        }
        
        if ($passwordMatch) {
            // Update last login time
            $updateLoginStmt = $conn->prepare("UPDATE user SET lastLogin = NOW(), updatedAt = NOW() WHERE userId = ?");
            if ($updateLoginStmt) {
                $updateLoginStmt->bind_param("i", $row['userId']);
                $updateLoginStmt->execute();
                $updateLoginStmt->close();
            }

            // Set session variables for authentication
            $_SESSION['user_id'] = $row['userId'];  // Primary authentication key
            $_SESSION['userId'] = $row['userId'];   // Alternative key for consistency
            $_SESSION['firstName'] = $row['firstName'];
            $_SESSION['lastName'] = $row['lastName'];
            $_SESSION['name'] = $row['firstName'] . " " . $row['lastName'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['status'] = $row['status'];
            $_SESSION['login_time'] = time();       // Track login time
            $_SESSION['is_authenticated'] = true;   // Authentication flag
            
            // Regenerate session ID for security
            session_regenerate_id(true);

            // Determine redirect URL based on role
            $redirectUrl = '';
            switch ($row['role']) {
                case 'Admin':
                    $redirectUrl = '../cmsApp/frontend/admin/adminDashboard/adminDashboard.php';
                    break;
                case 'Secretary':
                    $redirectUrl = '../cmsApp/frontend/secretary/secretary.php';
                    break;
                case 'Client':
                default:
                    $redirectUrl = '../cmsApp/frontend/client/clientDashboard/clientDashboard.php';
                    break;
            }

            echo json_encode([
                "status" => "success",
                "message" => "Login successful! Redirecting to dashboard...",
                "role" => $row['role'],
                "fullName" => $_SESSION['name'],
                "redirect" => $redirectUrl
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid email or password"
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid email or password"
        ]);
    }

    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ]);
}

$conn->close();
?>
