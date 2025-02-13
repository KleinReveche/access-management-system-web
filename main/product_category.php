<?php
ob_start();
include('../includes/header.php');
checkUserAccess(['Admin', 'Cashier', 'Staff']);

// Fetch favicon from website settings
$stmt = $pdo->query("SELECT favicon FROM website_settings WHERE id = 1");
$favicon = $stmt->fetchColumn();

if (!isset($_SESSION['admin_username'])) {
    header('Location: access_denied.php');
    exit();
}

/**
 * Log category actions.
 *
 * @param string $action  The action performed.
 * @param string $details Details about the action.
 */
function logAction($action, $details) {
    $logFile = '../logs/category_actions.log';
    $logEntry = date('Y-m-d H:i:s') . " | " . $_SESSION['admin_username'] . " | " . $action . " | " . $details . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Process form submissions for adding, editing, and deleting categories
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        // Add new category
        $name = trim($_POST['name']);
        if ($name !== "") {
            try {
                $stmt = $pdo->prepare("INSERT INTO product_categories (name, added_by) VALUES (:name, :added_by)");
                $stmt->execute([
                    'name'     => $name,
                    'added_by' => $_SESSION['admin_username']
                ]);
                $_SESSION['success_message'] = "Category added successfully.";
                logAction("ADD CATEGORY", "Added category '$name'.");
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error adding category: " . $e->getMessage();
                logAction("ADD CATEGORY ERROR", "Error adding category '$name'. Error: " . $e->getMessage());
            }
        } else {
            $_SESSION['error_message'] = "Category name cannot be empty.";
        }
    } elseif (isset($_POST['edit_category'])) {
        // Edit existing category
        $id   = $_POST['id'];
        $name = trim($_POST['name']);
        if ($name !== "") {
            try {
                $stmt = $pdo->prepare("UPDATE product_categories SET name = :name, updated_by = :updated_by WHERE id = :id");
                $stmt->execute([
                    'name'       => $name,
                    'updated_by' => $_SESSION['admin_username'],
                    'id'         => $id
                ]);
                $_SESSION['success_message'] = "Category updated successfully.";
                logAction("EDIT CATEGORY", "Updated category id '$id' to '$name'.");
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error updating category: " . $e->getMessage();
                logAction("EDIT CATEGORY ERROR", "Error updating category id '$id'. Error: " . $e->getMessage());
            }
        } else {
            $_SESSION['error_message'] = "Category name cannot be empty.";
        }
    } elseif (isset($_POST['delete_category'])) {
        // Delete category (soft delete)
        $id = $_POST['id'];
        // Check if the category is linked to any products
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id");
        $stmt->execute(['id' => $id]);
        $productCount = $stmt->fetchColumn();
        if ($productCount > 0) {
            $_SESSION['error_message'] = "Cannot delete category: It is linked to one or more products.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE product_categories SET deleted_by = :deleted_by, deleted_at = NOW() WHERE id = :id");
                $stmt->execute([
                    'deleted_by' => $_SESSION['admin_username'],
                    'id'         => $id
                ]);
                $_SESSION['success_message'] = "Category deleted successfully.";
                logAction("DELETE CATEGORY", "Soft-deleted category id '$id'.");
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Error deleting category: " . $e->getMessage();
                logAction("DELETE CATEGORY ERROR", "Error deleting category id '$id'. Error: " . $e->getMessage());
            }
        }
    }

    // Redirect to the same page to clear POST data and prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch active categories (excluding soft-deleted ones)
$stmt = $pdo->prepare("SELECT * FROM product_categories WHERE deleted_at IS NULL ORDER BY created_at DESC");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories</title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($favicon); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Scoped Styles -->
    <style>
        #product-category-page {
            --primary-color: #1B263B;
            --secondary-color: #415A77;
            --accent-color: #778DA9;
            --danger-color: #dc2626;
            background: url('your-image-url.jpg') no-repeat center center;
            background-size: cover;
        }
        #product-category-page body {
            background: #f3f4f6;
        }
        #product-category-page .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        #product-category-page .category-card {
            border: none !important; /* Removes the border */
            box-shadow: none !important; /* Ensures no shadow effect */
            background: #ffffff; /* Keeps the background white */
        }


        #product-category-page .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 255, 0.2); /* Light blue shadow effect */
        }

        #product-category-page .category-card h4 {
            color: #0d47a1; /* Deep navy blue for text */
            font-weight: bold;
        }


        /* Buttons */
        #product-category-page .btn-primary {
            background-color: #1B263B;; /* Sky blue */
            border-color:rgb(235, 237, 238);
            color: #ffffff; /* White text */
        }

        #product-category-page .btn-primary:hover {
            background-color: #1B263B;;
            border-color:rgb(240, 241, 241);
        }

        #product-category-page .btn-danger {
            background-color:rgb(240, 236, 236); /* Soft red */
            border-color:#dc2626;
            color:#dc2626;
        }

        #product-category-page .btn-danger:hover {
            background-color: #dc2626;
            border-color: #dc2626;
        }

        #product-category-page .btn-secondary {
            background-color: #1B263B; /* Cyan */
            border-color: #26c6da;
            color: #ffffff;
        }

        #product-category-page .btn-secondary:hover {
            background-color: #00acc1;
            border-color: #00acc1;
        }

        /* Modal Header */
        #product-category-page .modal-header {
            background-color: #1565c0; /* Strong blue */
            color: #ffffff;
        }

        /* Toast Notification */
        #product-category-page .toast {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1055;
            border-radius: 8px;
            background-color: #ffffff;
            color: #0d47a1; /* Deep blue text */
            padding: 1rem;
            border: 1px solidrgb(240, 242, 243);
            box-shadow: 0 4px 10px rgba(0, 0, 255, 0.2);
        }
    </style>
</head>
<body>
<div id="product-category-page">
    <div class="container mt-5 main-container">
        <header class="bg-white shadow-sm p-4 mb-3 d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0"><i class="fas fa-tags me-2"></i>Manage Categories</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </header>

        <!-- Flash Message Toasts -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $_SESSION['success_message']; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $_SESSION['error_message']; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Categories Grid -->
        <div class="row">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <div class="col-md-4 mb-4">
                        <div class="category-card">
                            <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                            <p><strong>ID:</strong> <?php echo htmlspecialchars($category['id']); ?></p>
                            <div class="d-flex gap-2 mt-2">
                                <button class="btn btn-secondary btn-sm" onclick="openEditModal('<?php echo htmlspecialchars($category['id']); ?>', '<?php echo addslashes(htmlspecialchars($category['name'])); ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <button type="submit" name="delete_category" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-center">No categories available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- End Main Container -->

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add-name" class="form-label">Category Name</label>
                            <input type="text" name="name" id="add-name" class="form-control" placeholder="Enter category name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Add Category Modal -->

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="mb-3">
                            <label for="edit-name" class="form-label">Category Name</label>
                            <input type="text" name="name" id="edit-name" class="form-control" placeholder="Enter category name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="edit_category" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Edit Category Modal -->
</div>

<?php include('../includes/footer.php'); ?>

<!-- Include Bootstrap JS and jQuery -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // Function to open the Edit Category Modal and pre-fill its fields
    function openEditModal(id, name) {
        $('#edit-id').val(id);
        $('#edit-name').val(name);
        var editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        editModal.show();
    }

    // Initialize toast messages (if any)
    var toastElList = [].slice.call(document.querySelectorAll('#product-category-page .toast'));
    var toastList = toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl, { delay: 3000 }).show();
    });
</script>
</body>
</html>
