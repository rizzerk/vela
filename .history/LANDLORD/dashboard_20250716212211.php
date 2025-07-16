<?php
session_start();
require_once '../connection.php';
require_once "../includes/auth/landlord_auth.php";
require_once '../vendor/autoload.php';

$landlord_id = $_SESSION['user_id'] ?? 1;

if ($_POST['action'] ?? '' === 'add_announcement') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $priority = $_POST['priority'];
    
    $stmt = $conn->prepare("INSERT INTO ANNOUNCEMENT (title, content, visible_to, priority, created_by, created_at) VALUES (?, ?, 'tenant', ?, ?, NOW())");
    $stmt->bind_param("sssi", $title, $content, $priority, $landlord_id);
    
    if ($stmt->execute()) {
        $announcement_id = $conn->insert_id;
        
        $recipient_query = "SELECT email, name FROM USERS WHERE role = 'tenant'";
        $recipient_result = $conn->query($recipient_query);
        $recipients = $recipient_result ? $recipient_result->fetch_all(MYSQLI_ASSOC) : [];
        
        if (!empty($recipients)) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'velacinco5@gmail.com';
                $mail->Password   = 'aycm atee woxl lmvj';
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                $mail->setFrom('velacinco5@gmail.com', 'VELA Cinco Rentals');
                $mail->isHTML(true);
                $mail->Subject = 'New Announcement: ' . htmlspecialchars($title);
                
                $email_template = '
                    <h2>New Announcement</h2>
                    <p>Hello {name},</p>
                    <p>A new announcement has been posted:</p>
                    <div style="background:#f8fafc; padding:1.5rem; border-radius:8px; margin:1rem 0;">
                        <h3 style="color:#1666ba; margin-top:0;">' . htmlspecialchars($title) . '</h3>
                        <p>' . nl2br(htmlspecialchars($content)) . '</p>
                        <p style="font-size:0.9rem; color:#64748b;">
                            Priority: ' . ucfirst($priority) . '<br>
                            Posted on: ' . date('F j, Y') . '
                        </p>
                    </div>
                    <p>Please <a href=\"http://localhost/vela/index.php\">log in to your account</a> for more details.</p>
                    <p>Best regards,<br>VELA Cinco Rentals</p>
                ';
                
                foreach ($recipients as $recipient) {
                    try {
                        $mail->clearAddresses();
                        $mail->addAddress($recipient['email'], $recipient['name']);
                        $personalized_email = str_replace('{name}', htmlspecialchars($recipient['name']), $email_template);
                        $mail->Body = $personalized_email;
                        $mail->AltBody = strip_tags($personalized_email);
                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Failed to send announcement to {$recipient['email']}: " . $mail->ErrorInfo);
                        continue;
                    }
                }
            } catch (Exception $e) {
                error_log("Mailer Error: " . $mail->ErrorInfo);
            }
        }
        
        header("Location: dashboard.php");
        exit;
    } else {
        header("Location: dashboard.php?error=1");
        exit;
    }
}

// Get property data
$properties = [];
$total_properties = 0;
$total_vacant = 0;
$total_occupied = 0;

$check_column = "SHOW COLUMNS FROM PROPERTY LIKE 'property_type'";
$column_result = $conn->query($check_column);

if ($column_result && $column_result->num_rows > 0) {
    $property_query = "SELECT property_type, COUNT(*) as count, 
                             SUM(CASE WHEN status = 'vacant' THEN 1 ELSE 0 END) as vacant,
                             SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied
                      FROM PROPERTY 
                      GROUP BY property_type";
    $result = $conn->query($property_query);
    if ($result) {
        $properties = $result->fetch_all(MYSQLI_ASSOC);
    }
} else {
    $basic_query = "SELECT 'All Properties' as property_type, COUNT(*) as count,
                           SUM(CASE WHEN status = 'vacant' THEN 1 ELSE 0 END) as vacant,
                           SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied
                    FROM PROPERTY";
    $result = $conn->query($basic_query);
    if ($result) {
        $properties = $result->fetch_all(MYSQLI_ASSOC);
    }
}

if (!empty($properties)) {
    $total_properties = array_sum(array_column($properties, 'count'));
    $total_vacant = array_sum(array_column($properties, 'vacant'));
    $total_occupied = array_sum(array_column($properties, 'occupied'));
}

