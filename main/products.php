<?php 
ob_start();
include('../includes/header.php');
checkUserAccess(['Admin', 'Cashier', 'Staff']);

// Fetch website settings (favicon)
$stmt = $pdo->query("SELECT favicon FROM website_settings WHERE id = 1");
$favicon = $stmt->fetchColumn();

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: access_denied.php');
    exit();
}

// Process form submissions and prepare toast messages
$toast = [
    'type'    => '',
    'message' => ''
];

if (isset($_POST['add_product'])) {
    // Add new product
    $name = $_POST['name'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $image = $_FILES['image']['name'];

    // Upload image if provided
    if ($image) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . uniqid() . '_' . basename($image);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        // Save only the filename (or relative path) in DB
        $image = basename($target_file);
    }

    try {
        // Include the added_by field in the INSERT query
        $stmt = $pdo->prepare("INSERT INTO products (name, price, category_id, image, added_by) VALUES (:name, :price, :category_id, :image, :added_by)");
        $stmt->execute([
            'name'        => $name,
            'price'       => $price,
            'category_id' => $category_id,
            'image'       => $image,
            'added_by'    => $_SESSION['admin_username'] // Record who added it
        ]);
        $toast = [
            'type'    => 'success',
            'message' => 'Product added successfully.'
        ];
    } catch (PDOException $e) {
        $toast = [
            'type'    => 'error',
            'message' => 'Error adding product: ' . $e->getMessage()
        ];
    }
        header("Location: " . $_SERVER['PHP_SELF']);
    exit();
} elseif (isset($_POST['edit_product'])) {
    // Edit product
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $newImage = $_FILES['image']['name'];

    if ($newImage) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . uniqid() . '_' . basename($newImage);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);

        // Delete the old image if exists
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $old_image = $stmt->fetchColumn();
        if ($old_image && file_exists("uploads/" . $old_image)) {
            unlink("uploads/" . $old_image);
        }
        $image = basename($target_file);
    } else {
        // Keep the existing image
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $image = $stmt->fetchColumn();
    }

    try {
        $stmt = $pdo->prepare("UPDATE products 
                               SET name = :name, 
                                   price = :price, 
                                   category_id = :category_id, 
                                   image = :image,
                                   updated_by = :updated_by
                               WHERE id = :id");
        $stmt->execute([
            'name'        => $name,
            'price'       => $price,
            'category_id' => $category_id,
            'image'       => $image,
            'updated_by'  => $_SESSION['admin_username'], // Record who updated it
            'id'          => $id
        ]);
        $toast = [
            'type'    => 'success',
            'message' => 'Product updated successfully.'
        ];
    } catch (PDOException $e) {
        $toast = [
            'type'    => 'error',
            'message' => 'Error updating product: ' . $e->getMessage()
        ];
    }
        header("Location: " . $_SERVER['PHP_SELF']);
    exit();
} elseif (isset($_POST['delete_product'])) {
    // Delete product
    $id = $_POST['id'];

    // Delete associated image if exists
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $image = $stmt->fetchColumn();
    if ($image && file_exists("uploads/" . $image)) {
        unlink("uploads/" . $image);
    }

    try {
        $stmt = $pdo->prepare("UPDATE products 
                               SET deleted_by = :deleted_by, 
                                   deleted_at = NOW() 
                               WHERE id = :id");
        $stmt->execute([
            'deleted_by' => $_SESSION['admin_username'], // Record who deleted it
            'id'         => $id
        ]);
        $toast = [
            'type'    => 'success',
            'message' => 'Product deleted successfully.'
        ];
    } catch (PDOException $e) {
        $toast = [
            'type'    => 'error',
            'message' => 'Error deleting product: ' . $e->getMessage()
        ];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch products and categories
$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN product_categories c ON p.category_id = c.id WHERE p.deleted_at IS NULL");
$stmt->execute();
$products = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM product_categories WHERE deleted_at IS NULL");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!-- Inline Styles Scoped to the Products Page -->
<style>
  /* All styles are scoped under #products-page */
  #products-page {
    --primary-color: #1B263B;   /* 60% Base Theme Color */
    --secondary-color: #415A77; /* 30% Lighter Variant */
    --accent-color: #778DA9;    /* 10% Accent */
    --danger-color: #dc2626;
  }
  /* Main Container */
  #products-page .products-main-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }
  /* Header Styling */
  #products-page header.products-header {
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  #products-page header.products-header h1 {
    color: var(--primary-color);
  }
  /* Card Container */
  #products-page .products-card-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }
  /* Product Card Styling */
  #products-page .products-product-card {
    background: #ffffff;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e5e7eb;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  #products-page .products-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
  }
  #products-page .products-product-card h5 {
    color: var(--primary-color);
  }
  #products-page .products-product-card p {
    margin-bottom: 0.5rem;
  }
  /* Image Styling */
  #products-page .products-product-card img {
    border-radius: 8px;
    object-fit: cover;
    height: 180px;
    width: 100%;
    margin-bottom: 0.5rem;
  }
  /* Button Styling Using Theme Variables */
  #products-page .btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
  }
  #products-page .btn-primary:hover {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
  }
  #products-page .btn-danger {
    background-color: var(--danger-color);
    border-color: var(--danger-color);
  }
  #products-page .btn-danger:hover {
    background-color: #e53935;
    border-color: #e53935;
  }
  #products-page .btn-secondary {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
  }
  #products-page .btn-secondary:hover {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
  }
  /* Modal Header Styling */
  #products-page .modal-header {
    background-color: var(--primary-color);
    color: #ffffff;
  }
  /* Toast Message Styling (Centered) */
  #products-page .toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1055;
    border-radius: 8px;
  }
