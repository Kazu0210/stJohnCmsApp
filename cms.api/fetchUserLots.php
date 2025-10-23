<?php
session_start();
require_once "db_connect.php";

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in",
        "data" => []
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

// Helper function to get lot type name
function getLotTypeName($lotTypeId) {
    $lotTypes = [
        '1' => 'Regular Lot (₱50,000)',
        '2' => 'Regular Lot (₱60,000)',
        '3' => 'Premium Lot (₱70,000)',
        '4' => 'Mausoleum Inside (₱500,000)',
        '5' => 'Mausoleum Roadside (₱600,000)',
        '6' => '4-Lot Package (₱300,000)',
        '7' => 'Exhumation (₱15,000)'
    ];
    
    return $lotTypes[$lotTypeId] ?? 'Unknown Lot Type';
}

try {
    // Fetch reservations for the logged-in user with user information
    $sql = "SELECT 
                r.reservationId,
                r.lotId,
                r.userId,
                r.clientName,
                r.address,
                r.contactNumber,
                r.clientValidId,
                r.deceasedName,
                r.burialDate,
                r.deathCertificate,
                r.deceasedValidId,
                r.burialPermit,
                r.reservationDate,
                r.area,
                r.block,
                r.rowNumber,
                r.lotNumber,
                r.burialDepth,
                r.notes,
                r.status,
                r.createdAt,
                r.updatedAt,
                r.lotTypeId,
                u.firstName,
                u.lastName,
                u.email,
                lt.price,
                lt.monthlyPayment
            FROM reservations r
            LEFT JOIN user u ON r.userId = u.userId
            LEFT JOIN lot_types lt ON r.lotTypeId = lt.lotTypeId
            WHERE r.userId = ? 
            AND r.status IN ('For Reservation', 'Reserved', 'Cancelled', 'Occupied')
            ORDER BY r.area, r.block, r.rowNumber, r.lotNumber";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("SQL prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        // Use client name from reservation, fallback to user's full name or email
        $clientName = $row['clientName'];
        if (empty($clientName)) {
            $clientName = trim($row['firstName'] . ' ' . $row['lastName']);
            if (empty($clientName)) {
                $clientName = $row['email']; // Fallback to email if no name
            }
        }
        
        $reservations[] = [
            'reservationId' => $row['reservationId'],
            'lotId' => $row['lotId'],
            'userId' => $row['userId'],
            'clientName' => $clientName,
            'address' => $row['address'],
            'contactNumber' => $row['contactNumber'],
            'clientValidId' => $row['clientValidId'],
            'deceasedName' => $row['deceasedName'],
            'burialDate' => $row['burialDate'],
            'deathCertificate' => $row['deathCertificate'],
            'deceasedValidId' => $row['deceasedValidId'],
            'burialPermit' => $row['burialPermit'],
            'reservationDate' => $row['reservationDate'],
            'area' => $row['area'],
            'block' => $row['block'],
            'rowNumber' => $row['rowNumber'],
            'lotNumber' => $row['lotNumber'],
            'burialDepth' => $row['burialDepth'],
            'notes' => $row['notes'],
            'status' => $row['status'],
            'createdAt' => $row['createdAt'],
            'updatedAt' => $row['updatedAt'],
            'lotTypeId' => $row['lotTypeId'],
            'lotType' => getLotTypeName($row['lotTypeId']),
            'price' => $row['price'],
            'monthlyPayment' => $row['monthlyPayment']
        ];
    }

    echo json_encode([
        "status" => "success",
        "data" => $reservations,
        "count" => count($reservations)
    ]);

    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage(),
        "data" => []
    ]);
}

$conn->close();
?>