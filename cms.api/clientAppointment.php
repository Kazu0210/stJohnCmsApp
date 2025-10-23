<?php
// clientAppointment.php - FINAL VERSION (clientId REMOVED)

// 🛑 TEMPORARY FIX: Suppress error output so it doesn't break JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Ensure your db_connect.php is in the correct path relative to this file
require_once "db_connect.php"; 
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// =========================================================================
// POST: Save New Appointment
// =========================================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CRUCIAL: Read the raw JSON input stream
    $data = json_decode(file_get_contents("php://input"), true); 

    // Extract data using the keys sent from login.js
    $clientName = $data["user_name"] ?? "";
    $clientEmail = $data["user_email"] ?? "";
    $clientAddress = $data["user_address"] ?? ""; 
    $clientContactNumber = $data["user_phone"] ?? "";
    $dateRequested = $data["appointment_date"] ?? "";
    $time = $data["appointment_time"] ?? "";
    $purpose = $data["appointment_purpose"] ?? "";

    // Basic Server-side validation
    if (!$clientName || !$clientEmail || !$clientAddress || !$clientContactNumber || !$dateRequested || !$time || !$purpose) {
        http_response_code(400); 
        echo json_encode(["status" => "error", "message" => "Missing required fields in API payload."]);
        exit;
    }

    // Use prepared statement to insert data securely
    $stmt = $conn->prepare("
        INSERT INTO appointments (
            /* ❌ clientId column removed from list */
            clientName, clientEmail, clientAddress, clientContactNumber, 
            dateRequested, time, purpose, status 
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, 'scheduled'
        )
    ");

    // 🛑 FIX: Bind parameters: Only 7 strings ('s' x 7) are now required.
    $stmt->bind_param("sssssss", 
        $clientName, 
        $clientEmail, 
        $clientAddress,
        $clientContactNumber, 
        $dateRequested, 
        $time, 
        $purpose
    );

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Appointment request submitted successfully."]);
    } else {
        http_response_code(500);
        // Return SQL error detail for debugging
        echo json_encode(["status" => "error", "message" => "Database INSERT failed: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// =========================================================================
// GET: Fetch All Appointments (This block remains correct)
// =========================================================================

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if ($conn->connect_error) {
        http_response_code(503); 
        echo json_encode(["status" => "error", "message" => "Database connection failed! Check db_connect.php."]);
        exit;
    }
    
    $result = $conn->query("SELECT 
        appointmentId AS id, 
        clientName AS client, 
        dateRequested AS date, 
        time, 
        purpose AS notes,
        status
        FROM appointments");
        
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    } else {
        http_response_code(500); 
        echo json_encode(["status" => "error", "message" => "Query failed: " . $conn->error]);
        $conn->close();
        exit;
    }
    
    echo json_encode($rows);
    $conn->close();
}
?>