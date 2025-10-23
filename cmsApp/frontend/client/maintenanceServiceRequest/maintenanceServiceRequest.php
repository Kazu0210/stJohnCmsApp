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

    <nav class="navbar navbar-expand-lg fixed-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <span class="fw-bold">Blessed Saint John Memorial</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="../clientDashboard/clientDashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../cemeteryMap/cemeteryMap.php">Cemetery Map</a></li>
                    <li class="nav-item"><a class="nav-link" href="../lotReservation/lotReservation.php">Lot Reservation</a></li>
                    <li class="nav-item"><a class="nav-link" href="../payment/payment.php">Payment</a></li>
                    <li class="nav-item"><a class="nav-link" href="../burialRecord/burialRecord.php">Burial Record</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="maintenanceServiceRequest.php">Maintenance Request</a></li>
                </ul>
        
               <div class="d-lg-none mt-3 pt-3 border-top border-dark-subtle">
                     <div class="d-flex align-items-center mb-2">
                        <span id="user-name-display-mobile" class="fw-bold">User Name</span>
                    </div>
                    <a href="#" id="logoutLinkMobile" class="mobile-logout-link">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
            
            <div class="dropdown d-none d-lg-block">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="user-name-display-desktop">User Name</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="../../auth/login/login.php" id="logoutLinkDesktop">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-fluid py-4"> 
        <div class="maintenance-request-container card p-4 mb-4">
            <h2 class="text-center mb-4">Request for Maintenance Services</h2>
            <form id="maintenance-form" method="POST" action="http://localhost/stJohnCmsApp/cms.api/clientMaintenanceRequest.php">
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
              
              <nav aria-label="Maintenance Request Pagination">
                <ul class="pagination justify-content-center" id="paginationControls">
                    </ul>
              </nav>

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
    <script type="module" src="../../environment.js"></script>
    <script src="maintenanceServiceRequest.js"></script>
</body>
</html>