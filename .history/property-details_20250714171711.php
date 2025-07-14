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
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .property-header {
            margin-bottom: 2rem;
        }
        
        .property-title {
            font-size: 2.5rem;
            color: #1666ba;
            margin-bottom: 0.5rem;
        }
        
        .property-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: #368ce7;
            margin-bottom: 1rem;
        }
        
        .property-address {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .property-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background-color: #1666ba;
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .property-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 3rem;
        }
        
        .property-gallery img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .property-gallery img:hover {
            transform: scale(1.02);
        }
        
        .property-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .property-description {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .property-description h3 {
            font-size: 1.5rem;
            color: #1666ba;
            margin-bottom: 1rem;
        }
        
        .property-features {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .property-features h3 {
            font-size: 1.5rem;
            color: #1666ba;
            margin-bottom: 1rem;
        }
        
        .features-list {
            list-style: none;
        }
        
        .features-list li {
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
        }
        
        .features-list i {
            color: #368ce7;
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        
        .application-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 3rem;
        }
        
        .application-section h3 {
            font-size: 1.5rem;
            color: #1666ba;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #444;
        }
        
        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 100px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(22, 102, 186, 0.2);
        }
        
        .login-prompt {
            background: #deecfb;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-prompt a {
            color: #1666ba;
            font-weight: 600;
            text-decoration: none;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #c62828;
        }
        
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #2e7d32;
        }
        
        @media (max-width: 768px) {
            .property-details {
                grid-template-columns: 1fr;
            }
            
            .property-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/navbar/navbarOUT.html" ?>
    
    <div class="container">
        <div class="property-header">
            <h1 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h1>
            <div class="property-price">₱<?php echo number_format($property['monthly_rent'], 2); ?> / month</div>
            <div class="property-address">📍 <?php echo htmlspecialchars($property['address']); ?></div>
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
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
            </div>
            
            <div class="property-features">
                <h3>Property Details</h3>
                <ul class="features-list">
                    <li><i class="fas fa-home"></i> Type: <?php echo ucfirst($property['property_type']); ?></li>
                    <li><i class="fas fa-calendar-alt"></i> Available: <?php echo $property['status'] == 'vacant' ? 'Now' : 'Occupied'; ?></li>
                    <li><i class="fas fa-tag"></i> Monthly Rent: ₱<?php echo number_format($property['monthly_rent'], 2); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="application-section">
            <h3>Apply for this Property</h3>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (!isset($_SESSION['loggedin'])): ?>
                <div class="login-prompt">
                    <p>You need to <a href="login.php">login</a> or <a href="registration.php">register</a> to apply for this property.</p>
                </div>
            <?php elseif ($_SESSION['role'] != 'general_user'): ?>
                <div class="login-prompt">
                    <p>Only general users can apply for properties. Please contact the administrator if you need to change your account type.</p>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="occupation">Occupation</label>
                        <input type="text" id="occupation" name="occupation" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="monthly_income">Monthly Income (PHP)</label>
                        <input type="number" id="monthly_income" name="monthly_income" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="num_of_tenants">Number of Tenants</label>
                        <input type="number" id="num_of_tenants" name="num_of_tenants" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="co_tenants">Co-Tenants Names (if any)</label>
                        <textarea id="co_tenants" name="co_tenants"></textarea>
                    </div>
                    
                    <button type="submit" name="submit_application" class="submit-btn">Submit Application</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>