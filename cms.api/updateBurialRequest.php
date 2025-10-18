<?php
// updateBurialRequest.php
// Handles updating a burial request record and file uploads

header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

$response = ["success" => false, "message" => "Unknown error."];

// Validate required fields
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}


// Get burial request ID (requestId is primary key)
$requestId = isset($_POST['requestId']) ? $_POST['requestId'] : null;
$deceasedName = isset($_POST['deceasedName']) ? $_POST['deceasedName'] : null;
$burialDate = isset($_POST['burialDate']) ? $_POST['burialDate'] : null;
$status = isset($_POST['status']) ? $_POST['status'] : null;

if (!$requestId || !$deceasedName || !$burialDate) {
    $response['message'] = 'Missing required fields.';
    echo json_encode($response);
    exit;
}

// Handle file uploads
$uploadDir = __DIR__ . '/../uploads/burial_requests/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

function handleFile($field, $existingFile) {
    global $uploadDir;
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $filename = basename($_FILES[$field]['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
            return $filename;
        }
    }
    return $existingFile;
}


// Fetch existing file names from DB
$stmt = $conn->prepare("SELECT deceasedValidId, deathCertificate, burialPermit FROM burial_request WHERE requestId = ? LIMIT 1");
$stmt->bind_param('i', $requestId);
$stmt->execute();
$stmt->bind_result($existingValidId, $existingDeathCert, $existingPermit);
$stmt->fetch();
$stmt->close();

$deceasedValidId = handleFile('deceasedValidId', $existingValidId);
$deathCertificate = handleFile('deathCertificate', $existingDeathCert);
$burialPermit = handleFile('burialPermit', $existingPermit);


// Update burial request
$stmt = $conn->prepare("UPDATE burial_request SET deceasedName = ?, burialDate = ?, deceasedValidId = ?, deathCertificate = ?, burialPermit = ?, status = ?, updatedAt = NOW() WHERE requestId = ?");
$stmt->bind_param('ssssssi', $deceasedName, $burialDate, $deceasedValidId, $deathCertificate, $burialPermit, $status, $requestId);
if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Burial request updated.';

    echo `Request status is: {$staus}`;

    // If status is Approved, insert into reservations table
    // if (strtolower($status) === 'approved') {
    //     // Fetch burial request data for mapping
    //     $fetchStmt = $conn->prepare("SELECT deceasedName, burialDate, deceasedValidId, deathCertificate, burialPermit FROM burial_request WHERE requestId = ? LIMIT 1");
    //     $fetchStmt->bind_param('i', $requestId);
    //     $fetchStmt->execute();
    //     $burialData = $fetchStmt->get_result()->fetch_assoc();
    //     $fetchStmt->close();

    //     if ($burialData) {
    //         // Update only the required fields in the existing reservation using reservationId
    //         $updateStmt = $conn->prepare("UPDATE reservations SET deceasedName = ?, burialDate = ?, deceasedValidId = ?, deathCertificate = ?, burialPermit = ? WHERE reservationId = ?");
    //         $updateStmt->bind_param(
    //             'sssssi',
    //             $burialData['deceasedName'],
    //             $burialData['burialDate'],
    //             $burialData['deceasedValidId'],
    //             $burialData['deathCertificate'],
    //             $burialData['burialPermit'],
    //             $requestId
    //         );
    //         if ($updateStmt->execute()) {
    //             $response['reservation_updated'] = true;
    //         } else {
    //             $response['reservation_updated'] = false;
    //             $response['reservation_error'] = $updateStmt->error;
    //         }
    //         $updateStmt->close();
    //     }
    // }
} else {
    $response['message'] = 'Database update failed.';
}
$stmt->close();
$conn->close();

echo json_encode($response);
