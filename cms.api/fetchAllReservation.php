<?php
require_once "db_connect.php";
session_start();

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
    // Query to get ALL reservations with client details for admin management
    $sql = "SELECT 
                r.reservationId,
                r.userId,
                r.lotId,
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
                r.lotTypeId
            FROM reservations r
            ORDER BY r.createdAt DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        // Format the lot type name based on type ID
        $lotTypeName = getLotTypeName($row['lotTypeId']);
        
        // Get the price from lot type (you might need to join with a lot_types table)
        $amount = getLotTypePrice($row['lotTypeId']);
        $formattedAmount = '₱' . number_format($amount, 2);
        
        // Format dates
        $submittedOn = $row['createdAt'] ? date('M d, Y', strtotime($row['createdAt'])) : 'N/A';
        $updatedOn = $row['updatedAt'] ? date('M d, Y', strtotime($row['updatedAt'])) : 'N/A';
        $reservationDate = $row['reservationDate'] ? date('M d, Y', strtotime($row['reservationDate'])) : 'N/A';
        $burialDate = $row['burialDate'] ? date('M d, Y', strtotime($row['burialDate'])) : 'N/A';
        
        $reservations[] = [
            'reservationId' => $row['reservationId'],
            'userId' => $row['userId'],
            'lotId' => $row['lotId'],
            'clientName' => $row['clientName'] ?? 'N/A',
            'clientAddress' => $row['address'] ?? 'N/A',
            'clientContact' => $row['contactNumber'] ?? 'N/A',
            'clientId' => $row['clientValidId'] ?? null,
            'deceasedName' => $row['deceasedName'] ?? 'N/A',
            'burialDate' => $burialDate,
            'deathCertificate' => $row['deathCertificate'] ?? null,
            'deceasedValidId' => $row['deceasedValidId'] ?? null,
            'burialPermit' => $row['burialPermit'] ?? null,
            'reservationDate' => $reservationDate,
            'area' => $row['area'] ?? 'N/A',
            'block' => $row['block'] ?? 'N/A',
            'row' => $row['rowNumber'] ?? 'N/A',
            'lotNumber' => $row['lotNumber'] ?? 'N/A',
            'lotType' => $row['lotTypeId'],
            'lotTypeName' => $lotTypeName,
            'burialDepth' => $row['burialDepth'] ?? 'N/A',
            'notes' => $row['notes'] ?? '',
            'amount' => $amount,
            'formattedAmount' => $formattedAmount,
            'status' => $row['status'] ?? 'For Reservation',
            'submittedOn' => $submittedOn,
            'updatedOn' => $updatedOn
        ];
    }

    // Return successful response
    $response = [
        'success' => true,
        'count' => count($reservations),
        'data' => $reservations
    ];

    echo json_encode($response);

    $stmt->close();

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'count' => 0,
        'data' => []
    ]);
}

$conn->close();

// Helper function to get lot type name
function getLotTypeName($lotType) {
    $lotTypes = [
        '1' => 'Regular Lot (₱50,000)',
        '2' => 'Regular Lot (₱60,000)',
        '3' => 'Premium Lot (₱70,000)',
        '4' => 'Mausoleum Inside (₱500,000)',
        '5' => 'Mausoleum Roadside (₱600,000)',
        '6' => '4-Lot Package (₱300,000)',
        '7' => 'Exhumation (₱15,000)'
    ];
    
    return $lotTypes[$lotType] ?? 'Unknown Lot Type';
}

// Helper function to get lot type price
function getLotTypePrice($lotType) {
    $lotPrices = [
        '1' => 50000,
        '2' => 60000,
        '3' => 70000,
        '4' => 500000,
        '5' => 600000,
        '6' => 300000,
        '7' => 15000
    ];
    
    return $lotPrices[$lotType] ?? 0;
}
?>