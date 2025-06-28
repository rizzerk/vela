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
    mkdir($upload_dir, 0755, true);
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['title']) || empty($_POST['address']) || empty($_POST['status']) || empty($_POST['monthly_rent'])) {
        $error = "Please fill in all required fields";
    } else {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $monthly_rent = (float)$_POST['monthly_rent'];
        
        // Insert property into database
        $query = "INSERT INTO PROPERTY (title, address, status, description, monthly_rent) 
                  VALUES ('$title', '$address', '$status', '$description', $monthly_rent)";
        
        if (mysqli_query($conn, $query)) {
            $property_id = mysqli_insert_id($conn);
            
            // Handle file uploads if any files were uploaded
            if (!empty($_FILES['photos']['name'][0])) {
                $total_files = count($_FILES['photos']['name']);
                
                for ($i = 0; $i < $total_files; $i++) {
                    // Check if file was uploaded without errors
                    if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_name = uniqid() . '_' . basename($_FILES['photos']['name'][$i]);
                        $file_path = $upload_dir . $file_name;
                        $relative_path = 'uploads/properties/' . $file_name;
                        
                        // Validate file type and size
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                        $file_type = $_FILES['photos']['type'][$i];
                        $file_size = $_FILES['photos']['size'][$i];
                        
                        if (in_array($file_type, $allowed_types)) {
                            if ($file_size <= 5 * 1024 * 1024) { // 5MB limit
                                if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $file_path)) {
                                    $query = "INSERT INTO PROPERTY_PHOTO (property_id, file_path) 
                                              VALUES ($property_id, '$relative_path')";
                                    mysqli_query($conn, $query);
                                } else {
                                    $error .= "Failed to move uploaded file: " . $_FILES['photos']['name'][$i] . "<br>";
                                }
                            } else {
                                $error .= "File too large: " . $_FILES['photos']['name'][$i] . " (max 5MB)<br>";
                            }
                        } else {
                            $error .= "Invalid file type: " . $_FILES['photos']['name'][$i] . " (only JPEG, PNG, GIF allowed)<br>";
                        }
                    } else {
                        $error .= "Upload error with file: " . $_FILES['photos']['name'][$i] . " (code: " . $_FILES['photos']['error'][$i] . ")<br>";
                    }
                }
            }
            
            if (empty($error)) {
                header("Location: properties.php?success=1");
                exit();
            }
        } else {
            $error = "Failed to add property: " . mysqli_error($conn);
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
        }

        .file-upload:hover {
            border-color: #1666ba;
            background-color: #f8fafc;
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

        .file-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
        }

        .file-item:hover {
            background: #f1f5f9;
        }

        .preview-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 12px;
            border: 1px solid #e2e8f0;
        }

        .file-item-info {
            flex: 1;
            min-width: 0;
        }

        .file-item-name {
            font-weight: 500;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-item-size {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 2px;
        }

        .file-item-remove {
            color: #ef4444;
            cursor: pointer;
            padding: 8px;
            margin-left: 8px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .file-item-remove:hover {
            background-color: #fee2e2;
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
            color: #ef4444;
            background-color: #fee2e2;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .success-message {
            color: #16a34a;
            background-color: #dcfce7;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Property added successfully!
            </div>
        <?php endif; ?>

        <form action="add-property.php" method="POST" enctype="multipart/form-data" id="property-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Property Title</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                        <option value="maintenance">Under Maintenance</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" class="form-control" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="monthly_rent">Monthly Rent (₱)</label>
                    <input type="number" id="monthly_rent" name="monthly_rent" class="form-control" step="0.01" min="0" required>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control"></textarea>
            </div>

            <div class="form-group">
                <label>Property Photos</label>
                <div class="file-upload" id="drop-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Click to upload photos or drag and drop</p>
                    <small>JPEG, PNG (Max 5MB each)</small>
                    <input type="file" id="photos" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">
                </div>
                <div id="file-list" class="file-list-container"></div>
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

    // Store our files in an array
    let filesList = [];

    // Make drop area clickable
    dropArea.addEventListener('click', function(e) {
        if (!fileInput.contains(e.target)) {
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
        dropArea.style.borderColor = '#1666ba';
        dropArea.style.backgroundColor = '#f0f7ff';
    }

    function unhighlight() {
        dropArea.style.borderColor = '#ddd';
        dropArea.style.backgroundColor = 'transparent';
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
        // Convert FileList to array and add to our filesList
        const newFiles = Array.from(files);
        filesList = [...filesList, ...newFiles];
        
        // Update the file input with all files
        updateFileInput();
        updateFileList();
    }

    function updateFileInput() {
        // Create a new DataTransfer object and add all files
        const dataTransfer = new DataTransfer();
        filesList.forEach(file => dataTransfer.items.add(file));
        
        // Update the file input with all files
        fileInput.files = dataTransfer.files;
    }

    function updateFileList() {
        fileList.innerHTML = '';
        
        if (filesList.length > 0) {
            fileList.classList.add('visible');
            
            filesList.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                
                // Create preview container
                const previewContainer = document.createElement('div');
                previewContainer.style.display = 'flex';
                previewContainer.style.alignItems = 'center';
                previewContainer.style.flex = '1';
                previewContainer.style.overflow = 'hidden';
                
                // Create file info container
                const fileInfo = document.createElement('div');
                fileInfo.className = 'file-item-info';
                
                // Create elements for file info
                const fileName = document.createElement('div');
                fileName.className = 'file-item-name';
                fileName.textContent = file.name;
                
                const fileSize = document.createElement('div');
                fileSize.className = 'file-item-size';
                fileSize.textContent = formatFileSize(file.size);
                
                // Create remove button
                const removeBtn = document.createElement('div');
                removeBtn.className = 'file-item-remove';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.title = 'Remove this file';
                removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    removeFile(index);
                });
                
                // Create preview for images
                if (file.type.match('image.*')) {
                    const preview = document.createElement('img');
                    preview.className = 'preview-image';
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    
                    previewContainer.appendChild(preview);
                }
                
                // Assemble the file item
                fileInfo.appendChild(fileName);
                fileInfo.appendChild(fileSize);
                previewContainer.appendChild(fileInfo);
                fileItem.appendChild(previewContainer);
                fileItem.appendChild(removeBtn);
                
                fileList.appendChild(fileItem);
            });
        } else {
            fileList.classList.remove('visible');
        }
    }

    function removeFile(index) {
        // Remove the file from our array
        filesList = filesList.filter((_, i) => i !== index);
        
        // Update the file input and list
        updateFileInput();
        updateFileList();
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i]);
    }

    // Form submission feedback
    form.addEventListener('submit', function(e) {
        // Ensure we have at least one file
        if (filesList.length === 0) {
            e.preventDefault();
            alert('Please upload at least one photo of the property');
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    });
});
</script>
</body>
</html>