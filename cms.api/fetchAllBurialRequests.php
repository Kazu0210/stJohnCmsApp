<?php
// fetchAllBurialRequests.php - For admin/secretary to fetch all burial requests

// Ensure no accidental output is sent before JSON
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db_connect.php';

// Optionally, check for admin/secretary role here if needed

try {
    $sql = "SELECT br.*, CONCAT(u.firstName, ' ', u.lastName) AS userName FROM burial_request br LEFT JOIN user u ON br.userId = u.userId ORDER BY br.createdAt DESC";
    // Execute query and handle errors
    $result = $conn->query($sql);
    $requests = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        $conn->close();

        // Clean any buffered output (stray whitespace/BOM) and output JSON
        ob_clean();
        echo json_encode(['requests' => $requests, 'success' => true]);
        ob_end_flush();
        exit;
    } else {
        $err = $conn->error;
        $conn->close();
        ob_clean();
        echo json_encode(['requests' => [], 'success' => false, 'message' => 'Query failed', 'error' => $err]);
        exit;
    }
} catch (Throwable $t) {
    // Ensure logs directory exists
    $logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . DIRECTORY_SEPARATOR . 'api_errors.log';
    $errMsg = date('c') . " - Exception: " . $t->getMessage() . " in " . $t->getFile() . ":" . $t->getLine() . "\n" . $t->getTraceAsString() . "\n\n";
    @file_put_contents($logFile, $errMsg, FILE_APPEND | LOCK_EX);
    // Return JSON error (clean output buffer first)
    ob_clean();
    echo json_encode(['requests' => [], 'success' => false, 'message' => 'Server error']);
    ob_end_flush();
    exit;
}
?>
