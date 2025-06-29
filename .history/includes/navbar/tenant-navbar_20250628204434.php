<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'tenant') {
    header("Location: ../../index.php");
    exit();
}
$fullName = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
?>

<style>
.tenant-navbar {
    background: #ffffff;
    padding: 1rem 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tenant-logo {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1666ba;
    text-decoration: none;
}

.tenant-nav-links {
    display: flex;
    list-style: none;
    gap: 2rem;
    align-items: center;
}

.tenant-nav-links a {
    text-decoration: none;
    color: #000000;
    font-weight: 500;
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tenant-nav-links a:hover,
.tenant-nav-links a.active {
    color: #1666ba;
    background: #deecfb;
}

.notification-icon {
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #368ce7;
    color: #ffffff;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.profile-dropdown {
    position: relative;
}

.profile-btn {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #1666ba;
    font-weight: 500;
    padding: 0.5rem;
    border-radius: 8px;
    transition: background 0.3s ease;
}

.profile-btn:hover {
    background: #deecfb;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    border: 1px solid #deecfb;
    min-width: 180px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    margin-top: 0.5rem;
}

.dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu a {
    display: block;
    padding: 0.75rem 1rem;
    color: #000000;
    text-decoration: none;
    font-size: 0.9rem;
    transition: background 0.3s ease;
    border-radius: 6px;
    margin: 0.25rem;
}

.dropdown-menu a:hover {
    background: #deecfb;
    color: #1666ba;
}

.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #1666ba;
    cursor: pointer;
}

@media (max-width: 768px) {
    .mobile-menu-btn {
        display: block;
    }

    .tenant-nav-links {
        position: fixed;
        top: 70px;
        left: 0;
        right: 0;
        background: #ffffff;
        border-bottom: 1px solid #deecfb;
        flex-direction: column;
        gap: 0;
        padding: 1rem 0;
        transform: translateY(-100%);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .tenant-nav-links.active {
        transform: translateY(0);
        opacity: 1;
        visibility: visible;
    }

    .tenant-nav-links a {
        padding: 1rem;
        text-align: center;
        width: 100%;
    }
}
</style>

<nav class="tenant-navbar">
    <a href="dashboard.php" class="tenant-logo">VELA</a>
    <ul class="tenant-nav-links">
        <li><a href="dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="notification-icon">
            <a href="../TENANT/notifications.php" class="nav-link">
                <i class="fas fa-bell"></i> Notifications
                <span class="notification-badge">3</span>
            </a>
        </li>
        <li class="profile-dropdown">
            <button class="profile-btn" id="profileBtn">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($fullName); ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </li>
    </ul>
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>
</nav>

<script>
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const navLinks = document.querySelector('.tenant-nav-links');
const profileBtn = document.getElementById('profileBtn');
const dropdownMenu = document.getElementById('dropdownMenu');

mobileMenuBtn.addEventListener('click', function() {
    navLinks.classList.toggle('active');
});

profileBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    dropdownMenu.classList.toggle('show');
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.profile-dropdown')) {
        dropdownMenu.classList.remove('show');
    }
    if (!e.target.closest('.tenant-navbar')) {
        navLinks.classList.remove('active');
    }
});

document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        navLinks.classList.remove('active');
    });
});

document.querySelectorAll('.dropdown-menu a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        
        if (href === 'profile.php') {
            window.location.href = 'profile.php';
        } else if (href === '../logout.php') {
            window.location.href = '../logout.php';
        }
        
        dropdownMenu.classList.remove('show');
    });
});
</script>