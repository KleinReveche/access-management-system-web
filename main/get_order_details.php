<?php
include('../database/database.php');
$stmt = $pdo->query("SELECT favicon FROM website_settings WHERE id = 1");

$favicon = $stmt->fetchColumn();
// Check if order ID is provided
if (isset($_GET['id'])) {
    $order_id = $_GET['id'];

    // Fetch the order details based on the order ID
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order) {
        // Decode the order details (products stored as JSON)
        $order_details = json_decode($order['products'], true);

        // Start generating the HTML content for the order details
        $output = "<h5>Order ID: " . $order['id'] . "</h5>";
        $output .= "<p><strong>Date:</strong> " . $order['order_date'] . "</p>";
        $output .= "<p><strong>Total Amount:</strong> $" . number_format($order['total_amount'], 2) . "</p>";
        $output .= "<p><strong>Payment Method:</strong> " . ucfirst($order['payment_method']) . "</p>";
        $output .= "<p><strong>Status:</strong> " . ucfirst($order['status']) . "</p>";

        // Add the staff_username field to the output
        $output .= "<p><strong>Staff Username:</strong> " . htmlspecialchars($order['staff_username']) . "</p>";

        $output .= "<hr>";

        $output .= "<h6>Products in Order:</h6>";

        // Display the ordered products
        if (!empty($order_details)) {
            foreach ($order_details as $product) {
                $output .= "<div class='order-item'>";
                $output .= "<p><strong>" . $product['name'] . "</strong></p>";
                $output .= "<p>Price: $" . number_format($product['price'], 2) . "</p>";
                $output .= "<p>Quantity: " . $product['quantity'] . "</p>";
                $output .= "<p>Total: $" . number_format($product['total'], 2) . "</p>";
                $output .= "<hr>";
                $output .= "</div>";
            }
        } else {
            $output .= "<p>No products in this order.</p>";
        }

        // Return the generated content to be displayed in the modal
        echo $output;
    } else {
        echo "<p>Order not found.</p>";
    }
} else {
    echo "<p>Order ID is missing.</p>";
}
?>
<link rel="icon" type="image/x-icon" href="<?php echo $favicon; ?>">
