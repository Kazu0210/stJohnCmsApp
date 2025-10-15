<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
    header("Location: ../../auth/login/login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cmsdb";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle form submission
$reservationSuccess = false;
$reservationError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $clientName = trim($_POST['client_name'] ?? '');
    $address = trim($_POST['client_address'] ?? '');
    $contactNumber = trim($_POST['client_contact'] ?? '');
    $deceasedName = trim($_POST['deceased_name'] ?? '') ?: null;
    $burialDate = trim($_POST['burial_date'] ?? '') ?: null;
    $reservationDate = trim($_POST['reservation_date'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $block = trim($_POST['block'] ?? '');
    $rowNumber = trim($_POST['rowNumber'] ?? '');
    $lotNumber = trim($_POST['lot_number'] ?? '');
    $burialDepth = trim($_POST['burial_depth'] ?? '');
    $notes = trim($_POST['additional_notes'] ?? '');
    $lotTypeId = isset($_POST['lotTypeId']) && $_POST['lotTypeId'] !== '' ? (int)$_POST['lotTypeId'] : null;
    $lotId = trim($_POST['lotId'] ?? '');

    // Validate lotId
    if (empty($lotId) || !ctype_digit($lotId)) {
        $reservationError = 'You must select a valid lot before submitting the reservation.';
    } else {
        // File uploads
        function saveFileLocal($fileInputName, $uploadDir) {
            if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
                $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "_", basename($_FILES[$fileInputName]['name']));
                $targetPath = $uploadDir . $fileName;
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetPath)) {
                    return "uploads/" . $fileName;
                }
            }
            return null;
        }
        $uploadDir = dirname(__DIR__, 4) . '/cms.api/uploads/';
        $clientValidId = saveFileLocal('client_id_upload', $uploadDir);
        $deathCertificate = saveFileLocal('death_certificate_upload', $uploadDir);
        $deceasedValidId = saveFileLocal('deceased_id_upload', $uploadDir);
        $burialPermit = saveFileLocal('burial_permit_upload', $uploadDir);

        if (empty($clientName) || empty($address) || empty($contactNumber) || empty($reservationDate) || $lotTypeId === null || empty($clientValidId)) {
            $reservationError = 'Missing required fields or client valid ID.';
        } else {
            $reservationStatus = 'For Reservation';
            $createdAt = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO reservations (
                userId, lotId, clientName, address, contactNumber, deceasedName, burialDate, reservationDate,
                area, block, rowNumber, lotNumber, burialDepth, notes, clientValidId, deathCertificate, deceasedValidId,
                burialPermit, status, createdAt, lotTypeId
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "iissssssssssssssssssi",
                $userId, $lotId, $clientName, $address, $contactNumber, $deceasedName, $burialDate,
                $reservationDate, $area, $block, $rowNumber, $lotNumber, $burialDepth, $notes,
                $clientValidId, $deathCertificate, $deceasedValidId, $burialPermit, $reservationStatus, $createdAt, $lotTypeId
            );
            if ($stmt->execute()) {
                // Update lot status to 'Pending'
                $updateLotStmt = $conn->prepare("UPDATE lots SET status = 'Pending' WHERE lotId = ?");
                $updateLotStmt->bind_param("i", $lotId);
                $updateLotStmt->execute();
                $updateLotStmt->close();
                // Redirect to payment page with lotId
                header('Location: ../payment/payment.php?lotId=' . urlencode($lotId));
                exit();
            } else {
                $reservationError = 'Database error: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch user info from API (for pre-filling fields)
include_once(dirname(__DIR__, 4) . '/cms.api/fetchUserInfo.php');

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
            <div class="col-12 col-md-11 col-lg-10 px-1 px-sm-2">
                <?php if (isset($reservationSuccess) && $reservationSuccess): ?>
                    <div class="alert alert-success">Reservation submitted successfully!</div>
                <?php elseif (isset($reservationError) && $reservationError): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($reservationError); ?></div>
                <?php endif; ?>
                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="card p-2 p-md-3 h-100">
                            <h4 class="mb-3">Available Lots</h4>
                            <div class="mb-2">
                                <span class="fw-semibold text-primary"><i class="fas fa-mouse-pointer me-1"></i>Tap a row to select a lot and auto-fill the reservation form.</span>
                            </div>
                            <div class="table-responsive mb-2" style="overflow-x:auto;">
                                <div id="availableLotsContainer">
                                    <div class="text-muted">Loading available lots...</div>
                                </div>
                            </div>
                            <div id="selectedLotMsg" class="alert alert-success py-2 px-3 d-none" style="font-size:0.95rem;"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="card p-3 p-md-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2 class="mb-0">Lot Reservation Form</h2>
                                <a href="lotReservation.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Packages
                                </a>
                            </div>
                            <section class="lot-reservation-section card p-0 mb-0 border-0 shadow-none">
                                <form class="lot-reservation-form row g-3" method="POST" action="" enctype="multipart/form-data">
                                    <h3>Client Information</h3>
                                    <div class="col-md-6">
                                        <label for="client_name" class="form-label">Client Name: <span class="text-danger">*</span></label>
                                        <input type="text" id="client_name" name="client_name" class="form-control" required 
                                               value="<?php 
                                               // Pre-populate with session user name
                                               if (isset($_SESSION['firstName']) && isset($_SESSION['lastName'])) {
                                                   echo htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName']);
                                               } elseif (isset($_SESSION['username'])) {
                                                   echo htmlspecialchars($_SESSION['username']);
                                               } else {
                                                   echo htmlspecialchars($_SESSION['email']);
                                               }
                                               ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="client_address" class="form-label">Address: <span class="text-danger">*</span></label>
                                        <input type="text" id="client_address" name="client_address" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="client_contact" class="form-label">Contact Number: <span class="text-danger">*</span></label>
                                        <input type="text" id="client_contact" name="client_contact" class="form-control" required 
                                            value="<?php 
                                                if (isset($user['contactNumber']) && !empty($user['contactNumber'])) {
                                                 echo htmlspecialchars($user['contactNumber']);
                                                } elseif (isset($_SESSION['contactNumber'])) {
                                                 echo htmlspecialchars($_SESSION['contactNumber']);
                                                } else {
                                                 echo '';
                                                }
                                            ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="client_id_upload" class="form-label">Upload Client's Valid ID: <span class="text-danger">*</span></label>
                                        <style>
                                        .file-input-wrapper { position: relative; display: flex; align-items: center; }
                                        .file-upload-label {
                                            background: #f8f9fa;
                                            border: 1px solid #ced4da;
                                            padding: 6px 12px;
                                            border-radius: 4px;
                                            cursor: pointer;
                                            margin-right: 10px;
                                        }
                                        .file-input-wrapper input[type="file"] {
                                            position: absolute;
                                            left: 0;
                                            top: 0;
                                            width: 100%;
                                            height: 100%;
                                            opacity: 0;
                                            cursor: pointer;
                                            z-index: 2;
                                        }
                                        .file-name { margin-left: 8px; font-size: 0.95em; color: #6c757d; }
                                        </style>
                                        <div class="file-input-wrapper">
                                            <label class="file-upload-label" for="client_id_upload">
                                                <i class="fas fa-upload"></i> Choose File
                                            </label>
                                            <input type="file" id="client_id_upload" name="client_id_upload" accept="image/*,application/pdf" required>
                                            <span class="file-name" id="client_id_upload_filename">No file chosen</span>
                                            <div class="file-actions" data-target="client_id_upload">
                                                <i class="fas fa-eye view-icon" title="View"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <h3>Deceased Person's Information (Optional for advance reservation)</h3>
                                    <div class="col-md-6">
                                        <label for="deceased_name" class="form-label">Deceased Person's Name:</label>
                                        <input type="text" id="deceased_name" name="deceased_name" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="burial_date" class="form-label">Burial Date:</label>
                                        <input type="date" id="burial_date" name="burial_date" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="death_certificate_upload" class="form-label">Upload Death Certificate:</label>
                                        <div class="file-input-wrapper">
                                            <label class="file-upload-label" for="death_certificate_upload">
                                                <i class="fas fa-upload"></i> Choose File
                                            </label>
                                            <input type="file" id="death_certificate_upload" name="death_certificate_upload" accept="image/*,application/pdf">
                                            <span class="file-name" id="death_certificate_upload_filename">No file chosen</span>
                                            <div class="file-actions" data-target="death_certificate_upload">
                                                <i class="fas fa-eye view-icon" title="View"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="deceased_id_upload" class="form-label">Upload Deceased's Valid ID (optional):</label>
                                        <div class="file-input-wrapper">
                                            <label class="file-upload-label" for="deceased_id_upload">
                                                <i class="fas fa-upload"></i> Choose File
                                            </label>
                                            <input type="file" id="deceased_id_upload" name="deceased_id_upload" accept="image/*,application/pdf">
                                            <span class="file-name" id="deceased_id_upload_filename">No file chosen</span>
                                            <div class="file-actions" data-target="deceased_id_upload">
                                                <i class="fas fa-eye view-icon" title="View"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="burial_permit_upload" class="form-label">Upload Burial Permit:</label>
                                        <div class="file-input-wrapper">
                                            <label class="file-upload-label" for="burial_permit_upload">
                                                <i class="fas fa-upload"></i> Choose File
                                            </label>
                                            <input type="file" id="burial_permit_upload" name="burial_permit_upload" accept="image/*,application/pdf">
                                            <span class="file-name" id="burial_permit_upload_filename">No file chosen</span>
                                            <div class="file-actions" data-target="burial_permit_upload">
                                                <i class="fas fa-eye view-icon" title="View"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <h3>Reservation Details</h3>
                                    <div class="col-md-6">
                                        <label for="reservation_date" class="form-label">Date of Reservation: <span class="text-danger">*</span></label>
                                        <input type="date" id="reservation_date" name="reservation_date" class="form-control" required>
                                    </div>
                                    <input type="hidden" id="lotId" name="lotId" value="">
                                    <div class="col-md-6">
                                        <label for="area" class="form-label">Area: </label>
                                        <input type="text" id="area" name="area" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="block" class="form-label">Block:</label>
                                        <input type="text" id="block" name="block" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="rowNumber" class="form-label">Row Number:</label>
                                        <input type="text" id="rowNumber" name="rowNumber" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lot_number" class="form-label">Lot Number:</label>
                                        <input type="text" id="lot_number" name="lot_number" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="preferred_lot" class="form-label">Preferred Lot Type: <span class="text-danger">*</span></label>
                                        <select id="preferred_lot" name="lotTypeId" class="form-select" required>
                                            <option value="" selected disabled>-- Select Lot Type --</option>
                                            <option value="1">Regular Lot (₱50,000)</option>
                                            <option value="2">Regular Lot (₱60,000)</option>
                                            <option value="3">Premium Lot (₱70,000)</option>
                                            <option value="4">Mausoleum Inside (₱500,000)</option>
                                            <option value="5">Mausoleum Roadside (₱600,000)</option>
                                            <option value="6">4-Lot Package (₱300,000)</option>
                                            <option value="7">Exhumation (₱15,000)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="depth_option">
                                        <label for="burial_depth" class="form-label">Burial Depth: <span class="text-danger">*</span></label>
                                        <select id="burial_depth" name="burial_depth" class="form-select">
                                            <option value="4ft" selected>4 feet</option>
                                            <option value="6ft">6 feet</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="additional_notes" class="form-label">Additional Notes:</label>
                                        <textarea id="additional_notes" name="additional_notes" rows="3" class="form-control"></textarea>
                                    </div>
                                    <div class="col-12 text-center">
                                        <button type="submit" class="submit-btn btn">Submit Reservation Request</button>
                                    </div>
                                </form>
                            </section>
                        </div>
                    </div>
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
                    container.innerHTML = `<div class=\"text-danger\">No available lots found for type: <b>${selectedType || 'N/A'}</b>.</div>`;
                    return;
                }
                let html = '<table id="availableLotsTable" class="table table-bordered table-sm lot-table-enhanced"><thead><tr><th>Block</th><th>Area</th><th>Row</th><th>Lot No.</th><th>Type</th><th>Status</th></tr></thead><tbody>';
                lots.forEach((lot, idx) => {
                    // Add a class if not available
                    const rowClass = lot.status !== 'Available' ? 'table-danger not-available' : '';
                    html += `<tr data-idx="${idx}" data-lotid="${lot.lotId}" class="${rowClass}"><td>${lot.block}</td><td>${lot.area}</td><td>${lot.rowNumber}</td><td>${lot.lotNumber}</td><td>${lot.type}</td><td>${lot.status}</td></tr>`;
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
                        // Auto-fill form fields
                        document.getElementById('block').value = lots[idx].block;
                        document.getElementById('rowNumber').value = lots[idx].rowNumber;
                        document.getElementById('lot_number').value = lots[idx].lotNumber;
                        // Set the hidden lotId input
                        document.getElementById('lotId').value = lots[idx].lotId;
                    });
                });
            })
            .catch(err => {
                document.getElementById('availableLotsContainer').innerHTML = '<div class="text-danger">Error loading lots.</div>';
            });

        // Reservation Form AJAX Submission removed. The form now submits directly to this PHP file.
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