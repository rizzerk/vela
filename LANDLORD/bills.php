<?php
session_start();
require_once '../connection.php';
require_once '../mailer.php'; // Path to your mailer configuration

// Function to send bill notifications
function sendBillNotification($conn, $billId) {
    // Get bill details with tenant and landlord info
    $query = "SELECT b.*, p.title as property_title, 
                     u.name as tenant_name, u.email as tenant_email,
                     ul.name as landlord_name, ul.email as landlord_email
              FROM BILL b
              JOIN LEASE l ON b.lease_id = l.lease_id
              JOIN PROPERTY p ON l.property_id = p.property_id
              JOIN USERS u ON l.tenant_id = u.user_id
              JOIN USERS ul ON p.landlord_id = ul.user_id
              WHERE b.bill_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $billId);
    $stmt->execute();
    $bill = $stmt->get_result()->fetch_assoc();
    
    if (!$bill) return false;
    
    $mail = getMailer();
    
    try {
        $mail->addAddress($bill['tenant_email'], $bill['tenant_name']);
        
        // CC landlord for non-rent bills
        if ($bill['bill_type'] !== 'rent') {
            $mail->addCC($bill['landlord_email'], $bill['landlord_name']);
        }
        
        $mail->Subject = 'New Bill Notification: ' . $bill['property_title'];
        
        // HTML Email Body
        $mail->Body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                      <h2 style='color: #1666ba;'>New Bill Notification</h2>
                      <p>Hello {$bill['tenant_name']},</p>
                      <p>A new bill has been issued for your tenancy at <strong>{$bill['property_title']}</strong>.</p>
                      
                      <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                          <h3 style='margin-top: 0;'>Bill Details</h3>
                          <p><strong>Type:</strong> " . ucfirst($bill['bill_type']) . "</p>
                          <p><strong>Amount Due:</strong> ₱" . number_format($bill['amount'], 2) . "</p>
                          <p><strong>Due Date:</strong> " . date('F j, Y', strtotime($bill['due_date'])) . "</p>";
        
        if ($bill['bill_type'] === 'rent' && $bill['billing_period_start']) {
            $mail->Body .= "<p><strong>Billing Period:</strong> " . 
                          date('M j', strtotime($bill['billing_period_start'])) . " - " . 
                          date('M j, Y', strtotime($bill['billing_period_end'])) . "</p>";
        }
        
        if (!empty($bill['description'])) {
            $mail->Body .= "<p><strong>Notes:</strong> {$bill['description']}</p>";
        }
        
        $mail->Body .= "</div>
                       <p>Please ensure payment is made by the due date to avoid late fees.</p>
                       <p>You can view and pay this bill through your tenant portal.</p>
                       <p>Thank you,<br><strong>Property Management Team</strong></p>
                       </div>";
        
        // Plain Text Email Body
        $mail->AltBody = "New Bill Notification\n\n" .
                        "Hello {$bill['tenant_name']},\n" .
                        "A new bill has been issued for your tenancy at {$bill['property_title']}.\n\n" .
                        "BILL DETAILS\n" .
                        "Type: " . ucfirst($bill['bill_type']) . "\n" .
                        "Amount Due: ₱" . number_format($bill['amount'], 2) . "\n" .
                        "Due Date: " . date('F j, Y', strtotime($bill['due_date'])) . "\n" .
                        ($bill['bill_type'] === 'rent' && $bill['billing_period_start'] ? 
                         "Billing Period: " . date('M j', strtotime($bill['billing_period_start'])) . " - " . 
                         date('M j, Y', strtotime($bill['billing_period_end'])) . "\n" : "") .
                        (!empty($bill['description']) ? "Notes: {$bill['description']}\n" : "") . "\n" .
                        "Please ensure payment is made by the due date to avoid late fees.\n" .
                        "You can view and pay this bill through your tenant portal.\n\n" .
                        "Thank you,\nProperty Management Team";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Bill notification failed for bill #$billId: " . $e->getMessage());
        return false;
    }
}

