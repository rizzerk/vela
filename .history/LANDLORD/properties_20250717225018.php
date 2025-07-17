<?php
session_start();
include '../connection.php';
require '../vendor/autoload.php';
require_once "../includes/auth/landlord_auth.php";


// Check if user is logged in

function sendNewPropertyNotification($property_title, $property_address, $monthly_rent) {
    include '../connection.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Get all general users' emails
        $query = "SELECT email FROM USERS WHERE role = 'general_user'";
        $result = mysqli_query($conn, $query);
        $emails = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        if (empty($emails)) return true; // No users to notify
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'velacinco5@gmail.com';
        $mail->Password   = 'aycm atee woxl lmvj';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Sender
        $mail->setFrom('velacinco5@gmail.com', 'VELA Cinco Rentals');
        
        // Add all general users as BCC recipients
        foreach ($emails as $user) {
            $mail->addBCC($user['email']);
        }
      
        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'New Property Available: ' . $property_title;
        
        $mail->Body = "
            <h2>New Property Available!</h2>
            <p>A new rental property has just been published on VELA Cinco Rentals.</p>
            
            <h3>Property Details:</h3>
            <ul>
                <li><strong>Title:</strong> {$property_title}</li>
                <li><strong>Address:</strong> {$property_address}</li>
                <li><strong>Monthly Rent:</strong> ₱" . number_format($monthly_rent, 2) . "</li>
            </ul>
            
            <p>Visit our website to view more details and apply for this property.</p>
            
            <p>Thank you,<br>VELA Cinco Rentals Team</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("New property notification error: " . $e->getMessage());
        return false;
    }
}

// Fetch all properties with their first photo
$query = "SELECT p.*, 
                 (SELECT file_path FROM PROPERTY_PHOTO 
                  WHERE property_id = p.property_id 
                  ORDER BY uploaded_at ASC LIMIT 1) AS first_photo
          FROM PROPERTY p";
$result = mysqli_query($conn, $query);
$properties = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Fetch property details for editing (if requested)
if (isset($_GET['edit_id'])) {
    $edit_id = mysqli_real_escape_string($conn, $_GET['edit_id']);
    $edit_query = "SELECT * FROM PROPERTY WHERE property_id = '$edit_id'";
    $edit_result = mysqli_query($conn, $edit_query);
    $property_to_edit = mysqli_fetch_assoc($edit_result);
}

