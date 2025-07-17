<?php
session_start();
require_once 'connection.php';

$error = '';

$properties = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT p.property_id, p.title, p.address, p.monthly_rent, p.description, pp.file_path 
    FROM PROPERTY p 
    LEFT JOIN PROPERTY_PHOTO pp ON p.property_id = pp.property_id 
    WHERE p.status = 'vacant' AND p.published = TRUE 
    GROUP BY p.property_id 
    ORDER BY p.property_id DESC");
        if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $properties[] = $row;
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (!$conn) {
        $error = "Database connection failed";
    } else {
        try {
            // Updated query to include is_active column
            $stmt = $conn->prepare("SELECT user_id, name, email, password, role, is_active FROM USERS WHERE email = ?");
            if (!$stmt) {
                $error = "Database prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 1) {
                    $user = $result->fetch_assoc();
                    
                    // Check if user is active before password verification
                    if ($user['is_active'] != '1') {
                        $error = "Your account has been deactivated. Please contact the administrator.";
                    } elseif (password_verify($password, $user['password'])) {
                        // User is active and password is correct - proceed with login
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['loggedin'] = true;
                        
                        try {
                            $update_stmt = $conn->prepare("UPDATE USERS SET last_login = NOW() WHERE user_id = ?");
                            $update_stmt->bind_param("i", $user['user_id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                        } catch (Exception $update_e) {
                            // Silent fail for last_login update
                        }
                        
                        // Redirect based on user role
                        if ($user['role'] == 'tenant') {
                            header("Location: TENANT/dashboard.php");
                            exit;
                        } elseif ($user['role'] == 'landlord') {
                            header("Location: LANDLORD/dashboard.php");
                            exit;
                        } elseif ($user['role'] == 'general_user') {
                            header("Location: index.php");
                        } else {
                            echo "Invalid user role.";
                        }
                        exit();
                    } else {
                        $error = "Invalid email or password";
                    }
                } else {
                    $error = "Invalid email or password";
                }
                
                $stmt->close();
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VELA - Rental Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.7;
            color: #000000;
            background-color: #ffffff;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-size: 16px;
        }

        body::-webkit-scrollbar {
            width: 12px;
        }

        body::-webkit-scrollbar-track {
            background: #deecfb;
            border-radius: 10px;
        }

        body::-webkit-scrollbar-thumb {
            background: #368ce7;
            border-radius: 10px;
        }

        body::-webkit-scrollbar-thumb:hover {
            background: #1666ba;
        }

        .hero {
            height: 100vh;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            padding: 0 5rem;
            gap: 4rem;
            background: url('./images/landing-page.png');
            background-size: cover;
            background-position: center;
        }

        .hero-content {
            flex: 1;
            text-align: right;
        }

        .hero-content h1 {
            font-size: 5rem;
            margin-bottom: 2rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.05;
            color: #ffffff;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .hero-content p {
            font-size: 1.6rem;
            opacity: 0.95;
            font-weight: 400;
            line-height: 1.5;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .login-form {
            width: 380px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 3rem;
            border: 2px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 32px 80px rgba(0,0,0,0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .login-form h3 {
            color: #ffffff;
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: 600;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 1.2rem 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            font-size: 1.1rem;
            outline: none;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            color: white;
            font-weight: 500;
        }

        .form-group input::placeholder {
            color: #7ab3ef;
        }

        .form-group input:focus {
            background: #ffffff;
            border-color: #368ce7;
            box-shadow: 0 8px 25px rgba(54, 140, 231, 0.2);
            color: #000000;
        }

        .form-group input:focus::placeholder {
            color: #999999;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
            border: none;
            padding: 1.3rem;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(54, 140, 231, 0.4);
            position: relative;
            overflow: hidden;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(54, 140, 231, 0.6);
        }

        .login-error {
            color: #ff4444;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
            background: rgba(255, 68, 68, 0.1);
            padding: 0.5rem;
            border-radius: 8px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .user-welcome {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 2rem;
            border: 2px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 32px 80px rgba(0,0,0,0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            text-align: center;
            color: white;
            width: 380px;
        }
        
        .user-welcome h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        
        .user-welcome p {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ff4444, #cc0000);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(255, 68, 68, 0.4);
            width: 100%;
        }
        
        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 68, 68, 0.6);
        }
        
        .user-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .user-action-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.8rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .user-action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }


        .section-title {
            text-align: center;
            font-size: 3.2rem;
            color: #1666ba;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.25rem;
            color: #000000;
            opacity: 0.7;
            margin-bottom: 5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .features {
            padding: 8rem 2rem;
            background: #ffffff;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }

        .feature-card {
            background: #ffffff;
            padding: 2rem 1.5rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid #deecfb;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #1666ba;
            background: #bedaf7;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
        }

        .feature-card h3 {
            color: #1666ba;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .properties {
            padding: 8rem 2rem;
            background: #ffffff;
        }

        .property-grid {
            display: flex;
            gap: 2.5rem;
            overflow-x: auto;
            padding: 1rem 0;
        }

        .property-card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.08);
            width: 280px;
            height: 420px;
            flex-shrink: 0;
            border: 2px solid #deecfb;
            display: flex;
            flex-direction: column;
        }

        .property-image {
            height: 180px;
            background-size: cover;
            background-position: center;
        }

        .property-info {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .property-title {
            font-size: 1.1rem;
            color: #1666ba;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .property-price {
            font-size: 1.5rem;
            color: #1666ba;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .property-features {
            display: flex;
            gap: 0.4rem;
            margin-bottom: 0.8rem;
            flex-wrap: wrap;
        }

        .feature {
            background: #deecfb;
            padding: 0.3rem 0.6rem;
            border-radius: 16px;
            font-size: 0.7rem;
            color: #1666ba;
            font-weight: 600;
        }

        .property-location {
            color: #000000;
            margin-bottom: 0.8rem;
            font-size: 0.85rem;
            opacity: 0.7;
        }

        .view-btn {
            background: #368ce7;
            color: #ffffff;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            width: 100%;
            margin-top: auto;
        }

        .view-btn:hover {
            background: #1666ba;
        }

        .contact {
            padding: 8rem 2rem;
            background: #f8fafc;
        }

        .contact-info {
            text-align: center;
            background: #ffffff;
            padding: 3rem 2rem;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #deecfb;
            max-width: 600px;
            margin: 0 auto;
        }

        .contact-info h3 {
            color: #1666ba;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .contact-details {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #000000;
            font-size: 1.1rem;
        }

        .contact-item i {
            color: #1666ba;
            font-size: 1.2rem;
        }

        .notifications {
            padding: 8rem 2rem;
            background: linear-gradient(135deg, #1666ba 0%, #368ce7 100%);
            color: #ffffff;
        }

        .notification-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-width: 800px;
            margin: 0 auto;
        }

        .notification-card h3 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .notification-card p {
            font-size: 1.2rem;
            line-height: 1.6;
            opacity: 0.95;
            margin-bottom: 2rem;
        }

        .notify-btn {
            background: #ffffff;
            color: #1666ba;
            border: none;
            padding: 1rem 2rem;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
        }

        .notify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.4);
        }

        .faqs {
            padding: 8rem 2rem;
            background: linear-gradient(135deg, #1666ba 0%, #368ce7 100%);
            color: #ffffff;
        }

        .faq-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .faq-question {
            padding: 2rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: transparent;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .faq-question h3 {
            color: #ffffff;
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }

        .faq-icon {
            color: #ffffff;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .faq-answer {
            padding: 0 2rem 2rem 2rem;
            color: #ffffff;
            line-height: 1.6;
            display: none;
            opacity: 0.9;
        }

        .faq-item.active .faq-answer {
            display: block;
        }

        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }

        @media (max-width: 768px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
            .contact-details {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <?php
        include "includes/navbar/navbarOUT.html";
    ?>
    
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Find Your Next Home with Ease</h1>
            <p>Seamless rental experience, from browsing to moving in</p>
        </div>
        
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['role'] == 'general_user'): ?>
            <div class="user-welcome">
                <h3>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h3>
                <p>You're logged in and ready to find your perfect home.</p>
                
                <div class="user-actions">
                    <a href="my-applications.php" class="user-action-btn">
                        <i class="fas fa-clipboard-list"></i> View My Applications
                    </a>
                    <a href="index.php#properties" class="user-action-btn">
                        <i class="fas fa-search"></i> Browse Properties
                    </a>
                </div>
                
                <form method="POST" action="">
                    <button type="submit" name="logout" class="logout-btn">Logout</button>
                </form>
            </div>

            <?php else: ?>
        <div class="login-form" id="login">
            <h3>Login</h3>
            <?php if ($error): ?>
                <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="index.php">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" name="login" class="login-btn">Login</button>
            </form>
            <div style="text-align: center; margin-top: 0.5rem;">
    <a href="forgot-password.php" style="color: #7ab3ef; text-decoration: none;">Forgot password?</a>
</div>
            <div style="text-align: center; margin-top: 1rem; color: #ffffff;">
                Don't have an account? <a href="registration.php" style="color: #7ab3ef; text-decoration: none;">Register here</a>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">Why Choose VELA</h2>
            <p class="section-subtitle">Experience the difference with our comprehensive rental management platform designed for modern living</p>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-home feature-icon"></i>
                    <h3>Property Availability</h3>
                    <p>Real-time property listings with instant availability updates</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-clipboard-list feature-icon"></i>
                    <h3>Lease Tracking</h3>
                    <p>Comprehensive lease management and renewal tracking</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-credit-card feature-icon"></i>
                    <h3>Payment Management</h3>
                    <p>Easily send proof of payment and receive timely rent reminders — no missed due dates</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-tools feature-icon"></i>
                    <h3>Maintenance Requests</h3>
                    <p>Easy submission and tracking of maintenance issues</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Notifications Section -->
    <section class="notifications" id="notifications">
        <div class="container">
            <div class="notification-card">
                <h3><i class="fas fa-bell"></i> Stay Updated on New Vacancies</h3>
                <p>Be the first to know when new properties become available. Get instant notifications about vacancy openings that match your preferences and budget.</p>
                <button class="notify-btn" onclick="window.location.href='registration.php'">Register for Vacancy Notifications</button>
            </div>
        </div>
    </section>

    <!-- Properties Section -->
    <section class="properties" id="properties">
        <div class="container">
            <h2 class="section-title">Limited Listings. High Demand. Act Fast.</h2>
            <p class="section-subtitle">Carefully curated rental properties that combine comfort, convenience, and value in prime locations across the city</p>
            <?php if (empty($properties)): ?>
                <div style="text-align: center; padding: 4rem 2rem; background: #deecfb; border-radius: 16px; margin: 2rem 0;">
                    <i class="fas fa-home" style="font-size: 4rem; color: #368ce7; margin-bottom: 1.5rem;"></i>
                    <h3 style="color: #1666ba; font-size: 1.8rem; margin-bottom: 1rem;">No Properties Available</h3>
                    <p style="color: #000000; opacity: 0.7; font-size: 1.1rem;">Currently, there are no vacant properties. Check back soon for new listings!</p>
                </div>
            <?php else: ?>
                <div class="property-grid">
                    <?php foreach ($properties as $property): ?>
                        <div class="property-card">
                            <div class="property-image" style="background-image: url('<?php echo $property['file_path'] ? htmlspecialchars($property['file_path']) : './images/default-property.jpg'; ?>')"></div>
                            <div class="property-info">
                                <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                                <div class="property-price">₱<?php echo number_format($property['monthly_rent'], 0); ?>/month</div>
                                <div class="property-location">📍 <?php echo htmlspecialchars($property['address']); ?></div>
                                <p style="font-size: 0.9rem; color: #000000; opacity: 0.8; margin: 1rem 0;"><?php echo htmlspecialchars(substr($property['description'], 0, 100)) . (strlen($property['description']) > 100 ? '...' : ''); ?></p>
                                <button class="view-btn" onclick="window.location.href='property-details.php?id=<?php echo $property['property_id']; ?>'">View Details</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <h2 class="section-title">Contact Information</h2>
        <p class="section-subtitle">Get in touch with our property management team</p>
        <h3 style="text-align: center; color: #1666ba; margin: 2rem 0;">Maria Rose Cinco - Property Manager</h3>
        <p style="text-align: center; margin: 1rem 0;"><i class="fas fa-phone" style="color: #1666ba; margin-right: 0.5rem;"></i>+63 912 345 6789</p>
        <p style="text-align: center; margin: 1rem 0;"><i class="fas fa-envelope" style="color: #1666ba; margin-right: 0.5rem;"></i>maria.cinco@vela.com</p>
        <p style="text-align: center; margin: 1rem 0;"><i class="fas fa-map-marker-alt" style="color: #1666ba; margin-right: 0.5rem;"></i>Manila, Philippines</p>
    </section>

</html>