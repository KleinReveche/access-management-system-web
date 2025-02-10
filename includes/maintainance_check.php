<?php
// maintenance_check.php
// Check if we're already on the maintenance page
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page === 'maintenance.php') return;

// Get maintenance status
global $pdo;
try {
    $stmt = $pdo->query("SELECT maintenance_mode FROM website_settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    $maintenance_mode = $settings['maintenance_mode'] ?? 0;
} catch (PDOException $e) {
    $maintenance_mode = 0;
}

// Redirect logic
if ($maintenance_mode) {
    $userRole = $_SESSION['role'] ?? null;
    
    // Redirect only staff and cashier (case-sensitive)
    if (in_array($userRole, ['Staff', 'Cashier'])) {
        header('Location: maintenance.php');
        exit();
    }
}
?>