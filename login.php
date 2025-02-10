<!--login ni jackkkkkkkkkkkkkkkkkkkkk-->
<?php
session_start();
require_once 'database/database.php';


function getWebsiteSettings(PDO $pdo): array {
    $stmt = $pdo->prepare("SELECT * FROM website_settings LIMIT 1");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

$websiteSettings = getWebsiteSettings($pdo);

function checkUserAlreadyLoggedIn(PDO $pdo, string $username): bool {
    $stmt = $pdo->prepare("SELECT login_token, logged_in FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if ($user && (!empty($user['login_token']) || $user['logged_in'] != 0)) {
        return true;
    }
    return false;
}

if (isset($_SESSION['admin_username'])) {
    if (checkUserAlreadyLoggedIn($pdo, $_SESSION['admin_username'])) {
        
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Already Logged In</title>
            <style>
               
                #toast {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background-color: rgba(0, 0, 0, 0.8);
                    color: #fff;
                    padding: 15px 25px;
                    border-radius: 4px;
                    z-index: 10000;
                    opacity: 0;
                    transition: opacity 0.5s ease-in-out;
                }
            </style>
        </head>
        <body>
            <div id="toast">You are already logged in. Redirecting to dashboard...</div>
            <script>
                var toast = document.getElementById("toast");
                toast.style.opacity = 1;
                setTimeout(function() {
                    window.location.href = "main/dashboard.php";
                }, 2000);
            </script>
        </body>
        </html>
        <?php
        exit();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $_SESSION['error'] = "Both fields are required.";
        header("Location: login.php");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

   
    if ($user && password_verify($password, $user['password'])) {
      
        if (!empty($user['login_token']) || $user['logged_in'] != 0) {
            $_SESSION['error'] = "User is already logged in. Please log out first.";
            header("Location: login.php");
            exit();
        }

       
        $loginToken = bin2hex(random_bytes(16));

       
        $stmtUpdate = $pdo->prepare("UPDATE users SET login_token = :token, logged_in = 1 WHERE id = :id");
        $stmtUpdate->execute([
            ':token' => $loginToken,
            ':id'    => $user['id']
        ]);

       
        $stmtLog = $pdo->prepare("INSERT INTO logs (admin_username, login_time, ip_address) VALUES (:username, NOW(), :ip_address)");
        $stmtLog->execute([
            ':username'   => $user['username'],
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);

      
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role']     = $user['role'];
        $_SESSION['login_token']    = $loginToken;
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Login Successful - <?= htmlspecialchars($websiteSettings['site_title'] ?? 'ACCESS POS System') ?></title>
          
            <meta http-equiv="refresh" content="2;url=main/dashboard.php">
            <link rel="icon" href="main/<?= htmlspecialchars($websiteSettings['favicon'] ?? 'favicon.ico') ?>" type="image/x-icon">
            <style>
              
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #120E47;
                    background: url('main/uploads/ws/bg image.jpg') no-repeat center center fixed;
                    background-size: cover;
                    font-family: sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                }
                .overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(18,14,71,0.7);
                    z-index: 1;
                }
                .success-container {
                    position: relative;
                    z-index: 2;
                    background: rgba(255,255,255,0.9);
                    padding: 2rem 3rem;
                    border-radius: 12px;
                    text-align: center;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    overflow: hidden;
                }
                .success-container::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: repeating-linear-gradient(
                        45deg,
                        rgba(42,92,255,0.8),
                        rgba(42,92,255,0.8) 1px,
                        transparent 1px,
                        transparent 10px
                    );
                    pointer-events: none;
                    z-index: -1;
                    animation: moveLines 5s linear infinite, lightningFlicker 3s ease-in-out infinite;
                    filter: drop-shadow(0 0 8px rgba(42,92,255,1));
                }
                @keyframes moveLines {
                    from { background-position: 0 0; }
                    to { background-position: 100% 100%; }
                }
                @keyframes lightningFlicker {
                    0%, 100% { opacity: 0.6; }
                    5% { opacity: 1; }
                    10% { opacity: 0.6; }
                    50% { opacity: 0.6; }
                    55% { opacity: 1; }
                    60% { opacity: 0.6; }
                }
                .branding-logos {
                    display: flex;
                    justify-content: center;
                    gap: 1.5rem;
                    margin-bottom: 1rem;
                }
                .logo-zoom {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    object-fit: cover;
                    padding: 2px;
                    background: white;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                    border: 3px solid #120E47;
                    opacity: 0;
                    animation: zoomInLogo 0.8s ease-out forwards;
                }
                @keyframes zoomInLogo {
                    0% { transform: scale(0.5); opacity: 0; }
                    100% { transform: scale(1); opacity: 1; }
                }
                .pos-icon {
                    font-size: 3rem;
                    color: #120E47;
                    margin-bottom: 1rem;
                    animation: bounce 1s ease-in-out infinite;
                }
                @keyframes bounce {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-10px); }
                }
                h1 {
                    margin: 0 0 0.5rem 0;
                    color: #120E47;
                    font-size: 2rem;
                }
                h2 {
                    margin: 0 0 1rem 0;
                    color: #120E47;
                    font-size: 1.5rem;
                    font-weight: 400;
                }
                p {
                    margin: 0;
                    color: #333;
                    font-size: 1rem;
                }
                .spinner {
                    font-size: 24px;
                    animation: spin 1s linear infinite;
                    color: #120E47;
                }
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            </style>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="overlay"></div>
            <div class="success-container">
                <div class="branding-logos">
                    <?php if (!empty($websiteSettings['organization_logo'])): ?>
                        <img src="main/<?= htmlspecialchars($websiteSettings['organization_logo']) ?>" class="logo-zoom" alt="Organization Logo">
                    <?php endif; ?>
                    <?php if (!empty($websiteSettings['school_logo'])): ?>
                        <img src="main/<?= htmlspecialchars($websiteSettings['school_logo']) ?>" class="logo-zoom" alt="School Logo">
                    <?php endif; ?>
                </div>
                <div class="pos-icon"><i class="fas fa-cash-register"></i></div>
                <h1>Login Successful</h1>
                <h2><?= htmlspecialchars($websiteSettings['site_title'] ?? 'ACCESS POS System') ?></h2>
                <p>Redirecting to dashboard... <i class="fas fa-spinner spinner"></i></p>
            </div>
        </body>
        </html>
        <?php
        exit();
    } else {
        // Invalid credentials.
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: login.php");
        exit();
    }
}

