<?php
session_start();
include('../database/database.php');

// Set default timezone to Philippine time
date_default_timezone_set('Asia/Manila');

// Fetch website settings (including favicon, site title, and logos)
$stmtSettings = $pdo->prepare("SELECT * FROM website_settings LIMIT 1");
$stmtSettings->execute();
$website_settings = $stmtSettings->fetch(PDO::FETCH_ASSOC);

$favicon = isset($website_settings['favicon']) ? $website_settings['favicon'] : '';

if (isset($_SESSION['admin_username'])) {
    $admin_username = $_SESSION['admin_username'];
    
    // Clear the login_token in the users table
$stmtToken = $pdo->prepare("UPDATE users SET login_token = NULL, logged_in = 0 WHERE username = :username");
    $stmtToken->execute(['username' => $admin_username]);

    // Update logs: set logout_time to the current timestamp for the active session
    $stmt = $pdo->prepare("UPDATE logs SET logout_time = NOW() WHERE admin_username = :username AND logout_time IS NULL");
    $stmt->execute(['username' => $admin_username]);

    // Destroy the session and redirect to login page
    session_destroy();
    header('Location: ../login.php');
    exit();
} else {
    $error = "You are not logged in.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logout - <?= htmlspecialchars($website_settings['site_title'] ?? 'Your Site Title') ?></title>
  <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($favicon) ?>">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    /* Reset & Base Styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    html, body {
      height: 100%;
      font-family: 'Segoe UI', sans-serif;
    }
    /* Background Image & Overlay */
    .bg-image {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('../main/uploads/bg image.jpg') no-repeat center center fixed;
      background-size: cover;
      filter: blur(8px);
      z-index: 0;
    }
    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(18,14,71,0.7);
      z-index: 1;
    }
    /* Logout Container */
    .logout-container {
      position: relative;
      z-index: 2;
      max-width: 440px;
      margin: 100px auto;
      padding: 2rem 3rem;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      text-align: center;
    }
    /* Branding */
    .branding {
      margin-bottom: 1.5rem;
    }
    .branding .logo {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      padding: 2px;
      background: #fff;
      border: 3px solid #120E47;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      margin-bottom: 1rem;
    }
    .branding h1 {
      margin: 0;
      color: #120E47;
      font-size: 1.8rem;
    }
    /* Message Styling */
    .message p {
      font-size: 1.2rem;
      margin: 1rem 0;
      color: #333;
    }
    .message .error {
      color: red;
    }
    .message .success {
      color: green;
    }
    /* Button */
    .btn {
      display: inline-block;
      padding: 14px 20px;
      background: linear-gradient(135deg, #120E47, #00C9FF, #2A5CFF);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 600;
      text-decoration: none;
      margin-top: 20px;
      transition: transform 0.3s ease, opacity 0.3s ease;
    }
    .btn:hover {
      transform: translateY(-2px);
      opacity: 0.95;
    }
  </style>
</head>
<body>
  <div class="bg-image"></div>
  <div class="overlay"></div>
  <div class="logout-container">
    <div class="branding">
      <?php if (!empty($website_settings['organization_logo'])): ?>
        <img src="../main/<?= htmlspecialchars($website_settings['organization_logo']) ?>" class="logo" alt="Organization Logo">
      <?php endif; ?>
      <h1><?= htmlspecialchars($website_settings['site_title'] ?? 'Your Site Title') ?></h1>
    </div>
    <div class="message">
      <?php 
        if (isset($error)) {
          echo "<p class='error'><i class='fas fa-exclamation-circle'></i> " . htmlspecialchars($error) . "</p>";
        } else {
          echo "<p class='success'><i class='fas fa-check-circle'></i> You have successfully logged out.</p>";
        }
      ?>
    </div>
    <a href="../login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Return to Login</a>
  </div>
</body>
</html>
