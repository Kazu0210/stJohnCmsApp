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

<body class="bg-light" style="min-height:100vh;">

    <?php include dirname(__DIR__) . '/clientNavbar.php'; ?>


    <main class="main-content container-fluid px-0 px-md-2">
        <div class="row justify-content-center mt-4 mx-0">
            <div class="col-12 col-md-11 col-lg-8 px-1 px-sm-2">
                <?php if ($selectedPackage): ?>
                <div class="alert alert-info mb-4 mx-1 mx-sm-0">
                    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Selected Package</h5>
                    <p class="mb-2"><strong><?php echo $selectedPackage; ?></strong></p>
                    <?php
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
                <div class="card p-3 p-md-4 mb-4 mx-1 mx-sm-0">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">Lot Reservation Form</h2>
                        <a href="lotReservation.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Packages
                        </a>
                    </div>
                    <form id="reservationForm" class="row g-3">
                        <h5 class="mb-3">Client Information</h5>
                        <!-- ...existing form fields... -->
                        <!-- You can add helper text or icons here for clarity -->
                    </form>
                </div>
                <div class="card p-2 p-md-3 mx-1 mx-sm-0">
                    <h4 class="mb-3">Available Lots</h4>
                    <div class="mb-2">
                        <span class="fw-semibold text-primary"><i class="fas fa-mouse-pointer me-1"></i>Tap a row to select a lot and auto-fill the reservation form above.</span>
                    </div>
                    <div class="table-responsive mb-2" style="overflow-x:auto;">
                        <div id="availableLotsContainer">
                            <div class="text-muted">Loading available lots...</div>
                        </div>
                    </div>
                    <div id="selectedLotMsg" class="alert alert-success py-2 px-3 d-none" style="font-size:0.95rem;"></div>
                </div>
            </div>
        </div>
    </main>

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
                let html = '<table id="availableLotsTable" class="table table-bordered table-sm lot-table-enhanced"><thead><tr><th>Block</th><th>Area</th><th>Row</th><th>Lot No.</th><th>Type</th><th>Status</th></tr></thead><tbody>';
                lots.forEach((lot, idx) => {
                    // Add a class if not available
                    const rowClass = lot.status !== 'Available' ? 'table-danger not-available' : '';
                    html += `<tr data-idx="${idx}" class="${rowClass}"><td>${lot.block}</td><td>${lot.area}</td><td>${lot.rowNumber}</td><td>${lot.lotNumber}</td><td>${lot.type}</td><td>${lot.status}</td></tr>`;
                });
                html += '</tbody></table>';
                container.innerHTML = html;

                // Add row interactivity
                const table = document.getElementById('availableLotsTable');
                let selectedRow = null;
                table.querySelectorAll('tbody tr').forEach((row, idx) => {
                    row.style.cursor = 'pointer';
                    row.addEventListener('mouseenter', function() {
                        if (row !== selectedRow) row.classList.add('table-active');
                    });
                    row.addEventListener('mouseleave', function() {
                        if (row !== selectedRow) row.classList.remove('table-active');
                    });
                    row.addEventListener('click', function() {
                        if (lots[idx].status !== 'Available') {
                            // Show error message
                            const msg = document.getElementById('selectedLotMsg');
                            msg.textContent = `This lot is not available for reservation.`;
                            msg.classList.remove('d-none');
                            msg.classList.remove('alert-success');
                            msg.classList.add('alert-danger');
                            return;
                        }
                        if (selectedRow) selectedRow.classList.remove('table-success');
                        if (selectedRow && selectedRow !== row) selectedRow.classList.remove('table-active');
                        row.classList.add('table-success');
                        selectedRow = row;
                        // Show message
                        const msg = document.getElementById('selectedLotMsg');
                        msg.textContent = `Selected Lot: Block ${lots[idx].block}, Area ${lots[idx].area}, Row ${lots[idx].rowNumber}, Lot No. ${lots[idx].lotNumber}`;
                        msg.classList.remove('d-none', 'alert-danger');
                        msg.classList.add('alert-success');
                        // TODO: Auto-fill form fields here if needed
                    });
                });
            })
            .catch(err => {
                document.getElementById('availableLotsContainer').innerHTML = '<div class="text-danger">Error loading lots.</div>';
            });
    });
    </script>

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