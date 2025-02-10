<?php
include('../includes/header.php');
checkUserAccess(['Admin']);

// Fetch current favicon
$stmt = $pdo->query("SELECT favicon FROM website_settings WHERE id = 1");
$favicon = $stmt->fetchColumn();

// Fetch master key
$stmt_key = $pdo->prepare("SELECT * FROM master_key LIMIT 1");
$stmt_key->execute();
$master_key_data = $stmt_key->fetch(PDO::FETCH_ASSOC);
$master_key = $master_key_data['key_value'] ?? '';

// Fetch recent activity logs (limit to 5)
$stmt_logs = $pdo->prepare("SELECT * FROM activities_log ORDER BY created_at DESC LIMIT 5");
$stmt_logs->execute();
$logs = $stmt_logs->fetchAll();
?>

<!-- Scoped Styles for Keys Page -->
<style>
  /* All styles are scoped under #keys-page to avoid conflicts */
  #keys-page {
    --primary-color: #1B263B;    /* 60% Base Theme Color */
    --secondary-color: #415A77;  /* 30% Lighter Variant */
    --accent-color: #778DA9;     /* 10% Accent */
    --danger-color: #dc2626;
  }
  /* Although body styles are usually global, if needed they can be scoped */
  #keys-page body {
    background: #f3f4f6;
  }
  /* Main container styling */
  #keys-page .main-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }
  /* Header styling */
  #keys-page .header {
    background: #ffffff;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  /* Card styling */
  #keys-page .card {
    background: #ffffff;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e5e7eb;
  }
  #keys-page .card-header {
    background-color: var(--primary-color);
    color: #ffffff;
    padding: 15px;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }
  #keys-page .card-body {
    padding: 20px;
  }
  /* Toast Message Styling (Centered) */
  #keys-page .toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1055;
    border-radius: 8px;
  }
  /* Button Styling Using Theme Variables */
  #keys-page .btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
  }
  #keys-page .btn-primary:hover {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
  }
  #keys-page .btn-secondary {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
  }
  #keys-page .btn-secondary:hover {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
  }
  /* Master Key display styling */
  #keys-page .key-display {
    font-size: 2rem;
    letter-spacing: 0.3rem;
  }
</style>

<!-- Main Content Container -->
<div id="keys-page">
  <div class="container mt-5 main-container">
    <!-- Header Section -->
    <div class="header">
      <h2 class="h4 mb-0">
        <i class="fas fa-key me-2"></i>Master Key Management
      </h2>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editMasterKeyModal">
        <i class="fas fa-edit me-2"></i>Update Key
      </button>
    </div>

    <!-- Key Status Card -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Current Master Key</h5>
      </div>
      <div class="card-body text-center">
        <div class="mb-4">
          <h3 id="master-key-display" class="key-display">
            <?= $master_key ? '••••••••' : 'Not Set' ?>
          </h3>
        </div>
        <button id="toggle-key-visibility" class="btn btn-outline-secondary" data-show="0">
          <i class="fas fa-eye me-2"></i>Show Key
        </button>
      </div>
    </div>

    <!-- Activity Logs Card -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-clock-rotate-left me-2"></i>Recent Activity</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Activity</th>
                <th>Timestamp</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td>
                    <i class="fas fa-history text-muted me-2"></i>
                    <?= htmlspecialchars($log['activity_description']) ?>
                  </td>
                  <td class="text-muted">
                    <?= date('M j, Y h:i A', strtotime($log['created_at'])) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($logs)): ?>
                <tr>
                  <td colspan="2" class="text-center">No recent activity.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <!-- End of Main Content Container -->

  <!-- Edit Master Key Modal -->
  <div class="modal fade" id="editMasterKeyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="process/process_keys">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-key me-2"></i>Update Master Key
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- New Master Key -->
            <div class="mb-3">
              <label class="form-label">New Master Key</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" name="new_master_key" required>
              </div>
            </div>
            <!-- Confirm Master Key -->
            <div class="mb-3">
              <label class="form-label">Confirm Master Key</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" name="confirm_master_key" required>
              </div>
            </div>
            <!-- Current Master Key -->
            <div class="mb-3">
              <label class="form-label">Current Master Key</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" class="form-control" name="master_key" required>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" name="update_key">
              <i class="fas fa-save me-2"></i>Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- End of #keys-page container -->

<?php include('../includes/footer.php'); ?>

<!-- Include Bootstrap JS (and jQuery if needed) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle Master Key Visibility
  document.getElementById('toggle-key-visibility').addEventListener('click', function() {
    var display = document.getElementById('master-key-display');
    var currentKey = '<?= $master_key ?>';
    if (this.getAttribute('data-show') === '0') {
      display.textContent = currentKey || 'No key set';
      this.innerHTML = '<i class="fas fa-eye-slash me-2"></i>Hide Key';
      this.setAttribute('data-show', '1');
    } else {
      display.textContent = currentKey ? '••••••••' : 'Not Set';
      this.innerHTML = '<i class="fas fa-eye me-2"></i>Show Key';
      this.setAttribute('data-show', '0');
    }
  });
</script>
