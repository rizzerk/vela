<?php 
session_start();
include '../connection.php';

date_default_timezone_set('Asia/Manila');

// Get current month/year or use GET params
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$statusFilter = $_GET['status'] ?? '';

// Fetch all maintenance requests with optional status filtering
$query = "
    SELECT 
        mr.request_id, 
        mr.status, 
        mr.description, 
        mr.requested_at, 
        mr.image_path,
        u.name AS service_provider
    FROM MAINTENANCE_REQUEST mr
    JOIN LEASE l ON mr.lease_id = l.lease_id
    JOIN USERS u ON l.tenant_id = u.user_id
    " . ($statusFilter ? "WHERE mr.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'" : "") . "
    ORDER BY mr.requested_at DESC
";
$result = mysqli_query($conn, $query);
$requests = [];
while ($row = mysqli_fetch_assoc($result)) {
    $requests[] = $row;
}

// Calendar generation
$start = strtotime("$year-$month-01");
$daysInMonth = date("t", $start);
$firstDay = date("w", $start);
$totalCells = $firstDay + $daysInMonth;
$calendarDays = [];
for ($i = 0; $i < $totalCells; $i++) {
    $calendarDays[] = ($i < $firstDay) ? "" : $i - $firstDay + 1;
}

// For navigation
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
  <meta charset="UTF-8">
  <title>Maintenance Requests - VELA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; background-color: #f6f6f6; color: #1e293b; }
    .sidebar { position: fixed; left: 0; top: 0; width: 250px; height: 100vh; background: #1666ba; padding: 2rem 0; z-index: 1000; }
    .sidebar-header { text-align: center; margin-bottom: 2rem; padding: 0 1rem; }
    .sidebar-header h2 { color: white; font-size: 1.5rem; font-weight: 700; }
    .nav-menu { list-style: none; }
    .nav-item { margin-bottom: 0.5rem; }
    .nav-link { display: flex; align-items: center; padding: 1rem 1.5rem; color: rgba(255, 255, 255, 0.8); text-decoration: none; transition: all 0.3s ease; font-size: 0.9rem; }
    .nav-link:hover, .nav-link.active { background: rgba(255, 255, 255, 0.1); color: white; }
    .nav-link i { margin-right: 0.75rem; width: 16px; }
    .main-content { margin-left: 250px; padding: 2rem; }
    .header { text-align: center; margin-bottom: 2rem; }
    .header h1 { font-size: 2.5rem; color: #1e293b; font-weight: bold; }
    .calendar { background: white; border-radius: 16px; box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1); padding: 1.5rem; overflow-x: auto; margin-bottom: 2rem; }
    .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .calendar-header h2 { font-size: 1.5rem; color: #1e293b; }
    .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1rem; }
    .calendar-cell { background: #f1f5f9; border-radius: 8px; min-height: 80px; padding: 0.5rem; }
    .calendar-cell span { font-weight: bold; display: block; margin-bottom: 0.25rem; }
    .request { background: #1666ba; color: white; border-radius: 4px; padding: 0.3rem 0.5rem; margin-top: 0.25rem; font-size: 0.8rem; }
    .table-container { background: white; border-radius: 16px; box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1); overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 1rem; border-bottom: 1px solid #e2e8f0; text-align: left; }
    th { background: #1666ba; color: white; }
    select, .status-filter { padding: 0.4rem; border-radius: 4px; border: 1px solid #cbd5e1; }
    .filter-container { margin-bottom: 1rem; display: flex; justify-content: flex-end; gap: 1rem; }
    img.thumbnail { max-width: 60px; border-radius: 4px; }
    @media (max-width: 1024px) { .sidebar { width: 200px; } .main-content { margin-left: 200px; } }
    @media (max-width: 768px) { .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; } .main-content { margin-left: 0; padding: 1rem; } .calendar-grid { grid-template-columns: repeat(2, 1fr); } .header h1 { font-size: 2rem; } }
  </style>
  <script>
    function updateStatus(id, status) {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', 'update-status.php', true);
      xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        if (xhr.status !== 200) {
          alert('Failed to update status');
        } else {
          location.reload();
        }
      };
      xhr.send(`request_id=${id}&status=${status}`);
    }
  </script>
</head>
<body>
<?php include ('../includes/navbar/landlord-sidebar.html'); ?>
<div class="main-content">
  <div class="header">
    <h1>Maintenance Requests</h1>
  </div>
  <div class="calendar">
    <div class="calendar-header">
      <h2><?= date("F Y", $start) ?></h2>
      <div>
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>&status=<?= $statusFilter ?>"><button>&lt;</button></a>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>&status=<?= $statusFilter ?>"><button>&gt;</button></a>
      </div>
    </div>
    <div class="calendar-grid">
      <?php foreach ($calendarDays as $day): ?>
        <div class="calendar-cell">
          <?php if ($day): ?><span><?= $day ?></span><?php endif; ?>
          <?php foreach ($requests as $req): ?>
            <?php if ($day && date('j', strtotime($req['requested_at'])) == $day && date('n', strtotime($req['requested_at'])) == $month && date('Y', strtotime($req['requested_at'])) == $year): ?>
              <div class="request">#<?= $req['request_id'] ?></div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
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

  <div class="table-container">
    <table>
     <thead>
  <tr>
    <th>Request ID</th>
    <th>Status</th>
    <th>Issue</th>
    <th>Image</th> <!-- Moved here -->
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
          <option value="in_progress" <?= $req['status'] === 'in_progress' ? 'selected' : '' ?>>In progress</option>
          <option value="resolved" <?= $req['status'] === 'resolved' ? 'selected' : '' ?>>Done</option>
        </select>
      </td>
      <td><?= htmlspecialchars($req['description']) ?></td>
      <td>
        <?php if (!empty($req['image_path'])): ?>
          <a href="<?= htmlspecialchars($req['image_path']) ?>" target="_blank">
            <img src="<?= htmlspecialchars($req['image_path']) ?>" style="width: 60px; border-radius: 4px;">
          </a>
        <?php else: ?>
          N/A
        <?php endif; ?>
      </td>
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
