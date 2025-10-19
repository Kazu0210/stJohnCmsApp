<?php

include "db_connect.php";

if (isset($_GET['reservationId'])) {
    $reservationId = $_GET['reservationId'];

    // Get all fields from the reservations table using the reservationId
    $query = "SELECT * FROM reservations WHERE reservationId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $reservationId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            // Store all fields from reservations into variables
            foreach ($data as $key => $value) {
                $$key = $value;
            }
            // Example: you can now use $reservationId, $userId, $lotId, $deceasedName, etc.
            echo json_encode(['status' => 'success', 'message' => 'Reservation data retrieved successfully.', 'data' => $data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No record found in reservations.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch data from burial_request.']);
    }
}