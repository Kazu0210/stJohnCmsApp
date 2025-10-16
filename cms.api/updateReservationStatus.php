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
    // Start a transaction so we update reservation and lot atomically
    $conn->begin_transaction();

    $sql = "UPDATE reservations SET status = ?, updatedAt = NOW() WHERE reservationID = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('si', $status, $reservationID);
    $stmt->execute();

    $affected = $stmt->affected_rows;
    $stmt->close();

    // If reservation was updated, and new status is cancelled, free the lot
    $statusLower = strtolower(trim($status));
    if ($affected > 0 && ($statusLower === 'cancelled' || $statusLower === 'cancel')) {
        // Find lotId associated with this reservation
        $sel = $conn->prepare("SELECT lotId FROM reservations WHERE reservationID = ? LIMIT 1");
        if ($sel) {
            $sel->bind_param('i', $reservationID);
            $sel->execute();
            $res = $sel->get_result();
            $row = $res->fetch_assoc();
            $sel->close();
            if ($row && isset($row['lotId']) && is_numeric($row['lotId'])) {
                $lotId = (int)$row['lotId'];
                $upd = $conn->prepare("UPDATE lots SET status = 'Available' WHERE lotId = ?");
                if ($upd) {
                    $upd->bind_param('i', $lotId);
                    $upd->execute();
                    $upd->close();
                }
            }
        }
    }

    $conn->commit();
    $conn->close();

    if ($affected > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Status updated']);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'No changes made (maybe already set)']);
    }

} catch (Exception $e) {
    if ($conn && $conn->connect_errno === 0) {
        // Attempt rollback if in transaction
        try { $conn->rollback(); } catch (Exception $_) { }
        $conn->close();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>
