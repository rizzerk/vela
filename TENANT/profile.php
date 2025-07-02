<?php
session_start();
require_once "../connection.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'tenant') {
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'] ?? 'Tenant';
$successMsg = "";

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName = trim($_POST['name']);
    $newEmail = trim($_POST['email']);
    $newPhone = trim($_POST['phone']);

    $updateStmt = $conn->prepare("UPDATE USERS SET name = ?, email = ?, phone = ? WHERE user_id = ?");
    $updateStmt->bind_param("sssi", $newName, $newEmail, $newPhone, $userId);
    
    if ($updateStmt->execute()) {
        $successMsg = "Profile updated successfully!";
        $_SESSION['name'] = $newName; // Update session
    } else {
        $successMsg = "Failed to update profile.";
    }
}

// Fetch latest profile info
$query = "SELECT name, email, phone FROM USERS WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch lease info
$leaseQuery = "SELECT l.lease_id, l.start_date, l.end_date, p.title, p.address, u.name AS landlord_name, u.email AS landlord_email, u.phone AS landlord_phone
               FROM LEASE l
               JOIN PROPERTY p ON l.property_id = p.property_id
               JOIN USERS u ON p.landlord_id = u.user_id
               WHERE l.tenant_id = ? AND l.active = 1";
$leaseStmt = $conn->prepare($leaseQuery);
$leaseStmt->bind_param("i", $userId);
$leaseStmt->execute();
$leaseResult = $leaseStmt->get_result();
$lease = $leaseResult->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tenant Profile - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #deecfb 100%);
            margin: 0;
            padding-top: 80px;
        }

        .content-wrapper {
            max-width: 1200px;
            margin: auto;
            padding: 2rem;
        }

        .profile-container {
            display: flex;
            flex-wrap: wrap;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(22, 102, 186, 0.08);
            border: 1px solid #deecfb;
            padding: 2rem;
            gap: 2rem;
        }

        .left-profile {
            flex: 1;
            min-width: 250px;
            border-right: 1px solid #ccc;
            padding-right: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #ccc;
            margin-bottom: 1rem;
        }

        .right-profile {
            flex: 2;
            min-width: 300px;
        }

        h2 {
            color: #1666ba;
            margin-bottom: 1rem;
        }

        label {
            font-weight: 500;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.3rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
        }

        .submit-btn {
            background: #1666ba;
            color: white;
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .submit-btn:hover {
            background: #1458a6;
        }

        .section-title {
            margin-top: 2rem;
            font-size: 1.2rem;
            color: #1666ba;
            font-weight: 600;
        }

        .lease-box {
            background: #f9fafe;
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid #deecfb;
            margin-top: 0.5rem;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            margin-top: 1rem;
            background: #1666ba;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
        }

        .download-btn i {
            margin-left: 0.5rem;
        }

        .logout-btn {
            margin-top: 2rem;
            padding: 0.6rem 1.5rem;
            border: none;
            background: #999;
            color: white;
            font-size: 1rem;
            border-radius: 10px;
            cursor: pointer;
        }

        .success-msg {
            color: green;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
                padding: 1rem;
            }

            .left-profile {
                border-right: none;
                padding-right: 0;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/navbar/tenant-navbar.php'; ?>

<div class="content-wrapper">
    <div class="profile-container">
        <div class="left-profile">
            <div class="profile-avatar"></div>
            <form method="POST">
                <label>
                    Name:
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </label>
                <label>
                    Email:
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </label>
                <label>
                    Phone:
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </label>
                <button type="submit" name="update_profile" class="submit-btn">Save Changes</button>
                <?php if (!empty($successMsg)): ?>
                    <p class="success-msg"><?php echo $successMsg; ?></p>
                <?php endif; ?>
            </form>
        </div>
        <div class="right-profile">
            <h2>Account</h2>
            <div class="section-title">Lease Agreements</div>
            <?php if ($lease): ?>
                <div class="lease-box">
                    <p><strong>Property:</strong> <?php echo htmlspecialchars($lease['title']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($lease['address']); ?></p>
                    <p><strong>Start Date:</strong> <?php echo htmlspecialchars($lease['start_date']); ?></p>
                    <p><strong>End Date:</strong> <?php echo htmlspecialchars($lease['end_date']); ?></p>
                    <a class="download-btn" href="lease-details.php">
                        View Agreement <i class="fas fa-download"></i>
                    </a>
                </div>

                <div class="section-title">Landlord Contact</div>
                <div class="lease-box">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($lease['landlord_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($lease['landlord_email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($lease['landlord_phone']); ?></p>
                </div>
            <?php else: ?>
                <p>No active lease found.</p>
            <?php endif; ?>

            <form method="post" action="../logout.php">
                <button type="submit" class="logout-btn">Log Out</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
