<?php
session_start();
require_once 'connection.php';

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
        'date' => $row['start_date']
    ];
    $events[$row['end_date']][] = [
        'title' => "Lease End: " . $row['property_name'],
        'type' => 'lease',
        'date' => $row['end_date']
    ];
}

// Get bill events
$billQuery = "SELECT b.due_date, b.amount, b.description, l.lease_id 
              FROM BILL b 
              JOIN LEASE l ON b.lease_id = l.lease_id 
              WHERE l.tenant_id = ?";
$stmt = $conn->prepare($billQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$billResult = $stmt->get_result();
while ($row = $billResult->fetch_assoc()) {
    $events[$row['due_date']][] = [
        'title' => "Payment Due: " . $row['description'] . " ($" . $row['amount'] . ")",
        'type' => 'bill',
        'date' => $row['due_date']
    ];
}

// Get maintenance events
$maintenanceQuery = "SELECT mr.requested_at, mr.description, l.lease_id 
                     FROM MAINTENANCE_REQUEST mr 
                     JOIN LEASE l ON mr.lease_id = l.lease_id 
                     WHERE l.tenant_id = ?";
$stmt = $conn->prepare($maintenanceQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$maintenanceResult = $stmt->get_result();
while ($row = $maintenanceResult->fetch_assoc()) {
    $events[$row['requested_at']][] = [
        'title' => "Maintenance: " . $row['description'],
        'type' => 'maintenance',
        'date' => $row['requested_at']
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
        'date' => $date
    ];
}

// Prepare events for JavaScript
$js_events = json_encode($events);

// Function to get days in month
function getDaysInMonth($month, $year) {
    return date('t', strtotime("$year-$month-01"));
}

// Function to get first day of month
function getFirstDayOfMonth($month, $year) {
    return date('w', strtotime("$year-$month-01"));
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
        $currentMonth = 0;
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
            min-height: 120px;
            padding: 10px;
            position: relative;
            transition: all 0.2s ease;
        }

        .calendar-day:hover {
            background: #f1f8ff;
        }

        .calendar-day.empty {
            background: #f8f9fa;
        }

        .calendar-day-number {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #495057;
        }

        .calendar-day.today .calendar-day-number {
            background: #1666ba;
            color: white;
            width: 30px;
            height: 30px;
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
            max-height: 80px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .calendar-event {
            font-size: 0.75rem;
            padding: 5px 8px;
            border-radius: 4px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
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
            display: none; /* Hidden by default */
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

        /* Responsive Design */
        @media (max-width: 1200px) {
            .calendar-wrapper {
                flex-direction: column;
            }
            
            .calendar-day {
                min-height: 100px;
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
            
            .calendar-table {
                grid-template-columns: repeat(7, 1fr);
            }
            
            .day-header {
                padding: 10px 2px;
                font-size: 0.75rem;
            }
            
            .calendar-day {
                min-height: 80px;
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
                min-height: 70px;
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
            
            /* Show event indicators on mobile */
            .event-indicators {
                display: flex;
            }
            
            /* Hide full event text on mobile */
            .calendar-events {
                display: none;
            }
        }

        @media (max-width: 400px) {
            .calendar-day {
                min-height: 60px;
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
    <div class="main-content">
        <div class="header">
            <div class="page-title">
                <i class="fas fa-calendar-alt"></i> Tenant Calendar
            </div>
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
                                echo "<div class='event-indicator {$event['type']}'></div>";
                            }
                        }
                        echo "</div>";
                        
                        // Display events
                        echo "<div class='calendar-events'>";
                        if (isset($events[$dateStr])) {
                            foreach ($events[$dateStr] as $event) {
                                echo "<div class='calendar-event {$event['type']}' title='{$event['title']}'>{$event['title']}</div>";
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
                                echo "<div class='event-card {$event['type']}'>";
                                echo "<div class='event-header'>";
                                echo "<div class='event-type'>{$event['type']}</div>";
                                echo "<div class='event-date-display'>$date</div>";
                                echo "</div>";
                                echo "<div class='event-content'>{$event['title']}</div>";
                                echo "</div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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
                    
                    // Get the date
                    const selectedDate = this.getAttribute('data-date');
                    
                    // In a real application, you could load events for this date
                    console.log("Selected date:", selectedDate);
                });
            });
            
            // Simulate event click
            document.querySelectorAll('.calendar-event').forEach(event => {
                event.addEventListener('click', function() {
                    alert('Event details: ' + this.title);
                });
            });
            
            // Add event indicators for mobile
            document.querySelectorAll('.calendar-day.has-events').forEach(day => {
                const events = day.querySelectorAll('.calendar-event');
                const indicators = day.querySelector('.event-indicators');
                
                // Clear existing indicators
                indicators.innerHTML = '';
                
                // Add new indicators
                events.forEach(event => {
                    const type = Array.from(event.classList).find(cls => 
                        cls !== 'calendar-event' && 
                        ['lease', 'bill', 'maintenance', 'announcement'].includes(cls)
                    );
                    
                    if (type) {
                        const indicator = document.createElement('div');
                        indicator.className = `event-indicator ${type}`;
                        indicators.appendChild(indicator);
                    }
                });
            });
        });
    </script>
</body>
</html>