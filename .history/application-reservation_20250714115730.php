<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$applications = [];

// Get user's applications
$stmt = $conn->prepare("SELECT a.*, p.title, p.address, p.monthly_rent 
                       FROM APPLICATIONS a 
                       JOIN PROPERTY p ON a.property_id = p.property_id 
                       WHERE a.applicant_id = ? 
                       ORDER BY a.submitted_at DESC");
$stmt->bind_param("i", $user_id);
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
    <title>VELA - Application Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background: #f5f7fa; }
        .container { max-width: 800px; margin: 0 auto; }
        .application { background: white; padding: 1.5rem; margin: 1rem 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 0.3rem 0.8rem; border-radius: 20px; font-weight: 600; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.approved { background: #d4edda; color: #155724; }
        .status.rejected { background: #f8d7da; color: #721c24; }
        h1 { color: #1666ba; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>My Applications</h1>
        
        <?php if (empty($applications)): ?>
            <p>No applications submitted yet.</p>
        <?php else: ?>
            <?php foreach ($applications as $app): ?>
                <div class="application">
                    <h3><?php echo htmlspecialchars($app['title']); ?></h3>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($app['address']); ?></p>
                    <p><strong>Rent:</strong> ₱<?php echo number_format($app['monthly_rent'], 2); ?>/month</p>
                    <p><strong>Tenants:</strong> <?php echo $app['num_of_tenants'] + 1; ?> total</p>
                    <p><strong>Status:</strong> <span class="status <?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span></p>
                    <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($app['submitted_at'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>