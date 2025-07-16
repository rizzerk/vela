<?php
session_start();
require_once "../connection.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    header('Content-Type: application/json');

    $requestId = intval($_POST['request_id']);
    $status = $_POST['status'];
    $allowed = ['pending', 'in_progress', 'resolved'];

    if (!in_array($status, $allowed, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE MAINTENANCE_REQUEST SET status = ?, updated_at = NOW() WHERE request_id = ?");
    $stmt->bind_param("si", $status, $requestId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

    $stmt->close();
    exit;
}

date_default_timezone_set('Asia/Manila');

$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$statusFilter = $_GET['status'] ?? '';

$todayDay = date('j');
$todayMonth = date('n');
$todayYear = date('Y');

$whereClauses = [];
if ($statusFilter) {
    $whereClauses[] = "mr.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
}

$whereSQL = "";
if (!empty($whereClauses)) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

$query = "
    SELECT 
        mr.request_id, 
        mr.status, 
        mr.issue_type, 
        mr.description, 
        mr.requested_at, 
        mr.image_path,
        u.name AS tenant_name
    FROM MAINTENANCE_REQUEST mr
    JOIN LEASE l ON mr.lease_id = l.lease_id
    JOIN USERS u ON l.tenant_id = u.user_id
    $whereSQL
    ORDER BY mr.requested_at DESC
";
$result = mysqli_query($conn, $query);
$requests = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reqMonth = intval(date('n', strtotime($row['requested_at'])));
    $reqYear = intval(date('Y', strtotime($row['requested_at'])));
    if ($reqMonth === $month && $reqYear === $year) {
        $requests[] = $row;
    }
}

$requestsByDay = [];
foreach ($requests as $req) {
    $day = intval(date('j', strtotime($req['requested_at'])));
    $requestsByDay[$day][] = $req;
}

$start = strtotime("$year-$month-01");
$daysInMonth = date("t", $start);
$firstDay = date("w", $start);
$totalCells = $firstDay + $daysInMonth;
$calendarDays = [];
for ($i = 0; $i < $totalCells; $i++) {
    $calendarDays[] = ($i < $firstDay) ? "" : $i - $firstDay + 1;
}

$prevMonth = $month - 1;
$prevYear = $year;
$nextMonth = $month + 1;
$nextYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Maintenance Requests - VELA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
   
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background-color: #f6f6f6; color: #1e293b; }

    .main-content { margin-left: 250px; padding: 2rem; }

    .header { text-align: center; margin-bottom: 2rem; }
    .header h1 { font-size: 2.5rem; color: #1e293b; font-weight: bold; }

    .calendar, .table-container {
      background: white;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
      padding: 1.5rem;
      margin-bottom: 2rem;
      overflow-x: auto;
    }

    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .calendar-header h2 {
      font-size: 1.5rem;
      color: #1e293b;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 1rem;
    }

    .calendar-cell {
      background: #f1f5f9;
      border-radius: 12px;
      min-height: 80px;
      padding: 0.5rem;
      position: relative;
      cursor: pointer;
      transition: background-color 0.3s ease;
      text-decoration: none;
      color: inherit;
      display: flex;
      flex-direction: column;
      user-select: none;
    }

    .calendar-cell:hover {
      background-color: #dbeeff;
    }

    .calendar-cell.today {
      background-color: #e0f0ff;
      border: 2px solid #1666ba;
      box-shadow: 0 0 0 2px #1666ba66;
    }

    .calendar-cell span {
      font-weight: bold;
      margin-bottom: 0.25rem;
    }

    .request {
      background: #1666ba;
      color: white;
      border-radius: 6px;
      padding: 0.2rem 0.4rem;
      margin-top: auto;
      font-size: 0.75rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      user-select: none;
    }

    .view-all {
      margin-top: 4px;
      font-size: 0.75rem;
      color: #104a8f;
      cursor: pointer;
      text-decoration: underline;
    }

    .filter-container {
      margin-top: -1rem;
      margin-bottom: 1rem;
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
    }

    select, .status-filter {
      padding: 0.4rem;
      border-radius: 8px;
      border: 1px solid #cbd5e1;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      border-radius: 12px;
      overflow: hidden;
    }

    th, td {
      padding: 1rem;
      border-bottom: 1px solid #e2e8f0;
      text-align: left;
    }

    th {
      background: #1666ba;
      color: white;
    }

    td select {
      padding: 0.25rem;
      border-radius: 6px;
    }

    img.thumbnail {
      max-width: 60px;
      border-radius: 6px;
    }

    button {
      background: #1666ba;
      color: white;
      padding: 0.4rem 0.8rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    button:hover {
      background: #104a8f;
    }

  
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0; top: 0;
      width: 100%; height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
      padding-top: 50px;
    }

    .modal-content {
      background-color: #fefefe;
      margin: auto;
      padding: 20px;
      border-radius: 12px;
      max-width: 900px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      position: relative;
    }

    .modal-close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .modal-close:hover,
    .modal-close:focus {
      color: #000;
      text-decoration: none;
    }

    .modal-header {
      font-size: 1.5rem;
      margin-bottom: 1rem;
      font-weight: 600;
      color: #1666ba;
    }

    .modal-table {
      width: 100%;
      border-collapse: collapse;
    }

    .modal-table th, .modal-table td {
      padding: 0.5rem;
      border: 1px solid #ddd;
      text-align: left;
      vertical-align: middle;
    }

    .modal-table th {
      background-color: #1666ba;
      color: white;
    }

    .modal-thumbnail {
      max-width: 50px;
      border-radius: 6px;
    }

    @media (max-width: 1024px) {
      .main-content { margin-left: 0; padding: 1rem; }
      .calendar-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 768px) {
      .calendar-grid { grid-template-columns: repeat(2, 1fr); }
      .header h1 { font-size: 2rem; }
    }
  </style>
</head>
<body>

<?php include ('../includes/navbar/landlord-sidebar.html'); ?>

<div class="main-content">
  <div class="header">
    <h1>Maintenance Requests</h1>
  </div>

  <div class="filter-container">
    <form method="get">
      <input type="hidden" name="month" value="<?= $month ?>">
      <input type="hidden" name="year" value="<?= $year ?>">
      <label for="status">Filter:</label>
      <select name="status" class="status-filter" onchange="this.form.submit()">
        <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All</option>
        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="resolved" <?= $statusFilter === 'resolved' ? 'selected' : '' ?>>Done</option>
      </select>
    </form>
  </div>

  <div class="calendar">
    <div class="calendar-header">
      <h2><?= date("F Y", $start) ?></h2>
      <div>
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>&status=<?= htmlspecialchars($statusFilter) ?>"><button>&lt;</button></a>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>&status=<?= htmlspecialchars($statusFilter) ?>"><button>&gt;</button></a>
      </div>
    </div>
    <div class="calendar-grid">
      <?php foreach ($calendarDays as $day): ?>
        <?php
          $isToday = ($day && $day == $todayDay && $month == $todayMonth && $year == $todayYear);
          $cellClass = $isToday ? 'calendar-cell today' : 'calendar-cell';
        ?>
        <?php if ($day): ?>
          <div class="<?= $cellClass ?>" onclick="openModal(<?= $day ?>)">
            <span><?= $day ?></span>

            <?php if (!empty($requestsByDay[$day])): ?>
              <?php 
                $count = 0;
                foreach ($requestsByDay[$day] as $req) {
                  if ($count >= 2) break; 
                  echo '<div class="request">#' . htmlspecialchars($req['request_id']) . ': ' . htmlspecialchars($req['issue_type']) . '</div>';
                  $count++;
                }
                if (count($requestsByDay[$day]) > 2) {
                  echo '<div class="view-all" onclick="event.stopPropagation(); openModal(' . $day . ');">View all (' . count($requestsByDay[$day]) . ')</div>';
                }
              ?>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="<?= $cellClass ?>"></div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <div id="modal" class="modal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
      <span class="modal-close" onclick="closeModal()">&times;</span>
      <div class="modal-header" id="modal-header">Maintenance Requests</div>
      <table class="modal-table" id="modal-table">
        <thead>
          <tr>
            <th>Request ID</th>
            <th>Status</th>
            <th>Issue</th>
            <th>Image</th>
            <th>Date Submitted</th>
            <th>Tenant Name</th>
          </tr>
        </thead>
        <tbody id="modal-body">
        </tbody>
      </table>
    </div>
  </div>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Request ID</th>
          <th>Status</th>
          <th>Issue</th>
          <th>Image</th>
          <th>Date Submitted</th>
          <th>Tenant Name</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $req): ?>
          <tr>
            <td>#<?= $req['request_id'] ?></td>
            <td>
<select onchange="updateStatus(<?= $req['request_id'] ?>, this.value)" <?= $req['status'] === 'resolved' ? 'disabled' : '' ?>>
                <option value="pending" <?= $req['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="in_progress" <?= $req['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="resolved" <?= $req['status'] === 'resolved' ? 'selected' : '' ?>>Done</option>
              </select>
            </td>
            <td><?= htmlspecialchars($req['issue_type']) ?></td>
            <td>
              <?php if (!empty($req['image_path'])): ?>
                <a href="<?= htmlspecialchars($req['image_path']) ?>" target="_blank">
                  <img src="<?= htmlspecialchars($req['image_path']) ?>" class="thumbnail" alt="Request Image">
                </a>
              <?php else: ?>
                N/A
              <?php endif; ?>
            </td>
            <td><?= date('Y-m-d', strtotime($req['requested_at'])) ?></td>
            <td><?= htmlspecialchars($req['tenant_name']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  const requestsByDay = <?= json_encode($requestsByDay) ?>;

  function openModal(day) {
    const modal = document.getElementById('modal');
    const modalBody = document.getElementById('modal-body');
    const modalHeader = document.getElementById('modal-header');

    modalBody.innerHTML = '';

    if (!requestsByDay[day]) {
      modalBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No maintenance requests for this day.</td></tr>';
    } else {
      const date = new Date(<?= $year ?>, <?= $month ?> - 1, day);
      modalHeader.textContent = 'Maintenance Requests for ' + date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });

      requestsByDay[day].forEach(req => {
        const tr = document.createElement('tr');

        const tdId = document.createElement('td');
        tdId.textContent = '#' + req.request_id;
        tr.appendChild(tdId);

        const tdStatus = document.createElement('td');
        const select = document.createElement('select');
        ['pending', 'in_progress', 'resolved'].forEach(status => {
          const option = document.createElement('option');
          option.value = status;
          option.textContent = status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
          if (req.status === status) option.selected = true;
          select.appendChild(option);
        });
        select.onchange = function () {
          updateStatus(req.request_id, this.value);
        };
        tdStatus.appendChild(select);
        tr.appendChild(tdStatus);

        const tdIssue = document.createElement('td');
        tdIssue.textContent = req.issue_type;
        tr.appendChild(tdIssue);

        const tdImage = document.createElement('td');
        if (req.image_path) {
          const a = document.createElement('a');
          a.href = req.image_path;
          a.target = '_blank';
          const img = document.createElement('img');
          img.src = req.image_path;
          img.alt = 'Request Image';
          img.className = 'modal-thumbnail';
          a.appendChild(img);
          tdImage.appendChild(a);
        } else {
          tdImage.textContent = 'N/A';
        }
        tr.appendChild(tdImage);

        const tdDate = document.createElement('td');
        tdDate.textContent = new Date(req.requested_at).toISOString().slice(0, 10);
        tr.appendChild(tdDate);

        const tdTenant = document.createElement('td');
        tdTenant.textContent = req.tenant_name;
        tr.appendChild(tdTenant);

        modalBody.appendChild(tr);
      });
    }

    modal.style.display = 'block';
  }

  function closeModal(event) {
    const modal = document.getElementById('modal');
    if (!event || event.target === modal || event.target.classList.contains('modal-close')) {
      modal.style.display = 'none';
    }
  }

  function updateStatus(id, status) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '', true); 
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      if (xhr.status === 200) {
        try {
          const res = JSON.parse(xhr.responseText);
          if (res.success) {
            window.location.href = window.location.pathname + '?month=<?= $month ?>&year=<?= $year ?>'; 
          } else {
            alert('Update failed: ' + (res.message || 'Unknown error'));
          }
        } catch {
          alert('Invalid server response');
        }
      } else {
        alert('Failed to update status');
      }
    };
    xhr.send(`request_id=${id}&status=${status}`);
  }
</script>