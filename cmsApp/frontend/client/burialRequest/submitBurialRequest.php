<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['client_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['email'])) {
    // User is not logged in, redirect to login page
    header("Location: ../../auth/login/login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Burial Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../clientNavbar.php'; ?>

    <div class="container mt-5 pt-4">
        <h2 class="mb-4">Submit Burial Request</h2>
        <form action="processBurialRequest.php" method="POST" enctype="multipart/form-data" class="bg-light p-4 rounded shadow-sm">
            <div class="mb-3">
                <label for="reservationId" class="form-label">Reservation</label>
                <select class="form-select" id="reservationId" name="reservationId" required>
                    <option value="">Select Reservation</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="lotId" class="form-label">Lot ID</label>
                <input type="number" class="form-control" id="lotId" name="lotId" required readonly>
            </div>
            <div class="mb-3">
                <label for="deceasedName" class="form-label">Deceased Name</label>
                <input type="text" class="form-control" id="deceasedName" name="deceasedName" maxlength="150" required>
            </div>
            <div class="mb-3">
                <label for="burialDate" class="form-label">Burial Date</label>
                <input type="date" class="form-control" id="burialDate" name="burialDate" required>
            </div>
            <div class="mb-3">
                <label for="deceasedValidId" class="form-label">Deceased Valid ID</label>
                <input type="file" class="form-control" id="deceasedValidId" name="deceasedValidId" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <div class="mb-3">
                <label for="deathCertificate" class="form-label">Death Certificate</label>
                <input type="file" class="form-control" id="deathCertificate" name="deathCertificate" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <div class="mb-3">
                <label for="burialPermit" class="form-label">Burial Permit</label>
                <input type="file" class="form-control" id="burialPermit" name="burialPermit" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <button type="submit" class="btn btn-warning"><i class="fas fa-paper-plane me-2"></i>Submit Request</button>
        </form>
    </div>
</body>
</body>
<script>
$(document).ready(function() {
    // Fetch user reservations and populate the select
    $.ajax({
        url: '/stJohnCmsApp/cms.api/fetchUserReservationsApi.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.reservations && response.reservations.length > 0) {
                $('#reservationId').empty().append('<option value="">Select Reservation</option>');
                response.reservations.forEach(function(res) {
                    var optionText = 'Reservation #' + res.reservationId + ' - Lot #' + res.lotId;
                    $('#reservationId').append('<option value="' + res.reservationId + '" data-lotid="' + res.lotId + '">' + optionText + '</option>');
                });
            } else {
                $('#reservationId').empty().append('<option value="">No reservations found</option>');
            }
        },
        error: function() {
            $('#reservationId').empty().append('<option value="">Error loading reservations</option>');
        }
    });

    // Auto-fill Lot ID when reservation is selected
    $('#reservationId').on('change', function() {
        var lotId = $(this).find(':selected').data('lotid') || '';
        $('#lotId').val(lotId);
    });
});
</script>
</html>