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
      border-radius: 12px;
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

    .profile-pic {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      background: linear-gradient(135deg, #e0e7ff, #d1e0fd);
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 4rem;
      color: var(--primary);
      border: 4px solid white;
      box-shadow: var(--shadow);
    }

    .profile-name {
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark);
    }

    .profile-role {
      color: var(--secondary);
      margin-bottom: 25px;
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
        <span>RentalEase</span>
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
          <div class="profile-pic">
            <i class="fas fa-building"></i>
          </div>
          <h2 class="profile-name" id="companyName">Smith Realty LLC</h2>
          <div class="profile-role">Professional Landlord & Property Manager</div>
          
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
            
            <div class="document-card">
              <div class="document-preview">
                <i class="fas fa-receipt"></i>
              </div>
              <div class="document-label">Payment Records</div>
            </div>
            
            <div class="document-card">
              <div class="document-preview">
                <i class="fas fa-shield-alt"></i>
              </div>
              <div class="document-label">Insurance</div>
            </div>
          </div>
        </div>
        
        <div class="btn-group">
          <button class="btn btn-outline">
            <i class="fas fa-download"></i> Download Documents
          </button>
        </div>
      </div>
    </div>
    
    <footer>
      <p>&copy; 2023 RentalEase - Landlord Portal. All rights reserved.</p>
      <p>Your trusted platform for property management</p>
    </footer>
  </div>

  <!-- Edit Profile Modal -->
  <div class="modal" id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; padding: 20px;">
    <div style="background: white; border-radius: 15px; width: 100%; max-width: 500px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
      <h2 style="font-size: 1.8rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-edit" style="color: #4a6cf7;"></i> Edit Profile
      </h2>
      
      <form id="profileForm" style="display: grid; gap: 15px;">
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <label style="font-weight: 500; color: #343a40;">Company/Full Name</label>
          <input type="text" id="editCompanyName" value="Smith Realty LLC" style="padding: 12px 15px; border: 1px solid #dee2e6; border-radius: 8px; font-size: 1rem;">
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <label style="font-weight: 500; color: #343a40;">Contact Method</label>
          <select id="editContactMethod" style="padding: 12px 15px; border: 1px solid #dee2e6; border-radius: 8px; font-size: 1rem;">
            <option value="Email">Email</option>
            <option value="Phone">Phone</option>
            <option value="Text">Text</option>
            <option value="Portal">Portal</option>
          </select>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <label style="font-weight: 500; color: #343a40;">Contact Hours</label>
          <input type="text" id="editContactHours" value="Weekdays 8am-6pm" style="padding: 12px 15px; border: 1px solid #dee2e6; border-radius: 8px; font-size: 1rem;">
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <label style="font-weight: 500; color: #343a40;">Payment Method</label>
          <input type="text" id="editPaymentMethod" value="Online Portal" style="padding: 12px 15px; border: 1px solid #dee2e6; border-radius: 8px; font-size: 1rem;">
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 20px;">
          <button type="button" id="cancelEdit" style="padding: 10px 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 30px; font-weight: 500; cursor: pointer;">Cancel</button>
          <button type="button" id="saveChanges" style="padding: 10px 25px; background: #4a6cf7; color: white; border: none; border-radius: 30px; font-weight: 500; cursor: pointer;">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // DOM Elements
    const editBtn = document.getElementById('editBtn');
    const editModal = document.getElementById('editModal');
    const cancelEdit = document.getElementById('cancelEdit');
    const saveChanges = document.getElementById('saveChanges');
    
    // Form fields
    const companyName = document.getElementById('companyName');
    const contactMethod = document.getElementById('contactMethod');
    const contactHours = document.getElementById('contactHours');
    const paymentMethod = document.getElementById('paymentMethod');
    
    // Edit form fields
    const editCompanyName = document.getElementById('editCompanyName');
    const editContactMethod = document.getElementById('editContactMethod');
    const editContactHours = document.getElementById('editContactHours');
    const editPaymentMethod = document.getElementById('editPaymentMethod');
    
    // Event listeners
    editBtn.addEventListener('click', () => {
      editModal.style.display = 'flex';
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
      
      // Show success message
      alert('Profile updated successfully!');
      
      // Close modal
      editModal.style.display = 'none';
    });
    
    // Close modal when clicking outside the form
    window.addEventListener('click', (e) => {
      if (e.target === editModal) {
        editModal.style.display = 'none';
      }
    });
  </script>
</body>
</html>