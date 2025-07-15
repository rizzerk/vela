<?php
session_start();
require_once '../connection.php';

$landlord_id = $_SESSION['user_id'] ?? 1;
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




if ($_POST['action'] ?? '' === 'add_announcement') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $visible_to = $_POST['visible_to'];
    $priority = $_POST['priority'];
    
    $insert_query = "INSERT INTO ANNOUNCEMENT (title, content, visible_to, priority, created_by, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssssi", $title, $content, $visible_to, $priority, $landlord_id);
    $stmt->execute();
}


// Financial data
$current_month = date('Y-m');
$current_year = date('Y');

// Monthly revenue from rent (simplified for existing schema)
$monthly_rent_query = "SELECT COALESCE(SUM(monthly_rent), 0) as monthly_rent FROM PROPERTY WHERE status = 'occupied'";
$result = $conn->query($monthly_rent_query);
$monthly_rent = $result ? $result->fetch_assoc()['monthly_rent'] : 0;

// Sample data for utilities and maintenance
$monthly_utilities = $monthly_rent * 0.15; // 15% of rent
$monthly_maintenance = $monthly_rent * 0.1; // 10% of rent

// Yearly calculations
$yearly_rent = $monthly_rent * 12;
$yearly_utilities = $monthly_utilities * 12;
$yearly_maintenance = $monthly_maintenance * 12;

$monthly_net = $monthly_rent - $monthly_maintenance;
$yearly_net = $yearly_rent - $yearly_maintenance;

// Get all properties for filter dropdown
$property_list_query = "SELECT property_id, title FROM PROPERTY ORDER BY title";
$property_list = $conn->query($property_list_query)->fetch_all(MYSQLI_ASSOC);

$announcement_query = "SELECT title, content, created_at 
                      FROM ANNOUNCEMENT 
                      WHERE visible_to IN ('landlord', 'all') 
                      ORDER BY created_at DESC 
                      LIMIT 3";
$announcements = $conn->query($announcement_query)->fetch_all(MYSQLI_ASSOC);

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
        
        .financial-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
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
            grid-column: span 2;
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
        }   color: white;
        }
        
        .announcements-card {
            grid-column: span 2;
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
    
    <?php include ('../includes/navbar/landlord-sidebar.html'); ?>

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
                <button class="action-btn" onclick="window.location.href='maintenance.php'">
                    <i class="fas fa-tools"></i> Maintenance Requests
                </button>
                <button class="action-btn" onclick="window.location.href='applications.php'">
                    <i class="fas fa-user-plus"></i> Tenant Applications
                </button>
                <button class="action-btn" onclick="window.location.href='payments.php'">
                    <i class="fas fa-credit-card"></i> View Payments
                </button>
            </div>

        </div>
        
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
                <canvas id="yearlyChart" width="400" height="120"></canvas>
            </div>
        </div>
        
        <div class="card announcements-card" style="grid-column: span 1;">
            <h2 class="card-title">
                Recent Announcements
                <button class="add-btn" onclick="openModal()"><i class="fas fa-plus"></i> Add</button>
            </h2>
            <?php if (empty($announcements)): ?>
                <div class="empty-state empty-state-white">
                    <i class="fas fa-bullhorn"></i>
                    <p>No announcements</p>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement">
                        <div class="announcement-date"><?= date('F j, Y', strtotime($announcement['created_at'])) ?></div>
                        <div class="announcement-text">
                            <strong><?= htmlspecialchars($announcement['title']) ?></strong><br>
                            <?= htmlspecialchars($announcement['content']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Announcement Modal -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <h3 style="color: #1666ba; margin-bottom: 1.5rem;">Add New Announcement</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_announcement">
                
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Visible To</label>
                    <select name="visible_to" required>
                        <option value="all">Everyone</option>
                        <option value="tenant">Tenants Only</option>
                        <option value="landlord">Landlords Only</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary">Create Announcement</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        let yearlyChart;
        
        function updateCharts() {
            const filter = document.getElementById('propertyFilter').value;
            // In a real implementation, you would fetch filtered data via AJAX
            // For now, we'll use the same data
            
            if (yearlyChart) {
                yearlyChart.destroy();
            }
            
            createYearlyChart();
        }
        
        function createYearlyChart() {

            const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
            yearlyChart = new Chart(yearlyCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Rent',
                    data: [<?= $monthly_rent ?>, <?= $monthly_rent * 1.1 ?>, <?= $monthly_rent * 0.9 ?>, <?= $monthly_rent * 1.2 ?>, <?= $monthly_rent ?>, <?= $monthly_rent * 1.1 ?>, <?= $monthly_rent * 1.3 ?>, <?= $monthly_rent * 1.1 ?>, <?= $monthly_rent ?>, <?= $monthly_rent * 1.2 ?>, <?= $monthly_rent * 1.1 ?>, <?= $monthly_rent ?>],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'Utilities',
                    data: [<?= $monthly_utilities ?>, <?= $monthly_utilities * 1.2 ?>, <?= $monthly_utilities * 0.8 ?>, <?= $monthly_utilities * 1.1 ?>, <?= $monthly_utilities ?>, <?= $monthly_utilities * 1.3 ?>, <?= $monthly_utilities * 1.4 ?>, <?= $monthly_utilities * 1.2 ?>, <?= $monthly_utilities * 0.9 ?>, <?= $monthly_utilities * 1.1 ?>, <?= $monthly_utilities * 1.2 ?>, <?= $monthly_utilities ?>],
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: false,
                    tension: 0.4
                }, {
                    label: 'Maintenance',
                    data: [<?= $monthly_maintenance ?>, <?= $monthly_maintenance * 0.8 ?>, <?= $monthly_maintenance * 1.5 ?>, <?= $monthly_maintenance * 0.6 ?>, <?= $monthly_maintenance * 1.2 ?>, <?= $monthly_maintenance * 0.9 ?>, <?= $monthly_maintenance * 1.8 ?>, <?= $monthly_maintenance * 1.1 ?>, <?= $monthly_maintenance * 0.7 ?>, <?= $monthly_maintenance * 1.3 ?>, <?= $monthly_maintenance * 0.9 ?>, <?= $monthly_maintenance ?>],
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: false,
                    tension: 0.4
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
        

        
        // Initialize chart
        createYearlyChart();

        function openModal() {
            document.getElementById('announcementModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('announcementModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('announcementModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Mobile menu functionality
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
    </script>

</body>
</html>
