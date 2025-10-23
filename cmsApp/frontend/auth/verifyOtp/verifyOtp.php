<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Email OTP Verification</title>
  <link rel="stylesheet" href="verifyOtp.css" />
  <style>
    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: #f0f2f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .otp-container {
      background: #fff;
      padding: 30px 25px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }

    h2 {
      margin-bottom: 15px;
      color: #333;
    }

    p {
      color: #555;
      margin-bottom: 20px;
    }

    input[type="email"] {
      width: 100%;
      padding: 12px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #ccc;
      margin-bottom: 10px;
      outline: none;
      transition: border-color 0.3s;
    }

    input[type="email"]:focus {
      border-color: #4a90e2;
    }

    .otp-inputs {
      display: flex;
      justify-content: space-between;
      margin: 20px 0;
    }

    .otp-inputs input {
      width: 45px;
      height: 50px;
      font-size: 24px;
      text-align: center;
      border: 1px solid #ccc;
      border-radius: 8px;
      outline: none;
      transition: border-color 0.3s;
    }

    .otp-inputs input:focus {
      border-color: #4a90e2;
      box-shadow: 0 0 5px rgba(74,144,226,0.5);
    }

    .btn {
      width: 100%;
      padding: 12px;
      margin-top: 10px;
      border: none;
      border-radius: 8px;
      background: #4a90e2;
      color: white;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .btn:hover {
      background: #357ab8;
    }

    .verify-btn {
      display: none; /* Initially hide the verify button */
    }

    #message {
      margin-top: 15px;
      font-weight: bold;
    }

    .resend {
      margin-top: 15px;
      font-size: 14px;
    }

    .resend a {
      color: #4a90e2;
      text-decoration: none;
    }

    .resend a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="otp-container">
    <h2>Email Verification</h2>
    <p>Enter your email and get a 6-digit OTP.</p>

    <form id="otp-form">
      <!-- Email input -->
      <input type="email" id="email" placeholder="Enter your email" required />

      <!-- Send OTP button -->
      <button type="button" id="send-otp-btn" class="btn">Send OTP</button>

      <!-- OTP input fields (disabled until OTP sent) -->
      <div class="otp-inputs">
        <input type="text" maxlength="1" disabled />
        <input type="text" maxlength="1" disabled />
        <input type="text" maxlength="1" disabled />
        <input type="text" maxlength="1" disabled />
        <input type="text" maxlength="1" disabled />
        <input type="text" maxlength="1" disabled />
      </div>

      <!-- Verify OTP button -->
      <button type="submit" class="btn verify-btn" id="verify-otp-btn">Verify OTP</button>
    </form>

    <!-- Message area -->
    <p id="message"></p>

    <!-- Resend OTP -->
    <p class="resend">
      Didn't get the code? <a href="#" id="resend-link">Resend OTP</a>
    </p>
  </div>

  <script src="verifyOtp.js"></script>
</body>
</html>
