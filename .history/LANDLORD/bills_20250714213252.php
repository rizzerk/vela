<?php
session_start();
require_once '../connection.php';

// Get all bills with lease and tenant info
$billsQuery = "SELECT b.*, p.title as property_title, 
                      u.name as tenant_name, u.email as tenant_email
               FROM BILL b
               JOIN LEASE l ON b.lease_id = l.lease_id
               JOIN PROPERTY p ON l.property_id = p.property_id
               JOIN USERS u ON l.tenant_id = u.user_id
               ORDER BY b.due_date DESC";
$billsResult = $conn->query($billsQuery);
$bills = $billsResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Bills</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Add your styling here */
        .bill-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .paid {
            background-color: #e6ffe6;
        }
        .unpaid {
            background-color: #ffe6e6;
        }
        .overdue {
            background-color: #ffcccc;
        }
    </style>
</head>
<body>
    <?php include "includes/navbar/navbarIN.html" ?>
    
    <div class="container">
        <h1>Manage Bills</h1>
        
        <button onclick="window.location.href='generate_monthly_bills.php'">
            Generate Monthly Bills
        </button>
        
        <?php foreach ($bills as $bill): ?>
            <div class="bill-card <?php echo $bill['status']; ?>">
                <h3><?php echo htmlspecialchars($bill['property_title']); ?></h3>
                <p>Tenant: <?php echo htmlspecialchars($bill['tenant_name']); ?></p>
                <p>Amount: ₱<?php echo number_format($bill['amount'], 2); ?></p>
                <p>Due: <?php echo date('M d, Y', strtotime($bill['due_date'])); ?></p>
                <p>Period: <?php echo date('M d', strtotime($bill['billing_period_start'])) . ' - ' . 
                                  date('M d, Y', strtotime($bill['billing_period_end'])); ?></p>
                <p>Status: <?php echo ucfirst($bill['status']); ?></p>
                
                <?php if ($bill['status'] == 'unpaid' || $bill['status'] == 'overdue'): ?>
                    <button onclick="markAsPaid(<?php echo $bill['bill_id']; ?>)">
                        Mark as Paid
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
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