</style>

<!-- Main Content Container -->
<div id="products-page">
  <div class="container-fluid products-main-container">
    <!-- Header -->
    <header class="bg-white shadow-sm products-header p-4 mb-3 d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">
        <i class="fas fa-box-open me-2"></i>Manage Products
      </h1>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="fas fa-plus"></i> Add Product
      </button>
    </header>

    <div class="container-fluid">
      <!-- Toast Message Container -->
      <?php if (!empty($toast['message'])) { ?>
        <div class="toast align-items-center text-white <?php echo ($toast['type'] === 'error') ? 'bg-danger' : 'bg-success'; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="d-flex">
            <div class="toast-body">
              <?php echo htmlspecialchars($toast['message']); ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      <?php } ?>

      <!-- Product Cards -->
      <div class="products-card-container">
        <?php foreach ($products as $product): ?>
          <div class="products-product-card">
            <?php if ($product['image']) { ?>
              <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <?php } else { ?>
              <div class="bg-light d-flex align-items-center justify-content-center mb-2" style="height: 180px; border-radius: 8px;">
                <span class="text-muted">No Image</span>
              </div>
            <?php } ?>
            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
            <p><strong>Price:</strong> ₱<?php echo number_format($product['price'], 2); ?></p>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
            <div class="d-flex justify-content-between">
              <button class="btn btn-secondary btn-sm" onclick="openEditModal(<?php echo htmlspecialchars($product['id']); ?>, '<?php echo addslashes(htmlspecialchars($product['name'])); ?>', <?php echo htmlspecialchars($product['price']); ?>, <?php echo htmlspecialchars($product['category_id']); ?>)">
                <i class="fas fa-edit"></i> Edit
              </button>
              <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
                <button type="submit" name="delete_product" class="btn btn-danger btn-sm">
                  <i class="fas fa-trash"></i> Delete
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Modals -->

  <!-- Add Product Modal (Centered) -->
  <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="name" class="form-label">Product Name</label>
              <input type="text" class="form-control" name="name" id="name" placeholder="Enter product name" required>
            </div>
            <div class="mb-3">
              <label for="price" class="form-label">Price</label>
              <input type="number" class="form-control" name="price" id="price" placeholder="Enter price" step="0.01" required>
            </div>
            <div class="mb-3">
              <label for="category_id" class="form-label">Category</label>
              <select class="form-select" name="category_id" id="category_id" required>
                <option value="" disabled selected>Select category</option>
                <?php foreach ($categories as $category) { ?>
                  <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="image" class="form-label">Product Image</label>
              <input type="file" class="form-control" name="image" id="image" accept="image/*">
            </div>
            <button type="submit" name="add_product" class="btn btn-primary w-100">Add Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Product Modal (Centered) -->
  <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="editProductForm" method="POST" enctype="multipart/form-data">
          <div class="modal-header">
            <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-3">
              <label for="edit_name" class="form-label">Product Name</label>
              <input type="text" class="form-control" name="name" id="edit_name" required>
            </div>
            <div class="mb-3">
              <label for="edit_price" class="form-label">Price</label>
              <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
            </div>
            <div class="mb-3">
              <label for="edit_category_id" class="form-label">Category</label>
              <select class="form-select" name="category_id" id="edit_category_id" required>
                <?php foreach ($categories as $category) { ?>
                  <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="edit_image" class="form-label">Product Image</label>
              <input type="file" class="form-control" name="image" id="edit_image" accept="image/*">
              <small class="text-muted">Leave blank to keep existing image.</small>
            </div>
            <button type="submit" name="edit_product" class="btn btn-success w-100">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- End of #products-page container -->

<!-- Include Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Function to open the Edit Product Modal and pre-fill its fields
  function openEditModal(id, name, price, categoryId) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_category_id').value = categoryId;
    var editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
    editModal.show();
  }

  // Show toast message if one exists
  window.addEventListener('load', function() {
    <?php if (!empty($toast['message'])) { ?>
      var toastEl = document.querySelector('#products-page .toast');
      var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
      toast.show();
    <?php } ?>
  });
</script>

<?php include('../includes/footer.php'); ?>