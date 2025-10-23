<?php
session_start();
header('Content-Type: application/json');
require 'db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!isset($data['reservationID'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameter: reservationID.']);
    exit;
}

$reservationID = (int)$data['reservationID'];

$clientName      = $data['clientName'] ?? null;
$address         = $data['address'] ?? null;
$contactNumber   = $data['contactNumber'] ?? null;
$reservationDate = $data['reservationDate'] ?? null;
$area            = $data['area'] ?? null;
$block           = $data['block'] ?? null;
$rowNumber       = $data['rowNumber'] ?? null;
$lotNumber       = $data['lotNumber'] ?? null;
$lotTypeID       = $data['lotTypeID'] ?? null;
$burialDepth     = $data['burialDepth'] ?? null;
$status          = $data['status'] ?? null; 
$clientValidId   = $data['clientValidId'] ?? null; 

$paramTypes = 'sssssssssssssssi';
$bindParams = [
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
    $status, $status, 
    $clientValidId, $clientValidId, 
    $reservationID
];

$sql = "
UPDATE reservations
SET clientName = ?,
    address = ?,
    contactNumber = ?,
    reservationDate = ?,
    area = ?,
    block = ?,
    rowNumber = ?,
    lotNumber = ?,
    lotTypeID = ?,
    burialDepth = ?,
    status = IF(? IS NULL, status, ?),
    clientValidId = IF(? IS NULL, clientValidId, ?),
    updatedAt = NOW()
WHERE reservationID = ?
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
    exit;
}

$stmt->bind_param($paramTypes, ...$bindParams);
$stmt->execute();

if ($conn->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Reservation record updated successfully.']);
} else {
    echo json_encode(['status' => 'success', 'message' => 'Update successful (no new changes).']);
}

$stmt->close();
$conn->close();
