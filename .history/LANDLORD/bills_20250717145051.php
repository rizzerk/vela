<?php
session_start();
require_once '../connection.php';

// Handle monthly bill generation if requested
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_monthly'])) {
    try {
        $conn->begin_transaction();
        
        // Get all active leases
        $leasesQuery = "SELECT l.lease_id, l.property_id, l.tenant_id, p.monthly_rent 
                        FROM LEASE l
                        JOIN PROPERTY p ON l.property_id = p.property_id
                        WHERE l.active = 1";
        $leasesResult = $conn->query($leasesQuery);
        $leases = $leasesResult->fetch_all(MYSQLI_ASSOC);
        
        $generatedCount = 0;
        $currentDate = date('Y-m-d');
        
        foreach ($leases as $lease) {
            // Check if a rent bill already exists for this month
            $checkQuery = "SELECT bill_id FROM BILL 
                          WHERE lease_id = ? 
                          AND bill_type = 'rent' 
                          AND billing_period_start <= ? 
                          AND billing_period_end >= ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("iss", $lease['lease_id'], $currentDate, $currentDate);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows == 0) {
                // No bill exists for this period - create one
                $period_start = date('Y-m-01');
                $period_end = date('Y-m-t');
                $due_date = date('Y-m-d', strtotime('+5 days'));
                
                $insertStmt = $conn->prepare("INSERT INTO BILL 
                    (lease_id, amount, due_date, status, description, 
                     bill_type, billing_period_start, billing_period_end)
                    VALUES (?, ?, ?, 'unpaid', 'Monthly Rent', 'rent', ?, ?)");
                
                $insertStmt->bind_param("idsss", 
                    $lease['lease_id'],
                    $lease['monthly_rent'],
                    $due_date,
                    $period_start,
                    $period_end
                );
                
                $insertStmt->execute();
                $generatedCount++;
            }
        }
        
        $conn->commit();
        $success = "Successfully generated $generatedCount monthly rent bills.";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error generating bills: " . $e->getMessage();
    }
}

// Rest of your existing bills.php code...
// Filter by bill type if specified
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query with optional filter
$billsQuery = "SELECT b.*, p.title as property_title, 
                      u.name as tenant_name, u.email as tenant_email
               FROM BILL b
               JOIN LEASE l ON b.lease_id = l.lease_id
               JOIN PROPERTY p ON l.property_id = p.property_id
               JOIN USERS u ON l.tenant_id = u.user_id";

if ($type_filter !== 'all') {
    $billsQuery .= " WHERE b.bill_type = '" . $conn->real_escape_string($type_filter) . "'";
}

