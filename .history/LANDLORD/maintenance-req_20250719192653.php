<?php
session_start();
require_once "../connection.php";
require_once "../includes/auth/landlord_auth.php";
require_once "../vendor/autoload.php"; // Path to autoload.php from Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load email configuration
$emailConfig = [
    'host' => 'smtp.gmail.com',  // e.g., smtp.gmail.com
    'username' => 'velacinco5@gmail.com',
    'password' => 'aycm atee woxl lmvj',
    'port' => 465,  // Typically 587 for TLS, 465 for SSL
    'encryption' => 'ssl',  // 'tls' or 'ssl'
    'from_email' => 'velacinco5@gmail.com',
    'from_name' => 'VELA Cinco Rentals'
];

// Function to send email notification
function sendStatusEmail($conn, $requestId, $status, $emailConfig) {
    // Get request details and tenant email
    $stmt = $conn->prepare("
        SELECT u.email, u.name, mr.request_id, mr.status, mr.issue_type, p.title AS property_title
        FROM MAINTENANCE_REQUEST mr
        JOIN LEASE l ON mr.lease_id = l.lease_id
        JOIN USERS u ON l.tenant_id = u.user_id
        JOIN PROPERTY p ON l.property_id = p.property_id
        WHERE mr.request_id = ?
    ");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if (!$request) return false;

    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $emailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['username'];
        $mail->Password = $emailConfig['password'];
        $mail->SMTPSecure = $emailConfig['encryption'];
        $mail->Port = $emailConfig['port'];

        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($request['email'], $request['name']);

        // Content
        $mail->isHTML(true);
        
        // Status mapping for display
        $statusDisplay = [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'rejected' => 'Rejected'
        ];
        
        $mail->Subject = 'Maintenance Request #' . $requestId . ' Status Update';
        $mail->Body = '
            <h2>Maintenance Request Status Update</h2>
            <p>Hello ' . htmlspecialchars($request['name']) . ',</p>
            <p>The status of your maintenance request has been updated:</p>
            <ul>
                <li><strong>Request ID:</strong> #' . $requestId . '</li>
                <li><strong>Property:</strong> ' . htmlspecialchars($request['property_title']) . '</li>
                <li><strong>Issue:</strong> ' . htmlspecialchars($request['issue_type']) . '</li>
                <li><strong>New Status:</strong> ' . $statusDisplay[$status] . '</li>
            </ul>';
        
        if ($status === 'in_progress') {
            $mail->Body .= '<p>Your request has been accepted and is now being processed.</p>';
        } elseif ($status === 'rejected') {
            $mail->Body .= '<p>Your request has been reviewed but cannot be processed at this time.</p>';
        } elseif ($status === 'resolved') {
            $mail->Body .= '<p>Your maintenance request has been completed.</p>';
        }
        
        $mail->Body .= '<p>Thank you for using our services.</p>';
        
        $mail->AltBody = strip_tags($mail->Body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle issue type update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['issue_type'])) {
    header('Content-Type: application/json');
    $requestId = intval($_POST['request_id']);
    $issueType = trim($_POST['issue_type']);

    $stmt = $conn->prepare("UPDATE MAINTENANCE_REQUEST SET issue_type = ?, updated_at = NOW() WHERE request_id = ?");
    $stmt->bind_param("si", $issueType, $requestId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update issue type']);
    }

    $stmt->close();
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    header('Content-Type: application/json');
    $requestId = intval($_POST['request_id']);
    $status = $_POST['status'];
    $allowed = ['pending', 'in_progress', 'resolved', 'rejected'];

    if (!in_array($status, $allowed, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE MAINTENANCE_REQUEST SET status = ?, updated_at = NOW() WHERE request_id = ?");
    $stmt->bind_param("si", $status, $requestId);

    if ($stmt->execute()) {
        // Send email notification for status changes (except pending)
        if ($status !== 'pending') {
            sendStatusEmail($conn, $requestId, $status, $emailConfig);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

    $stmt->close();
    exit;
}

// Handle GET requests for displaying maintenance requests
date_default_timezone_set('Asia/Manila');

$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$statusFilter = $_GET['status'] ?? '';

$todayDay = date('j');
$todayMonth = date('n');
$todayYear = date('Y');

$whereClauses = [];
if (!empty(trim($statusFilter))) {
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
        u.name AS tenant_name,
        p.title AS property_title
    FROM MAINTENANCE_REQUEST mr
    JOIN LEASE l ON mr.lease_id = l.lease_id
    JOIN USERS u ON l.tenant_id = u.user_id
    JOIN PROPERTY p ON l.property_id = p.property_id
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

    button {
  background: #1666ba;
  color: white;
  padding: 0.4rem 0.8rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

button:hover {
  opacity: 0.9;
}

button[style*="background: #28a745"]:hover {
  background: #218838 !important;
}

button[style*="background: #dc3545"]:hover {
  background: #c82333 !important;
}

/* Custom Dialog Styles */
.custom-dialog {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 10000;
  justify-content: center;
  align-items: center;
}

.dialog-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
  text-align: center;
}

.dialog-title {
  font-size: 1.5rem;
  margin-bottom: 1rem;
  font-weight: 600;
}

.dialog-message {
  margin-bottom: 1.5rem;
  color: #555;
}

.dialog-buttons {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.dialog-button {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  min-width: 80px;
}

.dialog-button-confirm {
  background-color: #28a745;
  color: white;
}

.dialog-button-cancel {
  background-color: #6c757d;
  color: white;
}

.dialog-button-reject {
  background-color: #dc3545;
  color: white;
}

.accept-dialog .dialog-title {
  color: #28a745;
}

.reject-dialog .dialog-title {
  color: #dc3545;
}

.accept-dialog .dialog-button-confirm:hover {
  background-color: #218838;
}

.reject-dialog .dialog-button-confirm:hover {
  background-color: #c82333;
}
  </style>
</head>
<body>

<?php include ('../includes/navbar/landlord-sidebar.php'); ?>

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
        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
    </form>
  </div>

<div id="acceptDialog" class="custom-dialog accept-dialog">
  <div class="dialog-content">
    <div class="dialog-title">Confirm Acceptance</div>
    <div class="dialog-message">Are you sure you want to accept this maintenance request? The status will be set to "In Progress".</div>
    <div class="dialog-buttons">
      <button class="dialog-button dialog-button-confirm" onclick="confirmAccept()">Accept</button>
      <button class="dialog-button dialog-button-cancel" onclick="closeDialog('acceptDialog')">Cancel</button>
    </div>
  </div>
</div>

<div id="rejectDialog" class="custom-dialog reject-dialog">
  <div class="dialog-content">
    <div class="dialog-title">Confirm Rejection</div>
    <div class="dialog-message">Are you sure you want to reject this maintenance request? The status will be set to "Rejected" and the tenant will be notified.</div>
    <div class="dialog-buttons">
      <button class="dialog-button dialog-button-confirm dialog-button-reject" onclick="confirmReject()">Reject</button>
      <button class="dialog-button dialog-button-cancel" onclick="closeDialog('rejectDialog')">Cancel</button>
    </div>
  </div>
</div> 
 
  <div class="calendar">
    <div class="calendar-header">
      <h2><?= date("F Y", $start) ?></h2>
      <div>
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?><?= $statusFilter !== '' ? '&status=' . htmlspecialchars($statusFilter) : '' ?>">
<button>&lt;</button></a>
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?><?= $statusFilter !== '' ? '&status=' . htmlspecialchars($statusFilter) : '' ?>">
<button>&gt;</button></a>
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
            <th>Property</th>

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
    <th>Actions</th>
    <th>Property</th>

  </tr>
</thead>
      <tbody>
       <?php foreach ($requests as $req): ?>
  <tr>
    <td>#<?= $req['request_id'] ?></td>
    <td>
<select onchange="updateStatus(<?= $req['request_id'] ?>, this.value)" <?= in_array($req['status'], ['resolved', 'rejected']) ? 'disabled' : '' ?>>
        <option value="pending" <?= $req['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="in_progress" <?= $req['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="resolved" <?= $req['status'] === 'resolved' ? 'selected' : '' ?>>Done</option>
        <option value="rejected" <?= $req['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
    </td>
    <td style="min-width: 250px;">
      <div style="display: flex; align-items: center; gap: 10px;" id="issue-container-<?= $req['request_id'] ?>">
        <span id="display-text-<?= $req['request_id'] ?>" style="flex: 1;">
          <?= htmlspecialchars($req['issue_type']) ?>
        </span>
        <button id="edit-button-<?= $req['request_id'] ?>" onclick="editIssueType(<?= $req['request_id'] ?>)">Edit</button>
        <input type="text" id="input-issue-<?= $req['request_id'] ?>" value="<?= htmlspecialchars($req['issue_type']) ?>" 
               style="flex: 1; padding: 6px 8px; border: 1px solid #ccc; border-radius: 6px; display: none;">
        <button id="save-button-<?= $req['request_id'] ?>" onclick="saveIssueType(<?= $req['request_id'] ?>)" style="display: none;">Save</button>
      </div>
    </td>
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
          
  
    <td>
  <div style="display: flex; gap: 5px;">
    <?php if ($req['status'] === 'pending'): ?>
  <button onclick="acceptRequest(<?= $req['request_id'] ?>)" style="background: #28a745;">Accept</button>
  <button onclick="rejectRequest(<?= $req['request_id'] ?>)" style="background: #dc3545;">Reject</button>
<?php elseif ($req['status'] === 'in_progress'): ?>
  <span style="color: #28a745; font-weight: 500;">Accepted</span>
<?php elseif ($req['status'] === 'resolved'): ?>
  <span style="color: #28a745; font-weight: 500;">Accepted</span>
<?php elseif ($req['status'] === 'rejected'): ?>
  <span style="color: #dc3545; font-weight: 500;">Rejected</span>
<?php else: ?>
  <span>-</span>
<?php endif; ?>

  </div>
</td>

    <td><?= htmlspecialchars($req['property_title']) ?></td>


  </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  const requestsByDay = <?= json_encode($requestsByDay) ?>;

  let selectedRequestId = null;

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
tdStatus.textContent = req.status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
tr.appendChild(tdStatus);


        const tdIssue = document.createElement('td');
tdIssue.textContent = req.issue_type; // read-only
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

        const tdProperty = document.createElement('td');
tdProperty.textContent = req.tenant_name;
tr.appendChild(tdProperty);

        const tdTenant = document.createElement('td');
        tdTenant.textContent = req.property_title;
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
window.location.href = window.location.pathname + '?month=<?= $month ?>&year=<?= $year ?>&status=<?= $statusFilter ?>';
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

  function updateIssueType(id, issueType) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '', true); 
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      if (xhr.status === 200) {
        try {
          const res = JSON.parse(xhr.responseText);
          if (!res.success) {
            alert('Failed to update issue type: ' + (res.message || 'Unknown error'));
          }
        } catch {
          alert('Invalid response');
        }
      } else {
        alert('Request failed');
      }
    };
    xhr.send(`request_id=${id}&issue_type=${encodeURIComponent(issueType)}`);
  }

  function editIssueType(id) {
  document.getElementById(`display-text-${id}`).style.display = 'none';
  document.getElementById(`edit-button-${id}`).style.display = 'none';

  document.getElementById(`input-issue-${id}`).style.display = 'inline-block';
  document.getElementById(`save-button-${id}`).style.display = 'inline-block';
}

function saveIssueType(id) {
  const newValue = document.getElementById(`input-issue-${id}`).value;

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '', true);
  xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  xhr.onload = function () {
    if (xhr.status === 200) {
      try {
        const res = JSON.parse(xhr.responseText);
        if (res.success) {
          document.getElementById(`display-text-${id}`).innerText = newValue;

          // Toggle views
          document.getElementById(`display-text-${id}`).style.display = 'inline-block';
          document.getElementById(`edit-button-${id}`).style.display = 'inline-block';

          document.getElementById(`input-issue-${id}`).style.display = 'none';
          document.getElementById(`save-button-${id}`).style.display = 'none';
        } else {
          alert('Failed to update issue type: ' + (res.message || 'Unknown error'));
        }
      } catch {
        alert('Invalid server response');
      }
    } else {
      alert('Request failed');
    }
  };
  xhr.send(`request_id=${id}&issue_type=${encodeURIComponent(newValue)}`);
}

function acceptRequest(id) {
  selectedRequestId = id;
  document.getElementById('acceptDialog').style.display = 'flex';
}

function rejectRequest(id) {
  selectedRequestId = id;
  document.getElementById('rejectDialog').style.display = 'flex';
}


function confirmAccept() {
  if (selectedRequestId !== null) {
    updateStatus(selectedRequestId, 'in_progress');
    closeDialog('acceptDialog');
  }
}

function confirmReject() {
  if (selectedRequestId !== null) {
    updateStatus(selectedRequestId, 'rejected');
    closeDialog('rejectDialog');
  }
}

function closeDialog(dialogId) {
  document.getElementById(dialogId).style.display = 'none';
  selectedRequestId = null;
}


document.addEventListener('keydown', function (e) {
  if (e.key === "Escape") {
    closeDialog('acceptDialog');
    closeDialog('rejectDialog');
  }
});


</script>