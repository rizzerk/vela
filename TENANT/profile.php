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

    // File upload handling
    $profilePicName = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
        $fileName = $_FILES['profile_pic']['name'];
        $fileSize = $_FILES['profile_pic']['size'];
        $fileType = mime_content_type($fileTmpPath);
        
        if (!in_array($fileType, $allowedTypes)) {
            $errorMsg = "Only JPG, PNG, and GIF images are allowed.";
        } elseif ($fileSize > 2 * 1024 * 1024) { // 2MB limit
            $errorMsg = "Image size should not exceed 2MB.";
        } else {
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = "profile_" . $userId . "_" . time() . "." . $ext;
            $uploadDir = "../uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $profilePicName = $newFileName;
            } else {
                $errorMsg = "Failed to upload image.";
            }
        }
    }

    if (empty($errorMsg)) {
        if ($profilePicName) {
            // Update including profile pic
            $updateStmt = $conn->prepare("UPDATE USERS SET name = ?, email = ?, phone = ?, profile_pic = ? WHERE user_id = ?");
            if ($profilePicName) {
    $updateStmt = $conn->prepare("UPDATE USERS SET name = ?, email = ?, phone = ?, profile_pic = ? WHERE user_id = ?");
    $updateStmt->bind_param("ssssi", $newName, $newEmail, $newPhone, $profilePicName, $userId);
} else {
    $updateStmt = $conn->prepare("UPDATE USERS SET name = ?, email = ?, phone = ? WHERE user_id = ?");
    $updateStmt->bind_param("sssi", $newName, $newEmail, $newPhone, $userId); // ✅ Correct
}
 
        } else {
            // Update without changing profile pic
            $updateStmt = $conn->prepare("UPDATE USERS SET name = ?, email = ?, phone = ? WHERE user_id = ?");
            $updateStmt->bind_param("sssi", $newName, $newEmail, $newPhone, $userId);
        }

        if ($updateStmt->execute()) {
            $successMsg = "Profile updated successfully!";
            $_SESSION['name'] = $newName;
        } else {
            $errorMsg = "Failed to update profile.";
        }
    }
}

// Fetch user data including profile pic
$userStmt = $conn->prepare("SELECT name, email, phone, profile_pic FROM USERS WHERE user_id = ?");
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
if ($lease) {
    $landlordStmt->bind_param("ii", $lease['property_id'], $userId);
    $landlordStmt->execute();
    $landlordResult = $landlordStmt->get_result();
    $landlord = $landlordResult->fetch_assoc();
    if ($landlord) {
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

    .profile-pic-container {
      position: relative;
      margin-bottom: 25px;
    }

    .profile-pic {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background: linear-gradient(135deg, #e0e7ff, #d1e0fd);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 4rem;
      color: var(--primary);
      border: 4px solid white;
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .profile-pic img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .upload-overlay {
      position: absolute;
      bottom: 0;
      right: 0;
      background: var(--primary);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      cursor: pointer;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
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

    .documents-section {
      margin-top: 30px;
    }

    .documents-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 20px;
      margin-top: 15px;
    }

    .document-card {
      border: 2px solid var(--border);
      border-radius: 10px;
      height: 150px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      padding: 15px 10px;
      transition: all 0.3s;
      cursor: pointer;
      background: white;
    }

    .document-card:hover {
      border-color: var(--primary);
      transform: translateY(-5px);
      box-shadow: var(--shadow);
    }

    .document-preview {
      width: 100%;
      height: 80px;
      background: repeating-linear-gradient(
        45deg,
        #f0f4ff,
        #f0f4ff 10px,
        #e2e8ff 10px,
        #e2e8ff 20px
      );
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      font-size: 2rem;
    }

    .document-label {
      margin-top: 10px;
      font-weight: 600;
      color: var(--dark);
      text-align: center;
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

    /* Responsive */
    @media (max-width: 768px) {
      .profile-content {
        flex-direction: column;
      }
      
      .right-panel {
        border-left: none;
        padding-left: 0;
      }
      
      .documents-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .profile-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .btn-group {
        width: 100%;
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
          <div class="profile-pic-container">
            <div class="profile-pic" id="profileImageDisplay">
              <?php if (!empty($user['profile_pic']) && file_exists("../uploads/" . $user['profile_pic'])): ?>
                <img src="../uploads/<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile Picture">
              <?php else: ?>
                <i class="fas fa-user"></i>
              <?php endif; ?>
            </div>
            <div class="upload-overlay" id="changeImageBtn">
              <i class="fas fa-camera"></i>
            </div>
          </div>
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
          <?php if ($lease): ?>
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
            <?php if ($landlordName): ?>
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
      
      <form method="POST" action="" enctype="multipart/form-data" id="profileForm">
        <?php if ($successMsg): ?>
          <div class="message success-msg">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
          </div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
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
        
        <div class="form-group">
          <label>Profile Image</label>
          <div class="profile-pic-container" style="margin: 0 auto 20px;">
            <div class="profile-pic" id="modalProfileImage">
              <?php if (!empty($user['profile_pic']) && file_exists("../uploads/" . $user['profile_pic'])): ?>
                <img src="../uploads/<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile Picture">
              <?php else: ?>
                <i class="fas fa-user"></i>
              <?php endif; ?>
            </div>
          </div>
          <input type="file" id="profile_pic" name="profile_pic" accept="image/*" style="display: none;">
          <button type="button" class="btn btn-outline" id="uploadImageBtn" style="width: 100%;">
            <i class="fas fa-cloud-upload-alt"></i> Change Profile Image
          </button>
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
    const uploadImageBtn = document.getElementById('uploadImageBtn');
    const profilePicInput = document.getElementById('profile_pic');
    const modalProfileImage = document.getElementById('modalProfileImage');
    const profileImageDisplay = document.getElementById('profileImageDisplay');
    
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
    
    uploadImageBtn.addEventListener('click', () => {
      profilePicInput.click();
    });
    
    profilePicInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
          // Update modal preview
          modalProfileImage.innerHTML = '';
          const img = document.createElement('img');
          img.src = event.target.result;
          modalProfileImage.appendChild(img);
          
          // Update main profile preview
          profileImageDisplay.innerHTML = '';
          const mainImg = document.createElement('img');
          mainImg.src = event.target.result;
          profileImageDisplay.appendChild(mainImg);
        };
        reader.readAsDataURL(file);
      }
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