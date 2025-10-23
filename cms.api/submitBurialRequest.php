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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Validate required fields
        $requiredFields = ['reservationId', 'lotId', 'deceasedName', 'burialDate', 'burialDepth'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Validate required files
        $requiredFiles = ['deceasedValidId', 'deathCertificate', 'burialPermit'];
        foreach ($requiredFiles as $file) {
            if (!isset($_FILES[$file]) || $_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Missing or invalid file: $file");
            }
        }

        $reservationId = intval($_POST['reservationId']);
        $lotId = intval($_POST['lotId']);
        $deceasedName = trim($_POST['deceasedName']);
        $burialDate = $_POST['burialDate'];
        $burialDepth = $_POST['burialDepth'];
        $notes = trim($_POST['notes'] ?? '');

        // Verify that the reservation belongs to the current user
        $verifyStmt = $conn->prepare("SELECT * FROM reservations WHERE reservationId = ? AND userId = ?");
        $verifyStmt->bind_param("ii", $reservationId, $userId);
        $verifyStmt->execute();
        $reservation = $verifyStmt->get_result()->fetch_assoc();
        $verifyStmt->close();

        if (!$reservation) {
            throw new Exception("Reservation not found or does not belong to you.");
        }

        // Create uploads directory if it doesn't exist
        $uploadDir = "../uploads/burials/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Handle file uploads
        $uploadedFiles = [];
        $fileFields = [
            'deceasedValidId' => 'valid_id',
            'deathCertificate' => 'death_cert',
            'burialPermit' => 'burial_permit'
        ];

        foreach ($fileFields as $inputName => $prefix) {
            $file = $_FILES[$inputName];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                throw new Exception("Invalid file type for $inputName. Only PDF, JPG, JPEG, PNG are allowed.");
            }

            $fileName = $prefix . '_' . $reservationId . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception("Failed to upload $inputName file.");
            }

            $uploadedFiles[$inputName] = 'burials/' . $fileName;
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Update the reservation with burial information
            $updateReservationStmt = $conn->prepare("
                UPDATE reservations 
                SET deceasedName = ?, burialDate = ?, burialDepth = ?, 
                    deceasedValidId = ?, deathCertificate = ?, burialPermit = ?, 
                    notes = ?, updatedAt = NOW()
                WHERE reservationId = ?
            ");
            $updateReservationStmt->bind_param(
                "sssssssi",
                $deceasedName,
                $burialDate,
                $burialDepth,
                $uploadedFiles['deceasedValidId'],
                $uploadedFiles['deathCertificate'],
                $uploadedFiles['burialPermit'],
                $notes,
                $reservationId
            );

            if (!$updateReservationStmt->execute()) {
                throw new Exception("Failed to update reservation: " . $updateReservationStmt->error);
            }
            $updateReservationStmt->close();

            // Insert into burials table
            $insertBurialStmt = $conn->prepare("
                INSERT INTO burials (
                    userId, lotId, reservationId, deceasedName, burialDate, 
                    area, block, rowNumber, lotNumber, lotType, burialDepth,
                    deceasedValidId, deathCertificate, burialPermit, createdAt
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insertBurialStmt->bind_param(
                "iiisssssssssss",
                $userId,
                $lotId,
                $reservationId,
                $deceasedName,
                $burialDate,
                $reservation['area'],
                $reservation['block'],
                $reservation['rowNumber'],
                $reservation['lotNumber'],
                $reservation['lotType'] ?? 'Standard',
                $burialDepth,
                $uploadedFiles['deceasedValidId'],
                $uploadedFiles['deathCertificate'],
                $uploadedFiles['burialPermit']
            );

            if (!$insertBurialStmt->execute()) {
                throw new Exception("Failed to create burial record: " . $insertBurialStmt->error);
            }
            $insertBurialStmt->close();

            // Update lot status to "Occupied" (this will make it red on the map)
            $updateLotStmt = $conn->prepare("UPDATE lots SET status = 'Occupied', updatedAt = NOW() WHERE lotId = ?");
            $updateLotStmt->bind_param("i", $lotId);
            if (!$updateLotStmt->execute()) {
                throw new Exception("Failed to update lot status: " . $updateLotStmt->error);
            }
            $updateLotStmt->close();

            // Commit transaction
            $conn->commit();

            echo json_encode([
                "status" => "success",
                "message" => "Burial request submitted successfully. The lot status has been updated to Occupied.",
                "burialId" => $conn->insert_id
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
