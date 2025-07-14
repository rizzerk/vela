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

        .status-dropdown {
            padding: 0.4rem 0.6rem;
            border-radius: 6px;
            font-size: 0.9rem;
            border: 1px solid #ccc;
            background-color: #fff;
            color: #333;
        }

        .download-icon {
            color: #1666ba;
            font-size: 1rem;
        }

        .no-record {
            text-align: center;
            color: #999;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <?php include ('../includes/navbar/landlord-sidebar.html'); ?>

    <div class="main-content">
        <h1>Payments</h1>

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
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="5" class="no-record">
                            <i class="fas fa-file-invoice-dollar" style="font-size:1.5rem;"></i><br>
                            No payment records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['tenant_name']) ?></td>
                            <td><?= date('M d, Y H:i', strtotime($payment['submitted_at'])) ?></td>
                            <td>
                                <select class="status-dropdown" onchange="updatePaymentStatus(<?= $payment['payment_id'] ?>, this.value)">
                                    <option value="pending" <?= $payment['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $payment['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="rejected" <?= $payment['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </td>
                            <td>
                                <?php if (!empty($payment['proof_of_payment'])): ?>
                                    <a href="<?= htmlspecialchars($payment['proof_of_payment']) ?>" target="_blank" download>
                                        <i class="fas fa-download download-icon"></i>
                                    </a>
                                <?php else: ?>
                                    <em style="color:#aaa;">No file</em>
                                <?php endif; ?>
                            </td>
                            <td>₱<?= number_format($payment['amount_paid'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function updatePaymentStatus(paymentId, newStatus) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "update-payment-status.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status !== 200) {
                    alert("Failed to update payment status.");
                }
            };
            xhr.send("payment_id=" + paymentId + "&status=" + newStatus);
        }
    </script>
</body>
</html>
