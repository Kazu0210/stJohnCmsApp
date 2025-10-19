<?php

include "db_connect.php";

header('Content-Type: application/json');

if (isset($_GET['reservationId'])) {
    $reservationId = $_GET['reservationId'];

    // Prepare SQL to get lotId from reservations table
    $sql = "SELECT lotId FROM reservations WHERE reservationId = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'status' => 'success',
                'data' => $row
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No lot found for this reservationId.'
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to prepare statement.'
        ]);
    }
    exit;
}