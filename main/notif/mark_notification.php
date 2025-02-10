<?php
// mark_notification.php
session_start();
include_once __DIR__ . '/../../database/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Mark all notifications as read
if (isset($_POST['mark_all']) && filter_var($_POST['mark_all'], FILTER_VALIDATE_BOOLEAN)) {
    $stmt = $pdo->prepare("UPDATE announcements SET is_read = 1 WHERE is_read = 0");
    if ($stmt->execute()) {
        echo "All marked as read";
    } else {
        http_response_code(500);
        echo "Error marking all as read";
    }
    exit;
}

// Mark a single notification as read
if (isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $stmt = $pdo->prepare("UPDATE announcements SET is_read = 1 WHERE id = ?");
    if ($stmt->execute([$notification_id])) {
        echo "Marked as read";
    } else {
        http_response_code(500);
        echo "Error marking as read";
    }
    exit;
}

http_response_code(400);
echo "Bad Request";
?>
