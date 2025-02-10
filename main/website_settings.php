<?php
include('../includes/header.php');
checkUserAccess(['Admin']);

// Single query for favicon
$stmt = $pdo->query("SELECT favicon FROM website_settings WHERE id = 1");
$favicon = $stmt->fetchColumn();

// Default settings
$settings = [
    'site_title'         => 'POS System',
    'site_description'   => 'Point of Sale System',
    'contact_email'      => 'admin@pos.com',
    'favicon'            => '',
    'school_logo'        => '',
    'organization_logo'  => '',
    'maintenance_mode'   => 0,
    'meta_title'         => 'POS System',
    'meta_description'   => 'Manage your business with our Point of Sale system',
    'meta_keywords'      => 'POS, Point of Sale, business management, inventory system'
];

// Fetch settings from database
try {
    $stmt = $pdo->query("SELECT * FROM website_settings WHERE id = 1");
    if ($db_settings = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings = array_merge($settings, $db_settings);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $site_title       = filter_input(INPUT_POST, 'site_title', FILTER_SANITIZE_STRING);
    $site_description = filter_input(INPUT_POST, 'site_description', FILTER_SANITIZE_STRING);
    $contact_email    = filter_input(INPUT_POST, 'contact_email', FILTER_VALIDATE_EMAIL);
    $meta_title       = filter_input(INPUT_POST, 'meta_title', FILTER_SANITIZE_STRING);
    $meta_description = filter_input(INPUT_POST, 'meta_description', FILTER_SANITIZE_STRING);
    $meta_keywords    = filter_input(INPUT_POST, 'meta_keywords', FILTER_SANITIZE_STRING);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

    if (!$contact_email) {
        $error = "Invalid email format!";
    } else {
        // Handle file uploads
        $uploads = [
            'favicon'           => handleFileUpload('favicon', $settings['favicon']),
            'school_logo'       => handleFileUpload('school_logo', $settings['school_logo']),
            'organization_logo' => handleFileUpload('organization_logo', $settings['organization_logo'])
        ];

        if (in_array(false, $uploads, true)) {
            $error = "File upload failed! Please check file types (PNG, JPG, JPEG, GIF, ICO) and size (<25MB).";
        } else {
            try {
                // Update database settings using a REPLACE INTO statement
                $sql = "REPLACE INTO website_settings (
                            id, site_title, site_description, contact_email, 
                            meta_title, meta_description, meta_keywords, favicon, school_logo, organization_logo, maintenance_mode
                        ) VALUES (
                            1, :site_title, :site_description, :contact_email, 
                            :meta_title, :meta_description, :meta_keywords, :favicon, :school_logo, :organization_logo, :maintenance_mode
                        )";
                
                $stmt = $pdo->prepare($sql);
                $params = [
                    ':site_title'        => $site_title,
                    ':site_description'  => $site_description,
                    ':contact_email'     => $contact_email,
                    ':meta_title'        => $meta_title,
                    ':meta_description'  => $meta_description,
                    ':meta_keywords'     => $meta_keywords,
                    ':favicon'           => $uploads['favicon'] ?? $settings['favicon'],
                    ':school_logo'       => $uploads['school_logo'] ?? $settings['school_logo'],
                    ':organization_logo' => $uploads['organization_logo'] ?? $settings['organization_logo'],
                    ':maintenance_mode'  => $maintenance_mode
                ];
                
                if ($stmt->execute($params)) {
                    $success = "Settings updated successfully!";
                    // Refresh settings
                    $stmt = $pdo->query("SELECT * FROM website_settings WHERE id = 1");
                    $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: $settings;
                }
            } catch (PDOException $e) {
                error_log("Update error: " . $e->getMessage());
                $error = "Failed to update settings. Please try again.";
            }
        }
    }
}

// File upload handler function
function handleFileUpload($field, $current) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return $current;
    }

    $file     = $_FILES[$field];
    // Allowed MIME types now include common ICO file MIME types
    $allowed  = ['image/png', 'image/jpeg', 'image/gif', 'image/ico', 'image/x-icon', 'image/vnd.microsoft.icon'];
    $max_size = 25 * 1024 * 1024; // 25MB

    if (!in_array($file['type'], $allowed) || $file['size'] > $max_size) {
        return false;
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $ext;
    $target   = "uploads/ws/" . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        // Delete old file if exists
        if ($current && file_exists($current)) {
            unlink($current);
        }
        return $target;
    }
    
    return false;
}
?>

<!-- Page-Specific Styles -->
<style>
  :root {
    /* Theme Concept: 60% Primary (#1B263B), 30% Secondary (#415A77), 10% Accent (#778DA9) */
    --ws-primary-color: #1B263B;
    --ws-secondary-color: #415A77;
    --ws-accent-color: #778DA9;
    --ws-danger-color: #dc2626;
    --ws-background-color: #F5F5F5;
  }
  body {
    background: var(--ws-background-color);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
  }
  .ws-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }
  .ws-card {
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e5e7eb;
  }
  .ws-card-header {
    background-color: var(--ws-primary-color);
    color: #ffffff;
    padding: 15px;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }
  .ws-card-body {
    padding: 20px;
  }
  .ws-form-group {
    margin-bottom: 20px;
  }
  .ws-form-label {
    font-weight: 600;
    color: var(--ws-primary-color);
    display: block;
    margin-bottom: 5px;
  }
  .ws-preview-img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 4px;
    margin: 10px 0;
  }
  .ws-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
  }
  .ws-switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }
  .ws-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
  }
  .ws-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
  }
  .ws-switch input:checked + .ws-slider {
    background-color: var(--ws-primary-color);
  }
  .ws-switch input:checked + .ws-slider:before {
    transform: translateX(26px);
  }
  .ws-toggle {
    display: flex;
    align-items: center;
  }
  .ws-toggle-status {
    margin-left: 10px;
    color: var(--ws-secondary-color);
  }
  .ws-form-actions {
    text-align: right;
    margin-top: 20px;
  }
  .ws-btn-primary {
    background-color: var(--ws-primary-color);
    border: none;
    color: #fff;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
    font-size: 1rem;
  }
  .ws-btn-primary:hover {
    background-color: var(--ws-secondary-color);
  }
  .ws-toast-container {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1080;
  }
