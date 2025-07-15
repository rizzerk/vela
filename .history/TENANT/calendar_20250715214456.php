<?php
session_start();
require_once '../connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get events from database
$events = [];
$currentYear = date('Y');
$currentMonth = date('n');
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get lease events
$leaseQuery = "SELECT l.lease_id, l.start_date, l.end_date, p.title AS property_name 
               FROM LEASE l 
               JOIN PROPERTY p ON l.property_id = p.property_id 
               WHERE l.tenant_id = ?";
$stmt = $conn->prepare($leaseQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$leaseResult = $stmt->get_result();
while ($row = $leaseResult->fetch_assoc()) {
    $events[$row['start_date']][] = [
        'title' => "Lease Start: " . $row['property_name'],
        'type' => 'lease',
        'date' => $row['start_date'],
        'paid' => false,
        'status' => 'active'
    ];
    $events[$row['end_date']][] = [
        'title' => "Lease End: " . $row['property_name'],
        'type' => 'lease',
        'date' => $row['end_date'],
        'paid' => false,
        'status' => 'active'
    ];
}

// Get bill events with payment status
$billQuery = "SELECT b.bill_id, b.due_date, b.amount, b.description, b.status, 
                     l.lease_id, p.payment_id, p.status as payment_status
              FROM BILL b 
              JOIN LEASE l ON b.lease_id = l.lease_id 
              LEFT JOIN PAYMENT p ON b.bill_id = p.bill_id
              WHERE l.tenant_id = ?";
$stmt = $conn->prepare($billQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$billResult = $stmt->get_result();
while ($row = $billResult->fetch_assoc()) {
    $isPaid = ($row['status'] == 'paid') || 
              ($row['payment_status'] == 'verified');
    
    $status = $row['payment_status'] ?? $row['status'];
    
    // Customize title based on payment status
    $title = "Payment Due: " . $row['description'] . " ($" . $row['amount'] . ")";
    if ($row['payment_status'] === 'rejected') {
        $title = "Payment Rejected: " . $row['description'] . " ($" . $row['amount'] . ")";
    } elseif ($row['payment_status'] === 'pending') {
        $title = "Payment Pending: " . $row['description'] . " ($" . $row['amount'] . ")";
    } elseif ($isPaid) {
        $title = "Payment Verified: " . $row['description'] . " ($" . $row['amount'] . ")";
    }
    
    $events[$row['due_date']][] = [
        'title' => $title,
        'type' => 'bill',
        'date' => $row['due_date'],
        'paid' => $isPaid,
        'status' => $status
    ];
}

// Get maintenance events
$maintenanceQuery = "SELECT mr.requested_at, mr.description, l.lease_id, mr.status
                     FROM MAINTENANCE_REQUEST mr 
                     JOIN LEASE l ON mr.lease_id = l.lease_id 
                     WHERE l.tenant_id = ?";
$stmt = $conn->prepare($maintenanceQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$maintenanceResult = $stmt->get_result();
while ($row = $maintenanceResult->fetch_assoc()) {
    $events[$row['requested_at']][] = [
        'title' => "Maintenance: " . $row['description'] . " (" . ucfirst(str_replace('_', ' ', $row['status'])) . ")",
        'type' => 'maintenance',
        'date' => $row['requested_at'],
        'paid' => false,
        'status' => $row['status']
    ];
}

// Get announcements
$announcementQuery = "SELECT title, content, created_at 
                      FROM ANNOUNCEMENT 
                      WHERE visible_to IN ('all', ?) 
                      AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 30 DAY) AND NOW()";
$stmt = $conn->prepare($announcementQuery);
$stmt->bind_param("s", $user_role);
$stmt->execute();
$announcementResult = $stmt->get_result();
while ($row = $announcementResult->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['created_at']));
    $events[$date][] = [
        'title' => "Announcement: " . $row['title'],
        'type' => 'announcement',
        'date' => $date,
        'paid' => false,
        'status' => 'published'
    ];
}

// Get current month and year
$currentMonth = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Handle month navigation
if (isset($_GET['prev'])) {
    $currentMonth--;
    if ($currentMonth < 1) {
        $currentMonth = 12;
        $currentYear--;
    }
} elseif (isset($_GET['next'])) {
    $currentMonth++;
    if ($currentMonth > 12) {
        $currentMonth = 1;
        $currentYear++;
    }
}

