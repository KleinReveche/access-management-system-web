<?php
// validate_master_key.php
session_start();
include('../../database/database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['master_key'])) {
    $masterKey = $_POST['master_key'];

    // Fetch the master key from the database
    $stmt = $pdo->prepare("SELECT * FROM master_key WHERE id = 1");
    $stmt->execute();
    $storedMasterKey = $stmt->fetch();

    // Check if the entered master key matches the stored one
    if ($storedMasterKey && $storedMasterKey['key_value'] === $masterKey) {
        echo json_encode(['success' => true, 'message' => 'Master key validated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid master key.']);
    }
    exit;
}
?>
