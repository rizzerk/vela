<?php
include '../connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define upload directory properly
$project_root = $_SERVER['DOCUMENT_ROOT'] . '/vela'; // Adjust if your path is different
$upload_dir = $project_root . '/uploads/properties/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        die("Failed to create upload directory. Please check permissions.");
    }
}

// Check if directory is writable
if (!is_writable($upload_dir)) {
    die("Upload directory is not writable. Please check permissions.");
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $title = trim($_POST['title'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $monthly_rent = filter_input(INPUT_POST, 'monthly_rent', FILTER_VALIDATE_FLOAT);
    
    // Basic validation
    if (empty($title)) {
        $errors['title'] = "Property title is required";
    }
    
    if (empty($address)) {
        $errors['address'] = "Address is required";
    }
    
    if (!in_array($status, ['available', 'unavailable', 'maintenance'])) {
        $errors['status'] = "Invalid status selected";
    }
    
    if ($monthly_rent === false || $monthly_rent <= 0) {
        $errors['monthly_rent'] = "Valid monthly rent is required";
    }
    
    // File upload validation
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_file_size = 5 * 1024 * 1024; // 5MB
    
    if (empty($_FILES['photos']['name'][0])) {
        $errors['photos'] = "At least one photo is required";
    } else {
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['photos']['error'][$key] !== UPLOAD_ERR_OK) {
                $errors['photos'] = "Error uploading file: " . $_FILES['photos']['name'][$key];
                continue;
            }
            
            // Check file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                $errors['photos'] = "Invalid file type: " . $_FILES['photos']['name'][$key] . 
                                   " (Only JPEG, PNG, and WebP are allowed)";
            }
            
            // Check file size
            if ($_FILES['photos']['size'][$key] > $max_file_size) {
                $errors['photos'] = "File too large: " . $_FILES['photos']['name'][$key] . 
                                   " (Max 5MB allowed)";
            }
        }
    }
    
    // If no errors, proceed with database operations
    if (empty($errors)) {
        // Escape data for database
        $title = mysqli_real_escape_string($conn, $title);
        $address = mysqli_real_escape_string($conn, $address);
        $status = mysqli_real_escape_string($conn, $status);
        $description = mysqli_real_escape_string($conn, $description);
        $monthly_rent = (float)$monthly_rent;
        
        // Insert property into database
        $query = "INSERT INTO PROPERTY (title, address, status, description, monthly_rent) 
                  VALUES ('$title', '$address', '$status', '$description', $monthly_rent)";
        
        if (mysqli_query($conn, $query)) {
            $property_id = mysqli_insert_id($conn);
            
            // Handle file uploads
            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_ext = pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '_' . md5(basename($_FILES['photos']['name'][$key])) . '.' . $file_ext;
                    $file_path = $upload_dir . $file_name;
                    $relative_path = 'uploads/properties/' . $file_name;
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $query = "INSERT INTO PROPERTY_PHOTO (property_id, file_path) 
                                  VALUES ($property_id, '$relative_path')";
                        mysqli_query($conn, $query);
                    } else {
                        $errors['photos'] = "Failed to upload file: " . $_FILES['photos']['name'][$key];
                    }
                }
            }
            
            if (empty($errors)) {
                $success = true;
                header("Location: properties.php?success=1");
                exit();
            }
        } else {
            $errors['database'] = "Database error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - VELA</title>
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
            font-size: 2rem;
            color: #1666ba;
            font-weight: 700;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #1666ba;
            text-decoration: none;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .back-btn:hover {
            text-decoration: underline;
        }

        .form-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1666ba;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #1666ba;
        }

        .form-control.error {
            border-color: #f87171;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: flex;
            gap: 1.5rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        .file-upload {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
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

        .preview-image {
            max-width: 50px;
            max-height: 50px;
            margin-right: 10px;
            object-fit: cover;
            border-radius: 4px;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 8px;
            background: #f8fafc;
            border-radius: 6px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }

        .file-item:hover {
            background: #f1f5f9;
        }

        .file-item-name {
            flex: 1;
            font-size: 0.9rem;
            margin: 0 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-item-size {
            color: #64748b;
            font-size: 0.8rem;
            margin-right: 10px;
        }

        .file-item-remove {
            color: #f87171;
            cursor: pointer;
            padding: 5px;
            transition: color 0.2s ease;
        }

        .file-item-remove:hover {
            color: #ef4444;
        }

        .submit-btn {
            background-color: #1666ba;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #12559e;
        }

        .submit-btn:disabled {
            background-color: #94a3b8;
            cursor: not-allowed;
        }

        .error-message {
            color: #f87171;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .success-message {
            color: #4ade80;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-box {
            background-color: #fef2f2;
            border-left: 4px solid #f87171;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0 4px 4px 0;
        }

        .error-box h3 {
            color: #b91c1c;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-box ul {
            list-style-position: inside;
            color: #b91c1c;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
<?php include ('../includes/navbar/landlord-sidebar.html'); ?>

<div class="main-content">
    <div class="header">
        <a href="properties.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Properties
        </a>
        <h1>Add New Property</h1>
    </div>

    <div class="form-container">
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <h3><i class="fas fa-exclamation-triangle"></i> There were errors with your submission</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Property added successfully!
            </div>
        <?php endif; ?>

        <form action="add-property.php" method="POST" enctype="multipart/form-data" id="property-form">
            <div class="form-row">
                <div class="form-group <?php echo isset($errors['title']) ? 'has-error' : ''; ?>">
                    <label for="title">Property Title</label>
                    <input type="text" id="title" name="title" class="form-control <?php echo isset($errors['title']) ? 'error' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                    <?php if (isset($errors['title'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['title']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="form-group <?php echo isset($errors['status']) ? 'has-error' : ''; ?>">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control <?php echo isset($errors['status']) ? 'error' : ''; ?>" required>
                        <option value="available" <?php echo ($_POST['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="unavailable" <?php echo ($_POST['status'] ?? '') === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        <option value="maintenance" <?php echo ($_POST['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                    </select>
                    <?php if (isset($errors['status'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['status']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group <?php echo isset($errors['address']) ? 'has-error' : ''; ?>">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" class="form-control <?php echo isset($errors['address']) ? 'error' : ''; ?>" 
                       value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required>
                <?php if (isset($errors['address'])): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['address']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group <?php echo isset($errors['monthly_rent']) ? 'has-error' : ''; ?>">
                    <label for="monthly_rent">Monthly Rent (₱)</label>
                    <input type="number" id="monthly_rent" name="monthly_rent" 
                           class="form-control <?php echo isset($errors['monthly_rent']) ? 'error' : ''; ?>" 
                           step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['monthly_rent'] ?? ''); ?>" required>
                    <?php if (isset($errors['monthly_rent'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['monthly_rent']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group <?php echo isset($errors['photos']) ? 'has-error' : ''; ?>">
            <label>Property Photos</label>
            <div class="file-upload <?php echo isset($errors['photos']) ? 'error' : ''; ?>" id="drop-area">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click to upload photos or drag and drop</p>
                <small>JPEG, PNG, WebP (Max 5MB each)</small>
                <input type="file" id="photos" name="photos[]" multiple 
                       accept="image/jpeg,image/png,image/webp" style="display: none;">
            </div>
            <div id="file-list" class="file-list-container"></div>
            <?php if (isset($errors['photos'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['photos']); ?>
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="submit-btn" id="submit-btn">
            <i class="fas fa-save"></i> Save Property
        </button>
    </form>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('photos');
    const fileList = document.getElementById('file-list');
    const form = document.getElementById('property-form');
    const submitBtn = document.getElementById('submit-btn');
    
    // Allowed file types and max size
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
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
        document.body.addEventListener(eventName, preventDefaults, false);
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
        handleFiles(files);
    }

    // Handle selected files
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        fileList.innerHTML = '';
        
        if (files.length > 0) {
            fileList.classList.add('visible');
            
            // Clear previous errors
            const photoError = document.querySelector('.form-group.photos .error-message');
            if (photoError) photoError.remove();
            dropArea.classList.remove('error');
            
            // Create file list container
            const listContainer = document.createElement('div');
            listContainer.className = 'file-list-items';
            fileList.appendChild(listContainer);
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                // Validate file
                if (!allowedTypes.includes(file.type)) {
                    showFileError(`Invalid file type: ${file.name} (Only JPEG, PNG, and WebP are allowed)`);
                    continue;
                }
                
                if (file.size > maxSize) {
                    showFileError(`File too large: ${file.name} (Max 5MB allowed)`);
                    continue;
                }
                
                // Create file item
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                
                // Create preview container
                const previewContainer = document.createElement('div');
                previewContainer.className = 'file-preview';
                
                // Create preview for images
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
                
                // File info container
                const fileInfo = document.createElement('div');
                fileInfo.className = 'file-info';
                
                fileInfo.innerHTML = `
                    <div class="file-item-name">${file.name}</div>
                    <div class="file-item-size">${formatFileSize(file.size)}</div>
                    <span class="file-item-remove" data-index="${i}">
                        <i class="fas fa-times"></i>
                    </span>
                `;
                
                fileItem.appendChild(previewContainer);
                fileItem.appendChild(fileInfo);
                listContainer.appendChild(fileItem);
            }
            
            // Add remove event listeners
            document.querySelectorAll('.file-item-remove').forEach(removeBtn => {
                removeBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const index = parseInt(this.getAttribute('data-index'));
                    removeFile(index);
                });
            });
        } else {
            fileList.classList.remove('visible');
        }
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showFileError(message) {
        dropArea.classList.add('error');
        
        const existingError = document.querySelector('.form-group.photos .error-message');
        if (existingError) {
            existingError.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            return;
        }
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        
        const formGroup = document.querySelector('.form-group.photos');
        if (formGroup) {
            formGroup.appendChild(errorDiv);
        }
    }

    function removeFile(index) {
        const files = Array.from(fileInput.files);
        files.splice(index, 1);
        
        const dataTransfer = new DataTransfer();
        files.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
        
        // Re-render file list
        handleFiles(fileInput.files);
    }

    // [Rest of the JavaScript remains the same]
});
</script>
</body>
</html>