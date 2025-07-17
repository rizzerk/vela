<?php
session_start();
require_once 'connection.php';

// if (!isset($_SESSION['loggedin'])) {
//     header("Location: not-login.php");
//     exit();
// }

$applications = [];

// Get user's applications
$stmt = $conn->prepare("SELECT a.*, p.title, p.address, p.monthly_rent 
                       FROM APPLICATIONS a 
                       JOIN PROPERTY p ON a.property_id = p.property_id 
                       WHERE a.applicant_id = ? 
                       ORDER BY a.submitted_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - VELA Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            background-color: #f8fafc;
            color: #333;
            line-height: 1.6;
            padding-top: 80px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        h1 {
            font-size: 2.5rem;
            color: #1666ba;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .no-applications {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .no-applications p {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            color: #666;
        }
        
        .no-applications a {
            display: inline-block;
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .no-applications a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(22, 102, 186, 0.2);
        }
        
        .applications-list {
            display: grid;
            gap: 1.5rem;
        }
        
        .application-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .application-property {
            margin-bottom: 1rem;
        }
        
        .application-property h3 {
            font-size: 1.3rem;
            color: #1666ba;
            margin-bottom: 0.5rem;
        }
        
        .application-property p {
            color: #666;
        }
        
        .application-details h4 {
            font-size: 1.1rem;
            margin-bottom: 0.8rem;
            color: #444;
        }
        
        .application-details p {
            margin-bottom: 0.5rem;
            color: #666;
        }
        
        .application-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #e65100;
        }
        
        .status-approved {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-rejected {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .application-meta {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .application-card {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/navbar/navbarOUT.html" ?>
    
    <div class="container">
        <h1>My Applications</h1>
        
        <?php if (empty($applications)): ?>
            <div class="no-applications">
                <p>You haven't submitted any applications yet.</p>
                <a href="index.php">Browse Properties</a>
            </div>
        <?php else: ?>
            <div class="applications-list">
                <?php foreach ($applications as $application): ?>
                    <div class="application-card">
                        <div class="application-property">
                            <h3><?php echo htmlspecialchars($application['title']); ?></h3>
                            <p><?php echo htmlspecialchars($application['address']); ?></p>
                            <p>₱<?php echo number_format($application['monthly_rent'], 2); ?> / month</p>
                        </div>
                        
                        <div class="application-details">
                            <h4>Application Details</h4>
                            <p><strong>Occupation:</strong> <?php echo htmlspecialchars($application['occupation']); ?></p>
                            <p><strong>Monthly Income:</strong> ₱<?php echo number_format($application['monthly_income'], 2); ?></p>
                            <p><strong>Number of Tenants:</strong> <?php echo htmlspecialchars($application['num_of_tenants']); ?></p>
                            <?php if (!empty($application['co_tenants'])): ?>
                                <p><strong>Co-Tenants:</strong> <?php echo htmlspecialchars($application['co_tenants']); ?></p>
                            <?php endif; ?>
                            
                            <span class="application-status status-<?php echo htmlspecialchars($application['status']); ?>">
                                <?php echo ucfirst($application['status']); ?>
                            </span>
                        </div>
                        
                        <div class="application-meta">
                            <span>Submitted: <?php echo date('M d, Y', strtotime($application['submitted_at'])); ?></span>
                            <?php if ($application['status'] == 'approved' && $application['approved_at']): ?>
                                <span>Approved: <?php echo date('M d, Y', strtotime($application['approved_at'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<script>
// Override navbar script to allow regular links
document.addEventListener('DOMContentLoaded', function() {
    // Remove existing event listeners and add new ones
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
</script>
</body>
</html>