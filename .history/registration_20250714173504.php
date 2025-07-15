<?php
require_once 'connection.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {

    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phonenumber = trim($_POST['phonenumber'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['con-password'] ?? '';
    $role = 'general_user'; 


    if (empty($firstname)) {
        $errors[] = "First name is required";
    }
    
    if (empty($lastname)) {
        $errors[] = "Last name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($phonenumber)) {
        $errors[] = "Phone number is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        try {

            $stmt = $conn->prepare("SELECT email FROM USERS WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = "Email already exists";
            } else {
    
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
                $full_name = $firstname . ' ' . $lastname;
                
                $stmt = $conn->prepare("INSERT INTO USERS (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $full_name, $email, $phonenumber, $hashed_password, $role);
                
                if ($stmt->execute()) {
                    $success = true;

                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            $errors[] = "An error occurred. Please try again later.";
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

        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .main {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 100px);
            text-align: center;
            padding: 40px 20px;
        }

        .form-container {
            position: relative;
            width: 100%;
            max-width: 800px;
            margin-top: 30px;
        }

        .back-arrow {
            position: absolute;
            top: -50px;
            left: 0;
        }

        .back-arrow a {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            color: #1666ba;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .back-arrow a:hover {
            color: #0d4a8a;
        }

        .back-arrow i {
            font-size: 22px;
        }

        #signup-form {
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
            border: 1px solid #e0e0e0;
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
            margin-bottom: 8px;
            color: #1666ba;
            font-weight: 500;
        }

        .form-grp input {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-grp input:focus {
            border-color: #1666ba;
            outline: none;
            box-shadow: 0 0 0 2px rgba(22, 102, 186, 0.1);
        }

        .footer {
            margin: 20px 0;
        }

        .footer a {
            color: #1666ba;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer a:hover {
            color: #0d4a8a;
            text-decoration: underline;
        }

        .signup-btn {
            margin-top: 15px;
            padding: 14px;
            width: 40%;
            background: linear-gradient(to right, #1666ba, #0d4a8a);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .signup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(13, 74, 138, 0.3);
        }

        .error-message {
            color: #c62828;
            margin-bottom: 20px;
            text-align: left;
            background-color: #ffebee;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #c62828;
        }

        .success-message {
            color: #2e7d32;
            margin-bottom: 20px;
            text-align: left;
            background-color: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2e7d32;
            font-weight: 600;
        }

        .form-title {
            color: #1666ba;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
            font-weight: 700;
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
            
            #signup-form {
                padding: 30px 20px;
            }
            
            .header {
                padding: 12px 5%;
            }
            
            .logo {
                font-size: 1.4rem;
            }
        }
    </style>
</head>

<body>
    <div class="main">
        <div class="form-container">
            <div class="back-arrow">
                <a href="index.php"><i class="fa-solid fa-arrow-left"></i></a>
            </div>

            <form id="signup-form" action="" method="POST">
                <h2 class="form-title">Create Your Account</h2>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        Registration successful! You can now <a href="">login</a>.
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="form-grp">
                        <label for="firstname">First Name</label>
                        <input type="text" name="firstname" id="firstname" value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" required>
                    </div>

                    <div class="form-grp">
                        <label for="lastname">Last Name</label>
                        <input type="text" name="lastname" id="lastname" value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-grp">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-grp">
                        <label for="phonenumber">Phone Number</label>
                        <input type="tel" name="phonenumber" id="phonenumber" value="<?php echo htmlspecialchars($_POST['phonenumber'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-grp">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                    </div>

                    <div class="form-grp">
                        <label for="con-password">Confirm Password</label>
                        <input type="password" name="con-password" id="con-password" required>
                    </div>
                </div>

                <div class="footer">
                    <p><em>Already have an account? <a href="">Sign in</a></em></p>
                </div>

                <div>
                    <button type="submit" name="signup" class="signup-btn">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>