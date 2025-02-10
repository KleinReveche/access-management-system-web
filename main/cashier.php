<?php
include('../includes/header.php');
checkUserAccess(['Admin', 'Cashier', 'Staff']);

// Ensure the user is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: access_denied.php');
    exit();
}

// Fetch current financial data
$stmt_financials = $pdo->prepare("SELECT * FROM cashier_data LIMIT 1");
$stmt_financials->execute();
$financial_data = $stmt_financials->fetch(PDO::FETCH_ASSOC);

$capital = $financial_data['capital'] ?? 0;
$cash_on_hand = $financial_data['cash_on_hand'] ?? 0;
$last_updated_raw = $financial_data['last_updated'] ?? null;
$last_updated = ($last_updated_raw)
    ? (new DateTime($last_updated_raw, new DateTimeZone('UTC')))
          ->setTimezone(new DateTimeZone('Asia/Manila'))
          ->format('Y-m-d h:i:s A')
    : 'Not updated yet';

// Fetch the master key from the database
$stmt_key = $pdo->prepare("SELECT * FROM master_key LIMIT 1");
$stmt_key->execute();
$master_key_data = $stmt_key->fetch(PDO::FETCH_ASSOC);
$master_key = $master_key_data['key'] ?? '';

// Fetch logs (latest 5)
$stmt_logs = $pdo->prepare("SELECT * FROM activities_log ORDER BY created_at DESC LIMIT 5");
$stmt_logs->execute();
$logs = $stmt_logs->fetchAll();
?>

<!-- Page-Specific Styles -->
<style>
  :root {
    /* Theme Concept (60/30/10) */
    --cs-primary-color: #1B263B;   /* 60% */
    --cs-secondary-color: #415A77; /* 30% */
    --cs-accent-color: #778DA9;    /* 10% */
    --cs-danger-color: #dc2626;
    --cs-background-color: #F5F5F5;
  }
  body {
    background: var(--cs-background-color);
  }
  /* Main container */
  .cs-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }
  /* Card Styling */
  .cs-card {
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e5e7eb;
  }
  .cs-card-header {
    color: #ffffff;
    padding: 15px;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }
  /* Color Variants for Card Headers */
  .cs-primary { background-color: var(--cs-primary-color); }
  .cs-secondary { background-color: var(--cs-secondary-color); }
  .cs-accent { background-color: var(--cs-accent-color); }
  .cs-card-body {
    padding: 20px;
    text-align: center;
  }
  /* Button Styling */
  .cs-btn-primary {
    background-color: var(--cs-primary-color);
    border: none;
    color: #fff;
    padding: 10px 20px;
    border-radius: 4px;
    transition: background-color 0.3s;
  }
  .cs-btn-primary:hover {
    background-color: var(--cs-secondary-color);
  }
  .cs-btn-warning {
    background-color: var(--cs-secondary-color);
    border: none;
    color: #fff;
    padding: 10px 20px;
    border-radius: 4px;
    transition: background-color 0.3s;
  }
  .cs-btn-warning:hover {
    background-color: var(--cs-accent-color);
  }
  .cs-btn-secondary {
    background-color: var(--cs-accent-color);
    border: none;
    color: #fff;
    padding: 10px 20px;
    border-radius: 4px;
    transition: background-color 0.3s;
  }
  .cs-btn-secondary:hover {
    background-color: var(--cs-secondary-color);
  }
  /* Toast Message Styling (if needed) */
  .cs-toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1055;
    border-radius: 8px;
  }
</style>

<div class="cs-container mt-5">
  <!-- Page Title -->
  <h2 class="text-center mb-4 cs-title">
    <i class="fas fa-cash-register me-2"></i>Cashier Management
  </h2>

  <!-- Current Financial Data Cards -->
  <div class="row mb-4">
    <!-- Current Capital Card -->
    <div class="col-md-4 mb-4">
      <div class="cs-card">
        <div class="cs-card-header cs-primary">
          <h5>Current Capital</h5>
        </div>
        <div class="cs-card-body">
          <h4>₱<?php echo number_format($capital, 2); ?></h4>
        </div>
      </div>
    </div>
    <!-- Cash on Hand Card -->
    <div class="col-md-4 mb-4">
      <div class="cs-card">
        <div class="cs-card-header cs-secondary">
          <h5>Cash on Hand</h5>
        </div>
        <div class="cs-card-body">
          <h4>₱<?php echo number_format($cash_on_hand, 2); ?></h4>
        </div>
      </div>
    </div>
    <!-- Last Updated Card -->
    <div class="col-md-4 mb-4">
      <div class="cs-card">
        <div class="cs-card-header cs-accent">
          <h5>Last Updated</h5>
        </div>
        <div class="cs-card-body">
          <p><?php echo $last_updated; ?></p>
        </div>
      </div>
    </div>
    <!-- Edit Financials Button -->
    <div class="col-12">
      <button class="cs-btn-warning w-100" data-bs-toggle="modal" data-bs-target="#editFinancialsModal">
        Edit Capital &amp; Cash
      </button>
    </div>
  </div>

  <!-- Logs Section -->
  <h3 class="mb-4 cs-logs-title">Recent Changes</h3>
  <div class="cs-card">
    <div class="cs-card-header cs-primary">
      <h5>Change Logs</h5>
    </div>
    <div class="cs-card-body">
      <ul class="list-unstyled">
        <?php foreach ($logs as $log): ?>
          <li class="mb-3">
            <strong><?php echo htmlspecialchars($log['activity_description']); ?></strong>
            <br>
            <small>
              <?php 
                // Convert log time from UTC to Philippine time in 12-hour format
                $dt = new DateTime($log['created_at'], new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                echo $dt->format('Y-m-d h:i:s A');
              ?>
            </small>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<!-- Edit Financials Modal -->
<div class="modal fade" id="editFinancialsModal" tabindex="-1" aria-labelledby="editFinancialsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action=process/process_cashier>
        <div class="modal-header">
          <h5 class="modal-title" id="editFinancialsModalLabel">Edit Capital &amp; Cash on Hand</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="capital" class="form-label">New Capital Amount</label>
            <input type="number" class="form-control" name="capital" id="capital" value="<?php echo $capital; ?>" step="0.01" required>
          </div>
          <div class="mb-3">
            <label for="cash_on_hand" class="form-label">New Cash on Hand Amount</label>
            <input type="number" class="form-control" name="cash_on_hand" id="cash_on_hand" value="<?php echo $cash_on_hand; ?>" step="0.01" required>
          </div>
          <div class="mb-3">
            <label for="master_key" class="form-label">Enter Master Key</label>
            <input type="password" class="form-control" name="master_key" id="master_key" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="cs-btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="cs-btn-primary" name="update_financials">Update Financials</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
