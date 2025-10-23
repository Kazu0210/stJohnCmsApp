<?php
header("Access-Control-Allow-Origin: http://localhost"); // Adjust if your client is on a different port
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Credentials: true");
// Enable CORS for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Include your database connection file
require_once 'database.php'; // Make sure this path is correct

// Start the session to get the logged-in user's ID
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated.']);
    exit;
}

$userId = $_SESSION['user_id'];
$response = [];

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // This complex query does the following:
    // 1. Selects reservation details from the 'reservations' table for the logged-in user.
    // 2. Joins with 'lots' and 'lot_types' to get the total price and monthly installment for each lot.
    // 3. Uses a subquery on the 'payments' table to calculate the total amount paid for each reservation.
    $sql = "
        SELECT 
            r.reservationId AS reservation_id,
            r.clientName,
            r.area,
            r.block,
            r.rowNumber,
            r.lotNumber,
            r.reservationDate AS reservation_date,
            lt.price AS totalPrice,
            lt.monthly_installment AS monthlyPayment,
            (
                SELECT SUM(p.amountPaid) 
                FROM payments p 
                WHERE p.reservationId = r.reservationId AND p.status = 'Confirmed'
            ) AS totalPaid
        FROM 
            reservations r
        JOIN 
            lots l ON r.lotId = l.lotId
        JOIN 
            lot_types lt ON l.lot_type_id = lt.type_id -- Assumes these column names exist
        WHERE 
            r.userId = :userId
            AND r.status = 'Reserved' -- Only fetch lots that are still actively reserved
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalPaid = $row['totalPaid'] ? (float)$row['totalPaid'] : 0.0;
        $totalPrice = (float)$row['totalPrice'];
        
        // Calculate remaining balance
        $row['remainingBalance'] = $totalPrice - $totalPaid;
        $row['totalPaid'] = $totalPaid; // Ensure totalPaid is a number
        
        // Determine if it's a new reservation (i.e., no confirmed payments have been made)
        $row['is_new_reservation'] = ($totalPaid == 0);

        $data[] = $row;
    }

    $response['status'] = 'success';
    $response['data'] = $data;

} catch (PDOException $e) {
    $response['status'] = 'error';
    $response['message'] = 'Database Error: ' . $e->getMessage();
}

// Close the connection
$conn = null;

echo json_encode($response);
?>