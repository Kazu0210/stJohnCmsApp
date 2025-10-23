<?php
require_once "db_connect.php";
session_start();

// ✅ Set header to JSON for all responses
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// ✅ Check user session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authentication required. Please log in."]);
    exit;
}

$userId = $_SESSION['user_id'];

// ✅ Create upload folder if not exists
$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to create upload directory."]);
        exit;
    }
}

// ✅ File upload handler function
function saveFile($fileInputName, $uploadDir) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        // Sanitize filename
        $fileName = time() . "_" . preg_replace("/[^a-zA-Z0-9.\-_]/", "_", basename($_FILES[$fileInputName]['name']));
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetPath)) {
            // Return relative path for database
            return "uploads/" . $fileName;
        }
    }
    return null;
}

/* ---------------------------------------------------
    GET: Display Reservation History (No Changes Needed)
--------------------------------------------------- */
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $sql = "SELECT r.reservationId, r.clientName, r.address, r.contactNumber,
                    r.clientValidId, r.reservationDate, r.area, r.block, r.rowNumber,
                    r.lotNumber, lt.typeName AS lotType, lt.price, r.status
            FROM reservations r
            LEFT JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
            WHERE r.userId = ?
            ORDER BY r.createdAt DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $history
    ]);
    exit;
}

/* ---------------------------------------------------
    POST: Save Reservation (MODIFIED)
--------------------------------------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // --- 1. Sanitize and Extract Input Data ---
    $lotId           = trim($_POST["lotId"] ?? '');
    $clientName      = trim($_POST["client_name"] ?? '');
    $address         = trim($_POST["client_address"] ?? '');
    $contactNumber   = trim($_POST["client_contact"] ?? '');
    $deceasedName    = empty(trim($_POST["deceased_name"])) ? null : trim($_POST["deceased_name"]);
    $burialDate      = empty(trim($_POST["burial_date"])) ? null : trim($_POST["burial_date"]);
    $reservationDate = trim($_POST["reservation_date"] ?? '');
    $area            = trim($_POST["area"] ?? '');
    $block           = trim($_POST["block"] ?? ''); // ✅ Corrected syntax, removed string:
    $rowNumber       = trim($_POST["rowNumber"] ?? ''); // ✅ Corrected variable name from rowumber
    $lotNumber       = trim($_POST["lot_number"] ?? '');
    $burialDepth     = trim($_POST["burial_depth"] ?? '');
    $notes           = trim($_POST["additional_notes"] ?? '');
    $lotTypeId       = isset($_POST['lotTypeId']) && $_POST['lotTypeId'] !== '' ? (int)$_POST['lotTypeId'] : null;

    // --- 2. Validation Checks ---
    if (empty($clientName) || empty($address) || empty($contactNumber) || empty($reservationDate) || $lotTypeId === null) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing required fields. Please fill all fields marked with *."]);
        exit;
    }

    // --- 3. Handle File Uploads ---
    $clientValidId      = saveFile("client_id_upload", $uploadDir);
    $deathCertificate   = saveFile("death_certificate_upload", $uploadDir);
    $deceasedValidId    = saveFile("deceased_id_upload", $uploadDir);
    $burialPermit       = saveFile("burial_permit_upload", $uploadDir);

    if (empty($clientValidId)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Client's Valid ID is a required document."]);
        exit;
    }

    // --- 4. Define Status and Timestamp ---
    // Status for a new client reservation request.
    $reservationStatus = "Pending"; 
    $createdAt = date("Y-m-d H:i:s");

    // --- 5. Prepare Database Statements ---

    // A. Reservation Insertion
    $sql = "INSERT INTO reservations (
        userId, lotId, clientName, address, contactNumber, deceasedName, burialDate, reservationDate,
        area, block, rowNumber, lotNumber, burialDepth, notes, clientValidId, deathCertificate, deceasedValidId,
        burialPermit, status, createdAt, lotTypeId
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    // B. Lot Status Update (Only if lotId is present)
    // IMPORTANT: Check if lotId is set to run the UPDATE query.
    $updateLot = !empty($lotId);
    if ($updateLot) {
        // Check if the lot is available before attempting to reserve it
        $checkLotSql = "SELECT status FROM lots WHERE lotId = ?";
        $checkStmt = $conn->prepare($checkLotSql);
        $checkStmt->bind_param("i", $lotId);
        $checkStmt->execute();
        $lotResult = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($lotResult && $lotResult['status'] !== 'Available') {
            http_response_code(409); // Conflict
            echo json_encode(["status" => "error", "message" => "Lot ID {$lotId} is already {$lotResult['status']}. Please select an available lot."]);
            exit;
        }

        $sqlLots = "UPDATE lots SET status = ? where lotId = ?";
        $stmtLots = $conn->prepare($sqlLots);

        if (!$stmtLots) {
             // Rollback reservation status on error if possible, or log
             // For now, exit with error:
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database prepare error (Lot Update): " . $conn->error]);
            exit;
        }
        $stmtLots->bind_param("si", $reservationStatus, $lotId);
    }


    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database prepare error (Reservation Insert): " . $conn->error]);
        exit;
    }

    // --- 6. Bind Parameters and Execute ---
    // Note: lotId is now passed as an integer 'i'
    $stmt->bind_param(
        "iissssssssssssssssssi",
        $userId, $lotId, $clientName, $address, $contactNumber, $deceasedName, $burialDate,
        $reservationDate, $area, $block, $rowNumber, $lotNumber, $burialDepth, $notes,
        $clientValidId, $deathCertificate, $deceasedValidId, $burialPermit, $reservationStatus, $createdAt, $lotTypeId
    );
    
    // Begin transaction for data consistency
    $conn->begin_transaction();
    
    $success = true;

    // Execute Reservation Insert
    if (!$stmt->execute()) {
        $success = false;
        $errorMessage = "Failed to save reservation: " . $stmt->error;
    } else {
        $reservationId = $stmt->insert_id;
        
        // Execute Lot Status Update (Only if LotId was provided)
        if ($success && $updateLot) {
            if (!$stmtLots->execute()) {
                $success = false;
                $errorMessage = "Failed to update lot status: " . $stmtLots->error;
            }
        }
    }
    
    $stmt->close();
    if (isset($stmtLots)) $stmtLots->close();


    // --- 7. Handle Transaction Result and Burials Insert ---
    if ($success) {
        $conn->commit();
        
        // Insert into burials only if deceased name is provided
        if ($deceasedName && $burialDate) {
            $sqlBurial = "INSERT INTO burials (
                userId, lotId, reservationId, deceasedName, burialDate,
                area, block, rowNumber, lotNumber, deceasedValidId, deathCertificate, burialPermit, createdAt
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmtBurial = $conn->prepare($sqlBurial);
            if ($stmtBurial) {
                // Binding LotId as integer 'i'
                $stmtBurial->bind_param(
                    "iiisssssssss",
                    $userId, $lotId, $reservationId, $deceasedName, $burialDate, $area,
                    $block, $rowNumber, $lotNumber, $deceasedValidId, $deathCertificate, $burialPermit
                );
                $stmtBurial->execute();
                $stmtBurial->close();
            }
        }

        echo json_encode(["status" => "success", "message" => "Reservation submitted successfully. Lot {$lotId} status set to '{$reservationStatus}'."]);
    } else {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $errorMessage]);
    }
    
    exit;
}

$conn->close();
?>