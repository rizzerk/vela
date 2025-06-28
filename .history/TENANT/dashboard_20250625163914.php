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
if ($lease) {
    $billQuery = "SELECT bill_id, amount, due_date, status, description 
                  FROM BILL 
                  WHERE lease_id = ? 
                  ORDER BY due_date DESC";
    $billStmt = $conn->prepare($billQuery);
    $billStmt->bind_param("i", $lease['lease_id']);
    $billStmt->execute();
    $billResult = $billStmt->get_result();
    
    while ($row = $billResult->fetch_assoc()) {
        $bills[] = $row;
    }
}

$conn->close();
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
            background: linear-gradient(135deg, #ffffff 0%, #deecfb 100%);
            color: #000000;
            line-height: 1.7;
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
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(22, 102, 186, 0.06);
            border: 1px solid #deecfb;
            margin-bottom: 2rem;
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
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .bill-item.overdue {
            background: #fef2f2;
            border-left-color: #dc2626;
        }

        .bill-item.unpaid {
            background: #fef3c7;
            border-left-color: #d97706;
        }

        .bill-item.paid {
            background: #f0fdf4;
            border-left-color: #16a34a;
        }

        .bill-amount {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1666ba;
        }

        .bill-due {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .bill-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 0.5rem;
        }

        .status-overdue {
            background: #dc2626;
            color: white;
        }

        .status-unpaid {
            background: #d97706;
            color: white;
        }

        .status-paid {
            background: #16a34a;
            color: white;
        }

        .notice-section {
            background: linear-gradient(135deg, #1666ba 0%, #368ce7 100%);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.2);
        }

        .notice-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
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
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(22, 102, 186, 0.06);
            border: 1px solid #deecfb;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .action-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(22, 102, 186, 0.06);
            border: 1px solid #deecfb;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(22, 102, 186, 0.15);
            border-color: #bedaf7;
        }

        .action-icon {
            font-size: 2.5rem;
            color: #1666ba;
            margin-bottom: 1rem;
        }

        .action-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1666ba;
            margin-bottom: 0.5rem;
        }

        .action-desc {
            font-size: 0.9rem;
            color: #666;
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
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar/tenant-navbar.php'?>

    <div class="content-wrapper">
        <div class="bills-section">
            <div class="section-header">
                <h2 class="section-title">Payment Status</h2>
                <div class="welcome-text">Welcome back, <?php echo htmlspecialchars($userName); ?>!</div>
            </div>
            
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
            <h2 class="section-title">Quick Actions</h2>
            
            <div class="actions-grid">
                <div class="action-card" onclick="maintenanceRequest()">
                    <div class="action-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="action-title">Maintenance Request</div>
                    <div class="action-desc">Report issues or request repairs for your property</div>
                </div>
                <div class="action-card" onclick="viewPaymentHistory()">
                    <div class="action-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="action-title">Payment History</div>
                    <div class="action-desc">View your past payments and transaction records</div>
                </div>
                <div class="action-card" onclick="viewLease()">
                    <div class="action-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="action-title">Lease Details</div>
                    <div class="action-desc">Review your lease agreement and property information</div>
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
    </script>
</body>
</html>