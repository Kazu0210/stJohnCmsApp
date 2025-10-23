<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="initial-scale=1,user-scalable=no,maximum-scale=1,width=device-width">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <title>Cemetery Map Management</title>

    <link rel="stylesheet" href="./resources/ol.css">
    <link rel="stylesheet" href="resources/fontawesome-all.min.css">
    <link href="resources/photon-geocoder-autocomplete.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./resources/ol-layerswitcher.css">
    <link rel="stylesheet" href="./resources/qgis2web.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="cemeteryMap.css">
</head>
<body class="bg-light">

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
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="cemeteryMap.php">Cemetery Map</a></li>
                    <li class="nav-item"><a class="nav-link" href="../lotReservation/lotReservation.php">Lot Reservation</a></li>
                    <li class="nav-item"><a class="nav-link" href="../payment/payment.php">Payment</a></li>
                    <li class="nav-item"><a class="nav-link" href="../burialRecord/burialRecord.php">Burial Record</a></li>
                    <li class="nav-item"><a class="nav-link" href="../maintenanceServiceRequest/maintenanceServiceRequest.php">Maintenance Request</a></li>
                </ul>
    
                <div class="d-lg-none mt-3 pt-3 border-top border-dark-subtle">
                    <div class="d-flex align-items-center mb-2">
                        <span id="user-name-display-mobile" class="fw-bold">Maria Anjelika Erese</span>
                    </div>
                    <a href="../auth/login/login.php" id="logoutLinkMobile" class="mobile-logout-link">
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

    <main class="main-content">
        <div class="page-header">
            <h1>Cemetery Map Management</h1>
            <p>Manage and view lot statuses on the interactive map.</p>
        </div>
        
        <div class="map-section">
            <div class="map-legend-container">
                <h4>Legend:</h4>
                <ul>
                    <li><span class="legend-color available"></span> Available</li><li><span class="legend-color pending"></span> Pending</li><li><span class="legend-color reserved"></span> Reserved</li><li><span class="legend-color occupied"></span> Occupied</li>
                </ul>
            </div>

            <div class="map-container-wrapper" style="width: 100%; height: 100%;">
                <div class="map-placeholder" id="cemeteryMap">
                    <div id="map">
                        <div id="popup" class="ol-popup">
                            <a href="#" id="popup-closer" class="ol-popup-closer"></a>
                            <div id="popup-content"></div>
                        </div>
                    </div>
                    <button id="expandMapBtn" class="expand-btn"><i class="fas fa-expand"></i> Expand Map</button>
                </div>

                <div class="lot-actions-panel">
                    <h3>Lot Management</h3>

                    <div class="lot-list-header">
                        <h4>Existing Lots</h4>
                        <input type="text" id="lotSearch" placeholder="Search by Lot ID, Block, Area...">
                    </div>
                    
                    <div id="lotList" class="lot-list"></div>
                    <div class="pagination-controls">
                        <button id="prevPageBtn" class="pagination-btn">&laquo; Previous</button>
                        <span id="pageInfo">Page 1 of 1</span>
                        <button id="nextPageBtn" class="pagination-btn">Next &raquo;</button>
                    </div>
                    <p class="note"><strong>Note:</strong> All status changes are verified and reflected system-wide across dashboards.</p>
                </div>
            </div>
        </div>
    </main>

    <div id="editLotModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Lot Details</h2>
            <form id="editLotForm">
                <label for="editLotId">Lot ID</label>
                <input type="text" id="editLotId" name="lotId" readonly>

                <label for="editBlock">Block</label>
                <input type="text" id="editBlock" required readonly>
                
                <label for="editArea">Area</label>
                <input type="text" id="editArea" required readonly>

                <label for="editRowNum">Row Number</label>
                <input type="text" id="editRowNumber" name="rowNumber" readonly>

                <label for="editLotNum">Lot Number</label>
                <input type="text" id="editLotNumber" name="lotNumber" readonly>

                <label for="editLotType">Type</label>
                <select id="editLotType" required>
                    <option value="1">Regular Lot (₱50,000)</option>
                    <option value="2">Regular Lot (₱60,000)</option>
                    <option value="3">Premium Lot (₱70,000)</option>
                    <option value="4">Mausoleum Inside (₱500,000)</option>
                    <option value="5">Mausoleum Roadside (₱600,000)</option>
                    <option value="6">4-Lot Package (₱300,000)</option>
                    <option value="7">Exhumation (₱15,000)</option>
                </select>

                <label for="editDepth">Burial Depth</label>
                <select id="editDepth" required>
                    <option value="4ft">4ft</option>
                    <option value="6ft">6ft</option>
                </select>

                <label for="editStatus">Status</label>
                <input id="editStatus" readonly>

                <input type="hidden" id="editGeo" name="geo">
                <input type="hidden" id="editUserId" name="userId">

                <button type="submit" class="btn btn-update">Reserve Now</button>
            </form>
        </div>
    </div>
    
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
        <div id="liveToast" class="toast hide bg-light" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-clock rounded me-2"></i>
                <strong class="me-auto">Your Lot Reservation Timer</strong>
                <small class="toast-time">Counting down...</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Time remaining will be displayed here.
            </div>
        </div>
    </div>
    <footer class="footer text-center py-3">
        <div class="container d-flex flex-column flex-md-row justify-content-center align-items-center">
            <p class="m-0">
                <strong>Blessed Saint John Memorial</strong> |
                <i class="fas fa-envelope"></i> <a href="mailto:saintjohnmp123@gmail.com">saintjohnmp123@gmail.com</a> |
                <i class="fas fa-phone"></i> <a href="tel:+639978442421">+63 997 844 2421</a>
            </p>
        </div>
    </footer>

    <script src="resources/qgis2web_expressions.js"></script>
    <script src="./resources/functions.js"></script>
    <script src="./resources/ol.js"></script>
    <script src="./resources/ol-layerswitcher.js"></script>
    <script src="resources/photon-geocoder-autocomplete.min.js"></script>
    <script src="layers/nondescriptbuildings_1.js"></script><script src="layers/geo_2.js"></script>
    <script src="styles/nondescriptbuildings_1_style.js"></script><script src="styles/geo_2_style.js"></script>
    <script src="./layers/layers.js" type="text/javascript"></script> 
    <script src="./resources/Autolinker.min.js"></script>
    <script src="./resources/qgis2web.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="cemeteryMap.js"></script>
</body>
</html>