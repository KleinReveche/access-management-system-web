<?php
session_start();


if (!isset($_SESSION['admin_username'])) {
    
    include 'login.php';
    exit();
} else {
   
    header('Location: main/dashboard.php');
    exit();
}
?>