$billsQuery .= " ORDER BY b.due_date DESC";
$billsResult = $conn->query($billsQuery);
$bills = $billsResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Bills</title>
    <link rel="stylesheet" href="../LANDLORD/styles.css">    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
      /* bills.php specific styles */
      .bills-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            color: #1666ba;
            margin-bottom: 0.5rem;
        }
        
        .filter-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            background: white;
            color: #1666ba;
            border: 1px solid #bedaf7;
        }
        
        .filter-btn.active {
            background: #1666ba;
            color: white;
            border-color: #1666ba;
        }
        
        .filter-btn:hover:not(.active) {
            background: #f5f9ff;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .action-buttons button {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
        }
        
        .action-buttons button:first-child {
            background: #1666ba;
            color: white;
        }
        
        .action-buttons button:last-child {
            background: white;
            color: #1666ba;
            border: 1px solid #bedaf7;
        }
        
        .action-buttons button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(22, 102, 186, 0.1);
        }
        
        .bill-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
            border: 1px solid rgba(190, 218, 247, 0.3);
            position: relative;
            transition: all 0.3s;
        }
        
        .bill-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(22, 102, 186, 0.15);
        }
        
        .bill-type {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            background: #deecfb;
            color: #1666ba;
        }
        
        .bill-card h3 {
            font-size: 1.25rem;
            color: #1666ba;
            margin-bottom: 0.5rem;
            padding-right: 80px;
        }
        
        .bill-card p {
            margin-bottom: 0.5rem;
            color: #475569;
        }
        
        .bill-card strong {
            color: #1e293b;
        }
        
        .bill-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .bill-actions button {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            font-size: 0.85rem;
        }
        
        .paid-btn {
            background: #22c55e;
            color: white;
        }
        
        .edit-btn {
            background: #3b82f6;
            color: white;
        }
        
        .paid {
            border-left: 4px solid #22c55e;
        }
        
        .unpaid {
            border-left: 4px solid #ef4444;
        }
        
        .overdue {
            border-left: 4px solid #f97316;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #bedaf7;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }
        
        @media (max-width: 768px) {
            .bills-container {
                padding: 1rem;
            }
            
            .filter-bar, .action-buttons {
                gap: 0.75rem;
            }
            
            .filter-btn, .action-buttons button {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
<?php include ('../includes/navbar/landlord-sidebar.php'); ?>

<div class="main-content">
<div class="container">
        <h1>Manage Bills</h1>
        
        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="filter-bar">
            <a href="bills.php?type=all" class="filter-btn <?php echo $type_filter === 'all' ? 'active' : ''; ?>">
                All Bills
            </a>
            <a href="bills.php?type=rent" class="filter-btn <?php echo $type_filter === 'rent' ? 'active' : ''; ?>">
                Rent
            </a>
            <a href="bills.php?type=utility" class="filter-btn <?php echo $type_filter === 'utility' ? 'active' : ''; ?>">
                Utilities
            </a>
            <a href="bills.php?type=penalty" class="filter-btn <?php echo $type_filter === 'penalty' ? 'active' : ''; ?>">
                Penalties
            </a>
            <a href="bills.php?type=other" class="filter-btn <?php echo $type_filter === 'other' ? 'active' : ''; ?>">
                Other
            </a>
        </div>
        
        <div class="action-buttons">
            <form method="POST">
                <button type="submit" name="generate_monthly">Generate Monthly Rent Bills</button>
            </form>
            <button onclick="window.location.href='create-bill.php'">
                Create Custom Bill
            </button>
        </div>
        
        <?php foreach ($bills as $bill): ?>
            <div class="bill-card <?php echo $bill['status']; ?>">
                <span class="bill-type"><?php echo ucfirst($bill['bill_type']); ?></span>
                <h3><?php echo htmlspecialchars($bill['property_title']); ?></h3>
                <p>Tenant: <?php echo htmlspecialchars($bill['tenant_name']); ?></p>
                <p>Amount: ₱<?php echo number_format($bill['amount'], 2); ?></p>
                <p>Due: <?php echo date('M d, Y', strtotime($bill['due_date'])); ?></p>
                
                <?php if ($bill['bill_type'] === 'rent' && $bill['billing_period_start']): ?>
                    <p>Period: <?php echo date('M d', strtotime($bill['billing_period_start'])) . ' - ' . 
                                  date('M d, Y', strtotime($bill['billing_period_end'])); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($bill['description'])): ?>
                    <p>Description: <?php echo htmlspecialchars($bill['description']); ?></p>
                <?php endif; ?>
                
                <p>Status: <strong><?php echo ucfirst($bill['status']); ?></strong></p>
                
                <?php if ($bill['status'] == 'unpaid' || $bill['status'] == 'overdue'): ?>
                    <button onclick="markAsPaid(<?php echo $bill['bill_id']; ?>)">
                        Mark as Paid
                    </button>
                <?php endif; ?>
                
                <button onclick="window.location.href='edit-bill.php?id=<?php echo $bill['bill_id']; ?>'">
    Edit Bill
</button>
            </div>
        <?php endforeach; ?>
    </div>

</div>

    <script>
        function markAsPaid(billId) {
            if (confirm('Mark this bill as paid?')) {
                fetch('mark_paid.php?id=' + billId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            }
        }

    </script>
</body>
</html>