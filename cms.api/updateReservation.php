<?php
// Enable error reporting for debugging (you can remove this in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include your database connection
include 'db_connect.php'; // Make sure this file defines $conn (mysqli connection)

// Validate if all required POST fields exist
if (
    isset($_POST['reservationID']) &&
    isset($_POST['clientName']) &&
    isset($_POST['address']) &&
    isset($_POST['contactNumber']) &&
    isset($_POST['reservationDate']) &&
    isset($_POST['area']) &&
    isset($_POST['block']) &&
    isset($_POST['rowNumber']) &&
    isset($_POST['lotNumber']) &&
    isset($_POST['lotTypeID']) &&
    isset($_POST['burialDepth'])
) {
    // Get data from POST
    $reservationID   = intval($_POST['reservationID']);
    $clientName      = trim($_POST['clientName']);
    $address         = trim($_POST['address']);
    $contactNumber   = trim($_POST['contactNumber']);
    $reservationDate = trim($_POST['reservationDate']);
    $area            = trim($_POST['area']);
    $block           = trim($_POST['block']);
    $rowNumber       = trim($_POST['rowNumber']);
    $lotNumber       = trim($_POST['lotNumber']);
    $lotTypeID       = intval($_POST['lotTypeID']);
    $burialDepth     = trim($_POST['burialDepth']);
    $status          = isset($_POST['status']) ? $_POST['status'] : null;

    // Prepare SQL update statement (using prepared statements for safety)
    $stmt = $conn->prepare("
        UPDATE reservations 
        SET clientName = ?, 
            address = ?, 
            contactNumber = ?, 
            reservationDate = ?, 
            area = ?, 
            block = ?, 
            rowNumber = ?, 
            lotNumber = ?, 
            lotTypeId = ?, 
            burialDepth = ?, 
            status = ?
        WHERE reservationId = ?
    ");

    if ($stmt === false) {
        die(json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]));
    }

    $stmt->bind_param(
        "ssssssssissi",
        $clientName,
        $address,
        $contactNumber,
        $reservationDate,
        $area,
        $block,
        $rowNumber,
        $lotNumber,
        $lotTypeID,
        $burialDepth,
        $status,
        $reservationID
    );

    // Execute and check if successful
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reservation updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating reservation: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Missing required POST data']);
}

$conn->close();
?>
