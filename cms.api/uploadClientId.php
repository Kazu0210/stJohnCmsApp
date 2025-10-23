<?php
// uploadClientId.php
// Handles file upload for clientValidId and updates the reservation record.

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require 'db_connect.php'; 

// --- 1. Configuration and Validation ---

// Target directory for uploads relative to this script's location (check if this path is correct!)
$uploadDir = 'reservations/uploads/client_ids/'; 
$maxFileSize = 5 * 1024 * 1024; // 5MB limit

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_POST['reservationID']) || !isset($_FILES['clientIdFile'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing reservation ID or file.']);
    exit;
}

$reservationID = (int)$_POST['reservationID'];
$file = $_FILES['clientIdFile'];

// Check file errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_msg = 'File upload failed.';
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $error_msg = 'The uploaded file exceeds the PHP limit.';
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $error_msg]);
    exit;
}

if ($file['size'] > $maxFileSize) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File size exceeds 5MB limit.']);
    exit;
}

$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];

if (!in_array($fileExt, $allowedExt)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, PDF allowed.']);
    exit;
}

// --- 2. File Handling ---

// Generate unique filename
$newFileName = 'client_id_' . $reservationID . '_' . uniqid() . '.' . $fileExt;
$targetPath = $uploadDir . $newFileName;

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory. Check permissions.']);
        exit;
    }
}

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // --- 3. Database Update ---
    try {
        $sql = "UPDATE reservations SET clientValidId = ?, updatedAt = NOW() WHERE reservationID = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$newFileName, $reservationID])) {
            echo json_encode(['status' => 'success', 'message' => 'Client ID file uploaded and saved.', 'filename' => $newFileName]);
        } else {
            // Delete the file if database update fails
            unlink($targetPath); 
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'File uploaded but failed to update database: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Server error during database update: ' . $e->getMessage()]);
    }
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file. Check directory permissions or file path.']);
}
?>