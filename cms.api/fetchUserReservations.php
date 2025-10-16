<?php
header('Content-Type: application/json');
require 'db_connect.php';
require 'auth_helper.php';

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$userId = getCurrentUserId();

try {
    $sql = "
        SELECT r.*, lt.lotTypeName, lt.description AS lotTypeDescription
        FROM reservations r
        LEFT JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
        WHERE r.userId = ?
        ORDER BY r.createdAt DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $err = $conn->error;
        // log error
        @mkdir(__DIR__ . '/logs', 0755, true);
        @file_put_contents(__DIR__ . '/logs/fetchUserReservations.log', date('c') . " | prepare_failed: $err\n", FILE_APPEND | LOCK_EX);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Server error']);
        exit;
    }

    if (!$stmt->bind_param('i', $userId)) {
        $err = $stmt->error;
        @file_put_contents(__DIR__ . '/logs/fetchUserReservations.log', date('c') . " | bind_param_failed: $err\n", FILE_APPEND | LOCK_EX);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Server error']);
        exit;
    }

    if (!$stmt->execute()) {
        $err = $stmt->error;
        @file_put_contents(__DIR__ . '/logs/fetchUserReservations.log', date('c') . " | execute_failed: $err\n", FILE_APPEND | LOCK_EX);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Server error']);
        exit;
    }

    $result = $stmt->get_result();
    if ($result === false) {
        $err = $stmt->error;
        @file_put_contents(__DIR__ . '/logs/fetchUserReservations.log', date('c') . " | get_result_failed: $err\n", FILE_APPEND | LOCK_EX);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Server error']);
        exit;
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}
