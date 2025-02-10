<?php
include('../includes/header.php');
checkUserAccess(['Admin', 'Cashier', 'Staff']);

// Get favicon, master key, and any other settings
$stmt = $pdo->query("SELECT favicon FROM website_settings WHERE id = 1");
$favicon = $stmt->fetchColumn();

if (!isset($_SESSION['admin_username'])) {
    header('Location: access_denied.php');
    exit();
}

// Retrieve stored master key (assumes first record is the active key)
$stmt = $pdo->prepare("SELECT key_value FROM master_key ORDER BY id ASC LIMIT 1");
$stmt->execute();
$storedMasterKey = $stmt->fetchColumn();

// Initialize toast messages
$success_message = "";
$error_message = "";

// === HANDLE ADD EXPENSE ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
    $name        = $_POST['name'];
    $amount      = $_POST['amount'];
    $description = $_POST['description'];
    $image       = $_FILES['image'];

    if ($image['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'expenses/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $imageName = uniqid() . '_' . basename($image['name']);
        $imagePath = $uploadDir . $imageName;
        move_uploaded_file($image['tmp_name'], $imagePath);

        try {
            $stmt = $pdo->prepare("INSERT INTO expenses (name, amount, description, image, added_by) VALUES (:name, :amount, :description, :image, :added_by)");
            $stmt->execute([
                'name'        => $name,
                'amount'      => $amount,
                'description' => $description,
                'image'       => $imagePath,
                'added_by'    => $_SESSION['admin_username']
            ]);
            $success_message = "🎉 Expense added successfully!";
        } catch (PDOException $e) {
            $error_message = "❌ Error adding expense: " . $e->getMessage();
        }
    } else {
        $error_message = "❌ Error uploading image.";
    }
        header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// === HANDLE EDIT EXPENSE ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_expense'])) {
    if (!isset($_POST['master_key']) || trim($_POST['master_key']) !== $storedMasterKey) {
        $error_message = "❌ Invalid master key. Expense update aborted.";
    } else {
        $expense_id  = $_POST['expense_id'];
        $name        = $_POST['name'];
        $amount      = $_POST['amount'];
        $description = $_POST['description'];
        $image       = $_FILES['image'];

        try {
            if ($image['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'expenses/';
                $imageName = uniqid() . '_' . basename($image['name']);
                $imagePath = $uploadDir . $imageName;
                move_uploaded_file($image['tmp_name'], $imagePath);

                $stmt = $pdo->prepare("UPDATE expenses SET name = :name, amount = :amount, description = :description, image = :image, edited_by = :edited_by, edited_at = NOW() WHERE id = :expense_id");
                $stmt->execute([
                    'name'        => $name,
                    'amount'      => $amount,
                    'description' => $description,
                    'image'       => $imagePath,
                    'edited_by'   => $_SESSION['admin_username'],
                    'expense_id'  => $expense_id
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE expenses SET name = :name, amount = :amount, description = :description, edited_by = :edited_by, edited_at = NOW() WHERE id = :expense_id");
                $stmt->execute([
                    'name'        => $name,
                    'amount'      => $amount,
                    'description' => $description,
                    'edited_by'   => $_SESSION['admin_username'],
                    'expense_id'  => $expense_id
                ]);
            }
            $success_message = "🎉 Expense updated successfully!";
        } catch (PDOException $e) {
            $error_message = "❌ Error updating expense: " . $e->getMessage();
        }
    }
        header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// === HANDLE DELETE EXPENSE ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_expense'])) {
    if (!isset($_POST['master_key']) || trim($_POST['master_key']) !== $storedMasterKey) {
        $error_message = "❌ Invalid master key. Expense deletion aborted.";
    } else {
        $expense_id = $_POST['expense_id'];
        try {
            $stmt = $pdo->prepare("UPDATE expenses SET deleted_by = :deleted_by, deleted_at = NOW() WHERE id = :expense_id");
            $stmt->execute([
                'deleted_by'  => $_SESSION['admin_username'],
                'expense_id'  => $expense_id
            ]);
            $success_message = "🎉 Expense deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "❌ Error deleting expense: " . $e->getMessage();
        }
    }
        header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// === FETCH EXPENSES ===
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE deleted_at IS NULL ORDER BY date DESC");
$stmt->execute();
$expenses = $stmt->fetchAll();
?>
<!-- Page-Specific Styles Scoped to the Expenses Page -->
<style>
  /* All styles are scoped under #expenses-page */
  #expenses-page {
    --primary-color: #1B263B;    /* 60% - Base Theme Color */
    --secondary-color: #415A77;  /* 30% - Lighter Variant */
    --accent-color: #778DA9;     /* 10% - Accent */
    --danger-color: #dc2626;
  }
  /* Main container styling specific to expenses page */
  #expenses-page .expenses-main-container {
    max-width: 1200px;
    margin: 80px auto 40px auto;  /* Top margin adjusted to avoid overlap with fixed header */
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }
  /* Card container (grid layout for expense cards) specific to expenses page */
  #expenses-page .expenses-card-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }
  /* Expense card styling specific to expenses page */
  #expenses-page .expenses-expense-card {
    background: #ffffff;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  #expenses-page .expenses-expense-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
  }
  #expenses-page .expenses-expense-card h5 {
    color: var(--primary-color);
  }
  #expenses-page .expenses-expense-card p {
    margin-bottom: 0.5rem;
  }
  /* Button Styling Using Theme Variables for expenses page */
  #expenses-page .btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
  }
  #expenses-page .btn-primary:hover {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
  }
  #expenses-page .btn-danger {
    background-color: var(--danger-color);
    border-color: var(--danger-color);
  }
  #expenses-page .btn-danger:hover {
    background-color: #e53935;
    border-color: #e53935;
  }
  #expenses-page .btn-secondary {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
  }
  #expenses-page .btn-secondary:hover {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
  }
  /* Modal Header Styling specific to expenses page */
  #expenses-page .modal-header {
    background-color: var(--primary-color);
    color: #ffffff;
  }
  /* Toast Message Styling (Centered) specific to expenses page */
  #expenses-page .toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1055;
    border-radius: 8px;
  }
