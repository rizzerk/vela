<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-container {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
        }

        .error-icon {
            font-size: 3rem;
            color: #ff4444;
            margin-bottom: 1rem;
        }

        .error-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .error-message {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .btn {
            background: #368ce7;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #1666ba;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <i class="fas fa-exclamation-triangle error-icon"></i>
        <h1 class="error-title">Access Denied</h1>
        <p class="error-message">
            You don't have permission to access this page.
        </p>
        
        <div class="action-buttons">
            <?php
            $dashboard_url = 'index.php';
            if (isset($_SESSION['role'])) {
                switch ($_SESSION['role']) {
                    case 'tenant':
                        $dashboard_url = 'TENANT/dashboard.php';
                        break;
                    case 'landlord':
                        $dashboard_url = 'LANDLORD/dashboard.php';
                        break;
                }
            }
            ?>
            <a href="<?php echo $dashboard_url; ?>" class="btn">
                <i class="fas fa-arrow-left"></i> Go to Dashboard
            </a>
        </div>
    </div>
</body>
</html>