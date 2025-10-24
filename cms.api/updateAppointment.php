<?php
// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

// Function to update appointment status
function updateAppointmentStatus($conn, $appointmentId, $status) {
    // Validate status value
    $allowedStatuses = ['', 'scheduled', 'confirmed', 'completed', 'cancelled'];
    if (!in_array($status, $allowedStatuses)) {
        sendResponse(false, "Invalid status value");
    }
    
    $stmt = $conn->prepare("UPDATE appointments SET status = ?, updatedAt = CURRENT_TIMESTAMP WHERE appointmentId = ?");
    
    if (!$stmt) {
        sendResponse(false, "Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("si", $status, $appointmentId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            sendResponse(false, "No appointment found with ID: " . $appointmentId);
        }
    } else {
        $stmt->close();
        sendResponse(false, "Error updating appointment: " . $stmt->error);
    }
}

// Function to update appointment date and time (for reschedule)
function updateAppointmentDateTime($conn, $appointmentId, $date, $startTime, $endTime = null) {
    // Update both appointment_start_time and appointment_end_time, and keep legacy `time` in sync (start)
    $stmt = $conn->prepare("UPDATE appointments SET dateRequested = ?, appointment_start_time = ?, appointment_end_time = ?, `time` = ?, status = 'scheduled', updatedAt = CURRENT_TIMESTAMP WHERE appointmentId = ?");
    
    if (!$stmt) {
        sendResponse(false, "Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("ssssi", $date, $startTime, $endTime, $startTime, $appointmentId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            sendResponse(false, "No appointment found with ID: " . $appointmentId);
        }
    } else {
        $stmt->close();
        sendResponse(false, "Error updating appointment: " . $stmt->error);
    }
}

// Main execution
try {
    // Validate database connection
    validateConnection($conn);
    
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        // Get POST data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendResponse(false, "Invalid JSON data");
        }
        
        $appointmentId = isset($input['appointmentId']) ? intval($input['appointmentId']) : null;
        $action = isset($input['action']) ? $input['action'] : null;
        
        if (!$appointmentId || !$action) {
            sendResponse(false, "Missing required fields: appointmentId and action");
        }
        
        // Handle different actions
        switch ($action) {
            case 'confirm':
                updateAppointmentStatus($conn, $appointmentId, 'confirmed');
                sendResponse(true, "Appointment confirmed successfully");
                break;
                
            case 'cancel':
                updateAppointmentStatus($conn, $appointmentId, 'cancelled');
                sendResponse(true, "Appointment cancelled successfully");
                break;
                
            case 'complete':
                updateAppointmentStatus($conn, $appointmentId, 'completed');
                sendResponse(true, "Appointment marked as completed");
                break;
                
            case 'reschedule':
                $newDate = isset($input['newDate']) ? $input['newDate'] : null;
                $newStart = isset($input['newStart']) ? $input['newStart'] : null;
                $newEnd = isset($input['newEnd']) ? $input['newEnd'] : null;
                
                if (!$newDate || !$newStart || !$newEnd) {
                    sendResponse(false, "Missing required fields for reschedule: newDate, newStart and newEnd");
                }
                
                updateAppointmentDateTime($conn, $appointmentId, $newDate, $newStart, $newEnd);
                sendResponse(true, "Appointment rescheduled successfully");
                break;
                
            default:
                sendResponse(false, "Invalid action: " . $action);
        }
        
    } else {
        // Method not allowed
        http_response_code(405);
        sendResponse(false, "Method not allowed. Only POST requests are supported.");
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