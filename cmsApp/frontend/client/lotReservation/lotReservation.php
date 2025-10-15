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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lot Reservation - Blessed Saint John Memorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="lotReservation.css">
</head>
<body>

    <?php include dirname(__DIR__) . '/clientNavbar.php'; ?>

    <main class="main-content container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="pricing-container card p-4 mb-4">
                    <h2>Cemetery Lot and Mausoleum Options</h2>
                    <p class="payment-terms">All lots are payable within 4 years and 2 months (50 months) and can be paid monthly or in advance.</p>
                
                    <div class="container my-5">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-dark">Burial Lots</h3>
                            <p class="text-muted">Choose the best lot that fits your needs and budget.</p>
                        </div>

                        <div class="row g-4">
                            <!-- Regular Lot - 50,000 - Silver Theme -->
                            <div class="col-md-4">
                                <div class="card shadow-sm border-0 h-100" style="background-color: #f8f9fa; border: 2px solid #adb5bd !important;">
                                    <div class="card-body text-center">
                                        <h5 class="card-title fw-bold" style="color: #495057;">Regular Lot</h5>
                                        <p class="card-text fs-5 fw-semibold mb-2" style="color: #343a40;">₱50,000</p>
                                        <p class="mb-2" style="color: #6c757d;">₱1,000/month</p>
                                        <p class="mb-3" style="color: #6c757d;">Depth: 4 feet or 6 feet</p>
                                        <button class="btn w-100 package-select-btn" 
                                                data-package="Regular Lot (₱50,000)"
                                                data-price="₱50,000"
                                                data-monthly="₱1,000/month"
                                                data-details="Depth: 4 feet or 6 feet"
                                                data-lot-type="1"
                                                style="background-color: #6c757d; color: white; border: none;">Select</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Regular Lot - 60,000 - Silver Theme -->
                            <div class="col-md-4">
                                <div class="card shadow-sm border-0 h-100" style="background-color: #f8f9fa; border: 2px solid #adb5bd !important;">
                                    <div class="card-body text-center">
                                        <h5 class="card-title fw-bold" style="color: #495057;">Regular Lot</h5>
                                        <p class="card-text fs-5 fw-semibold mb-2" style="color: #343a40;">₱60,000</p>
                                        <p class="mb-2" style="color: #6c757d;">₱1,200/month</p>
                                        <p class="mb-3" style="color: #6c757d;">Depth: 4 feet or 6 feet</p>
                                        <button class="btn w-100 package-select-btn" 
                                                data-package="Regular Lot (₱60,000)"
                                                data-price="₱60,000"
                                                data-monthly="₱1,200/month"
                                                data-details="Depth: 4 feet or 6 feet"
                                                data-lot-type="2"
                                                style="background-color: #6c757d; color: white; border: none;">Select</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Premium Lot - Gold Theme -->
                            <div class="col-md-4">
                                <div class="card shadow-sm border-0 h-100" style="background-color: #fff3cd; border: 2px solid #d4a574 !important;">
                                    <div class="card-body text-center">
                                        <h5 class="card-title fw-bold" style="color: #b8860b;">Premium Lot</h5>
                                        <p class="card-text fs-5 fw-semibold mb-2" style="color: #856404;">₱70,000</p>
                                        <p class="mb-2" style="color: #b8860b;">₱1,400/month</p>
                                        <p class="mb-3" style="color: #b8860b;">Depth: 4 feet or 6 feet</p>
                                        <button class="btn w-100 package-select-btn" 
                                                data-package="Premium Lot (₱70,000)"
                                                data-price="₱70,000"
                                                data-monthly="₱1,400/month"
                                                data-details="Depth: 4 feet or 6 feet"
                                                data-lot-type="3"
                                                style="background-color: #daa520; color: white; border: none;">Select</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container my-5">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-dark">Mausoleums</h3>
                            <p class="text-muted">Premium mausoleum options for lasting memorials.</p>
                        </div>

                        <div class="row g-4 justify-content-center">
                            <!-- Mausoleum Inside Cemetery - Premium Gold Theme -->
                            <div class="col-md-6">
                                <div class="card shadow-sm border-0 h-100" style="background-color: #fff3cd; border: 2px solid #d4a574 !important;">
                                    <div class="card-body text-center">
                                        <h5 class="card-title fw-bold" style="color: #b8860b;">Mausoleum - Inside Cemetery</h5>
                                        <p class="card-text fs-5 fw-semibold mb-2" style="color: #856404;">₱500,000</p>
                                        <p class="mb-2" style="color: #b8860b;">₱10,000/month</p>
                                        <p class="mb-3" style="color: #b8860b;">Dimensions: 5x4 sqm</p>
                                        <button class="btn w-100 package-select-btn" 
                                                data-package="Mausoleum - Inside Cemetery"
                                                data-price="₱500,000"
                                                data-monthly="₱10,000/month"
                                                data-details="Dimensions: 5x4 sqm"
                                                data-lot-type="4"
                                                style="background-color: #daa520; color: white; border: none;">Select</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Mausoleum Along Road - Luxury Gold Theme -->
                            <div class="col-md-6">
                                <div class="card shadow-sm border-0 h-100" style="background-color: #ffd700; border: 2px solid #cd853f !important;">
                                    <div class="card-body text-center">
                                        <h5 class="card-title fw-bold" style="color: #8b4513;">Mausoleum - Roadside</h5>
                                        <p class="card-text fs-5 fw-semibold mb-2" style="color: #654321;">₱600,000</p>
                                        <p class="mb-2" style="color: #8b4513;">₱12,000/month</p>
                                        <p class="mb-3" style="color: #8b4513;">Dimensions: 5x4 sqm</p>
                                        <button class="btn w-100 package-select-btn" 
                                                data-package="Mausoleum - Roadside"
                                                data-price="₱600,000"
                                                data-monthly="₱12,000/month"
                                                data-details="Dimensions: 5x4 sqm"
                                                data-lot-type="5"
                                                style="background-color: #b8860b; color: white; border: none;">Select</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container my-5">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-dark">Special Packages</h3>
                            <p class="text-muted">Comprehensive packages and additional services.</p>
                        </div>

                        <div class="row g-4 justify-content-center">
                            <!-- 4-Lot Package - Gold Theme -->
                            <div class="col-md-6">
                                <div class="card shadow-sm border-0 h-100" style="background-color: #fff3cd; border: 2px solid #d4a574 !important;">
                                    <div class="card-body text-center">
                                        <h5 class="card-title fw-bold" style="color: #b8860b;">4-Lot Package</h5>
                                        <p class="card-text fs-5 fw-semibold mb-2" style="color: #856404;">₱300,000</p>
                                        <p class="mb-2" style="color: #b8860b;">₱6,000/month</p>
                                        <p class="mb-3" style="color: #b8860b;">Depth: 4 feet or 6 feet</p>
                                        <button class="btn w-100 package-select-btn" 
                                                data-package="4-Lot Package"
                                                data-price="₱300,000"
                                                data-monthly="₱6,000/month"
                                                data-details="Depth: 4 feet or 6 feet"
                                                data-lot-type="6"
                                                style="background-color: #daa520; color: white; border: none;">Select</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Exhumation Service - Silver Theme -->
                            <div class="col-md-6">
                                <div class="card shadow-sm border-0 h-100" style="background-color: #f8f9fa; border: 2px solid #adb5bd !important;">
                                    <div class="card-body text-center">
                                        <h5 class="card-title fw-bold" style="color: #495057;">Exhumation Service</h5>
                                        <p class="card-text fs-5 fw-semibold mb-2" style="color: #343a40;">₱15,000</p>
                                        <p class="mb-2" style="color: #6c757d;">One-time payment</p>
                                        <p class="mb-3" style="color: #6c757d;">Per person service</p>
                                        <button class="btn w-100 package-select-btn" 
                                                data-package="Exhumation Service"
                                                data-price="₱15,000"
                                                data-monthly="One-time payment"
                                                data-details="Per person service"
                                                data-lot-type="7"
                                                style="background-color: #6c757d; color: white; border: none;">Select</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container my-5">
                        <div class="row justify-content-center">
                            <div class="col-lg-10">
                                <div class="bg-light rounded-4 p-4 border-0 shadow-sm">
                                    <div class="text-center mb-4">
                                        <h3 class="fw-bold text-dark mb-2">Important Information</h3>
                                        <p class="text-muted mb-0">Please review these details before making your reservation</p>
                                    </div>
                                    
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="bg-white rounded-3 p-3 h-100 border border-light">
                                                <h6 class="fw-semibold text-dark mb-2">Mausoleum Specifications</h6>
                                                <p class="text-muted mb-0 small">All mausoleums measure 5 meters by 4 meters (5x4 sqm)</p>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="bg-white rounded-3 p-3 h-100 border border-light">
                                                <h6 class="fw-semibold text-dark mb-2">Burial Depth Options</h6>
                                                <p class="text-muted mb-0 small">Burial lots offer depth options of either 4 feet or 6 feet</p>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="bg-white rounded-3 p-3 h-100 border border-light">
                                                <h6 class="fw-semibold text-dark mb-2">Payment Terms</h6>
                                                <p class="text-muted mb-0 small">Monthly payment plans span 50 months (4 years and 2 months)</p>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="bg-white rounded-3 p-3 h-100 border border-light">
                                                <h6 class="fw-semibold text-dark mb-2">Payment Flexibility</h6>
                                                <p class="text-muted mb-0 small">Advance payments are accepted and welcome</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <section class="lot-history-section card p-4">
            <h3>Lot Reservation History</h3>
            <div class="table-responsive">
                <table class="lot-history-table table table-hover">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Address</th>
                            <th>Contact Number</th>
                            <th>Client Valid ID</th>
                            <th>Date of Reservation</th>
                            <th>Area</th>
                            <th>Block</th>
                            <th>Row Number</th>
                            <th>Lot Number</th>
                            <th>Preferred Lot Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- History rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div id="reservation-pagination">
                </div>
        </section>
    </main>

    <div class="modal" id="docModal" tabindex="-1" aria-labelledby="docModalLabel" aria-hidden="true" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="docModalLabel">Document Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="docFilename" style="font-weight:bold;"></p>
                    <img id="img-preview" class="img-fluid" style="display:none;" alt="Image Preview">
                    <canvas id="pdf-canvas" class="img-fluid"></canvas>
                    <!-- ✅ MODIFIED: Added d-flex and justify-content-center for centering -->
                    <div id="pdfControls" class="d-flex justify-content-center align-items-center" style="margin-top:10px; display:none;">
                        <button id="prevPage" class="btn btn-secondary btn-sm me-2">Prev</button>
                        <span id="pageInfo" class="me-2"></span>
                        <button id="nextPage" class="btn btn-secondary btn-sm">Next</button>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-left">
                    <a id="downloadLink" href="#" target="_blank" download class="btn btn-primary">
                        <i class="fas fa-download"></i> Download
                    </a>
                    <!-- Actions below are for form interaction, hidden for history view -->
                    <button type="button" class="btn btn-danger" id="deleteBtn">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                    <label for="replaceFileInput" class="btn btn-warning mb-0">
                        <i class="fas fa-sync-alt"></i> Replace
                    </label>
                    <input type="file" id="replaceFileInput" hidden>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Package Selection Modal -->
    <div class="modal fade" id="packageModal" tabindex="-1" aria-labelledby="packageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="packageModalLabel">Package Selection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h4 id="selectedPackageTitle" class="fw-bold text-dark"></h4>
                        <p id="selectedPackagePrice" class="fs-4 fw-semibold text-dark"></p>
                        <p id="selectedPackageMonthly" class="text-muted"></p>
                        <p id="selectedPackageDetails" class="text-muted"></p>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Next Steps:</strong> This package will be automatically selected in the reservation form below. Please scroll down to complete your reservation details.
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Payment Terms</h6>
                                    <p class="card-text small mb-0">50 months payment plan available</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Advance Payment</h6>
                                    <p class="card-text small mb-0">Full payment discounts available</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmPackageSelection" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>Confirm Selection
                    </button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="lotReservation.js" defer></script>

    <script>
        // Package selection redirect functionality
        document.addEventListener('DOMContentLoaded', function() {
            const packageButtons = document.querySelectorAll('.package-select-btn');
            
            // Handle package button clicks - redirect to new page
            packageButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const packageName = this.getAttribute('data-package');
                    const price = this.getAttribute('data-price');
                    const monthly = this.getAttribute('data-monthly');
                    const details = this.getAttribute('data-details');
                    const lotType = this.getAttribute('data-lot-type');
                    
                    // Create URL with parameters
                    const url = new URL('lotReservationForm.php', window.location.origin + window.location.pathname.replace('lotReservation.php', ''));
                    url.searchParams.set('package', packageName);
                    url.searchParams.set('price', price);
                    url.searchParams.set('monthly', monthly);
                    url.searchParams.set('details', details);
                    url.searchParams.set('lotType', lotType);
                    
                    // Redirect to the new page
                    window.location.href = url.toString();
                });
            });
        });
    </script>
</body>
</html>