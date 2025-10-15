<?php
// ======================================================
// 💰 Payment API (cms.api/save_payment.php)
// Handles payment submission and payment history
// ======================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');
require 'db_connect.php';

// ✅ Ensure user is logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

$userId = $_SESSION['user_id'];

// -----------------------------------------------------
// 🟦 GET — Fetch Payment History
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['mode'] ?? '') === 'getPayments') {
    try {
        $paymentId = $_GET['paymentId'] ?? null;
        $reservationId = $_GET['reservationId'] ?? null;

        $query = "
            SELECT 
                p.paymentId,
                p.reservationId,
                p.paymentMethodId,
                pm.methodName,
                p.month,
                p.amount,
                p.datePaid,
                p.reference,
                p.document,
                p.status,
                p.paymentType
            FROM payments p
            LEFT JOIN payment_methods pm ON p.paymentMethodId = pm.paymentMethodId
            LEFT JOIN reservations r ON p.reservationId = r.reservationId
            WHERE p.userId = ?
        ";

        // Add filters if passed
        if (!empty($paymentId)) {
            $query .= " AND p.paymentId = ?";
        } elseif (!empty($reservationId)) {
            $query .= " AND p.reservationId = ?";
        }

        $query .= " ORDER BY p.datePaid DESC";

        if (!empty($paymentId)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $userId, $paymentId);
        } elseif (!empty($reservationId)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $userId, $reservationId);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $userId);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = [
                "paymentId"       => $row['paymentId'],
                "reservationId"   => $row['reservationId'],
                "paymentMethodId" => $row['paymentMethodId'],
                "methodName"      => $row['methodName'] ?? paymentMethodName($row['paymentMethodId']),
                "month"           => $row['month'],
                "amount"          => (float)$row['amount'],
                "datePaid"        => $row['datePaid'],
                "reference"       => $row['reference'],
                "document"        => $row['document'],
                "status"          => $row['status'],
                "paymentType"     => $row['paymentType'] ?? ''
            ];
        }

        echo json_encode(["status" => "success", "data" => $payments]);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Database error: " . $e->getMessage()
        ]);
        exit;
    }
}

// -----------------------------------------------------
// 🟩 POST — Submit Payment
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId   = trim($_POST['reservationId'] ?? '');
    $paymentMethodId = trim($_POST['paymentMethodId'] ?? '');
    $month           = trim($_POST['month'] ?? date('F'));
    $amount          = trim($_POST['amount'] ?? '');
    $reference       = trim($_POST['reference'] ?? '');
    $paymentType     = trim($_POST['paymentType'] ?? '');
    $status          = "Pending";
    $datePaid        = date("Y-m-d H:i:s");
    $filePath        = null;

    // ✅ Validate required fields
    if (empty($reservationId) || empty($paymentMethodId) || empty($amount)) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit;
    }

    // ✅ Validate reservation belongs to user
    $resStmt = $conn->prepare("SELECT reservationId FROM reservations WHERE reservationId = ? AND userId = ?");
    $resStmt->bind_param("ii", $reservationId, $userId);
    $resStmt->execute();
    $res = $resStmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Invalid reservation ID"]);
        exit;
    }

    // ✅ Handle file upload
    if (!empty($_FILES['proofFile']) && $_FILES['proofFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/payments/";
        $publicDir = "uploads/payments/";

        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $originalName = basename($_FILES['proofFile']['name']);
        $safeName = time() . "_" . preg_replace("/[^A-Za-z0-9.\-_]/", "_", $originalName);
        $targetPath = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['proofFile']['tmp_name'], $targetPath)) {
            $filePath = $publicDir . $safeName;
        } else {
            echo json_encode(["status" => "error", "message" => "File upload failed"]);
            exit;
        }
    }

    // ✅ Insert payment record
    try {
        $stmt = $conn->prepare("
            INSERT INTO payments 
            (reservationId, userId, paymentMethodId, month, amount, datePaid, reference, document, status, paymentType, createdAt, updatedAt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->bind_param(
            "iiisssssss",
            $reservationId,
            $userId,
            $paymentMethodId,
            $month,
            $amount,
            $datePaid,
            $reference,
            $filePath,
            $status,
            $paymentType
        );

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Payment submitted successfully",
                "paymentId" => $stmt->insert_id,
                "document_path" => $filePath
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Database insert failed: " . $stmt->error
            ]);
        }

        $stmt->close();
        $conn->close();
        exit;

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        exit;
    }
}

// -----------------------------------------------------
// 🟥 Invalid request
// -----------------------------------------------------
echo json_encode(["status" => "error", "message" => "Invalid request method"]);
exit;

// -----------------------------------------------------
// 🔹 Helper
// -----------------------------------------------------
function paymentMethodName($id) {
    switch ($id) {
        case 1: return "GCash";
        case 2: return "Bank Transfer";
        case 3: return "Cash";
        default: return "Unknown";
    }
}
?>
