<?php
session_start();
require_once "../connection.php";

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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #deecfb 100%);
            padding-top: 80px;
            color: #000000;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
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

        .nav-links {
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
            padding: 0 1rem;
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
            .container {
                padding: 1rem;
            }

            h1.title {
                font-size: 2rem;
            }

            table {
                font-size: 0.85rem;
                min-width: 100%;
            }

            .nav-links {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
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

        <div class="nav-links">
            <a href="view-dues.php"><i class="fas fa-arrow-left"></i> View Dues</a>
            <a href="pay-dues.php">Pay Dues <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</body>
</html>
