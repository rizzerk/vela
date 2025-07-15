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
            $updateStmt->bind_param("ssssi", $newName, $newEmail, $newPhone, $profilePicName, $userId);
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
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Tenant Profile - VELA</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

  body {
    margin: 0; 
    padding: 110px 20px 40px; /* top padding to avoid fixed navbar overlap */
    font-family: 'Poppins', sans-serif;
    background: #f0f4ff;
    color: #1e2a78;
    display: flex;
    justify-content: center;
  }

 .profile-wrapper {
    background: #fff;
    max-width: 900px;
    width: 100%;
    display: flex;
    border-radius: 16px;
    box-shadow: 0 15px 35px rgba(22, 102, 186, 0.15);
    overflow: hidden;
    padding: 20px;
    gap: 30px;
    box-sizing: border-box;
    margin: 0 auto;
}

.profile-left {
    flex: 1;
    min-width: 0;
    padding: 30px 20px;
    border-right: 1px solid #ddd;
    box-sizing: border-box;
}

.profile-right {
    flex: 0 0 300px;
    padding: 30px 20px;
    background: #f9faff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-sizing: border-box;
}

  .profile-left h2 {
    font-weight: 700;
    font-size: 1.8rem;
    margin-bottom: 30px;
    letter-spacing: 1px;
  }

  .avatar {
    width: 100px; height: 100px;
    border-radius: 50%;
    margin-bottom: 20px;
    background-size: cover;
    background-position: center;
    background-color: #ccc;
  }
  .avatar.placeholder {
    position: relative;
  }
  .avatar.placeholder::before {
    content: "\f007";
    font-family: 'FontAwesome';
    font-size: 60px;
    color: #aaa;
    position: absolute;
    top: 20px; left: 20px;
  }

  form label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #0d2240;
  }
  form input[type="text"], form input[type="email"], form input[type="file"] {
    width: 100%;
    padding: 10px 14px;
    margin-bottom: 20px;
    border: 2px solid #c8d2e8;
    border-radius: 10px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
  }
  form input[type="text"]:focus, form input[type="email"]:focus, form input[type="file"]:focus {
    border-color: #1666ba;
    outline: none;
    box-shadow: 0 0 6px #1666baaa;
  }
  button.save-btn {
    background: #1666ba;
    color: white;
    padding: 12px 28px;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 5px 15px rgba(22, 102, 186, 0.3);
    transition: background-color 0.3s ease;
  }
  button.save-btn:hover {
    background: #0f4f8a;
  }
  .success-msg {
    color: #1a7a1a;
    font-weight: 600;
    margin-bottom: 20px;
  }
  .error-msg {
    color: #d03939;
    font-weight: 600;
    margin-bottom: 20px;
  }

  .profile-right h3 {
    margin-bottom: 14px;
    font-weight: 700;
    font-size: 1.2rem;
    letter-spacing: 0.5px;
    color: #0d2240;
  }
  .section-box {
    margin-bottom: 30px;
  }
  .lease-agreement, .landlord-contact {
    background: #e3eaff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: inset 0 0 10px #c0c9e8;
  }
  .lease-agreement p, .landlord-contact p {
    margin: 6px 0;
    font-weight: 600;
    color: #2d3a72;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .download-icon {
    cursor: pointer;
    font-size: 1.3rem;
    color: #1666ba;
  }
  .download-icon:hover {
    color: #0f4f8a;
  }
  .logout-btn {
    background: #f44336;
    color: white;
    border: none;
    padding: 14px 0;
    font-weight: 700;
    border-radius: 12px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
    width: 100%;
  }
  .logout-btn:hover {
    background: #b32b21;
  }

  @media (max-width: 800px) {
    .profile-wrapper {
      flex-direction: column;
      padding: 20px;
      margin-top: 0;
    }
    .profile-left, .profile-right {
      flex: 1 1 100%;
      border: none;
      padding: 20px;
    }
    
  }
</style>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>

<?php include '../includes/navbar/tenant-navbar.php'; ?>

<div class="profile-wrapper">

  <section class="profile-left">
    <h2>ACCOUNT</h2>

    <?php if (!empty($user['profile_pic']) && file_exists("../uploads/" . $user['profile_pic'])): ?>
      <div class="avatar" style="background-image: url('../uploads/<?= htmlspecialchars($user['profile_pic']) ?>');"></div>
    <?php else: ?>
      <div class="avatar placeholder" aria-label="User avatar"></div>
    <?php endif; ?>

    <?php if ($successMsg): ?>
      <p class="success-msg"><?= htmlspecialchars($successMsg) ?></p>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <p class="error-msg"><?= htmlspecialchars($errorMsg) ?></p>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
      <label for="profile_pic">Profile Picture</label>
      <input type="file" id="profile_pic" name="profile_pic" accept="image/*">

      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

      <label for="phone">Phone</label>
      <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

      <button type="submit" name="update_profile" class="save-btn">Save Changes</button>
    </form>
  </section>

  <section class="profile-right">
    <div class="section-box lease-agreement">
      <h3>Lease Agreements</h3>
      <?php if ($lease): ?>
        <p>
          <?= htmlspecialchars($lease['title']) ?>
          <span class="download-icon" title="Download Agreement">
            <a href="lease_agreement.php?lease_id=<?= $lease['lease_id'] ?>" style="color: inherit; text-decoration: none;" download>
              <i class="fas fa-download"></i>
            </a>
          </span>
        </p>
        <p><small><?= htmlspecialchars($lease['start_date']) ?> - <?= htmlspecialchars($lease['end_date']) ?></small></p>
      <?php else: ?>
        <p>No active lease found.</p>
      <?php endif; ?>
    </div>

    <div class="section-box landlord-contact">
      <h3>Landlord Contact</h3>
      <?php if ($landlordName): ?>
        <p><strong>Name:</strong> <?= htmlspecialchars($landlordName) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($landlordEmail) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($landlordPhone) ?></p>
      <?php else: ?>
        <p>Landlord contact not available.</p>
      <?php endif; ?>
    </div>

    <form method="POST" action="../logout.php">
      <button type="submit" class="logout-btn">Log Out</button>
    </form>
  </section>

</div>

</
