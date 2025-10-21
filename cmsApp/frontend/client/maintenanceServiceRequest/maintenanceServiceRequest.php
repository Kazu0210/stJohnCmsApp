<?php
session_start();
// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: /stJohnCmsApp/cmsApp/frontend/auth/login/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - Blessed Saint John Memorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="maintenanceServiceRequest.css">
</head>
<body>

    <?php include '../../client/clientNavbar.php'; ?>

    <main class="container py-4">
        <div class="maintenance-request-container card p-4 mb-4">
            <h2 class="text-center mb-4">Request for Maintenance Services</h2>
            <form id="maintenance-form" method="POST" action="http://localhost/cms.api/clientMaintenanceRequest.php">
                <div class="mb-3">
                    <label for="reservationId" class="form-label">Select Lot</label>
                    <select id="reservationId" name="reservationId" class="form-select" required>
                        <option value="">-- Select Lot --</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="serviceType" class="form-label">Service Type</label>
                    <select id="serviceType" name="serviceType" class="form-select" required>
                        <option value="General Cleaning">General Cleaning</option>
                        <option value="Trimming">Trimming</option>
                        <option value="Repainting">Repainting</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Additional Notes</label>
                    <textarea id="notes" name="notes" rows="4" class="form-control" placeholder="Enter any additional details about the maintenance request"></textarea>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="submit-btn btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>

        <div class="maintenance-request-history-container card p-4 mb-4">
              <h2 class="text-center mb-4">Request History</h2>
              <div class="table-responsive">
                  <table class="maintenance-history-table table table-hover">
                      <thead>
                          <tr>
                              <th>Area</th>
                              <th>Block</th>
                              <th>Row Number</th>
                              <th>Lot Number</th>
                              <th>Service Type</th>
                              <th>Status</th>
                              <th>Date Requested</th>
                              <th>Details</th>
                          </tr>
                      </thead>
                      <tbody id="requestHistoryBody"></tbody>
                  </table>
              </div>
          </div>
        </div>
    </main>

    <footer class="footer text-center py-3">
        <div class="container d-flex flex-column flex-md-row justify-content-center align-items-center">
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="maintenanceServiceRequest.js"></script>
</body>
</html>
