<?php
// Helper to log audit actions
require_once 'db_connect.php';

function log_audit($user_id, $action, $details = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log("Audit log prepare failed: " . $conn->error);
        return;
    }
    $stmt->bind_param("iss", $user_id, $action, $details);
    if (!$stmt->execute()) {
        error_log("Audit log execute failed: " . $stmt->error);
    }
    $stmt->close();
}
