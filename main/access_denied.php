<?php


// If we get here, maintenance mode is off but user has no access
http_response_code(403);
$stmt = $pdo->query("SELECT favicon FROM website_settings WHERE id = 1");

$favicon = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="<?php echo $favicon; ?>">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .container {
            margin-top: 100px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-out;
        }
        .card-header {
            background-color: #2b576d;
            color: white;
            text-align: center;
        }
        .card-body {
            text-align: center;
        }
        .card-footer {
            background-color: #2b576d;
            color: white;
            font-size: 14px;
            text-align: center;
        }
        .alert {
            font-size: 16px;
        }
        .alert-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: #2b576d;
            border-color: #2b576d;
        }
        .btn-primary:hover {
            background-color: #1f4652;
            border-color: #1f4652;
        }
        /* Fade-in animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        .logos {
            margin-top: 20px;
        }
        .logos img {
            width: 100px;
            height: 100px;
            margin: 0 20px;
            border-radius: 50%;
            border: 3px solid #2b576d;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-header">
            <h3>Access Control</h3>
        </div>
        <div class="card-body">
            <!-- Message displayed based on session status -->
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle alert-icon"></i>
                <div>
                    <strong>Access Denied!</strong> You are not authorized to view this page.
                </div>
            </div>
            <p>You will be redirected shortly.</p>
            <p>If the redirect doesn't happen, you can click <a href="/" class="btn btn-primary">here</a> to go back to the homepage.</p>
        </div>
        <div class="card-footer">
            <p>&copy; 2025 AccessManagementSystem. All rights reserved.</p>
        </div>

        <!-- Logos section inside the card -->
        <div class="logos text-center">
            <img src="../img/schoollogo.jpg" alt="Trace College">
            <img src="../img/organizationlogo.jpg" alt="ACCESS">
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>