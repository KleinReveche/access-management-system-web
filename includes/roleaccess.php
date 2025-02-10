<?php
function checkUserAccess(array $allowedRoles) {
    global $pdo;

    // Ensure the user is logged in
    if (!isset($_SESSION['admin_username'])) {
        echo "<script>alert('Please log in to access this page.');</script>";
        echo "<script>window.history.back();</script>";
        exit();
    }

    $username = $_SESSION['admin_username'];

    try {
        // Fetch user role from the database
        $stmt = $pdo->prepare("SELECT role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch website settings (logo, site title, etc.)
        $settingsStmt = $pdo->prepare("SELECT site_title, favicon, school_logo, organization_logo FROM website_settings LIMIT 1");
        $settingsStmt->execute();
        $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $userRole = $user['role'];

            // Add maintenance mode check here
            $maintenanceStmt = $pdo->prepare("SELECT maintenance_mode FROM website_settings WHERE id = 1");
            $maintenanceStmt->execute();
            $maintenance = $maintenanceStmt->fetch(PDO::FETCH_ASSOC);
            $maintenance_mode = $maintenance['maintenance_mode'] ?? 0;

            // Maintenance mode redirection logic
            if ($maintenance_mode && in_array($userRole, ['Staff', 'Cashier'])) {
                header('Location: maintenance.php');
                exit();
            }

            // Existing role check
            if (!in_array($userRole, $allowedRoles)) {
                echo "<script>
                        document.body.innerHTML += `
                            <div class='popup'>
                                <div class='popup-content'>
                                    <span class='close-btn'>&times;</span>
                                    <i class='fas fa-exclamation-circle icon'></i>
                                    <strong>Dear " . htmlspecialchars($username) . ",</strong>
                                    <p>Your role is <span style='color: #ff0000; font-weight: bold;'>" . htmlspecialchars($userRole) . "</span>. You're not allowed to access this page.</p>
                                    <div class='logos'>
                                        <img src='" . $settings['school_logo'] . "' class='logo' alt='School Logo'>
                                        <img src='" . $settings['organization_logo'] . "' class='logo' alt='Organization Logo'>
                                    </div>
                                    <button class='back-btn' onclick='window.history.back()'>Go Back</button>
                                </div>
                            </div>`;
                        var popup = document.querySelector('.popup');
                        var closeBtn = document.querySelector('.close-btn');
                        closeBtn.onclick = function() {
                            popup.style.display = 'none';
                            window.history.back();
                        };
                        popup.style.display = 'flex';
                      </script>";
                exit();
            }
        } else {
            echo "<script>alert('User not found.');</script>";
            echo "<script>window.history.back();</script>";
            exit();
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
$stmt_settings = $pdo->prepare("SELECT * FROM website_settings WHERE id = 1");
$stmt_settings->execute();
$settings = $stmt_settings->fetch(PDO::FETCH_ASSOC);

// Assign values from settings
$site_title = $settings['site_title'] ?? 'Default Site Title';
$site_description = $settings['site_description'] ?? 'Default site description';
$meta_title = $settings['meta_title'] ?? $site_title;
$meta_description = $settings['meta_description'] ?? $site_description;
$meta_keywords = $settings['meta_keywords'] ?? '';
$favicon = $settings['favicon'] ?? 'default-favicon.ico';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <meta name="author" content="Your Organization">
    <title><?php echo htmlspecialchars($meta_title); ?></title
    <link rel="icon" href="https://accessmanagementsystem.online/assets/uploads/img_6798db921ad71.jpg" type="image/jpeg">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Popup Styles */
        .popup {
            display: none; /* Initially hidden */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .popup-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.8s ease-in-out;
            position: relative;
        }

        .popup-content .icon {
            font-size: 50px;
            color: #ff0000;
            margin-bottom: 20px;
        }

        .popup-content strong {
            font-weight: bold;
            color: #1b1b1b;
            font-size: 20px;
        }

        .popup-content p {
            font-size: 16px;
            color: #333;
            margin-bottom: 20px;
        }

        .logos {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 5px solid #ff0000;
            object-fit: cover;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 30px;
            color: #1b1b1b;
            cursor: pointer;
        }

        .back-btn {
            background-color: #ff0000;
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: #e60000;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <!-- The popup will be inserted here if access is denied -->
</body>
</html>
