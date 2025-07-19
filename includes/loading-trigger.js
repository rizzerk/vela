// Function to show loading screen
function showLoadingScreen() {
    const loadingHTML = `
        <div id="loadingScreen" class="loading-screen">
            <div class="loading-content">
                <img src="https://vela5.dcism.org/vela.png" alt="VELA" class="loading-logo">
                <div class="loading-spinner"></div>
                <div class="loading-text">Loading...</div>
            </div>
        </div>
        <style>
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-content {
            text-align: center;
        }
        .loading-logo {
            width: 80px;
            height: 80px;
            margin-bottom: 2rem;
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #1666ba;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        .loading-text {
            color: #64748b;
            font-size: 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
    `;
    document.body.insertAdjacentHTML('beforeend', loadingHTML);
}

// Add loading to forms and links
document.addEventListener('DOMContentLoaded', function() {
    // Registration form - check multiple selectors
    const regForm = document.querySelector('form[action*="registration"]') || 
                   document.querySelector('#signup-form') ||
                   document.querySelector('form[method="POST"]');
    if (regForm && (window.location.href.includes('registration') || regForm.id === 'signup-form')) {
        regForm.addEventListener('submit', showLoadingScreen);
    }
    
    // Application reservation form
    const appForm = document.querySelector('form[method="POST"][enctype="multipart/form-data"]');
    if (appForm && !regForm) {
        appForm.addEventListener('submit', showLoadingScreen);
    }
    
    // Maintenance request form
    const maintForm = document.querySelector('form[action*="maintenance"]');
    if (maintForm) {
        maintForm.addEventListener('submit', showLoadingScreen);
    }
    
    // Add property form
    const propForm = document.querySelector('form[action*="add-property"], form[action*="properties"], #property-form');
    if (propForm && !regForm && !appForm) {
        propForm.addEventListener('submit', showLoadingScreen);
    }
    
    // Pay dues links/forms
    const payLinks = document.querySelectorAll('a[href*="pay"], button[onclick*="pay"]');
    payLinks.forEach(link => {
        link.addEventListener('click', showLoadingScreen);
    });
    
    // Announcements form
    const announceForm = document.querySelector('form[action*="announcement"], form[method="POST"]:not(#signup-form):not(#property-form)');
    if (announceForm && !regForm && !appForm && !propForm) {
        announceForm.addEventListener('submit', showLoadingScreen);
    }
    
    // Generate bills form/button
    const billButtons = document.querySelectorAll('button[onclick*="generate"], form[action*="bill"]');
    billButtons.forEach(btn => {
        btn.addEventListener('click', showLoadingScreen);
    });
    
    // Edit bill links
    const editLinks = document.querySelectorAll('a[href*="edit"], button[onclick*="edit"]');
    editLinks.forEach(link => {
        link.addEventListener('click', showLoadingScreen);
    });
    
    // Properties page links
    const propLinks = document.querySelectorAll('a[href*="properties"]');
    propLinks.forEach(link => {
        link.addEventListener('click', showLoadingScreen);
    });
});