<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>CMS Landing</title>
  <link rel="stylesheet" href="./resources/ol.css">
  <link rel="stylesheet" href="resources/fontawesome-all.min.css">
  <link href="resources/photon-geocoder-autocomplete.min.css" rel="stylesheet">
  <link rel="stylesheet" href="./resources/ol-layerswitcher.css">
  <link rel="stylesheet" href="./resources/qgis2web.css">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="login.css"/>
  <script type="text/javascript"
          src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js">
  </script>
</head>
<body>

<!-- ======== NAVBAR ======== -->
<nav class="navbar navbar-expand-lg navbar-light bg-warning sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">
      <span class="d-none d-sm-inline">Blessed Saint John Memorial Gardens and Park</span>
      <span class="d-sm-none">BSJ Memorial Park</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="#login">Login</a></li>
        <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="../signup/signup.php">Join</a></li>
        <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="#appointment">Appointment</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- ======== LOGIN SECTION ======== -->
<section id="login" class="py-5 bg-light">
  <div class="container">
    <div class="login-box p-4 shadow-sm rounded mx-auto">
      <h2 class="text-center mb-4">Login to your <span>account</span></h2>
      <form id="loginForm">
        <div class="mb-3 position-relative">
          <i class="bi bi-envelope-fill input-icon"></i>
          <input type="text" id="email" class="form-control ps-5" placeholder="Email">
          <div class="text-danger small" id="emailError"></div>
        </div>
        <div class="mb-3 position-relative">
          <i class="bi bi-lock-fill input-icon"></i>
          <input type="password" id="password" class="form-control ps-5" placeholder="Password">
          <i class="bi bi-eye-slash password-toggle-icon" onclick="togglePassword()"></i>
          <div class="text-danger small" id="passwordError"></div>
        </div>
        <div class="text-center mb-2">
          <a href="../forgotPassword/forgotPassword.php">Forgot Password?</a>
        </div>
        <div class="text-center mb-3">
          Not a member yet? <a href="../signup/signup.php">Join Now</a>
        </div>
        <div id="serverMessage" class="mb-2"></div>
        <button type="submit" class="btn btn-warning w-100 fw-bold">Log In</button>
      </form>
    </div>
  </div>
</section>

<!-- ======== HOME SECTION ======== -->
<section id="home" class="py-5 home-section">
  <div class="container">
    <div class="text-center">
      <h2 class="mb-3">Welcome to Blessed Saint John Memorial Gardens and Park</h2>
      <p class="mb-4">This sacred space offers peace and reflection for families and loved ones. Our cemetery map below guides you through available plots, mausoleums, and key landmarks.</p>
      <div class="map-container ratio ratio-16x9">
        <div id="map">
              <div id="popup" class="ol-popup">
                <a href="#" id="popup-closer" class="ol-popup-closer"></a>
                <div id="popup-content"></div>
              </div>
        </div>
      </div>
    </div>

    
</section>

<!-- ======== APPOINTMENT SECTION ======== -->
  <!-- APPOINTMENT FORM + CALENDAR -->
  <section id="appointment" class="py-4 appointment-section">
    <div class="container">
      <h2 class="mb-2 text-center">Schedule Your Visit</h2>
      <p class="text-center mb-3">Please fill out the form below with your details, preferred date, time (7 AM - 4 PM), and purpose of your visit.</p>

      <div class="row gx-3">
        <div class="col-lg-7">
          <!-- Appointment form (kept) -->
          <form id="appointmentForm" class="appointment-form">
            <div>
              <label for="user_name" class="required">Your Name</label>
              <input type="text" id="user_name" required aria-required="true" placeholder="Full name">
            </div>
            <div>
              <label for="user_email" class="required">Your Email</label>
              <input type="email" id="user_email" required placeholder="name@example.com">
            </div>
            <div>
                <label for="user_address" class="required">Your Address</label>
                <input type="text" id="user_address" required placeholder="Full address (Street, City, Province)">
            </div>
            <div>
              <label for="user_phone" class="required">Contact Number</label>
              <input type="tel" id="user_phone" required placeholder="+63...">
            </div>
            <div>
              <label for="appointment_date" class="required">Preferred Date</label>
              <input type="date" id="appointment_date" required>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
              <div style="flex:1;min-width:140px">
                <label for="appointment_start_time" class="required">Start Time</label>
                <input type="time" id="appointment_start_time" min="07:00" max="16:00" required>
              </div>
              <div style="flex:1;min-width:140px">
                <label for="appointment_end_time" class="required">End Time</label>
                <input type="time" id="appointment_end_time" min="07:00" max="16:00" required>
              </div>
            </div>
            <div>
              <label for="appointment_purpose" class="required">Purpose of Visit</label>
              <textarea id="appointment_purpose" rows="4" required placeholder="Reason for visit"></textarea>
            </div>
            <div id="appointmentMessage" class="text-danger"></div>
            <div class="submit-container">
              <button type="submit" class="submit-appointment">Submit Appointment</button>
            </div>
          </form>

          <div class="mt-3 muted">Tip: If you'd like to pick a date visually, use the calendar on the right (or below on small screens).</div>
        </div>

        <!-- Calendar + Sidebar -->
        <div class="col-lg-5">
          <div class="calendar-card" role="application" aria-label="Calendar with Appointments">
            <div class="header-row mb-3">
              <div style="display:flex;gap:8px;align-items:center;">
                <button class="btn btn-sm btn-warning" id="prevMonth" title="Previous month">◀</button>
                <div id="monthLabel" style="font-weight:700;margin:0 8px;">Month Year</div>
                <button class="btn btn-sm btn-warning" id="nextMonth" title="Next month">▶</button>
              </div>
              <div>
                <button id="todayBtn" class="btn btn-sm btn-ghost">Today</button>
              </div>
            </div>

            <div class="grid" id="calendarGrid" role="grid" aria-hidden="false" style="grid-template-columns:repeat(7,1fr);">
              <div class="weekday">Sun</div>
              <div class="weekday">Mon</div>
              <div class="weekday">Tue</div>
              <div class="weekday">Wed</div>
              <div class="weekday">Thu</div>
              <div class="weekday">Fri</div>
              <div class="weekday">Sat</div>
              <!-- days injected here -->
            </div>

            <aside class="details-card mt-3" aria-labelledby="sideTitle">
              <h3 id="sideTitle" style="margin:0">Details</h3>
              <div id="selectedDayHeading" style="font-weight:700;margin-top:8px">Select a day</div>

              <div id="dayAppts" style="flex:1; overflow:auto;margin-top:8px;">
                <div class="empty">No date selected — click a day to see or add appointments.</div>
              </div>

              <div style="display:flex;gap:8px;align-items:center;justify-content:space-between;margin-top:8px">
                <small class="muted">List of Appointments:</small>
                <small class="muted"><span id="apptCount">0</span> appt(s)</small>
              </div>
            </aside>

          </div>
        </div>
      </div>
    </div>
  </section>

