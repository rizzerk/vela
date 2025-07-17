<?php
session_start();
require_once '../connection.php';
require_once "../includes/auth/landlord_auth.php";
require_once '../vendor/autoload.php';

$landlord_id = $_SESSION['user_id'] ?? 1;

if (isset($_GET['ajax']) && $_GET['ajax'] === 'financial_data') {
    $selected_year = $_GET['selected_year'] ?? date('Y');
    $property_id = $_GET['property_id'] ?? 'all';
    
    $date_condition_bill = "AND YEAR(b.generated_at) = " . intval($selected_year);
    
    $property_condition = "";
    if ($property_id !== 'all') {
        $property_condition = "AND l.property_id = " . intval($property_id);
    }
    
    // Get expected rent (monthly rent * 12 for yearly comparison)
    $expected_rent_query = "SELECT COALESCE(SUM(monthly_rent * 12), 0) as total_rent FROM PROPERTY WHERE status = 'occupied'";
    if ($property_id !== 'all') {
        $expected_rent_query .= " AND property_id = " . intval($property_id);
    }
    $expected_rent_result = $conn->query($expected_rent_query);
    $expected_rent = $expected_rent_result ? $expected_rent_result->fetch_assoc()['total_rent'] : 0;

    // Get actual rent collected
    $rent_query = "SELECT COALESCE(SUM(b.amount), 0) as collected FROM BILL b JOIN LEASE l ON b.lease_id = l.lease_id WHERE b.status = 'paid' AND b.bill_type = 'rent' $date_condition_bill $property_condition";
    $rent_result = $conn->query($rent_query);
    $rent_collected = $rent_result ? $rent_result->fetch_assoc()['collected'] : 0;
    
    // Get utility payments
    $utilities_query = "SELECT COALESCE(SUM(b.amount), 0) as utilities FROM BILL b JOIN LEASE l ON b.lease_id = l.lease_id WHERE b.status = 'paid' AND b.bill_type = 'utility' $date_condition_bill $property_condition";
    $utilities_result = $conn->query($utilities_query);
    $utilities = $utilities_result ? $utilities_result->fetch_assoc()['utilities'] : 0;
    
    // Get penalty payments
    $penalties_query = "SELECT COALESCE(SUM(amount), 0) as penalties FROM BILL b JOIN LEASE l ON b.lease_id = l.lease_id WHERE b.status = 'paid' AND b.bill_type = 'penalty' $date_condition_bill $property_condition";
    $penalties_result = $conn->query($penalties_query);
    $penalties = $penalties_result ? $penalties_result->fetch_assoc()['penalties'] : 0;
    
    // Get other payments
    $other_query = "SELECT COALESCE(SUM(amount), 0) as other FROM BILL b JOIN LEASE l ON b.lease_id = l.lease_id WHERE b.status = 'paid' AND b.bill_type = 'other' $date_condition_bill $property_condition";
    $other_result = $conn->query($other_query);
    $other = $other_result ? $other_result->fetch_assoc()['other'] : 0;

    // Chart data for selected year
    $chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $chart_data = ['rent' => array_fill(0, 12, 0), 'utility' => array_fill(0, 12, 0), 'penalty' => array_fill(0, 12, 0), 'other' => array_fill(0, 12, 0)];
    
    // Get monthly data for chart with property filter
    $chart_bill_query = "SELECT MONTH(b.generated_at) as month, b.bill_type, SUM(b.amount) as total FROM BILL b";
    if ($property_id !== 'all') {
        $chart_bill_query .= " JOIN LEASE l ON b.lease_id = l.lease_id";
    }
    $chart_bill_query .= " WHERE b.status = 'paid' $date_condition_bill";
    if ($property_id !== 'all') {
        $chart_bill_query .= " AND l.property_id = " . intval($property_id);
    }
    $chart_bill_query .= " GROUP BY MONTH(b.generated_at), b.bill_type ORDER BY MONTH(b.generated_at)";
    
    $chart_bill_result = $conn->query($chart_bill_query);
    if ($chart_bill_result) {
        while ($row = $chart_bill_result->fetch_assoc()) {
            $month_index = $row['month'] - 1;
            if (isset($chart_data[$row['bill_type']])) {
                $chart_data[$row['bill_type']][$month_index] = (float)$row['total'];
            }
        }
    }

    // Calculate totals - rent is income, utilities/penalties/other are expenses
    $total_income = $rent_collected;
    $total_expenses = $utilities + $penalties + $other;
    $collection_rate = $expected_rent > 0 ? ($rent_collected / $expected_rent) * 100 : 0;
    $net_profit = $total_income - $total_expenses;
    
    header('Content-Type: application/json');
    echo json_encode([
        'rent_collected' => $rent_collected,
        'expected_rent' => $expected_rent,
        'collection_rate' => $collection_rate,
        'utilities' => $utilities,
        'penalties' => $penalties,
        'other' => $other,
        'total_income' => $total_income,
        'total_expenses' => $total_expenses,
        'net_profit' => $net_profit,
        'chart_data' => $chart_data,
        'chart_labels' => $chart_labels
    ]);
    exit;
}

