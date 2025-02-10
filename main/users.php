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
  <!-- Header Section -->
  <header class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded shadow-sm">
    <h1 class="h4 mb-0">
      <i class="fas fa-user-cog me-2"></i> Manage Users
    </h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
      <i class="fas fa-plus"></i> Add User
    </button>
  </header>

  <!-- Toast Messages -->
  <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
    <?php if (isset($success_message)) { ?>
      <div class="toast show align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
          <div class="toast-body"> <?php echo $success_message; ?> </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    <?php } ?>
    <?php if (isset($error_message)) { ?>
      <div class="toast show align-items-center text-white bg-danger border-0" role="alert">
        <div class="d-flex">
          <div class="toast-body"> <?php echo $error_message; ?> </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    <?php } ?>
  </div>

  <!-- Users Grid -->
  <div class="row g-4">
    <?php foreach ($users as $user): ?>
      <div class="col-md-4">
        <div class="card shadow-sm border-0">
          <div class="card-body text-center">
            <h5 class="card-title text-primary"> <?php echo htmlspecialchars($user['username']); ?> </h5>
            <p class="card-text"> <strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?> </p>
            <p class="text-muted small"> Created By: <?php echo htmlspecialchars($user['created_by']); ?> </p>
            <p class="text-muted small">
              Created At: <?php echo (new DateTime($user['created_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Manila'))->format('Y-m-d h:i:s A'); ?>
            </p>
            <div class="d-flex justify-content-center gap-2">
              <button class="btn btn-sm btn-secondary" onclick="openEditModal('<?php echo htmlspecialchars($user['id']); ?>')">
                <i class="fas fa-edit"></i> Edit
              </button>
              <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                <button type="submit" name="delete_user" class="btn btn-sm btn-danger">
                  <i class="fas fa-trash"></i> Delete
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

