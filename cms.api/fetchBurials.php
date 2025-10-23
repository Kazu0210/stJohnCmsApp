<?php
session_start();
require_once "db_connect.php";
// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authentication required. Please log in."]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $sql = "SELECT 
                    reservationId,
                    deceasedName,
                    burialDate,
                    area,
                    block,
                    rowNumber,
                    lotNumber,
                    deceasedValidId,
                    deathCertificate,
                    burialPermit,
                    createdAt
                FROM burials
                WHERE userId = ?
                ORDER BY createdAt DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $burials = [];
        while ($row = $result->fetch_assoc()) {
            $burials[] = $row;
        }

        echo json_encode(["status" => "success", "data" => $burials]);
        exit;
    } else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Invalid request method."]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Unexpected server error: " . $e->getMessage()]);
    exit;
}
?>
