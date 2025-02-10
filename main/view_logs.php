<?php

include('../includes/header.php');
checkUserAccess(['Admin']);

$stmt = $pdo->query("SELECT favicon FROM website_settings WHERE id = 1");
$favicon = $stmt->fetchColumn();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: access_denied.php');
    exit();
}

// Set default timezone to Philippine time
date_default_timezone_set('Asia/Manila');

/* ================================
   Pagination for Login Logs
   ================================ */
$login_limit = 10;
$login_page  = isset($_GET['login_page']) && is_numeric($_GET['login_page']) ? (int)$_GET['login_page'] : 1;
$login_offset = ($login_page - 1) * $login_limit;

$stmtCountLogin = $pdo->prepare("SELECT COUNT(*) FROM logs");
$stmtCountLogin->execute();
$total_login_logs = $stmtCountLogin->fetchColumn();
$total_login_pages = ceil($total_login_logs / $login_limit);

$stmtLogin = $pdo->prepare("SELECT * FROM logs ORDER BY login_time DESC LIMIT :offset, :limit");
$stmtLogin->bindValue(':offset', $login_offset, PDO::PARAM_INT);
$stmtLogin->bindValue(':limit', $login_limit, PDO::PARAM_INT);
$stmtLogin->execute();
$login_logs = $stmtLogin->fetchAll();

/* ================================
   Pagination for Order Logs
   ================================ */
$order_limit = 10;
$order_page  = isset($_GET['order_page']) && is_numeric($_GET['order_page']) ? (int)$_GET['order_page'] : 1;
$order_offset = ($order_page - 1) * $order_limit;

$stmtCountOrder = $pdo->prepare("SELECT COUNT(*) FROM order_logs");
$stmtCountOrder->execute();
$total_order_logs = $stmtCountOrder->fetchColumn();
$total_order_pages = ceil($total_order_logs / $order_limit);

