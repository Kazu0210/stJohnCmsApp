<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="clientDashboard.css">
    <script src="clientDashboard.js" defer></script>
</head>
<body>
<nav class="navbar navbar-expand-lg fixed-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <span class="fw-bold">Cemetery Management System</span> </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../cemeteryMap/cemeteryMap.php">Cemetery Map</a></li>
                    <li class="nav-item"><a class="nav-link" href="../lotReservation/lotReservation.php">Lot Reservation</a></li>
                    <li class="nav-item"><a class="nav-link" href="../payment/payment.php">Payment</a></li>
                    <li class="nav-item"><a class="nav-link" href="../burialRecord/burialRecord.php">Burial Record</a></li>
                    <li class="nav-item"><a class="nav-link" href="../maintenanceServiceRequest/maintenanceServiceRequest.php">Maintenance Request</a></li>
                </ul>
            
                <div class="d-lg-none mt-3 pt-3 border-top border-dark-subtle">
                     <div class="d-flex align-items-center mb-2">
                        <span id="user-name-display-mobile" class="fw-bold">User Name</span>
                    </div>
                    <a href="../../../cms.api/logout.php" id="logoutLinkMobile" class="mobile-logout-link">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
            
            <div class="dropdown d-none d-lg-block">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="user-name-display-desktop">User Name</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="../../../cms.api/logout.php" id="logoutLinkDesktop">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </nav>


    <main class="main-content container-fluid">
        <section class="row g-3 mb-4">
            <div class="col-6 col-lg">
                <div class="panel">
                    <small>Reserved Lots</small>
                    <span class="fw-bold" id="reservedLotsCount">0</span>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="panel">
                    <small>Total Paid</small>
                    <span class="fw-bold">₱25,000</span>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="panel">
                    <small>Balance</small>
                    <span class="fw-bold">₱15,000</span>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="panel">
                    <small>Maintenance Requests</small>
                    <span class="fw-bold">3</span>
                </div>
            </div>
            <div class="col-12 col-lg">
                <div class="panel">
                    <small>Upcoming Payment</small>
                    <span class="fw-bold fs-6">May 5, 2025</span>
                </div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h2 class="card-title mb-0">Your Reserved Lots</h2>
                    </div>
                    <div class="card-body p-0 p-lg-3"> <div class="table-responsive-mobile">
                            <table class="custom-table table">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Area</th>
                                        <th>Block</th>
                                        <th>Row No.</th>
                                        <th>Lot No.</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Maria A. Erese</td>
                                        <td>Area A</td>
                                        <td>1</td>
                                        <td>1</td>
                                        <td>12</td>
                                        <td><span class="status completed">Paid</span></td>
                                    </tr>
                                    <tr>
                                        <td>Maria A. Erese</td>
                                        <td>Area B</td>
                                        <td>3</td>
                                        <td>1</td>
                                        <td>8</td>
                                        <td><span class="status pending">Partially Paid</span></td>
                                    </tr>
                                    <tr>
                                        <td>Maria A. Erese</td>
                                        <td>Area C</td>
                                        <td>5</td>
                                        <td>1</td>
                                        <td>22</td>
                                        <td><span class="status in-progress">Reserved</span></td>
                                    </tr>
                                    <tr>
                                        <td>Maria A. Erese</td>
                                        <td>Area D</td>
                                        <td>2</td>
                                        <td>1</td>
                                        <td>15</td>
                                        <td><span class="status completed">Paid</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="d-flex flex-column gap-4">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">Payment Progress</h2>
                            <p class="text-start mb-2 fw-medium">₱25,000 Paid / ₱40,000 Total</p>
                            <div class="progress" role="progressbar" aria-valuenow="62" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar fw-bold" style="width: 62%;">62%</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card scrollable-content">
                        <div class="card-header">
                            <h2 class="card-title">Active Service Requests</h2>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">Grass Trimming<span class="status in-progress">In Progress</span></li>
                                <li class="list-group-item">Repainting (Lot 12)<span class="status pending">Pending</span></li>
                                <li class="list-group-item">Cleaning (Lot 8)<span class="status completed">Completed</span></li>
                                <li class="list-group-item">Flower Planting<span class="status in-progress">In Progress</span></li>
                                <li class="list-group-item">Stone Repair (Lot 22)<span class="status pending">Pending</span></li>
                                <li class="list-group-item">Monument Polish<span class="status completed">Completed</span></li>
                                <li class="list-group-item">Fence Installation<span class="status pending">Pending</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer text-center">
        <div class="container-fluid">
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> | <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> | <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="../../environment.js"></script>
    <script src="signup.js"></script>
</body>
</html>
