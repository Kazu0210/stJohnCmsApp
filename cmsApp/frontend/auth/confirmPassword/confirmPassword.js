//confirmPassword.js (Functional Code)

// Get DOM elements
const resetForm = document.getElementById("resetForm");
const newPassword = document.getElementById("newPassword");
const confirmPassword = document.getElementById("confirmPassword");
const errorMsg = document.getElementById("errorMsg");
const resetButton = document.getElementById("resetButton");

// Create a dynamic message element for success feedback
const successMsg = document.createElement("p");
successMsg.style.color = "green";
successMsg.style.fontSize = "14px";
successMsg.style.textAlign = "center";
successMsg.style.display = "none";
// Insert success message before the button
resetForm.insertBefore(successMsg, resetButton); 

// Password strength pattern: at least 8 chars, including uppercase, lowercase, number, and special character
const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

// Helper function to display errors
function displayError(message) {
    errorMsg.style.color = "red";
    errorMsg.textContent = message;
    errorMsg.style.display = "block";
}

// Form submit handler
resetForm.addEventListener("submit", async function (e) {
    e.preventDefault();

    // Hide previous messages
    errorMsg.style.display = "none";
    successMsg.style.display = "none";

    const passwordValue = newPassword.value.trim();
    const confirmValue = confirmPassword.value.trim();
    const userEmail = localStorage.getItem('recoveryEmail'); // Retrieve the email from local storage

    // --- Client-side Validations ---

    if (!userEmail) {
        displayError("Error: User email not found. Please restart the recovery process from the beginning.");
        return;
    }

    // 1️⃣ Validate password strength
    if (!passwordPattern.test(passwordValue)) {
        displayError(
            "Password must be at least 8 characters, include uppercase, lowercase, number, and special character."
        );
        return;
    }

    // 2️⃣ Check password match
    if (passwordValue !== confirmValue) {
        displayError("Passwords do not match.");
        return;
    }

    // --- Server Submission ---

    // Disable button and show loading state
    resetButton.disabled = true;
    resetButton.textContent = "Resetting...";

    try {
        const response = await fetch("/stJohnCmsApp/cms.api/resetPassword.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            // Send email and the new password
            body: JSON.stringify({ 
                email: userEmail, 
                password: passwordValue 
            }),
        });

        // The PHP script sends JSON responses
        const data = await response.json();

        if (response.ok && data.success) {
            // 3️⃣ Success
            successMsg.style.display = "block";
            successMsg.textContent = "Password successfully reset! Redirecting to login...";
            
            // Clean up stored email on successful reset
            localStorage.removeItem('recoveryEmail');

            // 🎯 Redirect to the specific login URL
            setTimeout(() => {
                window.location.href = "http://localhost/stJohnCmsApp/cmsApp/frontend/auth/login/login.php";
            }, 1500);

        } else {
            // 4️⃣ Server Error/Failure
            const errorMessage = data.error || "Password reset failed due to a server error.";
            displayError(errorMessage);
        }

    } catch (error) {
        console.error("Fetch Error:", error);
        displayError("Network error. Please check your connection or try again.");
    } finally {
        // Re-enable button on error, but not on success (as it redirects)
        if (!successMsg.style.display || successMsg.style.display === "none") {
            resetButton.disabled = false;
            resetButton.textContent = "Reset Password";
        }
    }
});