$stmtOrder = $pdo->prepare("
    SELECT ol.*, 
           o.customer_name, 
           o.grade_program, 
           o.order_date, 
           o.total_amount, 
           o.cash_paid, 
           o.change_amount, 
           o.payment_method, 
           o.status, 
           o.products, 
           o.staff_username, 
           o.transaction_id, 
           o.payment_proof AS order_payment_proof, 
           o.signature
    FROM order_logs ol
    LEFT JOIN orders o ON ol.order_id = o.id
    ORDER BY ol.order_time DESC
    LIMIT :offset, :limit
");
$stmtOrder->bindValue(':offset', $order_offset, PDO::PARAM_INT);
$stmtOrder->bindValue(':limit', $order_limit, PDO::PARAM_INT);
$stmtOrder->execute();
$order_logs = $stmtOrder->fetchAll();

/* ================================
   Pagination for User Creation Logs
   ================================ */
$user_limit = 10;
$user_page  = isset($_GET['user_page']) && is_numeric($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
$user_offset = ($user_page - 1) * $user_limit;

$stmtCountUser = $pdo->prepare("SELECT COUNT(*) FROM users");
$stmtCountUser->execute();
$total_user_logs = $stmtCountUser->fetchColumn();
$total_user_pages = ceil($total_user_logs / $user_limit);

$stmtUser = $pdo->prepare("SELECT id, username, created_by, created_at, role FROM users ORDER BY created_at DESC LIMIT :offset, :limit");
$stmtUser->bindValue(':offset', $user_offset, PDO::PARAM_INT);
$stmtUser->bindValue(':limit', $user_limit, PDO::PARAM_INT);
$stmtUser->execute();
$user_logs = $stmtUser->fetchAll();

/* ================================
   Pagination for Expense Logs
   ================================ */
$expense_limit = 10;
$expense_page  = isset($_GET['expense_page']) && is_numeric($_GET['expense_page']) ? (int)$_GET['expense_page'] : 1;
$expense_offset = ($expense_page - 1) * $expense_limit;

$stmtCountExpense = $pdo->prepare("SELECT COUNT(*) FROM expenses");
$stmtCountExpense->execute();
$total_expense_logs = $stmtCountExpense->fetchColumn();
$total_expense_pages = ceil($total_expense_logs / $expense_limit);

$stmtExpense = $pdo->prepare("SELECT * FROM expenses ORDER BY date DESC LIMIT :offset, :limit");
$stmtExpense->bindValue(':offset', $expense_offset, PDO::PARAM_INT);
$stmtExpense->bindValue(':limit', $expense_limit, PDO::PARAM_INT);
$stmtExpense->execute();
$expense_logs = $stmtExpense->fetchAll();

/* ================================
   Pagination for Product Logs
   ================================ */
$product_log_limit = 10;
$product_log_page  = isset($_GET['product_log_page']) && is_numeric($_GET['product_log_page']) ? (int)$_GET['product_log_page'] : 1;
$product_log_offset = ($product_log_page - 1) * $product_log_limit;

$stmtCountProductLog = $pdo->prepare("SELECT COUNT(*) FROM products");
$stmtCountProductLog->execute();
$total_product_logs = $stmtCountProductLog->fetchColumn();
$total_product_log_pages = ceil($total_product_logs / $product_log_limit);

$stmtProductLog = $pdo->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT :offset, :limit");
$stmtProductLog->bindValue(':offset', $product_log_offset, PDO::PARAM_INT);
$stmtProductLog->bindValue(':limit', $product_log_limit, PDO::PARAM_INT);
$stmtProductLog->execute();
$product_logs = $stmtProductLog->fetchAll();

/* ================================
   Pagination for Product Categories Logs
   ================================ */
$prodcat_limit = 10;
$prodcat_page  = isset($_GET['prodcat_page']) && is_numeric($_GET['prodcat_page']) ? (int)$_GET['prodcat_page'] : 1;
$prodcat_offset = ($prodcat_page - 1) * $prodcat_limit;

$stmtCountProdcat = $pdo->prepare("SELECT COUNT(*) FROM product_categories");
$stmtCountProdcat->execute();
$total_prodcat_logs = $stmtCountProdcat->fetchColumn();
$total_prodcat_pages = ceil($total_prodcat_logs / $prodcat_limit);

$stmtProdcat = $pdo->prepare("SELECT * FROM product_categories ORDER BY created_at DESC LIMIT :offset, :limit");
$stmtProdcat->bindValue(':offset', $prodcat_offset, PDO::PARAM_INT);
$stmtProdcat->bindValue(':limit', $prodcat_limit, PDO::PARAM_INT);
$stmtProdcat->execute();
$prodcat_logs = $stmtProdcat->fetchAll();
?>

<!-- jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Then Bootstrap Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    html {
      scroll-behavior: smooth;
    }
    /* Global Styles */
    body {
      font-family: 'Roboto', sans-serif;
      margin: 0;
      padding: 0;
      background: #f4f7fc;
      color: #333;
    }
    /* Container Styles */
    .container {
      width: 100%;
      padding: 20px;
      margin: 30px auto;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #2b576d;
    }
    /* Card Styles */
    .card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      padding: 20px;
      margin-bottom: 40px;
    }
    /* Table Responsive for Mobile */
    .table-responsive {
      width: 100%;
    }
    @media (max-width: 1199px) {
      .table-responsive {
        overflow-x: auto;
      }
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    table thead {
      background-color: #2b576d;
      color: #fff;
    }
    table th, table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    table tbody tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    table tbody tr:hover {
      background-color: #f1f1f1;
    }
    table th i {
      margin-right: 8px;
      color: #ffdd57;
    }
    /* Pagination Styles */
    .pagination {
      margin-top: 15px;
      text-align: center;
    }
    .pagination a, .pagination span {
      display: inline-block;
      padding: 8px 12px;
      margin: 0 3px;
      color: #2b576d;
      text-decoration: none;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    .pagination a:hover {
      background-color: #ddd;
    }
    .pagination .current {
      background-color: #2b576d;
      color: #fff;
      border-color: #2b576d;
    }
    @media (max-width: 768px) {
      .container {
        padding: 15px;
      }
      table th, table td {
        padding: 10px;
        font-size: 14px;
      }
    }
    /* Logs Navigation Buttons */
    .logs-nav {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 20px;
    }
    .logs-nav a {
      padding: 8px 12px;
      border: 1px solid #2b576d;
      border-radius: 4px;
      color: #2b576d;
      text-decoration: none;
      background: #fff;
    }
    .logs-nav a:hover {
      background: #ddd;
    }
  </style>

  <div class="container">
    <!-- Logs Navigation Buttons (anchor links for auto-scroll) -->
    <div class="logs-nav">
      <a href="#login-logs">Login Logs</a>
      <a href="#order-logs">Order Logs</a>
      <a href="#user-logs">User Logs</a>
      <a href="#expense-logs">Expense Logs</a>
      <a href="#product-logs">Product Logs</a>
      <a href="#prodcat-logs">Product Categories Logs</a>
    </div>
    
    <!-- Login Logs Section -->
    <h2 id="login-logs"><i class="fas fa-file-alt"></i> Login Logs</h2>
    <div class="card">
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th><i class="fas fa-user"></i> Username</th>
              <th><i class="fas fa-sign-in-alt"></i> Login Time</th>
              <th><i class="fas fa-sign-out-alt"></i> Logout Time</th>
              <th><i class="fas fa-network-wired"></i> IP Address</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($login_logs): ?>
              <?php foreach ($login_logs as $log): ?>
                <tr>
                  <td><?php echo htmlspecialchars($log['admin_username']); ?></td>
                  <td><?php echo date('Y-m-d h:i:s A', strtotime($log['login_time'] . ' +8 hours')); ?></td>
                  <td>
                    <?php 
                      if ($log['logout_time']) {
                        echo date('Y-m-d h:i:s A', strtotime($log['logout_time'] . ' +8 hours'));
                      } else {
                        echo '<span style="color:#e60000;">Not logged out yet</span>';
                      }
                    ?>
                  </td>
                  <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4">No login logs available.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="pagination">
        <?php if ($login_page > 1): ?>
          <a href="?login_page=<?php echo $login_page - 1; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>">« Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_login_pages; $i++): ?>
          <?php if ($i == $login_page): ?>
            <span class="current"><?php echo $i; ?></span>
          <?php else: ?>
            <a href="?login_page=<?php echo $i; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>"><?php echo $i; ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($login_page < $total_login_pages): ?>
          <a href="?login_page=<?php echo $login_page + 1; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>">Next »</a>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Order Logs Section -->
    <h2 id="order-logs"><i class="fas fa-shopping-cart"></i> Order Logs</h2>
    <div class="card">
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th><i class="fas fa-receipt"></i> Order ID</th>
              <th><i class="fas fa-user"></i> Customer Name</th>
              <th><i class="fas fa-info-circle"></i> Grade/Program</th>
              <th><i class="fas fa-clock"></i> Order Date</th>
              <th><i class="fas fa-dollar-sign"></i> Total Amount</th>
              <th><i class="fas fa-money-bill-wave"></i> Cash Paid</th>
              <th><i class="fas fa-money-check-alt"></i> Change</th>
              <th><i class="fas fa-network-wired"></i> IP Address</th>
              <th><i class="fas fa-cogs"></i> Action</th>
              <th><i class="fas fa-user-tie"></i> Staff Username</th>
              <th><i class="fas fa-image"></i> Payment Info</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($order_logs): ?>
              <?php foreach ($order_logs as $order): ?>
                <tr>
                  <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                  <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                  <td><?php echo htmlspecialchars($order['grade_program']); ?></td>
                  <td><?php echo date('Y-m-d h:i:s A', strtotime($order['order_date'] . ' +8 hours')); ?></td>
                  <td><?php echo number_format($order['total_amount'], 2); ?></td>
                  <td><?php echo !empty($order['cash_paid']) ? number_format($order['cash_paid'], 2) : 'N/A'; ?></td>
                  <td><?php echo !empty($order['change_amount']) ? number_format($order['change_amount'], 2) : 'N/A'; ?></td>
                  <td><?php echo htmlspecialchars($order['ip_address']); ?></td>
                  <td><?php echo htmlspecialchars($order['action']); ?></td>
                  <td><?php echo htmlspecialchars($order['staff_username']); ?></td>
                  <td>
                    <?php 
                      if (strtolower($order['action']) === 'voided') {
                          echo "Payment Return to customer";
                      } else {
                          if (!empty($order['order_payment_proof'])) {
                              echo '<a href="'.htmlspecialchars($order['order_payment_proof']).'" target="_blank"><i class="fas fa-image"></i> View</a>';
                          } else {
                              if ($order['payment_method'] === 'cash') {
                                  echo "Payment is Cash";
                              } else {
                                  echo "N/A";
                              }
                          }
                      }
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="11">No order logs available.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="pagination">
        <?php if ($order_page > 1): ?>
          <a href="?order_page=<?php echo $order_page - 1; ?>&login_page=<?php echo $login_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>">« Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_order_pages; $i++): ?>
          <?php if ($i == $order_page): ?>
            <span class="current"><?php echo $i; ?></span>
          <?php else: ?>
            <a href="?order_page=<?php echo $i; ?>&login_page=<?php echo $login_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>"><?php echo $i; ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($order_page < $total_order_pages): ?>
          <a href="?order_page=<?php echo $order_page + 1; ?>&login_page=<?php echo $login_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>">Next »</a>
        <?php endif; ?>
      </div>
    </div>
    
<!-- User Creation Logs Section -->
<h2 id="user-logs"><i class="fas fa-user-plus"></i> User Creation Logs</h2>
<div class="card">
  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th><i class="fas fa-id-badge"></i> ID</th>
          <th><i class="fas fa-user"></i> Username</th>
          <th><i class="fas fa-user-shield"></i> Role</th>
          <th><i class="fas fa-users-cog"></i> Created By</th>
          <th><i class="fas fa-clock"></i> Created At</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($user_logs): ?>
          <?php foreach ($user_logs as $user): ?>
            <tr>
              <td><?php echo htmlspecialchars($user['id']); ?></td>
              <td><?php echo htmlspecialchars($user['username']); ?></td>
              <td><?php echo htmlspecialchars($user['role']); ?></td>
              <td><?php echo htmlspecialchars($user['created_by']); ?></td>
              <td>
                <?php 
                  // Assume the stored time is in UTC. Create a DateTime object and then convert it to Philippine time.
                  $dt = new DateTime($user['created_at'], new DateTimeZone('UTC'));
                  $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                  echo $dt->format('Y-m-d h:i:s A');
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5">No user creation logs available.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="pagination">
    <?php if ($user_page > 1): ?>
      <a href="?user_page=<?php echo $user_page - 1; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>">« Prev</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $total_user_pages; $i++): ?>
      <?php if ($i == $user_page): ?>
        <span class="current"><?php echo $i; ?></span>
      <?php else: ?>
        <a href="?user_page=<?php echo $i; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>"><?php echo $i; ?></a>
      <?php endif; ?>
    <?php endfor; ?>
    <?php if ($user_page < $total_user_pages): ?>
      <a href="?user_page=<?php echo $user_page + 1; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>">Next »</a>
    <?php endif; ?>
  </div>
