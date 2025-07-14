<?php 
session_start();
require_once "../connection.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'tenant') {
    header('Location: ../index.php');
    exit();
}

$userName = $_SESSION['name'] ?? 'Tenant';
$userId = $_SESSION['user_id'] ?? 0;  // Make sure user_id is set in session on login

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $issueType = trim($_POST["issueType"]);
    $description = trim($_POST["description"]);
    $imagePath = null;

    // Find active lease ID for this user
    $leaseStmt = $conn->prepare("SELECT lease_id FROM LEASE WHERE tenant_id = ? AND active = 1 LIMIT 1");
    $leaseStmt->bind_param("i", $userId);
    $leaseStmt->execute();
    $leaseResult = $leaseStmt->get_result();

    if ($leaseResult && $leaseRow = $leaseResult->fetch_assoc()) {
        $leaseId = $leaseRow['lease_id'];

        // Handle image upload if any
        if (!empty($_FILES['imageUpload']['name'])) {
            $uploadDir = "../uploads/maintenance/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileTmp = $_FILES['imageUpload']['tmp_name'];
            $fileName = basename($_FILES['imageUpload']['name']);
            $targetFilePath = $uploadDir . time() . "_" . $fileName;

            if (move_uploaded_file($fileTmp, $targetFilePath)) {
                $imagePath = $targetFilePath;
            }
        }

        // Insert into MAINTENANCE_REQUEST table with image path
        $insertStmt = $conn->prepare("
            INSERT INTO MAINTENANCE_REQUEST (lease_id, description, status, requested_at, updated_at, image_path)
            VALUES (?, ?, 'pending', NOW(), NOW(), ?)
        ");
        $insertStmt->bind_param("iss", $leaseId, $description, $imagePath);

        if ($insertStmt->execute()) {
            echo "<script>alert('Request submitted successfully.'); window.location.href = 'maintenance.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error submitting request.');</script>";
        }

        $insertStmt->close();
    } else {
        echo "<script>alert('No active lease found.');</script>";
    }

    $leaseStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Maintenance Request - VELA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, #deecfb 100%);
            margin: 0;
            padding-top: 80px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        h1.title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1666ba;
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .maintenance-wrapper {
            display: flex;
            gap: 2rem;
            flex-wrap: nowrap;
            justify-content: space-between;
            align-items: flex-start;
        }

        .form-section, .request-section {
            flex: 1 1 48%;
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(22, 102, 186, 0.06);
            border: 1px solid #deecfb;
            min-width: 320px;
            max-height: 600px;
            overflow-y: auto;
        }

        h2 {
            font-size: 1.5rem;
            color: #1666ba;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        label {
            font-weight: 600;
            display: block;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }

        input[type="text"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #ccc;
            background: #f2f7fb;
            font-size: 0.95rem;
            box-sizing: border-box;
        }

        input[type="file"] {
            border-style: dashed;
            border-color: #999;
        }

        button {
            margin-top: 1.5rem;
            background: #1666ba;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
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
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 0.95rem;
        }

        th {
            background-color: #f0f6fd;
            color: #1666ba;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .maintenance-wrapper {
                flex-wrap: wrap;
                flex-direction: column;
            }

            .form-section, .request-section {
                flex: 1 1 100%;
                max-height: none;
                overflow-y: visible;
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar/tenant-navbar.php'; ?>

    <div class="container">
        <h1 class="title">Maintenance Request</h1>

        <div class="maintenance-wrapper">
            <!-- Left: Maintenance Request Form -->
            <section class="form-section" aria-labelledby="submit-request">
                <h2 id="submit-request">Submit a Request</h2>
                <form action="#" method="POST" enctype="multipart/form-data">
                    <label for="issueType">Issue Type</label>
                    <input type="text" id="issueType" name="issueType" placeholder="e.g. Broken faucet" required />

                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" placeholder="Describe the issue in detail..." required></textarea>

                    <label for="imageUpload">Upload Image</label>
                    <input type="file" id="imageUpload" name="imageUpload" accept="image/*" />

                    <button type="submit">Submit</button>
                </form>
            </section>

            <!-- Right: User's Requests Table -->
            <section class="request-section" aria-labelledby="my-requests">
                <h2 id="my-requests">My Requests</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Image</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($userId > 0) {
                            $stmt = $conn->prepare("
                                SELECT MR.request_id, MR.description, MR.requested_at, MR.status, MR.image_path
                                FROM MAINTENANCE_REQUEST MR
                                JOIN LEASE L ON MR.lease_id = L.lease_id
                                WHERE L.tenant_id = ?
                                ORDER BY MR.requested_at DESC
                            ");
                            $stmt->bind_param("i", $userId);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows === 0) {
                                echo "<tr><td colspan='5' style='text-align:center;'>No maintenance requests found.</td></tr>";
                            } else {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>#".htmlspecialchars($row['request_id'])."</td>
                                            <td>".htmlspecialchars($row['description'])."</td>
                                            <td>".htmlspecialchars(date('Y-m-d', strtotime($row['requested_at'])))."</td>
                                            <td>".ucfirst(str_replace('_', ' ', htmlspecialchars($row['status'])))."</td>";

                                    if (!empty($row['image_path'])) {
                                        echo "<td><img src='" . htmlspecialchars($row['image_path']) . "' style='width:60px;'></td>";
                                    } else {
                                        echo "<td>N/A</td>";
                                    }

                                    echo "</tr>";
                                }
                            }
                            $stmt->close();
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center;'>User not logged in properly.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</body>
</html>
