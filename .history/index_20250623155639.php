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
            $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, role FROM USERS WHERE email = ?");
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
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
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
            color: #000000;
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
</body>
</html>