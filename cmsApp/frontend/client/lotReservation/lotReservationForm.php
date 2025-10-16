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
    $paymentType = trim($_POST['paymentType'] ?? '');
    // capture posted total amount (if provided). fallback to GET price if present, otherwise 0.0
    $totalAmount = null;
    if (isset($_POST['total_amount']) && $_POST['total_amount'] !== '') {
        // remove any non-digit except dot and minus
        $sanitized = preg_replace('/[^0-9.\-]/', '', $_POST['total_amount']);
        $totalAmount = $sanitized !== '' ? (float)$sanitized : 0.0;
    } elseif (isset($_GET['price']) && $_GET['price'] !== '') {
        $sanitized = preg_replace('/[^0-9.\-]/', '', $_GET['price']);
        $totalAmount = $sanitized !== '' ? (float)$sanitized : 0.0;
    } else {
        $totalAmount = 0.0;
    }

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
            // Lookup monthlyPayment for the selected lot type and set amount_due
            $amountDue = 0.0;
            if ($lotTypeId !== null) {
                $typeStmt = $conn->prepare("SELECT monthlyPayment FROM lot_types WHERE lotTypeId = ? LIMIT 1");
                if ($typeStmt) {
                    $typeStmt->bind_param('i', $lotTypeId);
                    $typeStmt->execute();
                    $typeRes = $typeStmt->get_result()->fetch_assoc();
                    if ($typeRes && isset($typeRes['monthlyPayment'])) {
                        $amountDue = (float)$typeRes['monthlyPayment'];
                    }
                    $typeStmt->close();
                }
            }
            $stmt = $conn->prepare("INSERT INTO reservations (
                userId, lotId, clientName, address, contactNumber, deceasedName, burialDate, reservationDate,
                area, block, rowNumber, lotNumber, burialDepth, notes, clientValidId, deathCertificate, deceasedValidId,
                burialPermit, payment_type, total_amount, status, createdAt, lotTypeId, amount_due
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "iisssssssssssssssssdssid",
                $userId, $lotId, $clientName, $address, $contactNumber, $deceasedName, $burialDate,
                $reservationDate, $area, $block, $rowNumber, $lotNumber, $burialDepth, $notes,
                $clientValidId, $deathCertificate, $deceasedValidId, $burialPermit, $paymentType, $totalAmount, $reservationStatus, $createdAt, $lotTypeId, $amountDue
            );
            if ($stmt->execute()) {
                // Update lot status to 'Pending'
                $updateLotStmt = $conn->prepare("UPDATE lots SET status = 'Pending' WHERE lotId = ?");
                $updateLotStmt->bind_param("i", $lotId);
                $updateLotStmt->execute();
                $updateLotStmt->close();
                // Redirect back to lotReservation listing after successful reservation
                // Add a success flag so the listing page can show feedback if desired
                $redirectUrl = '../lotReservation/lotReservation.php?success=1&lotId=' . urlencode($lotId);
                header('Location: ' . $redirectUrl);
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
                                <div>
                                    <button type="button" id="chooseAnotherLotBtn" class="btn btn-outline-primary me-2" title="Choose another lot on the map">
                                        <i class="fas fa-map-marker-alt me-2"></i>Choose another lot
                                    </button>
                                    <a href="lotReservation.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Packages
                                    </a>
                                </div>
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
                                        <?php
                                        // Fetch lot types to populate the select
                                        $lotTypes = [];
                                        // Include price so the option data-price can be populated for JS to read
                                        $typeQuery = $conn->prepare("SELECT lotTypeId, typeName, price FROM lot_types ORDER BY typeName ASC");
                                        if ($typeQuery) {
                                            $typeQuery->execute();
                                            $typeRes = $typeQuery->get_result();
                                            while ($r = $typeRes->fetch_assoc()) {
                                                $lotTypes[] = $r;
                                            }
                                            $typeQuery->close();
                                        }
                                        ?>
                                        <select id="preferred_lot" name="lotTypeId" class="form-select" required>
                                            <option value="">Select preferred lot type</option>
                                                <?php foreach ($lotTypes as $lt):
                                                    $optVal = htmlspecialchars($lt['lotTypeId']);
                                                    $optText = htmlspecialchars($lt['typeName']);
                                                    // try to include price if available
                                                    $optPrice = isset($lt['price']) ? htmlspecialchars($lt['price']) : '';
                                                    $sel = ($selectedLotType !== '' && (string)$selectedLotType === (string)$lt['lotTypeId']) ? 'selected' : '';
                                                ?>
                                                <option value="<?php echo $optVal; ?>" data-price="<?php echo $optPrice; ?>" <?php echo $sel; ?>><?php echo $optText; ?></option>
                                                <?php endforeach; ?>
                                        </select>
                                        <input type="text" id="preferred_lot_display" class="form-control mt-2" value="<?php echo htmlspecialchars($selectedPackage); ?>" readonly required>
                                        <input type="hidden" id="total_amount" name="total_amount" value="<?php echo $selectedPrice; ?>">
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
                                    <div class="col-md-6">
                                        <label for="paymentType" class="form-label">Payment Type</label>
                                        <select id="paymentType" name="paymentType" class="form-select">
                                            <option value="">Select payment type</option>
                                            <option value="full">Full Payment</option>
                                            <option value="installment">Exact Installment Amount</option>
                                            <option value="advance">Advance Payment</option>
                                            <option value="deferred">Deferred Amount</option>
                                        </select>
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
        // Set the preferred lot type select and sync a readonly display
        const lotTypeDisplay = document.getElementById('preferred_lot_display');
        const lotTypeSelect = document.getElementById('preferred_lot');
        if (lotTypeDisplay && lotTypeSelect) {
            // If the display is empty, try to set from PHP variables
            if (!lotTypeDisplay.value) {
                lotTypeDisplay.value = '<?php echo $selectedPackage; ?>';
            }
            // If PHP passed a selected lot type ID, set the select value
            try {
                const phpSelected = '<?php echo $selectedLotType; ?>';
                if (phpSelected && lotTypeSelect.querySelector(`option[value="${phpSelected}"]`)) {
                    lotTypeSelect.value = phpSelected;
                }
            } catch (e) { /* ignore */ }

            // Helper: set total_amount from selected option (data-price)
            const setTotalFromOption = (opt) => {
                try {
                    const totalInput = document.getElementById('total_amount');
                    if (!totalInput || !opt) return;
                    const p = opt.getAttribute('data-price') || '';
                    // Keep the existing value if data-price is empty; otherwise set it
                    if (p !== '') {
                        totalInput.value = p;
                    }
                } catch (e) { /* ignore */ }
            };

            // Sync display and total on change
            lotTypeSelect.addEventListener('change', () => {
                const opt = lotTypeSelect.options[lotTypeSelect.selectedIndex];
                if (opt) {
                    lotTypeDisplay.value = opt.text || opt.value || '';
                    setTotalFromOption(opt);
                }
                toggleDepthOption?.();
            });
            // Initialize display and total to selected option text/price if any
            const initOpt = lotTypeSelect.options[lotTypeSelect.selectedIndex];
            if (initOpt && initOpt.value) {
                lotTypeDisplay.value = initOpt.text || initOpt.value;
                setTotalFromOption(initOpt);
            }
        }
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

        // ====== AUTOFILL: Populate form from cemeteryMap selection ======
        try {
            const raw = localStorage.getItem('selectedLotData');
            if (raw) {
                const sel = JSON.parse(raw);
                // Fill basic fields if present
                if (sel.lotId) document.getElementById('lotId').value = sel.lotId;
                if (sel.area) document.getElementById('area').value = sel.area;
                if (sel.block) document.getElementById('block').value = sel.block;
                if (sel.rowNumber) document.getElementById('rowNumber').value = sel.rowNumber;
                if (sel.lotNumber) document.getElementById('lot_number').value = sel.lotNumber;
                // Map lotTypeId into the preferred lot select if available and trigger change
                if (sel.lotTypeId) {
                    const prefEl = document.getElementById('preferred_lot');
                    if (prefEl) {
                        try {
                            prefEl.value = sel.lotTypeId;
                            // dispatch change so the price/total and depth toggle logic run
                            prefEl.dispatchEvent(new Event('change'));
                        } catch (e) { /* ignore */ }
                    }
                }
                // Burial depth
                const depthVal = sel.buryDepth || sel.burialDepth;
                if (depthVal) {
                    const depthEl = document.getElementById('burial_depth');
                    if (depthEl) {
                        try { depthEl.value = depthVal; } catch (e) { /* ignore */ }
                    }
                }
                // Display a selected lot message
                const msg = document.getElementById('selectedLotMsg');
                if (msg) {
                    msg.textContent = `Selected Lot: Block ${sel.block || ''}, Area ${sel.area || ''}, Row ${sel.rowNumber || ''}, Lot No. ${sel.lotNumber || ''}`;
                    msg.classList.remove('d-none', 'alert-danger');
                    msg.classList.add('alert-success');
                }
            }
        } catch (err) {
            console.error('Failed to apply selectedLotData autofill:', err);
        }

        // Remove saved selection when the form is submitted so it doesn't persist
        const reservationFormEl = document.querySelector('.lot-reservation-form');
        if (reservationFormEl) {
            reservationFormEl.addEventListener('submit', function() {
                try { localStorage.removeItem('selectedLotData'); } catch (e) { /* ignore */ }
            });
        }

        // ====== DRAFT SAVE / BACK TO MAP ======
        // Restore draft if present (so user can navigate back and forth without losing inputs)
        try {
            const draftRaw = localStorage.getItem('reservationDraft');
            if (draftRaw) {
                const draft = JSON.parse(draftRaw);
                // Common fields - only set if element exists
                const setIf = (id, val) => { const el = document.getElementById(id); if (el && (el.value === '' || el.value == null)) el.value = val; };
                setIf('client_name', draft.client_name || '');
                setIf('client_address', draft.client_address || '');
                setIf('client_contact', draft.client_contact || '');
                setIf('deceased_name', draft.deceased_name || '');
                setIf('burial_date', draft.burial_date || '');
                setIf('reservation_date', draft.reservation_date || '');
                setIf('area', draft.area || '');
                setIf('block', draft.block || '');
                setIf('rowNumber', draft.rowNumber || '');
                setIf('lot_number', draft.lot_number || '');
                setIf('preferred_lot', draft.lotTypeId || '');
                setIf('burial_depth', draft.burial_depth || '');
                setIf('additional_notes', draft.additional_notes || '');
                setIf('paymentType', draft.paymentType || '');
                // After restoring, remove the draft so it doesn't reapply unintentionally
                try { localStorage.removeItem('reservationDraft'); } catch (e) { /* ignore */ }
            }
            // If the draft included a preferred lot, trigger change so price and depth logic run
            try {
                if (draft && draft.lotTypeId) {
                    const prefEl = document.getElementById('preferred_lot');
                    if (prefEl) prefEl.dispatchEvent(new Event('change'));
                }
            } catch (e) { /* ignore */ }
        } catch (e) {
            console.error('Failed to restore reservationDraft:', e);
        }

        // Wire the Choose another lot button to save current form values and navigate back to the map
        const chooseAnotherBtn = document.getElementById('chooseAnotherLotBtn');
        if (chooseAnotherBtn) {
            chooseAnotherBtn.addEventListener('click', function() {
                try {
                    const formEl = document.querySelector('.lot-reservation-form');
                    if (!formEl) return window.location.href = '../cemeteryMap/cemeteryMap.php';
                    const formData = new FormData(formEl);
                    const draft = {};
                    for (const [k, v] of formData.entries()) {
                        draft[k] = v;
                    }
                    // Save the draft
                    localStorage.setItem('reservationDraft', JSON.stringify(draft));
                } catch (err) {
                    console.error('Failed to save reservation draft:', err);
                }
                // Navigate to the client cemetery map
                window.location.href = '../cemeteryMap/cemeteryMap.php';
            });
        }
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