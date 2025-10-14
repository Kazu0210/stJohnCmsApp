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

// Get package details from URL parameters if available
$selectedPackage = isset($_GET['package']) ? htmlspecialchars($_GET['package']) : '';
$selectedPrice = isset($_GET['price']) ? htmlspecialchars($_GET['price']) : '';
$selectedMonthly = isset($_GET['monthly']) ? htmlspecialchars($_GET['monthly']) : '';
$selectedDetails = isset($_GET['details']) ? htmlspecialchars($_GET['details']) : '';
$selectedLotType = isset($_GET['lotType']) ? htmlspecialchars($_GET['lotType']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lot Reservation Form - Blessed Saint John Memorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="lotReservation.css">
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
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="lotReservation.php">Lot Reservation</a></li>
                    <li class="nav-item"><a class="nav-link" href="../payment/payment.php">Payment</a></li>
                    <li class="nav-item"><a class="nav-link" href="../burialRecord/burialRecord.php">Burial Record</a></li>
                    <li class="nav-item"><a class="nav-link" href="../maintenanceServiceRequest/maintenanceServiceRequest.php">Maintenance Request</a></li>
                </ul>
            
                <div class="dropdown d-none d-lg-block">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="user-name-display-desktop">
                            <?php 
                            // Display user name from session, fallback to email if name not available
                            if (isset($_SESSION['firstName']) && isset($_SESSION['lastName'])) {
                                echo htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName']);
                            } elseif (isset($_SESSION['username'])) {
                                echo htmlspecialchars($_SESSION['username']);
                            } else {
                                echo htmlspecialchars($_SESSION['email']);
                            }
                            ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="../../../../cms.api/logout.php" id="logoutLinkDesktop">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card p-4 mb-4 mt-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">Lot Reservation Form</h2>
                        <a href="lotReservation.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Packages
                        </a>
                    </div>

                    <?php if ($selectedPackage): ?>
                    <!-- Selected Package Summary -->
                    <div class="alert alert-info">
                        <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Selected Package</h5>
                        <p class="mb-2"><strong><?php echo $selectedPackage; ?></strong></p>
                        <?php
                        // Determine if package is Burial Lot or Mausoleum
                        $typeLabel = '';
                        $burialKeywords = ['Regular Lot', '4-Lot Package', 'Exhumation'];
                        $mausoleumKeywords = ['Mausoleum'];
                        foreach ($burialKeywords as $kw) {
                            if (stripos($selectedPackage, $kw) !== false) {
                                $typeLabel = 'Burial Lot';
                                break;
                            }
                        }
                        foreach ($mausoleumKeywords as $kw) {
                            if (stripos($selectedPackage, $kw) !== false) {
                                $typeLabel = 'Mausoleum';
                                break;
                            }
                        }
                        ?>
                        <?php if ($typeLabel): ?>
                        <p class="mb-2">Type: <span class="badge bg-primary"><?php echo $typeLabel; ?></span></p>
                        <?php endif; ?>
                        <p class="mb-2">Price: <strong><?php echo $selectedPrice; ?></strong></p>
                        <p class="mb-2">Monthly Payment: <strong><?php echo $selectedMonthly; ?></strong></p>
                        <p class="mb-0">Details: <?php echo $selectedDetails; ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Reservation form with available lots -->
                    <div class="mb-4">
                        <h4>Available Lots</h4>
                        <div id="availableLotsContainer" class="mb-3">
                            <div class="text-muted">Loading available lots...</div>
                        </div>
                    </div>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Determine the type from the selected package (PHP to JS)
                        let selectedType = '';
                        <?php
                        $typeLabel = '';
                        $burialKeywords = ['Regular Lot', '4-Lot Package', 'Exhumation'];
                        $mausoleumKeywords = ['Mausoleum'];
                        foreach ($burialKeywords as $kw) {
                            if (stripos($selectedPackage, $kw) !== false) {
                                $typeLabel = 'BurialLot';
                                break;
                            }
                        }
                        foreach ($mausoleumKeywords as $kw) {
                            if (stripos($selectedPackage, $kw) !== false) {
                                $typeLabel = 'Mausoleum';
                                break;
                            }
                        }
                        ?>
                        selectedType = '<?php echo $typeLabel; ?>';
                        fetch('/stJohnCmsApp/cms.api/get_lots.php?limit=1000')
                            .then(res => res.json())
                            .then(data => {
                                if (!data.success) throw new Error('Failed to fetch lots');
                                const lots = data.data.filter(lot => lot.type === selectedType);
                                const container = document.getElementById('availableLotsContainer');
                                if (lots.length === 0) {
                                    container.innerHTML = `<div class="text-danger">No available lots found for type: <b>${selectedType || 'N/A'}</b>.</div>`;
                                    return;
                                }
                                let html = '<table class="table table-bordered table-sm"><thead><tr><th>Block</th><th>Area</th><th>Row</th><th>Lot No.</th><th>Type</th><th>Status</th></tr></thead><tbody>';
                                lots.forEach(lot => {
                                    html += `<tr><td>${lot.block}</td><td>${lot.area}</td><td>${lot.rowNumber}</td><td>${lot.lotNumber}</td><td>${lot.type}</td><td>${lot.status}</td></tr>`;
                                });
                                html += '</tbody></table>';
                                container.innerHTML = html;
                            })
                            .catch(err => {
                                document.getElementById('availableLotsContainer').innerHTML = '<div class="text-danger">Error loading lots.</div>';
                            });
                    });
                    </script>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>