<?php
// void_order.php

// Start output buffering to avoid any accidental output
ob_start();
session_start();

header('Content-Type: application/json');

include('../../database/database.php');

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
    $admin_username = (isset($_SESSION['admin_username']) && !empty($_SESSION['admin_username']))
                        ? $_SESSION['admin_username']
                        : 'unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $stmt->execute([$order_id, $admin_username, $ip_address, $action, $payment_proof]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = trim($_POST['order_id']);
    try {
        // Retrieve the current order record
        $stmtFetch = $pdo->prepare("SELECT status, payment_proof FROM orders WHERE id = ?");
        $stmtFetch->execute([$orderId]);
        $order = $stmtFetch->fetch();
        
        if (!$order) {
            echo json_encode([
                'success' => false,
                'message' => 'Order not found.'
            ]);
            // Clean the output buffer and exit
            ob_end_flush();
            exit;
        }
        
        $payment_proof = $order['payment_proof'];
        
        // Update the order status to 'void'
        $stmtUpdate = $pdo->prepare("UPDATE orders SET status = 'void' WHERE id = ?");
        $stmtUpdate->execute([$orderId]);
        
        // Log the void action regardless of whether the order was already void
        logOrderAction($orderId, 'Voided', $payment_proof);
        
        // Clear any previous output
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Order voided successfully.'
        ]);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to void the order: ' . $e->getMessage()
        ]);
    }
    // Flush and end output buffering
    ob_end_flush();
    exit;
}
?>
