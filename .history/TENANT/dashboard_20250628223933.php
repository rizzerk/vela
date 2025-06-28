<?php
session_start();
require_once "../connection.php";
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'tenant') {
    header('Location: ../index.php');
    exit();
}

$userName = $_SESSION['name'] ?? 'Tenant';
$userId = $_SESSION['user_id'];

$leaseQuery = "SELECT l.lease_id, l.property_id, p.title, p.address 
               FROM LEASE l 
               JOIN PROPERTY p ON l.property_id = p.property_id 
               WHERE l.tenant_id = ? AND l.active = 1";
$leaseStmt = $conn->prepare($leaseQuery);
$leaseStmt->bind_param("i", $userId);
$leaseStmt->execute();
$leaseResult = $leaseStmt->get_result();
$lease = $leaseResult->fetch_assoc();

$bills = [];
$debug_info = "User ID: $userId, ";

if ($lease) {
    $debug_info .= "Lease ID: {$lease['lease_id']}, ";
    $billQuery = "SELECT bill_id, amount, due_date, status, description 
                  FROM BILL 
                  WHERE lease_id = ? 
                  ORDER BY due_date DESC LIMIT 1";
    $billStmt = $conn->prepare($billQuery);
    $billStmt->bind_param("i", $lease['lease_id']);
    $billStmt->execute();
    $billResult = $billStmt->get_result();
    
    while ($row = $billResult->fetch_assoc()) {
        $bills[] = $row;
    }
    $debug_info .= "Bills found: " . count($bills);
} else {
    $debug_info .= "No active lease found";
}

// Don't close connection yet - navbar needs it
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
            background: #ffffff;
            color: #000000;
            line-height: 1.5;
            min-height: 100vh;
            padding-top: 80px;
        }

        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .bills-section {
            background: #ffffff;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #deecfb;
            margin-bottom: 1.5rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            color: #1666ba;
            font-weight: 700;
        }

        .welcome-text {
            font-size: 1.1rem;
            color: #666;
        }

        .bill-item {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            border: 1px solid #deecfb;
        }

        .bill-item.overdue {
            background: #ffffff;
            border-color: #dc2626;
        }

        .bill-item.unpaid {
            background: #ffffff;
            border-color: #1666ba;
        }

        .bill-item.paid {
            background: #ffffff;
            border-color: #16a34a;
        }

        .bill-amount {
            font-size: 1rem;
            font-weight: 600;
            color: #1666ba;
        }

        .bill-due {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.2rem;
        }

        .bill-status {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
            margin-top: 0.3rem;
        }

        .status-overdue {
            background: #dc2626;
            color: white;
        }

        .status-unpaid {
            background: #1666ba;
            color: white;
        }

        .status-paid {
            background: #16a34a;
            color: white;
        }

        .notice-section {
            background: #1666ba;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            color: #ffffff;
        }

        .notice-content {
            position: relative;
            z-index: 2;
        }

        .notice-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .notice-text {
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.95;
        }

        .actions-section {
            background: #ffffff;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #deecfb;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .action-card {
            background: #1666ba;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #1666ba;
            cursor: pointer;
            transition: background-color 0.2s ease;
            text-align: center;
            color: #ffffff;
        }

        .action-card:hover {
            background: #368ce7;
        }

        .action-icon {
            font-size: 2rem;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .action-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .action-desc {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.4;
        }

        .no-bills {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .bills-section, .actions-section {
                padding: 1rem;
            }

            .notice-section {
                padding: 1.5rem;
            }

            .actions-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }

            .action-card {
                padding: 1rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 120px;
            }

            .action-icon {
                font-size: 1.5rem;
                margin-bottom: 0.5rem;
            }

            .action-title {
                font-size: 0.8rem;
                margin-bottom: 0.25rem;
            }

            .action-desc {
                font-size: 0.7rem;
                display: none;
            }

            .section-title {
                font-size: 1.1rem;
            }

            .welcome-text {
                font-size: 0.85rem;
            }

            .bill-item {
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }

            .bill-amount {
                font-size: 1rem;
            }

            .bill-due {
                font-size: 0.8rem;
            }

            .bill-status {
                font-size: 0.7rem;
                padding: 0.2rem 0.6rem;
            }

            .notice-title {
                font-size: 1.1rem;
                margin-bottom: 0.5rem;
            }

            .notice-text {
                font-size: 0.8rem;
                line-height: 1.4;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar/tenant-navbar.php'?>
    <?php $conn->close(); ?>

    <div class="content-wrapper">
        <div class="bills-section">
            <div class="section-header">
                <h2 class="section-title">Payment Status</h2>
                <div class="welcome-text">Welcome back, <?php echo htmlspecialchars($userName); ?>!</div>
            </div>
            <!-- Debug: <?= $debug_info ?> -->
            <?php if (empty($bills)): ?>
                <div class="no-bills">No bills found</div>
            <?php else: ?>
                <?php foreach ($bills as $bill): ?>
                    <div class="bill-item <?php echo $bill['status']; ?>">
                        <div class="bill-amount">₱<?php echo number_format($bill['amount'], 2); ?></div>
                        <div class="bill-due">Due: <?php echo date('M d, Y', strtotime($bill['due_date'])); ?></div>
                        <div class="bill-status status-<?php echo $bill['status']; ?>">
                            <?php echo ucfirst($bill['status']); ?>
                        </div>
                        <?php if ($bill['description']): ?>
                            <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                                <?php echo htmlspecialchars($bill['description']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="notice-section">
            <div class="notice-content">
                <h2 class="notice-title">Important Notice</h2>
                <p class="notice-text">
                    Your monthly rent payment is due on the 5th of each month. Please ensure timely payment to avoid late fees. 
                    For any maintenance requests or concerns, use the button below or contact our support team.
                </p>
            </div>
        </div>

        <div class="actions-section">
            <div class="actions-grid">
                <div class="action-card" onclick="maintenanceRequest()">
                    <div class="action-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="action-title">Maintenance Request</div>
                </div>
                <div class="action-card" onclick="viewPaymentHistory()">
                    <div class="action-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="action-title">Payment History</div>
                </div>
                <div class="action-card" onclick="viewLease()">
                    <div class="action-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="action-title">Lease Details</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function maintenanceRequest() {
            window.location.href = 'maintenance-request.php';
        }
        
        function viewPaymentHistory() {
            window.location.href = 'payment-history.php';
        }
        
        function viewLease() {
            window.location.href = 'lease-details.php';
        }

        function toggleNotifications(event) {
            event.preventDefault();
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('show');
        }

        document.addEventListener('click', function(event) {
            const notificationIcon = document.querySelector('.notification-icon');
            const dropdown = document.getElementById('notificationDropdown');
            
            if (notificationIcon && !notificationIcon.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>