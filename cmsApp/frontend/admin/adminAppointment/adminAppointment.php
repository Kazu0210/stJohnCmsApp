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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - Blessed Saint John Memorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="adminAppointment.css"> 
</head>
<body>
    <?php include '../components/adminNavbar.php'; ?>
    <main class="main-content">
        <h1 class="mb-4 text-center text-md-start">Appointment Management</h1>

        <div class="row g-4 dashboard-cards mb-5">
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100 p-3 text-center">
                    <h5 class="card-title text-success fw-bold">Confirmed</h5>
                    <p id="confirmed-count" class="fs-1 fw-bolder" style="color: var(--success);">0</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100 p-3 text-center">
                    <h5 class="card-title text-warning fw-bold">Scheduled</h5>
                    <p id="scheduled-count" class="fs-1 fw-bolder" style="color: var(--pending);">0</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100 p-3 text-center">
                    <h5 class="card-title text-danger fw-bold">Cancelled</h5>
                    <p id="cancelled-count" class="fs-1 fw-bolder" style="color: var(--danger);">0</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100 p-3 text-center">
                    <h5 class="card-title text-secondary fw-bold">Completed</h5>
                    <p id="completed-count" class="fs-1 fw-bolder" style="color: var(--archived);">0</p>
                </div>
            </div>
        </div>

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
                        </tbody>
                    </table>
                </div>
                <p id="noLogsMessage" class="text-center p-4 d-none">No appointments found matching your criteria.</p>
            </div>
            
            <div class="card-footer d-flex justify-content-center justify-content-md-between align-items-center">
                <span class="text-muted d-none d-md-block">Showing <span id="recordCount">0</span> records</span>
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
                    <div class="mb-3">
                        <label for="newAppointmentTime" class="form-label">New Time</label>
                        <input type="time" class="form-control" id="newAppointmentTime" required>
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
    <script src="adminAppointment.js" type="module"></script>
</body>
</html>
