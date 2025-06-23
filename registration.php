<?php
session_start();
require_once '../connection.php';

// Initialize variables
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup'])) {
    // Sanitize inputs
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $email = $conn->real_escape_string($_POST['email']);
    $phonenumber = $conn->real_escape_string($_POST['phonenumber']);
    $password = $_POST['password'];
    $confirm_password = $_POST['con-password'];
    
    // Combine first and last name
    $name = $firstname . ' ' . $lastname;
    
    // Validate inputs
    if (empty($firstname) || empty($lastname) || empty($email) || empty($phonenumber) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        try {
            // Check if email exists
            $stmt = $conn->prepare("SELECT email FROM USERS WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = "Email already registered";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Default role is 'tenant'
                $role = 'tenant';
                
                // Insert new user
                $insert_stmt = $conn->prepare("INSERT INTO USERS (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("sssss", $name, $email, $phonenumber, $hashed_password, $role);
                
                if ($insert_stmt->execute()) {
                    $success = "Registration successful! You can now login.";
                    // Clear form
                    $_POST = array();
                } else {
                    $error = "Registration failed. Please try again.";
                }
                
                $insert_stmt->close();
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = "A system error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REGISTRATION</title>
    <script src="https://kit.fontawesome.com/dddee79f2e.js" crossorigin="anonymous"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        .header {
            background-color: #155670;
            color: white;
            padding: 15px 5%;
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            box-sizing: border-box;
        }

        .main {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            padding: 20px;
            font-family: 'Poppins';
        }

        .form-container {
            position: relative;
            width: 100%;
            max-width: 800px;
        }

        .back-arrow {
            position: absolute;
            top: -50px;
            left: 0;
        }

        .back-arrow a {
            font-size: 22px;
            color: black;
            text-decoration: none;
        }

        .back-arrow a:hover {
            color: #155670;
        }

        #signup-form {
            background-color: lightblue;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            max-width: 800px;
            width: 100%;
        }

        .row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-grp {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-grp label {
            text-align: left;
            margin-bottom: 5px;
        }

        .form-grp input {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-grp input:focus {
            border-color: #155670;
            outline: none;
        }

        .footer a {
            color: #155670;
            text-decoration: none;
        }

        .footer a:hover {
            color: #357e9c;
        }

        .signup-btn {
            margin-top: 15px;
            padding: 12px;
            width: 30%;
            background-color: #357e9c;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .signup-btn:hover {
            background-color: #155670;
        }

        .error-message {
            color: #ff4444;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 5px;
        }

        .success-message {
            color: #00C851;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .row {
                flex-direction: column;
            }

            .signup-btn {
                width: 100%;
            }

            .back-arrow {
                position: static;
                margin-bottom: 20px;
                text-align: left;
            }

            .form-container {
                padding: 0 10px;
            }
        }
    </style>
</head>

<body>

    <nav class="header">
        <div class="header-logo">
            <h1 id="logo">VELA</h1>
        </div>
    </nav>

    <div class="main">
        <div class="form-container">
            <div class="back-arrow">
                <a href="login.php"><i class="fa-solid fa-arrow-left"></i></a>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <form id="signup-form" method="POST" action="">
                <div class="row">
                    <div class="form-grp">
                        <label for="firstname">First Name: </label>
                        <input type="text" name="firstname" id="firstname" value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>" required>
                    </div>

                    <div class="form-grp">
                        <label for="lastname">Last Name: </label>
                        <input type="text" name="lastname" id="lastname" value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-grp">
                        <label for="email">Email: </label>
                        <input type="email" name="email" id="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>

                    <div class="form-grp">
                        <label for="phonenumber">Phone Number: </label>
                        <input type="tel" name="phonenumber" id="phonenumber" value="<?php echo isset($_POST['phonenumber']) ? htmlspecialchars($_POST['phonenumber']) : ''; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-grp">
                        <label for="password">Password: </label>
                        <input type="password" name="password" id="password" required minlength="8">
                    </div>

                    <div class="form-grp">
                        <label for="con-password">Confirm Password: </label>
                        <input type="password" name="con-password" id="con-password" required minlength="8">
                    </div>
                </div>

                <div class="footer">
                    <p><em>Already have an account? <strong><a href="login.php">Sign in</a></strong></em></p>
                </div>

                <div>
                    <button type="submit" name="signup" class="signup-btn"><strong>Sign Up</strong></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password match validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('con-password');
        const form = document.getElementById('signup-form');

        form.addEventListener('submit', function(e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
            }
        });

        // Show password strength (optional)
        password.addEventListener('input', function() {
            const strengthText = document.getElementById('password-strength');
            const password = this.value;
            
            if (!strengthText && password.length > 0) {
                const div = document.createElement('div');
                div.id = 'password-strength';
                div.style.marginTop = '5px';
                div.style.fontSize = '0.8rem';
                div.style.textAlign = 'left';
                this.parentNode.appendChild(div);
            }
            
            if (password.length > 0) {
                let strength = 0;
                if (password.length >= 8) strength++;
                if (password.match(/[A-Z]/)) strength++;
                if (password.match(/[0-9]/)) strength++;
                if (password.match(/[^A-Za-z0-9]/)) strength++;
                
                const strengthMessages = ['Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong'];
                const strengthColors = ['#ff4444', '#ffbb33', '#ffbb33', '#00C851', '#00C851'];
                
                document.getElementById('password-strength').textContent = 
                    `Strength: ${strengthMessages[strength]}`;
                document.getElementById('password-strength').style.color = strengthColors[strength];
            } else if (strengthText) {
                strengthText.remove();
            }
        });
    </script>
</body>
</html>