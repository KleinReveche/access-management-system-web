<?php 
include('../includes/header.php');
checkUserAccess(['Admin', 'Cashier', 'Staff']);

// Fetch products (only non-deleted) and categories
$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name 
                        FROM products p 
                        JOIN product_categories c ON p.category_id = c.id 
                        WHERE p.deleted_at IS NULL");
$stmt->execute();
$products = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM product_categories WHERE deleted_at IS NULL");
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<!-- Localized and Themed Styles for the POS Page -->
<style>
  /* Namespaced styles for the POS page with the new theme */
  #posPage {
    /* Theme Variables: Primary (60%), Secondary (30%), Accent (10%), Danger */
    --primary-color: #1B263B;
    --secondary-color: #415A77;
    --accent-color: #778DA9;
    --danger-color: #dc2626;

    font-size: 1.32rem; /* Increased base font-size */
    background: #f3f4f6;
    padding: 1rem;
    box-sizing: border-box;
  }
  #posPage .pos-container {
    display: grid;
    grid-template-columns: 3fr 1fr;
    gap: 1rem;
    width: 95%;
    max-width: 1920px;
    margin: 0 auto;
  }
  @media (max-width: 768px) {
    #posPage .pos-container {
      grid-template-columns: 1fr;
    }
  }
  /* Product Section */
  #posPage .product-section {
    background: #ffffff;
    border-radius: 14.4px;
    padding: 1.2rem;
    box-shadow: 0 4.8px 14.4px rgba(0,0,0,0.05);
  }
  #posPage .category-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    margin-bottom: 1.2rem;
    padding: 0.9rem;
    background: #f8fafc;
    border-radius: 14.4px;
  }
  #posPage .category-tab {
    padding: 0.9rem 1.5rem;
    border-radius: 119%;
    background: #e2e8f0;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 1.32rem;
    white-space: nowrap;
  }
  /* Use the new primary color for active tabs */
  #posPage .category-tab.active {
    background: var(--primary-color);
    color: #ffffff;
  }
  #posPage .product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1.2rem;
    overflow-y: auto;
    max-height: 70vh;
    padding-right: 0.6rem;
  }
  #posPage .product-card {
    background: #ffffff;
    border-radius: 14.4px;
    padding: 1.2rem;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #e5e7eb;
  }
  #posPage .product-card:hover {
    transform: translateY(-4.8px);
    box-shadow: 0 7.2px 9.6px rgba(0,0,0,0.1);
  }
  #posPage .product-image {
    width: 100%;
    height: 144px;
    object-fit: cover;
    border-radius: 9.6px;
    margin-bottom: 0.9rem;
  }
  /* Order Panel */
  #posPage .order-panel {
    background: #ffffff;
    border-radius: 14.4px;
    padding: 1.8rem;
    display: flex;
    flex-direction: column;
    box-shadow: 0 7.2px 9.6px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    position: sticky;
    top: 1rem;
    max-height: 80vh;
    overflow-y: auto;
  }
  /* Total Display uses the primary theme color */
  #posPage .total-display {
    background: var(--primary-color);
    color: #ffffff;
    padding: 1.2rem;
    border-radius: 14.4px;
    margin-bottom: 1.2rem;
    text-align: center;
    font-size: 1.8rem;
  }
  #posPage .order-items {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 1.2rem;
    max-height: 300px;
  }
  #posPage .order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.2rem 0;
    border-bottom: 1px solid #e5e7eb;
    font-size: 1.2rem;
  }
  #posPage .quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }
  /* Button and Input Adjustments */
  #posPage .btn {
    font-size: 1.32rem;
    padding: 0.9rem 1.5rem;
  }
  #posPage .form-control, 
  #posPage .form-select {
    font-size: 1.32rem;
    padding: 0.9rem;
  }
  /* Toast Container inside Order Panel */
  #posPage .toast-container {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translate(-50%, 0);
    z-index: 1080;
    width: 100%;
    pointer-events: none;
  }
  #posPage .toast {
    margin: 0.3rem auto;
    pointer-events: auto;
  }
