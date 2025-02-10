<?php
// Include common header (which includes DOCTYPE, head section, and library links)
include('../includes/header.php');
checkUserAccess(['Admin', 'Cashier', 'Staff']);

// Set PHP default timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');
// Set MySQL session timezone to Asia/Manila so that CURDATE() and HOUR() work in PH time
$pdo->query("SET time_zone = '+08:00'");

// Fetch total expenses from the `expenses` table
$stmt_expenses = $pdo->prepare("SELECT SUM(amount) AS total_expenses FROM expenses");
$stmt_expenses->execute();
$total_expenses_data = $stmt_expenses->fetch(PDO::FETCH_ASSOC);
$total_expenses = $total_expenses_data['total_expenses'] ?? 0;

// Fetch total sales from the `orders` table (only completed orders)
$stmt_sales = $pdo->prepare("SELECT SUM(total_amount) AS total_sales FROM orders WHERE status='completed'");
$stmt_sales->execute();
$total_sales_data = $stmt_sales->fetch(PDO::FETCH_ASSOC);
$total_sales = $total_sales_data['total_sales'] ?? 0;

// Fetch pending sales from the `orders` table (pending orders)
$stmt_pending_sales = $pdo->prepare("SELECT SUM(total_amount) AS total_pending_sales FROM orders WHERE status='pending'");
$stmt_pending_sales->execute();
$total_pending_sales_data = $stmt_pending_sales->fetch(PDO::FETCH_ASSOC);
$total_pending_sales = $total_pending_sales_data['total_pending_sales'] ?? 0;

// Fetch Capital and Cash on Hand from the `cashier_data` table
$stmt_financials = $pdo->prepare("SELECT * FROM cashier_data LIMIT 1");
$stmt_financials->execute();
$financial_data = $stmt_financials->fetch(PDO::FETCH_ASSOC);
$capital = $financial_data['capital'] ?? 0;
$cash_on_hand = $financial_data['cash_on_hand'] ?? 0;

// Calculate Gross Income and Profit
$gross_income = $total_sales; // Assuming gross income equals total sales (completed)
$profit = $total_sales - $total_expenses;

// Prepare data for charts
$expense_vs_sales = [$total_expenses, $total_sales];
$financial_distribution = [$capital, $cash_on_hand, $total_expenses];

// ==================================================================
// Fetch Hourly Profit Data for the Current Day (using PH time)
// ==================================================================
// Because we set the MySQL session timezone to '+08:00', the following query will use Philippine time.
$stmt_profit_over_time = $pdo->prepare("
    SELECT 
        HOUR(o.order_date) AS hour,
        SUM(o.total_amount) - (
            SELECT IFNULL(SUM(e.amount), 0)
            FROM expenses e
            WHERE HOUR(e.date) = HOUR(o.order_date) 
              AND DATE(e.date) = CURDATE()
        ) AS hourly_profit
    FROM orders o
    WHERE o.status = 'completed' 
      AND DATE(o.order_date) = CURDATE()
    GROUP BY HOUR(o.order_date)
    ORDER BY HOUR(o.order_date) ASC
");
$stmt_profit_over_time->execute();

// Initialize an array with 24 hours (0 to 23) with default profit 0
$hourly_profit_data = array_fill(0, 24, 0);
while ($row = $stmt_profit_over_time->fetch(PDO::FETCH_ASSOC)) {
    $hour = (int)$row['hour'];
    $hourly_profit_data[$hour] = $row['hourly_profit'];
}
$profit_over_time = array_values($hourly_profit_data);

// Create labels for each hour in 12-hour format (e.g., "12:00 AM", "1:00 AM", …, "11:00 PM")
$hour_labels = [];
for ($i = 0; $i < 24; $i++) {
    if ($i == 0) {
        $hour_labels[] = "12:00 AM";
    } elseif ($i < 12) {
        $hour_labels[] = $i . ":00 AM";
    } elseif ($i == 12) {
        $hour_labels[] = "12:00 PM";
    } else {
        $hour_labels[] = ($i - 12) . ":00 PM";
    }
}
?>

<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Inline Styles (matching the Master Key Management design template) -->
<style>
  :root {
    /* Primary (Base) Color: #120E47 */
    --primary-color: #120E47;
    /* Secondary Color: Softer Purple */
    --secondary-color: #7C43BD;
    /* Accent Color: Light Lavender */
    --accent-color: #D1C4E9;
    /* Danger Color remains red */
    --danger-color: #dc2626;
  }
  body {
    background: #f3f4f6;
  }
  /* Main container styling matching the dashboard concept */
  .main-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }
  /* Header styling */
  .header {
    background: #ffffff;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .header h2 {
    font-weight: 600;
    color: var(--primary-color);
  }
  /* Card styling */
  .card {
    background: #ffffff;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    transition: transform 0.3s ease;
  }
  .card-hover {
    transform: scale(1.03);
    cursor: pointer;
  }
  .card-header {
    background-color: var(--primary-color);
    color: #ffffff;
    padding: 15px;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }
  .card-body {
    padding: 20px;
  }
  /* Toast Message Styling (if used) */
  .toast {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1055;
    border-radius: 8px;
  }
  /* Button Styling Using Theme Variables */
  .btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
  }
  .btn-primary:hover {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
  }
  .btn-secondary {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
  }
  .btn-secondary:hover {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
  }
