<?php  
// Start output buffering para hindi magkaroon ng hindi inaasahang output
ob_start();
session_start();
date_default_timezone_set('Asia/Manila'); // Gamitin ang Philippine time

include('../../database/database.php');
require_once('../../vendor/tecnickcom/tcpdf/tcpdf.php'); 

$school_logo_url = "https://accessmanagementsystem.online/main/uploads/ws/schoollogo.jpg";
$organization_logo_url = "https://accessmanagementsystem.online/main/uploads/ws/organizationlogo.jpg";

$stmt = $pdo->query("SELECT favicon, site_title FROM website_settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
$favicon = isset($settings['favicon']) ? $settings['favicon'] : 'default.ico';
$site_title = isset($settings['site_title']) ? $settings['site_title'] : 'Your Site Title';


if (!isset($_SESSION['admin_username'])) {
    echo "error: Unauthorized access.";
    exit();
}


if (isset($_POST['order_details']) && isset($_POST['total_amount']) && isset($_POST['payment_method'])) {
    $order_details = json_decode($_POST['order_details'], true);  // I-decode ang JSON order details
    $total_amount = $_POST['total_amount'];
    $payment_method = $_POST['payment_method'];
    $staff_username = $_SESSION['admin_username']; 

    
    $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : '';
    $grade_program = isset($_POST['grade_program']) ? $_POST['grade_program'] : '';
    $cash_paid = isset($_POST['cash_paid']) ? $_POST['cash_paid'] : null;
    $change_amount = isset($_POST['change_amount']) ? $_POST['change_amount'] : null;
    $signature = isset($_POST['signature']) ? $_POST['signature'] : '';

    if (!is_array($order_details) || empty($order_details)) {
        echo "error: Invalid order details.";
        exit();
    }

    try {
        
        $current_timestamp = date('Y-m-d H:i:s');

      
        $stmt = $pdo->prepare("INSERT INTO orders 
            (order_date, customer_name, grade_program, total_amount, cash_paid, change_amount, payment_method, status, products, staff_username, signature)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
        $stmt->execute([
            $current_timestamp,    
            $customer_name,
            $grade_program,
            $total_amount,
            $cash_paid,
            $change_amount,
            $payment_method,
            json_encode($order_details),
            $staff_username,
            $signature
        ]);
        $order_id = $pdo->lastInsertId();

       
        $payment_proof = null;
        if ($payment_method === 'online_wallet' && isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/proof/'; 
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            $fileTmpName = $_FILES['payment_proof']['tmp_name'];
            $fileType = mime_content_type($fileTmpName);
            if (!in_array($fileType, $allowedTypes)) {
                die("error: Invalid file type. Only JPG, PNG, and PDF allowed.");
            }
            $fileExt = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            $newFileName = uniqid("proof_", true) . '.' . $fileExt;
            $filePath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpName, $filePath)) {
                $payment_proof = 'uploads/proof/' . $newFileName;
                $stmtUpdate = $pdo->prepare("UPDATE orders SET payment_proof = ? WHERE id = ?");
                $stmtUpdate->execute([$payment_proof, $order_id]);
            } else {
                die("error: Failed to upload file.");
            }
        }

       
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(0, 79, 153);
        $pdf->Cell(0, 10, $site_title . ' - Order Receipt', 0, 1, 'C', 1);

        
        $pdf->Image($school_logo_url, 20, 30, 30, 30);
        $pdf->Image($organization_logo_url, 60, 30, 30, 30);

       
        $display_date = date('F j, Y, g:i A', strtotime($current_timestamp));

      
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(40);
        $pdf->Cell(0, 10, 'Order ID: #' . $order_id, 0, 1, 'L');
        $pdf->Cell(0, 10, 'Cashier: ' . $staff_username, 0, 1, 'L');
        $pdf->Cell(0, 10, 'Payment Method: ' . $payment_method, 0, 1, 'L');
        $pdf->Cell(0, 10, 'Date: ' . $display_date, 0, 1, 'L');

       
        $pdf->Ln(10);
        $pdf->Cell(70, 10, 'Product', 1, 0, 'C');
        $pdf->Cell(30, 10, 'Price', 1, 0, 'C');
        $pdf->Cell(30, 10, 'Qty', 1, 0, 'C');
        $pdf->Cell(30, 10, 'Total', 1, 1, 'C');

        foreach ($order_details as $item) {
            $pdf->Cell(70, 10, $item['name'], 1);
            $pdf->Cell(30, 10, '₱' . number_format($item['price'], 2), 1, 0, 'R');
            $pdf->Cell(30, 10, $item['quantity'], 1, 0, 'C');
            $pdf->Cell(30, 10, '₱' . number_format($item['total'], 2), 1, 1, 'R');
        }

        
        $pdf->Ln(10);
        $pdf->Cell(0, 10, 'Subtotal: ₱' . number_format($total_amount, 2), 0, 1, 'R');
        $pdf->Cell(0, 10, 'Tax: ₱50.00', 0, 1, 'R');
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Total: ₱' . number_format($total_amount + 50, 2), 0, 1, 'R');

     
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 10, "Thank you for your purchase! We hope you have a wonderful day!", 0, 'C');
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->MultiCell(0, 10, "Wishing you a Happy Valentine's Day! ❤️", 0, 'C');

        
        $receiptsDir = __DIR__. '/../receipts';
        if (!is_dir($receiptsDir)) {
            mkdir($receiptsDir, 0755, true);
        }
        $pdfFilePath = $receiptsDir . '/receipt_' . $order_id . '.pdf';
        $pdf->Output($pdfFilePath, 'F');

        /* ---------------------------------------------------------------------
           Log the Order Placement
        --------------------------------------------------------------------- */
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $stmt = $pdo->prepare("INSERT INTO order_logs (order_id, admin_username, order_time, ip_address) VALUES (?, ?, NOW(), ?)");
        $stmt->execute([$order_id, $staff_username, $ip_address]);

        /* ---------------------------------------------------------------------
           Update Order with Transaction ID and Payment Proof (if online_wallet)
        --------------------------------------------------------------------- */
        if ($payment_method === 'online_wallet') {
            $transaction_id = $_POST['transaction_id']; // Manually input Transaction ID
            if ($payment_proof) {
                $stmt = $pdo->prepare("UPDATE orders SET transaction_id = ?, payment_proof = ? WHERE id = ?");
                $stmt->execute([$transaction_id, $payment_proof, $order_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE orders SET transaction_id = ? WHERE id = ?");
                $stmt->execute([$transaction_id, $order_id]);
            }
        }

        /* ---------------------------------------------------------------------
           Email Notification using PHP mail()
        --------------------------------------------------------------------- */
        $to = "techhubph01@gmail.com";
        $subject = "New Order Has Been Placed at " . $site_title;
        $email_date = date('F j, Y, g:i A', strtotime($current_timestamp));
        $message = '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>New Order Notification</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 20px;
    }
    .container {
      background-color: #ffffff;
      border-radius: 5px;
      padding: 20px;
      box-shadow: 0 2px 3px rgba(0,0,0,0.1);
      max-width: 600px;
      margin: auto;
    }
    h1 {
      color: #333333;
      font-size: 24px;
    }
    p {
      font-size: 16px;
      line-height: 1.5;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }
    table, th, td {
      border: 1px solid #dddddd;
    }
    th, td {
      padding: 10px;
      text-align: left;
    }
    .footer {
      margin-top: 30px;
      font-size: 12px;
      color: #777777;
      text-align: center;
    }
    .logos {
      text-align: center;
      margin-bottom: 20px;
    }
    .logos img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      border: 2px solid #ddd;
      margin: 0 10px;
      object-fit: cover;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logos">
      <img src="' . $school_logo_url . '" alt="School Logo">
      <img src="' . $organization_logo_url . '" alt="Organization Logo">
    </div>
    <h1>New Order Has Been Placed at ' . $site_title . '!</h1>
    <p>Hello,</p>
    <p>A new order has been placed. Please find the details below:</p>
    <table>
      <tr>
        <th>Order ID</th>
        <td>' . $order_id . '</td>
      </tr>
      <tr>
        <th>Cashier</th>
        <td>' . htmlspecialchars($staff_username) . '</td>
      </tr>
      <tr>
        <th>Payment Method</th>
        <td>' . htmlspecialchars($payment_method) . '</td>
      </tr>
      <tr>
        <th>Total Amount</th>
        <td>₱' . number_format($total_amount, 2) . '</td>
      </tr>
      <tr>
        <th>Date</th>
        <td>' . $email_date . '</td>
      </tr>
    </table>
    <p>Please check the system for further details.</p>
    <div class="footer">
      <p>Regards,<br>' . $site_title . ' Support Team</p>
      <p>' . $site_title . '</p>
    </div>
  </div>
</body>
</html>';

        // Set headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . $site_title . ' Support Team <support@accessmanagementsystem.online>' . "\r\n";
        $headers .= 'Reply-To: support@accessmanagementsystem.online' . "\r\n";

       
        mail($to, $subject, $message, $headers);

       
        ob_clean();
        echo "success";
        exit();
    } catch (Exception $e) {
        ob_clean();
        echo "error: " . $e->getMessage();
        exit();
    }
}
?>
