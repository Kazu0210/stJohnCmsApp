<?php

// confirmReservation.php
// Marks a reservation as 'Reserved' and updates the corresponding lot status to 'Reserved'.

header('Content-Type: application/json');
require_once 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!isset($data['reservationID'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing reservationID']);
    exit;
}

$reservationID = (int)$data['reservationID'];
$lotId = isset($data['lotId']) && $data['lotId'] !== '' ? (int)$data['lotId'] : null;

try {
    // Start transaction
    $conn->begin_transaction();

    // If lotId not provided, fetch it from reservations
    if (!$lotId) {
        $stmt = $conn->prepare('SELECT lotId, status FROM reservations WHERE reservationId = ? FOR UPDATE');
        $stmt->bind_param('i', $reservationID);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $lotId = (int)$row['lotId'];
            $currentStatus = $row['status'];
        } else {
            throw new Exception('Reservation not found');
        }
        $stmt->close();
    } else {
        // also lock reservation row
        $stmt = $conn->prepare('SELECT status FROM reservations WHERE reservationId = ? FOR UPDATE');
        $stmt->bind_param('i', $reservationID);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $currentStatus = $row['status'];
        } else {
            throw new Exception('Reservation not found');
        }
        $stmt->close();
    }

    // Prevent confirming reservations that are already final states (only allow pending-like statuses)
    if (isset($currentStatus)) {
        $lowerStatus = strtolower(trim($currentStatus));
        // Accept 'pending' and common variants like 'for reservation'
        if (!preg_match('/^(pending|for reservation|for_reservation|for-reservation)$/', $lowerStatus)) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Reservation is not in a pending state and cannot be confirmed.']);
            exit;
        }
    }

    // Update reservation status
    $newStatus = 'Reserved';
    $stmt = $conn->prepare('UPDATE reservations SET status = ?, updatedAt = NOW() WHERE reservationId = ?');
    $stmt->bind_param('si', $newStatus, $reservationID);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update reservation: ' . $stmt->error);
    }
    $stmt->close();

    // Update lot status if lotId exists
    if ($lotId) {
        // Lock lot row
        $stmt = $conn->prepare('SELECT status FROM lots WHERE lotId = ? FOR UPDATE');
        $stmt->bind_param('i', $lotId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            // proceed to update
            $stmt->close();
            $stmt2 = $conn->prepare('UPDATE lots SET status = ? WHERE lotId = ?');
            $stmt2->bind_param('si', $newStatus, $lotId);
            if (!$stmt2->execute()) {
                throw new Exception('Failed to update lot: ' . $stmt2->error);
            }
            $stmt2->close();
        } else {
            // lot not found - rollback
            throw new Exception('Associated lot not found');
        }
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Reservation confirmed and lot status updated.']);
    $conn->close();
    exit;

} catch (Exception $e) {
    if ($conn->errno) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}

?>
