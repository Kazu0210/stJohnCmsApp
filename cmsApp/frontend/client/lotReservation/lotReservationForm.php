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
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
</head>
<body>

    <?php include dirname(__DIR__) . '/clientNavbar.php'; ?>

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
                                let html = '<table id="availableLotsTable" class="table table-bordered table-sm"><thead><tr><th>Block</th><th>Area</th><th>Row</th><th>Lot No.</th><th>Type</th><th>Status</th></tr></thead><tbody>';
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
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables integration script -->
    <script src="lotReservationForm-datatables.js"></script>
</body>
</html>