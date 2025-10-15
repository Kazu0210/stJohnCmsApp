
<?php
// Suppress error output for API JSON endpoints
error_reporting(0);
ini_set('display_errors', 0);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cmsdb";

// Create and check connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    // Don't output HTML, return JSON if used in API context
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'cms.api') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed',
            'data' => []
        ]);
        exit;
    } else {
        die("Database connection failed: " . $conn->connect_error);
    }
}
?>
