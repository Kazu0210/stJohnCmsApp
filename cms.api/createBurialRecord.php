<?php

include "db_connect.php";

if (isset($$_POST['reservationId'])) {
    $reservationId = $$_POST['reservationId'];

    // Get all fields from the reservations table using the reservationId
    $query = "SELECT * FROM reservations WHERE reservationId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $reservationId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            // Store all fields from reservations into variables, set missing fields to empty string
            $userId = isset($data['userId']) ? $data['userId'] : 0;
            $lotId = isset($data['lotId']) ? $data['lotId'] : 0;
            $reservationId = isset($data['reservationId']) ? $data['reservationId'] : 0;
            $deceasedName = isset($data['deceasedName']) ? $data['deceasedName'] : '';
            $burialDate = isset($data['burialDate']) ? $data['burialDate'] : '';
            $area = isset($data['area']) ? $data['area'] : '';
            $block = isset($data['block']) ? $data['block'] : '';
            $rowNumber = isset($data['rowNumber']) ? $data['rowNumber'] : '';
            $lotNumber = isset($data['lotNumber']) ? $data['lotNumber'] : '';
            $lotType = isset($data['lotType']) ? $data['lotType'] : '';
            // If lotType is empty, get it from lots table using lotId and the correct column name 'Type'
            if (empty($lotType) && !empty($lotId)) {
                $queryLot = "SELECT Type FROM lots WHERE lotId = ?";
                $stmtLot = $conn->prepare($queryLot);
                if (!$stmtLot) {
                    echo json_encode(['status' => 'error', 'message' => 'Prepare failed for lots query', 'error' => $conn->error]);
                    exit;
                }
                $stmtLot->bind_param("i", $lotId);
                if ($stmtLot->execute()) {
                    $resultLot = $stmtLot->get_result();
                    if ($resultLot->num_rows > 0) {
                        $lotData = $resultLot->fetch_assoc();
                        $lotType = $lotData['Type'];
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'No lot found for lotId', 'lotId' => $lotId]);
                        exit;
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to execute lots query', 'error' => $stmtLot->error]);
                    exit;
                }
            }
            $deceasedValidId = isset($data['deceasedValidId']) ? $data['deceasedValidId'] : '';
            $deathCertificate = isset($data['deathCertificate']) ? $data['deathCertificate'] : '';
            $burialPermit = isset($data['burialPermit']) ? $data['burialPermit'] : '';
            $createdAt = isset($data['createdAt']) ? $data['createdAt'] : date('Y-m-d H:i:s');
            $updatedAt = isset($data['updatedAt']) ? $data['updatedAt'] : NULL;

            $queryInsert = "INSERT INTO burials (userId, lotId, reservationId, deceasedName, burialDate, area, block, rowNumber, lotNumber, lotType, deceasedValidId, deathCertificate, burialPermit, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($queryInsert);
            $stmtInsert->bind_param(
                "iiissssssssssss",
                $userId,
                $lotId,
                $reservationId,
                $deceasedName,
                $burialDate,
                $area,
                $block,
                $rowNumber,
                $lotNumber,
                $lotType,
                $deceasedValidId,
                $deathCertificate,
                $burialPermit,
                $createdAt,
                $updatedAt
            );
            if ($stmtInsert->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Burial record created successfully.', 'burialId' => $stmtInsert->insert_id]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create burial record.', 'error' => $stmtInsert->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No record found in reservations.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch data from burial_request.']);
    }
}