<?php
// processBurialRequest.php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../cmsApp/frontend/auth/login/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $reservationId = $_POST['reservationId'];
    $lotId = $_POST['lotId'];
    $deceasedName = $_POST['deceasedName'];
    $burialDate = $_POST['burialDate'];
    $status = 'pending';
    $createdAt = date('Y-m-d H:i:s');
    $updatedAt = date('Y-m-d H:i:s');

    // Handle file uploads
    $deceasedValidId = '';
    $deathCertificate = '';
    $burialPermit = '';
    $uploadDir = '../uploads/burial_requests/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    if (isset($_FILES['deceasedValidId']) && $_FILES['deceasedValidId']['error'] === UPLOAD_ERR_OK) {
        $deceasedValidId = $uploadDir . basename($_FILES['deceasedValidId']['name']);
        move_uploaded_file($_FILES['deceasedValidId']['tmp_name'], $deceasedValidId);
    }
    if (isset($_FILES['deathCertificate']) && $_FILES['deathCertificate']['error'] === UPLOAD_ERR_OK) {
        $deathCertificate = $uploadDir . basename($_FILES['deathCertificate']['name']);
        move_uploaded_file($_FILES['deathCertificate']['tmp_name'], $deathCertificate);
    }
    if (isset($_FILES['burialPermit']) && $_FILES['burialPermit']['error'] === UPLOAD_ERR_OK) {
        $burialPermit = $uploadDir . basename($_FILES['burialPermit']['name']);
        move_uploaded_file($_FILES['burialPermit']['tmp_name'], $burialPermit);
    }

    $sql = "INSERT INTO burial_request (userId, lotId, reservationId, deceasedName, burialDate, deceasedValidId, deathCertificate, burialPermit, status, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiissssssss', $userId, $lotId, $reservationId, $deceasedName, $burialDate, $deceasedValidId, $deathCertificate, $burialPermit, $status, $createdAt, $updatedAt);
    if ($stmt->execute()) {
        header('Location: ../cmsApp/frontend/client/burialRequest/burialRequest.php?success=1');
        exit();
    } else {
        header('Location: ../cmsApp/frontend/client/burialRequest/submitBurialRequest.php?error=1');
        exit();
    }
}
?>
