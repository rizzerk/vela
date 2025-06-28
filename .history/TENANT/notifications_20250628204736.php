<?php
session_start();
require_once '../connection.php';

$tenant_id = $_SESSION['user_id'] ?? 1;

// Fetch bills
$bills_query = "SELECT 'bill' as type, CONCAT('Bill due: ₱', amount, ' - ', description) as message, due_date as date, 'medium' as priority
                FROM BILL b JOIN LEASE l ON b.lease_id = l.lease_id
                WHERE l.tenant_id = ? AND b.status IN ('unpaid', 'overdue')";
$stmt = $conn->prepare($bills_query);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch payments
$payments_query = "SELECT 'payment' as type, CONCAT('Payment ', status, ': ₱', amount_paid) as message, submitted_at as date, 'low' as priority
                   FROM PAYMENT p JOIN BILL b ON p.bill_id = b.bill_id JOIN LEASE l ON b.lease_id = l.lease_id
                   WHERE l.tenant_id = ? AND p.status IN ('pending', 'verified', 'rejected')";
$stmt = $conn->prepare($payments_query);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch maintenance
$maintenance_query = "SELECT 'maintenance' as type, CONCAT('Maintenance request ', status, ': ', description) as message, updated_at as date, 'high' as priority
                      FROM MAINTENANCE_REQUEST mr JOIN LEASE l ON mr.lease_id = l.lease_id
                      WHERE l.tenant_id = ?";
$stmt = $conn->prepare($maintenance_query);
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$maintenance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Combine all notifications
$notifications = array_merge($bills, $payments, $maintenance);

// Sort by date
usort($notifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Limit to 20
$notifications = array_slice($notifications, 0, 20);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f6f6f6;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .header {
            text-align: left;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            color: #1666ba;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .notifications-container {
            max-width: 800px;
        }

        .notification {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 16px rgba(22, 102, 186, 0.1);
            border-left: 4px solid #bedaf7;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notification.high {
            border-left-color: #ef4444;
        }

        .notification.medium {
            border-left-color: #f59e0b;
        }

        .notification.low {
            border-left-color: #10b981;
        }

        .notification-icon {
            font-size: 1.5rem;
            color: #1666ba;
            width: 40px;
            text-align: center;
        }

        .notification.high .notification-icon {
            color: #ef4444;
        }

        .notification.medium .notification-icon {
            color: #f59e0b;
        }

        .notification.low .notification-icon {
            color: #10b981;
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            font-size: 1rem;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .notification-date {
            font-size: 0.85rem;
            color: #64748b;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #bedaf7;
        }

        .empty-state p {
            font-size: 1rem;
            color: #64748b;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar placeholder -->

    <div class="main-content">
        <div class="header">
            <h1>Notifications</h1>
        </div>

        <div class="notifications-container">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell"></i>
                    <p>No notifications yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification <?= $notification['priority'] ?>">
                        <div class="notification-icon">
                            <?php if ($notification['type'] === 'bill'): ?>
                                <i class="fas fa-file-invoice-dollar"></i>
                            <?php elseif ($notification['type'] === 'payment'): ?>
                                <i class="fas fa-credit-card"></i>
                            <?php else: ?>
                                <i class="fas fa-tools"></i>
                            <?php endif; ?>
                        </div>
                        <div class="notification-content">
                            <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                            <div class="notification-date"><?= date('F j, Y g:i A', strtotime($notification['date'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>