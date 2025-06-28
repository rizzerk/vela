<?php
session_start();
require_once '../connection.php';

$tenant_id = $_SESSION['user_id'] ?? 1;
$pageNotifications = [];
$sort = $_GET['sort'] ?? 'newest';
$order = $sort === 'newest' ? 'DESC' : 'ASC';

try {
    $query = "SELECT 'bill' as type, CONCAT('Bill: ₱', amount, ' - ', description) as message, 
                     due_date as date, 'medium' as priority
              FROM BILL b 
              WHERE b.status IN ('unpaid', 'overdue')
              ORDER BY due_date $order LIMIT 10";
    
    $result = $conn->query($query);
    if ($result) {
        $pageNotifications = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Notifications error: " . $e->getMessage());
}

// Fetch notifications for navbar dropdown
$notifications = [];
try {
    $navQuery = "SELECT 'bill' as type, CONCAT('Bill: ₱', amount, ' - ', description) as message, 
                        due_date as date, 'medium' as priority
                 FROM BILL b 
                 WHERE b.status IN ('unpaid', 'overdue')
                 ORDER BY due_date DESC LIMIT 5";
    
    $navResult = $conn->query($navQuery);
    if ($navResult) {
        $notifications = $navResult->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Navbar notifications error: " . $e->getMessage());
}

// Get user's full name for navbar
$fullName = $_SESSION['name'] ?? 'User';

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
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
            padding-top: 80px;
        }

        .main-content {
            margin: 0 auto;
            padding: 2rem;
            max-width: 1000px;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
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

        .sort-controls {
            max-width: 800px;
            margin: 0 auto 1rem auto;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .sort-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #deecfb;
            background: white;
            color: #1666ba;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sort-btn:hover {
            background: #deecfb;
        }

        .sort-btn.active {
            background: #1666ba;
            color: white;
            border-color: #1666ba;
        }

        .notifications-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }

        .notification {
            padding: 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .notification:last-child {
            border-bottom: none;
        }

        .notification-icon {
            font-size: 1.5rem;
            color: #1666ba;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #deecfb;
            border-radius: 8px;
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
    <?php include '../includes/navbar/tenant-navbar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Notifications</h1>
        </div>

        <div class="sort-controls">
            <a href="?sort=newest" class="sort-btn <?= $sort === 'newest' ? 'active' : '' ?>">Newest First</a>
            <a href="?sort=oldest" class="sort-btn <?= $sort === 'oldest' ? 'active' : '' ?>">Oldest First</a>
        </div>

        <div class="notifications-container">
            <?php if (empty($pageNotifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell"></i>
                    <p>No notifications yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($pageNotifications as $notification): ?>
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