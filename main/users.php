<?php 
ob_start();
include('../includes/header.php');
checkUserAccess(['Admin']);

// Fetch favicon from website settings
$stmt = $pdo->query("SELECT favicon FROM website_settings WHERE id = 1");
$favicon = $stmt->fetchColumn();

if (!isset($_SESSION['admin_username'])) {
    header('Location: access_denied.php');
    exit();
}

function logAction($action, $details) {
    $logFile = '../logs/user_actions.log';
    $logEntry = date('Y-m-d H:i:s') . " | " . $_SESSION['admin_username'] . " | " . $action . " | " . $details . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Process form submissions for adding, editing, and deleting users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $username   = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role       = $_POST['role'];
        $created_by = $_SESSION['admin_username'];

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, created_by) VALUES (:username, :password, :role, :created_by)");
            $stmt->execute([
                'username'   => $username,
                'password'   => $password,
                'role'       => $role,
                'created_by' => $created_by
            ]);

            $success_message = "User added successfully.";
            logAction("ADD USER", "Added user '$username' with role '$role'.");
        } catch (PDOException $e) {
            $error_message = "Error adding user: " . $e->getMessage();
            logAction("ADD USER ERROR", "Error adding user '$username'. Error: " . $e->getMessage());
        }
    } elseif (isset($_POST['edit_user'])) {
        // Edit existing user
        $id       = $_POST['id'];
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
        $role     = $_POST['role'];

        try {
            if ($password) {
                $stmt = $pdo->prepare("UPDATE users SET username = :username, password = :password, role = :role WHERE id = :id");
                $stmt->execute([
                    'username' => $username,
                    'password' => $password,
                    'role'     => $role,
                    'id'       => $id
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = :username, role = :role WHERE id = :id");
                $stmt->execute([
                    'username' => $username,
                    'role'     => $role,
                    'id'       => $id
                ]);
            }
            $success_message = "User updated successfully.";
            logAction("EDIT USER", "Updated user id '$id' to username '$username' with role '$role'.");
        } catch (PDOException $e) {
            $error_message = "Error updating user: " . $e->getMessage();
            logAction("EDIT USER ERROR", "Error updating user id '$id'. Error: " . $e->getMessage());
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $id = $_POST['id'];
        try {
            // Fetch username for logging purposes before deletion
            $stmtSelect = $pdo->prepare("SELECT username FROM users WHERE id = :id");
            $stmtSelect->execute(['id' => $id]);
            $userToDelete = $stmtSelect->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $success_message = "User deleted successfully.";
            logAction("DELETE USER", "Deleted user '$userToDelete' (id: $id).");
        } catch (PDOException $e) {
            $error_message = "Error deleting user: " . $e->getMessage();
            logAction("DELETE USER ERROR", "Error deleting user id '$id'. Error: " . $e->getMessage());
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch the list of users
$stmt = $pdo->prepare("SELECT * FROM users");
$stmt->execute();
$users = $stmt->fetchAll();

ob_flush();
?>

<style>
  :root {
    /* Primary Color (60%): Base Theme Color */
    --primary-color: #1B263B;
    /* Secondary Color (30%): Lighter Variant */
    --secondary-color: #415A77;
    /* Accent Color (10%): Light Variant */
    --accent-color: #778DA9;
    /* Danger Color remains red */
    --danger-color: #dc2626;
  }
  body {
    background: #f3f4f6;
  }
  /* Main container styling specific to users page */
  .users-main-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }
  /* Users header styling */
  .users-header {
    background: #ffffff;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .users-header h1 {
    color: var(--primary-color);
  }
  /* User card styling specific to users page */
  .users-user-card {
    background: #ffffff;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .users-user-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
  }
  .users-user-card h4 {
    color: var(--primary-color);
  }
  .users-user-card p {
    margin-bottom: 0.5rem;
  }
  /* Button Styling Specific to Users Page */
  .users-btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
  }
  .users-btn-primary:hover {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
  }
  .users-btn-danger {
    background-color: var(--danger-color);
    border-color: var(--danger-color);
  }
  .users-btn-danger:hover {
    background-color: #e53935;
    border-color: #e53935;
  }
  .users-btn-secondary {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
  }
  .users-btn-secondary:hover {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
  }
  /* Modal Header Styling Specific to Users Page */
  .users-modal-header {
    background-color: var(--primary-color);
    color: #ffffff;
  }
  /* Toast Message Styling (Centered) Specific to Users Page */
  .users-toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1055;
    border-radius: 8px;
  }
</style>

<!-- Main Content Container -->
<div class="container mt-5 users-main-container">
  <!-- Header -->
  <header class="users-header">
    <h1 class="h4 mb-0">
      <i class="fas fa-user-cog me-2"></i>Manage Users
    </h1>
    <button class="btn btn-primary users-btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
      <i class="fas fa-plus"></i> Add User
    </button>
  </header>

  <!-- Toast Message Container -->
  <?php if (isset($success_message)) { ?>
    <div class="toast users-toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?php echo $success_message; ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php } ?>
  <?php if (isset($error_message)) { ?>
    <div class="toast users-toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?php echo $error_message; ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php } ?>

  <!-- Users Grid -->
  <div class="row">
    <?php foreach ($users as $user): ?>
      <div class="col-md-4 mb-4">
        <div class="users-user-card">
          <h4><?php echo htmlspecialchars($user['username']); ?></h4>
          <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
          <p><strong>Created By:</strong> <?php echo htmlspecialchars($user['created_by']); ?></p>
          <p><strong>Created At:</strong>
            <?php
              $dt = new DateTime($user['created_at'], new DateTimeZone('UTC'));
              $dt->setTimezone(new DateTimeZone('Asia/Manila'));
              echo $dt->format('Y-m-d h:i:s A');
            ?>
          </p>
          <div class="d-flex gap-2 mt-2">
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
              <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
              <button type="submit" name="delete_user" class="btn btn-danger btn-sm users-btn-danger">
                <i class="fas fa-trash"></i> Delete
              </button>
            </form>
            <button class="btn btn-secondary btn-sm users-btn-secondary" onclick="openEditModal('<?php echo htmlspecialchars($user['id']); ?>')">
              <i class="fas fa-edit"></i> Edit
            </button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<!-- End of Main Content Container -->

<!-- Modals -->

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header users-modal-header">
          <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="add-username" class="form-label">Username</label>
            <input type="text" class="form-control" id="add-username" name="username" required>
          </div>
          <div class="mb-3 position-relative">
            <label for="add-password" class="form-label">Password</label>
            <input type="password" class="form-control" id="add-password" name="password" required>
            <i class="fas fa-eye position-absolute" style="top: 38px; right: 15px; cursor:pointer;" id="toggleAddPassword"></i>
          </div>
          <div class="mb-3">
            <label for="add-role" class="form-label">Role</label>
            <select class="form-select" id="add-role" name="role" required>
              <option value="Cashier">Cashier</option>
              <option value="Staff">Staff</option>
              <option value="Admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_user" class="btn btn-primary users-btn-primary">Add User</button>
          <button type="button" class="btn btn-secondary users-btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header users-modal-header">
          <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit-id" name="id">
          <div class="mb-3">
            <label for="edit-username" class="form-label">Username</label>
            <input type="text" class="form-control" id="edit-username" name="username" required>
          </div>
          <div class="mb-3 position-relative">
            <label for="edit-password" class="form-label">Password (leave blank to keep current)</label>
            <input type="password" class="form-control" id="edit-password" name="password">
            <i class="fas fa-eye position-absolute" style="top: 38px; right: 15px; cursor:pointer;" id="toggleEditPassword"></i>
          </div>
          <div class="mb-3">
            <label for="edit-role" class="form-label">Role</label>
            <select class="form-select" id="edit-role" name="role" required>
              <option value="Cashier">Cashier</option>
              <option value="Staff">Staff</option>
              <option value="Admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="edit_user" class="btn btn-primary users-btn-primary">Save Changes</button>
          <button type="button" class="btn btn-secondary users-btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>

<!-- Include Bootstrap JS and jQuery -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
  // Toggle password visibility for Add User Modal
  $('#toggleAddPassword').on('click', function() {
    var input = $('#add-password');
    if (input.attr('type') === 'password') {
      input.attr('type', 'text');
      $(this).removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      input.attr('type', 'password');
      $(this).removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  // Toggle password visibility for Edit User Modal
  $('#toggleEditPassword').on('click', function() {
    var input = $('#edit-password');
    if (input.attr('type') === 'password') {
      input.attr('type', 'text');
      $(this).removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      input.attr('type', 'password');
      $(this).removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  // Function to open the Edit User Modal and populate fields
  function openEditModal(userId) {
    var users = <?php echo json_encode($users); ?>;
    // Find the user (using non-strict comparison for id)
    var user = users.find(u => u.id == userId);
    if (user) {
      $('#edit-id').val(user.id);
      $('#edit-username').val(user.username);
      $('#edit-role').val(user.role);
      $('#edit-password').val('');
      var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
      editModal.show();
    }
  }

  // Initialize and show toast messages (if any)
  var toastElList = [].slice.call(document.querySelectorAll('.users-toast'));
  var toastList = toastElList.map(function(toastEl) {
    return new bootstrap.Toast(toastEl, { delay: 3000 }).show();
  });
</script>
