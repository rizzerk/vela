<?php
require_once 'connection.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM USERS WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            // Generate token (64 characters)
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", time() + 3600); // 1 hour expiration
            
            // Store token in database
            $update = $conn->prepare("UPDATE USERS SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
            $update->bind_param("sss", $token, $expires, $email);
            $update->execute();
            
            // Send email with reset link
            require_once 'send-reset-email.php';
            
            $success = 'Password reset link has been sent to your email';
        } else {
            $error = 'No account found with that email address';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        
        .reset-form {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
        }
        
        .reset-form h2 {
            color: #1666ba;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1666ba;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #deecfb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .reset-btn {
            background: #368ce7;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .reset-btn:hover {
            background: #1666ba;
        }
        
        .error {
            color: #ff4444;
            margin-bottom: 1rem;
        }
        
        .success {
            color: #00C851;
            margin-bottom: 1rem;
        }
        
        .login-link {
            margin-top: 1.5rem;
            display: block;
            color: #1666ba;
            text-decoration: none;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reset-form">
        <h2>Forgot Password</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST" action="forgot-password.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="reset-btn">Send Reset Link</button>
        </form>
        <a href="index.php" class="login-link">Back to Login</a>
    </div>
</body>
</html>