// Get financial data
$monthly_rent_query = "SELECT COALESCE(SUM(monthly_rent), 0) as total_rent FROM PROPERTY WHERE status = 'occupied'";
$rent_result = $conn->query($monthly_rent_query);
$monthly_rent = $rent_result ? $rent_result->fetch_assoc()['total_rent'] : 0;

// Get actual rent collected this month
$monthly_collected_query = "
    SELECT COALESCE(SUM(p.amount_paid), 0) as collected
    FROM PAYMENT p 
    JOIN BILL b ON p.bill_id = b.bill_id
    WHERE p.status = 'verified' 
    AND b.bill_type = 'rent'
    AND MONTH(p.submitted_at) = MONTH(NOW()) 
    AND YEAR(p.submitted_at) = YEAR(NOW())
";
$collected_result = $conn->query($monthly_collected_query);
$monthly_collected = $collected_result ? $collected_result->fetch_assoc()['collected'] : 0;

// Get utility payments this month
$monthly_utilities_query = "
    SELECT COALESCE(SUM(p.amount_paid), 0) as utilities
    FROM PAYMENT p 
    JOIN BILL b ON p.bill_id = b.bill_id
    WHERE p.status = 'verified' 
    AND b.bill_type = 'utility'
    AND MONTH(p.submitted_at) = MONTH(NOW()) 
    AND YEAR(p.submitted_at) = YEAR(NOW())
";
$utilities_result = $conn->query($monthly_utilities_query);
$monthly_utilities = $utilities_result ? $utilities_result->fetch_assoc()['utilities'] : 0;

// Get other expenses (penalties, other bills)
$other_expenses_query = "
    SELECT COALESCE(SUM(p.amount_paid), 0) as other_expenses
    FROM PAYMENT p 
    JOIN BILL b ON p.bill_id = b.bill_id
    WHERE p.status = 'verified' 
    AND b.bill_type IN ('penalty', 'other')
    AND MONTH(p.submitted_at) = MONTH(NOW()) 
    AND YEAR(p.submitted_at) = YEAR(NOW())
";
$other_expenses_result = $conn->query($other_expenses_query);
$other_expenses = $other_expenses_result ? $other_expenses_result->fetch_assoc()['other_expenses'] : 0;

// Get yearly data for chart
$yearly_data_query = "
    SELECT 
        MONTH(p.submitted_at) as month,
        b.bill_type,
        SUM(p.amount_paid) as total
    FROM PAYMENT p 
    JOIN BILL b ON p.bill_id = b.bill_id
    WHERE p.status = 'verified' 
    AND YEAR(p.submitted_at) = YEAR(NOW())
    GROUP BY MONTH(p.submitted_at), b.bill_type
    ORDER BY MONTH(p.submitted_at)
";
$yearly_result = $conn->query($yearly_data_query);
$yearly_data = $yearly_result ? $yearly_result->fetch_all(MYSQLI_ASSOC) : [];

// Process yearly data for chart
$monthly_rent_data = array_fill(0, 12, 0);
$monthly_utility_data = array_fill(0, 12, 0);

foreach ($yearly_data as $row) {
    $month_index = $row['month'] - 1;
    if ($row['bill_type'] === 'rent') {
        $monthly_rent_data[$month_index] = (float)$row['total'];
    } elseif ($row['bill_type'] === 'utility') {
        $monthly_utility_data[$month_index] = (float)$row['total'];
    }
}

$total_expenses = $monthly_utilities + $other_expenses;

$property_list_query = "SELECT property_id, title FROM PROPERTY ORDER BY title";
$property_list = $conn->query($property_list_query)->fetch_all(MYSQLI_ASSOC);

$announcement_query = "SELECT title, content, created_at 
                      FROM ANNOUNCEMENT 
                      ORDER BY created_at DESC 
                      LIMIT 1";
