<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$initials = substr($name, 0, 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    
    header("Location: payment_confirmation.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAY DUES</title>
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
            background-color: white;
            position: relative;
            min-height: 100vh;
        }

        .page-title-container {
            background: white;
            padding: 20px 10%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .page-title {
            color: #1666ba;
            font-size: 2.2rem;
            font-weight: 700;
            max-width: 1200px;
            margin: 0 auto;
        }

        .main-container {
            display: flex;
            justify-content: center;
            min-height: calc(100vh - 180px);
            padding: 0 10% 80px;
            background-color: white;
        }

        .payment-container {
            display: flex;
            max-width: 1200px;
            width: 100%;
            gap: 30px;
        }

        .payment-info {
            flex: 1;
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border: 1px solid #ddd;
        }

        .payment-info h2 {
            text-align: left;
            margin-bottom: 15px;
            color: #1666ba;
        }

        .payment-form-container {
            flex: 1;
            position: relative;
        }

        #payment-form {
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            height: 100%;
            border: 1px solid #ddd;
        }

        .navigation-arrows {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 100;
        }

        .nav-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-text {
            color: #1666ba;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s;
        }

        .arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: linear-gradient(to right, #1666ba, #0d4a8a);
            border-radius: 50%;
            color: white;
            font-size: 22px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .arrow:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        h2 {
            color: #1666ba;
            margin-bottom: 25px;
        }

        .qr-display {
            text-align: center;
            margin: 20px 0;
        }

        .qr-display img {
            max-width: 300px;
            border: 1px solid #ddd;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }

        .payment-options {
            background-color: #e1f0fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .payment-options h3 {
            color: #1666ba;
            margin-bottom: 15px;
            text-align: left;
        }

        .payment-options p {
            margin-bottom: 10px;
            text-align: left;
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
        }

        .form-grp input,
        .form-grp select {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            width: 100%;
        }

        .form-grp input:focus,
        .form-grp select:focus {
            border-color: #1666ba;
            outline: none;
        }

        .file-upload-box {
            border: 2px dashed #1666ba;
            padding: 30px;
            border-radius: 8px;
            background-color: #e1f0fa;
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-box:hover {
            background-color: #d0e5f5;
        }

        .file-upload-box i {
            font-size: 40px;
            color: #1666ba;
            margin-bottom: 10px;
        }

        .file-upload-box p {
            margin-bottom: 10px;
            color: #1666ba;
            font-weight: 500;
        }

        .file-upload-box input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .submit-btn {
            margin-top: 15px;
            padding: 12px;
            width: 100%;
            background: linear-gradient(to right, #1666ba, #0d4a8a);
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(13, 74, 138, 0.4);
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 1.8rem;
                margin: 20px 0;
                padding: 0 5%;
            }
            
            .payment-container {
                flex-direction: column;
            }

            .row {
                flex-direction: column;
            }

            .payment-info,
            #payment-form {
                padding: 30px 20px;
            }

            .navigation-arrows {
                position: fixed;
                bottom: 10px;
                padding: 0 10px;
            }

            .arrow {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .nav-group {
                gap: 8px;
            }

            .nav-text {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <div class="page-title-container">
        <h1 class="page-title">PAY DUES</h1>
    </div>
    
    <div class="main-container">
        <div class="payment-container">
            <div class="payment-info">
                <h2>SCAN TO PAY</h2>
                <div class="qr-display">
                    <img src="https://ph-test-11.slatic.net/p/b4e1945f971c9fd8bd4eb1a1cf606c1b.jpg" alt="GCash QR Code">
                </div>
                
                <div class="payment-options">
                    <h3>PAYMENT OPTIONS</h3>
                    <p><strong>GCash:</strong> 09123456789</p>
                    <p><strong>BDO:</strong> 01384320182</p>
                    <p><strong>BPI:</strong> 29034390248</p>
                    <p><strong>Cash:</strong> Visit our office</p>
                </div>
            </div>

            <div class="payment-form-container">
                <form id="payment-form" action="pay-dues.php" method="post" enctype="multipart/form-data">
                    <h2>PROOF OF PAYMENT</h2>
                    
                    <div class="file-upload-box">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop your file here or click to browse</p>
                        <input type="file" id="proof" name="proof" required accept="image/png, image/jpeg">
                        <small>Accepted formats: JPG, PNG (Max 5MB)</small>
                    </div>

                    <div class="row">
                        <div class="form-grp">
                            <label for="amount">Amount:</label>
                            <input type="number" id="amount" name="amount" required>
                        </div>
                        
                        <div class="form-grp">
                            <label for="payment-method">Payment Method:</label>
                            <select id="payment-method" name="payment-method" required>
                                <option value=""></option>
                                <option value="Gcash">GCash</option>
                                <option value="BDO">Bank Transfer - BDO</option>
                                <option value="BPI">Bank Transfer - BPI</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grp">
                        <label for="ref-num">Reference Number:</label>
                        <input type="text" id="ref-num" name="ref-num" required>
                    </div>

                    <button type="submit" class="submit-btn">SUBMIT</button>
                </form>
            </div>
        </div>
    </div>

    <div class="navigation-arrows">
        <div class="nav-group">
            <a href="view-dues.php" class="arrow"><i class="fa-solid fa-arrow-left"></i></a>
            <p class="nav-text">View Dues</p>
        </div>

        <div class="nav-group">
            <p class="nav-text">View Payment History</p>
            <a href="payment-history.php" class="arrow"><i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</body>
</html>