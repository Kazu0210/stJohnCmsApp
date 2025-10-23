<?php
// getDocument.php
session_start();
require_once "db_connect.php";
// Assuming auth_helper.php contains: isAuthenticated(), isAdminOrSecretary(), getCurrentUserId(), isClient()
require_once "auth_helper.php"; 

// The script must handle CORS for cross-origin local development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// === Helper: Safe logger (keeping this as is) ===
function logError($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/document_errors.log';
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Initialize parameters
$userId = getCurrentUserId();
$reservationId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$docType = $_GET['doc'] ?? null;
$column = null;
$isPaymentDoc = false;

// === 1. Authentication check (Basic) ===
if (!isAuthenticated()) {
    http_response_code(401);
    logError("Unauthorized access attempt for $docType.");
    // Changed output to JSON for consistency with client expectations
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Error: User not logged in."]);
    exit;
}

// === 2. Validate parameters ===
if (!$docType) {
    http_response_code(400);
    logError("Missing document type parameter.");
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Error: Missing document type parameter."]);
    exit;
}

// === 3. Allowed document fields and Authorization Setup (Granular Check) ===
$allowedDocs = [
    "valid_id"      => ["col" => "deceasedValidId", "table" => "burials", "auth" => "client_or_admin", "id_field" => "burialId"],
    "death_cert"    => ["col" => "deathCertificate", "table" => "burials", "auth" => "client_or_admin", "id_field" => "burialId"],
    "burial_permit" => ["col" => "burialPermit", "table" => "burials", "auth" => "client_or_admin", "id_field" => "burialId"],
    "client_id"     => ["col" => "clientValidId", "table" => "reservations", "auth" => "client_or_admin", "id_field" => "reservationId"],
    "payment"       => ["col" => "document", "table" => "payments", "auth" => "admin_only", "id_field" => "paymentId"]
    // Added aliases for consistency (optional but helpful)
    , "death-cert"    => ["col" => "deathCertificate", "table" => "burials", "auth" => "client_or_admin", "id_field" => "burialId"]
    , "burial-permit" => ["col" => "burialPermit", "table" => "burials", "auth" => "client_or_admin", "id_field" => "burialId"]
    , "valid-id"      => ["col" => "deceasedValidId", "table" => "burials", "auth" => "client_or_admin", "id_field" => "burialId"]
    , "client-id"     => ["col" => "clientValidId", "table" => "reservations", "auth" => "client_or_admin", "id_field" => "reservationId"]
];


if (!array_key_exists($docType, $allowedDocs)) {
    http_response_code(400);
    logError("Invalid document type requested: $docType by user $userId");
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Error: Invalid document type."]);
    exit;
}

$docInfo = $allowedDocs[$docType];
$column = $docInfo['col'];
$table = $docInfo['table'];
$idField = $docInfo['id_field'];
$isPaymentDoc = ($docType === 'payment');

// === 4. Granular Authorization Check ===
if ($docInfo['auth'] === 'admin_only' && !isAdminOrSecretary()) {
    http_response_code(403);
    logError("Access denied. Admin or Secretary role required for $docType.");
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Error: Access denied."]);
    exit;
}

// Determine the ID to use. Assuming the client-side passes the appropriate ID.
$id = $reservationId; // Default to the ID passed in the 'id' parameter

// === 5. Fetch file path and Authorization logic (DB Check) ===

// Base query for admin/secretary (direct ID lookup)
$sqlAdmin = "SELECT $column FROM $table WHERE $idField = ? LIMIT 1";

// Base query for client (ID + ownership check)
$sqlClient = "";
if ($table === 'burials') {
    // Note: Burials table usually links to reservations. Assuming a 'reservationId' field links it back to the client.
    $sqlClient = "SELECT b.$column FROM burials b JOIN reservations r ON b.reservationId = r.reservationId WHERE b.reservationId = ? AND r.userId = ? LIMIT 1";
} elseif ($table === 'reservations') {
    $sqlClient = "SELECT $column FROM $table WHERE $idField = ? AND userId = ? LIMIT 1";
} elseif ($table === 'payments') {
    // Payments requires admin_only, but if we allowed client access, the check would be here
    $sqlClient = "SELECT $column FROM $table WHERE $idField = ? AND userId = ? LIMIT 1";
}


if (isAdminOrSecretary()) {
    $stmt = $conn->prepare($sqlAdmin);
    $stmt->bind_param("i", $id);
} else {
    // This covers 'client_or_admin' access for non-admin users
    if ($table === 'burials' || $table === 'reservations') {
        $stmt = $conn->prepare($sqlClient);
        // Bind the ID (reservationId) and the userId
        $stmt->bind_param("ii", $id, $userId); 
    } else {
        // Should not happen due to 'admin_only' check, but safe fallback
        http_response_code(403);
        logError("Invalid state: Non-admin trying to access non-client data.");
        header("Content-Type: application/json");
        echo json_encode(["status" => "error", "message" => "Error: Forbidden."]);
        exit;
    }
}

$stmt->execute();
$stmt->bind_result($filePath);
$stmt->fetch();
$stmt->close();

// === 6. File path validation and file_exists check ===
if (!$filePath) {
    http_response_code(404);
    logError("No file path found or unauthorized access for user $userId, ID $id, type $docType.");
    
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Error: File not found or access denied (record not linked to your account)."]);
    exit;
}

$filePath = trim(str_replace(["\\", "//"], "/", $filePath));

// Normalizing path separators for the current OS
$normalizePath = function($path) {
    return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
};

// --- REDUCED AND FOCUSED PATH CHECKING ---
// NOTE: Files should generally be stored with a relative path (e.g., 'uploads/burials/filename.pdf')
// The client application is in stJohnCmsApp/cmsApp/client/burialRecord/
// The API is in stJohnCmsApp/cmsApp/cms.api/
// Documents are likely in stJohnCmsApp/uploads/ or stJohnCmsApp/cmsApp/uploads/

$baseDir = dirname(__DIR__); // Should resolve to /stJohnCmsApp/cmsApp/

$possiblePaths = [
    // 1. Path relative to the API's parent directory (e.g., /stJohnCmsApp/cmsApp/ + uploads/filename.pdf)
    $normalizePath($baseDir . DIRECTORY_SEPARATOR . $filePath),
    // 2. Path relative to the API directory itself (e.g., /stJohnCmsApp/cmsApp/cms.api/ + uploads/filename.pdf)
    $normalizePath(__DIR__ . DIRECTORY_SEPARATOR . $filePath),
];

$foundPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $foundPath = $path;
        break;
    }
}

if (!$foundPath) {
    http_response_code(404);
    logError("File missing for user $userId, ID $id, doc $docType. Checked: " . json_encode($possiblePaths));
    
    header("Content-Type: application/json");
    echo json_encode([
        "status" => "error",
        "message" => "Error: File missing from server storage.",
        "file" => basename($filePath)
    ]);
    exit;
}

// === 7. Send file with proper headers ===
// ... (The file sending logic remains correct) ...
$ext = strtolower(pathinfo($foundPath, PATHINFO_EXTENSION));

switch ($ext) {
    case "pdf":
        header("Content-Type: application/pdf");
        header("Content-Disposition: inline; filename=\"" . basename($foundPath) . "\"");
        break;
    case "jpg":
    case "jpeg":
        header("Content-Type: image/jpeg");
        header("Content-Disposition: inline; filename=\"" . basename($foundPath) . "\"");
        break;
    case "png":
        header("Content-Type: image/png");
        header("Content-Disposition: inline; filename=\"" . basename($foundPath) . "\"");
        break;
    default:
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($foundPath) . "\"");
}

header("Content-Length: " . filesize($foundPath));
readfile($foundPath);
exit;
?>