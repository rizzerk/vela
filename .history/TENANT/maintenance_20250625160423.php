<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'tenant') {
    header('Location: ../index.php');
    exit();
}

$userName = $_SESSION['name'] ?? 'Tenant';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Request - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #deecfb 100%);
            margin: 0;
            padding: 80px 0 40px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #1666ba;
            text-align: center;
            margin-bottom: 2rem;
        }

        .maintenance-wrapper {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .form-section, .request-table {
            flex: 1 1 45%;
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            border: 1px solid #deecfb;
        }

        .form-section h2,
        .request-table h2 {
            font-size: 1.4rem;
            color: #1666ba;
            margin-bottom: 1rem;
        }

        label {
            font-weight: 600;
            display: block;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #ccc;
            background: #f2f7fb;
        }

        input[type="file"] {
            background: #f2f7fb;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px dashed #999;
            width: 100%;
        }

        button {
            margin-top: 1.5rem;
            background: #1666ba;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #104e91;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f0f6fd;
            font-weight: 600;
            color: #1666ba;
        }

        @media (max-width: 768px) {
            .maintenance-wrapper {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar/tenant-navbar.php'; ?>

    <div class="container">
        <h1 class="title">Maintenance Request</h1>

        <div class="maintenance-wrapper">
            <!-- Form Section -->
            <div class="form-section">
                <h2>Submit a Request</h2>
                <form action="#" method="POST" enctype="multipart/form-data">
                    <label for="issueType">Issue Type</label>
                    <input type="text" id="issueType" name="issueType" placeholder="e.g. Broken faucet" required>

                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" placeholder="Describe the issue in detail..." required></textarea>

                    <label for="imageUpload">Upload Image</label>
                    <input type="file" id="imageUpload" name="imageUpload" accept="image/*">

                    <button type="submit">Submit</button>
                </form>
            </div>

            <!-- My Requests Table -->
            <div class="request-table">
                <h2>My Requests</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Type</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Example placeholder rows -->
                        <tr>
                            <td>#001</td>
                            <td>Broken Window</td>
                            <td>2025-06-20</td>
                            <td>Pending</td>
                        </tr>
                        <tr>
                            <td>#002</td>
                            <td>Leaking Faucet</td>
                            <td>2025-06-15</td>
                            <td>Resolved</td>
                        </tr>
                        <!-- Real data will come from backend -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