if ($_POST['action'] ?? '' === 'add_announcement') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $priority = $_POST['priority'];
    
    $stmt = $conn->prepare("INSERT INTO ANNOUNCEMENT (title, content, visible_to, priority, created_by, created_at) VALUES (?, ?, 'tenant', ?, ?, NOW())");
    $stmt->bind_param("sssi", $title, $content, $priority, $landlord_id);
    
    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit;
    } else {
        header("Location: dashboard.php?error=1");
        exit;
    }
}

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

// Expected yearly rent from occupied properties
$yearly_rent_query = "SELECT COALESCE(SUM(monthly_rent * 12), 0) as total_rent FROM PROPERTY WHERE status = 'occupied'";
$rent_result = $conn->query($yearly_rent_query);
$yearly_expected_rent = $rent_result ? $rent_result->fetch_assoc()['total_rent'] : 0;

// Actual rent collected this year
$yearly_collected_query = "
    SELECT COALESCE(SUM(b.amount), 0) as collected
    FROM BILL b 
    WHERE b.status = 'paid' 
    AND b.bill_type = 'rent'
    AND YEAR(b.generated_at) = YEAR(NOW())
";
$collected_result = $conn->query($yearly_collected_query);
$yearly_collected = $collected_result ? $collected_result->fetch_assoc()['collected'] : 0;

// Utility payments this year
$yearly_utilities_query = "
    SELECT COALESCE(SUM(b.amount), 0) as utilities
    FROM BILL b 
    WHERE b.status = 'paid' 
    AND b.bill_type = 'utility'
    AND YEAR(b.generated_at) = YEAR(NOW())
";
$utilities_result = $conn->query($yearly_utilities_query);
$yearly_utilities = $utilities_result ? $utilities_result->fetch_assoc()['utilities'] : 0;

// Get penalty expenses
$penalty_expenses_query = "
    SELECT COALESCE(SUM(amount), 0) as penalty_expenses
    FROM BILL 
    WHERE status = 'paid' 
    AND bill_type = 'penalty'
    AND YEAR(generated_at) = YEAR(NOW())
";
$penalty_result = $conn->query($penalty_expenses_query);
$yearly_penalties = $penalty_result ? $penalty_result->fetch_assoc()['penalty_expenses'] : 0;

// Get other expenses
$other_expenses_query = "
    SELECT COALESCE(SUM(amount), 0) as other_expenses
    FROM BILL 
    WHERE status = 'paid' 
    AND bill_type = 'other'
    AND YEAR(generated_at) = YEAR(NOW())
";
$other_result = $conn->query($other_expenses_query);
$yearly_other = $other_result ? $other_result->fetch_assoc()['other_expenses'] : 0;

// Process yearly data for chart
$monthly_rent_data = array_fill(0, 12, 0);
$monthly_utility_data = array_fill(0, 12, 0);
$monthly_penalty_data = array_fill(0, 12, 0);
$monthly_other_data = array_fill(0, 12, 0);

// Get monthly data for chart
$monthly_data_query = "
    SELECT 
        MONTH(b.generated_at) as month,
        b.bill_type,
        SUM(b.amount) as total
    FROM BILL b
    WHERE b.status = 'paid' 
    AND YEAR(b.generated_at) = YEAR(NOW())
    GROUP BY MONTH(b.generated_at), b.bill_type
    ORDER BY MONTH(b.generated_at)
