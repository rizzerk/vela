<?php
session_start();
require_once 'connection.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT user_id, name, role, password FROM USERS WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] === 'landlord') {
                header('Location: LANDLORD/dashboard.php');
            } elseif ($user['role'] === 'tenant') {
                header('Location: TENANT/dashboard.php');
            } elseif ($user['role'] === 'general_user') {
                header('Location: application-reservation.php');
            }else {
                header('Location: index.php');
            }
            exit;
        }
    }
    $error = "Invalid credentials";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN</title>
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
            max-width: 600px;
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

        #login-form {
            background-color: lightblue;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            width: 100%;
        }

        .form-grp {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        .form-grp label {
            text-align: left;
            margin-bottom: 8px;
        }

        .form-grp input {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            width: 100%;
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

        .login-btn {
            margin-top: 15px;
            padding: 12px;
            width: 30%;
            background-color: #357e9c;
            border: none;
            border-radius: 5px;
            color: white;
        }

        .login-btn:hover {
            background-color: #155670;
        }

        @media (max-width: 768px) {
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

            <form id="login-form" action="" method="POST">
                <?php if (isset($error)): ?>
                    <div style="color: red; margin-bottom: 15px;"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="form-grp">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" placeholder="Enter Username" required>
                </div>

                <div class="form-grp">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" placeholder="Enter Password" required>
                </div>

                <div class="footer">
                    <p><em>Don't have an account? <strong><a href="">Register here</a></strong></em></p>
                </div>

                <div>
                    <button type="submit" name="login" class="login-btn"><strong>Login</strong></button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>