</style>

<div id="posPage">
  <div class="pos-container">
    <!-- Product Section -->
    <div class="product-section">
      <div class="category-tabs">
        <div class="category-tab active" data-category="all">All</div>
        <?php foreach ($categories as $category) { ?>
          <div class="category-tab" data-category="<?php echo $category['id']; ?>">
            <?php echo htmlspecialchars($category['name']); ?>
          </div>
        <?php } ?>
      </div>
      <div class="product-grid">
        <?php foreach ($products as $product) { ?>
          <div class="product-card" 
               data-category="<?php echo $product['category_id']; ?>"
               onclick="addToOrder(<?php echo $product['id']; ?>, '<?php echo addslashes(htmlspecialchars($product['name'])); ?>', <?php echo $product['price']; ?>)">
            <img src="uploads/<?php echo $product['image'] ?: 'default.png'; ?>" 
                 class="product-image" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>">
            <div class="fw-bold text-truncate"><?php echo htmlspecialchars($product['name']); ?></div>
            <div class="text-primary fw-bold">₱<?php echo number_format($product['price'], 2); ?></div>
          </div>
        <?php } ?>
      </div>
    </div>
    
    <!-- Order Panel -->
    <div class="order-panel">
      <!-- Toast Container inside Order Panel -->
      <div class="toast-container" id="toastContainer"></div>
      
      <div class="total-display">
        Total: ₱<span id="total-amount">0.00</span>
      </div>
      <div class="order-items" id="order-items">
        <div class="text-center text-muted py-4">No items selected</div>
      </div>
      <!-- Payment Options Form -->
      <form method="POST" id="order-form">
        <input type="hidden" name="total_amount" id="order-total" value="0.00">
        <input type="hidden" name="order_details" id="order-details">
        <div class="mb-3">
          <select id="payment-method" name="payment_method" class="form-select">
            <option value="cash">Cash</option>
            <option value="online_wallet">Online Wallet</option>
          </select>
        </div>
        <!-- The "Place Order" button opens the modal for customer & payment details -->
        <button type="button" class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
          <i class="fa-solid fa-check me-2"></i> Place Order
        </button>
        <button type="button" class="btn btn-danger w-100" id="clear-order">
          <i class="fa-solid fa-ban me-2"></i> Clear Order
        </button>
      </form>
    </div>
  </div>

  <!-- Unified Payment & Customer Details Modal -->
  <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <!-- Larger size -->
      <div class="modal-content">
        <div class="modal-header" style="background: var(--primary-color); color: #fff;">
          <!-- The modal header text will be updated during processing -->
          <h5 class="modal-title" id="paymentModalLabel">Customer & Payment Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Customer Info -->
          <div class="mb-3">
            <label for="modal_customer_name" class="form-label">Customer Name</label>
            <input type="text" id="modal_customer_name" class="form-control" placeholder="Enter Customer Name" required>
          </div>
          <div class="mb-3">
            <label for="modal_grade_program" class="form-label">Grade / Program</label>
            <input type="text" id="modal_grade_program" class="form-control" placeholder="Enter Grade / Program" required>
          </div>
          <!-- Conditional Payment Fields -->
          <div id="modal_cash_fields" style="display: none;">
            <div class="mb-3">
              <label for="modal_cash_paid" class="form-label">Cash Paid</label>
              <input type="number" step="0.01" id="modal_cash_paid" class="form-control" placeholder="Enter Cash Paid">
            </div>
            <div class="mb-3">
              <label for="modal_change_amount" class="form-label">Change Amount</label>
              <input type="number" step="0.01" id="modal_change_amount" class="form-control" placeholder="Change Amount" readonly>
            </div>
          </div>
          <div id="modal_wallet_fields" style="display: none;">
            <div class="mb-3">
              <label for="modal_transaction_id" class="form-label">Transaction ID</label>
              <input type="text" id="modal_transaction_id" class="form-control" placeholder="Enter Transaction ID">
            </div>
            <div class="mb-3">
              <label for="modal_payment_proof" class="form-label">Payment Proof (Screenshot)</label>
              <input type="file" id="modal_payment_proof" class="form-control">
            </div>
          </div>
          <!-- Signature (Optional) -->
          <div class="mb-3">
            <label for="modal_signature" class="form-label">Signature (Optional)</label>
            <textarea id="modal_signature" class="form-control" placeholder="Enter Signature (Optional)"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <!-- Cancel button simply dismisses the modal -->
          <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">Cancel</button>
          <!-- Confirm Order button submits the order -->
          <button type="button" class="btn btn-success btn-lg" id="confirm-order">Confirm Order</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- jQuery & Bootstrap JS Bundle -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  $(document).ready(function() {
    let order = [];
    let total = 0;

    // Category filtering
    $('.category-tab').click(function() {
      $('.category-tab').removeClass('active');
      $(this).addClass('active');
      const category = $(this).data('category');
      $('.product-card').each(function() {
        if (category === 'all' || $(this).data('category') == category) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    });

    // Add to order function
    window.addToOrder = function(id, name, price) {
      const existing = order.find(item => item.id === id);
      if (existing) {
        existing.quantity++;
        existing.total = existing.quantity * price;
      } else {
        order.push({
          id: id,
          name: name,
          price: price,
          quantity: 1,
          total: price
        });
      }
      updateOrderDisplay();
      showToast(`${name} added to order.`, 'success');
    };

    // Update order display and hidden fields
    function updateOrderDisplay() {
      $('#order-items').empty();
      total = 0;
      if (order.length === 0) {
        $('#order-items').html('<div class="text-center text-muted py-4">No items selected</div>');
      } else {
        order.forEach(item => {
          total += item.total;
          const itemHtml = `
            <div class="order-item">
              <div class="flex-grow-1">
                <div class="fw-bold">${item.name}</div>
                <div class="small text-muted">₱${item.price.toFixed(2)} each</div>
              </div>
              <div class="quantity-controls">
                <button class="btn btn-sm btn-outline-secondary quantity-btn" data-id="${item.id}" data-action="decrease">
                  <i class="fas fa-minus"></i>
                </button>
                <span class="px-2">${item.quantity}</span>
                <button class="btn btn-sm btn-outline-secondary quantity-btn" data-id="${item.id}" data-action="increase">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
              <div class="fw-bold ms-3">₱${item.total.toFixed(2)}</div>
            </div>
          `;
          $('#order-items').append(itemHtml);
        });
      }
      $('#total-amount').text(total.toFixed(2));
      $('#order-total').val(total.toFixed(2));
      $('#order-details').val(JSON.stringify(order));
    }

    // Quantity controls
    $(document).on('click', '.quantity-btn', function() {
      const itemId = $(this).data('id');
      const action = $(this).data('action');
      const item = order.find(i => i.id === itemId);
      if (action === 'increase') {
        item.quantity++;
      } else if (action === 'decrease') {
        if (item.quantity > 1) {
          item.quantity--;
        } else {
          order = order.filter(i => i.id !== itemId);
        }
      }
      if(item) {
        item.total = item.quantity * item.price;
      }
      updateOrderDisplay();
    });

    // Clear order
    $('#clear-order').click(() => {
      order = [];
      updateOrderDisplay();
      showToast('Order cleared.', 'warning');
    });

    // Payment method selection handler for modal
    function adjustModalFields() {
      const paymentMethod = $('#payment-method').val();
      if (paymentMethod === 'cash') {
        $('#modal_cash_fields').show();
        $('#modal_wallet_fields').hide();
      } else if (paymentMethod === 'online_wallet') {
        $('#modal_cash_fields').hide();
        $('#modal_wallet_fields').show();
      } else {
        $('#modal_cash_fields, #modal_wallet_fields').hide();
      }
    }
    $('#payment-method').change(adjustModalFields);
    $('#payment-method').trigger('change');

    // When customer details modal is shown, adjust its fields based on payment method
    $('#paymentModal').on('show.bs.modal', function() {
      adjustModalFields();
    });

    // Auto-calculate change in modal when cash paid is entered
    $('#modal_cash_paid').on('input', function() {
      const cashPaid = parseFloat($(this).val());
      const totalAmount = parseFloat($('#order-total').val());
      if (!isNaN(cashPaid) && !isNaN(totalAmount)) {
        const change = cashPaid - totalAmount;
        $('#modal_change_amount').val(change.toFixed(2));
      } else {
        $('#modal_change_amount').val('');
      }
    });

    // Toast message function using Bootstrap Toasts
    function showToast(message, type = 'info') {
      // Remove any existing toast messages so that only one is displayed
      $('#toastContainer').empty();
      const toastId = 'toast-' + Date.now();
      const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      `;
      $('#toastContainer').append(toastHtml);
      var toastEl = document.getElementById(toastId);
      var toast = new bootstrap.Toast(toastEl, { delay: 5000 });
      toast.show();
      toastEl.addEventListener('hidden.bs.toast', function () {
        $(toastEl).remove();
      });
    }

    // Confirm Order from modal: gather data and submit via AJAX
    $('#confirm-order').click(function() {
      if ($('#modal_customer_name').val().trim() === "" || $('#modal_grade_program').val().trim() === "") {
        showToast('Please fill in the customer details.', 'warning');
        return;
      }
      // Change the modal header to indicate processing
      $("#paymentModalLabel").text("Processing Order...");
      
      const orderDetails = $('#order-details').val();
      const totalAmount  = $('#order-total').val();
      const paymentMethod= $('#payment-method').val();

      const formData = new FormData();
      formData.append('order_details', orderDetails);
      formData.append('total_amount', totalAmount);
      formData.append('payment_method', paymentMethod);
      formData.append('action', 'place_order');

      formData.append('customer_name', $('#modal_customer_name').val());
      formData.append('grade_program', $('#modal_grade_program').val());
      if (paymentMethod === 'cash') {
        formData.append('cash_paid', $('#modal_cash_paid').val());
        formData.append('change_amount', $('#modal_change_amount').val());
      } else if (paymentMethod === 'online_wallet') {
        formData.append('transaction_id', $('#modal_transaction_id').val());
        const proofFile = $('#modal_payment_proof')[0].files[0];
        if (proofFile) {
          formData.append('payment_proof', proofFile);
        }
      }
      formData.append('signature', $('#modal_signature').val());

      $.ajax({
        url: 'process/process_order',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          if(response.trim() === "success"){
            showToast('Order placed successfully!', 'success');
            // Clear order data and update display
            order = [];
            updateOrderDisplay();
            // Clear all modal fields
            $('#modal_customer_name, #modal_grade_program, #modal_cash_paid, #modal_change_amount, #modal_transaction_id, #modal_signature').val('');
            $('#modal_payment_proof').val('');
            $('#payment-method').val('cash').trigger('change');
            // Close all open modals and remove backdrop
            $('.modal').modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            // Reset the modal header text for the next order
            $("#paymentModalLabel").text("Customer & Payment Details");
          } else {
            // Reset modal header text on error
            $("#paymentModalLabel").text("Customer & Payment Details");
            showToast(response.trim(), 'danger');
          }
        },
        error: function() {
          $("#paymentModalLabel").text("Customer & Payment Details");
          showToast('Error with the request.', 'danger');
        }
      });
    });
  });
</script>
<?php include('../includes/footer.php'); ?>
