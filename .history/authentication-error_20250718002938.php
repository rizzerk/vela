<?php
session_start();

$redirectUrl = 'index.php'; 

if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'tenant':
            $redirectUrl = 'TENANT/dashboard.php';
            break;
        case 'landlord':
            $redirectUrl = 'LANDLORD/dashboard.php';
            break;
        default:
            $redirectUrl = 'index.php';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .error-container {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            border: 1px solid #ddd;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .error-icon {
            font-size: 3rem;
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .error-message {
            font-size: 1rem;
            color: #666;
            margin-bottom: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #1666ba;
            color: white;
        }

        .btn-primary:hover {
            background: #0f4d87;
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
        <a href="<?= $redirectUrl ?>" class="btn btn-primary">Go to Dashboard</a>
    </div>
</body>
</html>