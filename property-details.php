<?php
session_start();
require_once 'connection.php';

// Check if property ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$property_id = $_GET['id'];
$property = [];
$photos = [];
$error = '';
$success = '';

// Get property details
$stmt = $conn->prepare("SELECT * FROM PROPERTY WHERE property_id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    header("Location: index.php");
    exit();
}

// Get property photos
$stmt = $conn->prepare("SELECT * FROM PROPERTY_PHOTO WHERE property_id = ?");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $photos[] = $row;
}
$stmt->close();

// Handle application submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_application'])) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'general_user') {
        $error = "You need to be logged in as a general user to submit an application.";
    } else {
        $occupation = trim($_POST['occupation']);
        $monthly_income = trim($_POST['monthly_income']);
        $num_of_tenants = trim($_POST['num_of_tenants']);
        $co_tenants = trim($_POST['co_tenants']);
        
        // Basic validation
        if (empty($occupation) || empty($monthly_income) || empty($num_of_tenants)) {
            $error = "Please fill in all required fields.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO APPLICATIONS (property_id, applicant_id, status, submitted_at, occupation, monthly_income, num_of_tenants, co_tenants) 
                                      VALUES (?, ?, 'pending', NOW(), ?, ?, ?, ?)");
                $stmt->bind_param("iisiis", $property_id, $_SESSION['user_id'], $occupation, $monthly_income, $num_of_tenants, $co_tenants);
                
                if ($stmt->execute()) {
                    $success = "Your application has been submitted successfully!";
                } else {
                    $error = "There was an error submitting your application. Please try again.";
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> - VELA Rental</title>
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
            line-height: 1.5;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .property-header {
            margin-bottom: 1.5rem;
        }
        
        .property-title {
            font-size: 1.8rem;
            color: #1666ba;
            margin-bottom: 0.3rem;
        }
        
        .property-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: #368ce7;
            margin-bottom: 0.5rem;
        }
        
        .property-address {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .property-status {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            background-color: #1666ba;
            color: white;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .property-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }
        
        .property-gallery img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .property-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .property-description, 
        .property-features,
        .application-section {
            background: white;
            padding: 1.2rem;
            border-radius: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        
        .section-title {
            font-size: 1.3rem;
            color: #1666ba;
            margin-bottom: 0.8rem;
            padding-bottom: 0.3rem;
            border-bottom: 1px solid #eee;
        }
        
        .features-list {
            list-style: none;
        }
        
        .features-list li {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .features-list i {
            color: #368ce7;
            margin-right: 0.5rem;
            width: 18px;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 600;
            color: #444;
        }
        
        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .form-group textarea {
            min-height: 80px;
        }
        
        .submit-btn {
            background: #1666ba;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        
        .login-prompt {
            background: #f0f7ff;
            padding: 1rem;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .login-prompt a {
            color: #1666ba;
            font-weight: 600;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 0.8rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 0.8rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        @media (min-width: 768px) {
            .property-details {
                grid-template-columns: 2fr 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/navbar/navbarOUT.html" ?>
    
    <div class="container">
        <div class="property-header">
            <h1 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h1>
            <div class="property-price">₱<?php echo number_format($property['monthly_rent'], 2); ?>/month</div>
            <div class="property-address"> <i class="fa-solid fa-location-dot"> </i><?php echo htmlspecialchars($property['address']); ?></div>
            <span class="property-status"><?php echo ucfirst($property['status']); ?></span>
        </div>
        
        <div class="property-gallery">
            <?php if (!empty($photos)): ?>
                <?php foreach ($photos as $photo): ?>
                    <img src="<?php echo htmlspecialchars($photo['file_path']); ?>" alt="Property photo">
                <?php endforeach; ?>
            <?php else: ?>
                <img src="./images/default-property.jpg" alt="Default property image">
            <?php endif; ?>
        </div>
        
        <div class="property-details">
            <div class="property-description">
                <h3 class="section-title">Description</h3>
                <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
            </div>
            
            <div class="property-features">
                <h3 class="section-title">Details</h3>
                <ul class="features-list">
                    <li><i class="fas fa-home"></i> Type: <?php echo ucfirst($property['property_type']); ?></li>
                    <li><i class="fas fa-calendar-alt"></i> Status: <?php echo ucfirst($property['status']); ?></li>
                    <li><i class="fas fa-tag"></i> Rent: ₱<?php echo number_format($property['monthly_rent'], 2); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="application-section">
            <h3 class="section-title">Apply for this Property</h3>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (!isset($_SESSION['loggedin'])): ?>
                <div class="login-prompt">
                    <p>Please <a href="login.php">login</a> or <a href="registration.php">register</a> to apply.</p>
                </div>
            <?php elseif ($_SESSION['role'] != 'general_user'): ?>
                <div class="login-prompt">
                    <p>Only registered accounts can apply for properties.<a href="registration.php" style="color: #1666ba;"> Register here</a> to reserve property </p>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="occupation">Occupation</label>
                        <input type="text" id="occupation" name="occupation" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="monthly_income">Monthly Income (₱)</label>
                        <input type="number" id="monthly_income" name="monthly_income" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="num_of_tenants">Number of Tenants</label>
                        <input type="number" id="num_of_tenants" name="num_of_tenants" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="co_tenants">Co-Tenants (if any)</label>
                        <textarea id="co_tenants" name="co_tenants"></textarea>
                    </div>
                    
                    <button type="submit" name="submit_application" class="submit-btn">Submit Application</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
