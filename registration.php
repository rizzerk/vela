<?php
// Include the database connection
require_once 'connection.php';

// Initialize variables
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    // Get form data
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phonenumber = trim($_POST['phonenumber'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['con-password'] ?? '';
    $role = 'tenant'; // Default role, you can change this if needed

    // Validate inputs
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

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT email FROM USERS WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = "Email already exists";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Combine first and last name
                $full_name = $firstname . ' ' . $lastname;
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO USERS (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $full_name, $email, $phonenumber, $hashed_password, $role);
                
                if ($stmt->execute()) {
                    $success = true;
                    // You can redirect to login page or show success message
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
        }

        .form-grp input {
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .footer a {
            color: #155670;
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
        }

        .signup-btn:hover {
            background-color: #155670;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
            text-align: left;
        }

        .success-message {
            color: green;
            margin-bottom: 15px;
            font-weight: bold;
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
            <h1 id="logo">Logo</h1>
        </div>
    </nav>

    <div class="main">

        <div class="form-container">
            <div class="back-arrow">
                <a href=""><i class="fa-solid fa-arrow-left"></i></a>
            </div>

            <form id="signup-form" action="" method="POST">
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
                        <label for="firstname">First Name: </label>
                        <input type="text" name="firstname" id="firstname" value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" required>
                    </div>

                    <div class="form-grp">
                        <label for="lastname">Last Name: </label>
                        <input type="text" name="lastname" id="lastname" value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-grp">
                        <label for="email">Email: </label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-grp">
                        <label for="phonenumber">Phone Number: </label>
                        <input type="number" name="phonenumber" id="phonenumber" value="<?php echo htmlspecialchars($_POST['phonenumber'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-grp">
                        <label for="password">Password: </label>
                        <input type="password" name="password" id="password" required>
                    </div>

                    <div class="form-grp">
                        <label for="con-password">Confirm Password: </label>
                        <input type="password" name="con-password" id="con-password" required>
                    </div>
                </div>

                <div class="footer">
                    <p><em>Already have an account? <strong><a href="">Sign in</a></strong></em></p>
                </div>

                <div>
                    <button type="submit" name="signup" class="signup-btn"><strong>Sign Up</strong></button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>