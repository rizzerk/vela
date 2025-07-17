<?php
session_start();
require_once "../connection.php";
require_once "../includes/auth/tenant_auth.php";
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'tenant') {
    header('Location: ../index.php');
    exit();
}
$userName = $_SESSION['name'] ?? 'Tenant';
$tenantId = $_SESSION['user_id'] ?? null;
$paymentResults = null;
if ($tenantId) {
    $query = "SELECT 
                p.amount_paid,
                p.reference_num,
                p.submitted_at AS date_paid,
                p.status,
                b.description AS transaction_type
              FROM PAYMENT p
              JOIN BILL b ON p.bill_id = b.bill_id
              JOIN LEASE l ON b.lease_id = l.lease_id
              WHERE l.tenant_id = ?
              ORDER BY p.submitted_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tenantId);
    $stmt->execute();
    $paymentResults = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment History - VELA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #deecfb 100%);
            padding-top: 80px;
            color: #000000;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 2rem 120px; /* Added bottom padding for navigation arrows */
        }
        h1.title {
            font-size: 2.8rem;
            color: #1666ba;
            font-weight: 800;
            text-align: center;
            margin-bottom: 2rem;
        }
        .table-wrapper {
            background: #ffffff;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(22, 102, 186, 0.06);
            border: 1px solid #deecfb;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }
        th, td {
            padding: 1rem;
            text-align: left;
        }
        th {
            background-color: #f0f6fd;
            color: #1666ba;
            font-size: 1rem;
            font-weight: 600;
        }
        td {
            background-color: #f9f9f9;
            font-size: 0.95rem;
        }
        td:last-child,
        th:last-child {
            text-align: right;
        }
        
        /* Navigation Arrows - Same styling as view dues page */
        .navigation-arrows {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            z-index: 100;
            background-color: #1666ba;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }

        .nav-group {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
        }

        .nav-text {
            color: white;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s;
        }

        .arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            color: #1666ba;
            font-size: 22px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .arrow:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            background: #f0f0f0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem 1rem 110px; /* Adjusted bottom padding for mobile */
            }
            h1.title {
                font-size: 2rem;
            }
            table {
                font-size: 0.85rem;
                min-width: 100%;
            }
            
            .navigation-arrows {
                bottom: 10px;
                padding: 0 10px;
            }

            .arrow {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .nav-group {
                gap: 8px;
            }

            .nav-text {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 1rem 1rem 100px; /* Further adjusted for small screens */
            }
            
            .navigation-arrows {
                bottom: 10px;
                padding: 0 10px;
            }

            .nav-text {
                font-size: 12px;
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
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($paymentResults && $paymentResults->num_rows > 0): ?>
                        <?php while ($row = $paymentResults->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['transaction_type']) ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d', strtotime($row['date_paid']))) ?></td>
                                <td><?= htmlspecialchars($row['reference_num']) ?></td>
                                <td>₱<?= number_format($row['amount_paid'], 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">No payment history found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Navigation Arrows -->
        <div class="navigation-arrows">
            <div class="nav-group" onclick="window.location.href='view-dues.php'">
                <a href="pay-dues.php" class="arrow"><i class="fas fa-arrow-left"></i></a>
                <p class="nav-text">Pay Dues</p>
            </div>
            <div class="nav-group" onclick="window.location.href='view-dues.php'">
                <p class="nav-text">View Dues</p>
                <a href="view-dues.php" class="arrow"><i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</body>
</html>