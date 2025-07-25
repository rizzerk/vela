<?php
session_start();
require_once '../connection.php';

$tenant_id = $_SESSION['user_id'] ?? 1;

$notifications_query = "
    SELECT 'bill' as type, CONCAT('Bill due: ₱', amount, ' - ', description) as message, due_date as date, 
           CASE WHEN status = 'overdue' THEN 'high' ELSE 'medium' END as priority
    FROM BILL b
    JOIN LEASE l ON b.lease_id = l.lease_id
    WHERE l.tenant_id = ? AND b.status IN ('unpaid', 'overdue')
    
    UNION ALL
    
    SELECT 'payment' as type, CONCAT('Payment ', status, ': ₱', amount_paid) as message, submitted_at as date, 'low' as priority
    FROM PAYMENT p
    JOIN BILL b ON p.bill_id = b.bill_id
    JOIN LEASE l ON b.lease_id = l.lease_id
    WHERE l.tenant_id = ? AND p.status IN ('pending', 'verified', 'rejected')
    
    UNION ALL
    
    SELECT 'maintenance' as type, CONCAT('Maintenance request ', status, ': ', LEFT(description, 50), '...') as message, updated_at as date,
           CASE WHEN status = 'resolved' THEN 'low' WHEN status = 'in_progress' THEN 'medium' ELSE 'high' END as priority
    FROM MAINTENANCE_REQUEST mr
    JOIN LEASE l ON mr.lease_id = l.lease_id
    WHERE l.tenant_id = ?
    
    ORDER BY date DESC
    LIMIT 20
";

$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("iii", $tenant_id, $tenant_id, $tenant_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
    <?php include ('../includes/navbar/tenant-sidebar.html'); ?>

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