</style>

<!-- Wrap all content in a unique container for the expenses page -->
<div id="expenses-page">
  <!-- Main Content Container -->
  <div class="container mt-5 expenses-main-container">
    <!-- Page Header -->
    <header class="bg-white shadow-sm p-4 mb-3">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="h4 mb-0">
          <i class="fas fa-money-check-alt me-2"></i>Manage Expenses
        </h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
          <i class="fas fa-plus"></i> Add Expense
        </button>
      </div>
    </header>

    <!-- Toast Messages -->
    <?php if (!empty($success_message)) { ?>
      <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?php echo $success_message; ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php } ?>
    <?php if (!empty($error_message)) { ?>
      <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?php echo $error_message; ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php } ?>

    <!-- Expense Cards -->
    <div class="expenses-card-container">
      <?php foreach ($expenses as $expense): ?>
        <div class="expenses-expense-card">
          <h5><?php echo htmlspecialchars($expense['name']); ?></h5>
          <p><strong>Amount:</strong> ₱<?php echo number_format($expense['amount'], 2); ?></p>
          <p><strong>Description:</strong> <?php echo htmlspecialchars($expense['description']); ?></p>
          <p>
            <small class="text-muted">
              Date: 
              <?php
                $dt = new DateTime($expense['date'], new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                echo $dt->format('Y-m-d h:i:s A');
              ?>
            </small>
          </p>
          <div class="d-flex gap-2 mt-2">
            <?php if ($expense['image']): ?>
              <button type="button" class="btn btn-secondary btn-sm" onclick="openImageModal('<?php echo $expense['image']; ?>', '<?php echo addslashes(htmlspecialchars($expense['name'])); ?>')">
                <i class="fas fa-eye"></i> View Image
              </button>
            <?php else: ?>
              <button type="button" class="btn btn-secondary btn-sm" disabled>
                <i class="fas fa-eye-slash"></i> No Image
              </button>
            <?php endif; ?>
            <!-- Edit and Delete Buttons -->
            <button type="button" class="btn btn-secondary btn-sm" onclick="openEditModal('<?php echo $expense['id']; ?>')">
              <i class="fas fa-edit"></i> Edit
            </button>
            <button type="button" class="btn btn-danger btn-sm" onclick="openDeleteModal('<?php echo $expense['id']; ?>')">
              <i class="fas fa-trash"></i> Delete
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <!-- End Main Content Container -->

  <!-- Modals -->

  <!-- Add Expense Modal -->
  <div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title" id="addExpenseModalLabel">Add New Expense</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="name" class="form-label">Expense Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="amount" class="form-label">Amount</label>
              <input type="number" name="amount" class="form-control" step="0.01" required>
            </div>
            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="mb-3">
              <label for="image" class="form-label">Image</label>
              <input type="file" name="image" class="form-control" required>
            </div>
            <button type="submit" name="add_expense" class="btn btn-primary w-100">Add Expense</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Expense Modal -->
  <div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title" id="editExpenseModalLabel">Edit Expense</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="expense_id" id="edit_expense_id">
            <div class="mb-3">
              <label for="edit_name" class="form-label">Expense Name</label>
              <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="edit_amount" class="form-label">Amount</label>
              <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" required>
            </div>
            <div class="mb-3">
              <label for="edit_description" class="form-label">Description</label>
              <textarea name="description" id="edit_description" class="form-control"></textarea>
            </div>
            <div class="mb-3">
              <label for="edit_image" class="form-label">Image</label>
              <input type="file" name="image" class="form-control">
            </div>
            <!-- Master Key Field for Edit -->
            <div class="mb-3">
              <label for="edit_master_key" class="form-label">Master Key</label>
              <input type="password" name="master_key" id="edit_master_key" class="form-control" placeholder="Enter master key" required>
            </div>
            <button type="submit" name="edit_expense" class="btn btn-success w-100">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Expense Modal -->
  <div class="modal fade" id="deleteExpenseModal" tabindex="-1" aria-labelledby="deleteExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteExpenseModalLabel">Confirm Delete Expense</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Enter master key to confirm deletion:</p>
            <input type="hidden" name="expense_id" id="delete_expense_id">
            <div class="mb-3">
              <input type="password" name="master_key" class="form-control" placeholder="Master Key" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="delete_expense" class="btn btn-danger">Delete Expense</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- View Image Modal -->
  <div class="modal fade" id="viewImageModal" tabindex="-1" aria-labelledby="viewImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewImageTitle">Expense Image</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <img id="expenseImage" src="" alt="Expense Image" class="img-fluid rounded">
        </div>
      </div>
    </div>
  </div>
</div>
<!-- End #expenses-page container -->

<?php include('../includes/footer.php'); ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
  // Make sure the expense data is available as an array
  var expensesData = <?php echo json_encode($expenses); ?>;
  
  function openEditModal(id) {
    console.log("openEditModal triggered with id:", id);
    var expense = expensesData.find(e => e.id == id);
    console.log("Expense found:", expense);
    if (expense) {
      $('#edit_expense_id').val(expense.id);
      $('#edit_name').val(expense.name);
      $('#edit_amount').val(expense.amount);
      $('#edit_description').val(expense.description);
      // Clear the master key field on open
      $('#edit_master_key').val('');
      var editModal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
      editModal.show();
    }
  }

  function openDeleteModal(id) {
    console.log("openDeleteModal triggered with id:", id);
    $('#delete_expense_id').val(id);
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteExpenseModal'));
    deleteModal.show();
  }

  function openImageModal(imagePath, expenseName) {
    document.getElementById('expenseImage').src = imagePath;
    document.getElementById('viewImageTitle').innerText = expenseName + " - Image";
    var imageModal = new bootstrap.Modal(document.getElementById('viewImageModal'));
    imageModal.show();
  }

  window.addEventListener('load', function() {
    <?php if (!empty($success_message) || !empty($error_message)) { ?>
      var toastEl = document.querySelector('#expenses-page .toast');
      var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
      toast.show();
    <?php } ?>
  });
</script>