<?php
include('../includes/header.php');
checkUserAccess(['Admin', 'Cashier', 'Staff']);

// Get favicon
$stmt = $pdo->query("SELECT favicon FROM website_settings WHERE id = 1");
$favicon = $stmt->fetchColumn();

// Redirect if not logged in
if (!isset($_SESSION['admin_username'])) {
  header('Location: access_denied.php');
  exit();
}

// Initialize toast message variables
$toastType = ''; // 'success' or 'error'
$toastMessage = '';

// Handle change username/password submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
  $current_password = $_POST['current_password'];
  $new_password = $_POST['new_password'];
  $confirm_password = $_POST['confirm_password'];
  $new_username = $_POST['new_username'];

  // Fetch current password and username from database
  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
  $stmt->execute(['username' => $_SESSION['admin_username']]);
  $admin = $stmt->fetch();

  if ($admin && password_verify($current_password, $admin['password'])) {
    if ($new_password === $confirm_password) {
      $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
      try {
        // Update username and password
        $stmt = $pdo->prepare("UPDATE users SET username = :username, password = :password WHERE username = :current_username");
        $stmt->execute([
          'username' => $new_username,
          'password' => $hashed_password,
          'current_username' => $_SESSION['admin_username']
        ]);
        $_SESSION['admin_username'] = $new_username; // Update session
        $toastType = 'success';
        $toastMessage = "Username and password changed successfully.";
      } catch (PDOException $e) {
        $toastType = 'error';
        $toastMessage = "Error updating settings: " . $e->getMessage();
      }
    } else {
      $toastType = 'error';
      $toastMessage = "New password and confirmation do not match.";
    }
  } else {
    $toastType = 'error';
    $toastMessage = "Current password is incorrect.";
  }
}
?>

<!-- Scoped Styles for Settings Page -->
<style>
  /* All styles are scoped under #settings-page */
  #settings-page {
    --primary-color: #1B263B;
    /* 60% Base Theme Color */
    --secondary-color: #415A77;
    /* 30% Lighter Variant */
    --accent-color: #778DA9;
    /* 10% Accent */
    --danger-color: #dc2626;
  }

  /* (Optional) You may scope body styles if needed */
  #settings-page body {
    background: #f3f4f6;
    font-family: 'Roboto', sans-serif;
    color: #333;
    margin: 0;
    padding: 0;
  }

  /* Main container styling */
  #settings-page .main-container {
    max-width: 600px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }

  /* Card/Form Styling */
  #settings-page .card-form {
    background: #ffffff;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  #settings-page .card-form:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
  }

  #settings-page .card-form h2 {
    margin-bottom: 20px;
    color: var(--primary-color);
    font-weight: 700;
  }

  #settings-page .form-group {
    margin-bottom: 15px;
  }

  #settings-page .form-group label {
    font-weight: 500;
    margin-bottom: 5px;
    display: block;
  }

  #settings-page .form-group input {
    width: 100%;
    padding: 12px;
    font-size: 1rem;
    border-radius: 6px;
    border: 1px solid #ccc;
    transition: border-color 0.3s ease;
  }

  #settings-page .form-group input:focus {
    border-color: var(--primary-color);
    outline: none;
  }

  #settings-page .btn-submit {
    background-color: var(--primary-color);
    border: none;
    color: #fff;
    padding: 12px;
    width: 100%;
    border-radius: 50px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  #settings-page .btn-submit:hover {
    background-color: var(--secondary-color);
  }

  /* Password Toggle Icon */
  #settings-page .password-container {
    position: relative;
  }

  #settings-page .password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #666;
    font-size: 1.1rem;
  }

  /* Toast Message Styling (Centered) */
  #settings-page #toast-container {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1055;
  }

  #settings-page .toast {
    border-radius: 8px;
    padding: 15px 20px;
    background-color: var(--primary-color);
    color: #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  }

  #settings-page .toast.toast-error {
    background-color: var(--danger-color);
  }
</style>

<!-- Main Content Container (Scoped) -->
<div id="settings-page">
  <div class="main-container">
    <div class="card-form">
      <!-- New label added here -->
      <h2>Username/Password Update</h2>
      <form method="POST">
        <div class="form-group">
          <label for="new_username">New Username</label>
          <input type="text" name="new_username" id="new_username"
            value="<?php echo htmlspecialchars($_SESSION['admin_username']); ?>" required>
        </div>
        <div class="form-group">
          <label for="current_password">Current Password</label>
          <div class="password-container">
            <input type="password" name="current_password" id="current_password" placeholder="Enter current password"
              required>
            <span class="password-toggle" id="current_password_icon"
              onclick="togglePasswordVisibility('current_password')">
              <i class="fa fa-eye"></i>
            </span>
          </div>
        </div>
        <div class="form-group">
          <label for="new_password">New Password</label>
          <div class="password-container">
            <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
            <span class="password-toggle" id="new_password_icon" onclick="togglePasswordVisibility('new_password')">
              <i class="fa fa-eye"></i>
            </span>
          </div>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <div class="password-container">
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password"
              required>
            <span class="password-toggle" id="confirm_password_icon"
              onclick="togglePasswordVisibility('confirm_password')">
              <i class="fa fa-eye"></i>
            </span>
          </div>
        </div>
        <button type="submit" name="change_password" class="btn-submit">Save Changes</button>
      </form>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div id="toast-container">
  <?php if (!empty($toastMessage)) { ?>
    <div id="liveToast" class="toast <?php echo ($toastType === 'error') ? 'toast-error' : ''; ?>" role="alert">
      <?php echo htmlspecialchars($toastMessage); ?>
    </div>
  <?php } ?>
</div>
</div>

<!-- JavaScript to Toggle Password Visibility -->
<script>
  // Toggle password visibility for input fields
  function togglePasswordVisibility(id) {
    var field = document.getElementById(id);
    var icon = document.getElementById(id + '_icon');
    if (field.type === "password") {
      field.type = "text";
      icon.querySelector('i').classList.remove('fa-eye');
      icon.querySelector('i').classList.add('fa-eye-slash');
    } else {
      field.type = "password";
      icon.querySelector('i').classList.remove('fa-eye-slash');
      icon.querySelector('i').classList.add('fa-eye');
    }
  }
</script>

<!-- Bootstrap Bundle JS (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Show toast (if any) once the page loads
  window.addEventListener('load', function () {
    var toastEl = document.getElementById('liveToast');
    if (toastEl) {
      var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
      toast.show();
    }
  });
</script>

<?php include('../includes/footer.php'); ?>