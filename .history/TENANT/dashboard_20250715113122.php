<?php
session_start();
require_once "../connection.php";

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
    
    $billQuery = "SELECT bill_id, amount, due_date, status, description, 
                         billing_period_start, billing_period_end, bill_type
                  FROM BILL 
                  WHERE lease_id = ? AND status != 'paid'
                  ORDER BY due_date ASC";
    
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
            background: #f8fafc;
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
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
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
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2rem;
            margin-bottom: 1rem;
        }

        .bill-info {
            flex: 1;
        }

        .bill-type {
            font-size: 0.875rem;
            color: #1666ba;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .bill-amount {
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .bill-due {
            font-size: 1rem;
            color: #64748b;
            font-weight: 500;
            margin: 0;
        }

        .bill-period {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
            margin-top: 0.25rem;
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
        }

        .action-card {
            background: #1666ba;
            border-radius: 12px;
            padding: 2rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(22, 102, 186, 0.1), 0 2px 4px -1px rgba(22, 102, 186, 0.06);
        }

        .action-card:hover {
            background: #368ce7;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(22, 102, 186, 0.2), 0 4px 6px -2px rgba(22, 102, 186, 0.1);
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
                min-height: 120px;
            }

            .action-icon {
                font-size: 1.5rem;
                margin-bottom: 0.5rem;
            }

            .action-title {
                font-size: 0.8rem;
            }

            .bill-item {
                padding: 1rem;
            }

            .bill-amount {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar/tenant-navbar.php'; ?>

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
                    <div class="bill-item">
                        <div class="bill-info">
                            <div class="bill-type"><?php echo ucfirst($bill['bill_type']); ?></div>
                            <div class="bill-amount">₱<?php echo number_format($bill['amount'], 2); ?></div>
                            <div class="bill-due">Due: <?php echo date('M d, Y', strtotime($bill['due_date'])); ?></div>
                            <?php if ($bill['bill_type'] === 'rent' && $bill['billing_period_start']): ?>
                                <div class="bill-period">
                                    Period: <?php echo date('M d', strtotime($bill['billing_period_start'])) . ' - ' . 
                                                  date('M d, Y', strtotime($bill['billing_period_end'])); ?>
                                </div>
                            <?php endif; ?>
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
            <h2 class="notice-title">Important Notice</h2>
            <p class="notice-text">
                Your monthly rent payment is due on the 5th of each month. Please ensure timely payment to avoid late fees. 
                For any maintenance requests or concerns, use the button below or contact our support team.
            </p>
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
                    <div class="action-title">Payments</div>
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
            window.location.href = 'maintenance.php';
        }
        
        function viewPaymentHistory() {
            window.location.href = 'pay-dues.php';
        }
        
        function viewLease() {
            window.location.href = 'lease-details.php';
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>