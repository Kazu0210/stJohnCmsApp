<?php
//get_lots.php - Corrected to include the datePending column
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require "db_connect.php"; // Ensure this connects to your database

// // ✅ Check user session
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(["success" => false, "message" => "User not logged in"]);
//     exit;
// }

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$page   = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit  = isset($_GET['limit']) ? intval($_GET['limit']) : 6000;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = "";
$params = [];
$types = "";

if (!empty($search)) {
    // Note: status is included in search, which is fine
    $where = "WHERE lotId LIKE ? OR block LIKE ? OR area LIKE ? OR lotNumber LIKE ? OR status LIKE ?";
    $searchTerm = "%" . $search . "%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
    $types = "issss"; // Assuming lotId is int, others are strings for search
}

// --- Total rows for pagination ---
$totalSql = "SELECT COUNT(*) as total FROM lots $where";
$stmt = $conn->prepare($totalSql);
if (!empty($where)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalResult = $stmt->get_result();
$totalRows = $totalResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRows / $limit);
$stmt->close();

// --- Fetch paginated data including the new datePending column ---
$sql = "SELECT lotId, userId, area, `block`, rowNumber, lotNumber, `type`, lotTypeId, buryDepth, `status`, datePending, geo, createdAt, updatedAt
        FROM lots $where
        ORDER BY createdAt DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

if (!empty($where)) {
    // Need to merge $params with $limit and $offset
    $params[] = $limit;
    $params[] = $offset;
    // Append "ii" for the two integer parameters ($limit, $offset)
    $stmt->bind_param($types . "ii", ...$params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

$lots = [];
while ($row = $result->fetch_assoc()) {
    $lots[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "data" => $lots,
    "page" => $page,
    "totalPages" => $totalPages,
    "totalRows" => $totalRows
]);
