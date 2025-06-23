<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'tenant') {
    header('Location: ../index.php');
    exit();
}

$userName = $_SESSION['name'] ?? 'Tenant';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Dashboard - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #deecfb 100%);
            color: #000000;
            line-height: 1.7;
            min-height: 100vh;
            padding-top: 80px;
        }



        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-title {
            font-size: 2.8rem;
            color: #1666ba;
            font-weight: 800;
            margin-bottom: 0.8rem;
            letter-spacing: -0.02em;
            text-align: center;
            margin-top: 2rem;
            padding: 2rem;
        }

        .notice-section {
            background: linear-gradient(135deg, #1666ba 0%, #368ce7 100%);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 4rem;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.2);
        }

        .notice-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
        }

        .notice-content {
            position: relative;
            z-index: 2;
        }

        .notice-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notice-text {
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.95;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }

        .dashboard-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 3rem 1.5rem;
            box-shadow: 0 2px 8px rgba(22, 102, 186, 0.06);
            border: 1px solid #deecfb;
            transition: all 0.3s ease;
            text-align: center;
            cursor: pointer;
            position: relative;
        }

        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(22, 102, 186, 0.12);
            border-color: #bedaf7;
        }

        .card-icon {
            width: 60px;
            height: 60px;
            background: #deecfb;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 1.5rem;
            color: #1666ba;
            transition: all 0.3s ease;
        }

        .dashboard-card:hover .card-icon {
            background: #1666ba;
            color: #ffffff;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1666ba;
            margin: 0;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .notice-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar/tenant-navbar.php'?>
    <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>

    <div class="content-wrapper">
        <div class="notice-section">
            <div class="notice-content">
                <h2 class="notice-title">
                    <i class="fas fa-bell"></i>
                    Important Notice
                </h2>
                <p class="notice-text">
                    Your monthly rent payment is due on the 5th of each month. Please ensure timely payment to avoid late fees. 
                    For any maintenance requests or concerns, use the dashboard below or contact our support team.
                </p>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card" onclick="viewDues()">
                <div class="card-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3 class="card-title">View Dues</h3>
            </div>

            <div class="dashboard-card" onclick="payDues()">
                <div class="card-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h3 class="card-title">Pay Dues</h3>
            </div>

            <div class="dashboard-card" onclick="maintenanceRequest()">
                <div class="card-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <h3 class="card-title">Maintenance Request</h3>
            </div>

            <div class="dashboard-card" onclick="paymentHistory()">
                <div class="card-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3 class="card-title">Payment History</h3>
            </div>
        </div>
    </div>

    <script>
        function viewDues() {
            window.location.href = 'view-dues.php';
        }

        function payDues() {
            window.location.href = 'pay-dues.php';
        }

        function maintenanceRequest() {
            window.location.href = 'maintenance-request.php';
        }

        function paymentHistory() {
            window.location.href = 'payment-history.php';
        }

    </script>
</body>
</html>