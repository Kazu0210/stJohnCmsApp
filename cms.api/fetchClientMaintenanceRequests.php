<?php
require_once "db_connect.php";

// Accept userId from GET or POST
$userId = $_GET['userId'] ?? $_POST['userId'] ?? null;

if (!$userId) {
    echo json_encode([
        "success" => false,
        "message" => "Missing userId parameter"
    ]);
    exit;
}

$sql = "SELECT * FROM maintenancerequest WHERE userId = ? ORDER BY requestedDate DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error preparing statement: " . $conn->error
    ]);
    exit;
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode([
    "success" => true,
    "data" => $requests
]);
?>
