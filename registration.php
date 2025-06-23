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

                <div class="row">
                    <div class="form-grp">
                        <label for="firstname">First Name: </label>
                        <input type="text" name="firstname" id="firstname">
                    </div>

                    <div class="form-grp">
                        <label for="lastname">Last Name: </label>
                        <input type="text" name="lastname" id="lastname">
                    </div>
                </div>

                <div class="row">
                    <div class="form-grp">
                        <label for="email">Email: </label>
                        <input type="email" name="email" id="email">
                    </div>

                    <div class="form-grp">
                        <label for="phonenumber">Phone Number: </label>
                        <input type="number" name="phonenumber" id="phonenumber">
                    </div>
                </div>

                <div class="row">
                    <div class="form-grp">
                        <label for="password">Password: </label>
                        <input type="password" name="password" id="password">
                    </div>

                    <div class="form-grp">
                        <label for="con-password">Confirm Password: </label>
                        <input type="password" name="con-password" id="con-password">
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