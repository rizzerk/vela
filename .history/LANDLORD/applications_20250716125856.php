<?php
session_start();
require_once '../connection.php';
require_once '../vendor/autoload.php'; // Load PHPMailer

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to send approval email
function sendApprovalEmail($applicant_name, $applicant_email, $property_title) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'velacinco5@gmail.com';
        $mail->Password   = 'aycm atee woxl lmvj';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('velacinco5@gmail.com', 'VELA Cinco Rentals');
        $mail->addAddress($applicant_email, $applicant_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Application Has Been Approved';
        
        $mail->Body = "
            <h2>Congratulations, {$applicant_name}!</h2>
            <p>We're pleased to inform you that your application for <strong>{$property_title}</strong> has been approved.</p>
            <p>You are now officially a tenant. Please log in to your account to view your lease details and next steps.</p>
            <p>If you have any questions, please don't hesitate to contact us.</p>
            <p>Welcome to VELA Cinco Rentals!</p>
        ";

        $mail->AltBody = "Congratulations, {$applicant_name}!\n\n" .
            "We're pleased to inform you that your application for {$property_title} has been approved.\n\n" .
            "You are now officially a tenant. Please log in to your account to view your lease details and next steps.\n\n" .
            "If you have any questions, please don't hesitate to contact us.\n\n" .
            "Welcome to VELA Cinco Rentals!";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Approval email error for {$applicant_email}: " . $e->getMessage());
        return false;
    }
}

// Function to send rejection email
function sendRejectionEmail($applicant_name, $applicant_email, $property_title) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'velacinco5@gmail.com';
        $mail->Password   = 'aycm atee woxl lmvj';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('velacinco5@gmail.com', 'VELA Cinco Rentals');
        $mail->addAddress($applicant_email, $applicant_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Update on Your Application for ' . $property_title;
        
        $mail->Body = "
            <h2>Application Update</h2>
            <p>Dear {$applicant_name},</p>
            <p>We regret to inform you that your application for <strong>{$property_title}</strong> has not been approved at this time.</p>
            <p>We appreciate your interest and encourage you to apply for other properties in our system that may better match your requirements.</p>
            <p>Thank you for considering VELA Cinco Rentals.</p>
        ";

        $mail->AltBody = "Application Update\n\n" .
            "Dear {$applicant_name},\n\n" .
            "We regret to inform you that your application for {$property_title} has not been approved at this time.\n\n" .
            "We appreciate your interest and encourage you to apply for other properties in our system that may better match your requirements.\n\n" .
            "Thank you for considering VELA Cinco Rentals.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Rejection email error for {$applicant_email}: " . $e->getMessage());
        return false;
    }
}

