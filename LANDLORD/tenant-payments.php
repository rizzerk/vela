<?php
session_start();
require_once '../connection.php';

$landlord_id = $_SESSION['user_id'] ?? 1;

$query = "
    SELECT 
        u.name AS tenant_name,
        p.submitted_at,
        p.status,
        p.proof_of_payment,
        p.amount_paid,
        p.payment_id
    FROM PAYMENT p
    JOIN BILL b ON p.bill_id = b.bill_id
    JOIN LEASE l ON b.lease_id = l.lease_id
    JOIN USERS u ON l.tenant_id = u.user_id
    JOIN PROPERTY prop ON l.property_id = prop.property_id
    WHERE prop.property_id IN (
        SELECT property_id FROM PROPERTY WHERE property_id = prop.property_id
    )
    ORDER BY p.submitted_at DESC
";

$result = $conn->query($query);
$payments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f6f6f6;
            margin: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        h1 {
            font-size: 2rem;
            color: #1666ba;
            margin-bottom: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(22, 102, 186, 0.1);
        }

        th, td {
            padding: 1rem;
            text-align: left;
            font-size: 0.95rem;
        }

        th {
            background-color: #eef4fb;
            color: #1666ba;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }

        .pending { background-color: #fcd34d; color: #92400e; }
        .verified { background-color: #4ade80; color: #065f46; }
        .rejected { background-color: #f87171; color: #7f1d1d; }

        .download-icon {
            color: #1666ba;
            font-size: 1rem;
        }

        .no-payments {
            padding: 2rem;
            text-align: center;
            color: #64748b;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <?php include ('../includes/navbar/landlord-sidebar.html'); ?>

    <div class="main-content">
        <h1>Payments</h1>

        <?php if (empty($payments)): ?>
            <div class="no-payments">
                <i class="fas fa-file-invoice-dollar" style="font-size:2rem;"></i>
                <p>No payment records found.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Proof of Payment</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['tenant_name']) ?></td>
                            <td><?= date('M d, Y H:i', strtotime($payment['submitted_at'])) ?></td>
                            <td>
                                <span class="status <?= strtolower($payment['status']) ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($payment['proof_of_payment'])): ?>
                                    <a href="<?= htmlspecialchars($payment['proof_of_payment']) ?>" target="_blank" download>
                                        <i class="fas fa-download download-icon"></i>
                                    </a>
                                <?php else: ?>
                                    <em style="color:#94a3b8;">No file</em>
                                <?php endif; ?>
                            </td>
                            <td>₱<?= number_format($payment['amount_paid'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
