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

$monthly_collected_query = "
    SELECT COALESCE(SUM(p.amount_paid), 0) as collected
    FROM PAYMENT p 
    JOIN BILL b ON p.bill_id = b.bill_id
    WHERE p.status = 'verified' 
    AND MONTH(p.submitted_at) = MONTH(NOW()) 
    AND YEAR(p.submitted_at) = YEAR(NOW())
";
$collected_result = $conn->query($monthly_collected_query);
$monthly_collected = $collected_result ? $collected_result->fetch_assoc()['collected'] : 0;

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
        
        /* Mobile navbar styles... (keep existing mobile styles) */
        
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
        
        .card-title {
            font-size: 1.1rem;
            color: #1666ba;
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(190, 218, 247, 0.5);
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(190, 218, 247, 0.5);
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

        /* Modal styles... (keep existing modal styles) */
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
                        <div class="financial-metric total">
                            <span class="metric-label">Total Expenses</span>
                            <span class="metric-value">₱<?= number_format($monthly_utilities, 2) ?></span>
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
                            <span class="metric-value">₱<?= number_format($monthly_utilities, 2) ?></span>
                        </div>
                        <div class="financial-metric total">
                            <span class="metric-label">Net Profit</span>
                            <span class="metric-value">₱<?= number_format($monthly_collected - $monthly_utilities, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="chart-container">
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
            yearlyChart = new Chart(yearlyCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Rent Collected',
                        data: [120000, 150000, 140000, 160000, 170000, 180000, <?= $monthly_collected ?>, 0, 0, 0, 0, 0],
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    }, {
                        label: 'Utilities',
                        data: [20000, 22000, 25000, 23000, 21000, 24000, <?= $monthly_utilities ?>, 0, 0, 0, 0, 0],
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
                                        const utilities = context[1].raw;
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