</div>

    
    <!-- Expense Logs Section -->
    <h2 id="expense-logs"><i class="fas fa-file-invoice"></i> Expense Logs</h2>
    <div class="card">
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th><i class="fas fa-id-badge"></i> ID</th>
              <th><i class="fas fa-file-alt"></i> Expense Name</th>
              <th><i class="fas fa-money-bill-wave"></i> Amount</th>
              <th><i class="fas fa-align-left"></i> Description</th>
              <th><i class="fas fa-clock"></i> Date</th>
              <th><i class="fas fa-user"></i> Added By</th>
              <th><i class="fas fa-edit"></i> Edited By</th>
              <th><i class="fas fa-clock"></i> Edited At</th>
              <th><i class="fas fa-trash-alt"></i> Deleted By</th>
              <th><i class="fas fa-clock"></i> Deleted At</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($expense_logs): ?>
              <?php foreach ($expense_logs as $expense): ?>
                <tr>
                  <td><?php echo htmlspecialchars($expense['id']); ?></td>
                  <td><?php echo htmlspecialchars($expense['name']); ?></td>
                  <td>₱<?php echo number_format($expense['amount'], 2); ?></td>
                  <td><?php echo htmlspecialchars($expense['description']); ?></td>
                  <td>
                    <?php 
                      $dt = new DateTime($expense['date'], new DateTimeZone('UTC'));
                      $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                      echo $dt->format('Y-m-d h:i:s A');
                    ?>
                  </td>
                  <td><?php echo htmlspecialchars($expense['added_by']); ?></td>
                  <td><?php echo !empty($expense['edited_by']) ? htmlspecialchars($expense['edited_by']) : 'N/A'; ?></td>
                  <td>
                    <?php 
                      if (!empty($expense['edited_at'])) {
                        $dt = new DateTime($expense['edited_at'], new DateTimeZone('UTC'));
                        $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                        echo $dt->format('Y-m-d h:i:s A');
                      } else {
                        echo 'N/A';
                      }
                    ?>
                  </td>
                  <td><?php echo !empty($expense['deleted_by']) ? htmlspecialchars($expense['deleted_by']) : 'N/A'; ?></td>
                  <td>
                    <?php 
                      if (!empty($expense['deleted_at'])) {
                        $dt = new DateTime($expense['deleted_at'], new DateTimeZone('UTC'));
                        $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                        echo $dt->format('Y-m-d h:i:s A');
                      } else {
                        echo 'N/A';
                      }
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="10">No expense logs available.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="pagination">
        <?php if ($expense_page > 1): ?>
          <a href="?expense_page=<?php echo $expense_page - 1; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>">« Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_expense_pages; $i++): ?>
          <?php if ($i == $expense_page): ?>
            <span class="current"><?php echo $i; ?></span>
          <?php else: ?>
            <a href="?expense_page=<?php echo $i; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>"><?php echo $i; ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($expense_page < $total_expense_pages): ?>
          <a href="?expense_page=<?php echo $expense_page + 1; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&product_log_page=<?php echo $product_log_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>">Next »</a>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Product Logs Section -->
