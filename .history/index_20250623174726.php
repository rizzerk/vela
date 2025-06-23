<?php
session_start();
require_once 'connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (!$conn) {
        $error = "Database connection failed";
    } else {
        try {
            $stmt = $conn->prepare("SELECT user_id, name, email, password, role FROM USERS WHERE email = ?");
            if (!$stmt) {
                $error = "Database prepare failed: " . $conn->error;
            } else {
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
                        
                        try {
                            $update_stmt = $conn->prepare("UPDATE USERS SET last_login = NOW() WHERE user_id = ?");
                            $update_stmt->bind_param("i", $user['user_id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                        } catch (Exception $update_e) {
                        }
                        
                        header("Location: " . ($user['role'] == 'tenant' ? 'TENANT/dashboard.php' : 'LANDLORD/dashboard.php'));
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
            margin-top: 4rem;
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
        }

        .faqs {
            padding: 8rem 2rem;
            background: #f8fafc;
        }

        .faq-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: #ffffff;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #deecfb;
            overflow: hidden;
        }

        .faq-question {
            padding: 2rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: #f8fafc;
        }

        .faq-question h3 {
            color: #1666ba;
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }

        .faq-icon {
            color: #368ce7;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .faq-answer {
            padding: 0 2rem 2rem 2rem;
            color: #000000;
            line-height: 1.6;
            display: none;
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
    <?php include "includes/navbar/navbarOUT.html" ?>
    
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

    <!-- Properties Section -->
    <section class="properties" id="properties">
        <div class="container">
            <h2 class="section-title">Limited Listings. High Demand. Act Fast.</h2>
            <p class="section-subtitle">Carefully curated rental properties that combine comfort, convenience, and value in prime locations across the city</p>
            <div class="property-grid">
                <div class="property-card">
                    <div class="property-image" style="background-image: url('./images/1.jpg')"></div>
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
                    <div class="property-image" style="background-image: url('./images/2.jpg')"></div>
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
                    <div class="property-image" style="background-image: url('./images/3.jpg')"></div>
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
                    <div class="property-image" style="background-image: url('./images/4.jpg')"></div>
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
                    <div class="property-image" style="background-image: url('./images/5.jpg')"></div>
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
                    <div class="property-image" style="background-image: url('./images/1.jpg')"></div>
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

    <!-- FAQs Section -->
    <section class="faqs" id="faqs">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Find answers to common questions about our rental management platform</p>
            <div class="faq-container">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h3>How do I register as a tenant or landlord?</h3>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Click on the "Register here" link on the login form or visit our registration page. You can choose to register as either a tenant looking for properties or a landlord wanting to list your properties.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h3>How do I submit a maintenance request?</h3>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Once logged in as a tenant, navigate to your dashboard and click on "Maintenance Requests". Fill out the form with details about the issue, and your landlord will be notified immediately.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h3>How do I make rent payments?</h3>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>You can upload proof of payment through your tenant dashboard. The system will notify your landlord and track your payment history for easy reference.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h3>Can I view my lease details online?</h3>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes! Your dashboard provides complete access to your lease information, including start and end dates, rental amount, and renewal options.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h3>How do landlords manage multiple properties?</h3>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>The landlord dashboard allows you to add, edit, and manage multiple properties from one central location. You can track tenants, payments, and maintenance requests for all your properties.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <h3>Is my personal information secure?</h3>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Absolutely. We use industry-standard encryption and security measures to protect all user data. Your personal and financial information is kept completely confidential.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Notifications Section -->
    <section class="notifications" id="notifications">
        <div class="container">
            <div class="notification-card">
                <h3><i class="fas fa-bell"></i> Stay Updated</h3>
                <p>Get instant notifications about rent reminders, maintenance updates, and important announcements. Never miss a payment deadline or property update again.</p>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <h2 class="section-title">Get In Touch</h2>
            <p class="section-subtitle">Have questions? We're here to help you find your perfect home</p>
            <div class="contact-info">
                <h3>Contact Information</h3>
                <div class="contact-details">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>+63 912 345 6789</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>info@vela.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Manila, Philippines</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function toggleFaq(element) {
            const faqItem = element.parentElement;
            const isActive = faqItem.classList.contains('active');
            
            // Close all FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Open clicked item if it wasn't active
            if (!isActive) {
                faqItem.classList.add('active');
            }
        }
    </script>
</body>
</html>