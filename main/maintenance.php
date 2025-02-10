<?php
session_start();
include('../database/database.php');

// Kunin ang website settings (maintenance mode, site title, favicon, logos, etc.)
try {
    $stmt = $pdo->prepare("SELECT * FROM website_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $settings = [];
}

$maintenance_mode    = $settings['maintenance_mode'] ?? 0;
$site_title          = htmlspecialchars($settings['site_title'] ?? 'POS System');
$favicon             = htmlspecialchars($settings['favicon'] ?? 'favicon.ico');
$organization_logo   = htmlspecialchars($settings['organization_logo'] ?? '');
$school_logo         = htmlspecialchars($settings['school_logo'] ?? '');
$contact_email       = htmlspecialchars($settings['contact_email'] ?? 'admin@pos.com');

// I-redirect sa dashboard kung naka-off ang maintenance mode o kung ang user ay Admin
if (!$maintenance_mode) {
    header('Location: dashboard.php');
    exit();
}
if (($_SESSION['role'] ?? null) === 'Admin') {
    header('Location: dashboard.php');
    exit();
}

// Itakda ang background image path.
// Siguraduhing tama ang relative path base sa lokasyon ng maintenance.php.
$bg_image = '../main/uploads/bg image.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?= $site_title ?></title>
    
    <!-- Favicon -->
    <?php if (!empty($favicon)): ?>
        <link rel="icon" href="../main/<?= $favicon ?>" type="image/x-icon">
    <?php endif; ?>
    
    <!-- Font Awesome para sa icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Reset at Base */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
            /* Ginamit ang Flexbox para i-center ang container */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Background image at overlay */
        .bg-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('<?= $bg_image ?>') no-repeat center center fixed;
            background-size: cover;
            filter: blur(8px);
            z-index: 0;
        }
        /* Binawasan ang opacity upang hindi masyadong violet */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(18,14,71,0.3);
            z-index: 1;
        }
        
        /* Maintenance container styling */
        .maintenance-container {
            position: relative;
            z-index: 2;
            max-width: 600px;
            width: 90%;
            background: rgba(255,255,255,0.9);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            text-align: center;
        }
        
        /* Branding para sa logos at site title */
        .branding {
            margin-bottom: 20px;
        }
        .branding .logos {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        .branding .logos img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #120E47;
        }
        .branding h1 {
            font-size: 2rem;
            color: #120E47;
            margin: 0;
        }
        
        /* Icon at Progress bar */
        .icon {
            font-size: 4rem;
            color: #120E47;
            margin-bottom: 20px;
        }
        .progress {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-bar {
            width: 45%;
            height: 100%;
            background: #120E47;
            animation: progress 2s ease-in-out infinite;
        }
        @keyframes progress {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(250%); }
        }
        
        /* Text content */
        .maintenance-container p {
            color: #333;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .contact-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="bg-image"></div>
    <div class="overlay"></div>
    
    <div class="maintenance-container">
        <div class="branding">
            <div class="logos">
                <?php if (!empty($organization_logo)): ?>
                    <img src="../main/<?= $organization_logo ?>" alt="Organization Logo">
                <?php endif; ?>
                <?php if (!empty($school_logo)): ?>
                    <img src="../main/<?= $school_logo ?>" alt="School Logo">
                <?php endif; ?>
            </div>
            <!-- Kapag walang logos, ipapakita ang site title -->
            <?php if (empty($organization_logo) && empty($school_logo)): ?>
                <h1><?= $site_title ?></h1>
            <?php endif; ?>
        </div>
        
        <div class="icon">
            <i class="fas fa-tools"></i>
        </div>
        <h1>System Maintenance</h1>
        
        <div class="progress">
            <div class="progress-bar"></div>
        </div>
        
        <p>
            We're currently performing scheduled maintenance to improve our services.<br>
            Please check back later. We apologize for any inconvenience caused.
        </p>
        
        <div class="contact-info">
            <p>
                If you need immediate assistance, please contact us at:<br>
                <a href="mailto:<?= $contact_email ?>"><?= $contact_email ?></a>
            </p>
        </div>
    </div>

    <!-- Auto-reload kada 5 minuto -->
    <meta http-equiv="refresh" content="300">
</body>
</html>
