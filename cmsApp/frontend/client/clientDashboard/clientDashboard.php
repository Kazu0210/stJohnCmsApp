<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    // User is not logged in, redirect to login page
    header("Location: ../../auth/login/login.php");
    exit();
}

// Optional: You can also check user role if needed
// if ($_SESSION['role'] !== 'client') {
//     header("Location: ../../auth/login/login.php");
//     exit();
// }
?>
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
    <?php include dirname(__DIR__) . '/clientNavbar.php'; ?>

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
                                    <!-- Data will be populated by JavaScript -->
                                    <tr>
                                        <td colspan="6" class="text-center">Loading...</td>
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
                            <ul class="list-group list-group-flush" id="serviceRequestsList">
                                <li class="list-group-item text-center">Loading...</li>
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

    <script>
        window.userId = <?php echo json_encode($_SESSION['user_id']); ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
