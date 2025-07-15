<?php
session_start();
require_once "../connection.php";

$userName = $_SESSION['name'] ?? 'Tenant';
$userId = $_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'all';

$leaseQuery = "SELECT l.lease_id, l.property_id, p.title, p.address 
               FROM LEASE l 
               JOIN PROPERTY p ON l.property_id = p.property_id 
               WHERE l.tenant_id = ? AND l.active = 1";
$leaseStmt = $conn->prepare($leaseQuery);
$leaseStmt->bind_param("i", $userId);
$leaseStmt->execute();
$leaseResult = $leaseStmt->get_result();
$lease = $leaseResult->fetch_assoc();

$bills = [];
$announcements = [];
$debug_info = "User ID: $userId, ";

if ($lease) {
    $announcementQuery = "SELECT title, content, priority, created_at FROM ANNOUNCEMENT 
                         WHERE visible_to IN ('tenant', 'all') 
                         ORDER BY FIELD(priority, 'high', 'medium', 'low'), created_at DESC LIMIT 3";
    $announcementStmt = $conn->prepare($announcementQuery);
    $announcementStmt->execute();
    $announcementResult = $announcementStmt->get_result();
    
    while ($row = $announcementResult->fetch_assoc()) {
        $announcements[] = $row;
    }
    $debug_info .= "Lease ID: {$lease['lease_id']}, Property ID: {$lease['property_id']}, Announcements: " . count($announcements) . ", ";

    // Base query
    $billQuery = "SELECT b.bill_id, b.amount, b.due_date, b.status as bill_status, 
                         b.description, b.billing_period_start, b.billing_period_end, 
                         b.bill_type, p.status as payment_status
                  FROM BILL b
                  LEFT JOIN PAYMENT p ON b.bill_id = p.bill_id
                  WHERE b.lease_id = ? ";

    switch ($filter) {
        case 'paid':
            $billQuery .= "AND (b.status = 'paid' OR p.status = 'verified') ";
            break;
        case 'unpaid':
            $billQuery .= "AND b.status = 'unpaid' AND (p.status IS NULL OR p.status != 'verified') ";
            break;
        case 'pending':
            $billQuery .= "AND p.status = 'pending' ";
            break;
        case 'rejected':
            $billQuery .= "AND p.status = 'rejected' ";
            break;
        case 'overdue':
            // show bills that are unpaid and past their due date
            $billQuery .= "AND (b.status = 'overdue' OR (b.due_date < CURDATE() AND b.status = 'unpaid' AND (p.status IS NULL OR p.status != 'verified'))) ";
            break;
        case 'all':
        default:
            break;
    }

    $billQuery .= "GROUP BY b.bill_id ORDER BY b.due_date ASC";

    $billStmt = $conn->prepare($billQuery);
    $billStmt->bind_param("i", $lease['lease_id']);
    $billStmt->execute();
    $billResult = $billStmt->get_result();

    while ($row = $billResult->fetch_assoc()) {
        // For overdue filter, mark bills as overdue if they meet the criteria
        if ($filter === 'overdue' && $row['bill_status'] === 'unpaid' && strtotime($row['due_date']) < time()) {
            $row['bill_status'] = 'overdue';
        }
        $bills[] = $row;
    }
    $debug_info .= "Bills found: " . count($bills);
} else {
    $debug_info .= "No active lease found";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Dashboard - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
            padding-top: 90px;
        }

        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .section {
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }

        .calendar-section {
            background: #ffffff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .calendar {
            width: 100%;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .calendar-nav {
            background: none;
            border: none;
            color: #1666ba;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .calendar-nav:hover {
            background: #f1f5f9;
        }

        .calendar-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .calendar-day-header {
            background: #f8fafc;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
        }

        .calendar-day {
            background: #ffffff;
            padding: 0.5rem;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }

        .calendar-day:hover {
            background: #f1f5f9;
        }

        .calendar-day.today {
            background: #1666ba;
            color: white;
            font-weight: 600;
        }

        .calendar-day.has-due {
            background: #fef3c7;
            color: #d97706;
            font-weight: 600;
        }

        .calendar-day.has-due.today {
            background: #dc2626;
            color: white;
        }

        .calendar-day.other-month {
            color: #cbd5e1;
        }

        .filter-section {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn:hover {
            background: #f1f5f9;
        }

        .filter-btn.active {
            background: #1666ba;
            color: white;
            border-color: #1666ba;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 1.75rem;
            color: #1666ba;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .welcome-text {
            font-size: 1rem;
            color: #64748b;
            font-weight: 500;
        }

        .bill-item {
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .bill-item:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .bill-info {
            flex: 1;
        }

        .bill-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .bill-type.rent {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .bill-type.utility {
            background-color: #fef3c7;
            color: #d97706;
        }

        .bill-type.penalty {
            background-color: #fecaca;
            color: #dc2626;
        }

        .bill-type.other {
            background-color: #e5e7eb;
            color: #374151;
        }

        .bill-amount {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .bill-due {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
            margin: 0;
        }

        .bill-period {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        .bill-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
            letter-spacing: 0.025em;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .status-overdue {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .status-unpaid {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .status-paid {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }

        .status-rejected {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .status-pending {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            color: white;
        }

        .notice-section {
            background: linear-gradient(135deg, #1666ba 0%, #368ce7 100%);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(22, 102, 186, 0.1), 0 2px 4px -1px rgba(22, 102, 186, 0.05);
        }

        .notice-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }

        .notice-text {
            font-size: 0.9rem;
            line-height: 1.5;
            opacity: 0.95;
        }

        .actions-section {
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        } 0.06);
            border: 1px solid #e2e8f0;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .action-card {
            background: #1666ba;
            border-radius: 12px;
            padding: 1.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            color: #ffffff;
            box-shadow: 0 4px 6px -1px rgba(22, 102, 186, 0.1), 0 2px 4px -1px rgba(22, 102, 186, 0.06);
        }

        .action-card:hover {
            background: #368ce7;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(22, 102, 186, 0.2), 0 4px 6px -2px rgba(22, 102, 186, 0.1);
        }

        .action-icon {
            font-size: 2rem;
            color: #ffffff;
            margin-bottom: 1rem;
        }

        .action-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: -0.025em;
        }

        .no-bills {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 2rem;
            background: #f8fafc;
            border-radius: 12px;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .section,
            .calendar-section {
                padding: 1.5rem;
            }

            .actions-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .action-card {
                padding: 1.25rem;
            }

            .action-icon {
                font-size: 1.75rem;
            }

            .bill-item {
                padding: 1.25rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .bill-status {
                align-self: flex-start;
            }

            .calendar-day {
                min-height: 35px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar/tenant-navbar.php'; ?>

    <div class="content-wrapper">
        <div class="dashboard-grid">
            <div class="main-content">
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Payment Status</h2>
                        <div class="welcome-text">Welcome back, <?php echo htmlspecialchars($userName); ?>!</div>
                    </div>

                    <div class="filter-section">
                        <button class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>" onclick="setFilter('all')">All</button>
                        <button class="filter-btn <?= $filter === 'paid' ? 'active' : '' ?>" onclick="setFilter('paid')">Paid</button>
                        <button class="filter-btn <?= $filter === 'unpaid' ? 'active' : '' ?>" onclick="setFilter('unpaid')">Unpaid</button>
                        <button class="filter-btn <?= $filter === 'pending' ? 'active' : '' ?>" onclick="setFilter('pending')">Pending</button>
                        <button class="filter-btn <?= $filter === 'rejected' ? 'active' : '' ?>" onclick="setFilter('rejected')">Rejected</button>
                        <button class="filter-btn <?= $filter === 'overdue' ? 'active' : '' ?>" onclick="setFilter('overdue')">Overdue</button>
                    </div>

                    <?php if (empty($bills)): ?>
                        <div class="no-bills">No bills found for this filter</div>
                    <?php else: ?>
                        <?php foreach ($bills as $bill): ?>
                            <div class="bill-item">
                                <div class="bill-info">
                                    <span class="bill-type <?= strtolower($bill['bill_type']) ?>">
                                        <?= ucfirst($bill['bill_type']) ?>
                                    </span>
                                    <div class="bill-amount">₱<?= number_format($bill['amount'], 2) ?></div>
                                    <div class="bill-due">Due: <?= date('M d, Y', strtotime($bill['due_date'])) ?></div>
                                    <?php if ($bill['billing_period_start'] && $bill['billing_period_end']): ?>
                                        <div class="bill-period">
                                            Period: <?= date('M d', strtotime($bill['billing_period_start'])) . ' - ' .
                                                        date('M d, Y', strtotime($bill['billing_period_end'])) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($bill['description']): ?>
                                        <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #64748b; font-weight: 500;">
                                            <?= htmlspecialchars($bill['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="bill-status 
                                    <?php if ($bill['payment_status'] === 'rejected'): ?>
                                        status-rejected">Payment Rejected
                                <?php elseif ($bill['payment_status'] === 'pending'): ?>
                                    status-pending">Payment Pending
                                <?php elseif ($bill['payment_status'] === 'verified'): ?>
                                    status-paid">Payment Verified
                                <?php elseif ($bill['bill_status'] === 'overdue'): ?>
                                    status-overdue">Overdue
                                <?php elseif ($bill['bill_status'] === 'paid'): ?>
                                    status-paid">Paid
                                <?php else: ?>
                                    status-unpaid">Unpaid
                                <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="actions-section">
                    <div class="actions-grid">
                        <div class="action-card" onclick="maintenanceRequest()">
                            <div class="action-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="action-title">Maintenance Request</div>
                        </div>
                        <div class="action-card" onclick="viewPaymentHistory()">
                            <div class="action-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <div class="action-title">Payments</div>
                        </div>
                        <div class="action-card" onclick="viewLease()">
                            <div class="action-icon">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <div class="action-title">Lease Details</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sidebar-content">
                <div class="calendar-section">
                    <div class="section-header">
                        <h2 class="section-title">Calendar</h2>
                    </div>
                    <div class="calendar">
                        <div class="calendar-header">
                            <button class="calendar-nav" onclick="changeMonth(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div class="calendar-title" id="calendar-title"></div>
                            <button class="calendar-nav" onclick="changeMonth(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <div class="calendar-grid" id="calendar-grid"></div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Notices</h2>
                        <span onclick="viewAllNotices()" style="color: #1666ba; cursor: pointer; font-weight: 500;">
                            View All
                        </span>
                    </div>
                    
                    <div class="notice-section">
                    <?php if (!empty($announcements)): ?>
                        <?php foreach ($announcements as $index => $announcement): ?>
                            <div style="<?= $index > 0 ? 'border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem; margin-top: 1rem;' : '' ?>">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                    <h3 style="font-size: 1.125rem; font-weight: 600; margin: 0; color: #ffffff;"><?= htmlspecialchars($announcement['title']) ?></h3>
                                    <span style="background: rgba(255,255,255,0.2); padding: 0.2rem 0.6rem; border-radius: 8px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">
                                        <?= ucfirst($announcement['priority']) ?>
                                    </span>
                                </div>
                                <p style="font-size: 0.9rem; line-height: 1.5; opacity: 0.95; margin: 0 0 0.5rem 0; color: #ffffff;"><?= htmlspecialchars($announcement['content']) ?></p>
                                <div style="font-size: 0.8rem; opacity: 0.8;">
                                    Posted: <?= date('M d, Y', strtotime($announcement['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div>
                            <h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 0.5rem 0; color: #ffffff;">Welcome</h3>
                            <p style="font-size: 0.9rem; line-height: 1.5; opacity: 0.95; margin: 0; color: #ffffff;">No announcements at this time. Check back later for updates from your landlord.</p>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentDate = new Date();
        const billDueDates = <?= json_encode(array_map(function($bill) { return date('Y-m-d', strtotime($bill['due_date'])); }, $bills)) ?>;

        function setFilter(filter) {
            window.location.href = `?filter=${filter}`;
        }

        function maintenanceRequest() {
            window.location.href = 'maintenance.php';
        }

        function viewPaymentHistory() {
            window.location.href = 'pay-dues.php';
        }

        function viewLease() {
            window.location.href = 'lease-details.php';
        }

        function viewAllNotices() {
            window.location.href = 'notices.php';
        }

        function changeMonth(direction) {
            currentDate.setMonth(currentDate.getMonth() + direction);
            renderCalendar();
        }

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const today = new Date();
            
            document.getElementById('calendar-title').textContent = 
                currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            let calendarHTML = '';
            const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            
            dayHeaders.forEach(day => {
                calendarHTML += `<div class="calendar-day-header">${day}</div>`;
            });

            // Previous month days
            for (let i = firstDay - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                calendarHTML += `<div class="calendar-day other-month">${day}</div>`;
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === day;
                const hasDue = billDueDates.includes(dateStr);
                
                let classes = 'calendar-day';
                if (isToday) classes += ' today';
                if (hasDue) classes += ' has-due';
                
                calendarHTML += `<div class="${classes}">${day}</div>`;
            }

            // Next month days
            const totalCells = Math.ceil((firstDay + daysInMonth) / 7) * 7;
            const remainingCells = totalCells - (firstDay + daysInMonth);
            for (let day = 1; day <= remainingCells; day++) {
                calendarHTML += `<div class="calendar-day other-month">${day}</div>`;
            }

            document.getElementById('calendar-grid').innerHTML = calendarHTML;
        }

        document.addEventListener('DOMContentLoaded', renderCalendar);
    </script>
</body>

</html>
<?php $conn->close(); ?>