// Function to send payment confirmations
function sendPaymentConfirmation($conn, $billId) {
    // Get payment details with tenant and landlord info
    $query = "SELECT b.*, p.title as property_title, 
                     u.name as tenant_name, u.email as tenant_email,
                     ul.name as landlord_name, ul.email as landlord_email
              FROM BILL b
              JOIN LEASE l ON b.lease_id = l.lease_id
              JOIN PROPERTY p ON l.property_id = p.property_id
              JOIN USERS u ON l.tenant_id = u.user_id
              JOIN USERS ul ON p.landlord_id = ul.user_id
              WHERE b.bill_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $billId);
    $stmt->execute();
    $bill = $stmt->get_result()->fetch_assoc();
    
    if (!$bill) return false;
    
    $mail = getMailer();
    
    try {
        $mail->addAddress($bill['tenant_email'], $bill['tenant_name']);
        $mail->addCC($bill['landlord_email'], $bill['landlord_name']);
        
        $mail->Subject = 'Payment Confirmation for ' . $bill['property_title'];
        
        // HTML Email Body
        $mail->Body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                      <h2 style='color: #5cb85c;'>Payment Received</h2>
                      <p>Hello {$bill['tenant_name']},</p>
                      <p>Thank you for your payment. Here are the details:</p>
                      
                      <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                          <h3 style='margin-top: 0;'>Payment Details</h3>
                          <p><strong>Property:</strong> {$bill['property_title']}</p>
                          <p><strong>Amount Paid:</strong> ₱" . number_format($bill['amount'], 2) . "</p>
                          <p><strong>Payment Date:</strong> " . date('F j, Y') . "</p>
                      </div>
                      
                      <p>This payment has been recorded in our system.</p>
                      <p>Thank you,<br><strong>Property Management Team</strong></p>
                      </div>";
        
        // Plain Text Email Body
        $mail->AltBody = "Payment Confirmation\n\n" .
                        "Hello {$bill['tenant_name']},\n" .
                        "Thank you for your payment. Here are the details:\n\n" .
                        "PAYMENT DETAILS\n" .
                        "Property: {$bill['property_title']}\n" .
                        "Amount Paid: ₱" . number_format($bill['amount'], 2) . "\n" .
                        "Payment Date: " . date('F j, Y') . "\n\n" .
                        "This payment has been recorded in our system.\n\n" .
                        "Thank you,\nProperty Management Team";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Payment confirmation failed for bill #$billId: " . $e->getMessage());
        return false;
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_paid'])) {
        $billId = $_POST['bill_id'];
        
        // Update bill status in database
        $stmt = $conn->prepare("UPDATE BILL SET status = 'paid', payment_date = NOW() WHERE bill_id = ?");
        $stmt->bind_param("i", $billId);
        
        if ($stmt->execute()) {
            // Send payment confirmation
            if (sendPaymentConfirmation($conn, $billId)) {
                $_SESSION['message'] = "Bill marked as paid and confirmation sent.";
            } else {
                $_SESSION['message'] = "Bill marked as paid but failed to send confirmation.";
            }
        } else {
            $_SESSION['error'] = "Failed to update bill status.";
        }
        
        header("Location: bills.php");
        exit();
    }
    
    if (isset($_POST['create_bill'])) {
        // Process bill creation form
        $leaseId = $_POST['lease_id'];
        $amount = $_POST['amount'];
        $dueDate = $_POST['due_date'];
        $billType = $_POST['bill_type'];
        $description = $_POST['description'];
        $periodStart = isset($_POST['period_start']) ? $_POST['period_start'] : null;
        $periodEnd = isset($_POST['period_end']) ? $_POST['period_end'] : null;
        
        $stmt = $conn->prepare("INSERT INTO BILL (lease_id, amount, due_date, bill_type, description, billing_period_start, billing_period_end) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idsssss", $leaseId, $amount, $dueDate, $billType, $description, $periodStart, $periodEnd);
        
        if ($stmt->execute()) {
            $billId = $conn->insert_id;
            
            // Send bill notification
            if (sendBillNotification($conn, $billId)) {
                $_SESSION['message'] = "Bill created and notification sent successfully.";
            } else {
                $_SESSION['message'] = "Bill created but failed to send notification.";
            }
        } else {
            $_SESSION['error'] = "Failed to create bill.";
        }
        
        header("Location: bills.php");
        exit();
    }
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }
        .filter-btn.active {
            background: #1666ba;
            color: white;
            border-color: #1666ba;
        }
        .bill-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            position: relative;
        }
        .bill-type {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            background: #f0f0f0;
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
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
    <?php include "includes/navbar/navbarIN.html" ?>
    
    <!-- Display messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="container">
        <h1>Manage Bills</h1>
        
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
            <button onclick="window.location.href='generate_monthly_bills.php'">
                Generate Monthly Rent Bills
            </button>
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
                
                <div class="action-buttons">
                    <?php if ($bill['status'] == 'unpaid' || $bill['status'] == 'overdue'): ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="bill_id" value="<?php echo $bill['bill_id']; ?>">
                            <button type="submit" name="mark_paid">Mark as Paid</button>
                        </form>
                    <?php endif; ?>
                    
                    <form method="post" action="delete_bill.php" style="display: inline;">
                        <input type="hidden" name="bill_id" value="<?php echo $bill['bill_id']; ?>">
                        <button type="submit" onclick="return confirm('Are you sure you want to delete this bill?')">
                            Delete Bill
                        </button>
                    </form>
                    
                    <?php if ($bill['status'] == 'unpaid' || $bill['status'] == 'overdue'): ?>
                        <button onclick="resendNotification(<?php echo $bill['bill_id']; ?>)">
                            Resend Notification
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        function resendNotification(billId) {
            if (confirm('Resend notification for this bill?')) {
                fetch('resend_notification.php?id=' + billId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Notification resent successfully');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            }
        }
    </script>
</body>
</html>