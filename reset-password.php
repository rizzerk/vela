<?php
require_once 'connection.php';
$error = '';
$success = '';

// Check if token and email are valid
if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    
    $current_time = date("Y-m-d H:i:s");
    
    $stmt = $conn->prepare("SELECT user_id FROM USERS WHERE email = ? AND reset_token = ? AND reset_token_expires > ?");
    $stmt->bind_param("sss", $email, $token, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $error = 'Invalid or expired reset link. Please request a new one.';
    }
} else {
    $error = 'Invalid reset link. Please request a new one.';
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } else {
        // Update password and clear reset token
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE USERS SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE email = ?");
        $update->bind_param("ss", $hashed_password, $email);
        
        if ($update->execute()) {
            $success = 'Password has been reset successfully. You can now login with your new password.';
        } else {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #666;
        }
    </style>
    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthText = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthText.textContent = '';
                return;
            }
            
            if (password.length < 8) {
                strengthText.textContent = 'Weak (min 8 characters)';
                strengthText.style.color = '#ff4444';
            } else if (password.length < 12) {
                strengthText.textContent = 'Medium';
                strengthText.style.color = '#ffbb33';
            } else {
                strengthText.textContent = 'Strong';
                strengthText.style.color = '#00C851';
            }
        }
    </script>
</head>
<body>
    <div class="reset-form">
        <h2>Reset Password</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <a href="index.php" class="login-link">Back to Login</a>
        <?php else: ?>
        <form method="POST" action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>&email=<?php echo htmlspecialchars($email); ?>">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required onkeyup="checkPasswordStrength()">
                <div id="password-strength" class="password-strength"></div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="reset-btn">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>