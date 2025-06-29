<?php
session_start();
require_once '../connection.php';

$tenant_id = $_SESSION['user_id'] ?? 1;
$notifications = [];

try {
    // Simple query to test - fetch bills only
    $query = "SELECT 'bill' as type, CONCAT('Bill: ₱', amount, ' - ', description) as message, 
                     due_date as date, 'medium' as priority
              FROM BILL b 
              WHERE b.status IN ('unpaid', 'overdue')
              ORDER BY due_date DESC LIMIT 10";
    
    $result = $conn->query($query);
    if ($result) {
        $notifications = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Keep notifications empty if there's an error
    error_log("Notifications error: " . $e->getMessage());
}

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
            background: linear-gradient(135deg, #deecfb 0%, #f6f6f6 100%);
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 0;
            padding: 2rem;
        }

        .header {
            text-align: left;
            margin-bottom: 3rem;
            position: relative;
        }

        .header h1 {
            font-size: 2.8rem;
            background: linear-gradient(135deg, #1666ba, #368ce7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        
        .header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #1666ba, #7ab3ef);
            border-radius: 2px;
        }

        .notifications-container {
            max-width: 800px;
        }

        .notification {
            background: white;
            border-radius: 16px;
            padding: 1.8rem;
            margin-bottom: 1.2rem;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.12);
            border-left: 5px solid #bedaf7;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .notification::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #bedaf7, transparent);
        }
        
        .notification:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(22, 102, 186, 0.18);
        }

        .notification.high {
            border-left-color: #ef4444;
            background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
        }
        
        .notification.high::before {
            background: linear-gradient(90deg, transparent, #ef4444, transparent);
        }

        .notification.medium {
            border-left-color: #f59e0b;
            background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
        }
        
        .notification.medium::before {
            background: linear-gradient(90deg, transparent, #f59e0b, transparent);
        }

        .notification.low {
            border-left-color: #10b981;
            background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        }
        
        .notification.low::before {
            background: linear-gradient(90deg, transparent, #10b981, transparent);
        }

        .notification-icon {
            font-size: 1.8rem;
            color: #1666ba;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #deecfb, #bedaf7);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(22, 102, 186, 0.15);
        }

        .notification.high .notification-icon {
            color: #ef4444;
            background: linear-gradient(135deg, #fef2f2, #fecaca);
        }

        .notification.medium .notification-icon {
            color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb, #fed7aa);
        }

        .notification.low .notification-icon {
            color: #10b981;
            background: linear-gradient(135deg, #f0fdf4, #bbf7d0);
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
            font-weight: 500;
            line-height: 1.4;
        }

        .notification-date {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .notification-date::before {
            content: '\f017';
            font-family: 'Font Awesome 6 Free';
            font-weight: 400;
            font-size: 0.8rem;
            color: #7ab3ef;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            color: #64748b;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.08);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #bedaf7, #7ab3ef);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: pulse 2s infinite;
        }

        .empty-state p {
            font-size: 1.1rem;
            color: #64748b;
            font-weight: 500;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2.2rem;
            }
            
            .notification {
                padding: 1.5rem;
                gap: 1rem;
            }
            
            .notification-icon {
                width: 45px;
                height: 45px;
                font-size: 1.6rem;
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