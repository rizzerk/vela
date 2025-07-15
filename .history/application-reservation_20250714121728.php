<?php
session_start();
require_once 'connection.php';

if ($_SESSION['role'] !== 'general_user') {
    header('Location: index.php');
    exit;
}else{
    header('Location: index.php');
}


$property_id = $_GET['property_id'];
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $conn->prepare("SELECT * FROM PROPERTY WHERE property_id = ? AND status = 'vacant'");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$property) {
    $error = "Property not available";
}

$existing = false;
if ($property) {
    $stmt = $conn->prepare("SELECT 1 FROM APPLICATIONS WHERE property_id = ? AND applicant_id = ? AND status IN ('pending', 'approved')");
    $stmt->bind_param("ii", $property_id, $user_id);
    $stmt->execute();
    $existing = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

// Process form
if ($_POST && !$existing && $property) {
    $num_tenants = $_POST['num_tenants'];
    $co_tenants = $_POST['co_tenants'];
    
    $docs = [];
    $upload_dir = 'uploads/';
    
    foreach (['govt_id', 'proof_income', 'proof_billing'] as $doc) {
        if ($_FILES[$doc]['error'] == 0) {
            $filename = $user_id . '_' . $doc . '_' . time() . '.' . pathinfo($_FILES[$doc]['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES[$doc]['tmp_name'], $upload_dir . $filename)) {
                $docs[$doc] = $upload_dir . $filename;
            }
        }
    }
    
    if (count($docs) == 3) {
        $stmt = $conn->prepare("INSERT INTO APPLICATIONS (property_id, applicant_id, num_of_tenants, co_tenants, documents, submitted_at, status) VALUES (?, ?, ?, ?, ?, NOW(), 'pending')");
        $stmt->bind_param("iiiss", $property_id, $user_id, $num_tenants, $co_tenants, json_encode($docs));
        if ($stmt->execute()) {
            $success = "Application submitted successfully!";
            $existing = true;
        }
        $stmt->close();
    } else {
        $error = "Please upload all required documents";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Property Application</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        .form { max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 0.8rem 2rem; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 1rem; }
        .success { color: green; margin-bottom: 1rem; }
        .existing { background: #fff3cd; padding: 1rem; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>
    <div class="form">
        <h1>Property Application</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($property && !$existing): ?>
            <h3><?php echo htmlspecialchars($property['title']); ?></h3>
            <p>₱<?php echo number_format($property['monthly_rent'], 2); ?>/month</p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Number of Accompanying Tenants:</label>
                    <select name="num_tenants" required>
                        <option value="0">Just myself</option>
                        <option value="1">1 additional</option>
                        <option value="2">2 additional</option>
                        <option value="3">3 additional</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Full Names of All Tenants:</label>
                    <textarea name="co_tenants" required placeholder="Enter all tenant names, separated by commas"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Government ID:</label>
                    <input type="file" name="govt_id" accept=".pdf,.jpg,.png" required>
                </div>
                
                <div class="form-group">
                    <label>Proof of Income:</label>
                    <input type="file" name="proof_income" accept=".pdf,.jpg,.png" required>
                </div>
                
                <div class="form-group">
                    <label>Proof of Billing:</label>
                    <input type="file" name="proof_billing" accept=".pdf,.jpg,.png" required>
                </div>
                
                <button type="submit">Submit Application</button>
            </form>
        <?php elseif ($existing): ?>
            <div class="existing">
                <h3>Application Already Submitted</h3>
                <p>You have already submitted an application for this property.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>