</style>

<!-- Main Content Container -->
<div class="container mt-5 main-container">
  <!-- Header Section -->
  <div class="header">
    <h2>
      <i class="fas fa-tachometer-alt me-2"></i>Dashboard
    </h2>
  </div>

  <!-- Financial Overview Cards (Row 1) -->
  <div class="row">
    <!-- Gross Income Card -->
    <div class="col-md-4 mb-4">
      <div class="card shadow-sm border-success">
        <div class="card-body text-center">
          <h5 class="card-title text-success">
            <i class="fas fa-peso-sign me-2"></i>Gross Income
          </h5>
          <h4 class="card-text">₱<?= number_format($gross_income, 2); ?></h4>
        </div>
      </div>
    </div>
    <!-- Capital Card -->
    <div class="col-md-4 mb-4">
      <div class="card shadow-sm border-primary">
        <div class="card-body text-center">
          <h5 class="card-title text-primary">
            <i class="fas fa-piggy-bank me-2"></i>Capital
          </h5>
          <h4 class="card-text">₱<?= number_format($capital, 2); ?></h4>
        </div>
      </div>
    </div>
    <!-- Cash on Hand Card -->
    <div class="col-md-4 mb-4">
      <div class="card shadow-sm border-warning">
        <div class="card-body text-center">
          <h5 class="card-title text-warning">
            <i class="fas fa-wallet me-2"></i>Cash on Hand
          </h5>
          <h4 class="card-text">₱<?= number_format($cash_on_hand, 2); ?></h4>
        </div>
      </div>
    </div>
  </div>

  <!-- Sales and Profit Cards (Row 2) -->
  <div class="row">
    <!-- Total Expenses Card -->
    <div class="col-md-4 mb-4">
      <div class="card shadow-sm border-danger">
        <div class="card-body text-center">
          <h5 class="card-title text-danger">
            <i class="fas fa-credit-card me-2"></i>Total Expenses
          </h5>
          <h4 class="card-text">₱<?= number_format($total_expenses, 2); ?></h4>
        </div>
      </div>
    </div>
    <!-- Completed Sales Card -->
    <div class="col-md-4 mb-4">
      <div class="card shadow-sm border-info">
        <div class="card-body text-center">
          <h5 class="card-title text-info">
            <i class="fas fa-check-circle me-2"></i>Completed Sales
          </h5>
          <h4 class="card-text">₱<?= number_format($total_sales, 2); ?></h4>
        </div>
      </div>
    </div>
    <!-- Profit Card -->
    <div class="col-md-4 mb-4">
      <div class="card shadow-sm border-success">
        <div class="card-body text-center">
          <h5 class="card-title text-success">
            <i class="fas fa-chart-line me-2"></i>Profit
          </h5>
          <h4 class="card-text">₱<?= number_format($profit, 2); ?></h4>
        </div>
      </div>
    </div>
  </div>

  <!-- Pending Sales Card (Row 3) -->
  <div class="row">
    <div class="col-md-4 offset-md-4 mb-4">
      <div class="card shadow-sm border-warning">
        <div class="card-body text-center">
          <h5 class="card-title text-warning">
            <i class="fas fa-hourglass-half me-2"></i>Pending Sales
          </h5>
          <h4 class="card-text">₱<?= number_format($total_pending_sales, 2); ?></h4>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts Section -->
  <div class="row">
    <!-- Expenses vs Sales Chart -->
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm border-secondary">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Expenses vs Sales</h5>
        </div>
        <div class="card-body">
          <canvas id="expenseVsSalesChart"></canvas>
        </div>
      </div>
    </div>
    <!-- Profit Over Time Chart (Hourly) -->
    <div class="col-md-6 mb-4">
      <div class="card shadow-sm border-secondary">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Profit Over Time (Hourly)</h5>
        </div>
        <div class="card-body">
          <canvas id="profitOverTimeChart"></canvas>
        </div>
      </div>
    </div>
    <!-- Financial Distribution Chart -->
    <div class="col-md-12 mb-4">
      <div class="card shadow-sm border-secondary">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Financial Distribution</h5>
        </div>
        <div class="card-body">
          <div style="max-width: 500px; margin: auto;">
            <canvas id="financialDistributionChart" style="width: 100%; height: 300px;"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- End of Main Content Container -->

