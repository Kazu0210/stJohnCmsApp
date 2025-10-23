<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password - Blessed Saint John Memorial Gardens and Park</title>
  <link rel="stylesheet" href="confirmPassword.css">
</head>
<body>
  <div class="content" style="justify-content: center;">
    <div class="right">
      <h2>Reset <span>your password</span></h2>
      <form id="resetForm">
        <input type="password" id="newPassword" placeholder="New Password" required />
        <input type="password" id="confirmPassword" placeholder="Confirm New Password" required />
        <p class="member-text">Create a strong password you haven't used before.</p>
        <p id="errorMsg" style="color: red; font-size: 14px; display: none; text-align: center;">Passwords do not match.</p>
        <button type="submit" id="resetButton" class="create-account">Reset Password</button>
      </form>
    </div>
  </div>
  <script src="confirmPassword.js"></script>
</body>
</html>