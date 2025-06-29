<?php
session_start();
include '../connection.php';

// Fetch maintenance requests
$query = "
    SELECT 
        mr.request_id, 
        mr.status, 
        mr.description, 
        mr.requested_at, 
        u.name AS service_provider
    FROM MAINTENANCE_REQUEST mr
    JOIN LEASE l ON mr.lease_id = l.lease_id
    JOIN USERS u ON l.tenant_id = u.user_id
    ORDER BY mr.requested_at DESC
";
$result = mysqli_query($conn, $query);
$requests = [];
while ($row = mysqli_fetch_assoc($result)) {
    $requests[] = $row;
}

// Generate calendar days for February 2025
$calendarDays = [];
$start = strtotime("2025-02-01");
$daysInMonth = date("t", $start);
$firstDay = date("w", $start);
$totalCells = $firstDay + $daysInMonth;

for ($i = 0; $i < $totalCells; $i++) {
    $calendarDays[] = ($i < $firstDay) ? "" : $i - $firstDay + 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Maintenance Requests - VELA</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background-color: #f6f6f6; color: #1e293b; }
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      width: 250px;
      height: 100vh;
      background-color: #1e293b;
      padding: 2rem 1rem;
    }
    .sidebar h2 {
      color: white;
      text-align: center;
      margin-bottom: 2rem;
      font-size: 1.5rem;
    }
    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      padding: 0.75rem 1rem;
      margin-bottom: 0.5rem;
      border-radius: 8px;
      background: #334155;
      transition: background 0.3s ease;
    }
    .sidebar a.active, .sidebar a:hover {
      background-color: #1666ba;
    }
    .main-content {
      margin-left: 250px;
      padding: 2rem;
    }
    .header {
      text-align: center;
      margin-bottom: 2rem;
    }
    .header h1 {
      font-size: 2.5rem;
      color: #1666ba;
      font-weight: 800;
    }
    .calendar {
      background: white;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
      padding: 1.5rem;
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
      border-radius: 8px;
      min-height: 80px;
      padding: 0.5rem;
    }
    .calendar-cell span {
      font-weight: bold;
      display: block;
      margin-bottom: 0.25rem;
    }
    .request {
      background: #1666ba;
      color: white;
      border-radius: 4px;
      padding: 0.3rem 0.5rem;
      margin-top: 0.25rem;
      font-size: 0.8rem;
    }
    .table-container {
      background: white;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
      margin-top: 2rem;
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
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
    td select, td input {
      padding: 0.4rem;
      border-radius: 4px;
      border: 1px solid #cbd5e1;
    }
    @media (max-width: 768px) {
      .main-content { margin-left: 0; padding: 1rem; }
      .sidebar { position: relative; width: 100%; height: auto; padding: 1rem; }
      .calendar-grid { grid-template-columns: repeat(2, 1fr); }
      .header h1 { font-size: 2rem; }
    }
  </style>
  <script>
    function updateStatus(requestId, newStatus) {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'update-status.php', true);
      xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        if (xhr.status !== 200) {
          alert('Failed to update status');
        }
      };
      xhr.send(`request_id=${requestId}&status=${newStatus}`);
    }
  </script>
</head>
<body>
  <div class="sidebar">
    <h2>VELA</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="#" class="active">Maintenance Requests</a>
    <a href="logout.php">Logout</a>
  </div>

  <div class="main-content">
    <div class="header">
      <h1>Maintenance Requests</h1>
    </div>

    <div class="calendar">
      <div class="calendar-header">
        <h2>February 2025</h2>
        <div>
          <button disabled><</button>
          <button disabled>></button>
        </div>
      </div>
      <div class="calendar-grid">
        <?php foreach ($calendarDays as $day): ?>
          <div class="calendar-cell">
            <?php if ($day): ?><span><?= $day ?></span><?php endif; ?>
            <?php foreach ($requests as $req): ?>
              <?php if ($day && date('j', strtotime($req['requested_at'])) == $day): ?>
                <div class="request">#<?= $req['request_id'] ?></div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Request ID</th>
            <th>Status</th>
            <th>Issue</th>
            <th>Date Submitted</th>
            <th>Assigned Service Provider</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $req): ?>
            <tr>
              <td>#<?= $req['request_id'] ?></td>
              <td>
                <select onchange="updateStatus(<?= $req['request_id'] ?>, this.value)">
                  <option value="pending" <?= $req['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="in_progress" <?= $req['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                  <option value="resolved" <?= $req['status'] === 'resolved' ? 'selected' : '' ?>>Done</option>
                </select>
              </td>
              <td><?= htmlspecialchars($req['description']) ?></td>
              <td><?= date('Y-m-d', strtotime($req['requested_at'])) ?></td>
              <td><?= htmlspecialchars($req['service_provider']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>  