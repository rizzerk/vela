<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Landlord Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    :root {
      --primary: #4a6cf7;
      --primary-dark: #3a5af0;
      --secondary: #6c757d;
      --light: #f8f9fa;
      --dark: #343a40;
      --success: #28a745;
      --border: #dee2e6;
      --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      --radius: 12px;
    }

    body {
      background-color: #f5f7fb;
      color: #333;
      padding: 20px;
      min-height: 100vh;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 30px;
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 0;
      border-bottom: 1px solid var(--border);
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--primary);
    }

    .logo i {
      color: var(--primary-dark);
    }

    .profile-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 0;
    }

    .profile-header h1 {
      font-size: 2rem;
      color: var(--dark);
    }

    .profile-content {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
    }

    .left-panel, .right-panel {
      background: white;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 30px;
      flex: 1;
      min-width: 300px;
    }

    .panel-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--primary);
      color: var(--dark);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .panel-title i {
      color: var(--primary);
    }

    /* Profile Section */
    .profile-section {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 20px 0;
    }

    .profile-pic-container {
      position: relative;
      margin-bottom: 25px;
    }

    .profile-pic {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background: linear-gradient(135deg, #e0e7ff, #d1e0fd);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 4rem;
      color: var(--primary);
      border: 4px solid white;
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .profile-pic img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .upload-overlay {
      position: absolute;
      bottom: 0;
      right: 0;
      background: var(--primary);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      cursor: pointer;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .profile-name {
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark);
      text-align: center;
    }

    .profile-role {
      color: var(--secondary);
      margin-bottom: 25px;
      text-align: center;
    }

    .info-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 15px;
      width: 100%;
    }

    .info-item {
      display: flex;
      align-items: center;
      padding: 15px;
      background: var(--light);
      border-radius: 8px;
      transition: transform 0.2s;
    }

    .info-item:hover {
      transform: translateY(-3px);
      background: #eef2ff;
    }

    .info-icon {
      width: 40px;
      height: 40px;
      background: var(--primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      margin-right: 15px;
      flex-shrink: 0;
    }

    .info-content h4 {
      font-size: 0.9rem;
      color: var(--secondary);
      margin-bottom: 3px;
    }

    .info-content p {
      font-size: 1.1rem;
      font-weight: 500;
      color: var(--dark);
    }

    /* Contact Section */
    .contact-info {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
    }

    .contact-item {
      display: flex;
      align-items: flex-start;
      padding: 20px;
      background: var(--light);
      border-radius: 10px;
      border-left: 4px solid var(--primary);
    }

    .contact-icon {
      width: 50px;
      height: 50px;
      background: #eef2ff;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      margin-right: 15px;
      flex-shrink: 0;
      font-size: 1.5rem;
    }

    .contact-details h3 {
      font-size: 1.2rem;
      margin-bottom: 8px;
      color: var(--dark);
    }

    .contact-details p {
      color: var(--secondary);
      line-height: 1.6;
    }

    .documents-section {
      margin-top: 30px;
    }

    .documents-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 20px;
      margin-top: 15px;
    }

    .document-card {
      border: 2px solid var(--border);
      border-radius: 10px;
      height: 150px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      padding: 15px 10px;
      transition: all 0.3s;
      cursor: pointer;
      background: white;
    }

    .document-card:hover {
      border-color: var(--primary);
      transform: translateY(-5px);
      box-shadow: var(--shadow);
    }

    .document-preview {
      width: 100%;
      height: 80px;
      background: repeating-linear-gradient(
        45deg,
        #f0f4ff,
        #f0f4ff 10px,
        #e2e8ff 10px,
        #e2e8ff 20px
      );
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      font-size: 2rem;
    }

    .document-label {
      margin-top: 10px;
      font-weight: 600;
      color: var(--dark);
      text-align: center;
    }

    /* Buttons */
    .btn-group {
      display: flex;
      gap: 15px;
      margin-top: 25px;
      flex-wrap: wrap;
    }

    .btn {
      padding: 12px 25px;
      border-radius: 30px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary {
      background: var(--primary);
      color: white;
    }

    .btn-primary:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(74, 108, 247, 0.3);
    }

    .btn-outline {
      background: transparent;
      border: 2px solid var(--primary);
      color: var(--primary);
    }

    .btn-outline:hover {
      background: #eef2ff;
      transform: translateY(-2px);
    }

    .btn-danger {
      background: #ff4757;
      color: white;
    }

    .btn-danger:hover {
      background: #ff2e43;
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(255, 71, 87, 0.3);
    }

    /* Modal */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.7);
      z-index: 1000;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      display: none;
    }

    .modal-content {
      background: white;
      border-radius: var(--radius);
      width: 100%;
      max-width: 500px;
      padding: 30px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .modal-title {
      font-size: 1.8rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .modal-title i {
      color: var(--primary);
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--secondary);
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--dark);
    }

    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color 0.3s;
    }

    .form-control:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.1);
    }

    .image-upload-container {
      border: 2px dashed var(--border);
      border-radius: 8px;
      padding: 25px;
      text-align: center;
      margin-bottom: 20px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .image-upload-container:hover {
      border-color: var(--primary);
      background: #f8faff;
    }

    .image-upload-container i {
      font-size: 3rem;
      color: var(--primary);
      margin-bottom: 15px;
    }

    .image-upload-container p {
      color: var(--secondary);
      margin-bottom: 15px;
    }

    .image-upload-container .btn {
      margin-top: 10px;
    }

    .image-preview {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      margin: 0 auto 20px;
      overflow: hidden;
      display: none;
    }

    .image-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 20px;
    }

    .btn-secondary {
      padding: 10px 20px;
      background: #f8f9fa;
      border: 1px solid var(--border);
      border-radius: 30px;
      font-weight: 500;
      cursor: pointer;
    }

    .btn-modal-primary {
      padding: 10px 25px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 30px;
      font-weight: 500;
      cursor: pointer;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .profile-content {
        flex-direction: column;
      }
      
      .right-panel {
        border-left: none;
        padding-left: 0;
      }
      
      .documents-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .profile-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      
      .btn-group {
        width: 100%;
      }
    }

    footer {
      text-align: center;
      padding: 30px 0;
      color: var(--secondary);
      font-size: 0.9rem;
      margin-top: 30px;
      border-top: 1px solid var(--border);
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <div class="logo">
        <i class="fas fa-home"></i>
        <span>LandlordHub</span>
      </div>
      <div class="user-actions">
        <button class="btn btn-outline">
          <i class="fas fa-cog"></i> Settings
        </button>
      </div>
    </header>

    <div class="profile-header">
      <h1>Landlord Profile</h1>
      <div class="btn-group">
        <button class="btn btn-primary" id="editBtn">
          <i class="fas fa-edit"></i> Edit Profile
        </button>
        <button class="btn btn-danger">
          <i class="fas fa-sign-out-alt"></i> Log Out
        </button>
      </div>
    </div>

    <div class="profile-content">
      <!-- Left Panel - Account Information -->
      <div class="left-panel">
        <h2 class="panel-title"><i class="fas fa-user-circle"></i> ACCOUNT INFORMATION</h2>
        
        <div class="profile-section">
          <div class="profile-pic-container">
            <div class="profile-pic" id="profileImageDisplay">
              <i class="fas fa-building"></i>
            </div>
            <div class="upload-overlay" id="changeImageBtn">
              <i class="fas fa-camera"></i>
            </div>
          </div>
          <h2 class="profile-name" id="companyName">Smith Realty LLC</h2>
          <div class="profile-role">Professional Property Manager</div>
          
          <div class="info-grid">
            <div class="info-item">
              <div class="info-icon">
                <i class="fas fa-phone"></i>
              </div>
              <div class="info-content">
                <h4>CONTACT METHOD</h4>
                <p id="contactMethod">Email</p>
              </div>
            </div>
            
            <div class="info-item">
              <div class="info-icon">
                <i class="fas fa-clock"></i>
              </div>
              <div class="info-content">
                <h4>CONTACT HOURS</h4>
                <p id="contactHours">Weekdays 8am-6pm</p>
              </div>
            </div>
            
            <div class="info-item">
              <div class="info-icon">
                <i class="fas fa-money-bill-wave"></i>
              </div>
              <div class="info-content">
                <h4>RENT PAYMENT METHOD</h4>
                <p id="paymentMethod">Online Portal</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Right Panel - Contact & Documents -->
      <div class="right-panel">
        <h2 class="panel-title"><i class="fas fa-address-book"></i> LANDLORD CONTACT</h2>
        
        <div class="contact-info">
          <div class="contact-item">
            <div class="contact-icon">
              <i class="fas fa-envelope"></i>
            </div>
            <div class="contact-details">
              <h3>Primary Contact</h3>
              <p id="contactInfo">Email: contact@smithrealty.com<br>Phone: (555) 123-4567</p>
            </div>
          </div>
          
          <div class="contact-item">
            <div class="contact-icon">
              <i class="fas fa-info-circle"></i>
            </div>
            <div class="contact-details">
              <h3>Availability</h3>
              <p>Preferred contact method is email during business hours. For urgent matters outside business hours, please text the emergency contact number provided in your lease agreement.</p>
            </div>
          </div>
        </div>
        
        <div class="documents-section">
          <h2 class="panel-title"><i class="fas fa-file-alt"></i> DOCUMENTS</h2>
          <div class="documents-grid">
            <div class="document-card">
              <div class="document-preview">
                <i class="fas fa-id-card"></i>
              </div>
              <div class="document-label">Business License</div>
            </div>
            
            <div class="document-card">
              <div class="document-preview">
                <i class="fas fa-file-contract"></i>
              </div>
              <div class="document-label">Lease Agreement</div>
            </div>
          </div>
        </div>
        
        <div class="btn-group">
          <button class="btn btn-outline">
            <i class="fas fa-download"></i> Download Documents
          </button>
          <button class="btn btn-outline">
            <i class="fas fa-upload"></i> Upload New
          </button>
        </div>
      </div>
    </div>
    
    <footer>
      <p>&copy; 2023 LandlordHub - Property Management Portal. All rights reserved.</p>
      <p>Your trusted platform for property management</p>
    </footer>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal" id="editModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title"><i class="fas fa-edit"></i> Edit Profile</h2>
        <button class="close-modal" id="closeModal">&times;</button>
      </div>
      
      <form id="profileForm">
        <div class="form-group">
          <label for="editCompanyName">Company/Full Name</label>
          <input type="text" id="editCompanyName" class="form-control" value="Smith Realty LLC">
        </div>
        
        <div class="form-group">
          <label for="editContactMethod">Contact Method</label>
          <select id="editContactMethod" class="form-control">
            <option value="Email">Email</option>
            <option value="Phone">Phone</option>
            <option value="Text">Text</option>
            <option value="Portal">Portal</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="editContactHours">Contact Hours</label>
          <input type="text" id="editContactHours" class="form-control" value="Weekdays 8am-6pm">
        </div>
        
        <div class="form-group">
          <label for="editPaymentMethod">Payment Method</label>
          <input type="text" id="editPaymentMethod" class="form-control" value="Online Portal">
        </div>
        
        <div class="form-group">
          <label>Profile Image</label>
          <div class="image-upload-container" id="imageUploadContainer">
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Click to upload a profile image</p>
            <p class="small">JPG or PNG, max 2MB</p>
            <button type="button" class="btn btn-outline">Select Image</button>
            <input type="file" id="imageUpload" accept="image/*" style="display: none;">
          </div>
          <div class="image-preview" id="imagePreview">
            <img src="" alt="Preview">
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn-secondary" id="cancelEdit">Cancel</button>
          <button type="button" class="btn-modal-primary" id="saveChanges">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // DOM Elements
    const editBtn = document.getElementById('editBtn');
    const editModal = document.getElementById('editModal');
    const closeModal = document.getElementById('closeModal');
    const cancelEdit = document.getElementById('cancelEdit');
    const saveChanges = document.getElementById('saveChanges');
    const imageUploadContainer = document.getElementById('imageUploadContainer');
    const imageUpload = document.getElementById('imageUpload');
    const imagePreview = document.getElementById('imagePreview');
    const profileImageDisplay = document.getElementById('profileImageDisplay');
    const changeImageBtn = document.getElementById('changeImageBtn');
    
    // Profile display elements
    const companyName = document.getElementById('companyName');
    const contactMethod = document.getElementById('contactMethod');
    const contactHours = document.getElementById('contactHours');
    const paymentMethod = document.getElementById('paymentMethod');
    
    // Edit form elements
    const editCompanyName = document.getElementById('editCompanyName');
    const editContactMethod = document.getElementById('editContactMethod');
    const editContactHours = document.getElementById('editContactHours');
    const editPaymentMethod = document.getElementById('editPaymentMethod');
    
    // Initialize modal with current values
    function initModal() {
      editCompanyName.value = companyName.textContent;
      editContactMethod.value = contactMethod.textContent;
      editContactHours.value = contactHours.textContent;
      editPaymentMethod.value = paymentMethod.textContent;
      
      // Reset image preview
      imagePreview.style.display = 'none';
    }
    
    // Event listeners
    editBtn.addEventListener('click', () => {
      initModal();
      editModal.style.display = 'flex';
    });
    
    closeModal.addEventListener('click', () => {
      editModal.style.display = 'none';
    });
    
    cancelEdit.addEventListener('click', () => {
      editModal.style.display = 'none';
    });
    
    saveChanges.addEventListener('click', () => {
      // Update profile with new values
      companyName.textContent = editCompanyName.value;
      contactMethod.textContent = editContactMethod.value;
      contactHours.textContent = editContactHours.value;
      paymentMethod.textContent = editPaymentMethod.value;
      
      // If image preview is visible, set it as profile image
      if (imagePreview.style.display === 'block') {
        const previewImg = imagePreview.querySelector('img');
        profileImageDisplay.innerHTML = '';
        const newImg = document.createElement('img');
        newImg.src = previewImg.src;
        profileImageDisplay.appendChild(newImg);
        
        // Here you would typically upload the image to your server
        // and save the path to the database
      }
      
      // Show success message
      alert('Profile updated successfully!');
      
      // Close modal
      editModal.style.display = 'none';
    });
    
    // Image upload functionality
    imageUploadContainer.addEventListener('click', () => {
      imageUpload.click();
    });
    
    changeImageBtn.addEventListener('click', () => {
      imageUpload.click();
    });
    
    imageUpload.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        if (file.size > 2 * 1024 * 1024) {
          alert('File size exceeds 2MB limit. Please choose a smaller image.');
          return;
        }
        
        const reader = new FileReader();
        reader.onload = function(event) {
          const img = imagePreview.querySelector('img');
          img.src = event.target.result;
          imagePreview.style.display = 'block';
          imageUploadContainer.style.display = 'none';
        };
        reader.readAsDataURL(file);
      }
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
      if (e.target === editModal) {
        editModal.style.display = 'none';
      }
    });
  </script>
</body>
</html>