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
            background: linear-gradient(135deg, #ffffff 0%, #deecfb 50%, #bedaf7 100%);
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
            padding-top: 90px;
        }

        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem;
        }

        .bills-section {
            background: linear-gradient(135deg, #ffffff 0%, #deecfb 100%);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
            border: 1px solid rgba(222, 236, 251, 0.5);
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 1.75rem;
            color: #1666ba;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .welcome-text {
            font-size: 1rem;
            color: #64748b;
            font-weight: 500;
        }

        .bill-item {
            padding: 0;
            border-radius: 16px;
            margin-bottom: 0;
            background: #ffffff;
            border: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2rem;
        }

        .bill-item.overdue {
            background: #ffffff;
        }

        .bill-item.unpaid {
            background: #ffffff;
        }

        .bill-item.paid {
            background: #ffffff;
        }

        .bill-info {
            flex: 1;
        }

        .bill-amount {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1666ba, #368ce7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .bill-due {
            font-size: 1rem;
            color: #64748b;
            font-weight: 500;
            margin: 0;
        }

        .bill-status {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: capitalize;
            letter-spacing: 0.025em;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .status-overdue {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .status-unpaid {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .status-paid {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }

        .notice-section {
            background: linear-gradient(135deg, #1666ba 0%, #368ce7 100%);
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            color: #ffffff;
            box-shadow: 0 10px 15px -3px rgba(22, 102, 186, 0.1), 0 4px 6px -2px rgba(22, 102, 186, 0.05);
        }

        .notice-content {
            position: relative;
            z-index: 2;
        }

        .notice-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: -0.025em;
        }

        .notice-text {
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.95;
        }

        .actions-section {
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .action-card {
            background: linear-gradient(135deg, #1666ba 0%, #368ce7 100%);
            border-radius: 12px;
            padding: 2rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            color: #ffffff;
            box-shadow: 0 8px 25px rgba(22, 102, 186, 0.2);
        }

        .action-card:hover {
            background: linear-gradient(135deg, #368ce7 0%, #7ab3ef 100%);
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(22, 102, 186, 0.3);
        }

        .action-icon {
            font-size: 2.5rem;
            color: #ffffff;
            margin-bottom: 1rem;
        }

        .action-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: -0.025em;
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
                        <div class="bill-info">
                            <div class="bill-amount">₱<?php echo number_format($bill['amount'], 2); ?></div>
                            <div class="bill-due">Due: <?php echo date('M d, Y', strtotime($bill['due_date'])); ?></div>
                            <?php if ($bill['description']): ?>
                                <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #64748b; font-weight: 500;">
                                    <?php echo htmlspecialchars($bill['description']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="bill-status status-<?php echo $bill['status']; ?>">
                            <?php echo ucfirst($bill['status']); ?>
                        </div>
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

    <script>
        function maintenanceRequest() {
            window.location.href = 'maintenance.php';
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