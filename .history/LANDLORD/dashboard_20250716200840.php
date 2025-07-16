<?php
session_start();
require_once '../connection.php';
require_once "../includes/auth/landlord_auth.php";
require_once '../vendor/autoload.php'; // Make sure PHPMailer is installed via Composer


$landlord_id = $_SESSION['user_id'] ?? 1;

if ($_POST['action'] ?? '' === 'add_announcement') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $priority = $_POST['priority'];
    
    $stmt = $conn->prepare("INSERT INTO ANNOUNCEMENT (title, content, visible_to, priority, created_by, created_at) VALUES (?, ?, 'tenant', ?, ?, NOW())");
    $stmt->bind_param("sssi", $title, $content, $priority, $landlord_id);
    
    if ($stmt->execute()) {
        $announcement_id = $conn->insert_id;
        
        $recipient_query = "SELECT email, name FROM USERS WHERE ";
        
        switch ($visible_to) {
            case 'all':
                $recipient_query .= "role IN ('tenant', 'general_user')";
                break;
            case 'tenant':
                $recipient_query .= "role = 'tenant'";
                break;
            case 'landlord':
                $recipient_query .= "role = 'landlord'";
                break;
        }
        
        $recipient_result = $conn->query($recipient_query);
        $recipients = $recipient_result ? $recipient_result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Send email to each recipient if there are any
        if (!empty($recipients)) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'velacinco5@gmail.com'; // SMTP username
                $mail->Password   = 'aycm atee woxl lmvj'; // SMTP password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                // Common email settings
                $mail->setFrom('velacinco5@gmail.com', 'VELA Cinco Rentals');
                $mail->isHTML(true);
                $mail->Subject = 'New Announcement: ' . htmlspecialchars($title);
                
                // Email template
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
                
                // Send to each recipient
                foreach ($recipients as $recipient) {
                    try {
                        $mail->clearAddresses();
                        $mail->addAddress($recipient['email'], $recipient['name']);
                        
                        // Personalize the email
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
        // Handle database error
        header("Location: dashboard.php?error=1");
        exit;
    }
}

// Rest of your existing dashboard code...
// Rest of your existing dashboard code...
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

// Get financial data from database
$financial_query = "
    SELECT 
        COALESCE(SUM(CASE WHEN p.status = 'occupied' THEN p.monthly_rent ELSE 0 END), 0) as total_rent,
        COALESCE(SUM(CASE WHEN pay.payment_type = 'rent' AND MONTH(pay.payment_date) = MONTH(NOW()) AND YEAR(pay.payment_date) = YEAR(NOW()) THEN pay.amount ELSE 0 END), 0) as monthly_collected,
        COALESCE(SUM(CASE WHEN pay.payment_type = 'utilities' AND MONTH(pay.payment_date) = MONTH(NOW()) AND YEAR(pay.payment_date) = YEAR(NOW()) THEN pay.amount ELSE 0 END), 0) as monthly_utilities,
        COALESCE(SUM(CASE WHEN mr.status = 'completed' AND MONTH(mr.completed_date) = MONTH(NOW()) AND YEAR(mr.completed_date) = YEAR(NOW()) THEN mr.cost ELSE 0 END), 0) as monthly_maintenance
    FROM PROPERTY p
    LEFT JOIN PAYMENT pay ON p.property_id = pay.property_id
    LEFT JOIN MAINTENANCE_REQUEST mr ON p.property_id = mr.property_id
";

$financial_result = $conn->query($financial_query);
$financial_data = $financial_result ? $financial_result->fetch_assoc() : [];

$monthly_rent = $financial_data['total_rent'] ?? 0;
$monthly_collected = $financial_data['monthly_collected'] ?? 0;
$monthly_utilities = $financial_data['monthly_utilities'] ?? 0;
$monthly_maintenance = $financial_data['monthly_maintenance'] ?? 0;

$monthly_net = $monthly_collected - $monthly_utilities - $monthly_maintenance;

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
            background-color: #f6f6f6;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .mobile-navbar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1001;
            justify-content: space-between;
            align-items: center;
        }
        
        .mobile-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1666ba;
            text-decoration: none;
        }
        
        .mobile-menu-toggle {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #1666ba;
            cursor: pointer;
        }
        
        .mobile-sidebar {
            position: fixed;
            right: -280px;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #1666ba 0%, #368ce7 100%);
            padding: 2rem 0;
            z-index: 1002;
            box-shadow: -2px 0 15px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
        }
        
        .mobile-sidebar.active {
            right: 0;
        }
        
        .mobile-sidebar-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 0 1rem;
            position: relative;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 1.5rem;
        }
        
        .mobile-sidebar-header h2 {
            color: white;
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 2px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .mobile-close-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            border: none;
        }
        
        .mobile-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }
        
        .mobile-nav-menu {
            list-style: none;
        }
        
        .mobile-nav-item {
            margin-bottom: 0.5rem;
        }
        
        .mobile-nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 25px 0 0 25px;
            margin-left: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .mobile-nav-link:hover,
        .mobile-nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(-5px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .mobile-nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .mobile-nav-link::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: white;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .mobile-nav-link.active::before,
        .mobile-nav-link:hover::before {
            transform: scaleY(1);
        }
        
        .mobile-nav-link i {
            margin-right: 1rem;
            width: 18px;
            font-size: 1.1rem;
        }
        
        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .mobile-overlay.active {
            opacity: 1;
            visibility: visible;
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
                padding-top: 80px;
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
        
        .financial-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .financial-grid .card {
            padding: 1.5rem;
        }
        
        #yearlyChart {
            max-height: 200px;
            width: 100% !important;
        }
        
        .filter-section {
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .filter-section select {
            padding: 0.5rem;
            border: 1px solid #bedaf7;
            border-radius: 8px;
            background: white;
            color: #1666ba;
            font-weight: 600;
        }
        
        .financial-card {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .financial-card .card-title {
            color: white;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
            border: 1px solid rgba(190, 218, 247, 0.3);
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
        
        .action-btn:last-child {
            margin-bottom: 0;
        }
        
        .action-btn i {
            margin-right: 0.5rem;
            width: 16px;
        }

        .card-title {
            font-size: 1.1rem;
            color: #1666ba;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .metric:last-child {
            margin-bottom: 0;
        }

        .metric.total {
            background: #1666ba;
            color: white;
            font-weight: 600;
        }

        .metric-label {
            font-size: 0.95rem;
            color: #64748b;
        }

        .metric.total .metric-label {
            color: white;
        }

        .metric-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1666ba;
        }

        .metric.total .metric-value {
            color: white;
        }

        .status-card {
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
        }

        .status-card .card-title {
            color: white;
        }

        .status-metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .status-metric:last-child {
            margin-bottom: 0;
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

        .properties-status-card {
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
        }
        
        .properties-status-card .card-title {
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
        
        .add-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1666ba;
            font-weight: 600;
        }
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #bedaf7;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .btn-primary {
            background: #1666ba;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .btn-secondary {
            background: #64748b;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            margin-left: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #bedaf7;
        }
        
        .empty-state p {
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .empty-state-white {
            color: rgba(255,255,255,0.8);
        }
        
        .empty-state-white i {
            color: rgba(255,255,255,0.5);
        }
        
        .empty-state-white p {
            color: rgba(255,255,255,0.7);
        }

        .announcement {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .announcement:last-child {
            margin-bottom: 0;
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

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 200px;
                padding: 1.5rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            
            .announcements-card {
                grid-column: span 2;
            }
            
            .header h1 {
                font-size: 2.5rem;
            }
            
            .dashboard-grid .card {
                padding: 1.25rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .header p {
                font-size: 1rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
            }
            
            .announcements-card {
                grid-column: span 2;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .dashboard-grid .card {
                padding: 1rem;
            }
            
            .filter-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .filter-section select {
                width: 100%;
            }
            
            .financial-grid {
                margin-bottom: 2rem;
            }
            
            .financial-grid .card {
                padding: 1rem;
            }
            
            #yearlyChart {
                max-height: 200px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 0.75rem;
            }
            
            .header {
                margin-bottom: 2rem;
            }
            
            .header h1 {
                font-size: 1.75rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 0.4rem;
            }
            
            .announcements-card {
                grid-column: span 1;
            }
            
            .card {
                padding: 1.25rem;
                border-radius: 12px;
            }
            
            .dashboard-grid .card {
                padding: 0.75rem;
            }
            
            .card-title {
                font-size: 0.9rem;
                margin-bottom: 1rem;
            }
            
            .metric, .status-metric {
                padding: 0.6rem;
            }
            
            .metric-label, .status-label {
                font-size: 0.8rem;
            }
            
            .metric-value, .status-value {
                font-size: 0.95rem;
            }
            
            .action-btn {
                padding: 0.6rem;
                font-size: 0.8rem;
            }
            
            .announcement {
                padding: 1.25rem;
            }
            
            .announcement-date {
                font-size: 0.75rem;
            }
            
            .announcement-text {
                font-size: 0.9rem;
            }
            
            .modal-content {
                margin: 10% auto;
                padding: 1.5rem;
                width: 95%;
            }
            
            .form-group input, .form-group textarea, .form-group select {
                padding: 0.6rem;
            }
            
            .btn-primary, .btn-secondary {
                padding: 0.6rem 1.25rem;
                font-size: 0.9rem;
            }
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
            <li class="mobile-nav-item">
                <a href="tenant-payments.php" class="mobile-nav-link">
                    <i class="fas fa-history"></i>
                    Tenant History
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="#" class="mobile-nav-link">
                    <i class="fas fa-user"></i>
                    Landlord Profile
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
        
        <!-- Financial Summary Section -->
        <div class="filter-section">
            <label for="propertyFilter" style="color: #1666ba; font-weight: 600;">Filter by Property:</label>
            <select id="propertyFilter" onchange="updateCharts()">
                <option value="all">All Properties</option>
                <?php foreach ($property_list as $property): ?>
                    <option value="<?= $property['property_id'] ?>"><?= htmlspecialchars($property['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="financial-grid">
            <div class="card">
                <h2 class="card-title">Financial Summary</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div class="metric">
                        <span class="metric-label">Expected Rent</span>
                        <span class="metric-value">₱<?= number_format($monthly_rent, 2) ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Collected</span>
                        <span class="metric-value">₱<?= number_format($monthly_collected, 2) ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Utilities</span>
                        <span class="metric-value">₱<?= number_format($monthly_utilities, 2) ?></span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Maintenance</span>
                        <span class="metric-value">₱<?= number_format($monthly_maintenance, 2) ?></span>
                    </div>
                    <div class="metric total">
                        <span class="metric-label">Net Income</span>
                        <span class="metric-value">₱<?= number_format($monthly_net, 2) ?></span>
                    </div>
                </div>
                <canvas id="yearlyChart" width="400" height="200"></canvas>
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
            yearlyChart = new Chart(yearlyCtx, {
                type: 'bar',
                data: {
                    labels: ['Current Month'],
                    datasets: [{
                        label: 'Rent Collected',
                        data: [<?= $monthly_collected ?>],
                        backgroundColor: '#10b981'
                    }, {
                        label: 'Utilities',
                        data: [<?= $monthly_utilities ?>],
                        backgroundColor: '#f59e0b'
                    }, {
                        label: 'Maintenance',
                        data: [<?= $monthly_maintenance ?>],
                        backgroundColor: '#ef4444'
                    }, {
                        label: 'Net Income',
                        data: [<?= $monthly_net ?>],
                        backgroundColor: '#1666ba'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
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