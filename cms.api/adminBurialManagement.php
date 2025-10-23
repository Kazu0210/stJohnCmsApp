<?php
// adminBurialManagement.php - Backend script to fetch burial records for admin
session_start();
require_once "db_connect.php";
require_once "auth_helper.php";

// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Check authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authentication required"]);
    exit;
}

// Check if user has admin or secretary role
if (!isAdminOrSecretary()) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access denied. Admin or Secretary role required"]);
    exit;
}

try {
    // --- 2. Data Fetching ---
    $sql = "SELECT
                burialId AS id,
                deceasedName AS name,
                burialDate,
                area,
                block,
                rowNumber,
                lotNumber,
                'active' AS status,
                createdAt AS submittedOn,
                COALESCE(updatedAt, createdAt) AS updatedOn,
                deathCertificate,
                burialPermit,
                deceasedValidId
            FROM burials
            ORDER BY burialDate DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database query failed: " . $e->getMessage()]);
    exit;
}

// --- 3. Format Data for Frontend JavaScript Consumption ---
$burialData = [];

foreach ($records as $record) {
    $docs = [];

    // Create document references (file paths are stored in database)
    if (!empty($record['deathCertificate'])) {
        $docs['death-cert'] = $record['deathCertificate'];
    }
    if (!empty($record['burialPermit'])) {
        $docs['burial-permit'] = $record['burialPermit'];
    }
    if (!empty($record['deceasedValidId'])) {
        $docs['valid-id'] = $record['deceasedValidId'];
    }

    $burialData[] = [
        'id' => (int)$record['id'],
        'name' => $record['name'],
        'burialDate' => $record['burialDate'],
        'area' => $record['area'],
        'block' => $record['block'],
        'rowNumber' => $record['rowNumber'],
        'lotNumber' => $record['lotNumber'],
        'status' => $record['status'],
        'submittedOn' => $record['submittedOn'],
        'updatedOn' => $record['updatedOn'],
        'docs' => (object)$docs
    ];
}

// --- 4. Output JSON response ---
echo json_encode([
    "status" => "success",
    "data" => $burialData
]);
exit;
?>