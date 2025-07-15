<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$leaseQuery = "SELECT l.lease_id, l.start_date, l.end_date, l.active, 
                      p.title AS property_name, p.address, p.description, p.monthly_rent,
                      u.name AS landlord_name, u.email AS landlord_email, u.phone AS landlord_phone,
                      a.co_tenants
               FROM LEASE l 
               JOIN PROPERTY p ON l.property_id = p.property_id 
               JOIN USERS u ON u.role = 'landlord'
               LEFT JOIN APPLICATIONS a ON a.property_id = p.property_id AND a.applicant_id = l.tenant_id
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
    <title>Lease Details</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f5f5;
            padding: 100px 20px 20px 20px;
        }

        .back-arrow {
            background: #1666ba;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 18px;
            margin: 0 auto 20px 0;
            max-width: 800px;
        }

        .back-arrow:hover {
            background: #0d4a8a;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #1666ba;
            margin-bottom: 30px;
            text-align: center;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
            width: 150px;
            color: #666;
        }

        .value {
            color: #333;
        }

        .amount {
            color: #1666ba;
            font-weight: bold;
            font-size: 18px;
        }

        .status {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }



        .no-lease {
            text-align: center;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar/tenant-navbar.php'; ?>

    <a href="dashboard.php" class="back-arrow">←</a>
    
    <div class="container">
        <h1>Lease Details</h1>
        
        <?php if ($lease): ?>
            <div class="section">
                <h2>Property Information</h2>
                <div class="info-row">
                    <span class="label">Property Name:</span>
                    <span class="value"><?php echo htmlspecialchars($lease['property_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Address:</span>
                    <span class="value"><?php echo htmlspecialchars($lease['address']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Description:</span>
                    <span class="value"><?php echo htmlspecialchars($lease['description'] ?: 'No description'); ?></span>
                </div>
            </div>

            <div class="section">
                <h2>Lease Information</h2>
                <div class="info-row">
                    <span class="label">Lease ID:</span>
                    <span class="value">#<?php echo $lease['lease_id']; ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Start Date:</span>
                    <span class="value"><?php echo date('M d, Y', strtotime($lease['start_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">End Date:</span>
                    <span class="value"><?php echo date('M d, Y', strtotime($lease['end_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Status:</span>
                    <span class="status">Active</span>
                </div>
                <div class="info-row">
                    <span class="label">Co-Tenants:</span>
                    <span class="value"><?php echo htmlspecialchars($lease['co_tenants'] ?: 'None'); ?></span>
                </div>
            </div>

            <div class="section">
                <h2>Financial Details</h2>
                <div class="info-row">
                    <span class="label">Monthly Rent:</span>
                    <span class="value amount"><?php echo formatCurrency($lease['monthly_rent'] ?? 0); ?></span>
                </div>
            </div>

            <div class="section">
                <h2>Landlord Information</h2>
                <div class="info-row">
                    <span class="label">Name:</span>
                    <span class="value"><?php echo htmlspecialchars($lease['landlord_name'] ?? 'Not available'); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($lease['landlord_email'] ?? 'Not available'); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Phone:</span>
                    <span class="value"><?php echo htmlspecialchars($lease['landlord_phone'] ?? 'Not provided'); ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="no-lease">
                <p>You don't have an active lease at this time.</p>
            </div>
        <?php endif; ?>


    </div>
</body>
</html>