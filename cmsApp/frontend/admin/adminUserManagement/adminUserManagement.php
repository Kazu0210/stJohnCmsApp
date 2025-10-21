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
    <title>User Management - BSJM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="adminUserManagement.css"> 
</head>
<body>
    <?php include '../components/adminNavbar.php'; ?>

    <main class="main-content">
        <!-- Key Metrics Section -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-2 p-md-3">
                        <i class="fas fa-users fs-4 text-primary mb-1"></i>
                        <h5 class="fw-bold mb-0" id="totalUsersMetric">0</h5>
                        <small class="text-muted fw-medium">Total</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-2 p-md-3">
                        <i class="fas fa-user-check fs-4 text-success mb-1"></i>
                        <h5 class="fw-bold mb-0" id="activeUsersMetric">0</h5>
                        <small class="text-muted fw-medium">Active</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-2 p-md-3">
                        <i class="fas fa-user-times fs-4 text-warning mb-1"></i>
                        <h5 class="fw-bold mb-0" id="inactiveUsersMetric">0</h5>
                        <small class="text-muted fw-medium">Inactive</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-2 p-md-3">
                        <div class="bg-warning text-dark rounded-circle p-1 d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px;">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h5 class="fw-bold mb-0" id="adminUsersMetric">0</h5>
                        <small class="text-muted fw-medium">Admins</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0">User Management</h5>
                <button id="addUserBtn" class="btn btn-gold btn-sm d-none d-md-inline-flex">
                    <i class="fas fa-user-plus me-2"></i>Add User
                </button>
            </div>
            <div class="card-body p-2 p-md-4">
                
                <!-- Mobile Add Button -->
                <div class="d-md-none mb-3">
                    <button id="addUserBtnMobile" class="btn btn-gold w-100">
                        <i class="fas fa-user-plus me-2"></i>Add New User
                    </button>
                </div>
                
                <!-- Filters Section -->
                <div class="row g-2 g-md-3 mb-3">
                    <div class="col-12 col-md-8">
                        <label class="form-label small d-none d-md-block">Search Users</label>
                        <div class="input-group">
                            <input type="text" id="userSearch" class="form-control" placeholder="Search users...">
                            <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" title="Clear Search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small d-none d-md-block">Role</label>
                        <select id="userRoleFilter" class="form-select">
                            <option value="All" selected>All Roles</option>
                            <option value="Admin">Admin</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Client">Client</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small d-none d-md-block">Status</label>
                        <select id="userStatusFilter" class="form-select">
                            <option value="All" selected>All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>

                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th class="text-center">Status</th>
                                <th>Last Login</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                            </tbody>
                    </table>
                    <p id="noUsersMessage" class="text-center p-4 d-none">No users found matching your criteria.</p>
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    <button id="prevPageBtn" class="btn btn-sm btn-outline-secondary me-2" disabled>Previous</button>
                    <span id="pageInfo" class="align-self-center fw-bold">Page 1 of 1</span>
                    <button id="nextPageBtn" class="btn btn-sm btn-outline-secondary ms-2" disabled>Next</button>
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

    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel"><i class="fas fa-user me-2"></i><span id="modalActionText">Create New User</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="userId">
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" required>
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" required>
                            <div class="invalid-feedback" id="emailFeedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">User Role</label>
                            <select id="role" class="form-select" required>
                                <option value="">Select a Role</option>
                                <option value="Admin">Admin</option>
                                <option value="Secretary">Secretary</option>
                                <option value="Client">Client</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Account Status</label>
                            <select id="status" class="form-select" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Archived">Archived</option>
                            </select>
                        </div>

                        <div id="creationPasswordFields">
                            <div class="mb-3">
                                <label for="password" class="form-label">Initial Password</label>
                                <input type="password" class="form-control" id="password" autocomplete="new-password">
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="userForm" class="btn btn-primary" id="saveUserBtn">Save User</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="passwordUpdateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Admin Password Update</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="passwordUpdateMessage" class="fw-bold text-danger">You are directly setting a new password for this user. Ensure they are immediately notified.</p>
                    <form id="passwordUpdateForm">
                        <input type="hidden" id="updateUserId">
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" required autocomplete="new-password">
                            <div class="invalid-feedback">Password cannot be empty.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmNewPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmNewPassword" required>
                            <div class="invalid-feedback" id="confirmNewPasswordFeedback">Passwords do not match.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="passwordUpdateForm" class="btn btn-danger" id="confirmPasswordUpdateBtn"><i class="fas fa-save me-2"></i>Update Password</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="archiveOrDeleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="archiveModalTitle"><i class="fas fa-archive me-2"></i>Choose an Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="archiveModalText">Do you want to **Archive** this account (making it inactive but retrievable) or **Delete** it permanently?</p>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <div>
                        <button type="button" class="btn btn-danger me-2" id="confirmDeleteBtn"><i class="fas fa-trash-alt me-2"></i>Delete</button>
                        <button type="button" class="btn btn-warning text-black" id="confirmArchiveBtn"><i class="fas fa-archive me-2"></i>Archive</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="adminUserManagement.js"></script>
</body>
</html>
