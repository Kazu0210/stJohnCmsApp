<?php

include "db_connect.php";

header('Content-Type: application/json');

if (isset($_POST['reservationId'])) {
    $reservationId = $_POST['reservationId'];

    // Prepare SQL to get lotId from reservations table
    $sql = "SELECT lotId FROM reservations WHERE reservationId = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $lotId = $row['lotId'];

            // Fetch lot data from lots table
            $sqlLot = "SELECT * FROM lots WHERE lotId = ?";
            $stmtLot = $conn->prepare($sqlLot);
            if ($stmtLot) {
                $stmtLot->bind_param("i", $lotId);
                $stmtLot->execute();
                $resultLot = $stmtLot->get_result();
                if ($lotData = $resultLot->fetch_assoc()) {
                    // Update lot status to 'Occupied'
                    $updateSql = "UPDATE lots SET status = 'Occupied' WHERE lotId = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    if ($updateStmt) {
                        $updateStmt->bind_param("i", $lotId);
                        $updateStmt->execute();
                        $updateStmt->close();
                        // Refresh lot data after update
                        $stmtLot2 = $conn->prepare($sqlLot);
                        $stmtLot2->bind_param("i", $lotId);
                        $stmtLot2->execute();
                        $resultLot2 = $stmtLot2->get_result();
                        $updatedLotData = $resultLot2->fetch_assoc();
                        $stmtLot2->close();
                        echo json_encode([
                            'status' => 'success',
                            'lotId' => $lotId,
                            'lotData' => $updatedLotData,
                            'message' => 'Lot status updated to Occupied.'
                        ]);
                    } else {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Failed to update lot status.'
                        ]);
                    }
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No data found for this lotId.'
                    ]);
                }
                $stmtLot->close();
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to prepare lot statement.'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No lot found for this reservationId.'
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to prepare statement.'
        ]);
    }
    exit;
}