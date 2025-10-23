document.addEventListener('DOMContentLoaded', () => {

    // Toggle password visibility
    const showPasswordCheckbox = document.getElementById('showPassword');
    showPasswordCheckbox.addEventListener('click', () => {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const type = showPasswordCheckbox.checked ? 'text' : 'password';
        passwordInput.type = type;
        confirmPasswordInput.type = type;
    });

    const signupForm = document.getElementById('signupForm');
    signupForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Collect form values
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('email').value.trim();
        const contactNumber = document.getElementById('contactNumber').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const emergencyContactName = document.getElementById('emergencyContactName').value.trim();
        const emergencyContactNumber = document.getElementById('emergencyContactNumber').value.trim();

        // Required fields
        if (!firstName || !lastName || !email || !contactNumber || !password || !confirmPassword || !emergencyContactName || !emergencyContactNumber) {
            alert("Please fill in all fields.");
            return;
        }

        // Phone number validation
        const phoneRegex = /^09\d{9}$/;
        if (!phoneRegex.test(contactNumber)) {
            alert("Contact number must be 11 digits and start with 09.");
            return;
        }
        if (!phoneRegex.test(emergencyContactNumber)) {
            alert("Emergency contact number must be 11 digits and start with 09.");
            return;
        }

        // Password validation
        const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d).{6,}$/;
        if (!passwordRegex.test(password)) {
            alert("Password must be at least 6 characters long and contain at least 1 letter and 1 number.");
            return;
        }   

        // Confirm password match
        if (password !== confirmPassword) {
            alert("Passwords do not match.");
            return;
        }

        // ✅ Send data to PHP via fetch
        const formData = new FormData(signupForm);

        fetch("/stJohnCmsApp/cms.api/signup.php", { 
            method: "POST",
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes("success")) {
                alert("✅ Account created successfully! Please login now.");

                // ✅ Clear form after success
                signupForm.reset();

                
            } else {
                alert(data);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Something went wrong. Please try again later.");
        });
    });

});
