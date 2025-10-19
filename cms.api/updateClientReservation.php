<?php

include 'db_connect.php';

if (isset($_GET['requestId'])) {
    // Allow GET request for testing via URL
    $requestId = $_GET['requestId'];
    $query = "SELECT deceasedName, burialDate, deceasedValidId, deathCertificate, burialPermit FROM burial_request WHERE requestId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $requestId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No record found.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch data.']);
    }
    exit;
}

if (isset($_POST['reservationId']) && isset($_POST['requestId'])) {
    $reservationId = $_POST['reservationId'];
    $requestId = $_POST['requestId'];

    // Try to get the required fields from the burial_request using the requestId
    $query = "SELECT deceasedName, burialDate, deceasedValidId, deathCertificate, burialPermit FROM burial_request WHERE requestId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $requestId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            // If not found in burial_request, try to get from reservations table
            $query2 = "SELECT * FROM reservations WHERE reservationId = ?";
            $stmt2 = $conn->prepare($query2);
            $stmt2->bind_param("i", $reservationId);
            if ($stmt2->execute()) {
                $result2 = $stmt2->get_result();
                if ($result2->num_rows > 0) {
                    $data2 = $result2->fetch_assoc();
                    echo json_encode(['status' => 'success', 'data' => $data2]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'No record found in burial_request or reservations.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to fetch data from reservations.']);
            }
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch data from burial_request.']);
    }
}