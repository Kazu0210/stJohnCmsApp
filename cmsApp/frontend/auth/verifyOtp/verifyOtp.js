//verifyOtp.js (Full Code - Modified to bypass server for front-end testing)

// Grab DOM nodes
const emailInput = document.getElementById("email");
const sendOtpBtn = document.getElementById("send-otp-btn");
const verifyOtpBtn = document.getElementById("verify-otp-btn");
const otpInputs = document.querySelectorAll(".otp-inputs input");
const otpForm = document.getElementById("otp-form");
const resendLink = document.getElementById("resend-link");
const message = document.getElementById("message");

// State management for better control
let isOtpSent = false;

// --- Helper Functions ---

// Helper: enable or disable OTP inputs and toggle Verify button visibility
function toggleOtpInputs(enable = false) {
  otpInputs.forEach(input => {
    input.disabled = !enable;
    if (!enable) input.value = "";
  });
  if (enable) otpInputs[0].focus();
  // Toggle the visibility of the verify button
  verifyOtpBtn.style.display = enable ? 'block' : 'none';
}

// Helper: read OTP string
function getEnteredOtp() {
  return Array.from(otpInputs).map(i => i.value).join("");
}

// Helper: clear OTP inputs
function clearOtpInputs() {
  otpInputs.forEach(i => i.value = "");
  otpInputs[0].focus();
}

// 🎯 New Functionality: Resend Cooldown Timer ⏳
const RESEND_COOLDOWN_SECONDS = 60;
let resendTimer = null;

function startResendCooldown() {
  let timeLeft = RESEND_COOLDOWN_SECONDS;
  resendLink.style.pointerEvents = 'none'; // Disable clicking during cooldown
  resendLink.style.color = '#ccc';
  resendLink.style.cursor = 'default';
  resendLink.textContent = `Resend in ${timeLeft}s`;

  clearInterval(resendTimer);
  resendTimer = setInterval(() => {
    timeLeft--;
    if (timeLeft > 0) {
      resendLink.textContent = `Resend in ${timeLeft}s`;
    } else {
      clearInterval(resendTimer);
      resendLink.textContent = "Resend OTP";
      resendLink.style.pointerEvents = 'auto';
      resendLink.style.color = '#4a90e2';
      resendLink.style.cursor = 'pointer';
    }
  }, 1000);
}


// --- Initial Setup and Email Persistence ---

// Pre-fill email from local storage if available
const storedEmail = localStorage.getItem('recoveryEmail');
if (storedEmail) {
  emailInput.value = storedEmail;
}


// --- Event Handlers ---

// Send OTP handler (MODIFIED: Simulates server success for testing)
async function sendOtpHandler() {
  const email = emailInput.value.trim();
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    message.style.color = "red";
    message.textContent = "Please enter a valid email address.";
    return;
  }

  // 1. Disable button and show loading text immediately
  sendOtpBtn.disabled = true;
  sendOtpBtn.textContent = "Sending...";
  message.textContent = "";
  emailInput.disabled = true;

  // 2. SIMULATION: Simulate a network delay (500ms)
  await new Promise(resolve => setTimeout(resolve, 500)); 

  // 3. SIMULATION: Define the simulated successful response data
  const data = { success: true }; 
  
  // 4. Handle simulated response
  if (data.success) {
    isOtpSent = true;
    localStorage.setItem('recoveryEmail', email); // Save email on success
    message.style.color = "green";
    message.textContent = `(TEST SUCCESS) OTP sent to ${email}! Please check your inbox.`;
    
    // Enable inputs and show Verify button
    toggleOtpInputs(true); 
    
    // Start cooldown for resend link
    startResendCooldown();
  } else {
    // This would be the real error path (currently not possible with simulation)
    message.style.color = "red";
    message.textContent = data.error || "Simulated failure: Failed to send OTP.";
    toggleOtpInputs(false);
    emailInput.disabled = false; 
  }

  // 5. Reset button state
  sendOtpBtn.disabled = false;
  sendOtpBtn.textContent = "Send OTP";
}

sendOtpBtn.addEventListener("click", sendOtpHandler);


