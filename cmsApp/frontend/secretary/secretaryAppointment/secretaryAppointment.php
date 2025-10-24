<?php
// Include authentication helper
require_once '../../../../cms.api/auth_helper.php';

// Require authentication - redirect to login if not logged in
requireAuth('../../auth/login/login.php');

// Require admin or secretary role for this page
requireAdminOrSecretary('../../auth/login/login.php');

// Get current user information
$userId = getCurrentUserId();
$userName = getCurrentUserName();
$userRole = getCurrentUserRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="secretaryAppointment.css"> 
  <title>Appointment Setup - BSJM</title>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top navbar-custom"> 
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">Blessed Saint John Memorial</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="../secretaryDashboard/secretaryDashboard.php">Home</a></li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="managementDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Management
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="managementDropdown">
                            <li><a class="dropdown-item active" href="../secretaryAppointment/secretaryAppointment.php">Appointment Setup</a></li>
                            <li><a class="dropdown-item" href="../secretaryCemeteryMap/secretaryCemeteryMap.php">Cemetery Map Status</a></li>
                            <li><a class="dropdown-item" href="../secretaryReservation/secretaryReservation.php">Reservation Processing</a></li>
                            <li><a class="dropdown-item" href="../secretaryPayment/secretaryPayment.php">Payment Recording</a></li>
                            <li><a class="dropdown-item" href="../secretaryBurial/secretaryBurial.php">Burial Record Entry</a></li>
                            <li><a class="dropdown-item" href="../secretaryMaintenance/secretaryMaintenance.php">Maintenance and Request</a></li>
                        </ul>
                    </li>
                </ul>

                <div class="d-lg-none mt-3 pt-3 border-top border-dark-subtle">
                    <div class="d-flex align-items-center mb-2">
                        <span id="user-name-display-mobile" class="fw-bold"><?php echo htmlspecialchars($userName); ?></span>
                        <small class="text-muted ms-2">(<?php echo htmlspecialchars($userRole); ?>)</small>
                    </div>
                    <a href="../../../../cms.api/logout.php" id="logoutLinkMobile" class="mobile-logout-link">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>

                <div class="dropdown d-none d-lg-flex">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="user-name-display-desktop"><?php echo htmlspecialchars($userName); ?></span>
                        <small class="text-muted ms-2">(<?php echo htmlspecialchars($userRole); ?>)</small>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="../../../../cms.api/logout.php" id="logoutLinkDesktop"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content">

        <div class="card">
            <div class="card-header d-flex align-items-center">
                <i class="fas fa-calendar-check me-2"></i> Appointment Records
            </div>
            
            <div class="card-body pb-0">
                <div class="row g-3 mb-4 align-items-center">
                    <div class="col-lg-4 col-md-6">
                        <input type="text" id="appointmentSearch" class="form-control" placeholder="Search by Client Name or Purpose..." oninput="filterAppointments()">
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            <input type="date" id="filterDate" class="form-control" title="Filter by Date" onchange="filterAppointments()">
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <select id="filterStatus" class="form-select" onchange="filterAppointments()">
                            <option value="all">Status (All)</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 text-end">
                        <button class="btn btn-sm filter-btn" id="clearFiltersBtn" onclick="clearFilters()"><i class="fas fa-undo me-1"></i> Clear Filters</button>
                    </div>
                </div>
            </div>

            <div class="p-0">
                <div class="table-responsive rounded">
                    <table class="table table-hover appointment-table">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Contact Info</th>
                                <th>Appointment Details</th>
                                <th>Purpose</th>
                                <th>Submitted On</th>
                                <th>Status</th>
                                <th>Actions</th>
                                <th>Internal Notes</th>
                            </tr>
                        </thead>
                        <tbody id="appointmentTableBody">
                            <tr>
                                <td><span class="fw-bold">Miriam Vega</span></td>
                                <td>
                                    <i class="fas fa-phone fa-fw me-1"></i>+63952382944<br>
                                    <i class="fas fa-map-marker-alt fa-fw me-1"></i>8, first street, Subd, SV
                                </td>
                                <td>
                                    <span class="fw-bold">Date:</span> Oct 25, 2023<br>
                                    <span class="fw-bold">Time:</span> 2:00 PM
                                </td>
                                <td><span class="text-muted">Inquire about plot pricing and maintenance fees.</span></td>
                                <td>Oct 20, 2023, 4:08 PM</td>
                                <td><span class="status-display confirmed">Confirmed</span></td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-info text-white" title="Reschedule" data-bs-toggle="modal" data-bs-target="#rescheduleModal"><i class="fas fa-clock"></i></button>
                                    <button class="btn btn-sm btn-success text-white" title="Mark Completed"><i class="fas fa-check"></i></button>
                                    <button class="btn btn-sm btn-danger text-white" title="Cancel Appointment"><i class="fas fa-times"></i></button>
                                </td>
                                <td><textarea class="form-control internal-note" placeholder="Add note..."></textarea></td>
                            </tr>
                            <tr>
                                <td><span class="fw-bold">John Doe</span></td>
                                <td>
                                    <i class="fas fa-envelope fa-fw me-1"></i>johndoe@example.com<br>
                                    <i class="fas fa-map-marker-alt fa-fw me-1"></i>12 Cemetery Rd, Tarlac City
                                </td>
                                <td>
                                    <span class="fw-bold">Date:</span> Oct 26, 2023<br>
                                    <span class="fw-bold">Time:</span> 10:30 AM
                                </td>
                                <td><span class="text-muted">Viewing of Plot D-15.</span></td>
                                <td>Oct 22, 2023, 11:15 AM</td>
                                <td><span class="status-display scheduled">Scheduled</span></td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-info text-white" title="Reschedule" data-bs-toggle="modal" data-bs-target="#rescheduleModal"><i class="fas fa-clock"></i></button>
                                    <button class="btn btn-sm btn-success text-white" title="Mark Completed"><i class="fas fa-check"></i></button>
                                    <button class="btn btn-sm btn-danger text-white" title="Cancel Appointment"><i class="fas fa-times"></i></button>
                                </td>
                                <td><textarea class="form-control internal-note" placeholder="Add note..."></textarea></td>
                            </tr>
                            </tbody>
                    </table>
                </div>
                <p id="noLogsMessage" class="text-center p-4 d-none">No appointments found matching your criteria.</p>
            </div>
            
            <div class="card-footer d-flex justify-content-center justify-content-md-between align-items-center">
                <span class="text-muted d-none d-md-block">Showing <span id="recordCount">2</span> records</span>
                <div class="d-flex">
                    <button id="prevPageBtn" class="btn btn-sm btn-outline-secondary me-2 rounded-pill" disabled onclick="changePage(-1)">&laquo; Previous</button>
                    <span id="pageInfo" class="align-self-center fw-bold text-dark">Page 1 of 1</span>
                    <button id="nextPageBtn" class="btn btn-sm btn-outline-secondary ms-2 rounded-pill" disabled onclick="changePage(1)">Next &raquo;</button>
                </div>
            </div>
        </div>
    </main>
    <div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rescheduleModalLabel">Reschedule Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Rescheduling appointment for: <strong id="rescheduleClientName"></strong></p>
                    <div class="mb-3">
                        <label for="newAppointmentDate" class="form-label">New Date</label>
                        <input type="date" class="form-control" id="newAppointmentDate" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label for="newAppointmentStart" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="newAppointmentStart" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="newAppointmentEnd" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="newAppointmentEnd" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveRescheduleBtn">Save New Schedule</button>
                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="secretaryAppointment.js"></script>
</body>
</html>