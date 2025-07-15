<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get tenant's lease details
$leaseQuery = "SELECT l.lease_id, l.start_date, l.end_date, l.active, 
                      p.title AS property_name, p.address, p.description, p.monthly_rent,
                      u.name AS landlord_name, u.email AS landlord_email, u.phone AS landlord_phone
               FROM LEASE l 
               JOIN PROPERTY p ON l.property_id = p.property_id 
               JOIN USERS u ON u.role = 'landlord'
               WHERE l.tenant_id = ? AND l.active = 1
               LIMIT 1";
$stmt = $conn->prepare($leaseQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$leaseResult = $stmt->get_result();
$lease = $leaseResult->fetch_assoc();

function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LEASE DETAILS - Tenant Portal</title>
    <script src="https://kit.fontawesome.com/dddee79f2e.js" crossorigin="anonymous"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }



        .main-container {
            display: flex;
            justify-content: center;
            padding: 30px 10% 80px;
            background-color: white;
        }

        .lease-container {
            max-width: 1200px;
            width: 100%;
        }

        .lease-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .card-header i {
            background: #1666ba;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .card-title {
            color: #1666ba;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: #1e293b;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .info-value.amount {
            color: #1666ba;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .navigation-arrows {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: flex-start;
            padding: 0 20px;
            z-index: 100;
        }

        .nav-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-text {
            color: #1666ba;
            font-weight: 500;
            text-decoration: none;
        }

        .arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: linear-gradient(to right, #1666ba, #0d4a8a);
            border-radius: 50%;
            color: white;
            font-size: 22px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .arrow:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 1.8rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .lease-card {
                padding: 1.5rem;
            }

            .navigation-arrows {
                bottom: 10px;
                padding: 0 10px;
            }

            .arrow {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .nav-text {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar/tenant-navbar.php'; ?>

    <div class="main-container">
        <div class="lease-container">
            <?php if ($lease): ?>
                <!-- Property Information -->
                <div class="lease-card">
                    <div class="card-header">
                        <i class="fas fa-building"></i>
                        <h2 class="card-title">Property Information</h2>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Property Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($lease['property_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Address</span>
                            <span class="info-value"><?php echo htmlspecialchars($lease['address']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Description</span>
                            <span class="info-value"><?php echo htmlspecialchars($lease['description'] ?: 'No description available'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Lease Information -->
                <div class="lease-card">
                    <div class="card-header">
                        <i class="fas fa-calendar-alt"></i>
                        <h2 class="card-title">Lease Information</h2>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Lease ID</span>
                            <span class="info-value">#<?php echo $lease['lease_id']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Start Date</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($lease['start_date'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">End Date</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($lease['end_date'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="status-badge status-active">Active</span>
                        </div>
                    </div>
                </div>

                <!-- Financial Information -->
                <div class="lease-card">
                    <div class="card-header">
                        <i class="fas fa-dollar-sign"></i>
                        <h2 class="card-title">Financial Details</h2>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Monthly Rent</span>
                            <span class="info-value amount"><?php echo formatCurrency($lease['monthly_rent'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Landlord Information -->
                <div class="lease-card">
                    <div class="card-header">
                        <i class="fas fa-user-tie"></i>
                        <h2 class="card-title">Landlord Information</h2>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($lease['landlord_name'] ?? 'Not available'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($lease['landlord_email'] ?? 'Not available'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <span class="info-value"><?php echo htmlspecialchars($lease['landlord_phone'] ?? 'Not provided'); ?></span>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="lease-card">
                    <div class="card-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h2 class="card-title">No Active Lease</h2>
                    </div>
                    <p>You don't have an active lease at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="navigation-arrows">
        <div class="nav-group" onclick="window.location.href='dashboard.php'">
            <a href="dashboard.php" class="arrow"><i class="fa-solid fa-arrow-left"></i></a>
            <p class="nav-text">Back to Dashboard</p>
        </div>
    </div>
</body>
</html>