<!-- Include necessary JS libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<?php include('../includes/footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Include Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  // Expenses vs Sales Chart
  var expenseVsSalesCtx = document.getElementById('expenseVsSalesChart').getContext('2d');
  var expenseVsSalesChart = new Chart(expenseVsSalesCtx, {
      type: 'bar',
      data: {
          labels: ['Expenses', 'Sales'],
          datasets: [{
              label: 'Amount (₱)',
              data: [<?= $expense_vs_sales[0]; ?>, <?= $expense_vs_sales[1]; ?>],
              backgroundColor: [
                  'rgba(255, 99, 132, 0.2)',
                  'rgba(54, 162, 235, 0.2)'
              ],
              borderColor: [
                  'rgba(255, 99, 132, 1)',
                  'rgba(54, 162, 235, 1)'
              ],
              borderWidth: 1
          }]
      },
      options: {
          responsive: true,
          scales: {
              y: {
                  beginAtZero: true
              }
          }
      }
  });

  // Profit Over Time Chart (Hourly)
  // The chart now uses the base color (#120E47) for the line
  var profitOverTimeCtx = document.getElementById('profitOverTimeChart').getContext('2d');
  var profitOverTimeChart = new Chart(profitOverTimeCtx, {
      type: 'line',
      data: {
          labels: <?= json_encode($hour_labels); ?>,
          datasets: [{
              label: 'Profit (₱)',
              data: <?= json_encode($profit_over_time); ?>,
              borderColor: 'rgba(18, 14, 71, 1)',
              backgroundColor: 'rgba(18, 14, 71, 0.2)',
              fill: true,
              tension: 0.3
          }]
      },
      options: {
          responsive: true
      }
  });

  // Financial Distribution Chart
  var financialDistributionCtx = document.getElementById('financialDistributionChart').getContext('2d');
  var financialDistributionChart = new Chart(financialDistributionCtx, {
      type: 'pie',
      data: {
          labels: ['Capital', 'Cash on Hand', 'Expenses'],
          datasets: [{
              data: <?= json_encode($financial_distribution); ?>,
              backgroundColor: [
                  'rgba(255, 206, 86, 0.2)', 
                  'rgba(75, 192, 192, 0.2)', 
                  'rgba(255, 99, 132, 0.2)'
              ],
              borderColor: [
                  'rgba(255, 206, 86, 1)', 
                  'rgba(75, 192, 192, 1)', 
                  'rgba(255, 99, 132, 1)'
              ],
              borderWidth: 1
          }]
      },
      options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
              legend: {
                  position: 'top'
              }
          }
      }
  });
</script>