<h2 id="product-logs"><i class="fas fa-file-invoice"></i> Product Logs</h2>
<div class="card">
  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th><i class="fas fa-id-badge"></i> ID</th>
          <th><i class="fas fa-box"></i> Name</th>
          <th><i class="fas fa-money-bill-wave"></i> Price</th>
          <th><i class="fas fa-user"></i> Added By</th>
          <th><i class="fas fa-clock"></i> Created At</th>
          <th><i class="fas fa-edit"></i> Updated By</th>
          <th><i class="fas fa-clock"></i> Updated At</th>
          <th><i class="fas fa-trash-alt"></i> Deleted By</th>
          <th><i class="fas fa-clock"></i> Deleted At</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($product_logs): ?>
          <?php foreach ($product_logs as $p_log): ?>
            <tr>
              <td><?php echo htmlspecialchars($p_log['id']); ?></td>
              <td><?php echo htmlspecialchars($p_log['name']); ?></td>
              <td>₱<?php echo number_format($p_log['price'], 2); ?></td>
              <td><?php echo htmlspecialchars($p_log['added_by']); ?></td>
              <td>
                <?php 
                  // Convert the created_at time from UTC to Asia/Manila
                  $dt = new DateTime($p_log['created_at'], new DateTimeZone('UTC'));
                  $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                  echo $dt->format('Y-m-d h:i:s A');
                ?>
              </td>
              <td><?php echo !empty($p_log['updated_by']) ? htmlspecialchars($p_log['updated_by']) : 'N/A'; ?></td>
              <td>
                <?php 
                  if (!empty($p_log['updated_at'])) {
                    $dt = new DateTime($p_log['updated_at'], new DateTimeZone('UTC'));
                    $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                    echo $dt->format('Y-m-d h:i:s A');
                  } else {
                    echo 'N/A';
                  }
                ?>
              </td>
              <td><?php echo !empty($p_log['deleted_by']) ? htmlspecialchars($p_log['deleted_by']) : 'N/A'; ?></td>
              <td>
                <?php 
                  if (!empty($p_log['deleted_at'])) {
                    $dt = new DateTime($p_log['deleted_at'], new DateTimeZone('UTC'));
                    $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                    echo $dt->format('Y-m-d h:i:s A');
                  } else {
                    echo 'N/A';
                  }
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" class="text-center">No product logs available.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="pagination mt-3">
    <?php if ($product_log_page > 1): ?>
      <a href="?product_log_page=<?php echo $product_log_page - 1; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>">« Prev</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $total_product_log_pages; $i++): ?>
      <?php if ($i == $product_log_page): ?>
        <span class="current"><?php echo $i; ?></span>
      <?php else: ?>
        <a href="?product_log_page=<?php echo $i; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>"><?php echo $i; ?></a>
      <?php endif; ?>
    <?php endfor; ?>
    <?php if ($product_log_page < $total_product_log_pages): ?>
      <a href="?product_log_page=<?php echo $product_log_page + 1; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&prodcat_page=<?php echo $prodcat_page; ?>">Next »</a>
    <?php endif; ?>
  </div>
