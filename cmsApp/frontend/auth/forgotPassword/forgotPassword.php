<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Blessed Saint John Memorial Gardens and Park</title>
  <link rel="stylesheet" href="forgotPassword.css" />
</head>
<body>
  <div class="content" style="justify-content: center;">
    <div class="right">
      <h2>Recover <span>your account</span></h2>
      <form id="recover-form">
        
        <input type="email" id="email-input" placeholder="Enter your Email Address" required />

        <p class="member-text">We'll send a verification code to your email address.</p>
        
        <p id="error-message" style="color:red; font-size:14px;"></p>
        <p id="success-message" style="color:green; font-size:14px;"></p>
        
        <button type="submit" class="create-account">Send OTP</button>
      </form>
    </div>
  </div>

  <script type="text/javascript"
          src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js">
  </script>
  <script type="text/javascript">
    (function(){
        emailjs.init({
          publicKey: "L37d4ZcJ3mtueanid",
        });
    })();
  </script>

  <script src="forgot-password.js"></script>
</body>
</html>