</style>

<!-- Page HTML -->
<div class="ws-container">
  <div class="ws-card ws-settings-card">
    <div class="ws-card-header">
      <h3 class="ws-title"><i class="fas fa-cog ws-icon"></i> POS System Settings</h3>
    </div>
    <div class="ws-card-body">
      <!-- Toast messages are displayed as centered notifications -->
      <form method="POST" enctype="multipart/form-data">
        <div class="row">
          <div class="col-md-6">
            <div class="ws-form-group">
              <label class="ws-form-label">Site Title</label>
              <input type="text" class="form-control" name="site_title" value="<?= htmlspecialchars($settings['site_title']) ?>" required>
            </div>
            <div class="ws-form-group">
              <label class="ws-form-label">Site Description</label>
              <textarea class="form-control" name="site_description" rows="3" required><?= htmlspecialchars($settings['site_description']) ?></textarea>
            </div>
            <div class="ws-form-group">
              <label class="ws-form-label">Contact Email</label>
              <input type="email" class="form-control" name="contact_email" value="<?= htmlspecialchars($settings['contact_email']) ?>" required>
            </div>
            <div class="ws-form-group">
              <label class="ws-form-label">SEO Meta Title</label>
              <input type="text" class="form-control" name="meta_title" value="<?= htmlspecialchars($settings['meta_title']) ?>" required>
            </div>
            <div class="ws-form-group">
              <label class="ws-form-label">SEO Meta Description</label>
              <textarea class="form-control" name="meta_description" rows="3" required><?= htmlspecialchars($settings['meta_description']) ?></textarea>
            </div>
            <div class="ws-form-group">
              <label class="ws-form-label">SEO Meta Keywords</label>
              <input type="text" class="form-control" name="meta_keywords" value="<?= htmlspecialchars($settings['meta_keywords']) ?>" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="ws-form-group">
              <label class="ws-form-label">Maintenance Mode</label>
              <div class="ws-toggle">
                <label class="ws-switch">
                  <input type="checkbox" name="maintenance_mode" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                  <span class="ws-slider"></span>
                </label>
                <span class="ws-toggle-status"><?= $settings['maintenance_mode'] ? 'Enabled' : 'Disabled' ?></span>
              </div>
            </div>
            <div class="ws-form-group">
              <label class="ws-form-label">Favicon</label>
              <!-- Adjust accept attribute to explicitly allow ICO files -->
              <input type="file" class="form-control" name="favicon" accept="image/png, image/jpeg, image/gif, image/x-icon, image/vnd.microsoft.icon">
              <?php if ($settings['favicon']): ?>
                <div class="ws-img-preview">
                  <img src="<?= htmlspecialchars($settings['favicon']) ?>" class="ws-preview-img" alt="Favicon">
                </div>
              <?php endif; ?>
            </div>
            <div class="ws-form-group">
              <label class="ws-form-label">School Logo</label>
              <input type="file" class="form-control" name="school_logo" accept="image/*">
              <?php if ($settings['school_logo']): ?>
                <div class="ws-img-preview">
                  <img src="<?= htmlspecialchars($settings['school_logo']) ?>" class="ws-preview-img" alt="School Logo">
                </div>
              <?php endif; ?>
            </div>
            <div class="ws-form-group">
              <label class="ws-form-label">Organization Logo</label>
              <input type="file" class="form-control" name="organization_logo" accept="image/*">
              <?php if ($settings['organization_logo']): ?>
                <div class="ws-img-preview">
                  <img src="<?= htmlspecialchars($settings['organization_logo']) ?>" class="ws-preview-img" alt="Organization Logo">
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="ws-form-actions">
          <button type="submit" class="ws-btn-primary">
            <i class="fas fa-save ws-icon"></i> Save Settings
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toast Container for Success/Error Messages -->
<div class="ws-toast-container" id="wsToastContainer">
  <?php if (!empty($success)): ?>
    <div id="wsSuccessToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?= $success; ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div id="wsErrorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?= $error; ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Show toast messages centered on the screen on page load
  document.addEventListener("DOMContentLoaded", function(){
    <?php if (!empty($success)): ?>
      var successToastEl = document.getElementById('wsSuccessToast');
      var successToast = new bootstrap.Toast(successToastEl, { delay: 5000 });
      successToast.show();
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      var errorToastEl = document.getElementById('wsErrorToast');
      var errorToast = new bootstrap.Toast(errorToastEl, { delay: 5000 });
      errorToast.show();
    <?php endif; ?>
  });
</script>
<?php include('../includes/footer.php'); ?>
