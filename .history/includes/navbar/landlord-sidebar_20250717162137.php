<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>VELA</h2>
    </div>
    <ul class="nav-menu">
        <!-- Core Management -->
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="properties.php" class="nav-link <?= $current_page == 'properties.php' ? 'active' : '' ?>">
                <i class="fas fa-building"></i>
                Properties
            </a>
        </li>
        <li class="nav-item">
            <a href="bills.php" class="nav-link <?= $current_page == 'bills.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                Bills
            </a>
        </li>
        <li class="nav-item">
            <a href="maintenance-req.php" class="nav-link <?= $current_page == 'maintenance-req.php' ? 'active' : '' ?>">
                <i class="fas fa-tools"></i>
                Maintenance
            </a>
        </li>
        
        <div class="nav-divider"></div>
        
        <!-- Tenant Management -->
        <li class="nav-item">
            <a href="applications.php" class="nav-link <?= $current_page == 'applications.php' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i>
                Applications
            </a>
        </li>
        <li class="nav-item">
            <a href="tenant-payments.php" class="nav-link <?= $current_page == 'tenant-payments.php' ? 'active' : '' ?>">
                <i class="fas fa-receipt"></i>
                Payments
            </a>
        </li>
        <li class="nav-item">
            <a href="tenant-history.php" class="nav-link <?= $current_page == 'tenant-history.php' ? 'active' : '' ?>">
                <i class="fas fa-history"></i>
                Tenant History
            </a>
        </li>
        
        <div class="nav-divider"></div>
        
        <!-- Account -->
        <li class="nav-item">
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user"></i>
                Profile
            </a>
        </li> 
    </ul>
</div>

<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    background: #1666ba;
    padding: 2rem 0;
    z-index: 1000;
}

.sidebar-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 0 1rem;
}

.sidebar-header h2 {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
}

.nav-menu {
    list-style: none;
}

.nav-item {
    margin-bottom: 0.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.nav-link:hover,
.nav-link.active {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.nav-link i {
    margin-right: 0.75rem;
    width: 16px;
}

.nav-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 1rem 1.5rem;
}

.main-content {
    margin-left: 250px;
}

@media (max-width: 1024px) {
    .sidebar {
        width: 200px;
    }
    
    .main-content {
        margin-left: 200px;
    }
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .main-content {
        margin-left: 0;
    }
}
</style>