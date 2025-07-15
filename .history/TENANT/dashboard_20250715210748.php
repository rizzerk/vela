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
    /* Add these styles to your existing CSS */
    .calendar-day.has-events {
        min-height: 80px;
        align-items: flex-start;
        padding: 4px;
    }
    
    .calendar-event {
        font-size: 0.7rem;
        line-height: 1.2;
        margin: 2px 0;
        padding: 2px 4px;
        border-radius: 4px;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .event-lease {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .event-bill {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .event-announcement {
        background-color: #ecfccb;
        color: #365314;
    }
    
    .event-maintenance {
        background-color: #ede9fe;
        color: #5b21b6;
    }
    
    .event-overdue {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .calendar-day-number {
        align-self: flex-start;
        margin-right: auto;
        font-weight: bold;
    }
</style>

<script>
    let currentDate = new Date();
    // Get all the events data from PHP
    const events = {
        lease: <?= json_encode($lease ? [
            'start' => $lease['lease_start_date'] ?? null,
            'end' => $lease['lease_end_date'] ?? null,
            'title' => 'Lease: ' . ($lease['title'] ?? 'Property')
        ] : []) ?>,
        bills: <?= json_encode(array_map(function($bill) { 
            return [
                'date' => $bill['due_date'],
                'status' => $bill['payment_status'] ?? $bill['bill_status'],
                'type' => $bill['bill_type'],
                'title' => 'Payment Due: ' . $bill['bill_type'],
                'amount' => $bill['amount']
            ]; 
        }, $bills)) ?>,
        announcements: <?= json_encode(array_map(function($announcement) { 
            return [
                'date' => $announcement['created_at'],
                'priority' => $announcement['priority'],
                'title' => 'Announcement: ' . (strlen($announcement['title']) > 10 ? 
                          substr($announcement['title'], 0, 10) . '...' : $announcement['title'])
            ]; 
        }, $announcements)) ?>,
        maintenance: [] // This would be populated if you have maintenance data
    };

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
            const dateObj = new Date(year, month, day);
            const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === day;
            
            // Check for events on this day
            const dayEvents = [];
            
            // Lease events
            if (events.lease.start && dateStr === events.lease.start) {
                dayEvents.push({
                    type: 'lease',
                    title: 'Lease Start: ' + (events.lease.title.length > 10 ? 
                          events.lease.title.substring(0, 10) + '...' : events.lease.title),
                    class: 'event-lease'
                });
            }
            if (events.lease.end && dateStr === events.lease.end) {
                dayEvents.push({
                    type: 'lease',
                    title: 'Lease End: ' + (events.lease.title.length > 10 ? 
                          events.lease.title.substring(0, 10) + '...' : events.lease.title),
                    class: 'event-lease'
                });
            }
            
            // Bill events
            events.bills.forEach(bill => {
                if (bill.date === dateStr) {
                    let statusClass = '';
                    let statusText = '';
                    
                    if (bill.status === 'overdue' || (bill.status === 'unpaid' && dateObj < today)) {
                        statusClass = 'event-overdue';
                        statusText = 'Overdue: ';
                    } else if (bill.status === 'unpaid') {
                        statusClass = 'event-bill';
                        statusText = 'Due: ';
                    } else if (bill.status === 'pending') {
                        statusClass = 'event-bill';
                        statusText = 'Pending: ';
                    } else if (bill.status === 'rejected') {
                        statusClass = 'event-overdue';
                        statusText = 'Rejected: ';
                    } else if (bill.status === 'verified' || bill.status === 'paid') {
                        statusClass = 'event-bill';
                        statusText = 'Paid: ';
                    }
                    
                    dayEvents.push({
                        type: 'bill',
                        title: statusText + (bill.title.length > 10 ? 
                              bill.title.substring(0, 10) + '...' : bill.title),
                        class: statusClass
                    });
                }
            });
            
            // Announcements
            events.announcements.forEach(announcement => {
                if (announcement.date.startsWith(dateStr)) {
                    dayEvents.push({
                        type: 'announcement',
                        title: announcement.title,
                        class: 'event-announcement'
                    });
                }
            });
            
            // Maintenance
            events.maintenance.forEach(maint => {
                if (maint.date === dateStr) {
                    dayEvents.push({
                        type: 'maintenance',
                        title: 'Maintenance: ' + (maint.title.length > 10 ? 
                              maint.title.substring(0, 10) + '...' : maint.title),
                        class: 'event-maintenance'
                    });
                }
            });
            
            let classes = 'calendar-day';
            if (isToday) classes += ' today';
            if (dayEvents.length > 0) classes += ' has-events';
            
            let eventsHTML = '';
            dayEvents.forEach(event => {
                eventsHTML += `<div class="calendar-event ${event.class}" title="${event.title}">${event.title}</div>`;
            });
            
            calendarHTML += `
                <div class="${classes}" onclick="showDayEvents('${dateStr}')">
                    <span class="calendar-day-number">${day}</span>
                    ${eventsHTML}
                </div>
            `;
        }

        // Next month days
        const totalCells = Math.ceil((firstDay + daysInMonth) / 7) * 7;
        const remainingCells = totalCells - (firstDay + daysInMonth);
        for (let day = 1; day <= remainingCells; day++) {
            calendarHTML += `<div class="calendar-day other-month">${day}</div>`;
        }

        document.getElementById('calendar-grid').innerHTML = calendarHTML;
    }

    function changeMonth(direction) {
        currentDate.setMonth(currentDate.getMonth() + direction);
        renderCalendar();
    }

    function showDayEvents(dateStr) {
        console.log('Events for', dateStr);
    }

    function setFilter(filter) {
        window.location.href = '?filter=' + filter;
    }

    function viewAllNotices() {
        window.location.href = '../notices.php';
    }

    function maintenanceRequest() {
        window.location.href = '../maintenance.php';
    }

    function viewPaymentHistory() {
        window.location.href = '../payments.php';
    }

    function viewLease() {
        window.location.href = '../lease.php';
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderCalendar();
    });atus === 'verified' || bill.status === 'paid') {
                        statusClass = 'event-bill';
                        statusText = 'Paid: ';
                    }
                    
                    dayEvents.push({
                        type: 'bill',
                        title: statusText + (bill.title.length > 10 ? 
                              bill.title.substring(0, 10) + '...' : bill.title),
                        class: statusClass
                    });
                }
            });
            
            // Announcements
            events.announcements.forEach(announcement => {
                if (announcement.date.startsWith(dateStr)) {
                    dayEvents.push({
                        type: 'announcement',
                        title: announcement.title,
                        class: 'event-announcement'
                    });
                }
            });
            
            // Maintenance (would work similarly if you have the data)
            events.maintenance.forEach(maint => {
                if (maint.date === dateStr) {
                    dayEvents.push({
                        type: 'maintenance',
                        title: 'Maintenance: ' + (maint.title.length > 10 ? 
                              maint.title.substring(0, 10) + '...' : maint.title),
                        class: 'event-maintenance'
                    });
                }
            });
            
            let classes = 'calendar-day';
            if (isToday) classes += ' today';
            if (dayEvents.length > 0) classes += ' has-events';
            
            let eventsHTML = '';
            dayEvents.forEach(event => {
                eventsHTML += `<div class="calendar-event ${event.class}" title="${event.title}">${event.title}</div>`;
            });
            
            calendarHTML += `
                <div class="${classes}" onclick="showDayEvents('${dateStr}')">
                    <span class="calendar-day-number">${day}</span>
                    ${eventsHTML}
                </div>
            `;
        }

        // Next month days
        const totalCells = Math.ceil((firstDay + daysInMonth) / 7) * 7;
        const remainingCells = totalCells - (firstDay + daysInMonth);
        for (let day = 1; day <= remainingCells; day++) {
            calendarHTML += `<div class="calendar-day other-month">${day}</div>`;
        }

        document.getElementById('calendar-grid').innerHTML = calendarHTML;
    }
</script>
</head>

<body>
    <?php include '../includes/navbar/tenant-navbar.php'; ?>

    <div class="content-wrapper">
        <div class="dashboard-grid">
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
        </div>
    </div>

</body>
</html>