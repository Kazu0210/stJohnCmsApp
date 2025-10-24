<?php
// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
require_once 'db_connect.php';

// Function to send JSON response
function sendResponse($success, $message, $data = null) {
    $response = array(
        'success' => $success,
        'message' => $message
    );
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit();
}

// Function to validate database connection
function validateConnection($conn) {
    if ($conn->connect_error) {
        sendResponse(false, "Database connection failed: " . $conn->connect_error);
    }
}

// Function to fetch all appointments
function fetchAllAppointments($conn) {
    // Order by start time (new column appointment_start_time). Keep compatibility with legacy 'time' if present.
    $sql = "SELECT * FROM appointments ORDER BY dateRequested DESC, appointment_start_time DESC";
    $result = $conn->query($sql);
    
    if ($result === false) {
        sendResponse(false, "Error executing query: " . $conn->error);
    }
    
    $appointments = array();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    return $appointments;
}

// Function to fetch appointments by client ID
function fetchAppointmentsByClientId($conn, $clientId) {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE clientId = ? ORDER BY dateRequested DESC, appointment_start_time DESC");
    
    if (!$stmt) {
        sendResponse(false, "Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($result === false) {
        sendResponse(false, "Error executing query: " . $stmt->error);
    }
    
    $appointments = array();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    $stmt->close();
    return $appointments;
}

// Function to fetch appointments by status ID
function fetchAppointmentsByStatusId($conn, $statusId) {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE statusId = ? ORDER BY dateRequested DESC, appointment_start_time DESC");
    
    if (!$stmt) {
        sendResponse(false, "Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $statusId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($result === false) {
        sendResponse(false, "Error executing query: " . $stmt->error);
    }
    
    $appointments = array();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    $stmt->close();
    return $appointments;
}

// Function to fetch appointments by status string
function fetchAppointmentsByStatus($conn, $status) {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE status = ? ORDER BY dateRequested DESC, appointment_start_time DESC");
    
    if (!$stmt) {
        sendResponse(false, "Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("s", $status);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($result === false) {
        sendResponse(false, "Error executing query: " . $stmt->error);
    }
    
    $appointments = array();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    $stmt->close();
    return $appointments;
}

// Main execution
try {
    // Validate database connection
    validateConnection($conn);
    
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get query parameters
        $clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : null;
        $statusId = isset($_GET['status_id']) ? intval($_GET['status_id']) : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        $appointments = array();
        
        // Fetch appointments based on parameters
        if ($clientId) {
            $appointments = fetchAppointmentsByClientId($conn, $clientId);
            $message = "Appointments fetched successfully for client ID: " . $clientId;
        } elseif ($statusId !== null) {
            $appointments = fetchAppointmentsByStatusId($conn, $statusId);
            $message = "Appointments fetched successfully for status ID: " . $statusId;
        } elseif ($status) {
            $appointments = fetchAppointmentsByStatus($conn, $status);
            $message = "Appointments fetched successfully for status: " . $status;
        } else {
            $appointments = fetchAllAppointments($conn);
            $message = "All appointments fetched successfully";
        }
        
        // Send successful response
        sendResponse(true, $message, $appointments);
        
    } else {
        // Method not allowed
        http_response_code(405);
        sendResponse(false, "Method not allowed. Only GET requests are supported.");
    }
    
} catch (Exception $e) {
    // Handle any unexpected errors
    sendResponse(false, "An error occurred: " . $e->getMessage());
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>
