<?php
// Helper to log audit actions
require_once 'db_connect.php';

function log_audit($user_id, $action, $details = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}