</div>

    <!-- Product Categories Logs Section -->
    <h2 id="prodcat-logs"><i class="fas fa-tags"></i> Product Categories Logs</h2>
    <div class="card">
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th><i class="fas fa-id-badge"></i> ID</th>
              <th><i class="fas fa-tag"></i> Name</th>
              <th><i class="fas fa-user"></i> Added By</th>
              <th><i class="fas fa-clock"></i> Created At</th>
              <th><i class="fas fa-edit"></i> Updated By</th>
              <th><i class="fas fa-clock"></i> Updated At</th>
              <th><i class="fas fa-trash-alt"></i> Deleted By</th>
              <th><i class="fas fa-clock"></i> Deleted At</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($prodcat_logs): ?>
              <?php foreach ($prodcat_logs as $cat): ?>
                <tr>
                  <td><?php echo htmlspecialchars($cat['id']); ?></td>
                  <td><?php echo htmlspecialchars($cat['name']); ?></td>
                  <td><?php echo htmlspecialchars($cat['added_by']); ?></td>
                  <td>
                    <?php 
                      $dt = new DateTime($cat['created_at'], new DateTimeZone('UTC'));
                      $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                      echo $dt->format('Y-m-d h:i:s A');
                    ?>
                  </td>
                  <td><?php echo !empty($cat['updated_by']) ? htmlspecialchars($cat['updated_by']) : 'N/A'; ?></td>
                  <td>
                    <?php 
                      if (!empty($cat['updated_at'])) {
                        $dt = new DateTime($cat['updated_at'], new DateTimeZone('UTC'));
                        $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                        echo $dt->format('Y-m-d h:i:s A');
                      } else {
                        echo 'N/A';
                      }
                    ?>
                  </td>
                  <td><?php echo !empty($cat['deleted_by']) ? htmlspecialchars($cat['deleted_by']) : 'N/A'; ?></td>
                  <td>
                    <?php 
                      if (!empty($cat['deleted_at'])) {
                        $dt = new DateTime($cat['deleted_at'], new DateTimeZone('UTC'));
                        $dt->setTimezone(new DateTimeZone('Asia/Manila'));
                        echo $dt->format('Y-m-d h:i:s A');
                      } else {
                        echo 'N/A';
                      }
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center">No product categories logs available.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="pagination mt-3">
        <?php if ($prodcat_page > 1): ?>
          <a href="?prodcat_page=<?php echo $prodcat_page - 1; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>">« Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_prodcat_pages; $i++): ?>
          <?php if ($i == $prodcat_page): ?>
            <span class="current"><?php echo $i; ?></span>
          <?php else: ?>
            <a href="?prodcat_page=<?php echo $i; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>"><?php echo $i; ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($prodcat_page < $total_prodcat_pages): ?>
          <a href="?prodcat_page=<?php echo $prodcat_page + 1; ?>&login_page=<?php echo $login_page; ?>&order_page=<?php echo $order_page; ?>&user_page=<?php echo $user_page; ?>&expense_page=<?php echo $expense_page; ?>&product_log_page=<?php echo $product_log_page; ?>">Next »</a>
        <?php endif; ?>
      </div>
    </div>
    
  </div>
  
  <?php include('../includes/footer.php'); ?>