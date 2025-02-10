<?php
session_start();
include('../../database/database.php');

// Ensure the user is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: access_denied.php');
    exit();
}

// Fetch the master key from the database
$stmt_key = $pdo->prepare("SELECT * FROM master_key LIMIT 1");
$stmt_key->execute();
$master_key_data = $stmt_key->fetch(PDO::FETCH_ASSOC);
$master_key = $master_key_data['key_value'] ?? '';

if (isset($_POST['update_financials'])) {
    $capital = $_POST['capital'];
    $cash_on_hand = $_POST['cash_on_hand'];
    $entered_key = $_POST['master_key'];

    // Validate the master key
    if ($entered_key != $master_key) {
        echo "Invalid master key. Changes cannot be saved.";
        exit();
    }

    try {
        // Update capital and cash on hand in the database
        $stmt = $pdo->prepare("UPDATE cashier_data SET capital = ?, cash_on_hand = ?, last_updated = NOW() WHERE id = 1");
        $stmt->execute([$capital, $cash_on_hand]);

        // Log the change into activities_log table
        $stmt_log = $pdo->prepare("INSERT INTO activities_log (activity_type, activity_description, activity_data, created_by) VALUES (?, ?, ?, ?)");
        $stmt_log->execute([
            'update', // Activity type
            'Capital and Cash on Hand updated', // Description of the activity
            json_encode(['capital' => $capital, 'cash_on_hand' => $cash_on_hand]), // Data about the update
            $_SESSION['admin_username'] // Who performed the update
        ]);

        // Redirect back to cashier.php with a success message
        $_SESSION['success_message'] = "Financial data updated successfully!";
        header('Location: ../cashier.php');
        exit();
    } catch (PDOException $e) {
        echo 'Error updating financials: ' . $e->getMessage();
    }
}
?>