// Retrieve and then clear any error message set in the session.
$errorMessage = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($websiteSettings['meta_description'] ?? 'ACCESS POS System Login') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($websiteSettings['meta_keywords'] ?? 'POS, System, Login, Admin, Point of Sale') ?>">
    <meta name="author" content="ACCESS POS System">
    <meta name="robots" content="index,follow">
    <meta property="og:title" content="<?= htmlspecialchars($websiteSettings['site_title'] ?? 'ACCESS POS System') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($websiteSettings['meta_description'] ?? 'ACCESS Point of Sale System') ?>">
    <title>Login - <?= htmlspecialchars($websiteSettings['site_title'] ?? 'ACCESS POS System') ?></title>
    
    <?php if (!empty($websiteSettings['favicon'])): ?>
        <link rel="icon" href="main/<?= htmlspecialchars($websiteSettings['favicon']) ?>" type="image/x-icon">
    <?php endif; ?>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>
        // Append favicon if needed.
        var link = document.createElement('link');
        link.type = 'image/x-icon';
        link.rel = 'shortcut icon';
        link.href = 'main/<?= htmlspecialchars($websiteSettings['favicon'] ?? 'favicon.ico') ?>';
        document.getElementsByTagName('head')[0].appendChild(link);
    </script>
    
    <style>
        :root {
            --primary-color: #120E47;
            --secondary-color: #2A5CFF;
            --accent-color: #00C9FF;
            --text-color: #2D2D2D;
            --background: #F8F9FF;
            --social-facebook: #3b5998;
        }
        .bg-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('main/uploads/bg image.jpg') no-repeat center center fixed;
            background-size: cover;
            filter: blur(8px);
            z-index: 0;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(18,14,71,0.7);
            z-index: 1;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", system-ui, sans-serif;
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .login-container {
            position: relative;
            z-index: 2;
            background: rgba(255,255,255,0.9);
            padding: 2rem 3rem;
            border-radius: 12px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .login-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(
                45deg,
                rgba(42,92,255,0.8),
                rgba(42,92,255,0.8) 1px,
                transparent 1px,
                transparent 10px
            );
            z-index: 0;
            pointer-events: none;
            animation: moveLines 5s linear infinite, lightningFlicker 3s ease-in-out infinite;
            filter: drop-shadow(0 0 8px rgba(42,92,255,1));
        }
        @keyframes moveLines {
            from { background-position: 0 0; }
            to { background-position: 100% 100%; }
        }
        @keyframes lightningFlicker {
            0%, 100% { opacity: 0.6; }
            5% { opacity: 1; }
            10% { opacity: 0.6; }
            50% { opacity: 0.6; }
            55% { opacity: 1; }
            60% { opacity: 0.6; }
        }
        .branding {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .logos {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            padding: 2px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 3px solid var(--primary-color);
            opacity: 0;
            animation: fadeInLogo 1s ease forwards;
        }
        @keyframes fadeInLogo {
            0% {
                opacity: 0;
                transform: rotate(-10deg) scale(0.9);
            }
            50% {
                opacity: 0.5;
                transform: rotate(10deg) scale(1.05);
            }
            100% {
                opacity: 1;
                transform: rotate(0deg) scale(1);
            }
        }
        h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        h1 .site-title-icon {
            margin-right: 8px;
            vertical-align: middle;
            color: var(--primary-color);
        }
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        .input-field {
            width: 100%;
            padding: 14px 20px 14px 48px;
            border: 5px solid #E0E0E0;
            border-radius: 12px;
            font-size: 15px;
            color: var(--text-color);
            transition: all 0.3s ease;
            background: #ffffff;
        }
        .input-field:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(18,14,71,0.2);
            outline: none;
        }
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 1.1rem;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }
        .btn-login:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
        .error-message {
            background: #FFEBEE;
            color: #D32F2F;
            padding: 14px;
            border-radius: 8px;
            margin: 1rem 0;
            border: 1px solid #FFCDD2;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        .support-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
            font-size: 14px;
        }
        .support-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        .support-link a:hover {
            color: var(--secondary-color);
        }
        .social-links {
            text-align: center;
            margin-top: 2rem;
        }
        .social-links a {
            font-size: 1.5rem;
            color: var(--social-facebook);
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .social-links a:hover {
            transform: scale(1.1);
        }
        .contact-us {
            text-align: center;
            margin-top: 1rem;
            font-size: 1.3rem;
        }
        .contact-us a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.3s ease;
        }
        .contact-us a:hover {
            transform: scale(1.1);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            z-index: 999;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        #checking {
            display: none;
            text-align: center;
            margin-top: 1rem;
            color: var(--primary-color);
            font-size: 15px;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            .logo {
                width: 100px;
                height: 100px;
            }
            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="bg-image"></div>
    <div class="overlay"></div>
    <div class="login-container">
        <div class="branding">
            <div class="logos">
                <?php if (!empty($websiteSettings['organization_logo'])): ?>
                    <img src="main/<?= htmlspecialchars($websiteSettings['organization_logo']) ?>" class="logo" alt="Organization Logo">
                <?php endif; ?>
                <?php if (!empty($websiteSettings['school_logo'])): ?>
                    <img src="main/<?= htmlspecialchars($websiteSettings['school_logo']) ?>" class="logo" alt="School Logo">
                <?php endif; ?>
            </div>
            <h1><i class="fas fa-cash-register site-title-icon"></i><?= htmlspecialchars($websiteSettings['site_title'] ?? 'POS PRO') ?></h1>
        </div>

        <form id="loginForm" method="POST">
            <div class="form-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" class="input-field" placeholder="Username" required>
            </div>
            
            <div class="form-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="password" class="input-field" placeholder="Password" required>
                <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
            </div>

            <?php if ($errorMessage): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-login">
                Sign In <i class="fas fa-arrow-right"></i>
            </button>
            
            <div id="checking">
                Checking credentials... <i class="fas fa-spinner fa-spin"></i>
            </div>
        </form>

        <div class="support-link">
            <a href="#" onclick="showModal()">
                <i class="fas fa-question-circle"></i> Request Access
            </a>
        </div>
        
        <div class="social-links">
            <a href="https://www.facebook.com/profile.php?id=61567622242053" target="_blank">
                <i class="fab fa-facebook-square"></i> Like us on Facebook
            </a>
        </div>
        
        <div class="contact-us">
            <a href="mailto:support@accessmanagementsystem.online" title="Contact Us">
                <i class="fas fa-envelope"></i> Contact Us
            </a>
        </div>
    </div>

    <div id="modal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 1rem; color: var(--primary-color);">
                <i class="fas fa-shield-alt"></i> Admin Access Required
            </h3>
            <p style="margin-bottom: 1.5rem; color: #666;">
                Please contact your system administrator for account creation and access permissions.
            </p>
            <p style="margin-bottom: 1.5rem;">
                Alternatively, reach out to:
                <br>
                <a href="https://www.facebook.com/bossjackneverdmfirst/" target="_blank" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                    Jack
                </a>
                <span style="margin: 0 10px;">|</span>
                <a href="https://www.facebook.com/emmanuel.laurente.986" target="_blank" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                    Emman
                </a>
                <span style="margin: 0 10px;">|</span>
                <a href="https://www.facebook.com/klein.reveche" target="_blank" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                    Klein 
                </a>
            </p>
            <button class="btn-login" onclick="hideModal()" style="max-width: 120px; margin: 0 auto;">
                Close
            </button>
        </div>
    </div>

    <script>
       
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.querySelector('.password-toggle');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

      
        function showModal() {
            document.getElementById('modal').style.display = 'flex';
        }

        function hideModal() {
            document.getElementById('modal').style.display = 'none';
        }

       
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            document.querySelector('.btn-login').disabled = true;
            document.querySelector('.btn-login').style.display = 'none';
            document.getElementById('checking').style.display = 'block';
        });

       
        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            if (event.target === modal) hideModal();
        }
    </script>
</body>
</html>
