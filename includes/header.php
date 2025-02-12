<?php
// header.php
ob_start();
session_start();

$base_url = 'https://accessmanagementsystem.online/index.php';
date_default_timezone_set('Asia/Manila');

// Include required files
include_once __DIR__ . '/../database/database.php';
include(__DIR__ . '/maintainance_check.php');
include(__DIR__ . '/roleaccess.php');

// Set default session values if not set
$username  = $_SESSION['admin_username'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'Unauthorized';

// Get unread notifications count
$unread_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM announcements WHERE is_read = 0");
    $stmt->execute();
    $unread_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Notification count error: " . $e->getMessage());
}

// Default website settings
$settings = [
    'site_title'        => 'POS System',
    'favicon'           => '../main/uploads/ws/favicon.jpg',
    'contact_email'     => 'admin@pos.com',
    'school_logo'       => '',
    'organization_logo' => ''
];

try {
    $stmt = $pdo->query("SELECT site_title, favicon, contact_email, school_logo, organization_logo FROM website_settings WHERE id = 1");
    $db_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($db_settings) {
        $settings = array_merge($settings, $db_settings);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// If a user is logged in, fetch additional details
if (!empty($username) && $username !== 'Guest') {
    try {
        $stmt = $pdo->prepare("SELECT username, role FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $username  = $user['username'];
            $user_role = $user['role'];
        }
    } catch (PDOException $e) {
        error_log("User fetch error: " . $e->getMessage());
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($settings['site_title']); ?></title>
  <link rel="icon" type="image/x-icon" href=<?= htmlspecialchars($settings['favicon']); ?>>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  
  <script>
      var link = document.createElement('link');
link.type = 'image/x-icon';
link.rel = 'shortcut icon';
link.href = '<?= htmlspecialchars($settings['favicon']); ?>';
document.getElementsByTagName('head')[0].appendChild(link);
  </script>
  <style>
    /* Dashboard Theme Styling (60/30/10 rule) */
    :root {
      --dashboard-primary: #1B263B;    /* 60% - Dominant Color */
      --dashboard-secondary: #415A77;  /* 30% - Secondary Color */
      --dashboard-accent: #FCA311;     /* 10% - Accent Color */
      --dashboard-background: #F8F9FA;
      --dashboard-text-light: #FFFFFF;
      --dashboard-hover: #162337;
    }

    /* HEADER */
    .dashboard-header {
      background-color: var(--dashboard-primary);
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      padding: 0.5rem 1rem;
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1100;
      display: flex;
      align-items: center;
    }
    .dashboard-header__toggle {
      background: transparent;
      border: none;
      color: var(--dashboard-text-light);
      font-size: 1.5rem;
      cursor: pointer;
      margin-right: 1rem;
    }
    .dashboard-header__brand {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      font-size: 1.5rem;
      color: var(--dashboard-text-light);
      text-decoration: none;
    }
    .dashboard-header__actions {
      margin-left: auto;
      display: flex;
      align-items: center;
    }
    .dashboard-header__clock {
      font-family: 'Fira Code', monospace;
      font-size: 0.9rem;
      background: rgba(255,255,255,0.1);
      padding: 0.25rem 0.75rem;
      border-radius: 0.375rem;
      border: 1px solid rgba(255,255,255,0.15);
      margin-left: 1rem;
      color: var(--dashboard-text-light);
    }

    /* Notification Panel */
    .dashboard-notification-panel {
      width: 320px;
      max-width: 95vw;
      padding: 0;
      border-radius: 0.5rem;
      overflow: hidden;
    }
    .dashboard-notification-panel .panel-header {
      padding: 0.75rem 1rem;
      background: var(--dashboard-primary);
      color: var(--dashboard-text-light);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .dashboard-notification-panel .panel-header h6 {
      margin: 0;
      font-size: 1rem;
    }
    .dashboard-notification-panel .panel-header a {
      font-size: 0.85rem;
      text-decoration: none;
      color: var(--dashboard-accent);
    }
    .dashboard-notification-panel .panel-body {
      max-height: 300px;
      overflow-y: auto;
      background: #fff;
      color: #333;
    }
    .dashboard-notification-panel .notification-item {
      padding: 0.75rem 1rem;
      border-bottom: 1px solid #e9ecef;
      transition: background-color 0.2s ease;
      cursor: pointer;
    }
    .dashboard-notification-panel .notification-item:hover {
      background-color: #f8f9fa;
    }
    .dashboard-notification-panel .notification-item:last-child {
      border-bottom: none;
    }
    
        /* Force the small dropdown caret icon to be white */
    .dashboard-header .dropdown-toggle::after {
      border-top-color: var(--dashboard-text-light) !important;
    }
    
    /* SIDEBAR */
    .dashboard-sidebar {
      width: 260px;
      background-color: var(--dashboard-secondary);
      height: calc(100vh - 64px);
      position: fixed;
      top: 64px;
      left: -260px;
      transition: all 0.3s ease;
      z-index: 1000;
      overflow-y: auto;
      box-shadow: 4px 0 6px rgba(0,0,0,0.1);
    }
    .dashboard-sidebar.active {
      left: 0;
    }
    .dashboard-sidebar__user {
      padding: 1.5rem;
      border-bottom: 1px solid rgba(255,255,255,0.2);
      margin-bottom: 1rem;
      color: var(--dashboard-text-light);
      display: flex;
      align-items: center;
    }
    .dashboard-sidebar__username {
      font-weight: 600;
      font-size: 1.1rem;
    }
    .dashboard-sidebar__role {
      font-size: 0.9rem;
      color: rgba(255,255,255,0.8);
    }
    .dashboard-sidebar__nav {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .dashboard-sidebar__nav-item {
      margin: 0.25rem 1rem;
    }
    .dashboard-sidebar__nav-link {
      display: flex;
      align-items: center;
      padding: 0.75rem 1.5rem;
      border-radius: 0.5rem;
      text-decoration: none;
      color: rgba(255,255,255,0.9);
      transition: background-color 0.2s ease;
    }
    .dashboard-sidebar__nav-link:hover,
    .dashboard-sidebar__nav-link.active {
      background-color: var(--dashboard-primary);
      color: var(--dashboard-text-light);
    }
    .dashboard-sidebar__nav-link i {
      margin-right: 0.75rem;
    }
    /* Emphasized Menu Items (Cashier, Orders, POS) */
    .dashboard-sidebar__nav-link--emphasis {
      font-weight: bold;
      background-color: var(--dashboard-accent);
      color: var(--dashboard-primary);
    }
    .dashboard-sidebar__nav-link--emphasis:hover {
      background-color: #e5a70a;
    }
    /* Logout Button */
    .dashboard-sidebar__logout {
      display: block;
      max-width: 180px;
      margin: 1rem auto;
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      background-color: #FF6961;
      color: var(--dashboard-primary);
      text-decoration: none;
      font-weight: bold;
      text-align: center;
      transition: background-color 0.2s ease;
    }
    .dashboard-sidebar__logout:hover {
      background-color: #FF6961;
    }

    /* MAIN CONTENT */
    .dashboard-content {
      margin-top: 80px;
      padding: 2rem;
      transition: padding-left 0.3s;
      background-color: var(--dashboard-background);
      min-height: calc(100vh - 64px);
    }

    /* FOOTER */
    .dashboard-footer {
      background-color: var(--dashboard-background);
      border-top: 1px solid #dee2e6;
      padding: 0.75rem 1rem;
      font-size: 0.9rem;
      margin-top: 2rem;
    }
    .dashboard-footer .container {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .dashboard-footer__logo {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
      max-width: 100%;
    }
    .dashboard-footer__text {
      flex-grow: 1;
      text-align: center;
    }
    @media (max-width: 768px) {
      .dashboard-footer__logo {
        width: 40px;
        height: 40px;
      }
      .dashboard-footer {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
      }
      .dashboard-sidebar {
        width: 220px;
        left: -220px;
      }
      .dashboard-sidebar.active {
        left: 0;
      }
      .dashboard-sidebar__nav-link {
        padding: 0.75rem 1rem;
        margin: 0 0.75rem;
      }
      .dashboard-sidebar__nav-link--emphasis {
        padding: 0.75rem 1rem;
        margin: 0 0.75rem;
      }
      .dashboard-sidebar__logout {
        max-width: 160px;
        padding: 0.5rem 0.75rem;
      }
      .dashboard-notification-panel {
        width: 280px;
      }
    }
  </style>
</head>
<body>
  <!-- HEADER -->
  <nav class="dashboard-header">
    <button class="dashboard-header__toggle" id="dashboardSidebarToggle">
      <i class="bi bi-list"></i>
    </button>
    <a href="<?= htmlspecialchars($base_url) ?>" class="dashboard-header__brand">
      <?= htmlspecialchars($settings['site_title']) ?>
    </a>
    <div class="dashboard-header__actions">
      <!-- Notification Dropdown -->
      <div class="dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
          <i class="bi bi-bell" style="color: var(--dashboard-text-light);"></i>
          <?php if ($unread_count > 0): ?>
            <span class="badge bg-danger" id="dashboardNotificationCount"><?= $unread_count ?></span>
          <?php endif; ?>
        </a>
        <div class="dropdown-menu dropdown-menu-end dashboard-notification-panel">
          <div class="panel-header">
            <h6>Notifications</h6>
            <a href="#" id="dashboardMarkAllRead">Mark All as Read</a>
          </div>
          <div class="panel-body" id="dashboardNotificationList">
            <?php include __DIR__ . '/../main/notif/fetch_notifications.php'; ?>
          </div>
        </div>
      </div>
      <div class="dashboard-header__clock" id="dashboardClock"></div>
    </div>
  </nav>

  <!-- SIDEBAR -->
  <aside class="dashboard-sidebar" id="dashboardSidebar">
    <div class="dashboard-sidebar__user">
      <i class="bi bi-person-circle me-2" style="font-size: 1.5rem;"></i>
      <div>
        <div class="dashboard-sidebar__username"><?= htmlspecialchars($username) ?></div>
        <div class="dashboard-sidebar__role"><?= htmlspecialchars(ucfirst($user_role)) ?></div>
      </div>
    </div>
    <ul class="dashboard-sidebar__nav">
      <?php
      $links = [
          'dashboard.php'        => ['icon' => 'speedometer2',    'text' => 'Dashboard'],
          'users.php'            => ['icon' => 'people',          'text' => 'Users'],
          'expenses.php'         => ['icon' => 'piggy-bank',      'text' => 'Expenses'],
          'products.php'         => ['icon' => 'box-seam',        'text' => 'Products'],
          'product_category.php' => ['icon' => 'tag',             'text' => 'Categories'],
          'keys.php'             => ['icon' => 'key',             'text' => 'Key'],
          'settings.php'         => ['icon' => 'gear',            'text' => 'Settings'],
          'view_logs.php'        => ['icon' => 'clipboard-data',  'text' => 'Logs'],
          'announcements.php'    => ['icon' => 'megaphone',       'text' => 'Announcement'],
          'website_settings.php' => ['icon' => 'globe2',          'text' => 'Website Settings']
      ];
      foreach ($links as $page => $data) {
          // For Cashier/Staff, skip pages they should not access.
        //   if (in_array($user_role, ['Cashier', 'Staff']) && in_array($page, ['users.php', 'expenses.php', 'keys.php', 'view_logs.php', 'website_settings.php'])) {
        //       continue;
        //   }
          $active = ($current_page === $page) ? 'active' : '';
          echo "<li class='dashboard-sidebar__nav-item'>
                    <a class='dashboard-sidebar__nav-link $active' href='$page'>
                      <i class='bi bi-{$data['icon']}'></i> {$data['text']}
                    </a>
                </li>";
      }
      ?>
      <div class="mt-3">
        <li class="dashboard-sidebar__nav-item">
          <a class="dashboard-sidebar__nav-link dashboard-sidebar__nav-link--emphasis <?= ($current_page === 'cashier.php') ? 'active' : '' ?>" href="cashier.php">
            <i class="bi bi-cash-coin"></i> Cashier
          </a>
        </li>
        <li class="dashboard-sidebar__nav-item">
          <a class="dashboard-sidebar__nav-link dashboard-sidebar__nav-link--emphasis <?= ($current_page === 'orders.php') ? 'active' : '' ?>" href="orders.php">
            <i class="bi bi-receipt"></i> Orders
          </a>
        </li>
        <li class="dashboard-sidebar__nav-item">
          <a class="dashboard-sidebar__nav-link dashboard-sidebar__nav-link--emphasis <?= ($current_page === 'pos.php') ? 'active' : '' ?>" href="pos.php">
            <i class="bi bi-terminal"></i> POS
          </a>
        </li>
      </div>
    </ul>
    <a href="logout.php" class="dashboard-sidebar__logout">
      <i class="bi bi-box-arrow-left me-2"></i> Logout
    </a>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="dashboard-content">
<!-- Page-specific content begins here -->
