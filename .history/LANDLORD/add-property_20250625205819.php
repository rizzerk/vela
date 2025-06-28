<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../connection.php';


$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo '<pre>';
        echo 'POST data: ';
        print_r($_POST);
        echo 'FILES data: ';
        print_r($_FILES);
        echo 'Upload directory: ' . $upload_dir . "\n";
        echo 'Is upload dir writable: ' . (is_writable($upload_dir) ? 'Yes' : 'No') . "\n";
        echo '</pre>';
        exit(); // Uncomment to stop here and see debug info

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
        
        // Handle file uploads
        if (!empty($_FILES['photos']['name'][0])) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/properties/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = basename($_FILES['photos']['name'][$key]);
                    $file_path = 'uploads/properties/' . uniqid() . '_' . $file_name;
                    $full_path = __DIR__ . '/../../' . $file_path;  // Absolute server path
                    
                    if (move_uploaded_file($tmp_name, $full_path)) {
                        $query = "INSERT INTO PROPERTY_PHOTO (property_id, file_path) 
                                  VALUES ($property_id, '$file_path')";
                        mysqli_query($conn, $query);
                    } else {
                        $error = "Failed to upload file: $file_name";
                    }
                } else {
                    $error = "Upload error: " . $_FILES['photos']['error'][$key];
                }
            }
        }
        
        header("Location: properties.php?success=1");
        exit();
    } else {
        $error = "Failed to add property: " . mysqli_error($conn);
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
            transition: border-color 0.3s ease;
        }

        .file-upload:hover {
            border-color: #1666ba;
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

        #file-list {
            margin-top: 1rem;
            display: none;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            background: #f8fafc;
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }

        .file-item i {
            margin-right: 0.5rem;
            color: #64748b;
        }

        .file-item-name {
            flex: 1;
            font-size: 0.9rem;
        }

        .file-item-remove {
            color: #f87171;
            cursor: pointer;
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

        .error-message {
            color: #f87171;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .success-message {
            color: #4ade80;
            margin-top: 0.5rem;
            font-size: 0.9rem;
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
        }/* Same styles as before */
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
}

.file-item-name {
    flex: 1;
    font-size: 0.9rem;
    margin: 0 10px;
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
            <input type="file" id="photos" name="photos[]" multiple accept="image/*" style="display: none;">
        </div>
        <div id="file-list" class="file-list-container"></div>
    </div>


                <button type="submit" class="submit-btn">
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

    // Make drop area clickable
    dropArea.addEventListener('click', function(e) {
        // Prevent triggering when clicking on child elements
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
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                
                // Create preview for images
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const preview = document.createElement('img');
                        preview.src = e.target.result;
                        preview.className = 'preview-image';
                        fileItem.insertBefore(preview, fileItem.firstChild);
                    };
                    
                    reader.readAsDataURL(file);
                }
                
                fileItem.innerHTML += `
                    <span class="file-item-name">${file.name}</span>
                    <span class="file-item-size">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                    <span class="file-item-remove" data-index="${i}">
                        <i class="fas fa-times"></i>
                    </span>
                `;
                
                fileList.appendChild(fileItem);
            }
            
            // Add remove event listeners
            document.querySelectorAll('.file-item-remove').forEach(removeBtn => {
                removeBtn.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    removeFile(index);
                });
            });
        } else {
            fileList.classList.remove('visible');
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

    // Form submission feedback
    form.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    });
});
</script>