// forgot-password.js

const emailInput = document.getElementById('email-input');
const recoverForm = document.getElementById('recover-form');
const errorMsg = document.getElementById('error-message');
const successMsg = document.getElementById('success-message');

function sendOtpEmail(recipientEmail) {
    const TEST_OTP = '123456'; 

    const templateParams = {
        email: recipientEmail,  
        passcode: TEST_OTP,
        time: '15 minutes', 
    };

    errorMsg.textContent = "";
    successMsg.textContent = "Sending OTP. Please wait...";

    emailjs.send("service_wglij81", "template_5zzhjva", templateParams)
        .then(
            (response) => {
                console.log('Email successfully sent!', response.status, response.text);
                successMsg.style.color = "green";
                successMsg.textContent = `OTP has been sent to your email: ${recipientEmail}`;

                setTimeout(() => {
                    window.location.href = "../verifyOtp/verifyOtp.php";
                }, 2000);
            },
            (error) => {
                console.error('Email failed to send...', error);
                errorMsg.style.color = "red";
                const errorText = error.text || "Please verify EmailJS settings.";
                errorMsg.textContent = `Error sending OTP. Details: ${errorText}`;
                successMsg.textContent = ""; 
            },
        );
}


recoverForm.addEventListener('submit', function(event) {
    event.preventDefault(); 
    errorMsg.textContent = "";
    successMsg.textContent = "";

    const emailValue = emailInput.value.trim();
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailValue) {
        errorMsg.style.color = "red";
        errorMsg.textContent = "Please enter your email address.";
        return;
    }
    if (!emailPattern.test(emailValue)) {
        errorMsg.style.color = "red";
        errorMsg.textContent = "Invalid email format. Example: name@example.com";
        return;
    }

    sendOtpEmail(emailValue);
});