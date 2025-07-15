<?php
session_start();
require_once '../connection.php';

// Get all active leases with tenant and property info
$leasesQuery = "SELECT l.lease_id, p.title as property_title, 
                       u.name as tenant_name, u.user_id as tenant_id
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
        $success = "Bill created successfully!";
    } else {
        $error = "Error creating bill: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Manual Bill</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #1666ba;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .success {
            color: green;
            margin-bottom: 15px;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include "includes/navbar/navbarIN.html" ?>
    
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