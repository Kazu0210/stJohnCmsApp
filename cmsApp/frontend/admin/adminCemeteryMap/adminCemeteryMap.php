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
    <link rel="stylesheet" href="adminCemeteryMap.css">
  </head>

  <body>
    <?php include '../components/adminNavbar.php'; ?>

    <!-- MAIN CONTENT (Unchanged) -->
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

        <div class="map-container-wrapper">
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

    <!-- EDIT LOT MODAL -->
    <div id="editLotModal" class="modal">
      <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Edit Lot Details</h2>
        <form id="editLotForm">
          <label for="editLotId">Lot ID</label>
          <input type="text" id="editLotId" name="lotId" readonly>

          <label for="editUserId">Assigned User:</label>
          <input type="text" id="editUserId" name="userId">

          <label for="editBlock">Block</label>
          <input type="text" id="editBlock" required>
          
          <label for="editArea">Area</label>
          <input type="text" id="editArea" required>

          <label for="editRowNum">Row Number</label>
          <input type="text" id="editRowNumber" name="rowNumber">

          <label for="editLotNum">Lot Number</label>
          <input type="text" id="editLotNumber" name="lotNumber">

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
          <select id="editStatus" required>
            <option value="Available">Available (Green)</option>
            <option value="Pending">Pending (Blue)</option>
            <option value="Reserved">Reserved (Yellow)</option>
            <option value="Occupied">Occupied (Red)</option>
          </select>

          <input type="hidden" id="editGeo" name="geo">

          <button type="submit" class="btn btn-update">Save Changes</button>
        </form>
      </div>
    </div>

    <!-- DELETE LOT MODAL -->
    <div id="deleteLotModal" class="modal">
      <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Confirm Deletion</h2>
        <p id="deleteConfirmationText">Are you sure you want to delete this lot? This action cannot be undone.</p>
        <div class="modal-actions">
          <button id="confirmDeleteBtn" class="btn btn-remove">Delete Lot</button>
          <button id="cancelDeleteBtn" class="btn btn-cancel">Cancel</button>
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
    <script src="adminCemeteryMap.js"></script>
  </body>
</html>