<?php
session_start();
include '../connection.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $monthly_rent = mysqli_real_escape_string($conn, $_POST['monthly_rent']);
    
    // Insert property into database
    $query = "INSERT INTO PROPERTY (title, address, status, description, monthly_rent) 
              VALUES ('$title', '$address', '$status', '$description', '$monthly_rent')";
    
    if (mysqli_query($conn, $query)) {
        $property_id = mysqli_insert_id($conn);
        
        // Handle photo uploads
        if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
            $upload_dir = '../uploads/properties/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name)) {
                    $file_name = time() . '_' . $key . '_' . $_FILES['photos']['name'][$key];
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $relative_path = 'uploads/properties/' . $file_name;
                        $photo_query = "INSERT INTO PROPERTY_PHOTO (property_id, file_path) 
                                       VALUES ('$property_id', '$relative_path')";
                        mysqli_query($conn, $photo_query);
                    }
                }
            }
        }
        
        header('Location: properties.php?success=1');
        exit();
    } else {
        $error = "Error adding property: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Property - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            color: #1666ba;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1.1rem;
            color: #475569;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #64748b;
        }

        .breadcrumb a {
            color: #1666ba;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .form-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
            border: 1px solid rgba(190, 218, 247, 0.3);
            max-width: 800px;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1666ba;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-input,
        .form-textarea,
        .form-select {
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #1666ba;
            box-shadow: 0 0 0 3px rgba(22, 102, 186, 0.1);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .photo-upload {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f8fafc;
        }

        .photo-upload:hover {
            border-color: #1666ba;
            background: #f1f5f9;
        }

        .photo-upload.dragover {
            border-color: #1666ba;
            background: #f1f5f9;
        }

        .photo-upload-icon {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .photo-upload-text {
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .photo-upload-hint {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .photo-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .photo-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 1;
            background: #f1f5f9;
        }

        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-remove {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(220, 38, 38, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        .btn-primary {
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
            box-shadow: 0 4px 16px rgba(22, 102, 186, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(22, 102, 186, 0.4);
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fecaca;
        }

        .required {
            color: #dc2626;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 200px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include ('../includes/navbar/landlord-sidebar.html'); ?>

    <div class="main-content">
        <div class="breadcrumb">
            <a href="properties.php">Properties</a>
            <i class="fas fa-chevron-right"></i>
            <span>Add New Property</span>
        </div>

        <div class="header">
            <h1>Add New Property</h1>
            <p>Fill in the details to add a new rental property</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-building"></i>
                        Basic Information
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Property Title <span class="required">*</span></label>
                            <input type="text" name="title" class="form-input" required 
                                   placeholder="e.g., Modern 2BR Condo Unit">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Monthly Rent <span class="required">*</span></label>
                            <input type="number" name="monthly_rent" class="form-input" required 
                                   placeholder="15000" step="0.01" min="0">
                        </div> 
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label class="form-label">Address <span class="required">*</span></label>
                            <input type="text" name="address" class="form-input" required 
                                   placeholder="Complete address of the property">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Status <span class="required">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                                <option value="maintenance">Under Maintenance</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-textarea" 
                                      placeholder="Describe the property features, amenities, and other details..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-camera"></i>
                        Property Photos
                    </h3>
                    
                    <div class="form-group">
                        <label class="form-label">Upload Photos</label>
                        <div class="photo-upload" onclick="document.getElementById('photo-input').click()">
                            <div class="photo-upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="photo-upload-text">Click to upload photos or drag and drop</div>
                            <div class="photo-upload-hint">PNG, JPG, JPEG up to 5MB each</div>
                        </div>
                        <input type="file" id="photo-input" name="photos[]" multiple 
                               accept="image/*" style="display: none;">
                        <div id="photo-preview" class="photo-preview"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="properties.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Property
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Photo upload handling
        const photoInput = document.getElementById('photo-input');
        const photoPreview = document.getElementById('photo-preview');
        const photoUpload = document.querySelector('.photo-upload');
        let selectedFiles = [];

        photoInput.addEventListener('change', handleFileSelect);

        function handleFileSelect(e) {
            const files = Array.from(e.target.files);
            selectedFiles = [...selectedFiles, ...files];
            displayPhotos();
        }

        function displayPhotos() {
            photoPreview.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const photoItem = document.createElement('div');
                    photoItem.className = 'photo-item';
                    photoItem.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="photo-remove" onclick="removePhoto(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    photoPreview.appendChild(photoItem);
                };
                reader.readAsDataURL(file);
            });
        }

        function removePhoto(index) {
            selectedFiles.splice(index, 1);
            updateFileInput();
            displayPhotos();
        }

        function updateFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            photoInput.files = dt.files;
        }

        // Drag and drop functionality
        photoUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            photoUpload.classList.add('dragover');
        });

        photoUpload.addEventListener('dragleave', () => {
            photoUpload.classList.remove('dragover');
        });

        photoUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            photoUpload.classList.remove('dragover');
            const files = Array.from(e.dataTransfer.files);
            selectedFiles = [...selectedFiles, ...files];
            updateFileInput();
            displayPhotos();
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.querySelector('[name="title"]').value.trim();
            const address = document.querySelector('[name="address"]').value.trim();
            const rent = document.querySelector('[name="monthly_rent"]').value;
            const status = document.querySelector('[name="status"]').value;

            if (!title || !address || !rent || !status) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }

            if (rent <= 0) {
                e.preventDefault();
                alert('Monthly rent must be greater than 0.');
                return;
            }
        });
    </script>