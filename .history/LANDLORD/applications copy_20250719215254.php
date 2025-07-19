<?php
session_start();
require_once '../connection.php';
require_once "../includes/auth/landlord_auth.php";
require_once '../vendor/autoload.php'; // Load PHPMailer

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to send approval email
function sendApprovalEmail($applicant_name, $applicant_email, $property_title, $monthly_rent) {
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
        $mail->Subject = 'Application Approved for ' . $property_title;
        
        $mail->Body = "
            <h2>Congratulations, {$applicant_name}!</h2>
            <p>Your application for <strong>{$property_title}</strong> has been approved!</p>
            
            <h3>Next Steps:</h3>
            <ol>
                <li>Please contact the property owner to schedule a contract signing</li>
                <li>Pay the security deposit (amount to be discussed with the owner)</li>
                <li>Once the deposit is confirmed, your lease will be activated</li>
            </ol>
            
            <p><strong>Monthly Rent:</strong> ₱" . number_format($monthly_rent, 2) . "</p>
            
            <p>You will receive another email once your lease is officially activated.</p>
            
            <p>If you have any questions, please contact the property owner directly.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Approval email error: " . $e->getMessage());
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

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Rejection email error for {$applicant_email}: " . $e->getMessage());
        return false;
    }
}

function sendLeaseActivationEmail($applicant_name, $applicant_email, $property_title, $start_date, $monthly_rent) {
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
        $mail->Subject = 'Lease Activated for ' . $property_title;
        
        $mail->Body = "
            <h2>Welcome, {$applicant_name}!</h2>
            <p>Your lease for <strong>{$property_title}</strong> has been officially activated.</p>
            
            <h3>Lease Details:</h3>
            <ul>
                <li><strong>Start Date:</strong> " . date('F j, Y', strtotime($start_date)) . "</li>
                <li><strong>End Date:</strong> Ongoing (will be set when lease is terminated)</li>
                <li><strong>Monthly Rent:</strong> ₱" . number_format($monthly_rent, 2) . "</li>
            </ul>
            
            <p>Your first rent payment of ₱" . number_format($monthly_rent, 2) . " is due on " . date('F j, Y', strtotime('+5 days')) . ".</p>
            
            <p>You can now access your tenant portal to view your lease details and payment history.</p>
            
            <p>Welcome to VELA Cinco Rentals!</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Lease activation email error: " . $e->getMessage());
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
$stmt = $conn->prepare("SELECT a.*, p.title, p.address, u.name as applicant_name, u.email, u.phone, p.monthly_rent
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
        
        // Get application details for email
        $appStmt = $conn->prepare("SELECT a.*, p.title, u.name as applicant_name, u.email, p.monthly_rent, p.property_id
                                 FROM APPLICATIONS a
                                 JOIN PROPERTY p ON a.property_id = p.property_id
                                 JOIN USERS u ON a.applicant_id = u.user_id
                                 WHERE a.application_id = ?");
        $appStmt->bind_param("i", $application_id);
        $appStmt->execute();
        $appResult = $appStmt->get_result();
        $application = $appResult->fetch_assoc();
        $appStmt->close();
        
        if ($status == 'approved') {
            // First approval - just mark as approved
            $stmt = $conn->prepare("UPDATE APPLICATIONS SET status = 'approved', approved_at = NOW() WHERE application_id = ?");
            $stmt->bind_param("i", $application_id);
            $stmt->execute();
            $stmt->close();
            
            // Reserve the property
            $propStmt = $conn->prepare("UPDATE PROPERTY SET status = 'occupied' WHERE property_id = ?");
            $propStmt->bind_param("i", $application['property_id']);
            $propStmt->execute();
            $propStmt->close();
            
            // Send approval email with next steps
            sendApprovalEmail(
                $application['applicant_name'],
                $application['email'],
                $application['title'],
                $application['monthly_rent']
            );
            
        } elseif ($status == 'deposit_paid') {
            // Deposit paid - create lease and activate tenant
            
            // Create lease with NULL end date
            $start_date = date('Y-m-d');
            
            $leaseStmt = $conn->prepare("INSERT INTO LEASE (tenant_id, property_id, start_date, end_date, active) 
                                        VALUES (?, ?, ?, NULL, 1)");
            $leaseStmt->bind_param("iis", $application['applicant_id'], $application['property_id'], $start_date);
            $leaseStmt->execute();
            $lease_id = $leaseStmt->insert_id;
            $leaseStmt->close();
            
            // Mark deposit as paid
            $depositStmt = $conn->prepare("UPDATE APPLICATIONS SET deposit_paid = 1 WHERE application_id = ?");
            $depositStmt->bind_param("i", $application_id);
            $depositStmt->execute();
            $depositStmt->close();
            
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
            
            // Create initial bill
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
            
            // Send lease activation email
            sendLeaseActivationEmail(
                $application['applicant_name'],
                $application['email'],
                $application['title'],
                $start_date,
                $application['monthly_rent']
            );
        } elseif ($status == 'rejected') {
            // Handle rejection
            $stmt = $conn->prepare("UPDATE APPLICATIONS SET status = 'rejected' WHERE application_id = ?");
            $stmt->bind_param("i", $application_id);
            $stmt->execute();
            $stmt->close();
            
            sendRejectionEmail(
                $application['applicant_name'],
                $application['email'],
                $application['title']
            );
        }
        
        $conn->commit();
        header("Location: applications.php");
        exit();
    } catch (Exception $e) {
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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: margin-left 0.3s;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
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
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .applications-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<?php include ('../includes/navbar/landlord-sidebar.php'); ?>
    
    <div class="main-content">
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
                            <th>Document</th>
                            <th>Status</th>
                            <th>Deposit Paid</th>
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
                            <td>
                                <?php if ($application['document_path']): ?>
                                    <a href="download.php?file=<?php echo urlencode(basename($application['document_path'])); ?>&app_id=<?php echo $application['application_id']; ?>" 
                                       class="view-btn">
                                        Download Document
                                    </a>
                                <?php else: ?>
                                    No document
                                <?php endif; ?>
                            </td>
                            <td class="status-<?php echo htmlspecialchars($application['status']); ?>">
                                <?php echo ucfirst($application['status']); ?>
                            </td>
                            <td>
                                <?php if ($application['status'] == 'approved'): ?>
                                    <?php echo $application['deposit_paid'] ? 'Yes' : 'No'; ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($application['status'] == 'pending'): ?>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                                        <button type="submit" name="update_status" value="approved" class="action-btn approve-btn">Approve</button>
                                        <button type="submit" name="update_status" value="rejected" class="action-btn reject-btn">Reject</button>
                                    </form>
                                <?php elseif ($application['status'] == 'approved' && !$application['deposit_paid']): ?>
                                    <form method="POST">
                                        <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                                        <button type="submit" name="update_status" value="deposit_paid" class="action-btn approve-btn">Confirm Deposit Paid</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>