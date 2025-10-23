<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Blessed Saint John Memorial Gardens and Park - Signup</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="signup.css" />
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-warning sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">Blessed Saint John Memorial Gardens and Park</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../login/login.php#login">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="../login/login.php#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Join</a></li>
                    <li class="nav-item"><a class="nav-link" href="../login/login.php#about">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="../login/login.php#appointment">Appointment</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section id="signup" class="py-5 bg-light">
        <div class="container">
            <div class="signup-box p-4 shadow-sm rounded mx-auto bg-white">
                <h2 class="text-center mb-4">Create <span class="text-warning">New Account</span></h2>
                <form id="signupForm" method="POST" action="/stJohnCmsApp/cms.api/signup.php
">

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <input type="text" id="firstName" name="firstName" class="form-control" placeholder="First Name" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="lastName" name="lastName" class="form-control" placeholder="Last Name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="email" id="email" name="email" class="form-control" placeholder="Email" required>
                    </div>

                    <div class="mb-3">
                        <input type="tel" id="contactNumber" name="contactNumber" class="form-control" placeholder="Phone Number" required>
                    </div>

                    <div class="mb-3 position-relative">
                        <i class="bi bi-lock-fill input-icon position-absolute top-50 start-0 translate-middle-y ps-2"></i>
                        <input type="password" id="password" name="password" class="form-control ps-5" placeholder="Password" required>
                        <i class="bi bi-eye-slash password-toggle-icon position-absolute top-50 end-0 translate-middle-y pe-3"
                            onclick="togglePassword('password', this)"></i>
                    </div>

                    <div class="mb-3 position-relative">
                        <i class="bi bi-lock-fill input-icon position-absolute top-50 start-0 translate-middle-y ps-2"></i>
                        <input type="password" id="confirmPassword" name="confirmPassword" class="form-control ps-5" placeholder="Confirm Password" required>
                        <i class="bi bi-eye-slash password-toggle-icon position-absolute top-50 end-0 translate-middle-y pe-3"
                            onclick="togglePassword('confirmPassword', this)"></i>
                    </div>

                    <div class="mb-3">
                        <input type="text" id="emergencyContactName" name="emergencyContactName" class="form-control" placeholder="Emergency Contact Name" required>
                    </div>

                    <div class="mb-3">
                        <input type="tel" id="emergencyContactNumber" name="emergencyContactNumber" class="form-control" placeholder="Emergency Contact Number" required>
                    </div>

                    <p class="text-center">Already a Member? <a href="../login/login.php">Log In</a></p>
                    <button type="submit" class="btn btn-warning w-100 fw-bold" a href="../login/login.php">Create Account</a></button>
                </form>
            </div>
        </div>
    </section>

    <footer class="text-center py-3">
        &copy; 2025 Blessed Saint John Memorial Gardens and Park. All Rights Reserved.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle password visibility
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            } else {
                input.type = "password";
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            }
        }
    </script>
<script src="signup.js"></script>
</body>
</html>
