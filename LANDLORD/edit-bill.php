<?php
session_start();
require_once '../connection.php';

// Check if bill ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: bills.php");
    exit();
}

$bill_id = (int)$_GET['id'];

// Fetch bill details
$billQuery = "SELECT b.*, p.title as property_title, 
                     u.name as tenant_name, u.email as tenant_email
              FROM BILL b
              JOIN LEASE l ON b.lease_id = l.lease_id
              JOIN PROPERTY p ON l.property_id = p.property_id
              JOIN USERS u ON l.tenant_id = u.user_id
              WHERE b.bill_id = ?";
$billStmt = $conn->prepare($billQuery);
$billStmt->bind_param("i", $bill_id);
$billStmt->execute();
$billResult = $billStmt->get_result();

if ($billResult->num_rows === 0) {
    header("Location: bills.php");
    exit();
}

$bill = $billResult->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = (float)$_POST['amount'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];
    $description = $_POST['description'];
    $bill_type = $_POST['bill_type'];
    $period_start = $_POST['billing_period_start'];
    $period_end = $_POST['billing_period_end'];

    try {
        $updateStmt = $conn->prepare("UPDATE BILL SET
            amount = ?,
            due_date = ?,
            status = ?,
            description = ?,
            bill_type = ?,
            billing_period_start = ?,
            billing_period_end = ?
            WHERE bill_id = ?");
        
        $updateStmt->bind_param("dssssssi", 
            $amount,
            $due_date,
            $status,
            $description,
            $bill_type,
            $period_start,
            $period_end,
            $bill_id
        );
        
        $updateStmt->execute();
        
        $_SESSION['success'] = "Bill updated successfully!";
        header("Location: bills.php");
        exit();
    } catch (Exception $e) {
        $error = "Error updating bill: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Bill</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .form-actions {
            margin-top: 20px;
            text-align: right;
        }
        
        button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #1666ba;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<?php include ('../includes/navbar/landlord-sidebar.php'); ?>

<div class="main-content">
    <div class="form-container">
        <h1>Edit Bill</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Property</label>
                <input type="text" value="<?php echo htmlspecialchars($bill['property_title']); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Tenant</label>
                <input type="text" value="<?php echo htmlspecialchars($bill['tenant_name']); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Bill Type</label>
                <select name="bill_type" required>
                    <option value="rent" <?php echo $bill['bill_type'] == 'rent' ? 'selected' : ''; ?>>Rent</option>
                    <option value="utility" <?php echo $bill['bill_type'] == 'utility' ? 'selected' : ''; ?>>Utility</option>
                    <option value="penalty" <?php echo $bill['bill_type'] == 'penalty' ? 'selected' : ''; ?>>Penalty</option>
                    <option value="other" <?php echo $bill['bill_type'] == 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Amount</label>
                <input type="number" name="amount" step="0.01" min="0" value="<?php echo htmlspecialchars($bill['amount']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date" value="<?php echo htmlspecialchars($bill['due_date']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="unpaid" <?php echo $bill['status'] == 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="paid" <?php echo $bill['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="overdue" <?php echo $bill['status'] == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                </select>
            </div>
            
            <?php if ($bill['bill_type'] == 'rent'): ?>
                <div class="form-group">
                    <label>Billing Period Start</label>
                    <input type="date" name="billing_period_start" value="<?php echo htmlspecialchars($bill['billing_period_start']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Billing Period End</label>
                    <input type="date" name="billing_period_end" value="<?php echo htmlspecialchars($bill['billing_period_end']); ?>">
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($bill['description']); ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="window.location.href='bills.php'">Cancel</button>
                <button type="submit" class="btn-primary">Update Bill</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>