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
    <title>Payment History - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #deecfb 100%);
            margin: 0;
            padding-top: 80px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        h1.title {
            font-size: 2.5rem;
            color: #1666ba;
            font-weight: 800;
            text-align: center;
            margin-bottom: 2rem;
        }

        .table-wrapper {
            overflow-x: auto;
            background: #ffffff;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            border: 1px solid #deecfb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: #f0f6fd;
            font-weight: 600;
            color: #1666ba;
        }

        td:last-child {
            text-align: right;
        }

        .nav-links {
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-links a {
            color: #1666ba;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #104e91;
        }

        @media (max-width: 768px) {
            table {
                font-size: 0.9rem;
            }
            h1.title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar/tenant-navbar.php'; ?>

    <div class="container">
        <h1 class="title">Payment History</h1>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Transactions</th>
                        <th>Date</th>
                        <th>Transaction #</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Placeholder sample rows -->
                    <tr>
                        <td>Monthly Rent</td>
                        <td>2025-06-05</td>
                        <td>TRX-20250605</td>
                        <td style="text-align:right;">₱5,000.00</td>
                    </tr>
                    <tr>
                        <td>Late Fee</td>
                        <td>2025-06-07</td>
                        <td>TRX-20250607</td>
                        <td style="text-align:right;">₱300.00</td>
                    </tr>
                    <!-- Backend data will go here -->
                </tbody>
            </table>
        </div>

        <div class="nav-links">
            <a href="view-dues.php"><i class="fas fa-arrow-left"></i> View Dues</a>
            <a href="pay-dues.php">Pay Dues <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</body>
</html>
