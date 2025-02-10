<?php
include('../includes/header.php');
checkUserAccess(['Admin', 'Cashier', 'Staff']);

/**
 * Log an order action.
 *
 * @param int         $order_id
 * @param string      $action
 * @param string|null $payment_proof Payment proof from the order (if any)
 */
function logOrderAction($order_id, $action, $payment_proof = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO order_logs (order_id, admin_username, ip_address, action, payment_proof) VALUES (?, ?, ?, ?, ?)");
    $admin_username = isset($_SESSION['admin_username']) && !empty($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt->execute([$order_id, $admin_username, $ip_address, $action, $payment_proof]);
}

/**
 * Fetch orders with optional LIMIT and OFFSET.
 *
 * @param string   $status The order status.
 * @param int|null $limit  Optional limit.
 * @param int|null $offset Optional offset.
 * @return array
 */
function fetchOrdersByStatus($status, $limit = null, $offset = null) {
    global $pdo;
    if ($limit !== null) {
        $stmt = $pdo->prepare("SELECT *, TIMESTAMPDIFF(MINUTE, order_date, NOW()) AS elapsed_time FROM orders WHERE status = ? ORDER BY order_date DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $status);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT *, TIMESTAMPDIFF(MINUTE, order_date, NOW()) AS elapsed_time FROM orders WHERE status = ? ORDER BY order_date DESC");
        $stmt->execute([$status]);
    }
    return $stmt->fetchAll();
}

/**
 * Count orders by status.
 *
 * @param string $status
 * @return int
 */
function countOrdersByStatus($status) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM orders WHERE status = ?");
    $stmt->execute([$status]);
    return $stmt->fetch()['total'];
}

/**
 * Update order status and log if completed.
 *
 * @param int    $order_id
 * @param string $status
 * @return void
 */
function updateOrderStatus($order_id, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        if ($status === 'completed') {
            $stmt2 = $pdo->prepare("SELECT payment_proof FROM orders WHERE id = ?");
            $stmt2->execute([$order_id]);
            $orderData = $stmt2->fetch();
            $payment_proof = isset($orderData['payment_proof']) ? $orderData['payment_proof'] : null;
            logOrderAction($order_id, 'Completed', $payment_proof);
            $_SESSION['success'] = "Order #{$order_id} marked as completed successfully.";
        }
echo '<script>window.location.href = "orders.php";</script>';
exit();
    } catch (PDOException $e) {
        error_log("Error updating order #{$order_id}: " . $e->getMessage());
        $_SESSION['error'] = "Error updating order #{$order_id}. Please check the logs for details.";
        header('Location: orders.php');
        exit();
    }
}

// Handle order completion action
if (isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    updateOrderStatus($order_id, $status);
}

// Pagination and latest orders for dashboard
$limit = 6; // orders per page
$latest_pending_orders = fetchOrdersByStatus('pending', 3, 0);
$latest_completed_orders = fetchOrdersByStatus('completed', 5, 0);
$total_pending = countOrdersByStatus('pending');
$pending_page = isset($_GET['pending_page']) ? (int)$_GET['pending_page'] : 1;
$pending_offset = ($pending_page - 1) * $limit;
$pending_orders_paginated = fetchOrdersByStatus('pending', $limit, $pending_offset);
$total_pending_pages = ceil($total_pending / $limit);
$total_completed = countOrdersByStatus('completed');
$completed_page = isset($_GET['completed_page']) ? (int)$_GET['completed_page'] : 1;
$completed_offset = ($completed_page - 1) * $limit;
$completed_orders_paginated = fetchOrdersByStatus('completed', $limit, $completed_offset);
$total_completed_pages = ceil($total_completed / $limit);
?>

  <!-- Namespaced Styles for Orders Page -->