// Resend OTP handler (uses the same simulated success logic as sendOtpHandler)
resendLink.addEventListener("click", async (e) => {
  e.preventDefault();
  if (resendLink.style.pointerEvents === 'none') return; // Do nothing if on cooldown

  const email = emailInput.value.trim();
  if (!email) {
    message.style.color = "red";
    message.textContent = "Enter your email to resend OTP.";
    return;
  }

  // Use the sendOtpHandler logic for the resend function
  await sendOtpHandler();
});


// OTP input handling (numeric, auto-move, backspace, paste)
otpInputs.forEach((input, index) => {
  input.setAttribute("inputmode", "numeric");
  input.setAttribute("autocomplete", "one-time-code");

  input.addEventListener("input", () => {
    input.value = input.value.replace(/\D/g, "");
    if (input.value.length > 1) input.value = input.value.charAt(0);
    if (input.value.length === 1 && index < otpInputs.length - 1) {
      otpInputs[index + 1].focus();
    }
    message.textContent = "";
  });

  input.addEventListener("keydown", (e) => {
    if (e.key === "Backspace" && input.value === "" && index > 0) {
      otpInputs[index - 1].focus();
    }
    if (e.key === "ArrowLeft" && index > 0) otpInputs[index - 1].focus();
    if (e.key === "ArrowRight" && index < otpInputs.length - 1) otpInputs[index + 1].focus();
  });

  if (index === 0) {
    input.addEventListener("paste", (e) => {
      const paste = (e.clipboardData || window.clipboardData).getData("text");
      const digits = paste.replace(/\D/g, "");
      if (digits.length) {
        e.preventDefault();
        for (let i = 0; i < otpInputs.length; i++) {
          otpInputs[i].value = digits[i] || "";
        }
        const firstEmpty = Array.from(otpInputs).findIndex(i => i.value === "");
        if (firstEmpty === -1) otpInputs[otpInputs.length - 1].focus();
        else otpInputs[firstEmpty].focus();
      }
    });
  }
});


// Form submit: verify OTP (MODIFIED: Simulates server verification)
otpForm.addEventListener("submit", async (e) => {
  e.preventDefault();

  if (!isOtpSent) {
      message.style.color = "red";
      message.textContent = "Please send an OTP first.";
      return;
  }

  const otp = getEnteredOtp();
  const email = emailInput.value.trim();

  if (otp.length < otpInputs.length) {
    message.style.color = "red";
    message.textContent = `Please enter the complete ${otpInputs.length}-digit OTP.`;
    return;
  }

  try {
    verifyOtpBtn.disabled = true; 
    verifyOtpBtn.textContent = "Verifying...";
    message.textContent = "";

    // 1. SIMULATION: Simulate a network delay (500ms)
    await new Promise(resolve => setTimeout(resolve, 500)); 

    // 2. SIMULATION: Success is hardcoded IF the user enters '123456'
    // This allows you to test the success path on the front end.
    const TEST_OTP = "123456"; 
    const isOtpCorrect = otp === TEST_OTP;

    const data = { verified: isOtpCorrect, error: isOtpCorrect ? null : "Incorrect OTP." };
    
    // 3. Handle simulated response
    if (data.verified) {
      message.style.color = "green";
      message.textContent = "✅ (TEST SUCCESS) OTP Verified! Redirecting to password reset...";
      toggleOtpInputs(false);
      // Redirect to the password reset page
      setTimeout(() => {
        window.location.href = "../confirmPassword/confirmPassword.php"; 
      }, 1500);

    } else {
      message.style.color = "red";
      message.textContent = data.error || "Invalid OTP. Try again.";
      clearOtpInputs();
      verifyOtpBtn.disabled = false; 
      verifyOtpBtn.textContent = "Verify OTP";
    }
  } catch (err) {
    // This catch block will never be hit with the simulation
    console.error(err);
    message.style.color = "red";
    message.textContent = "Server error during verification, try again.";
    verifyOtpBtn.disabled = false;
    verifyOtpBtn.textContent = "Verify OTP";
  }
});