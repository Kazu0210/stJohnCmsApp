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

    // Get only the required fields from the burial_request using the requestId
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
}