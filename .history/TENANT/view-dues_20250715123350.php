<?php
session_start();
require_once '../connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get tenant's lease and bills
$user_id = $_SESSION['user_id'];
$bills = [];
$categories = [
    'RENT' => [],
    'UTILITIES' => [],
    'OTHER' => []
];

// Get tenant's lease
$leaseQuery = "SELECT l.lease_id, p.title AS property_name 
               FROM LEASE l 
               JOIN PROPERTY p ON l.property_id = p.property_id 
               WHERE l.tenant_id = ? AND l.active = 1";
$stmt = $conn->prepare($leaseQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$leaseResult = $stmt->get_result();
$lease = $leaseResult->fetch_assoc();

if ($lease) {
    // Get bills with payment information for this lease
    $billQuery = "SELECT 
                    b.bill_id, 
                    b.description, 
                    b.amount, 
                    b.due_date, 
                    b.status,
                    b.bill_type,
                    COALESCE(SUM(CASE WHEN p.status = 'verified' THEN p.amount_paid ELSE 0 END), 0) AS paid_amount,
                    (b.amount - COALESCE(SUM(CASE WHEN p.status = 'verified' THEN p.amount_paid ELSE 0 END), 0)) AS balance
                  FROM BILL b 
                  LEFT JOIN PAYMENT p ON b.bill_id = p.bill_id
                  WHERE b.lease_id = ?
                  GROUP BY b.bill_id, b.description, b.amount, b.due_date, b.status, b.bill_type
                  HAVING balance > 0 OR b.status = 'unpaid'
                  ORDER BY b.due_date ASC";
    $stmt = $conn->prepare($billQuery);
    $stmt->bind_param("i", $lease['lease_id']);
    $stmt->execute();
    $billResult = $stmt->get_result();

    while ($row = $billResult->fetch_assoc()) {
        // Only include bills that have a remaining balance or are unpaid
        if ($row['balance'] > 0 || $row['status'] == 'unpaid') {
            // Use bill_type from database if available, otherwise categorize by description
            $category = 'OTHER';
            
            if (!empty($row['bill_type'])) {
                switch (strtolower($row['bill_type'])) {
                    case 'rent':
                        $category = 'RENT';
                        break;
                    case 'utility':
                        $category = 'UTILITIES';
                        break;
                    default:
                        $category = 'OTHER';
                        break;
                }
            } else {
                // Fallback to description-based categorization
                $description = strtolower($row['description']);
                if (strpos($description, 'rent') !== false) {
                    $category = 'RENT';
                } elseif (
                    strpos($description, 'electric') !== false ||
                    strpos($description, 'water') !== false ||
                    strpos($description, 'utility') !== false
                ) {
                    $category = 'UTILITIES';
                }
            }

            $categories[$category][] = $row;
            $bills[] = $row;
        }
    }
}

// Calculate totals (using balance instead of full amount)
$totals = [
    'RENT' => 0,
    'UTILITIES' => 0,
    'OTHER' => 0
];

$originalTotals = [
    'RENT' => 0,
    'UTILITIES' => 0,
    'OTHER' => 0
];

$paidTotals = [
    'RENT' => 0,
    'UTILITIES' => 0,
    'OTHER' => 0
];

foreach ($categories as $category => $categoryBills) {
    foreach ($categoryBills as $bill) {
        $totals[$category] += $bill['balance'];
        $originalTotals[$category] += $bill['amount'];
        $paidTotals[$category] += $bill['paid_amount'];
    }
}

$grandTotal = array_sum($totals);
$grandOriginalTotal = array_sum($originalTotals);
$grandPaidTotal = array_sum($paidTotals);

// Get payment statistics
$paymentStatsQuery = "SELECT 
                        COUNT(DISTINCT b.bill_id) AS total_bills,
                        COUNT(DISTINCT CASE WHEN b.status = 'paid' THEN b.bill_id END) AS paid_bills,
                        COUNT(DISTINCT CASE WHEN b.status = 'unpaid' THEN b.bill_id END) AS unpaid_bills
                      FROM BILL b 
                      WHERE b.lease_id = ?";
$stmt = $conn->prepare($paymentStatsQuery);
$stmt->bind_param("i", $lease['lease_id']);
$stmt->execute();
$statsResult = $stmt->get_result();
$stats = $statsResult->fetch_assoc();

// Format currency
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

// Get status badge
function getStatusBadge($status) {
    switch ($status) {
        case 'paid':
            return '<span class="status-badge status-paid">Paid</span>';
        case 'unpaid':
            return '<span class="status-badge status-unpaid">Unpaid</span>';
        case 'overdue':
            return '<span class="status-badge status-overdue">Overdue</span>';
        default:
            return '<span class="status-badge status-pending">Pending</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIEW DUES - Tenant Portal</title>
    <script src="https://kit.fontawesome.com/dddee79f2e.js" crossorigin="anonymous"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            position: relative;
            min-height: 100vh;
        }

        .page-title-container {
            background: white;
            padding: 20px 10%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .page-title {
            color: #1666ba;
            font-size: 2.2rem;
            font-weight: 700;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            background: #e1f0fa;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1666ba;
            font-size: 1.8rem;
        }

        .main-container {
            display: flex;
            justify-content: center;
            min-height: calc(100vh - 180px);
            padding: 30px 10% 40px;
            background-color: white;
        }

        .dues-container {
            max-width: 1200px;
            width: 100%;
        }

        .property-info {
            background: #e1f0fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .property-detail {
            flex: 1;
            min-width: 300px;
        }

        .property-title {
            color: #1666ba;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .property-meta {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
        }

        .meta-item i {
            color: #1666ba;
        }

        .property-stats {
            display: flex;
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            min-width: 100px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1666ba;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        .payment-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .summary-value {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .summary-label {
            font-size: 0.9rem;
            color: #666;
        }

        .summary-item.total .summary-value {
            color: #1666ba;
        }

        .summary-item.paid .summary-value {
            color: #28a745;
        }

        .summary-item.remaining .summary-value {
            color: #dc3545;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            table-layout: fixed;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th:nth-child(1),
        td:nth-child(1) {
            width: 35%;
        }

        th:nth-child(2),
        td:nth-child(2) {
            width: 20%;
        }

        th:nth-child(3),
        td:nth-child(3) {
            width: 15%;
            text-align: right;
        }

        th:nth-child(4),
        td:nth-child(4) {
            width: 15%;
            text-align: right;
        }

        th:nth-child(5),
        td:nth-child(5) {
            width: 15%;
            text-align: right;
            padding-right: 20px;
        }

        th {
            background-color: #1666ba;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: rgba(22, 102, 186, 0.05);
        }

        tr:hover {
            background-color: rgba(22, 102, 186, 0.1);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-paid {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-unpaid {
            background: #ffebee;
            color: #c62828;
        }

        .status-overdue {
            background: #ffebee;
            color: #c62828;
        }

        .status-pending {
            background: #fff8e1;
            color: #f57f17;
        }

        .amount-paid {
            color: #28a745;
            font-weight: 500;
        }

        .amount-balance {
            color: #dc3545;
            font-weight: 600;
        }

        tr.total-row-rent {
            background-color: #e1f0fa;
            font-weight: 600;
        }

        tr.total-row-utilities {
            background-color: #e8f5e9;
            font-weight: 600;
        }

        tr.total-row-other {
            background-color: #fff8e1;
            font-weight: 600;
        }

        .grand-total {
            background: linear-gradient(to right, #1666ba, #0d4a8a);
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .grand-total-label {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .grand-total-amount {
            font-size: 1.8rem;
            font-weight: 700;
        }

        td {
            color: #333;
        }

        .table-title {
            color: #1666ba;
            margin: 25px 0 10px;
            font-size: 1.5rem;
            font-weight: 600;
            padding-left: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-title i {
            font-size: 1.3rem;
        }

        /* Payment Button Styles */
        .navigation-arrows {
            display: flex;
            justify-content: flex-end;
            margin-top: 40px;
            margin-bottom: 20px;
        }

        .nav-group {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            cursor: pointer;
        }

        .nav-group:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .nav-text {
            color: #1666ba;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
            font-size: 1.1rem;
        }

        .arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: linear-gradient(to right, #1666ba, #0d4a8a);
            border-radius: 50%;
            color: white;
            font-size: 22px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .arrow:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        @media (max-width: 992px) {
            .page-title-container,
            .main-container {
                padding-left: 5%;
                padding-right: 5%;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .payment-summary {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 1.5rem;
            }

            .page-title i {
                width: 45px;
                height: 45px;
                font-size: 1.3rem;
            }

            .property-info {
                flex-direction: column;
                align-items: flex-start;
            }

            .property-detail {
                min-width: 100%;
            }

            .property-stats {
                width: 100%;
                justify-content: space-around;
            }

            th,
            td {
                padding: 12px 8px;
                font-size: 0.85rem;
            }

            th:nth-child(1),
            td:nth-child(1) {
                width: 40%;
            }

            th:nth-child(2),
            td:nth-child(2) {
                width: 20%;
            }

            th:nth-child(3),
            td:nth-child(3),
            th:nth-child(4),
            td:nth-child(4),
            th:nth-child(5),
            td:nth-child(5) {
                width: 13.33%;
                text-align: right;
                padding-right: 8px;
            }

            .table-title {
                font-size: 1.2rem;
                padding-left: 0;
            }

            .navigation-arrows {
                margin-top: 30px;
            }

            .arrow {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .nav-group {
                gap: 10px;
                padding: 8px 15px;
            }

            .nav-text {
                font-size: 0.9rem;
            }

            .grand-total-label {
                font-size: 1.1rem;
            }

            .grand-total-amount {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 480px) {
            .table-title {
                font-size: 1.1rem;
            }

            .property-title {
                font-size: 1.2rem;
            }

            .property-meta {
                flex-direction: column;
                gap: 8px;
            }

            th,
            td {
                font-size: 0.8rem;
                padding: 8px 6px;
            }

            .grand-total {
                padding: 20px;
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .grand-total-label {
                font-size: 1rem;
            }

            .grand-total-amount {
                font-size: 1.2rem;
            }

            .navigation-arrows {
                margin-top: 20px;
                justify-content: center;
            }

            .nav-group {
                width: 100%;
                justify-content: center;
            }

            .nav-text {
                font-size: 1rem;
            }

            .payment-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="page-title-container">
        <h1 class="page-title">
            <i class="fas fa-receipt"></i>
            Your Dues Summary
        </h1>
    </div>

    <?php include '../includes/navbar/tenant-navbar.php'?>

    <div class="main-container">
        <div class="dues-container">
            <?php if ($lease): ?>
                <div class="property-info">
                    <div class="property-detail">
                        <div class="property-title"><?php echo htmlspecialchars($lease['property_name']); ?></div>
                        <div class="property-meta">
                            <div class="meta-item">
                                <i class="fas fa-key"></i>
                                <span>Active Lease</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Current Billing Period</span>
                            </div>
                        </div>
                    </div>
                    <div class="property-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['total_bills']; ?></div>
                            <div class="stat-label">Total Bills</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['paid_bills']; ?></div>
                            <div class="stat-label">Paid Bills</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo count($bills); ?></div>
                            <div class="stat-label">Outstanding</div>
                        </div>
                    </div>
                </div>

                <div class="payment-summary">
                    <div class="summary-item total">
                        <div class="summary-value"><?php echo formatCurrency($grandOriginalTotal); ?></div>
                        <div class="summary-label">Total Billed</div>
                    </div>
                    <div class="summary-item paid">
                        <div class="summary-value"><?php echo formatCurrency($grandPaidTotal); ?></div>
                        <div class="summary-label">Total Paid</div>
                    </div>
                    <div class="summary-item remaining">
                        <div class="summary-value"><?php echo formatCurrency($grandTotal); ?></div>
                        <div class="summary-label">Remaining Balance</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Rent Dues -->
            <h3 class="table-title">
                <i class="fas fa-home"></i>
                RENT
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Billed</th>
                        <th>Paid</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories['RENT'])): ?>
                        <?php foreach ($categories['RENT'] as $bill): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill['description']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($bill['due_date'])); ?></td>
                                <td><?php echo formatCurrency($bill['amount']); ?></td>
                                <td><span class="amount-paid"><?php echo formatCurrency($bill['paid_amount']); ?></span></td>
                                <td><span class="amount-balance"><?php echo formatCurrency($bill['balance']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row-rent">
                            <td><strong>Total Rent</strong></td>
                            <td></td>
                            <td><strong><?php echo formatCurrency($originalTotals['RENT']); ?></strong></td>
                            <td><strong class="amount-paid"><?php echo formatCurrency($paidTotals['RENT']); ?></strong></td>
                            <td><strong class="amount-balance"><?php echo formatCurrency($totals['RENT']); ?></strong></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #777;">
                                No outstanding rent dues at this time
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Utilities Dues -->
            <h3 class="table-title">
                <i class="fas fa-bolt"></i>
                UTILITIES
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Billed</th>
                        <th>Paid</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories['UTILITIES'])): ?>
                        <?php foreach ($categories['UTILITIES'] as $bill): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill['description']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($bill['due_date'])); ?></td>
                                <td><?php echo formatCurrency($bill['amount']); ?></td>
                                <td><span class="amount-paid"><?php echo formatCurrency($bill['paid_amount']); ?></span></td>
                                <td><span class="amount-balance"><?php echo formatCurrency($bill['balance']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row-utilities">
                            <td><strong>Total Utilities</strong></td>
                            <td></td>
                            <td><strong><?php echo formatCurrency($originalTotals['UTILITIES']); ?></strong></td>
                            <td><strong class="amount-paid"><?php echo formatCurrency($paidTotals['UTILITIES']); ?></strong></td>
                            <td><strong class="amount-balance"><?php echo formatCurrency($totals['UTILITIES']); ?></strong></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #777;">
                                No outstanding utility dues at this time
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Other Dues -->
            <h3 class="table-title">
                <i class="fas fa-file-invoice"></i>
                OTHER
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Billed</th>
                        <th>Paid</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories['OTHER'])): ?>
                        <?php foreach ($categories['OTHER'] as $bill): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill['description']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($bill['due_date'])); ?></td>
                                <td><?php echo formatCurrency($bill['amount']); ?></td>
                                <td><span class="amount-paid"><?php echo formatCurrency($bill['paid_amount']); ?></span></td>
                                <td><span class="amount-balance"><?php echo formatCurrency($bill['balance']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row-other">
                            <td><strong>Total Other</strong></td>
                            <td></td>
                            <td><strong><?php echo formatCurrency($originalTotals['OTHER']); ?></strong></td>
                            <td><strong class="amount-paid"><?php echo formatCurrency($paidTotals['OTHER']); ?></strong></td>
                            <td><strong class="amount-balance"><?php echo formatCurrency($totals['OTHER']); ?></strong></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #777;">
                                No outstanding other dues at this time
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="grand-total">
                <span class="grand-total-label">TOTAL OUTSTANDING BALANCE:</span>
                <span class="grand-total-amount"><?php echo formatCurrency($grandTotal); ?></span>
            </div>

            <!-- Navigation Arrows -->
            <div class="navigation-arrows">
                <div class="nav-group" onclick="window.location.href='dashboard.php'">
                    <a href="dashboard.php" class="arrow"><i class="fa-solid fa-arrow-left"></i></a>
                    <p class="nav-text">Back to Dashboard</p>
                </div>
                <?php if ($grandTotal > 0): ?>
                <div class="nav-group" onclick="window.location.href='pay-dues.php'">
                    <p class="nav-text">Proceed to Payment</p>
                    <a href="pay-dues.php" class="arrow"><i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>