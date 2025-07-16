<?php
require_once 'connection.php';
require_once 'vendor/autoload.php'; // Load PHPMailer (adjust path as needed)

$errors = [];
$success = false;

// Function to send welcome email
function sendWelcomeEmail($email, $name) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // SMTP Configuration (same as your billing system)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'velacinco5@gmail.com'; // Your Gmail
        $mail->Password   = 'aycm atee woxl lmvj';  // App Password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('velacinco5@gmail.com', 'VELA Cinco Rentals');
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to VELA Cinco Rentals';
        
        $mail->Body = "
            <h2>Welcome, {$name}!</h2>
            <p>Thank you for registering with VELA Cinco Rentals. Your account has been successfully created.</p>
            <p>Please wait for the admin to activate your account.</p>
            <p>An email will be sent to notify you of the status of your account.</p>
            <p>If you have any questions, please don't hesitate to contact our support team.</p>
            <p>Thank you,<br>VELA Cinco Rentals Team</p>
        ";

        $mail->AltBody = "Welcome, {$name}!\n\n" .
            "Thank you for registering with VELA Cinco Rentals. Your account has been successfully created.\n\n" .
            "You can now log in to your account at http://localhost/vela/index.php using the credentials you provided during registration.\n\n" .
            "If you have any questions, please don't hesitate to contact our support team.\n\n" .
            "Thank you,\nVELA Cinco Rentals Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error for {$email}: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {

    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phonenumber = trim($_POST['phonenumber'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['con-password'] ?? '';
    $role = 'general_user'; 

    // Validation
    if (empty($firstname)) {
        $errors[] = "First name is required";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstname)) {
        $errors[] = "First name should contain only letters and spaces";
    }
    
    if (empty($lastname)) {
        $errors[] = "Last name is required";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $lastname)) {
        $errors[] = "Last name should contain only letters and spaces";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($phonenumber)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match('/^09[0-9]{9}$/', $phonenumber)) {
        $errors[] = "Phone number must be 11 digits starting with 09";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    } elseif (!preg_match('/[\W]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

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
                // Check if phone number already exists
                $stmt = $conn->prepare("SELECT phone FROM USERS WHERE phone = ?");
                $stmt->bind_param("s", $phonenumber);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $errors[] = "Phone number already exists";
                } else {
                    // Proceed with registration
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $full_name = $firstname . ' ' . $lastname;
                    
                    $stmt = $conn->prepare("INSERT INTO USERS (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $full_name, $email, $phonenumber, $hashed_password, $role);
                    
                    if ($stmt->execute()) {
                        // Send welcome email
                        if (sendWelcomeEmail($email, $full_name)) {
                            $success = true;
                        } else {
                            // Email failed but registration succeeded
                            $success = true;
                            $errors[] = "Registration successful but welcome email failed to send";
                        }
                    } else {
                        $errors[] = "Registration failed. Please try again.";
                    }
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
            position: relative;
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

        .form-grp .error {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 5px;
            text-align: left;
            display: none;
        }

        .form-grp input.invalid {
            border-color: #dc3545;
        }

        .form-grp input.valid {
            border-color: #28a745;
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

        .signup-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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

        .password-strength {
            margin-top: 5px;
            height: 5px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }

        .weak {
            background-color: #dc3545;
            width: 33%;
        }

        .medium {
            background-color: #ffc107;
            width: 66%;
        }

        .strong {
            background-color: #28a745;
            width: 100%;
        }

        .password-requirements {
            margin-top: 5px;
            font-size: 0.8rem;
            color: #6c757d;
            text-align: left;
        }

        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
        }

        .requirement i {
            margin-right: 5px;
            font-size: 0.7rem;
        }

        .requirement.valid {
            color: #28a745;
        }

        .requirement.invalid {
            color: #6c757d;
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
                        Registration successful! You can now <a href="index.php">login</a>.
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
                        <div class="error" id="firstname-error">First name should contain only letters and spaces</div>
                    </div>

                    <div class="form-grp">
                        <label for="lastname">Last Name</label>
                        <input type="text" name="lastname" id="lastname" value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" required>
                        <div class="error" id="lastname-error">Last name should contain only letters and spaces</div>
                    </div>
                </div>

                <div class="row">
                    <div class="form-grp">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <div class="error" id="email-error">Please enter a valid email address</div>
                    </div>

                    <div class="form-grp">
                        <label for="phonenumber">Phone Number</label>
                        <input type="tel" name="phonenumber" id="phonenumber" value="<?php echo htmlspecialchars($_POST['phonenumber'] ?? ''); ?>" required>
                        <div class="error" id="phonenumber-error">Phone number must be 11 digits starting with 09</div>
                    </div>
                </div>

                <div class="row">
                    <div class="form-grp">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                        <div class="password-strength">
                            <div class="strength-meter" id="strength-meter"></div>
                        </div>
                        <div class="password-requirements">
                            <div class="requirement" id="length-req">
                                <i class="fas fa-circle"></i> At least 8 characters
                            </div>
                            <div class="requirement" id="uppercase-req">
                                <i class="fas fa-circle"></i> At least 1 uppercase letter
                            </div>
                            <div class="requirement" id="lowercase-req">
                                <i class="fas fa-circle"></i> At least 1 lowercase letter
                            </div>
                            <div class="requirement" id="number-req">
                                <i class="fas fa-circle"></i> At least 1 number
                            </div>
                            <div class="requirement" id="special-req">
                                <i class="fas fa-circle"></i> At least 1 special character
                            </div>
                        </div>
                    </div>

                    <div class="form-grp">
                        <label for="con-password">Confirm Password</label>
                        <input type="password" name="con-password" id="con-password" required>
                        <div class="error" id="confirm-error">Passwords do not match</div>
                    </div>
                </div>

                <div class="footer">
                    <p><em>Already have an account? <a href="index.php">Sign in</a></em></p>
                </div>

                <div>
                    <button type="submit" name="signup" class="signup-btn" id="submit-btn">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form elements
            const form = document.getElementById('signup-form');
            const firstname = document.getElementById('firstname');
            const lastname = document.getElementById('lastname');
            const email = document.getElementById('email');
            const phonenumber = document.getElementById('phonenumber');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('con-password');
            const submitBtn = document.getElementById('submit-btn');

            // Error elements
            const firstnameError = document.getElementById('firstname-error');
            const lastnameError = document.getElementById('lastname-error');
            const emailError = document.getElementById('email-error');
            const phonenumberError = document.getElementById('phonenumber-error');
            const confirmError = document.getElementById('confirm-error');

            // Password strength elements
            const strengthMeter = document.getElementById('strength-meter');
            const lengthReq = document.getElementById('length-req');
            const uppercaseReq = document.getElementById('uppercase-req');
            const lowercaseReq = document.getElementById('lowercase-req');
            const numberReq = document.getElementById('number-req');
            const specialReq = document.getElementById('special-req');

            // Validation functions
            function validateName(name) {
                return /^[a-zA-Z\s]+$/.test(name);
            }

            function validateEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }

            function validatePhone(phone) {
                return /^09[0-9]{9}$/.test(phone);
            }

            function validatePassword(password) {
                const hasMinLength = password.length >= 8;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[\W_]/.test(password);

                return {
                    valid: hasMinLength && hasUppercase && hasLowercase && hasNumber && hasSpecial,
                    hasMinLength,
                    hasUppercase,
                    hasLowercase,
                    hasNumber,
                    hasSpecial
                };
            }

            function updatePasswordStrength(password) {
                const validation = validatePassword(password);
                let strength = 0;

                // Update requirements
                updateRequirement(lengthReq, validation.hasMinLength);
                updateRequirement(uppercaseReq, validation.hasUppercase);
                updateRequirement(lowercaseReq, validation.hasLowercase);
                updateRequirement(numberReq, validation.hasNumber);
                updateRequirement(specialReq, validation.hasSpecial);

                // Calculate strength
                if (validation.hasMinLength) strength += 20;
                if (validation.hasUppercase) strength += 20;
                if (validation.hasLowercase) strength += 20;
                if (validation.hasNumber) strength += 20;
                if (validation.hasSpecial) strength += 20;

                // Update strength meter
                strengthMeter.className = 'strength-meter';
                if (strength <= 40) {
                    strengthMeter.classList.add('weak');
                } else if (strength <= 80) {
                    strengthMeter.classList.add('medium');
                } else {
                    strengthMeter.classList.add('strong');
                }
            }

            function updateRequirement(element, isValid) {
                if (isValid) {
                    element.classList.add('valid');
                    element.classList.remove('invalid');
                    element.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    element.classList.add('invalid');
                    element.classList.remove('valid');
                    element.querySelector('i').className = 'fas fa-circle';
                }
            }

            function toggleError(element, errorElement, isValid, message) {
                if (!isValid) {
                    element.classList.add('invalid');
                    errorElement.textContent = message;
                    errorElement.style.display = 'block';
                } else {
                    element.classList.remove('invalid');
                    errorElement.style.display = 'none';
                }
            }

            function validateForm() {
                let isValid = true;

                // Validate first name
                const firstNameValid = validateName(firstname.value);
                toggleError(firstname, firstnameError, firstNameValid, 'First name should contain only letters and spaces');
                if (!firstNameValid) isValid = false;

                // Validate last name
                const lastNameValid = validateName(lastname.value);
                toggleError(lastname, lastnameError, lastNameValid, 'Last name should contain only letters and spaces');
                if (!lastNameValid) isValid = false;

                // Validate email
                const emailValid = validateEmail(email.value);
                toggleError(email, emailError, emailValid, 'Please enter a valid email address');
                if (!emailValid) isValid = false;

                // Validate phone number
                const phoneValid = validatePhone(phonenumber.value);
                toggleError(phonenumber, phonenumberError, phoneValid, 'Phone number must be 11 digits starting with 09');
                if (!phoneValid) isValid = false;

                // Validate password
                const passwordValidation = validatePassword(password.value);
                if (!passwordValidation.valid) isValid = false;

                // Validate password confirmation
                const passwordsMatch = password.value === confirmPassword.value;
                toggleError(confirmPassword, confirmError, passwordsMatch, 'Passwords do not match');
                if (!passwordsMatch) isValid = false;

                // Enable/disable submit button
                submitBtn.disabled = !isValid;

                return isValid;
            }

            // Event listeners
            firstname.addEventListener('input', function() {
                const isValid = validateName(this.value);
                toggleError(this, firstnameError, isValid, 'First name should contain only letters and spaces');
                validateForm();
            });

            lastname.addEventListener('input', function() {
                const isValid = validateName(this.value);
                toggleError(this, lastnameError, isValid, 'Last name should contain only letters and spaces');
                validateForm();
            });

            email.addEventListener('input', function() {
                const isValid = validateEmail(this.value);
                toggleError(this, emailError, isValid, 'Please enter a valid email address');
                validateForm();
            });

            phonenumber.addEventListener('input', function() {
                const isValid = validatePhone(this.value);
                toggleError(this, phonenumberError, isValid, 'Phone number must be 11 digits starting with 09');
                validateForm();
            });

            password.addEventListener('input', function() {
                updatePasswordStrength(this.value);
                validateForm();
            });

            confirmPassword.addEventListener('input', function() {
                const passwordsMatch = password.value === this.value;
                toggleError(this, confirmError, passwordsMatch, 'Passwords do not match');
                validateForm();
            });

            // Initial validation
            validateForm();
        });
    </script>
</body>
</html>