$latest_announcement = $conn->query($announcement_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Dashboard - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Mobile navbar styles */
        .mobile-navbar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            justify-content: space-between;
            align-items: center;
        }
        
        .mobile-logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1666ba;
            text-decoration: none;
        }
        
        .mobile-menu-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #1666ba;
            cursor: pointer;
        }
        
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1001;
        }
        
        .mobile-overlay.active {
            display: block;
        }
        
        .mobile-sidebar {
            position: fixed;
            top: 0;
            left: -300px;
            width: 300px;
            height: 100vh;
            background: white;
            z-index: 1002;
            transition: left 0.3s ease;
            overflow-y: auto;
        }
        
        .mobile-sidebar.active {
            left: 0;
        }
        
        .mobile-sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .mobile-sidebar-header h2 {
            color: #1666ba;
            font-size: 1.5rem;
            font-weight: 800;
        }
        
        .mobile-close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #64748b;
            cursor: pointer;
        }
        
        .mobile-nav-menu {
            list-style: none;
            padding: 0;
        }
        
        .mobile-nav-item {
            border-bottom: 1px solid #f1f5f9;
        }
        
        .mobile-nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: #64748b;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .mobile-nav-link:hover,
        .mobile-nav-link.active {
            background: #f8fafc;
            color: #1666ba;
        }
        
        .mobile-nav-link i {
            margin-right: 0.75rem;
            width: 20px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 200px;
            }
        }
        
        @media (max-width: 768px) {
            .mobile-navbar {
                display: flex;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 80px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        .header {
            text-align: left;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 3rem;
            color: #1666ba;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1.2rem;
            color: #475569;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .full-width-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(22, 102, 186, 0.08);
            border: 1px solid rgba(190, 218, 247, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 50px rgba(22, 102, 186, 0.12);
        }
        
        .dashboard-grid .card {
            padding: 1.5rem;
        }
        
        .quick-actions-card {
            background: linear-gradient(135deg, #bedaf7, #7ab3ef);
            color: white;
        }
        
        .quick-actions-card .card-title {
            color: white;
        }
        
        .action-btn {
            display: block;
            width: 100%;
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            padding: 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            text-align: left;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .card-title {
            font-size: 1.1rem;
            color: white;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .properties-status-card {
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
        }
        
        .status-metric {
            display: flex;
            justify-content: space-between;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .status-label {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .status-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
        }
        
        .announcements-card {
            background: linear-gradient(135deg, #7ab3ef, #368ce7);
            color: white;
        }

        .announcements-card .card-title {
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .add-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }
        
        .empty-state-white {
            color: rgba(255,255,255,0.8);
        }
        
        .announcement {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .announcement-date {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .announcement-text {
            font-size: 0.95rem;
            color: white;
            line-height: 1.6;
        }

        /* Financial Report Styles */
        .financial-section {
            margin-bottom: 3rem;
        }
        
        .section-title {
            color: #1666ba;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .filter-section {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .filter-group label {
            color: #1666ba;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .filter-group select {
            padding: 0.5rem 1rem;
            border: 1px solid #bedaf7;
            border-radius: 8px;
            background: white;
            color: #1666ba;
            font-weight: 500;
            min-width: 150px;
        }
        
        .financial-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .financial-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(190, 218, 247, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .financial-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .income-card {
            border-top: 4px solid #10b981;
        }
        
        .expenses-card {
            border-top: 4px solid #f59e0b;
        }
        
        .net-income-card {
            border-top: 4px solid #1666ba;
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            color: #1666ba;
        }
        
        .card-header i {
            font-size: 1.25rem;
        }
        
        .card-header h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
        }
        
        .financial-metric {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .financial-metric:last-child {
            border-bottom: none;
        }
        
        .metric-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .metric-value {
            font-weight: 600;
            color: #1e293b;
        }
        
        .financial-metric.total .metric-label {
            font-weight: 700;
            color: #1666ba;
        }
        
        .financial-metric.total .metric-value {
            font-weight: 700;
            color: #1666ba;
        }
        
        .income-card .financial-metric.total .metric-value {
            color: #10b981;
        }
        
        .expenses-card .financial-metric.total .metric-value {
            color: #f59e0b;
        }
        
        .net-income-card .financial-metric.total .metric-value {
            color: #1666ba;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(190, 218, 247, 0.3);
        }
        
        .chart-container h3 {
            color: #1666ba;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .financial-cards {
                grid-template-columns: 1fr;
            }
            
            .filter-section {
                flex-direction: column;
                gap: 1rem;
            }
            
            .filter-group select {
                width: 100%;
            }
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1666ba;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #bedaf7;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1666ba;
            box-shadow: 0 0 0 3px rgba(22, 102, 186, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-primary {
            background: #1666ba;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            margin-right: 1rem;
            transition: background 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #1454a3;
        }
        
        .btn-secondary {
            background: #64748b;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }
    </style>
</head>
<body>
    <div class="mobile-navbar">
        <a href="dashboard.php" class="mobile-logo">VELA</a>
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileSidebar()"></div>
    
    <div class="mobile-sidebar" id="mobileSidebar">
        <div class="mobile-sidebar-header">
            <h2>VELA</h2>
            <button class="mobile-close-btn" onclick="closeMobileSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="mobile-nav-menu">
            <li class="mobile-nav-item">
                <a href="dashboard.php" class="mobile-nav-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="properties.php" class="mobile-nav-link">
                    <i class="fas fa-building"></i>
                    Properties
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="#" class="mobile-nav-link">
                    <i class="fas fa-chart-line"></i>
                    Financial Reports
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="maintenance-req.php" class="mobile-nav-link">
                    <i class="fas fa-tools"></i>
                    Maintenance Requests
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="#" class="mobile-nav-link">
                    <i class="fas fa-file-alt"></i>
                    Tenant Applications
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="tenant-payments.php" class="mobile-nav-link">
                    <i class="fas fa-receipt"></i>
                    Tenant Payments
                </a>
            </li>
        </ul>
    </div>
    
    <?php include ('../includes/navbar/landlord-sidebar.php'); ?>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
        </div>

        <div class="dashboard-grid">
            <div class="card properties-status-card">
                <h2 class="card-title">Properties & Status</h2>
                <?php if (empty($properties)): ?>
                    <div class="empty-state empty-state-white">
                        <i class="fas fa-home"></i>
                        <p>No properties yet</p>
                    </div>
                <?php else: ?>
                    <div class="status-metric">
                        <span class="status-label">Total Units</span>
                        <span class="status-value"><?= $total_properties ?></span>
                    </div>
                    <div class="status-metric">
                        <span class="status-label">Vacant</span>
                        <span class="status-value"><?= $total_vacant ?></span>
                    </div>
                    <div class="status-metric">
                        <span class="status-label">Occupied</span>
                        <span class="status-value"><?= $total_occupied ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card quick-actions-card">
                <h2 class="card-title">Quick Actions</h2>
                <button class="action-btn" onclick="window.location.href='properties.php'">
                    <i class="fas fa-home"></i> Manage Properties
                </button>
                <button class="action-btn" onclick="window.location.href='maintenance-req.php'">
                    <i class="fas fa-tools"></i> Maintenance Requests
                </button>
                <button class="action-btn" onclick="window.location.href='#'">
                    <i class="fas fa-user-plus"></i> Tenant Applications
                </button>
                <button class="action-btn" onclick="window.location.href='view-dues.php'">
                    <i class="fas fa-credit-card"></i> View Payments
                </button>
            </div>
        </div>
        
        <div class="financial-section">
            <h2 class="section-title">Financial Report</h2>
            
            <div class="filter-section">
                <div class="filter-group">
                    <label for="propertyFilter">Property:</label>
                    <select id="propertyFilter" onchange="updateCharts()">
                        <option value="all">All Properties</option>
                        <?php foreach ($property_list as $property): ?>
                            <option value="<?= $property['property_id'] ?>"><?= htmlspecialchars($property['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="timeFilter">Time Period:</label>
                    <select id="timeFilter" onchange="updateCharts()">
                        <option value="monthly">This Month</option>
                        <option value="quarterly">This Quarter</option>
                        <option value="yearly">This Year</option>
                    </select>
                </div>
            </div>
            
            <div class="financial-cards">
                <!-- Income Card -->
                <div class="financial-card income-card">
                    <div class="card-header">
                        <i class="fas fa-money-bill-wave"></i>
                        <h3>Income</h3>
                    </div>
                    <div class="card-body">
                        <div class="financial-metric">
                            <span class="metric-label">Rent Collected</span>
                            <span class="metric-value">₱<?= number_format($monthly_collected, 2) ?></span>
                        </div>
                        <div class="financial-metric">
                            <span class="metric-label">Expected Rent</span>
                            <span class="metric-value">₱<?= number_format($monthly_rent, 2) ?></span>
                        </div>
                        <div class="financial-metric total">
                            <span class="metric-label">Collection Rate</span>
                            <span class="metric-value"><?= $monthly_rent > 0 ? number_format(($monthly_collected/$monthly_rent)*100, 2) : '0' ?>%</span>
                        </div>
                    </div>
                </div>
                
                <!-- Expenses Card -->
                <div class="financial-card expenses-card">
                    <div class="card-header">
                        <i class="fas fa-receipt"></i>
                        <h3>Expenses</h3>
                    </div>
                    <div class="card-body">
                        <div class="financial-metric">
                            <span class="metric-label">Utilities</span>
                            <span class="metric-value">₱<?= number_format($monthly_utilities, 2) ?></span>
                        </div>
                        <div class="financial-metric">
                            <span class="metric-label">Other Expenses</span>
                            <span class="metric-value">₱<?= number_format($other_expenses, 2) ?></span>
                        </div>
                        <div class="financial-metric total">
                            <span class="metric-label">Total Expenses</span>
                            <span class="metric-value">₱<?= number_format($total_expenses, 2) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Net Income Card -->
                <div class="financial-card net-income-card">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i>
                        <h3>Net Income</h3>
                    </div>
                    <div class="card-body">
                        <div class="financial-metric">
                            <span class="metric-label">Gross Income</span>
                            <span class="metric-value">₱<?= number_format($monthly_collected, 2) ?></span>
                        </div>
                        <div class="financial-metric">
                            <span class="metric-label">Total Expenses</span>
                            <span class="metric-value">₱<?= number_format($total_expenses, 2) ?></span>
                        </div>
                        <div class="financial-metric total">
                            <span class="metric-label">Net Profit</span>
                            <span class="metric-value">₱<?= number_format($monthly_collected - $total_expenses, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>Monthly Financial Overview</h3>
                <canvas id="yearlyChart" height="300"></canvas>
            </div>
        </div>
        
        <!-- Announcements Section -->
        <div class="full-width-section">
            <div class="card announcements-card">
                <h2 class="card-title">
                    Latest Announcement
                    <button class="add-btn" onclick="openAnnouncementModal()">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </h2>
                <?php if (!$latest_announcement): ?>
                    <div class="empty-state empty-state-white">
                        <i class="fas fa-bullhorn"></i>
                        <p>No announcements</p>
                    </div>
                <?php else: ?>
                    <div class="announcement">
                        <div class="announcement-date"><?= date('F j, Y', strtotime($latest_announcement['created_at'])) ?></div>
                        <div class="announcement-text">
                            <strong><?= htmlspecialchars($latest_announcement['title']) ?></strong><br>
                            <?= htmlspecialchars($latest_announcement['content']) ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="announcements.php" style="color: rgba(255,255,255,0.9); text-decoration: underline; font-size: 0.9rem;">
                        View All Announcements
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Announcement Modal -->
    <div class="modal" id="announcementModal">
        <div class="modal-content">
            <h3 style="color: #1666ba; margin-bottom: 1.5rem;">Add Announcement</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_announcement">
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" required></textarea>
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary">Add Announcement</button>
                <button type="button" class="btn-secondary" onclick="closeAnnouncementModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        let yearlyChart;
        
        function updateCharts() {
            const filter = document.getElementById('propertyFilter').value;
            
            if (yearlyChart) {
                yearlyChart.destroy();
            }
            
            createYearlyChart();
        }
        
        function createYearlyChart() {
            const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
            const rentData = <?= json_encode($monthly_rent_data) ?>;
            const utilityData = <?= json_encode($monthly_utility_data) ?>;
            
            yearlyChart = new Chart(yearlyCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Rent Collected',
                        data: rentData,
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    }, {
                        label: 'Utilities',
                        data: utilityData,
                        backgroundColor: '#f59e0b',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                afterBody: function(context) {
                                    const datasetIndex = context[0].datasetIndex;
                                    const dataIndex = context[0].dataIndex;
                                    let netIncome = 0;
                                    
                                    if (datasetIndex === 0) {
                                        const rent = context[0].raw;
                                        const utilities = yearlyChart.data.datasets[1].data[dataIndex] || 0;
                                        netIncome = rent - utilities;
                                    }
                                    
                                    return datasetIndex === 0 ? ['------------------', `Net Income: ₱${netIncome.toLocaleString()}`] : [];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            },
                            grid: {
                                color: '#e2e8f0'
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }
        
        createYearlyChart();
        
        function toggleMobileMenu() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileOverlay');
            sidebar.classList.add('active');
            overlay.classList.add('active');
        }
        
        function closeMobileSidebar() {
            const sidebar = document.getElementById('mobileSidebar');
            const overlay = document.getElementById('mobileOverlay');
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
        
        function openAnnouncementModal() {
            document.getElementById('announcementModal').style.display = 'block';
        }
        
        function closeAnnouncementModal() {
            document.getElementById('announcementModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('announcementModal');
            if (event.target == modal) {
                closeAnnouncementModal();
            }
        }
    </script>
</body>
</html>