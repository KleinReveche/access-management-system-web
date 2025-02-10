<?php
session_start();
include('../../database/database.php');

// Ensure the user is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: access_denied.php');
    exit();
}

// Fetch the current master key
$stmt_key = $pdo->prepare("SELECT * FROM master_key LIMIT 1");
$stmt_key->execute();
$master_key_data = $stmt_key->fetch(PDO::FETCH_ASSOC);
$master_key = $master_key_data['key_value'] ?? '';

// Handle the form submission
if (isset($_POST['update_key'])) {
    $new_master_key = $_POST['new_master_key'];
    $confirm_master_key = $_POST['confirm_master_key'];
    $entered_key = $_POST['master_key'];

    // Validate the master key
    if ($entered_key != $master_key) {
        $_SESSION['error_message'] = "Invalid master key. Changes cannot be saved.";
        header('Location: keys.php');
        exit();
    }

    if ($new_master_key === $confirm_master_key) {
        // Update the master key in the database
        $stmt = $pdo->prepare("UPDATE master_key SET key_value = ? WHERE id = 1");
        $stmt->execute([$new_master_key]);

        // Log the activity
        $stmt_log = $pdo->prepare("INSERT INTO activities_log (activity_type, activity_description, activity_data, created_by) 
                                   VALUES (?, ?, ?, ?)");
        $stmt_log->execute([
            'update',
            'Master key updated',
            json_encode(['new_master_key' => $new_master_key]),
            $_SESSION['admin_username']
        ]);

        $_SESSION['success_message'] = "Master key updated successfully!";
        header('Location: ../keys.php');
        exit();
    } else {
        $_SESSION['error_message'] = "Master keys do not match!";
        header('Location: ../keys.php');
        exit();
    }
}
?>
