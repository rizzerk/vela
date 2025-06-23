<?php
session_start();
require_once 'connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    try {
        $stmt = $conn->prepare("SELECT user_id, name, email, password, role FROM USERS WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['loggedin'] = true;
                
                $update_stmt = $conn->prepare("UPDATE USERS SET last_login = NOW() WHERE user_id = ?");
                $update_stmt->bind_param("i", $user['user_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                header("Location: " . ($user['role'] == 'tenant' ? 'tenant_dashboard.php' : 'landlord_dashboard.php'));
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "A system error occurred. Please try again.";
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



        /* Hero Section */
        .hero {
            height: 100vh;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin-top: 4rem;
            padding: 0 5rem;
            gap: 4rem;
            background: url('images/landing-page.png');
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
            color: #1666ba;
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

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
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

        /* Properties Section */
        .properties {
            padding: 8rem 2rem;
            background: #ffffff;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 3.2rem;
            color: #1666ba;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 1rem;
            position: relative;
            text-decoration: none;
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
            font-weight: 400;
            line-height: 1.6;
        }

        .faq .section-title {
            color: #ffffff;
        }

        .faq .section-subtitle {
            color: #ffffff;
            opacity: 0.9;
            margin-bottom: 3rem;
        }



        .property-grid {
            display: flex;
            gap: 2.5rem;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding: 1rem 0;
        }

        .property-grid::-webkit-scrollbar {
            display: none;
        }

        .property-card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            width: 280px;
            height: 420px;
            flex-shrink: 0;
            border: 2px solid #deecfb;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .property-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(22, 102, 186, 0.15);
            border-color: #7ab3ef;
        }

        .property-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
        }

        .property-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40%;
            background: linear-gradient(transparent, rgba(0,0,0,0.1));
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
            line-height: 1.3;
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
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }

        .property-location {
            color: #000000;
            margin-bottom: 0.8rem;
            font-size: 0.85rem;
            font-weight: 500;
            opacity: 0.7;
            display: flex;
            align-items: center;
            gap: 0.4rem;
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
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 4px 15px rgba(54, 140, 231, 0.3);
            margin-top: auto;
        }

        .view-btn:hover {
            background: #1666ba;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(22, 102, 186, 0.4);
        }

        /* Features Section */
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
            background: linear-gradient(135deg, #ffffff 0%, #deecfb 100%);
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
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            background: #7ab3ef;
            color: #ffffff;
        }

        .feature-card h3 {
            color: #1666ba;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .feature-card p {
            color: #000000;
            opacity: 0.8;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Notification Section */
        .notification {
            padding: 12rem 2rem;
            background: linear-gradient(135deg, #1666ba 0%, #368ce7 50%, #7ab3ef 100%);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .notification::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 0%, transparent 35%),
                radial-gradient(circle at 40% 80%, rgba(255,255,255,0.12) 0%, transparent 45%);
        }

        .notification .container {
            position: relative;
            z-index: 3;
        }

        .notification h2 {
            color: #ffffff;
            margin-bottom: 2rem;
            font-size: 3.5rem;
            font-weight: 800;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            letter-spacing: -0.02em;
        }

        .notification p {
            color: #ffffff;
            margin-bottom: 4rem;
            opacity: 0.9;
            font-size: 1.4rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
            font-weight: 400;
        }

        .register-btn {
            background: #ffffff;
            color: #1666ba;
            border: none;
            padding: 1.5rem 4rem;
            border-radius: 60px;
            cursor: pointer;
            font-weight: 800;
            font-size: 1.3rem;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 1;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .register-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.8s;
        }

        .register-btn:hover::before {
            left: 100%;
        }

        .register-btn:hover {
            background: #1666ba;
            color: #ffffff;
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.2);
        }

        /* FAQ Section */
        .faq {
            padding: 2rem 2rem;
            background: linear-gradient(135deg, #1666ba 0%, #368ce7 50%, #7ab3ef 100%);
            position: relative;
            overflow: hidden;
        }

        .faq::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 0%, transparent 35%),
                radial-gradient(circle at 40% 80%, rgba(255,255,255,0.12) 0%, transparent 45%);
        }

        .faq .container {
            position: relative;
            z-index: 3;
        }

        .faq-grid {
            max-width: 900px;
            margin: 0 auto;
        }

        .faq-item {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.4s ease;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .faq-item:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .faq-item:last-child {
            margin-bottom: 0;
        }

        .faq-question {
            padding: 1.5rem 2rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-question h3 {
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.4;
        }

        .faq-question i {
            color: #bedaf7;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: all 0.4s ease;
        }

        .faq-item.active .faq-answer {
            max-height: 200px;
        }

        .faq-answer p {
            padding: 0 2rem 1.5rem 2rem;
            color: #ffffff;
            line-height: 1.6;
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }



        /* Responsive */
        @media (max-width: 768px) {
            
            .hero {
                flex-direction: column;
                padding: 2rem;
                margin-top: 7rem;
                text-align: center;
            }
            
            .hero-content {
                text-align: center;
                margin-bottom: 2rem;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .login-form {
                width: 100%;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-content p {
                font-size: 1.2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .notification h2 {
                font-size: 2.5rem;
            }
            
            .notification p {
                font-size: 1.1rem;
            }
            
            .register-btn {
                padding: 1.2rem 2.5rem;
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div id="navbar-container"></div>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Find Your Next Home with Ease</h1>
            <p>Seamless rental experience, from browsing to moving in</p>
        </div>
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
            <div style="text-align: center; margin-top: 1rem; color: #ffffff;">
                Don't have an account? <a href="registration.php" style="color: #7ab3ef; text-decoration: none;">Register here</a>
            </div>
        </div>
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

    <!-- Notification Section -->
    <section class="notification">
        <div class="container">
            <h2>Never Miss a Property</h2>
            <p>Get notified instantly when properties matching your criteria become available</p>
            <button class="register-btn" onclick="window.location.href='registration.php'">Register for Vacancy Notifications</button>
        </div>
    </section>

    <!-- Properties Section -->
    <section class="properties" id="properties">
        <div class="container">
            <h2 class="section-title">Limited Listings. High Demand. Act Fast.</h2>
            <p class="section-subtitle">Carefully curated rental properties that combine comfort, convenience, and value in prime locations across the city</p>
            <div class="property-grid">
                <div class="property-card">
                    <div class="property-image" style="background-image: url('images/1.jpg')"></div>
                    <div class="property-info">
                        <h3 class="property-title">Luxury Downtown Penthouse</h3>
                        <div class="property-price">₱15,000/month</div>
                        <div class="property-features">
                            <span class="feature"><i class="fas fa-bed"></i> 3 Bed</span>
                            <span class="feature"><i class="fas fa-bath"></i> 2 Bath</span>
                            <span class="feature"><i class="fas fa-building"></i> Balcony</span>
                            <span class="feature"><i class="fas fa-car"></i> Parking</span>
                        </div>
                        <div class="property-location">📍 Makati Business District</div>
                        <button class="view-btn">View Details</button>
                    </div>
                </div>

                <div class="property-card">
                    <div class="property-image" style="background-image: url('images/2.jpg')"></div>
                    <div class="property-info">
                        <h3 class="property-title">Modern City Apartment</h3>
                        <div class="property-price">₱12,000/month</div>
                        <div class="property-features">
                            <span class="feature"><i class="fas fa-bed"></i> 2 Bed</span>
                            <span class="feature"><i class="fas fa-bath"></i> 2 Bath</span>
                            <span class="feature"><i class="fas fa-dumbbell"></i> Gym</span>
                            <span class="feature"><i class="fas fa-swimming-pool"></i> Pool</span>
                        </div>
                        <div class="property-location">📍 BGC Taguig</div>
                        <button class="view-btn">View Details</button>
                    </div>
                </div>

                <div class="property-card">
                    <div class="property-image" style="background-image: url('images/3.jpg')"></div>
                    <div class="property-info">
                        <h3 class="property-title">Cozy Studio Unit</h3>
                        <div class="property-price">₱4,500/month</div>
                        <div class="property-features">
                            <span class="feature"><i class="fas fa-home"></i> Studio</span>
                            <span class="feature"><i class="fas fa-bath"></i> 1 Bath</span>
                            <span class="feature"><i class="fas fa-wifi"></i> WiFi</span>
                        </div>
                        <div class="property-location">📍 Quezon City</div>
                        <button class="view-btn">View Details</button>
                    </div>
                </div>

                <div class="property-card">
                    <div class="property-image" style="background-image: url('images/4.jpg')"></div>
                    <div class="property-info">
                        <h3 class="property-title">Budget-Friendly Flat</h3>
                        <div class="property-price">₱3,500/month</div>
                        <div class="property-features">
                            <span class="feature"><i class="fas fa-bed"></i> 1 Bed</span>
                            <span class="feature"><i class="fas fa-bath"></i> 1 Bath</span>
                            <span class="feature"><i class="fas fa-utensils"></i> Kitchen</span>
                        </div>
                        <div class="property-location">📍 Manila</div>
                        <button class="view-btn">View Details</button>
                    </div>
                </div>

                <div class="property-card">
                    <div class="property-image" style="background-image: url('images/5.jpg')"></div>
                    <div class="property-info">
                        <h3 class="property-title">Family Townhouse</h3>
                        <div class="property-price">₱8,000/month</div>
                        <div class="property-features">
                            <span class="feature"><i class="fas fa-bed"></i> 3 Bed</span>
                            <span class="feature"><i class="fas fa-bath"></i> 2 Bath</span>
                            <span class="feature"><i class="fas fa-seedling"></i> Garden</span>
                            <span class="feature"><i class="fas fa-warehouse"></i> Garage</span>
                        </div>
                        <div class="property-location">📍 Pasig City</div>
                        <button class="view-btn">View Details</button>
                    </div>
                </div>

                <div class="property-card">
                    <div class="property-image" style="background-image: url('images/1.jpg')"></div>
                    <div class="property-info">
                        <h3 class="property-title">Affordable Condo Unit</h3>
                        <div class="property-price">₱5,000/month</div>
                        <div class="property-features">
                            <span class="feature"><i class="fas fa-bed"></i> 2 Bed</span>
                            <span class="feature"><i class="fas fa-bath"></i> 2 Bath</span>
                            <span class="feature"><i class="fas fa-building"></i> Balcony</span>
                        </div>
                        <div class="property-location">📍 Downtown District</div>
                        <button class="view-btn">View Details</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq" id="faq">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Get answers to common questions about our rental process, requirements, and services</p>
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>How do I apply for a rental property?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Simply browse our available properties, click "View Details" on your preferred unit, and submit your application online. You'll need to provide basic information, proof of income, and valid identification.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>What documents do I need to rent?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Required documents include: valid government ID, proof of income (payslips or employment certificate), bank statements, and references from previous landlords if applicable.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>How much is the security deposit?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Security deposits typically range from 1-2 months' rent, depending on the property. This amount is refundable upon move-out, subject to property condition assessment.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Can I schedule a property viewing?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! Contact us through the property listing or call our office to schedule a viewing. We offer flexible viewing hours including weekends to accommodate your schedule.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>Are utilities included in the rent?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>This varies by property. Some units include water and basic utilities, while others require separate utility arrangements. Check the property details or contact us for specific information.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <h3>How do I submit maintenance requests?</h3>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Once you're a tenant, you can submit maintenance requests through our online portal, mobile app, or by calling our maintenance hotline. Emergency repairs are handled 24/7.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.querySelector('input[name="email"]').value;
            const password = document.querySelector('input[name="password"]').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please enter both email and password');
            }
        });

        fetch('includes/navbar/navbarOUT.html')
            .then(response => response.text())
            .then(data => {
                document.getElementById('navbar-container').innerHTML = data;
                
                setTimeout(() => {
                    const navLinksArray = document.querySelectorAll('.nav-link');
                    const sections = document.querySelectorAll('section');

                    function updateActiveNav() {
                        let current = '';
                        sections.forEach(section => {
                            const sectionTop = section.offsetTop;
                            if (window.scrollY >= (sectionTop - 200)) {
                                current = section.getAttribute('id');
                            }
                        });

                        navLinksArray.forEach(link => {
                            link.classList.remove('active');
                            if (link.getAttribute('href') === '#' + current) {
                                link.classList.add('active');
                            }
                        });
                    }

                    window.addEventListener('scroll', updateActiveNav);
                    updateActiveNav();
                }, 100);
            });

        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', function() {
                const faqItem = this.parentElement;
                const isActive = faqItem.classList.contains('active');
                
                document.querySelectorAll('.faq-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                if (!isActive) {
                    faqItem.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
