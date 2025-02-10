<?php
session_start();
include('../../database/database.php');

// Fetch website settings
$settingsStmt = $pdo->prepare("SELECT site_title, favicon, school_logo, organization_logo FROM website_settings LIMIT 1");
$settingsStmt->execute();
$settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

// Fetch order details
if (!isset($_GET['order_id'])) {
    die('Order ID is required.');
}

$order_id = $_GET['order_id'];

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found.');
}

// Decode order products (order items stored as JSON)
$order_details = json_decode($order['products'], true); // Assuming 'products' field holds JSON data
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($settings['site_title']); ?> - Receipt</title>
  <link rel="icon" href="<?php echo $settings['favicon'] ?? '../assets/images/default-favicon.ico'; ?>" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      /* Use deep purple theme colors */
      --primary-color: #4A148C;     /* Deep Purple */
      --secondary-color: #7C43BD;   /* Softer Purple */
      --accent-color: #D1C4E9;      /* Light Lavender */
      --success-color: #28a745;     /* Green for totals */
      --background-color: #ffffff;  /* White background */
    }

    body {
      font-family: 'Roboto', sans-serif;
      background-color: var(--background-color);
      line-height: 1.6;
      margin: 0;
      padding: 0;
      color: #333;
    }

    .receipt-container {
      max-width: 800px;
      margin: 2rem auto;
      background: var(--background-color);
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }

    .receipt-header {
      padding: 2rem;
      /* Use a gradient based on the primary color */
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      text-align: center;
    }

    .logo-container {
      display: flex;
      justify-content: center;
      gap: 1.5rem;
      flex-wrap: wrap;
      margin-bottom: 1.5rem;
    }

    .logo-container img {
      max-width: 80px;
      height: 80px;
      object-fit: cover;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
      border-radius: 50%;
    }

    .receipt-body {
      padding: 2rem;
    }

    .order-meta {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
      background: var(--accent-color);
      padding: 1.5rem;
      border-radius: 8px;
    }

    .order-meta-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .order-meta-item i {
      color: var(--primary-color);
      font-size: 1.2rem;
    }

    .table-responsive {
      overflow-x: auto;
      margin: 2rem 0;
    }

    .order-table {
      width: 100%;
      border-collapse: collapse;
      background: var(--background-color);
    }

    .order-table th,
    .order-table td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid #dee2e6;
    }

    .order-table th {
      background: var(--primary-color);
      color: white;
      font-weight: 600;
    }

    .total-section {
      background: var(--accent-color);
      padding: 1.5rem;
      border-radius: 8px;
      margin-top: 2rem;
    }

    .total-line {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.75rem;
    }

    .grand-total {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--success-color);
    }

    .receipt-footer {
      text-align: center;
      padding: 1.5rem;
      color: var(--secondary-color);
      border-top: 1px solid #dee2e6;
      margin-top: 2rem;
    }

    @media (max-width: 768px) {
      .receipt-container {
        margin: 1rem;
        border-radius: 8px;
      }

      .receipt-header {
        padding: 1.5rem;
      }

      .logo-container img {
        max-width: 60px;
      }

      .order-meta {
        grid-template-columns: 1fr;
        padding: 1rem;
      }

      .order-table th,
      .order-table td {
        padding: 0.75rem;
      }

      .grand-total {
        font-size: 1.25rem;
      }
    }

    @media print {
      .receipt-container {
        box-shadow: none;
        border-radius: 0;
      }
      
      .receipt-header {
        background: #fff !important;
        color: #000 !important;
        border-bottom: 2px solid #000;
      }
      
      .logo-container img {
        filter: none;
      }
    }
  </style>
</head>
<body>
  <div class="receipt-container">
    <div class="receipt-header">
      <div class="logo-container">
        <img src="<?php echo $settings['school_logo']; ?>" alt="School Logo">
        <img src="<?php echo $settings['organization_logo']; ?>" alt="Organization Logo">
      </div>
      <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($settings['site_title']); ?></h1>
      <h2 class="h4">Order Receipt</h2>
    </div>

    <div class="receipt-body">
      <div class="order-meta">
        <div class="order-meta-item">
          <i class="fas fa-user"></i>
          <div>
            <div class="text-muted small">Cashier</div>
            <div><?php echo htmlspecialchars($order['staff_username']); ?></div>
          </div>
        </div>
        <div class="order-meta-item">
          <i class="fas fa-hashtag"></i>
          <div>
            <div class="text-muted small">Order ID</div>
            <div>#<?php echo $order['id']; ?></div>
          </div>
        </div>
        <div class="order-meta-item">
          <i class="fas fa-calendar-day"></i>
          <div>
            <div class="text-muted small">Date</div>
            <div><?php echo $order['order_date']; ?></div>
          </div>
        </div>
        <div class="order-meta-item">
          <i class="fas fa-credit-card"></i>
          <div>
            <div class="text-muted small">Payment Method</div>
            <div><?php echo htmlspecialchars($order['payment_method']); ?></div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="order-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Price</th>
              <th>Qty</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($order_details as $product) { ?>
              <tr>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                <td><?php echo $product['quantity']; ?></td>
                <td>₱<?php echo number_format($product['total'], 2); ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <div class="total-section">
        <div class="total-line">
          <span>Subtotal:</span>
          <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
        <div class="total-line">
          <span>Tax:</span>
          <span>+₱50</span>
        </div>
        <div class="total-line grand-total">
          <span>Total:</span>
          <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
      </div>

      <div class="receipt-footer">
        <p class="mb-1">Thank you for your purchase on our booth!</p>
        <p class="mb-0 fst-italic">Wishing you a Happy Valentine's Day! ❤️</p>
      </div>
    </div>
  </div>

  <script>
    // If desired, uncomment the next line to auto-print when the page loads.
    // window.onload = function() { window.print(); };
  </script>
</body>
</html>
