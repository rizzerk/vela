<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $payment_method = filter_input(INPUT_POST, 'payment-method', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $ref_num = filter_input(INPUT_POST, 'ref-num', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $payment_description = filter_input(INPUT_POST, 'payment-description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $bill_id = filter_input(INPUT_POST, 'bill-id', FILTER_VALIDATE_INT);

    if (!$amount || !$payment_method || !$ref_num) {
        die("Invalid input data");
    }

    // Upload proof of payment
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        // Use relative path instead of document root
        $upload_dir = __DIR__ . '/../uploads/payments/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                // Try alternative method if mkdir fails
                $upload_dir = 'uploads/payments/';
                if (!file_exists($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        die("Failed to create upload directory. Please check permissions.");
                    }
                }
            }
        }

        // Validate file
        $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $file_type = $_FILES['proof']['type'];
        $file_ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));

        // Check if file type is allowed
        if (!array_key_exists($file_type, $allowed_types) || !in_array($file_ext, $allowed_types)) {
            die("Only JPG and PNG files are allowed");
        }

        // Check file size (max 5MB)
        if ($_FILES['proof']['size'] > 5000000) {
            die("File is too large. Maximum size is 5MB");
        }

        $file_tmp = $_FILES['proof']['tmp_name'];
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $full_file_path = $upload_dir . $file_name;

        // Web-accessible path for DB
        $file_path_for_db = 'uploads/payments/' . $file_name;

        if (move_uploaded_file($file_tmp, $full_file_path)) {
            // Determine which bill to process
            if ($bill_id) {
                // Specific bill ID provided - validate it belongs to this tenant
                $bill_query = "
                    SELECT b.bill_id, b.amount, b.description, b.bill_type, b.status
                    FROM BILL b
                    JOIN LEASE l ON b.lease_id = l.lease_id
                    WHERE l.tenant_id = ? AND b.bill_id = ?
                ";
                $stmt = $conn->prepare($bill_query);
                if (!$stmt) {
                    unlink($full_file_path);
                    die("Prepare failed: " . $conn->error);
                }

                $stmt->bind_param("ii", $user_id, $bill_id);
                if (!$stmt->execute()) {
                    unlink($full_file_path);
                    die("Query failed: " . $stmt->error);
                }

                $bill_result = $stmt->get_result();

                if ($bill_result->num_rows === 0) {
                    unlink($full_file_path);
                    die("Bill not found or you don't have permission to pay this bill.");
                }
            } else {
                // No specific bill ID - get the next unpaid bill for this tenant
                $bill_query = "
                    SELECT b.bill_id, b.amount, b.description, b.bill_type, b.status
                    FROM BILL b
                    JOIN LEASE l ON b.lease_id = l.lease_id
                    WHERE l.tenant_id = ? AND b.status = 'unpaid' AND NOT EXISTS (
                        SELECT 1 FROM PAYMENT p WHERE p.bill_id = b.bill_id AND p.status = 'verified'
                    )
                    ORDER BY b.due_date ASC
                    LIMIT 1
                ";
                $stmt = $conn->prepare($bill_query);
                if (!$stmt) {
                    unlink($full_file_path);
                    die("Prepare failed: " . $conn->error);
                }

                $stmt->bind_param("i", $user_id);
                if (!$stmt->execute()) {
                    unlink($full_file_path);
                    die("Query failed: " . $stmt->error);
                }

                $bill_result = $stmt->get_result();
            }

            if ($bill_result->num_rows > 0) {
                $bill = $bill_result->fetch_assoc();
                $target_bill_id = $bill['bill_id'];
                $bill_amount = $bill['amount'];
                $bill_description = $bill['description'];
                $bill_type = $bill['bill_type'];
                $bill_status = $bill['status'];

                // Create a comprehensive payment description
                $final_payment_description = $payment_description;
                if (empty($payment_description)) {
                    // Auto-generate description based on bill info
                    $final_payment_description = ucfirst($bill_type) . " Payment";
                    if (!empty($bill_description)) {
                        $final_payment_description .= " - " . $bill_description;
                    }
                    $final_payment_description .= " (Bill ID: " . $target_bill_id . ")";
                }

                // Begin transaction for atomic operations
                $conn->begin_transaction();

                try {
                    // Insert payment record - store description in message field
                    $insert_query = "
                        INSERT INTO PAYMENT (
                            bill_id, 
                            amount_paid, 
                            proof_of_payment, 
                            submitted_at, 
                            status, 
                            reference_num, 
                            mode,
                            message
                        ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)
                    ";

                    // Set payment status and normalize payment method
                    $payment_status = 'pending';
                    $payment_method_lower = strtolower(trim($payment_method));
                    
                    // Validate payment method against allowed ENUM values
                    $allowed_modes = ['cash', 'bpi', 'gcash', 'bdo'];
                    if (!in_array($payment_method_lower, $allowed_modes)) {
                        throw new Exception("Invalid payment method: " . $payment_method);
                    }

                    $stmt = $conn->prepare($insert_query);
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }

                    if (!$stmt->bind_param(
                        "idsssss",
                        $target_bill_id,
                        $amount,
                        $file_path_for_db,
                        $payment_status,
                        $ref_num,
                        $payment_method_lower,
                        $final_payment_description
                    )) {
                        throw new Exception("Bind failed: " . $stmt->error);
                    }

                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }

                    // Update bill status based on payment
                    // Only set to 'paid' if fully paid, otherwise keep as current status
                    $new_status = ($amount >= $bill_amount) ? 'paid' : $bill_status;

                    $update_bill_query = "UPDATE BILL SET status = ? WHERE bill_id = ?";
                    $update_stmt = $conn->prepare($update_bill_query);
                    if (!$update_stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }

                    if (!$update_stmt->bind_param("si", $new_status, $target_bill_id)) {
                        throw new Exception("Bind failed: " . $update_stmt->error);
                    }

                    if (!$update_stmt->execute()) {
                        throw new Exception("Execute failed: " . $update_stmt->error);
                    }

                    // Commit transaction if all succeeds
                    $conn->commit();

                    header("Location: payment_confirmation.php");
                    exit();
                } catch (Exception $e) {
                    $conn->rollback();
                    unlink($full_file_path);
                    die("Payment processing failed: " . $e->getMessage());
                }
            } else {
                unlink($full_file_path);
                if ($bill_id) {
                    die("Bill ID " . $bill_id . " not found or you don't have permission to pay this bill.");
                } else {
                    die("No unpaid bill found for you.");
                }
            }
        } else {
            die("File upload failed. Check directory permissions.");
        }
    } else {
        die("No file uploaded or upload error: " . $_FILES['proof']['error']);
    }
}