// Handle publish/unpublish action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $property_id = mysqli_real_escape_string($conn, $_GET['id']);
    $action = mysqli_real_escape_string($conn, $_GET['action']);
    
    if ($action === 'publish') {
        // Only allow publishing if property is vacant
        $check_query = "SELECT status, title, address, monthly_rent FROM PROPERTY WHERE property_id = '$property_id'";
        $check_result = mysqli_query($conn, $check_query);
        $property = mysqli_fetch_assoc($check_result);
        
        if ($property['status'] === 'vacant') {
            $update_query = "UPDATE PROPERTY SET published = TRUE WHERE property_id = '$property_id'";
            mysqli_query($conn, $update_query);
            
            // Send notification to all general users
            $emailSent = sendNewPropertyNotification(
                $property['title'],
                $property['address'],
                $property['monthly_rent']
            );
            
            if (!$emailSent) {
                $_SESSION['error'] = "Property published but failed to send notifications";
            }
            
            header("Location: properties.php");
            exit();
        } else {
            $_SESSION['error'] = "Only vacant properties can be published";
            header("Location: properties.php");
            exit();
        }
    } elseif ($action === 'unpublish') {
        $update_query = "UPDATE PROPERTY SET published = FALSE WHERE property_id = '$property_id'";
        mysqli_query($conn, $update_query);
        header("Location: properties.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Base styles */
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background-color: #f6f6f6; 
            color: #1e293b; 
            line-height: 1.6; 
            min-height: 100vh; 
        }
        
        .main-content { 
            margin-left: 250px; 
            padding: 2rem; 
        }
        
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 2rem; 
        }
        
        .header h1 { 
            font-size: 2rem; 
            color: #1666ba; 
            font-weight: 700; 
        }
        
        .add-property-btn { 
            background-color: #1666ba; 
            color: white; 
            border: none; 
            padding: 0.75rem 1.5rem; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
            transition: background-color 0.3s ease; 
        }
        
        .add-property-btn:hover { 
            background-color: #12559e; 
        }
        
        .properties-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 2rem; 
        }
        
        .property-card { 
            background: white; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); 
            transition: transform 0.3s ease, box-shadow 0.3s ease; 
        }
        
        .property-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15); 
        }
        
        .property-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
            background-color: #f0f7ff;
        }
        
        .no-image {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e3f2fd;
            color: #1666ba;
            font-size: 3rem;
        }
        
        .property-status { 
            position: absolute; 
            top: 1rem; 
            right: 1rem; 
            padding: 0.25rem 0.75rem; 
            border-radius: 20px; 
            font-size: 0.8rem; 
            font-weight: 600; 
        }
        
        .status-vacant { 
            background-color: #4ade80; 
            color: white; 
        }
        
        .status-occupied { 
            background-color: #f87171; 
            color: white; 
        }
    
        
        .property-details { 
            padding: 1.5rem; 
        }
        
        .property-title { 
            font-size: 1.25rem; 
            font-weight: 700; 
            margin-bottom: 0.5rem; 
            color: #1666ba; 
        }
        
        .property-address { 
            color: #64748b; 
            margin-bottom: 1rem; 
            font-size: 0.9rem; 
        }
        
        .property-price { 
            font-size: 1.1rem; 
            font-weight: 700; 
            color: #1666ba; 
            margin-bottom: 1.5rem; 
        }
        
        .property-actions { 
            display: flex; 
            gap: 0.75rem; 
        }
        
        .action-btn { 
            flex: 1; 
            padding: 0.5rem; 
            border-radius: 6px; 
            border: none; 
            font-weight: 600; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 0.5rem; 
            transition: background-color 0.3s ease; 
        }
        
        .edit-btn { 
            background-color: #e0f2fe; 
            color: #0369a1; 
        }
        
        .edit-btn:hover { 
            background-color: #bae6fd; 
        }
        
        .delete-btn { 
            background-color: #fee2e2; 
            color: #b91c1c; 
        }
        
        .delete-btn:hover { 
            background-color: #fecaca; 
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            width: 100%;
            margin: 2rem 0;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .empty-state .add-property-btn {
            padding: 0.75rem 1.75rem;
            font-size: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(22, 102, 186, 0.2);
            width: auto;
            display: inline-flex;
            justify-content: center;
        }
        .empty-state:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #1666ba;
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #1e293b;
            font-weight: 600;
            max-width: 500px;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: 1rem;
            max-width: 500px;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            font-size: 1.5rem;
            color: #1666ba;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #64748b;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: #1666ba;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1e293b;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #1666ba;
        }

        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            background-color: white;
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: #1666ba;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* File upload styles */
        .file-upload {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            margin-bottom: 1rem;
        }

        .file-upload:hover {
            border-color: #1666ba;
            background-color: #f8fafc;
        }

        .file-upload.highlight {
            border-color: #1666ba;
            background-color: #ebf5ff;
        }

        .file-upload.error {
            border-color: #f87171;
            background-color: #fef2f2;
        }

        .file-upload i {
            font-size: 2rem;
            color: #1666ba;
            margin-bottom: 1rem;
        }

        .file-upload p {
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .file-upload small {
            color: #94a3b8;
        }

        .file-list-container {
            margin-top: 1rem;
            display: none;
        }

        .file-list-container.visible {
            display: block;
        }

        .file-list-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }

        .file-item {
            display: flex;
            flex-direction: column;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            transition: all 0.2s ease;
            position: relative;
        }

        .file-item:hover {
            background: #f1f5f9;
        }

        .file-preview {
            width: 100%;
            height: 150px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .preview-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .file-info {
            display: flex;
            flex-direction: column;
        }

        .file-item-name {
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.25rem;
        }

        .file-item-size {
            color: #64748b;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }

        .file-item-remove {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            color: #f87171;
            cursor: pointer;
            padding: 0.25rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .file-item-remove:hover {
            color: #ef4444;
            background: rgba(255, 255, 255, 0.9);
            transform: scale(1.1);
        }

        /* Current photos styles */
        .photos-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .photo-item {
            position: relative;
            width: 120px;
            height: 120px;
        }

        .photo-thumbnail {
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .delete-photo-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #f87171;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .delete-photo-btn:hover {
            background: #ef4444;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            border: none;
        }

        .btn-primary {
            background-color: #1666ba;
            color: white;
        }

        .btn-primary:hover {
            background-color: #12559e;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #1e293b;
        }

        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        .error-message {
            color: #f87171;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Add these styles to your existing styles */
        .publish-btn {
            background-color: #dbeafe;
            color: #1d4ed8;
        }
        
        .publish-btn:hover {
            background-color: #bfdbfe;
        }
        
        .unpublish-btn {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .unpublish-btn:hover {
            background-color: #fecaca;
        }

        @media (max-width: 1024px) { 
            .properties-grid { 
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); 
            } 
        }
        
        @media (max-width: 768px) { 
            .main-content { 
                margin-left: 0; 
                padding: 1rem; 
            } 
            
            .header { 
                flex-direction: column; 
                align-items: flex-start; 
                gap: 1rem; 
            } 
            
            .add-property-btn { 
                width: 100%; 
            } 

            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }
            
            .file-list-items {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include ('../includes/navbar/landlord-sidebar.php'); ?>

    <div class="main-content">
        <div class="header">
            <h1>My Properties</h1>
            <button class="add-property-btn" onclick="window.location.href='add-property.php'">
                <i class="fas fa-plus"></i> Add Property
            </button>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message" style="margin-bottom: 20px; background: #fee2e2; padding: 10px; border-radius: 8px; color: #b91c1c;">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($properties)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-home"></i>
                </div>
                <h3>No Properties Listed</h3>
                <p>You haven't added any properties yet. Start by adding your first property to manage rentals, tenants, and payments all in one place.</p>
                <button class="add-property-btn" onclick="window.location.href='add-property.php'">
                    <i class="fas fa-plus"></i> Add Your First Property
                </button>
            </div>
        <?php else: ?>
            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                    <div class="property-card">
                        <div class="property-image <?php echo empty($property['first_photo']) ? 'no-image' : ''; ?>" 
                             style="<?php if (!empty($property['first_photo'])) echo "background-image: url('../" . htmlspecialchars($property['first_photo']) . "')"; ?>">
                            <?php if (empty($property['first_photo'])): ?>
                                <i class="fas fa-home"></i>
                            <?php endif; ?>
                            <span class="property-status status-<?php echo strtolower($property['status']); ?>">
                                <?php echo ucfirst($property['status']); ?>
                            </span>
                            <?php if ($property['published']): ?>
                                <span class="property-status" style="background-color: #3b82f6; top: 3.5rem;">
                                    Published
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="property-details">
                            <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                            <p class="property-address"><?php echo htmlspecialchars($property['address']); ?></p>
                            <p class="property-price">₱<?php echo number_format($property['monthly_rent'], 2); ?>/month</p>
                            <div class="property-actions">
                                <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $property['property_id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($property['published']): ?>
                                    <button class="action-btn unpublish-btn" onclick="unpublishProperty(<?php echo $property['property_id']; ?>)">
                                        <i class="fas fa-eye-slash"></i> Unpublish
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn publish-btn" onclick="publishProperty(<?php echo $property['property_id']; ?>)">
                                        <i class="fas fa-eye"></i> Publish
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Property Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Property</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="editPropertyForm" action="update-property.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="property_id" id="edit_property_id">
                
                <div class="form-group">
                    <label for="edit_title">Property Title</label>
                    <input type="text" class="form-control" id="edit_title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <input type="text" class="form-control" id="edit_address" name="address" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_monthly_rent">Monthly Rent (₱)</label>
                    <input type="number" class="form-control" id="edit_monthly_rent" name="monthly_rent" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select class="form-select" id="edit_status" name="status" required>
                        <option value="vacant">vacant</option>
                        <option value="occupied">occupied</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_property_type">Property Type</label>
                    <select class="form-select" id="edit_property_type" name="property_type" required>
                        <option value="apartment">Apartment</option>
                        <option value="house">House</option>
                        <option value="condo">Condo</option>
                        <option value="studio">Studio</option>
                        <option value="commercial">Commercial</option>
                        <option value="others">Others</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Current Photos</label>
                    <div id="currentPhotos" class="photos-container">
                        <!-- Current photos will be loaded here -->
                    </div>
                    
                    <div class="file-upload" id="drop-area-edit">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload additional photos or drag and drop</p>
                        <small>JPEG, PNG, WebP (Max 5MB each)</small>
                        <input type="file" id="new_photos" name="new_photos[]" multiple 
                               accept="image/jpeg,image/png,image/webp" style="display: none;">
                    </div>
                    <div id="new_photos_preview" class="file-list-container"></div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="submit-text">Save Changes</span>
                        <span class="loading-spinner" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>

        // Add these functions for publish/unpublish
        function publishProperty(propertyId) {
            if (confirm('Are you sure you want to publish this property? It will be visible to potential tenants.')) {
                window.location.href = `properties.php?action=publish&id=${propertyId}`;
            }
        }

        function unpublishProperty(propertyId) {
            if (confirm('Are you sure you want to unpublish this property? It will no longer be visible to potential tenants.')) {
                window.location.href = `properties.php?action=unpublish&id=${propertyId}`;
            }
        }

        // Global variables
        let allNewFiles = [];
        const maxFiles = 10;
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        // Delete property functionality
        document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function() {
        const propertyId = this.getAttribute('data-id');
        if (confirm('Are you sure you want to delete this property and all its photos? This action cannot be undone.')) {
            fetch(`delete-property.php?id=${propertyId}`, {
                method: 'DELETE'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remove the property card from the UI
                    this.closest('.property-card').remove();
                    
                    // Show success message
                    alert('Property deleted successfully');
                    
                    // Check if no properties left to show empty state
                    if (document.querySelectorAll('.property-card').length === 0) {
                        window.location.reload();
                    }
                    
                    // Show warning if some files couldn't be deleted
                    if (data.warning) {
                        console.warn(data.warning);
                    }
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting property: ' + error.message);
            });
        }
    });
});

        // Modal functions
        function openEditModal(propertyId) {
            // Reset form and clear previous files
            document.getElementById('editPropertyForm').reset();
            allNewFiles = [];
            document.getElementById('new_photos_preview').innerHTML = '';
            document.getElementById('new_photos').value = '';
            
            // Fetch property details
            fetch(`get-property.php?id=${propertyId}`)
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        // Populate form fields
                        document.getElementById('edit_property_id').value = data.property_id;
                        document.getElementById('edit_title').value = data.title;
                        document.getElementById('edit_address').value = data.address;
                        document.getElementById('edit_description').value = data.description || '';
                        document.getElementById('edit_monthly_rent').value = data.monthly_rent;
                        document.getElementById('edit_status').value = data.status;
                        document.getElementById('edit_property_type').value = data.property_type;
                        
                        // Load current photos
                        loadPropertyPhotos(propertyId);
                        
                        // Initialize file upload for new photos
                        initFileUpload();
                        
                        // Show modal
                        document.getElementById('editModal').style.display = 'flex';
                    } else {
                        alert('Error loading property details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading property details');
                });
        }

        function loadPropertyPhotos(propertyId) {
            fetch(`get-property-photos.php?id=${propertyId}`)
                .then(response => response.json())
                .then(photos => {
                    const photosContainer = document.getElementById('currentPhotos');
                    photosContainer.innerHTML = '';
                    
                    if (photos.length === 0) {
                        photosContainer.innerHTML = '<p>No photos available</p>';
                        return;
                    }
                    
                    photos.forEach(photo => {
                        const photoWrapper = document.createElement('div');
                        photoWrapper.className = 'photo-item';
                        
                        const photoElement = document.createElement('div');
                        photoElement.className = 'photo-thumbnail';
                        photoElement.style.backgroundImage = `url('../${photo.file_path}')`;
                        
                        const deleteBtn = document.createElement('button');
                        deleteBtn.className = 'delete-photo-btn';
                        deleteBtn.innerHTML = '&times;';
                        deleteBtn.onclick = (e) => {
                            e.stopPropagation();
                            deletePhoto(photo.photo_id, propertyId);
                        };
                        
                        photoWrapper.appendChild(photoElement);
                        photoWrapper.appendChild(deleteBtn);
                        photosContainer.appendChild(photoWrapper);
                    });
                })
                .catch(error => {
                    console.error('Error loading photos:', error);
                    document.getElementById('currentPhotos').innerHTML = '<p>Error loading photos</p>';
                });
        }

        function deletePhoto(photoId, propertyId) {
            if (!confirm('Are you sure you want to delete this photo?')) return;
            
            fetch(`delete-photo.php?id=${photoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadPropertyPhotos(propertyId);
                    } else {
                        alert('Error deleting photo: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the photo');
                });
        }

        function initFileUpload() {
            const dropArea = document.getElementById('drop-area-edit');
            const fileInput = document.getElementById('new_photos');
            const previewContainer = document.getElementById('new_photos_preview');
            
            // Make drop area clickable
            dropArea.addEventListener('click', function(e) {
                if (e.target === dropArea || e.target.tagName === 'P' || 
                    e.target.tagName === 'SMALL' || e.target.classList.contains('fa-cloud-upload-alt')) {
                    fileInput.click();
                }
            });

            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Highlight drop area
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                dropArea.classList.add('highlight');
            }

            function unhighlight() {
                dropArea.classList.remove('highlight');
            }

            // Handle dropped files
            dropArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleNewFiles(files);
            }

            // Handle selected files
            fileInput.addEventListener('change', function() {
                handleNewFiles(this.files);
            });
            
            function handleNewFiles(newFiles) {
                // Check if adding these files would exceed max
                if (allNewFiles.length + newFiles.length > maxFiles) {
                    alert(`Maximum ${maxFiles} files allowed`);
                    return;
                }

                // Process each file
                for (let i = 0; i < newFiles.length; i++) {
                    const file = newFiles[i];
                    
                    if (!allowedTypes.includes(file.type)) {
                        alert(`Invalid file type: ${file.name} (Only JPEG/PNG/WebP allowed)`);
                        continue;
                    }
                    
                    if (file.size > maxSize) {
                        alert(`File too large: ${file.name} (Max 5MB allowed)`);
                        continue;
                    }
                    
                    allNewFiles.push(file);
                }

                renderNewFilesPreview();
            }

            function renderNewFilesPreview() {
                previewContainer.innerHTML = '';
                
                if (allNewFiles.length > 0) {
                    previewContainer.classList.add('visible');
                    
                    const listContainer = document.createElement('div');
                    listContainer.className = 'file-list-items';
                    previewContainer.appendChild(listContainer);
                    
                    for (let i = 0; i < allNewFiles.length; i++) {
                        const file = allNewFiles[i];
                        
                        const fileItem = document.createElement('div');
                        fileItem.className = 'file-item';
                        
                        const previewContainer = document.createElement('div');
                        previewContainer.className = 'file-preview';
                        
                        if (file.type.match('image.*')) {
                            const reader = new FileReader();
                            
                            reader.onload = function(e) {
                                const preview = document.createElement('img');
                                preview.src = e.target.result;
                                preview.className = 'preview-image';
                                previewContainer.appendChild(preview);
                            };
                            
                            reader.readAsDataURL(file);
                        }
                        
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'file-info';
                        
                        fileInfo.innerHTML = `
                            <div class="file-item-name" title="${file.name}">${file.name}</div>
                            <div class="file-item-size">${formatFileSize(file.size)}</div>
                        `;
                        
                        const removeBtn = document.createElement('span');
                        removeBtn.className = 'file-item-remove';
                        removeBtn.setAttribute('data-index', i);
                        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                        removeBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const index = parseInt(this.getAttribute('data-index'));
                            allNewFiles.splice(index, 1);
                            renderNewFilesPreview();
                        });
                        
                        fileItem.appendChild(previewContainer);
                        fileItem.appendChild(fileInfo);
                        fileItem.appendChild(removeBtn);
                        listContainer.appendChild(fileItem);
                    }
                } else {
                    previewContainer.classList.remove('visible');
                }
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
        }

        // Close modal when clicking outside the modal content
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeModal();
            }
        });

        // Handle form submission
        document.getElementById('editPropertyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const submitText = submitBtn.querySelector('.submit-text');
            const loadingSpinner = submitBtn.querySelector('.loading-spinner');
            
            submitText.style.display = 'none';
            loadingSpinner.style.display = 'inline-block';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            
            // Append all new files to the FormData
            allNewFiles.forEach((file, index) => {
                formData.append(`new_photos[${index}]`, file);
            });
            
            fetch('update-property.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Property updated successfully');
                    closeModal();
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating property: ' + error.message);
            })
            .finally(() => {
                submitText.style.display = 'inline-block';
                loadingSpinner.style.display = 'none';
                submitBtn.disabled = false;
            });
        });

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
            // Clear any file inputs
            document.getElementById('new_photos').value = '';
        }
    </script>
</body>
</html>