<?php
include('../includes/header.php');
checkUserAccess(['Admin', 'Cashier', 'Staff']);

$success = "";
$error   = "";

// Create announcements table if not exists (for initial testing)
// Remove this block once your table is set up.
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_read TINYINT DEFAULT 0,
        added_by VARCHAR(255) NOT NULL,
        deleted_by VARCHAR(255) DEFAULT NULL,
        deleted_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}

// Handle soft delete if requested
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("UPDATE announcements SET deleted_by = ?, deleted_at = NOW() WHERE id = ?");
        if ($stmt->execute([$_SESSION['admin_username'], $delete_id])) {
            $success = "Announcement deleted successfully!";
        } else {
            $error = "Failed to delete announcement.";
        }
    } catch (PDOException $e) {
        $error = "Error deleting announcement: " . $e->getMessage();
    }
}

// Handle form submission for creating an announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['delete_id'])) {
    $title   = htmlspecialchars($_POST['title']);
    $content = htmlspecialchars($_POST['content']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, added_by) VALUES (?, ?, ?)");
        $stmt->execute([$title, $content, $_SESSION['admin_username']]);
        $success = "Announcement created successfully!";
    } catch (PDOException $e) {
        $error = "Error creating announcement: " . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!-- Scoped Styles for Announcement Page -->
<style>
  /* All styles are scoped under #announcement-page */
  #announcement-page {
    --primary-color: #1B263B;    /* 60% Base Theme Color */
    --secondary-color: #415A77;  /* 30% Lighter Variant */
    --accent-color: #778DA9;     /* 10% Accent */
    --danger-color: #dc2626;
    --card-bg: #ffffff;
  }
  #announcement-page .main-container {
    max-width: 1200px;
    margin: 80px auto 40px auto; /* Adjust top margin to avoid fixed header overlap */
    padding: 20px;
  }
  #announcement-page .card {
    background: var(--card-bg);
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
    margin-bottom: 30px;
    overflow: hidden;
  }
  #announcement-page .card-header {
    padding: 20px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  #announcement-page .card-header.primary {
    background-color: var(--secondary-color);
  }
  #announcement-page .card-header.secondary {
    background-color: var(--secondary-color);
  }
  #announcement-page .card-header i {
    font-size: 1.5rem;
  }
  #announcement-page .card-header h5 {
    margin: 0;
    font-weight: 500;
    font-size: 1.25rem;
  }
  #announcement-page .card-body {
    padding: 25px;
  }
  /* Form Elements */
  #announcement-page .form-label {
    font-weight: 500;
    color: var(--primary-color);
  }
  #announcement-page .form-control {
    border-radius: 6px;
    padding: 12px 14px;
    border: 1px solid #ccc;
    transition: border-color 0.3s ease;
  }
  #announcement-page .form-control:focus {
    border-color: var(--primary-color);
    outline: none;
  }
  #announcement-page .btn {
    border-radius: 50px;
    font-weight: 500;
    transition: background-color 0.3s ease;
  }
  #announcement-page .btn-primary {
    background-color: var(--secondary-color);
    border: none;
    padding: 12px 30px;
  }
  #announcement-page .btn-primary:hover {
    background-color: var(--primary-color);
  }
  #announcement-page .btn-danger {
    background-color: var(--danger-color);
    border: none;
    padding: 6px 14px;
    font-size: 0.9rem;
    color: #fff;
  }
  #announcement-page .btn-danger:hover {
    background-color: #b91c1c;
  }
  /* Announcement Item */
  #announcement-page .announcement-item {
    padding: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 20px;
    transition: background-color 0.3s ease;
  }
  #announcement-page .announcement-item:hover {
    background-color: #f9f9f9;
  }
  #announcement-page .announcement-item h6 {
    color: var(--primary-color);
    margin-bottom: 8px;
    font-weight: 500;
  }
  #announcement-page .announcement-item small {
    color: #666;
  }
  /* Toast Container */
  #announcement-page .toast-container {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1080;
    width: 100%;
    max-width: 400px;
  }
  /* Responsive Adjustments */
  @media (max-width: 768px) {
    #announcement-page .card-header h5 {
      font-size: 1.1rem;
    }
    #announcement-page .card-body {
      padding: 20px;
    }
    #announcement-page .main-container {
      padding: 15px;
    }
  }
</style>

<!-- Main Content Container -->
<div id="announcement-page">
  <div class="main-container">
    <!-- Create Announcement Card -->
    <div class="card">
      <div class="card-header primary">
        <i class="bi bi-megaphone"></i>
        <h5>Create Announcement</h5>
      </div>
      <div class="card-body">
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" placeholder="Enter announcement title" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="4" placeholder="Enter announcement content" required></textarea>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send"></i> Publish Announcement
          </button>
        </form>
      </div>
    </div>

    <!-- Recent Announcements Card -->
    <div class="card">
      <div class="card-header secondary">
        <i class="bi bi-list-ul"></i>
        <h5>Recent Announcements</h5>
      </div>
      <div class="card-body">
        <?php
        $stmt = $pdo->query("SELECT * FROM announcements WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
        ?>
        <div class="announcement-item">
          <div class="d-flex align-items-center">
            <i class="bi bi-person-circle me-3" style="font-size: 1.75rem; color: var(--secondary-color);"></i>
            <div class="flex-grow-1">
              <h6><?= htmlspecialchars($row['title']); ?></h6>
              <small>
                <?= date('M d, Y h:i A', strtotime($row['created_at'])); ?> (PH Time)
                <br>
                <em>Added by: <?= htmlspecialchars($row['added_by']); ?></em>
              </small>
            </div>
            <!-- Delete Button -->
            <div class="ms-auto">
              <a href="?delete_id=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this announcement?');">
                <i class="bi bi-trash"></i>
              </a>
            </div>
          </div>
          <p class="mt-3"><?= nl2br(htmlspecialchars($row['content'])); ?></p>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>

  <!-- Toast Container for Success/Error Messages -->
  <div class="toast-container" id="toastContainer">
    <?php if (!empty($success)): ?>
      <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?= $success; ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div id="errorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?= $error; ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<!-- End of #announcement-page container -->

<!-- JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Display toast messages when the page loads, if any exist
  document.addEventListener("DOMContentLoaded", function(){
    <?php if (!empty($success)): ?>
      var successToastEl = document.getElementById('successToast');
      var successToast = new bootstrap.Toast(successToastEl, { delay: 5000 });
      successToast.show();
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      var errorToastEl = document.getElementById('errorToast');
      var errorToast = new bootstrap.Toast(errorToastEl, { delay: 5000 });
      errorToast.show();
    <?php endif; ?>
  });
</script>
<?php include('../includes/footer.php'); ?>
