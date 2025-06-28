<?php
session_start();
include '../connection.php';

// Check if user is logged in

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Image handling styles */
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
        
        .property-status { 
            position: absolute; 
            top: 1rem; 
            right: 1rem; 
            padding: 0.25rem 0.75rem; 
            border-radius: 20px; 
            font-size: 0.8rem; 
            font-weight: 600; 
        }
        
        .status-available { 
            background-color: #4ade80; 
            color: white; 
        }
        
        .status-unavailable { 
            background-color: #f87171; 
            color: white; 
        }
        
        .status-maintenance { 
            background-color: #fbbf24; 
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
            max-width: 600px;
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

        /* Add these styles to your existing CSS */
.photos-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 1rem;
}

.photo-thumbnail {
    width: 100px;
    height: 100px;
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

/* Preview for newly selected photos */
#new_photos_preview {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.new-photo-preview {
    width: 100px;
    height: 100px;
    background-size: cover;
    background-position: center;
    border-radius: 4px;
    border: 1px dashed #1666ba;
    position: relative;
}

.remove-new-photo {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #f87171;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
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
        }
    </style>
</head>
<body>
    <?php include ('../includes/navbar/landlord-sidebar.html'); ?>

    <div class="main-content">
        <div class="header">
            <h1>My Properties</h1>
            <button class="add-property-btn" onclick="window.location.href='add-property.php'">
                <i class="fas fa-plus"></i> Add Property
            </button>
        </div>

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
                        </div>
                        <div class="property-details">
                            <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                            <p class="property-address"><?php echo htmlspecialchars($property['address']); ?></p>
                            <p class="property-price">₱<?php echo number_format($property['monthly_rent'], 2); ?>/month</p>
                            <div class="property-actions">
                                <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $property['property_id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn delete-btn" data-id="<?php echo $property['property_id']; ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
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
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            
            <!-- Photo Management Section -->
            <!-- Inside your modal form -->
<div class="form-group">
    <label>Current Photos</label>
    <div id="currentPhotos" class="photos-container">
        <!-- Current photos will be loaded here -->
    </div>
    
    <label for="new_photos">Add New Photos</label>
    <input type="file" class="form-control" id="new_photos" name="new_photos[]" multiple accept="image/jpeg,image/png,image/webp" onchange="previewNewPhotos(this)">
    <small class="text-muted">You can select multiple photos (Max 10MB each)</small>
    
    <div id="new_photos_preview" class="photos-container"></div>
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
        // Delete property functionality
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const propertyId = this.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this property?')) {
                    fetch(`delete-property.php?id=${propertyId}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('.property-card').remove();
                            // Check if no properties left to show empty state
                            if (document.querySelectorAll('.property-card').length === 0) {
                                window.location.reload();
                            }
                        } else {
                            alert('Error deleting property: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the property');
                    });
                }
            });
        });

        // Modal functions
        function openEditModal(propertyId) {
    // Clear previous form data
    document.getElementById('editPropertyForm').reset();
    
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
                
                // Load current photos
                loadPropertyPhotos(propertyId);
                
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
                photoWrapper.style.position = 'relative';
                photoWrapper.style.margin = '5px';
                
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

        // Close modal when clicking outside the modal content
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeModal();
            }
        });

        // Handle form submission
     // Update the form submission handler to properly handle multiple files
document.getElementById('editPropertyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitBtn.disabled = true;
    
    const formData = new FormData(this);
    
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
            window.location.reload(); // Refresh to show changes
        } else {
            throw new Error(data.message || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating property: ' + error.message);
    })
    .finally(() => {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    });
});

// Add this function to handle new photo previews
function previewNewPhotos(input) {
    const previewContainer = document.getElementById('new_photos_preview');
    previewContainer.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        // Show how many files were selected
        const fileCount = document.createElement('p');
        fileCount.style.margin = '5px 0';
        fileCount.textContent = `${input.files.length} photo(s) selected`;
        previewContainer.appendChild(fileCount);
        
        // Preview up to 5 images (for performance)
        const maxPreviews = 5;
        let previewCount = 0;
        
        for (let i = 0; i < input.files.length && previewCount < maxPreviews; i++) {
            const file = input.files[i];
            
            // Check file type
            if (!file.type.match('image.*')) continue;
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'new-photo-preview';
                previewDiv.style.backgroundImage = `url(${e.target.result})`;
                
                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-new-photo';
                removeBtn.innerHTML = '&times;';
                removeBtn.onclick = function() {
                    // Remove the file from the input
                    const files = Array.from(input.files);
                    files.splice(i, 1);
                    
                    // Create new DataTransfer and set files
                    const dataTransfer = new DataTransfer();
                    files.forEach(f => dataTransfer.items.add(f));
                    input.files = dataTransfer.files;
                    
                    // Update preview
                    previewNewPhotos(input);
                };
                
                previewDiv.appendChild(removeBtn);
                previewContainer.appendChild(previewDiv);
                previewCount++;
            };
            
            reader.readAsDataURL(file);
        }
        
        if (input.files.length > maxPreviews) {
            const moreText = document.createElement('p');
            moreText.textContent = `+ ${input.files.length - maxPreviews} more`;
            moreText.style.fontSize = '0.8em';
            moreText.style.color = '#666';
            previewContainer.appendChild(moreText);
        }
    }
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
    // Clear any file inputs
    document.getElementById('new_photos').value = '';
}
    </script>
</body>
</html>