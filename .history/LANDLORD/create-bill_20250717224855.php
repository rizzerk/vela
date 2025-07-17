<?php
session_start();
require_once '../connection.php';
require_once '../vendor/autoload.php'; // Load PHPMailer
require_once "../includes/auth/landlord_auth.php";


// Function to send bill notification email
function sendBillEmail($tenantEmail, $tenantName, $billDetails) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'velacinco5@gmail.com'; // Your Gmail
        $mail->Password   = 'aycm atee woxl lmvj';      // App Password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('velacinco5@gmail.com', 'VELA Cinco Rentals');
        $mail->addAddress($tenantEmail, $tenantName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Bill Generated - ' . $billDetails['property_title'];
        
        $mail->Body = "
            <h2>Hello {$tenantName},</h2>
            <p>A new bill has been generated for your property <strong>{$billDetails['property_title']}</strong>:</p>
            
            <table border='1' cellpadding='10' style='border-collapse: collapse;'>
                <tr>
                    <th style='text-align: left;'>Bill Type</th>
                    <td>" . ucfirst($billDetails['bill_type']) . "</td>
                </tr>
                <tr>
                    <th style='text-align: left;'>Amount Due</th>
                    <td>₱" . number_format($billDetails['amount'], 2) . "</td>
                </tr>
                <tr>
                    <th style='text-align: left;'>Due Date</th>
                    <td>{$billDetails['due_date']}</td>
                </tr>
        ";
        
        if ($billDetails['bill_type'] === 'rent') {
            $mail->Body .= "
                <tr>
                    <th style='text-align: left;'>Billing Period</th>
                    <td>{$billDetails['period_start']} to {$billDetails['period_end']}</td>
                </tr>
            ";
        }
        
        $mail->Body .= "
            </table>
            
            <p>Description: {$billDetails['description']}</p>
            
            <p>Please make payment before the due date.</p>
            <p>Thank you,<br>VELA Cinco Rentals</p>
        ";

        $mail->AltBody = "Hello {$tenantName},\n\n" .
            "A new bill has been generated for your property {$billDetails['property_title']}:\n\n" .
            "Bill Type: " . ucfirst($billDetails['bill_type']) . "\n" .
            "Amount Due: ₱" . number_format($billDetails['amount'], 2) . "\n" .
            "Due Date: {$billDetails['due_date']}\n" .
            ($billDetails['bill_type'] === 'rent' ? 
                "Billing Period: {$billDetails['period_start']} to {$billDetails['period_end']}\n" : "") .
            "Description: {$billDetails['description']}\n\n" .
            "Please make payment before the due date.\n\n" .
            "Thank you,\nProperty Management Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error for {$tenantEmail}: " . $e->getMessage());
        return false;
    }
}

// Get all active leases with tenant and property info
$leasesQuery = "SELECT l.lease_id, p.title as property_title, 
                       u.name as tenant_name, u.user_id as tenant_id,
                       u.email as tenant_email
                FROM LEASE l
                JOIN PROPERTY p ON l.property_id = p.property_id
                JOIN USERS u ON l.tenant_id = u.user_id
                WHERE l.active = 1";
$leasesResult = $conn->query($leasesQuery);
$leases = $leasesResult->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lease_id = $_POST['lease_id'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    $description = $_POST['description'];
    $bill_type = $_POST['bill_type'];
    $period_start = !empty($_POST['period_start']) ? $_POST['period_start'] : NULL;
    $period_end = !empty($_POST['period_end']) ? $_POST['period_end'] : NULL;

    // Get lease details for email
    $leaseDetails = [];
    foreach ($leases as $lease) {
        if ($lease['lease_id'] == $lease_id) {
            $leaseDetails = $lease;
            break;
        }
    }

    $stmt = $conn->prepare("INSERT INTO BILL 
        (lease_id, amount, due_date, status, description, 
         bill_type, billing_period_start, billing_period_end)
        VALUES (?, ?, ?, 'unpaid', ?, ?, ?, ?)");
    
    $stmt->bind_param("idsssss", 
        $lease_id, 
        $amount, 
        $due_date,
        $description,
        $bill_type,
        $period_start,
        $period_end
    );
    
    if ($stmt->execute()) {
        // Prepare bill details for email
        $billDetails = [
            'property_title' => $leaseDetails['property_title'],
            'bill_type' => $bill_type,
            'amount' => $amount,
            'due_date' => date('M j, Y', strtotime($due_date)),
            'period_start' => $period_start ? date('M j, Y', strtotime($period_start)) : '',
            'period_end' => $period_end ? date('M j, Y', strtotime($period_end)) : '',
            'description' => $description
        ];
        
        // Send email notification
        if (sendBillEmail(
            $leaseDetails['tenant_email'],
            $leaseDetails['tenant_name'],
            $billDetails
        )) {
            $success = "Bill created successfully and notification email sent!";
        } else {
            $success = "Bill created successfully but email notification failed to send.";
        }
    } else {
        $error = "Error creating bill: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Manual Bill</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
          .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
            border: 1px solid rgba(190, 218, 247, 0.3);
        }
        
        h2 {
            font-size: 2rem;
            color: #1666ba;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1666ba;
            font-size: 0.9rem;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #bedaf7;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        button {
            background: #1666ba;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        button:hover {
            background: #135a9e;
        }
        
        .success {
            color: #2e7d32;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #e6ffe6;
            border-radius: 6px;
            border-left: 4px solid #2e7d32;
        }
        
        .error {
            color: #c62828;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #ffe6e6;
            border-radius: 6px;
            border-left: 4px solid #c62828;
        }
        
        #period-group {
            display: none;
        }
    </style>
</head>
<body>
<?php include ('../includes/navbar/landlord-sidebar.php'); ?>
    
    <div class="form-container">
        <h2>Create Manual Bill</h2>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="lease_id">Select Lease</label>
                <select id="lease_id" name="lease_id" required>
                    <option value="">-- Select Lease --</option>
                    <?php foreach ($leases as $lease): ?>
                        <option value="<?php echo $lease['lease_id']; ?>">
                            <?php echo htmlspecialchars($lease['property_title'] . " - " . $lease['tenant_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="bill_type">Bill Type</label>
                <select id="bill_type" name="bill_type" required>
                    <option value="rent">Rent</option>
                    <option value="utility">Utility</option>
                    <option value="penalty">Penalty/Fee</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" step="0.01" min="0" id="amount" name="amount" required>
            </div>
            
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" required>
            </div>
            
            <div class="form-group" id="period-group">
                <label>Billing Period (Optional)</label>
                <div style="display: flex; gap: 10px;">
                    <input type="date" id="period_start" name="period_start" placeholder="Start date">
                    <span>to</span>
                    <input type="date" id="period_end" name="period_end" placeholder="End date">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            
            <button type="submit">Create Bill</button>
        </form>
    </div>

    <script>
        // Show/hide period fields based on bill type
        document.getElementById('bill_type').addEventListener('change', function() {
            const periodGroup = document.getElementById('period-group');
            if (this.value === 'rent') {
                periodGroup.style.display = 'block';
            } else {
                periodGroup.style.display = 'none';
            }
        });
        
        // Set default due date to today + 5 days
        const today = new Date();
        today.setDate(today.getDate() + 5);
        const dueDate = today.toISOString().split('T')[0];
        document.getElementById('due_date').value = dueDate;
        
        // For rent bills, set default period to current month
        document.getElementById('bill_type').addEventListener('change', function() {
            if (this.value === 'rent') {
                const now = new Date();
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                
                document.getElementById('period_start').value = firstDay.toISOString().split('T')[0];
                document.getElementById('period_end').value = lastDay.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>