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
                'title' => 'Announcement: ' . (announcement['title'].length > 10 ? 
                          announcement['title'].substring(0, 10) + '...' : announcement['title'])
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