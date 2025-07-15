<?php
session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

// Check if property_id is provided
if (!isset($_GET['property_id'])) {
    header('Location: properties.php');
    exit;
}

$property_id = $_GET['property_id'];
$error = '';
$success = '';
$property = [];
$user_id = $_SESSION['user_id'];

// Get property details
if ($conn) {
    $stmt = $conn->prepare("SELECT p.*, pp.file_path 
                          FROM PROPERTY p 
                          LEFT JOIN PROPERTY_PHOTO pp ON p.property_id = pp.property_id 
                          WHERE p.property_id = ? AND p.status = 'vacant'
                          GROUP BY p.property_id");
    if ($stmt) {
        $stmt->bind_param("i", $property_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $property = $result->fetch_assoc();
        $stmt->close();
        
        if (!$property) {
            $error = "Property not found or no longer available";
        }
    }
}

// Check if user already has an application for this property
$existing_application = false;
if ($conn && $property) {
    $stmt = $conn->prepare("SELECT application_id FROM APPLICATIONS 
                           WHERE property_id = ? AND applicant_id = ? 
                           AND status IN ('pending', 'approved')");
    if ($stmt) {
        $stmt->bind_param("ii", $property_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $existing_application = true;
        }
        $stmt->close();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$existing_application && $property) {
    $num_tenants = $_POST['num_tenants'];
    $co_tenants = $_POST['co_tenants'];
    
    // File upload handling
    $upload_dir = 'uploads/';
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $documents = [];
    $upload_errors = [];
    
    // Check each required document
    $required_docs = ['govt_id', 'proof_income', 'proof_billing'];
    foreach ($required_docs as $doc_type) {
        if (isset($_FILES[$doc_type])) {
            $file = $_FILES[$doc_type];

            if ($file['error'] == UPLOAD_ERR_OK) {
                // Validate file type and size
                $file_type = mime_content_type($file['tmp_name']);
                if (!in_array($file_type, $allowed_types)) {
                    $upload_errors[] = "Invalid file type for $doc_type. Only PDF, JPG, PNG allowed.";
                    continue;
                }

                if ($file['size'] > $max_size) {
                    $upload_errors[] = "File too large for $doc_type. Max 5MB allowed.";
                    continue;
                }

                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = "user_{$user_id}_" . uniqid() . ".$ext";
                $destination = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $documents[$doc_type] = $destination;
                } else {
                    $upload_errors[] = "Failed to upload $doc_type";
                }
            } else {
                $upload_errors[] = "Error uploading $doc_type";
            }
        } else {
            $upload_errors[] = "Missing required document: $doc_type";
        }
    }
    
    // If no upload errors, create application
    if (empty($upload_errors) && count($documents) == count($required_docs)) {
        $documents_str = json_encode($documents);
        
        $stmt = $conn->prepare("INSERT INTO APPLICATIONS 
                               (property_id, applicant_id, num_of_tenants, co_tenants, documents, submitted_at, status)
                               VALUES (?, ?, ?, ?, ?, NOW(), 'pending')");
        if ($stmt) {
            $stmt->bind_param("iiiss", $property_id, $user_id, $num_tenants, $co_tenants, $documents_str);
            if ($stmt->execute()) {
                $success = "Your application has been submitted successfully!";
                $existing_application = true;
                
                // Update property status to "pending" (optional)
                $update_stmt = $conn->prepare("UPDATE PROPERTY SET status = 'pending' WHERE property_id = ?");
                $update_stmt->bind_param("i", $property_id);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                $error = "Failed to submit application. Please try again.";
            }
            $stmt->close();
        } else {
            $error = "Database error. Please try again.";
        }
    } else {
        $error = implode("<br>", $upload_errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VELA - Property Application</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            color: #1666ba;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .application-container {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }

        .property-card {
            flex: 1;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .property-image {
            height: 250px;
            background-size: cover;
            background-position: center;
        }

        .property-details {
            padding: 1.5rem;
        }

        .property-title {
            font-size: 1.5rem;
            color: #1666ba;
            margin-bottom: 0.5rem;
        }

        .property-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1666ba;
            margin-bottom: 1rem;
        }

        .property-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .feature {
            background: #deecfb;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #1666ba;
            font-weight: 600;
        }

        .property-description {
            color: #555;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .property-location {
            display: flex;
            align-items: center;
            color: #666;
            margin-bottom: 1rem;
        }

        .property-location i {
            margin-right: 0.5rem;
            color: #1666ba;
        }

        .application-form-container {
            flex: 1;
        }

        .application-form {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .form-title {
            font-size: 1.5rem;
            color: #1666ba;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #444;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #368ce7;
            outline: none;
        }

        .file-upload {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1rem;
            background: #f0f7ff;
            border: 1px dashed #368ce7;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .file-upload-label:hover {
            background: #e0efff;
        }

        .file-upload-label i {
            color: #368ce7;
        }

        .file-name {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.3rem;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(54, 140, 231, 0.4);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .error-message {
            color: #e74c3c;
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: #fdecea;
            border-radius: 8px;
            border-left: 4px solid #e74c3c;
        }

        .success-message {
            color: #27ae60;
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: #e8f8f0;
            border-radius: 8px;
            border-left: 4px solid #27ae60;
        }

        .existing-application {
            text-align: center;
            padding: 2rem;
            background: #fff8e6;
            border-radius: 8px;
            border-left: 4px solid #f39c12;
        }

        .existing-application h3 {
            color: #f39c12;
            margin-bottom: 1rem;
        }

        .existing-application p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .view-status-btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: #f39c12;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }

        .view-status-btn:hover {
            background: #e67e22;
        }

        @media (max-width: 768px) {
            .application-container {
                flex-direction: column;
            }
            
            .property-image {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <?php include "includes/navbar/navbarIN.html" ?>
    
    <div class="container">
        <div class="header">
            <h1>Property Application</h1>
            <p>Complete your application for this rental property</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$property): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> Property not found or no longer available for application.
            </div>
        <?php else: ?>
            <div class="application-container">
                <div class="property-card">
                    <div class="property-image" style="background-image: url('<?php echo $property['file_path'] ? htmlspecialchars($property['file_path']) : './images/default-property.jpg'; ?>')"></div>
                    <div class="property-details">
                        <h2 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h2>
                        <div class="property-price">₱<?php echo number_format($property['monthly_rent'], 2); ?>/month</div>
                        
                        <div class="property-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($property['address']); ?></span>
                        </div>
                        
                        <div class="property-features">
                            <span class="feature"><?php echo htmlspecialchars($property['property_type']); ?></span>
                            <span class="feature">Vacant</span>
                        </div>
                        
                        <p class="property-description"><?php echo htmlspecialchars($property['description']); ?></p>
                    </div>
                </div>
                
                <div class="application-form-container">
                    <?php if ($existing_application): ?>
                        <div class="existing-application">
                            <h3><i class="fas fa-info-circle"></i> Application Submitted</h3>
                            <p>You've already submitted an application for this property. The landlord is currently reviewing your application.</p>
                            <a href="application-status.php?id=<?php echo $property_id; ?>" class="view-status-btn">View Application Status</a>
                        </div>
                    <?php else: ?>
                        <form class="application-form" method="POST" enctype="multipart/form-data">
                            <h3 class="form-title">Application Form</h3>
                            
                            <div class="form-group">
                                <label for="num_tenants">Number of Accompanying Tenants</label>
                                <select id="num_tenants" name="num_tenants" required>
                                    <option value="0">Just myself</option>
                                    <option value="1">1 additional tenant</option>
                                    <option value="2">2 additional tenants</option>
                                    <option value="3">3 additional tenants</option>
                                    <option value="4">4 additional tenants</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="co_tenants">Full Names of All Tenants (comma separated)</label>
                                <textarea id="co_tenants" name="co_tenants" rows="3" placeholder="e.g. Juan Dela Cruz, Maria Santos" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Required Documents</label>
                                <p style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Upload clear copies of the following documents (PDF, JPG, PNG, max 5MB each):</p>
                                
                                <div class="file-upload">
                                    <label for="govt_id" class="file-upload-label">
                                        <i class="fas fa-id-card"></i>
                                        <span>Government Issued ID</span>
                                    </label>
                                    <input type="file" id="govt_id" name="govt_id" accept=".pdf,.jpg,.jpeg,.png" required style="display: none;">
                                    <div class="file-name" id="govt_id_name">No file selected</div>
                                </div>
                                
                                <div class="file-upload" style="margin-top: 1rem;">
                                    <label for="proof_income" class="file-upload-label">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                        <span>Proof of Income</span>
                                    </label>
                                    <input type="file" id="proof_income" name="proof_income" accept=".pdf,.jpg,.jpeg,.png" required style="display: none;">
                                    <div class="file-name" id="proof_income_name">No file selected</div>
                                </div>
                                
                                <div class="file-upload" style="margin-top: 1rem;">
                                    <label for="proof_billing" class="file-upload-label">
                                        <i class="fas fa-file-alt"></i>
                                        <span>Proof of Billing</span>
                                    </label>
                                    <input type="file" id="proof_billing" name="proof_billing" accept=".pdf,.jpg,.jpeg,.png" required style="display: none;">
                                    <div class="file-name" id="proof_billing_name">No file selected</div>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-top: 2rem;">
                                <button type="submit" class="submit-btn">
                                    <i class="fas fa-paper-plane"></i> Submit Application
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Display selected file names
        document.getElementById('govt_id').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
            document.getElementById('govt_id_name').textContent = fileName;
        });
        
        document.getElementById('proof_income').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
            document.getElementById('proof_income_name').textContent = fileName;
        });
        
        document.getElementById('proof_billing').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
            document.getElementById('proof_billing_name').textContent = fileName;
        });
    </script>
</body>
</html>