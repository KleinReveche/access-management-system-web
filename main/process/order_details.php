<?php
session_start();
include('../../database/database.php');

// Check if the order_id is passed in the URL
if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];

    // Fetch the order details from the 'orders' table
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the order exists
    if ($order) {
        // Check if 'products' field contains valid JSON
        if (!empty($order['products'])) {
            // Decode the JSON stored in the 'products' column
            $products = json_decode($order['products'], true);

            // If JSON decoding fails (e.g., malformed JSON), return an error
            if (json_last_error() !== JSON_ERROR_NONE) {
                $order['products'] = []; // Set as empty if decoding fails
                $error_message = 'Error decoding products data.';
            } else {
                $order['products'] = $products; // Assign the decoded products array
            }
        } else {
            $order['products'] = []; // No products associated with this order
        }

        // Return the order details as a JSON response
        echo json_encode($order);
    } else {
        // Return an error message if the order is not found
        echo json_encode(['error' => 'Order not found']);
    }
} else {
    // Return an error message if no order_id is provided
    echo json_encode(['error' => 'No order ID provided']);
}
?>