// Get month name
$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
$currentMonthName = $monthNames[$currentMonth];

// Function to get days in month
function getDaysInMonth($month, $year) {
    return date('t', strtotime("$year-$month-01"));
}

// Function to get first day of month
function getFirstDayOfMonth($month, $year) {
    return date('w', strtotime("$year-$month-01"));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tenant Calendar - VELA</title>
    <script src="https://kit.fontawesome.com/dddee79f2e.js" crossorigin="anonymous"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #343a40;
            min-height: 100vh;
        }

        /* Main content */
        .main-content {
            padding: 25px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .page-title {
            font-size: 1.8rem;
            color: #1666ba;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-dues-btn {
            background: #1666ba;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .view-dues-btn:hover {
            background: #0d4a8a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .view-dues-btn i {
            font-size: 1rem;
        }

        .calendar-wrapper {
            display: flex;
            gap: 30px;
        }

        .calendar-container {
            flex: 3;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
        }

        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .calendar-header .year {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1666ba;
        }

        .calendar-header .month-nav {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .month-nav button {
            border: none;
            background: #e9ecef;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: #1666ba;
        }

        .month-nav button:hover {
            background: #1666ba;
            color: white;
        }

        .month-title {
            font-size: 1.8rem;
            font-weight: 600;
            min-width: 150px;
            text-align: center;
            color: #343a40;
        }

        .calendar-table {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #dee2e6;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }

        .day-header {
            background: #0d4a8a;
            color: white;
            font-weight: 600;
            padding: 15px 5px;
            text-align: center;
            font-size: 0.9rem;
        }

        .calendar-day {
            background: white;
            height: 120px;
            width: 100%;
            padding: 8px;
            position: relative;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .calendar-day:hover {
            background: #f1f8ff;
        }

        .calendar-day.empty {
            background: #f8f9fa;
        }

        .calendar-day-number {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 5px;
            color: #495057;
        }

        .calendar-day.today .calendar-day-number {
            background: #1666ba;
            color: white;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .calendar-day.selected {
            background: #e1f0fa;
            border: 2px solid #1666ba;
        }

        .calendar-events {
            flex-grow: 1;
            overflow: hidden;
        }

        .calendar-event {
            font-size: 0.7rem;
            padding: 4px 6px;
            border-radius: 3px;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            position: relative;
        }

        .calendar-event.paid {
            text-decoration: line-through;
            opacity: 0.7;
        }

        .calendar-event.paid::after {
            content: "✓ Paid";
            color: #28a745;
            margin-left: 5px;
            font-weight: bold;
        }

        .calendar-event.rejected {
            background: #f8d7da;
            border-left: 3px solid #dc3545;
        }

        .calendar-event.rejected::after {
            content: "✗ Rejected";
            color: #dc3545;
            margin-left: 5px;
            font-weight: bold;
        }

        .calendar-event.pending {
            background: #fff3cd;
            border-left: 3px solid #ffc107;
        }

        .calendar-event.pending::after {
            content: "⏳ Pending";
            color: #ffc107;
            margin-left: 5px;
            font-weight: bold;
        }

        .calendar-event.lease {
            background: #d4edda;
            border-left: 3px solid #28a745;
        }

        .calendar-event.bill {
            background: #fff3cd;
            border-left: 3px solid #ffc107;
        }

        .calendar-event.maintenance {
            background: #f8d7da;
            border-left: 3px solid #dc3545;
        }

        .calendar-event.announcement {
            background: #cce5ff;
            border-left: 3px solid #1666ba;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 25px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: relative;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 25px;
            font-size: 1.5rem;
            color: #aaa;
            cursor: pointer;
        }

        .close:hover {
            color: #333;
        }

        .modal-header {
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1666ba;
        }

        .modal-status {
            font-size: 0.9rem;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .modal-status.rejected {
            background: #f8d7da;
            color: #dc3545;
        }

        .modal-status.pending {
            background: #fff3cd;
            color: #ffc107;
        }

        .modal-status.paid {
            background: #d4edda;
            color: #28a745;
        }

        .modal-body {
            padding: 15px 0;
        }

        .modal-footer {
            padding-top: 15px;
            margin-top: 15px;
            border-top: 1px solid #eee;
            text-align: right;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: #1666ba;
            color: white;
        }

        .btn-primary:hover {
            background: #0d4a8a;
        }

        /* Events Sidebar */
        .events-sidebar {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 30px;
            min-width: 300px;
        }

        .events-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1666ba;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        .section-title i {
            font-size: 1.2rem;
        }

        .calendar-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .calendar-list-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .calendar-list-item:last-child {
            border-bottom: none;
        }

        .color-indicator {
            height: 20px;
            width: 20px;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .color-indicator.lease {
            background: #28a745;
        }

        .color-indicator.bill {
            background: #ffc107;
        }

        .color-indicator.maintenance {
            background: #dc3545;
        }

        .color-indicator.announcement {
            background: #1666ba;
        }

        .color-indicator.rejected {
            background: #dc3545;
        }

        .color-indicator.pending {
            background: #ffc107;
        }

        .event-details {
            flex-grow: 1;
        }

        .event-title {
            font-weight: 500;
            margin-bottom: 3px;
        }

        .event-date {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .events-list {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .event-card {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .event-card.paid {
            text-decoration: line-through;
            opacity: 0.7;
        }

        .event-card.paid::after {
            content: "✓ Paid";
            color: #28a745;
            margin-left: 10px;
            font-weight: bold;
            float: right;
        }

        .event-card.rejected {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .event-card.rejected::after {
            content: "✗ Rejected";
            color: #dc3545;
            margin-left: 10px;
            font-weight: bold;
            float: right;
        }

        .event-card.pending {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .event-card.pending::after {
            content: "⏳ Pending";
            color: #ffc107;
            margin-left: 10px;
            font-weight: bold;
            float: right;
        }

        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .event-card.lease {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }

        .event-card.bill {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .event-card.maintenance {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .event-card.announcement {
            background: #cce5ff;
            border-left: 4px solid #1666ba;
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .event-type {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .event-date-display {
            font-size: 0.9rem;
            color: #495057;
        }

        .event-content {
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .no-events {
            text-align: center;
            padding: 30px 0;
            color: #6c757d;
        }

        .no-events i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ced4da;
        }
        
        /* Event indicators for mobile */
        .event-indicators {
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 4px;
            display: none;
        }
        
        .event-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .event-indicator.lease {
            background: #28a745;
        }
        
        .event-indicator.bill {
            background: #ffc107;
        }
        
        .event-indicator.maintenance {
            background: #dc3545;
        }
        
        .event-indicator.announcement {
            background: #1666ba;
        }
        
        .event-indicator.rejected {
            background: #dc3545;
        }
        
        .event-indicator.pending {
            background: #ffc107;
        }

        

        /* Responsive Design */
        @media (max-width: 1200px) {
            .calendar-wrapper {
                flex-direction: column;
            }
            
            .calendar-day {
                height: 100px;
            }
        }

        @media (max-width: 992px) {
            .main-content {
                padding: 15px;
            }
            
            .events-sidebar {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .view-dues-btn {
                width: 100%;
                justify-content: center;
            }
            
            .calendar-table {
                grid-template-columns: repeat(7, 1fr);
            }
            
            .day-header {
                padding: 10px 2px;
                font-size: 0.75rem;
            }
            
            .calendar-day {
                height: 80px;
                padding: 5px;
            }
            
            .calendar-day-number {
                font-size: 0.9rem;
            }
            
            .calendar-event {
                font-size: 0.65rem;
                padding: 2px 4px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .month-title {
                font-size: 1.5rem;
                min-width: 120px;
            }
            
            .calendar-header .year {
                font-size: 2rem;
            }
            
            .events-section {
                padding: 15px;
            }
            
            .section-title {
                font-size: 1.2rem;
            }
            
            .calendar-list-item {
                padding: 8px 0;
            }
            
            /* Show event indicators on mobile */
            .event-indicators {
                display: flex;
            }
            
            /* Hide full event text on mobile */
            .calendar-events {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .calendar-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .month-nav {
                width: 100%;
                justify-content: space-between;
            }
            
            .month-nav a {
                flex: 1;
                display: flex;
                justify-content: center;
            }
            
            .month-nav button {
                width: 35px;
                height: 35px;
            }
            
            .calendar-table {
                gap: 0;
                border: none;
                background: transparent;
            }
            
            .day-header {
                padding: 8px 2px;
                font-size: 0.7rem;
            }
            
            .calendar-day {
                height: 70px;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
                margin: 1px;
            }
            
            .calendar-day-number {
                padding: 3px;
            }
            
            .event-card {
                padding: 10px;
            }
            
            .event-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .event-type, .event-date-display {
                font-size: 0.8rem;
            }
            
            .event-content {
                font-size: 0.85rem;
            }

            /* Modal adjustments for mobile */
            .modal-content {
                width: 95%;
                margin: 20% auto;
            }
        }

        @media (max-width: 400px) {
            .calendar-day {
                height: 60px;
            }
            
            .calendar-day-number {
                font-size: 0.8rem;
            }
            
            .month-title {
                font-size: 1.2rem;
                min-width: 100px;
            }
            
            .calendar-header .year {
                font-size: 1.5rem;
            }
            
            .day-header {
                font-size: 0.65rem;
                padding: 6px 2px;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar/tenant-navbar.php'?>

    <div class="main-content">
        <div class="header">
            <div class="page-title">
                <i class="fas fa-calendar-alt"></i> Tenant Calendar
            </div>
            <a href="view-dues.php" class="view-dues-btn">
                <i class="fas fa-receipt"></i> View Dues
            </a>
        </div>

        <div class="calendar-wrapper">
            <div class="calendar-container">
                <div class="calendar-header">
                    <div class="year"><?php echo $currentYear; ?></div>
                    <div class="month-nav">
                        <a href="?prev=1&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>">
                            <button><i class="fas fa-chevron-left"></i></button>
                        </a>
                        <div class="month-title"><?php echo $currentMonthName; ?></div>
                        <a href="?next=1&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>">
                            <button><i class="fas fa-chevron-right"></i></button>
                        </a>
                    </div>
                </div>

                <div class="calendar-table">
                    <!-- Day headers -->
                    <div class="day-header">Sun</div>
                    <div class="day-header">Mon</div>
                    <div class="day-header">Tue</div>
                    <div class="day-header">Wed</div>
                    <div class="day-header">Thu</div>
                    <div class="day-header">Fri</div>
                    <div class="day-header">Sat</div>

                    <!-- Calendar days -->
                    <?php
                    $daysInMonth = getDaysInMonth($currentMonth, $currentYear);
                    $firstDay = getFirstDayOfMonth($currentMonth, $currentYear);
                    
                    // Create empty cells for days before the first day of the month
                    for ($i = 0; $i < $firstDay; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }
                    
                    // Create cells for each day of the month
                    $today = date('Y-m-d');
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $dateStr = sprintf("%04d-%02d-%02d", $currentYear, $currentMonth, $day);
                        $isToday = ($dateStr == date('Y-m-d')) ? 'today' : '';
                        $hasEvents = isset($events[$dateStr]) ? 'has-events' : '';
                        
                        echo "<div class='calendar-day $isToday $hasEvents' data-date='$dateStr'>";
                        echo "<div class='calendar-day-number'>$day</div>";
                        
                        // Display event indicators
                        echo "<div class='event-indicators'>";
                        if (isset($events[$dateStr])) {
                            foreach ($events[$dateStr] as $event) {
                                $statusClass = $event['status'] === 'rejected' ? 'rejected' : 
                                             ($event['status'] === 'pending' ? 'pending' : $event['type']);
                                echo "<div class='event-indicator $statusClass'></div>";
                            }
                        }
                        echo "</div>";
                        
                        // Display events
                        echo "<div class='calendar-events'>";
                        if (isset($events[$dateStr])) {
                            foreach ($events[$dateStr] as $event) {
                                $paidClass = $event['paid'] ? 'paid' : '';
                                $statusClass = $event['status'] === 'rejected' ? 'rejected' : 
                                             ($event['status'] === 'pending' ? 'pending' : $event['type']);
                                echo "<div class='calendar-event $statusClass $paidClass' 
                                      title='{$event['title']}'
                                      onclick='showEventModal(\"{$event['title']}\", \"{$event['type']}\", \"{$event['date']}\", \"{$event['status']}\")'>
                                      {$event['title']}
                                  </div>";
                            }
                        }
                        echo "</div>";
                        
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
            
            <div class="events-sidebar">
                <div class="events-section">
                    <div class="section-title">
                        <i class="fas fa-tags"></i>
                        <span>Event Categories</span>
                    </div>
                    <div class="calendar-list">
                        <div class="calendar-list-item">
                            <div class="color-indicator lease"></div>
                            <div class="event-details">
                                <div class="event-title">Lease Events</div>
                                <div class="event-date">Start/End dates</div>
                            </div>
                        </div>
                        <div class="calendar-list-item">
                            <div class="color-indicator bill"></div>
                            <div class="event-details">
                                <div class="event-title">Bills & Payments</div>
                                <div class="event-date">Due dates</div>
                            </div>
                        </div>
                        <div class="calendar-list-item">
                            <div class="color-indicator maintenance"></div>
                            <div class="event-details">
                                <div class="event-title">Maintenance</div>
                                <div class="event-date">Request dates</div>
                            </div>
                        </div>
                        <div class="calendar-list-item">
                            <div class="color-indicator announcement"></div>
                            <div class="event-details">
                                <div class="event-title">Announcements</div>
                                <div class="event-date">Posted dates</div>
                            </div>
                        </div>
                        <div class="calendar-list-item">
                            <div class="color-indicator rejected"></div>
                            <div class="event-details">
                                <div class="event-title">Rejected Payments</div>
                                <div class="event-date">Requires attention</div>
                            </div>
                        </div>
                        <div class="calendar-list-item">
                            <div class="color-indicator pending"></div>
                            <div class="event-details">
                                <div class="event-title">Pending Payments</div>
                                <div class="event-date">Under review</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="events-section">
                    <div class="section-title">
                        <i class="fas fa-calendar-day"></i>
                        <span>Today's Events</span>
                    </div>
                    <div class="events-list" id="today-events">
                        <?php
                        $todayEvents = isset($events[$today]) ? $events[$today] : [];
                        
                        if (empty($todayEvents)) {
                            echo '<div class="no-events">';
                            echo '<i class="far fa-calendar-check"></i>';
                            echo '<p>No events scheduled for today</p>';
                            echo '</div>';
                        } else {
                            foreach ($todayEvents as $event) {
                                $date = date('M j, Y', strtotime($event['date']));
                                $paidClass = $event['paid'] ? 'paid' : '';
                                $statusClass = $event['status'] === 'rejected' ? 'rejected' : 
                                             ($event['status'] === 'pending' ? 'pending' : $event['type']);
                                echo "<div class='event-card $statusClass $paidClass' 
                                      onclick='showEventModal(\"{$event['title']}\", \"{$event['type']}\", \"{$event['date']}\", \"{$event['status']}\")'>
                                      <div class='event-header'>
                                          <div class='event-type'>{$event['type']}</div>
                                          <div class='event-date-display'>$date</div>
                                      </div>
                                      <div class='event-content'>{$event['title']}</div>
                                  </div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h2 class="modal-title" id="modalEventType"></h2>
                <div class="modal-status" id="modalEventStatus"></div>
            </div>
            <div class="modal-body">
                <p id="modalEventContent"></p>
                <p><strong>Date:</strong> <span id="modalEventDate"></span></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Get the modal
        const modal = document.getElementById("eventModal");
        
        // Function to show modal with event details
        function showEventModal(title, type, date, status) {
            document.getElementById("modalEventType").textContent = 
                type.charAt(0).toUpperCase() + type.slice(1);
            
            document.getElementById("modalEventContent").textContent = title;
            
            // Display status if available
            const statusElement = document.getElementById("modalEventStatus");
            if (status) {
                statusElement.textContent = "Status: " + status.charAt(0).toUpperCase() + status.slice(1);
                statusElement.className = "modal-status " + status;
            } else {
                statusElement.textContent = "";
                statusElement.className = "modal-status";
            }
            
            document.getElementById("modalEventDate").textContent = new Date(date).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            modal.style.display = "block";
        }
        
        // Function to close modal
        function closeModal() {
            modal.style.display = "none";
        }
        
        // Close modal when clicking on X
        document.querySelector(".close").addEventListener('click', closeModal);
        
        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                closeModal();
            }
        });
        
        // Highlight today in the calendar
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            const todayCell = document.querySelector(`.calendar-day[data-date="${todayStr}"]`);
            
            if (todayCell) {
                todayCell.classList.add('selected');
            }
            
            // Add click event to days
            document.querySelectorAll('.calendar-day:not(.empty)').forEach(day => {
                day.addEventListener('click', function() {
                    // Remove selected class from all days
                    document.querySelectorAll('.calendar-day').forEach(d => {
                        d.classList.remove('selected');
                    });
                    
                    // Add selected class to clicked day
                    this.classList.add('selected');
                });
            });
        });
    </script>
</body>
</html>