";
$monthly_result = $conn->query($monthly_data_query);
if ($monthly_result) {
    while ($row = $monthly_result->fetch_assoc()) {
        $month_index = $row['month'] - 1;
        if ($row['bill_type'] === 'rent') {
            $monthly_rent_data[$month_index] = (float)$row['total'];
        } elseif ($row['bill_type'] === 'utility') {
            $monthly_utility_data[$month_index] = (float)$row['total'];
        } elseif ($row['bill_type'] === 'penalty') {
            $monthly_penalty_data[$month_index] = (float)$row['total'];
        } elseif ($row['bill_type'] === 'other') {
            $monthly_other_data[$month_index] = (float)$row['total'];
        }
    }
}

// Correct financial calculations - rent is income, utilities/penalties/other are expenses
$total_income = $yearly_collected;
$total_expenses = $yearly_utilities + $yearly_penalties + $yearly_other;
$net_profit = $total_income - $total_expenses;

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
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
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
            background: linear-gradient(135deg, #368ce7, #1666ba);
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
            color: #1666ba;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .properties-status-card .card-title,
        .quick-actions-card .card-title,
        .announcements-card .card-title {
            color: white;
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
        
        .income-card .card-header h3 {
            color: #10b981;
        }
        
        .expenses-card .card-header h3 {
            color: #f59e0b;
        }
        
        .net-income-card .card-header h3 {
            color: #1666ba;
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            color: #1666ba;
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
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .financial-cards {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .filter-section {
                flex-direction: column;
                gap: 1rem;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .filter-group select {
                width: 100%;
            }
            
            .modal-content {
                margin: 10% auto;
                padding: 1.5rem;
                width: 95%;
            }
        }
    </style>
</head>
<body>
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
                <button class="action-btn" onclick="window.location.href='applications.php'">
                    <i class="fas fa-user-plus"></i> Tenant Applications
                </button>
                <button class="action-btn" onclick="window.location.href='tenant-payments.php'">
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
                    <label for="yearFilter">Year:</label>
                    <select id="yearFilter" onchange="updateCharts()">
                        <?php 
                        $current_year = date('Y');
                        for ($year = $current_year; $year >= $current_year - 5; $year--): ?>
                            <option value="<?= $year ?>" <?= $year == $current_year ? 'selected' : '' ?>><?= $year ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="financial-cards">
                <div class="financial-card income-card">
                    <div class="card-header">
                        <h3>Income</h3>
                    </div>
                    <div class="card-body">
                        <div class="financial-metric">
                            <span class="metric-label">Expected Rent</span>
                            <span class="metric-value">₱<?= number_format($yearly_expected_rent, 2) ?></span>
                        </div>
                        <div class="financial-metric">
                            <span class="metric-label">Rent Collected</span>
                            <span class="metric-value">₱<?= number_format($yearly_collected, 2) ?></span>
                        </div>
                        <div class="financial-metric total">
                            <span class="metric-label">Collection Rate</span>
                            <span class="metric-value"><?= $yearly_expected_rent > 0 ? number_format(($yearly_collected/$yearly_expected_rent)*100, 2) : '0' ?>%</span>
                        </div>
                    </div>
                </div>
                
                <div class="financial-card expenses-card">
                    <div class="card-header">
                        <h3>Expenses</h3>
                    </div>
                    <div class="card-body">
                        <div class="financial-metric">
                            <span class="metric-label">Utilities</span>
                            <span class="metric-value">₱<?= number_format($yearly_utilities, 2) ?></span>
                        </div>
                        <div class="financial-metric">
                            <span class="metric-label">Penalties/Damages</span>
                            <span class="metric-value">₱<?= number_format($yearly_penalties, 2) ?></span>
                        </div>
                        <div class="financial-metric">
                            <span class="metric-label">Property Expenses</span>
                            <span class="metric-value">₱<?= number_format($yearly_other, 2) ?></span>
                        </div>
                        <div class="financial-metric total">
                            <span class="metric-label">Total Expenses</span>
                            <span class="metric-value">₱<?= number_format($total_expenses, 2) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="financial-card net-income-card">
                    <div class="card-header">
                        <h3>Net Income</h3>
                    </div>
                    <div class="card-body">
                        <div class="financial-metric">
                            <span class="metric-label">Total Income</span>
                            <span class="metric-value">₱<?= number_format($total_income, 2) ?></span>
                        </div>
                        <div class="financial-metric">
                            <span class="metric-label">Total Expenses</span>
                            <span class="metric-value">₱<?= number_format($total_expenses, 2) ?></span>
                        </div>
                        <div class="financial-metric total">
                            <span class="metric-label">Net Profit</span>
                            <span class="metric-value">₱<?= number_format($net_profit, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>Monthly Financial Overview</h3>
                <canvas id="yearlyChart" height="300"></canvas>
            </div>
        </div>
        
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
            const propertyFilter = document.getElementById('propertyFilter').value;
            const yearFilter = document.getElementById('yearFilter').value;
            
            document.querySelector('.financial-cards').style.opacity = '0.5';
            
            fetch(`dashboard.php?ajax=financial_data&selected_year=${yearFilter}&property_id=${propertyFilter}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data); // Debug log
                    updateFinancialCards(data);
                    updateChart(data.chart_data, data.chart_labels);
                    document.querySelector('.financial-cards').style.opacity = '1';
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    document.querySelector('.financial-cards').style.opacity = '1';
                });
        }
        
        function updateFinancialCards(data) {
            const formatCurrency = (amount) => {
                return '₱' + parseFloat(amount).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            document.querySelector('.income-card .card-body .financial-metric:nth-child(1) .metric-value').textContent = 
                formatCurrency(data.expected_rent);
            document.querySelector('.income-card .card-body .financial-metric:nth-child(2) .metric-value').textContent = 
                formatCurrency(data.rent_collected);
            document.querySelector('.income-card .card-body .financial-metric.total .metric-value').textContent = 
                parseFloat(data.collection_rate).toFixed(2) + '%';
            
            document.querySelector('.expenses-card .card-body .financial-metric:nth-child(1) .metric-value').textContent = 
                formatCurrency(data.utilities);
            document.querySelector('.expenses-card .card-body .financial-metric:nth-child(2) .metric-value').textContent = 
                formatCurrency(data.penalties);
            document.querySelector('.expenses-card .card-body .financial-metric:nth-child(3) .metric-value').textContent = 
                formatCurrency(data.other);
            document.querySelector('.expenses-card .card-body .financial-metric.total .metric-value').textContent = 
                formatCurrency(data.total_expenses);
            
            document.querySelector('.net-income-card .card-body .financial-metric:nth-child(1) .metric-value').textContent = 
                formatCurrency(data.total_income);
            document.querySelector('.net-income-card .card-body .financial-metric:nth-child(2) .metric-value').textContent = 
                formatCurrency(data.total_expenses);
            document.querySelector('.net-income-card .card-body .financial-metric.total .metric-value').textContent = 
                formatCurrency(data.net_profit);
        }
        
        function updateChart(chartData, chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']) {
            if (yearlyChart) {
                yearlyChart.destroy();
            }
            
            const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
            
            // Ensure data arrays exist and have proper length
            const rentData = chartData.rent || Array(12).fill(0);
            const utilityData = chartData.utility || Array(12).fill(0);
            const penaltyData = chartData.penalty || Array(12).fill(0);
            const otherData = chartData.other || Array(12).fill(0);
            
            yearlyChart = new Chart(yearlyCtx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
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
                    }, {
                        label: 'Penalties',
                        data: penaltyData,
                        backgroundColor: '#ef4444',
                        borderRadius: 4
                    }, {
                        label: 'Other Expenses',
                        data: otherData,
                        backgroundColor: '#8b5cf6',
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
                                label: function(context) {
                                    return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
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
        
        function createYearlyChart() {
            const chartData = {
                rent: <?= json_encode($monthly_rent_data) ?>,
                utility: <?= json_encode($monthly_utility_data) ?>,
                penalty: <?= json_encode($monthly_penalty_data) ?>,
                other: <?= json_encode($monthly_other_data) ?>
            };
            console.log('Initial chart data:', chartData); // Debug log
            updateChart(chartData);
        }
        
        createYearlyChart();
        
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