<div class="modal-backdrop" id="modalBackdrop" role="dialog" aria-modal="true" aria-hidden="true">
  <form class="modal-form" id="apptForm" onsubmit="return false;">
    <h3 style="margin-top:0" id="apptFormTitle">Edit Local Appointment</h3>

    <div class="field mb-2">
      <label for="apptClient">Client name:</label>
      <input id="apptClient" type="text" placeholder="e.g., (Dela Cruz, Jon Hipolito)" required class="form-control" />
    </div>

    <div class="row gx-2">
      <div class="field col-auto" style="max-width:160px">
        <label for="apptDate">Date</label>
        <input id="apptDate" type="date" required class="form-control"/>
      </div>
      <div class="field col-auto" style="max-width:130px">
        <label for="apptTime">Time</label>
        <input id="apptTime" type="time" class="form-control"/>
      </div>
    </div>

    <div class="field mt-2">
      <label for="apptNotes">Notes (optional)</label>
      <textarea id="apptNotes" placeholder="Add location, link, or details" class="form-control"></textarea>
    </div>

    <input type="hidden" id="editingId" value="">

    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
      <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
      <button id="deleteBtn" class="btn btn-danger" type="button" style="display:none">Delete</button>
      <button id="saveBtn" class="btn btn-primary" type="submit">Save</button>
    </div>
  </form>
</div>

<!-- ======== ABOUT SECTION ======== -->
<section id="about" class="py-5 bg-light">
  <div class="container">
    <h2 class="mb-4 text-center">About Us</h2>
    <div class="row g-4 justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm about-card">
          <img src="anime1.jpg" class="card-img-top" alt="Garden 1" onclick="openModal(this)">
          <div class="card-body">
            <h5 class="card-title">Peaceful Environment</h5>
            <p class="card-text">Our memorial garden provides a serene and calm atmosphere for reflection and remembrance.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm about-card">
          <img src="anime2.jpg" class="card-img-top" alt="Garden 2" onclick="openModal(this)">
          <div class="card-body">
            <h5 class="card-title">Well-Maintained Grounds</h5>
            <p class="card-text">We ensure all plots, pathways, and landscapes are maintained beautifully.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm about-card">
          <img src="anime3.jpg" class="card-img-top" alt="Garden 3" onclick="openModal(this)">
          <div class="card-body">
            <h5 class="card-title">Supportive Staff</h5>
            <p class="card-text">Our team is ready to assist families with care and professionalism.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======== IMAGE MODAL ======== -->
<div id="modal" class="modal" onclick="closeModal()">
  <span class="close">&times;</span>
  <img id="modal-img" class="modal-content">
  <div id="caption"></div>
</div>

<!-- ======== FOOTER ======== -->
<footer class="text-center py-3">
  &copy; 2025 Blessed Saint John Memorial Gardens and Park. All Rights Reserved.
</footer>

<!-- Bootstrap JS -->
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
<script src="login.js"> </script>
</body>
</html>