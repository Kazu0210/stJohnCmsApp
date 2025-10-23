<?php
session_start();

require_once "db_connect.php";

try {
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $sql = "SELECT 
            burialId,
            userId,
            lotId,
            reservationId,
            deceasedName,
            burialDate,
            area,
            block,
            rowNumber,
            lotNumber,
            lotType,
            deceasedValidId,
            deathCertificate,
            burialPermit,
            createdAt,
            updatedAt
        FROM burials
        ORDER BY createdAt DESC";

        $result = $conn->query($sql);
        $burials = [];
        while ($row = $result->fetch_assoc()) {
            $burials[] = $row;
        }

        echo json_encode($burials);
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
