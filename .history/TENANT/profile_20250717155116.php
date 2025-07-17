<?php  
session_start();
require_once "../connection.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'tenant') {
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$successMsg = "";
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName = trim($_POST['name']);
    $newEmail = trim($_POST['email']);
    $newPhone = trim($_POST['phone']);

    // Basic validation
    if (empty($newName)) {
        $errorMsg = "Name is required";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Invalid email format";
    } elseif (empty($newPhone)) {
        $errorMsg = "Phone number is required";
    }

    if (empty($errorMsg)) {
        $updateStmt = $conn->prepare("UPDATE USERS SET name = ?, email = ?, phone = ? WHERE user_id = ?");
        $updateStmt->bind_param("sssi", $newName, $newEmail, $newPhone, $userId);

        if ($updateStmt->execute())) {
            $successMsg = "Profile updated successfully!";
            $_SESSION['name'] = $newName;
        } else {
            $errorMsg = "Failed to update profile.";
        }
    }
}

// Fetch user data
$userStmt = $conn->prepare("SELECT name, email, phone FROM USERS WHERE user_id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

$leaseStmt = $conn->prepare("
    SELECT l.lease_id, l.start_date, l.end_date, p.title, p.address, p.property_id
    FROM LEASE l
    JOIN PROPERTY p ON l.property_id = p.property_id
    WHERE l.tenant_id = ? AND l.active = 1
");
$leaseStmt->bind_param("i", $userId);
$leaseStmt->execute();
$leaseResult = $leaseStmt->get_result();
$lease = $leaseResult->fetch_assoc();

$landlordStmt = $conn->prepare("
    SELECT u.name, u.email, u.phone 
    FROM USERS u 
    JOIN PROPERTY p ON p.property_id = ? 
    JOIN LEASE l ON l.property_id = p.property_id 
    WHERE l.tenant_id = ? AND u.role = 'landlord'
    LIMIT 1
");
$landlordName = $landlordEmail = $landlordPhone = null;
if ($lease)) {
    $landlordStmt->bind_param("ii", $lease['property_id'], $userId);
    $landlordStmt->execute();
    $landlordResult = $landlordStmt->get_result();
    $landlord = $landlordResult->fetch_assoc();
    if ($landlord)) {
        $landlordName = $landlord['name'];
        $landlordEmail = $landlord['email'];
        $landlordPhone = $landlord['phone'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tenant Profile - VELA</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    :root {
      --primary: #4a6cf7;
      --primary-dark: #3a5af0;
      --secondary: #6c757d;
      --light: #f8f9fa;
      --dark: #343a40;
      --success: #28a745;
      --error: #dc3545;
      --border: #dee2e6;
      --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      --radius: 12px;
    }

    body {
      background-color: #f5f7fb;
      color: #333;
      min-height: 100vh;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 30px;
      padding: 20px;
      margin-top: 80px;
    }

    .profile-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 0;
    }

    .profile-header h1 {
      font-size: 2rem;
      color: var(--dark);
    }

    .profile-content {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
    }

    .left-panel, .right-panel {
      background: white;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 30px;
      flex: 1;
      min-width: 300px;
    }

    .panel-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--primary);
      color: var(--dark);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .panel-title i {
      color: var(--primary);
    }

    /* Profile Section */
    .profile-section {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 20px 0;
    }

    .profile-name {
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark);
      text-align: center;
    }

    .profile-role {
      color: var(--secondary);
      margin-bottom: 25px;
      text-align: center;
    }

    .info-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 15px;
      width: 100%;
    }

    .info-item {
      display: flex;
      align-items: center;
      padding: 15px;
      background: var(--light);
      border-radius: 8px;
      transition: transform 0.2s;
    }

    .info-item:hover {
      transform: translateY(-3px);
      background: #eef2ff;
    }

    .info-icon {
      width: 40px;
      height: 40px;
      background: var(--primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      margin-right: 15px;
      flex-shrink: 0;
    }

    .info-content h4 {
      font-size: 0.9rem;
      color: var(--secondary);
      margin-bottom: 3px;
    }

    .info-content p {
      font-size: 1.1rem;
      font-weight: 500;
      color: var(--dark);
    }

    /* Contact Section */
    .contact-info {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
    }

    .contact-item {
      display: flex;
      align-items: flex-start;
      padding: 20px;
      background: var(--light);
      border-radius: 10px;
      border-left: 4px solid var(--primary);
    }

    .contact-icon {
      width: 50px;
      height: 50px;
      background: #eef2ff;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      margin-right: 15px;
      flex-shrink: 0;
      font-size: 1.5rem;
    }

    .contact-details h3 {
      font-size: 1.2rem;
      margin-bottom: 8px;
      color: var(--dark);
    }

    .contact-details p {
      color: var(--secondary);
      line-height: 1.6;
    }

    /* Buttons */
    .btn-group {
      display: flex;
      gap: 15px;
      margin-top: 25px;
      flex-wrap: wrap;
    }

    .btn {
      padding: 12px 25px;
      border-radius: 30px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary {
      background: var(--primary);
      color: white;
    }

    .btn-primary:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(74, 108, 247, 0.3);
    }

    .btn-outline {
      background: transparent;
      border: 2px solid var(--primary);
      color: var(--primary);
    }

    .btn-outline:hover {
      background: #eef2ff;
      transform: translateY(-2px);
    }

    .btn-danger {
      background: #ff4757;
      color: white;
    }

    .btn-danger:hover {
      background: #ff2e43;
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(255, 71, 87, 0.3);
    }

    /* Form Styles */
    .form-group {
      margin-bottom: 20px;
      width: 100%;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--dark);
    }

    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.3s;
    }

    .form-control:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.1);
    }

    /* Messages */
    .message {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .success-msg {
      background-color: rgba(40, 167, 69, 0.1);
      color: var(--success);
      border-left: 4px solid var(--success);
    }

    .error-msg {
      background-color: rgba(220, 53, 69, 0.1);
      color: var(--error);
      border-left: 4px solid var(--error);
    }

    /* Modal Styling */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .modal-content {
      background: #fff;
      padding: 30px;
      border-radius: var(--radius);
      width: 100%;
      max-width: 500px;
      box-shadow: var(--shadow);
      position: relative;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .modal-title {
      font-size: 1.5rem;
      color: var(--dark);
    }

    .close-modal {
      font-size: 1.5rem;
      background: none;
      border: none;
      cursor: pointer;
      color: var(--dark);
    }

    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 25px;
    }

    .btn-modal-primary {
      background: var(--primary);
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
    }

    .btn-modal-primary:hover {
      background: var(--primary-dark);
    }

    .btn-secondary {
      background: var(--light);
      border: 2px solid var(--border);
      color: var(--dark);
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .profile-content {
        flex-direction: column;
      }
      
      .right-panel {
        border-left: none;
        padding-left: 0;
      }

      .profile-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .btn-group {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <?php include '../includes/navbar/tenant-navbar.php'; ?>

  <div class="container">
    <div class="profile-header">
      <h1>Tenant Profile</h1>
      <div class="btn-group">
        <button class="btn btn-primary" id="editBtn">
          <i class="fas fa-edit"></i> Edit Profile
        </button>
        <form method="POST" action="../logout.php" style="display: inline;">
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-sign-out-alt"></i> Log Out
          </button>
        </form>
      </div>
    </div>

    <div class="profile-content">
      <!-- Left Panel - Account Information -->
      <div class="left-panel">
        <h2 class="panel-title"><i class="fas fa-user-circle"></i> ACCOUNT INFORMATION</h2>
        
        <div class="profile-section">
          <h2 class="profile-name" id="tenantName"><?= htmlspecialchars($user['name']) ?></h2>
          <div class="profile-role">Tenant</div>
          
          <div class="info-grid">
            <div class="info-item">
              <div class="info-icon">
                <i class="fas fa-envelope"></i>
              </div>
              <div class="info-content">
                <h4>EMAIL</h4>
                <p id="tenantEmail"><?= htmlspecialchars($user['email']) ?></p>
              </div>
            </div>
            
            <div class="info-item">
              <div class="info-icon">
                <i class="fas fa-phone"></i>
              </div>
              <div class="info-content">
                <h4>PHONE</h4>
                <p id="tenantPhone"><?= htmlspecialchars($user['phone']) ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Right Panel - Lease & Landlord Info -->
      <div class="right-panel">
        <h2 class="panel-title"><i class="fas fa-file-contract"></i> LEASE AGREEMENT</h2>
        
        <div class="contact-info">
          <?php if ($lease)): ?>
            <div class="contact-item">
              <div class="contact-icon">
                <i class="fas fa-home"></i>
              </div>
              <div class="contact-details">
                <h3><?= htmlspecialchars($lease['title']) ?></h3>
                <p><?= htmlspecialchars($lease['address']) ?></p>
                <p><small><?= htmlspecialchars($lease['start_date']) ?> - <?= htmlspecialchars($lease['end_date']) ?></small></p>
                <a href="lease_agreement.php?lease_id=<?= $lease['lease_id'] ?>" class="btn btn-outline" style="margin-top: 10px;" download>
                  <i class="fas fa-download"></i> Download Lease
                </a>
              </div>
            </div>
          <?php else: ?>
            <div class="contact-item">
              <div class="contact-icon">
                <i class="fas fa-exclamation-circle"></i>
              </div>
              <div class="contact-details">
                <h3>No Active Lease</h3>
                <p>You don't currently have an active lease agreement.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
        
        <div class="documents-section">
          <h2 class="panel-title"><i class="fas fa-address-book"></i> LANDLORD CONTACT</h2>
          <div class="contact-info">
            <?php if ($landlordName)): ?>
              <div class="contact-item">
                <div class="contact-icon">
                  <i class="fas fa-user-tie"></i>
                </div>
                <div class="contact-details">
                  <h3><?= htmlspecialchars($landlordName) ?></h3>
                  <p><strong>Email:</strong> <?= htmlspecialchars($landlordEmail) ?></p>
                  <p><strong>Phone:</strong> <?= htmlspecialchars($landlordPhone) ?></p>
                </div>
              </div>
            <?php else: ?>
              <div class="contact-item">
                <div class="contact-icon">
                  <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="contact-details">
                  <h3>No Landlord Information</h3>
                  <p>Landlord contact information is not available.</p>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal" id="editModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title"><i class="fas fa-edit"></i> Edit Profile</h2>
        <button class="close-modal" id="closeModal">&times;</button>
      </div>
      
      <form method="POST" action="" id="profileForm">
        <?php if ($successMsg)): ?>
          <div class="message success-msg">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
          </div>
        <?php endif; ?>
        <?php if ($errorMsg)): ?>
          <div class="message error-msg">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?>
          </div>
        <?php endif; ?>
        
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
        
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        
        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn-secondary" id="cancelEdit">Cancel</button>
          <button type="submit" name="update_profile" class="btn-modal-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // DOM Elements
    const editBtn = document.getElementById('editBtn');
    const editModal = document.getElementById('editModal');
    const closeModal = document.getElementById('closeModal');
    const cancelEdit = document.getElementById('cancelEdit');
    const profileForm = document.getElementById('profileForm');
    
    // Event listeners
    editBtn.addEventListener('click', () => {
      editModal.style.display = 'flex';
    });
    
    closeModal.addEventListener('click', () => {
      editModal.style.display = 'none';
    });
    
    cancelEdit.addEventListener('click', () => {
      editModal.style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
      if (e.target === editModal) {
        editModal.style.display = 'none';
      }
    });
  </script>
</body>
</html>