// Check if user is logged in and is landlord
if (!isset($_SESSION['loggedin'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != 'landlord') {
    header("Location: ../index.php");
    exit();
} 

$applications = [];

// Get all applications for landlord's properties
$stmt = $conn->prepare("SELECT a.*, p.title, p.address, u.name as applicant_name, u.email, u.phone 
                       FROM APPLICATIONS a 
                       JOIN PROPERTY p ON a.property_id = p.property_id 
                       JOIN USERS u ON a.applicant_id = u.user_id 
                       ORDER BY a.submitted_at DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}
$stmt->close();

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $application_id = $_POST['application_id'];
    $status = $_POST['update_status'];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // First get application details for email
        $appStmt = $conn->prepare("SELECT a.*, p.title, u.name as applicant_name, u.email 
                                 FROM APPLICATIONS a
                                 JOIN PROPERTY p ON a.property_id = p.property_id
                                 JOIN USERS u ON a.applicant_id = u.user_id
                                 WHERE a.application_id = ?");
        $appStmt->bind_param("i", $application_id);
        $appStmt->execute();
        $appResult = $appStmt->get_result();
        $application = $appResult->fetch_assoc();
        $appStmt->close();
        
        // Update application status
        $stmt = $conn->prepare("UPDATE APPLICATIONS SET status = ?, approved_at = ? WHERE application_id = ?");
        $approved_at = $status == 'approved' ? date('Y-m-d H:i:s') : NULL;
        $stmt->bind_param("ssi", $status, $approved_at, $application_id);
        $stmt->execute();
        $stmt->close();
        
        // If approved, create lease and update user role
        if ($status == 'approved') {
            // Create lease (1 year by default)
            $start_date = date('Y-m-d');
            
            
            $leaseStmt = $conn->prepare("INSERT INTO LEASE (tenant_id, property_id, start_date, active) 
                                        VALUES (?, ?, ?, 1)");
            $leaseStmt->bind_param("iis", $application['applicant_id'], $application['property_id'], $start_date, );
            $leaseStmt->execute();
            $lease_id = $leaseStmt->insert_id;
            $leaseStmt->close();
            
            // Update property status to occupied
            $propStmt = $conn->prepare("UPDATE PROPERTY SET status = 'occupied' WHERE property_id = ?");
            $propStmt->bind_param("i", $application['property_id']);
            $propStmt->execute();
            $propStmt->close();
            
            // Update user role to tenant
            $userStmt = $conn->prepare("UPDATE USERS SET role = 'tenant' WHERE user_id = ?");
            $userStmt->bind_param("i", $application['applicant_id']);
            $userStmt->execute();
            $userStmt->close();
            
            // Create initial bill with billing period
            $rentStmt = $conn->prepare("SELECT monthly_rent FROM PROPERTY WHERE property_id = ?");
            $rentStmt->bind_param("i", $application['property_id']);
            $rentStmt->execute();
            $rentResult = $rentStmt->get_result();
            $rent = $rentResult->fetch_assoc();
            $rentStmt->close();
            
            $billStmt = $conn->prepare("INSERT INTO BILL 
                (lease_id, amount, due_date, status, description, 
                 billing_period_start, billing_period_end, bill_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $bill_status = 'unpaid';
            $description = 'Monthly Rent';
            $bill_type = 'rent';
            $due_date = date('Y-m-d', strtotime('+5 days'));
            $period_start = date('Y-m-d');
            $period_end = date('Y-m-d', strtotime('+1 month'));
            
            $billStmt->bind_param("idssssss", 
                $lease_id, 
                $rent['monthly_rent'], 
                $due_date,
                $bill_status,
                $description,
                $period_start,
                $period_end,
                $bill_type
            );
            $billStmt->execute();
            $billStmt->close();
            
            // Send approval email
            sendApprovalEmail(
                $application['applicant_name'],
                $application['email'],
                $application['title']
            );
        } else if ($status == 'rejected') {
            // Send rejection email
            sendRejectionEmail(
                $application['applicant_name'],
                $application['email'],
                $application['title']
            );
        }
        
        // Commit transaction
        $conn->commit();
        
        // Refresh the page to show updated status
        header("Location: applications.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Error updating application: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - VELA Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            background-color: #f8fafc;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        h1 {
            font-size: 2.5rem;
            color: #1666ba;
            margin-bottom: 2rem;
        }
        
        .applications-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .applications-table th, 
        .applications-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .applications-table th {
            background-color: #1666ba;
            color: white;
            font-weight: 600;
        }
        
        .applications-table tr:hover {
            background-color: #f5f9ff;
        }
        
        .status-pending {
            color: #e65100;
            font-weight: 600;
        }
        
        .status-approved {
            color: #2e7d32;
            font-weight: 600;
        }
        
        .status-rejected {
            color: #c62828;
            font-weight: 600;
        }
        
        .action-form {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .approve-btn {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .approve-btn:hover {
            background-color: #c8e6c9;
        }
        
        .reject-btn {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .reject-btn:hover {
            background-color: #ffcdd2;
        }
        
        .view-btn {
            background-color: #e3f2fd;
            color: #1565c0;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
        }
        
        .view-btn:hover {
            background-color: #bbdefb;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #c62828;
        }
        
        @media (max-width: 768px) {
            .applications-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<?php include ('../includes/navbar/landlord-sidebar.php'); ?>
    
    <div class="container">
        <h1>Rental Applications</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (empty($applications)): ?>
            <p>No applications have been submitted yet.</p>
        <?php else: ?>
            <table class="applications-table">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Applicant</th>
                        <th>Contact</th>
                        <th>Income</th>
                        <th>Tenants</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $application): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($application['title']); ?></strong><br>
                                <?php echo htmlspecialchars($application['address']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($application['applicant_name']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($application['email']); ?><br>
                                <?php echo htmlspecialchars($application['phone']); ?>
                            </td>
                            <td>₱<?php echo number_format($application['monthly_income'], 2); ?></td>
                            <td><?php echo htmlspecialchars($application['num_of_tenants']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($application['submitted_at'])); ?></td>
                            <td class="status-<?php echo htmlspecialchars($application['status']); ?>">
                                <?php echo ucfirst($application['status']); ?>
                            </td>
                            <td>
                                <?php if ($application['status'] == 'pending'): ?>
                                  <form method="POST" class="action-form">
    <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
    <button type="submit" name="update_status" value="approved" class="action-btn approve-btn">Approve</button>
    <button type="submit" name="update_status" value="rejected" class="action-btn reject-btn">Reject</button>
</form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>