<style>
    /* Namespaced styles under #ordersPage to prevent conflicts */
    #ordersPage {
      /* Theme Variables (Scoped to orders page) */
      --primary-color: #1B263B;   /* 60% */
      --secondary-color: #415A77; /* 30% */
      --accent-color: #778DA9;    /* 10% */
      --danger-color: #dc2626;
      --highlight-bg: #fff3cd;
      --cash-tag: #28a745;
      --wallet-tag: #007bff;

      background: #fff;
      font-family: 'Roboto', sans-serif;
      font-size: 1.1rem;
      padding: 20px;
    }
    #ordersPage .orders-container {
      width: 100%;
      max-width: 1820px;
      margin: 30px auto;
      padding: 1rem 2rem;
      background: #fff;
      border-top: 8px solid var(--primary-color, #1B263B);
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      border-radius: 8px;
    }
    #ordersPage .orders-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 1rem;
    }
    /* Left Panel */
    #ordersPage .orders-left {
      padding: 1rem;
    }
    #ordersPage .orders-left h2 {
      color: var(--primary-color, #1B263B);
      margin-bottom: 1rem;
    }
    /* Payment Filter Tabs */
    #ordersPage .payment-filter {
      margin-bottom: 1rem;
    }
    #ordersPage .payment-filter .nav-link {
      cursor: pointer;
      font-weight: bold;
    }
    /* Order Card Styling */
    #ordersPage .order-card {
      border: 1px solid #ddd;
      border-radius: 8px;
      margin-bottom: 1rem;
      overflow: hidden;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      background: #fff;
    }
    /* Highlight latest pending order */
    #ordersPage .order-card.latest-pending {
      background: var(--highlight-bg, #fff3cd);
      border-color: var(--primary-color, #1B263B);
    }
    #ordersPage .order-card .card-header {
      background: var(--primary-color, #1B263B);
      color: #fff;
      padding: 0.75rem 1rem;
      font-weight: bold;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    #ordersPage .order-card .order-info {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    /* Payment Tag Styling */
    #ordersPage .payment-tag {
      font-size: 0.85rem;
      font-weight: normal;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      color: #fff;
    }
    #ordersPage .payment-tag.cash { background: var(--cash-tag, #28a745); }
    #ordersPage .payment-tag.wallet { background: var(--wallet-tag, #007bff); }
    #ordersPage .order-card .card-body {
      padding: 1rem;
    }
    #ordersPage .order-card .card-footer {
      background: var(--secondary-color, #415A77);
      padding: 0.75rem 1rem;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
    }
    #ordersPage .btn-custom, 
    #ordersPage .btn-danger-custom {
      padding: 0.5rem 0.75rem;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      color: #fff;
      margin: 2px;
      transition: background 0.3s;
    }
    #ordersPage .btn-custom { background: var(--primary-color, #1B263B); }
    #ordersPage .btn-custom:hover { background: var(--secondary-color, #415A77); }
    #ordersPage .btn-danger-custom { background: var(--danger-color, #dc2626); }
    #ordersPage .btn-danger-custom:hover { background: #e53935; }
    #ordersPage .pagination {
      display: flex;
      justify-content: center;
      list-style: none;
      padding: 0;
    }
    #ordersPage .pagination li { margin: 0 5px; }
    #ordersPage .pagination a {
      text-decoration: none;
      padding: 0.5rem 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      color: var(--primary-color, #1B263B);
    }
    #ordersPage .pagination .active a {
      background: var(--primary-color, #1B263B);
      color: #fff;
      border-color: var(--primary-color, #1B263B);
    }
    /* Right Panel: Dashboard Summary (Sticky) */
    #ordersPage .orders-right {
      background: #fff;
      border-radius: 8px;
      padding: 1.5rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      border: 1px solid #e5e7eb;
      position: sticky;
      top: 30px;
      max-height: 80vh;
      overflow-y: auto;
    }
    #ordersPage .dashboard-section {
      margin-bottom: 2rem;
    }
    #ordersPage .dashboard-section h3 {
      color: var(--primary-color, #1B263B);
      margin-bottom: 0.75rem;
    }
    #ordersPage .dashboard-card {
      background: var(--primary-color, #1B263B);
      color: #fff;
      padding: 1rem;
      border-radius: 8px;
      text-align: center;
    }
    /* Latest Orders List */
    #ordersPage .list-group-item {
      border: 1px solid #ddd;
      padding: 0.75rem;
      border-radius: 4px;
      margin-bottom: 0.5rem;
    }
    #ordersPage .list-group-item.highlight-latest {
      background: var(--highlight-bg, #fff3cd);
      border-color: var(--primary-color, #1B263B);
      font-weight: bold;
    }
    /* Flash Messages */
    #ordersPage .alert {
      padding: 0.75rem;
      margin-bottom: 1rem;
      border-radius: 4px;
      text-align: center;
      font-weight: bold;
    }
    #ordersPage .alert-success { background: #C8E6C9; color: #256029; }
    #ordersPage .alert-danger { background: #FFCDD2; color: #C62828; }
    /* Responsive adjustments */
    @media (max-width: 768px) {
      #ordersPage .orders-grid {
        grid-template-columns: 1fr;
      }
    }
    /* Toast Container (positioned at bottom-right) */
    #ordersPage .toast-container {
      position: fixed;
      bottom: 1rem;
      right: 1rem;
      z-index: 1050;
    }

    /* Additional Styling for Tabs */
    /* Pending & Completed Orders Tabs */
    #ordersPage .nav-tabs .nav-link {
      border: 1px solid transparent;
      border-top-left-radius: 0.25rem;
      border-top-right-radius: 0.25rem;
      color: var(--primary-color, #1B263B);
      background-color: #f8f9fa;
      margin-right: 0.2rem;
      transition: background-color 0.3s, color 0.3s;
    }
    #ordersPage .nav-tabs .nav-link.active {
      color: #fff;
      background-color: var(--primary-color, #1B263B);
      border-color: var(--primary-color, #1B263B) transparent transparent;
    }
    /* Payment Filter Sub-Tabs (All, Cash, Online Wallet) */
    #ordersPage .nav-pills .nav-link {
      border-radius: 0.25rem;
      margin-right: 0.2rem;
      background-color: #f8f9fa;
      color: var(--primary-color, #1B263B);
      transition: background-color 0.3s, color 0.3s;
    }
    #ordersPage .nav-pills .nav-link.active {
      color: #fff;
      background-color: var(--secondary-color, #415A77);
    }
</style>

<div id="ordersPage">
  <div class="orders-container">
    <div class="orders-grid">
      <!-- Left Panel: Orders List -->
      <div class="orders-left">
        <h2>Manage Orders</h2>
        <!-- Flash Messages -->
        <?php if(isset($_SESSION['success'])): ?>
          <div class="alert alert-success" id="flash-message">
            <?php 
              echo $_SESSION['success']; 
              unset($_SESSION['success']);
            ?>
          </div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
          <div class="alert alert-danger" id="flash-message">
            <?php 
              echo $_SESSION['error']; 
              unset($_SESSION['error']);
            ?>
          </div>
        <?php endif; ?>

        <!-- Order Tabs: Pending & Completed -->
        <ul class="nav nav-tabs mb-3" id="ordersTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-orders" type="button" role="tab" aria-controls="pending-orders" aria-selected="true">Pending Orders</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-orders" type="button" role="tab" aria-controls="completed-orders" aria-selected="false">Completed Orders</button>
          </li>
        </ul>

        <!-- Payment Filter Sub-Tabs -->
        <ul class="nav nav-pills mb-3 payment-filter" id="paymentFilter" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" data-filter="all" type="button">All</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-filter="cash" type="button">Cash</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-filter="wallet" type="button">Online Wallet</button>
          </li>
        </ul>

        <!-- Orders Tab Content -->
        <div class="tab-content" id="ordersTabContent">
          <!-- Pending Orders Tab -->
          <div class="tab-pane fade show active" id="pending-orders" role="tabpanel" aria-labelledby="pending-tab">
            <?php $pCount = 0; ?>
            <?php foreach ($pending_orders_paginated as $order): 
              // Determine payment tag.
              $paymentTag = ($order['payment_method'] === 'online_wallet')
                            ? '<span class="payment-tag wallet">Online Wallet</span>'
                            : '<span class="payment-tag cash">Cash</span>';
              // Mark the first pending order as the latest.
              $latestClass = ($pCount === 0) ? 'latest-pending' : '';
              // Output the order date as an ISO 8601 string.
              $orderISO = date("c", strtotime($order['order_date']));
            ?>
              <div class="order-card <?php echo $latestClass; ?>" data-payment="<?php echo ($order['payment_method'] === 'online_wallet') ? 'wallet' : 'cash'; ?>" id="order-<?php echo $order['id']; ?>">
                <div class="card-header">
                  <div class="order-info">
                    <i class="fas fa-spinner fa-spin"></i>
                    Order #<?php echo $order['id']; ?>
                    <span class="elapsed-time-tag"> - Elapsed Time: </span>
                    <!-- The span below will be updated in real time using the ISO 8601 string -->
                    <span class="elapsed-time" data-order-time="<?php echo $orderISO; ?>">Loading...</span>
                  </div>
                  <?php echo $paymentTag; ?>
                </div>
                <div class="card-body">
                  <p><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
                  <p><strong>Total:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                  <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                  <?php if ($order['payment_method'] === 'online_wallet'): ?>
                    <p><strong>Payment:</strong> Online Wallet</p>
                    <?php if (!empty($order['payment_proof'])): ?>
                      <p><strong>Proof:</strong></p>
                      <button class="btn-custom" onclick="viewPaymentProof('<?php echo $order['payment_proof']; ?>')">
                        <i class="fas fa-eye"></i> View Proof
                      </button>
                    <?php else: ?>
                      <p><em>No Proof Uploaded</em></p>
                    <?php endif; ?>
                  <?php else: ?>
                    <p><strong>Payment:</strong> Cash</p>
                  <?php endif; ?>
                </div>
                <div class="card-footer">
                  <form method="POST" style="margin:0;">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <input type="hidden" name="status" value="completed">
                    <button type="submit" class="btn-custom">
                      <i class="fas fa-check"></i> Complete
                    </button>
                  </form>
                  <div>
                    <button class="btn-custom" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                      <i class="fas fa-info-circle"></i> Details
                    </button>
                    <button class="btn-danger-custom" onclick="showVoidOrderModal(<?php echo $order['id']; ?>)">
                      <i class="fas fa-ban"></i> Void
                    </button>
                  </div>
                </div>
              </div>
            <?php $pCount++; endforeach; ?>
            <!-- Pagination for Pending Orders -->
            <?php if ($total_pending_pages > 1): ?>
              <nav aria-label="Pending orders pagination">
                <ul class="pagination">
                  <?php for ($i = 1; $i <= $total_pending_pages; $i++): ?>
                    <li class="<?php if ($i === $pending_page) echo 'active'; ?>">
                      <a href="?pending_page=<?php echo $i; ?><?php echo isset($_GET['completed_page']) ? '&completed_page=' . $_GET['completed_page'] : ''; ?>">
                        <?php echo $i; ?>
                      </a>
                    </li>
                  <?php endfor; ?>
                </ul>
              </nav>
            <?php endif; ?>
          </div>

          <!-- Completed Orders Tab (Elapsed Time not shown) -->
          <div class="tab-pane fade" id="completed-orders" role="tabpanel" aria-labelledby="completed-tab">
            <?php foreach ($completed_orders_paginated as $order): 
              $paymentTag = ($order['payment_method'] === 'online_wallet')
                            ? '<span class="payment-tag wallet">Online Wallet</span>'
                            : '<span class="payment-tag cash">Cash</span>';
            ?>
              <div class="order-card" data-payment="<?php echo ($order['payment_method'] === 'online_wallet') ? 'wallet' : 'cash'; ?>" id="order-<?php echo $order['id']; ?>">
                <div class="card-header">
                  <div class="order-info">
                    <i class="fas fa-check"></i>
                    Order #<?php echo $order['id']; ?>
                  </div>
                  <?php echo $paymentTag; ?>
                </div>
                <div class="card-body">
                  <p><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
                  <p><strong>Total:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                  <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                  <?php if ($order['payment_method'] === 'online_wallet'): ?>
                    <p><strong>Payment:</strong> Online Wallet</p>
                    <?php if (!empty($order['payment_proof'])): ?>
                      <p><strong>Proof:</strong></p>
                      <button class="btn-custom" onclick="viewPaymentProof('<?php echo $order['payment_proof']; ?>')">
                        <i class="fas fa-eye"></i> View Proof
                      </button>
                    <?php else: ?>
                      <p><em>No Proof Uploaded</em></p>
                    <?php endif; ?>
                  <?php else: ?>
                    <p><strong>Payment:</strong> Cash</p>
                  <?php endif; ?>
                </div>
                <div class="card-footer">
                  <button class="btn-custom" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                    <i class="fas fa-info-circle"></i> Details
                  </button>
                  <button class="btn-custom" onclick="showReceipt(<?php echo $order['id']; ?>)">
                    <i class="fas fa-receipt"></i> Receipt
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
            <!-- Pagination for Completed Orders -->
            <?php if ($total_completed_pages > 1): ?>
              <nav aria-label="Completed orders pagination">
                <ul class="pagination">
                  <?php for ($i = 1; $i <= $total_completed_pages; $i++): ?>
                    <li class="<?php if ($i === $completed_page) echo 'active'; ?>">
                      <a href="?completed_page=<?php echo $i; ?><?php echo isset($_GET['pending_page']) ? '&pending_page=' . $_GET['pending_page'] : ''; ?>">
                        <?php echo $i; ?>
                      </a>
                    </li>
                  <?php endfor; ?>
                </ul>
              </nav>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Right Panel: Dashboard Summary (Sticky) -->
      <div class="orders-right">
        <div class="dashboard-section">
          <h3>Total Orders</h3>
          <div class="dashboard-card">
            <div>Pending: <?php echo $total_pending; ?></div>
            <div>Completed: <?php echo $total_completed; ?></div>
          </div>
        </div>
        <div class="dashboard-section">
          <h3>Latest Pending Orders</h3>
          <ul class="list-group">
            <?php $lCount = 0; ?>
            <?php foreach ($latest_pending_orders as $order): 
              $highlight = ($lCount === 0) ? 'highlight-latest' : '';
              $orderISO = date("c", strtotime($order['order_date']));
            ?>
              <li class="list-group-item <?php echo $highlight; ?>">
                <i class="fas fa-box"></i> Order #<?php echo $order['id']; ?>
                <span class="float-end"><?php echo ucfirst($order['status']); ?></span>
                <br>
                <small>
                  Elapsed Time: 
                  <span class="elapsed-time" data-order-time="<?php echo $orderISO; ?>">Loading...</span>
                </small>
              </li>
            <?php $lCount++; endforeach; ?>
          </ul>
        </div>
        <div class="dashboard-section">
          <h3>Latest Completed Orders</h3>
          <ul class="list-group">
            <?php 
              $counter = 0;
              foreach ($latest_completed_orders as $order): 
                if ($counter++ < 5): ?>
                  <li class="list-group-item">
                    <i class="fas fa-box"></i> Order #<?php echo $order['id']; ?>
                    <span class="float-end"><?php echo ucfirst($order['status']); ?></span>
                  </li>
            <?php endif; endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Container for Notifications -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- Modals -->
  <!-- Order Details Modal -->
  <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="background: var(--primary-color, #1B263B); color: #fff;">
          <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="order-details-content">
          <!-- Order details loaded dynamically -->
        </div>
      </div>
    </div>
  </div>

  <!-- Receipt Modal -->
  <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="background: var(--primary-color, #1B263B); color: #fff;">
          <h5 class="modal-title" id="receiptModalLabel">Order Receipt</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="receipt-content">
          <!-- Receipt content loaded dynamically -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-custom" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn-custom" onclick="printReceipt()">
            <i class="fas fa-print"></i> Print Receipt
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Payment Proof Modal -->
  <div class="modal fade" id="paymentProofModal" tabindex="-1" aria-labelledby="paymentProofModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="background: var(--primary-color, #1B263B); color: #fff;">
          <h5 class="modal-title">Payment Proof</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="payment-proof-content">
          <!-- Payment proof will be displayed here -->
        </div>
      </div>
    </div>
  </div>

  <!-- Master Key Modal for Voiding Orders -->
  <div class="modal fade" id="masterKeyModal" tabindex="-1" aria-labelledby="masterKeyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="background: var(--primary-color, #1B263B); color: #fff;">
          <h5 class="modal-title" id="masterKeyModalLabel"><i class="fas fa-lock"></i> Enter Master Key</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="masterKeyForm">
            <div class="mb-3">
              <label for="master_key" class="form-label"><i class="fas fa-key"></i> Master Key</label>
              <input type="password" class="form-control" id="master_key" placeholder="Enter your master key" required>
              <input type="hidden" id="void_order_id">
            </div>
            <div id="mk_error" class="text-danger mb-2"></div>
            <div class="d-grid">
              <button type="submit" class="btn-custom">
                <i class="fas fa-check"></i> Confirm Void
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

</div>

  <?php include('../includes/footer.php'); ?>

  <!-- Bootstrap & jQuery -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
  <script defer>
    // Function to show toast notifications using Bootstrap Toasts
    function showToast(message, type = 'info') {
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
        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', function () {
            $(toastEl).remove();
        });
    }

    // Flash message fade-out
    setTimeout(() => { $('#flash-message').fadeOut('slow'); }, 3000);

    // Payment Filter Functionality
    $('#paymentFilter .nav-link').click(function() {
        $('#paymentFilter .nav-link').removeClass('active');
        $(this).addClass('active');
        const filter = $(this).data('filter');
        if(filter === 'all'){
            $('.order-card').show();
        } else {
            $('.order-card').each(function(){
                if($(this).data('payment') === filter) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
        showToast("Filtered orders by: " + filter, "info");
    });

    // Show Master Key Modal for voiding an order
    function showVoidOrderModal(orderId) {
        $('#void_order_id').val(orderId);
        $('#mk_error').text('');
        new bootstrap.Modal(document.getElementById('masterKeyModal')).show();
        showToast("Please enter your master key to void order #" + orderId, "warning");
    }

    // Master Key form submission
    $('#masterKeyForm').on('submit', function(e) {
        e.preventDefault();
        const orderId = $('#void_order_id').val().trim();
        const masterKey = $('#master_key').val().trim();
        if (masterKey === '') {
            $('#mk_error').text('Please enter your master key.');
            showToast("Master key cannot be empty", "danger");
            return;
        }
        $.ajax({
            url: 'process/validate_master_key',
            type: 'POST',
            data: { master_key: masterKey },
            success: function(response) {
                const res = JSON.parse(response);
                if (res.success) {
                    showToast("Master key validated. Voiding order #" + orderId, "success");
                    voidOrder(orderId);
                } else {
                    $('#mk_error').text(res.message);
                    showToast("Master key validation failed: " + res.message, "danger");
                }
            },
            error: function() {
                $('#mk_error').text('An error occurred while validating the master key.');
                showToast("AJAX error during master key validation.", "danger");
            }
        });
    });

    function voidOrder(orderId) {
        $.ajax({
            url: 'process/void_order',
            type: 'POST',
            data: { order_id: orderId },
            success: function(response) {
                const res = (typeof response === 'object') ? response : JSON.parse(response);
                if (res.success) {
                    showToast("Order #" + orderId + " voided successfully.", "success");
                    setTimeout(() => { location.reload(); }, 1000);
                } else {
                    showToast("Failed to void order: " + res.message, "danger");
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                showToast("AJAX error while voiding the order.", "danger");
            }
        });
    }

    // View Order Details Modal
    function viewOrderDetails(orderId) {
        $.ajax({
            url: 'process/order_details.php',
            type: 'GET',
            data: { order_id: orderId },
            success: function(response) {
                const order = JSON.parse(response);
                if (order.error) {
                    $('#order-details-content').html('<p class="text-muted">Order not found.</p>');
                    showToast("Order details not found.", "danger");
                } else {
                    let details = `<p><strong>Order ID:</strong> ${order.id}</p>
                                   <p><strong>Date:</strong> ${order.order_date}</p>
                                   <p><strong>Total:</strong> ₱${order.total_amount}</p>
                                   <p><strong>Status:</strong> ${order.status}</p>
                                   <p><strong>Payment Method:</strong> ${order.payment_method}</p>
                                   <p><strong>Cashier:</strong> ${order.staff_username}</p>
                                   <h5>Ordered Items:</h5>
                                   <ul class="ps-3">`;
                    if (Array.isArray(order.products) && order.products.length > 0) {
                        order.products.forEach(item => {
                            details += `<li>${item.name} x${item.quantity} (₱${item.price})</li>`;
                        });
                    } else {
                        details += '<li>No products found for this order.</li>';
                    }
                    details += '</ul>';
                    $('#order-details-content').html(details);
                    showToast("Order details loaded.", "success");
                }
                new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
            },
            error: function() { 
                alert("Error fetching order details.");
                showToast("Error fetching order details.", "danger");
            }
        });
    }

    // Show Receipt Modal
    function showReceipt(orderId) {
        $.ajax({
            url: 'process/receipts.php',
            type: 'GET',
            data: { order_id: orderId },
            success: function(response) {
                $('#receipt-content').html(response);
                new bootstrap.Modal(document.getElementById('receiptModal')).show();
                showToast("Receipt loaded for order #" + orderId, "success");
            },
            error: function() { 
                alert("Error fetching receipt content.");
                showToast("Error fetching receipt content.", "danger");
            }
        });
    }

    // Print Receipt
    function printReceipt() {
        const receiptContent = $('#receipt-content').html();
        const printWindow = window.open('', '', 'width=600,height=600');
        printWindow.document.write(`
            <html>
              <head>
                <title>Print Receipt</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
              </head>
              <body>
                ${receiptContent}
                <script>
                  window.onload = function() {
                    window.print();
                    window.close();
                  };
                <\/script>
              </body>
            </html>
        `);
        printWindow.document.close();
        showToast("Printing receipt...", "info");
    }

    // View Payment Proof Modal
    function viewPaymentProof(proofPath) {
        const fullPath = proofPath;
        let content = '';
        if (/\.(jpg|jpeg|png|gif)$/i.test(proofPath)) {
            content = `<img src="${fullPath}" alt="Payment Proof" style="max-width:100%;">`;
        } else {
            content = `<a href="${fullPath}" target="_blank">View Payment Proof File</a>`;
        }
        $('#payment-proof-content').html(content);
        new bootstrap.Modal(document.getElementById('paymentProofModal')).show();
        showToast("Payment proof loaded.", "success");
    }
    
    // Realtime elapsed time updater (updates every second)
    function updateElapsedTimes() {
      document.querySelectorAll('.elapsed-time').forEach(function(el) {
          // Parse the ISO 8601 date string using the Date constructor.
          const orderTime = new Date(el.getAttribute('data-order-time')).getTime();
          if (!orderTime) return;
          const now = Date.now();
          let diffInSeconds = Math.floor((now - orderTime) / 1000);
          if (diffInSeconds < 0) diffInSeconds = 0; // In case of any discrepancies
          const minutes = Math.floor(diffInSeconds / 60);
          const seconds = diffInSeconds % 60;
          el.textContent = minutes + "m " + (seconds < 10 ? "0" : "") + seconds + "s";
      });
    }
    // Update elapsed times every second and on load.
    setInterval(updateElapsedTimes, 1000);
    updateElapsedTimes();
  </script>