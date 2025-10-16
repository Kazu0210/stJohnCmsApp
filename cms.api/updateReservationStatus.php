<?php
header('Content-Type: application/json');
require 'db_connect.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$reservationID = isset($data['reservationID']) ? (int)$data['reservationID'] : 0;
$status = isset($data['status']) ? $data['status'] : null;

if ($reservationID <= 0 || $status === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing reservationID or status']);
    exit;
}

try {
    $sql = "UPDATE reservations SET status = ?, updatedAt = NOW() WHERE reservationID = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('si', $status, $reservationID);
    $stmt->execute();

    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    if ($affected > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Status updated']);
    } else {
        // If no rows affected, still return success but indicate no change
        echo json_encode(['status' => 'success', 'message' => 'No changes made (maybe already set)']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>