// Fetch tenant's unpaid bills for the dropdown
$bills_query = "
    SELECT b.bill_id, b.amount, b.description, b.bill_type, b.due_date, b.status
    FROM BILL b
    JOIN LEASE l ON b.lease_id = l.lease_id
    WHERE l.tenant_id = ? AND b.status IN ('unpaid', 'overdue')
    ORDER BY b.due_date ASC
";
$stmt = $conn->prepare($bills_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bills_result = $stmt->get_result();
$unpaid_bills = $bills_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAY DUES</title>
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
            background-color: white;
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
        }

        .main-container {
            display: flex;
            justify-content: center;
            min-height: calc(100vh - 180px);
            padding: 0 10% 80px;
            background-color: white;
        }

        .payment-container {
            display: flex;
            max-width: 1200px;
            width: 100%;
            gap: 30px;
        }

        .payment-info {
            flex: 1;
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid #ddd;
        }

        .payment-info h2 {
            text-align: left;
            margin-bottom: 15px;
            color: #1666ba;
        }

        .payment-form-container {
            flex: 1;
            position: relative;
        }

        #payment-form {
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            height: 100%;
            border: 1px solid #ddd;
        }

        .navigation-arrows {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 100;
        }

        .nav-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-text {
            color: #1666ba;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s;
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
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        h2 {
            color: #1666ba;
            margin-bottom: 25px;
        }

        .qr-display {
            text-align: center;
            margin: 20px 0;
        }

        .qr-display img {
            max-width: 300px;
            border: 1px solid #ddd;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }

        .payment-options {
            background-color: #e1f0fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .payment-options h3 {
            color: #1666ba;
            margin-bottom: 15px;
            text-align: left;
        }

        .payment-options p {
            margin-bottom: 10px;
            text-align: left;
        }

        .row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-grp {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-grp label {
            text-align: left;
            margin-bottom: 8px;
            color: #1666ba;
        }

        .form-grp input,
        .form-grp select,
        .form-grp textarea {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            width: 100%;
        }

        .form-grp textarea {
            resize: vertical;
            min-height: 60px;
        }

        .form-grp input:focus,
        .form-grp select:focus,
        .form-grp textarea:focus {
            border-color: #1666ba;
            outline: none;
        }

        .file-upload-box {
            border: 2px dashed #1666ba;
            padding: 30px;
            border-radius: 8px;
            background-color: #e1f0fa;
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-box:hover {
            background-color: #d0e5f5;
        }

        .file-upload-box i {
            font-size: 40px;
            color: #1666ba;
            margin-bottom: 10px;
        }

        .file-upload-box p {
            margin-bottom: 10px;
            color: #1666ba;
            font-weight: 500;
        }

        .file-upload-box input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .submit-btn {
            margin-top: 15px;
            padding: 12px;
            width: 100%;
            background: linear-gradient(to right, #1666ba, #0d4a8a);
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(13, 74, 138, 0.4);
        }

        .bill-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #1666ba;
        }

        .bill-info h4 {
            color: #1666ba;
            margin-bottom: 8px;
        }

        .bill-info p {
            margin: 5px 0;
            color: #666;
        }

        .bill-info .amount {
            color: #d32f2f;
            font-weight: 600;
            font-size: 1.1em;
        }

        .bill-info .overdue {
            color: #d32f2f;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 1.8rem;
                margin: 20px 0;
                padding: 0 5%;
            }

            .payment-container {
                flex-direction: column;
            }

            .row {
                flex-direction: column;
            }

            .payment-info,
            #payment-form {
                padding: 30px 20px;
            }

            .navigation-arrows {
                position: fixed;
                bottom: 10px;
                padding: 0 10px;
            }

            .arrow {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .nav-group {
                gap: 8px;
            }

            .nav-text {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <div class="page-title-container">
        <h1 class="page-title">PAY DUES</h1>
    </div>

    <div class="main-container">
        <div class="payment-container">
            <div class="payment-info">
                <h2>SCAN TO PAY</h2>
                <div class="qr-display">
                    <img src="https://ph-test-11.slatic.net/p/b4e1945f971c9fd8bd4eb1a1cf606c1b.jpg" alt="GCash QR Code">
                </div>

                <div class="payment-options">
                    <h3>PAYMENT OPTIONS</h3>
                    <p><strong>GCash:</strong> 09123456789</p>
                    <p><strong>BDO:</strong> 01384320182</p>
                    <p><strong>BPI:</strong> 29034390248</p>
                    <p><strong>Cash:</strong> Visit our office</p>
                </div>
            </div>

            <div class="payment-form-container">
                <form id="payment-form" action="pay-dues.php" method="post" enctype="multipart/form-data">
                    <h2>PROOF OF PAYMENT</h2>

                    <div class="form-grp">
                        <label for="bill-id">Select Bill to Pay (Optional):</label>
                        <select id="bill-id" name="bill-id" onchange="updateBillInfo()">
                            <option value="">Auto-select next unpaid bill</option>
                            <?php foreach ($unpaid_bills as $bill): ?>
                                <option value="<?= $bill['bill_id'] ?>" 
                                        data-amount="<?= $bill['amount'] ?>"
                                        data-description="<?= htmlspecialchars($bill['description']) ?>"
                                        data-type="<?= $bill['bill_type'] ?>"
                                        data-due-date="<?= $bill['due_date'] ?>"
                                        data-status="<?= $bill['status'] ?>">
                                    Bill #<?= $bill['bill_id'] ?> - <?= ucfirst($bill['bill_type']) ?> 
                                    (₱<?= number_format($bill['amount'], 2) ?>) 
                                    <?= $bill['status'] == 'overdue' ? '- OVERDUE' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="bill-info" class="bill-info" style="display: none;">
                        <h4>Bill Details</h4>
                        <p><strong>Bill ID:</strong> <span id="selected-bill-id"></span></p>
                        <p><strong>Type:</strong> <span id="selected-bill-type"></span></p>
                        <p><strong>Description:</strong> <span id="selected-bill-description"></span></p>
                        <p><strong>Amount:</strong> <span id="selected-bill-amount" class="amount"></span></p>
                        <p><strong>Due Date:</strong> <span id="selected-bill-due-date"></span></p>
                        <p><strong>Status:</strong> <span id="selected-bill-status"></span></p>
                    </div>

                    <div class="file-upload-box">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop your file here or click to browse</p>
                        <input type="file" id="proof" name="proof" required accept="image/png, image/jpeg">
                        <small>Accepted formats: JPG, PNG (Max 5MB)</small>
                    </div>

                    <div class="form-grp">
                        <label for="payment-description">Payment Description:</label>
                        <textarea id="payment-description" name="payment-description" placeholder="e.g., Monthly Rent - January 2025, Utility Bill, Security Deposit, etc. (Optional - will auto-generate if left empty)"></textarea>
                    </div>

                    <div class="row">
                        <div class="form-grp">
                            <label for="amount">Amount:</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                        </div>

                        <div class="form-grp">
                            <label for="payment-method">Payment Method:</label>
                            <select id="payment-method" name="payment-method" required>
                                <option value=""></option>
                                <option value="Gcash">GCash</option>
                                <option value="BDO">Bank Transfer - BDO</option>
                                <option value="BPI">Bank Transfer - BPI</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grp">
                        <label for="ref-num">Reference Number:</label>
                        <input type="text" id="ref-num" name="ref-num" required>
                    </div>

                    <button type="submit" class="submit-btn">SUBMIT</button>
                </form>
            </div>
        </div>
    </div>

    <div class="navigation-arrows">
        <div class="nav-group">
            <a href="view-dues.php" class="arrow"><i class="fa-solid fa-arrow-left"></i></a>
            <p class="nav-text">View Dues</p>
        </div>

        <div class="nav-group">
            <p class="nav-text">View Payment History</p>
            <a href="payment-history.php" class="arrow"><i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>

    <script>
        function updateBillInfo() {
            const billSelect = document.getElementById('bill-id');
            const billInfo = document.getElementById('bill-info');
            const amountInput = document.getElementById('amount');
            
            if (billSelect.value) {
                const selectedOption = billSelect.options[billSelect.selectedIndex];
                
                // Show bill info
                billInfo.style.display = 'block';
                
                // Update bill details
                document.getElementById('selected-bill-id').textContent = selectedOption.value;
                document.getElementById('selected-bill-type').textContent = selectedOption.dataset.type;
                document.getElementById('selected-bill-description').textContent = selectedOption.dataset.description || 'No description';
                document.getElementById('selected-bill-amount').textContent = '₱' + parseFloat(selectedOption.dataset.amount).toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('selected-bill-due-date').textContent = selectedOption.dataset.dueDate;
                
                const statusSpan = document.getElementById('selected-bill-status');
                statusSpan.textContent = selectedOption.dataset.status.toUpperCase();
                statusSpan.className = selectedOption.dataset.status === 'overdue' ? 'overdue' : '';
                
                // Pre-fill amount
                amountInput.value = selectedOption.dataset.amount;
            } else {
                billInfo.style.display = 'none';
                amountInput.value = '';
            }
        }
    </script>

</body>

</html>