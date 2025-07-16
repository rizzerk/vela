<?php
session_start();
require_once '../connection.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'landlord' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../index.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO USERS (name, email, phone, password, role, is_active) 
                  VALUES ('$name', '$email', '$phone', '$hashed_password', '$role', 1)";
        mysqli_query($conn, $query);
        
        // Set success message
        $_SESSION['message'] = "User added successfully";
        $_SESSION['message_type'] = "success";
        
    } elseif (isset($_POST['update_user'])) {
        // Update existing user
        $user_id = intval($_POST['user_id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        
        $query = "UPDATE USERS SET 
                  name = '$name',
                  email = '$email',
                  phone = '$phone',
                  role = '$role'
                  WHERE user_id = $user_id";
        mysqli_query($conn, $query);
        
        // Set success message
        $_SESSION['message'] = "User updated successfully";
        $_SESSION['message_type'] = "success";
        
    } elseif (isset($_POST['toggle_status'])) {
        // Toggle user status
        $user_id = intval($_POST['user_id']);
        $current_status = intval($_POST['current_status']);
        $new_status = $current_status ? 0 : 1;
        
        $query = "UPDATE USERS SET is_active = $new_status WHERE user_id = $user_id";
        mysqli_query($conn, $query);
        
        // Set success message
        $_SESSION['message'] = "User status updated";
        $_SESSION['message_type'] = "success";
    }
    
    // Redirect to prevent form resubmission
    header("Location: users.php");
    exit;
}

// Fetch all users
$query = "SELECT * FROM USERS ORDER BY is_active DESC, name ASC";
$result = mysqli_query($conn, $query);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Management - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f6f6f6;
            margin: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        h1 {
            font-size: 2rem;
            color: #1666ba;
            margin-bottom: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(22, 102, 186, 0.1);
        }

        th,
        td {
            padding: 0.8rem;
            text-align: left;
            font-size: 0.9rem;
        }

        th {
            background-color: #eef4fb;
            color: #1666ba;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        /* Highlight inactive users */
        tr.inactive-user {
            background-color: #fee2e2 !important;
            border-left: 4px solid #ef4444;
        }

        tr.inactive-user:nth-child(even) {
            background-color: #fee2e2 !important;
        }

        .status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }

        .active {
            background-color: #4ade80;
            color: #065f46;
        }

        .inactive {
            background-color: #f87171;
            color: #7f1d1d;
        }

        .role {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            text-align: center;
            display: inline-block;
        }

        .role.tenant {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .role.landlord {
            background-color: #fef3c7;
            color: #d97706;
        }

        .role.general_user {
            background-color: #e5e7eb;
            color: #374151;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .edit-btn {
            background-color: #3b82f6;
            color: white;
        }

        .edit-btn:hover {
            background-color: #2563eb;
        }

        .toggle-btn {
            background-color: #10b981;
            color: white;
        }

        .toggle-btn.inactive {
            background-color: #ef4444;
        }

        .toggle-btn:hover {
            opacity: 0.9;
        }

        .no-users {
            padding: 2rem;
            text-align: center;
            color: #64748b;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 8px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .btn-primary {
            background-color: #1666ba;
            color: white;
        }

        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            z-index: 1001;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out;
        }

        .notification.success {
            background-color: #10b981;
            color: white;
        }

        .notification.error {
            background-color: #ef4444;
            color: white;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        /* Responsive table */
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            table {
                font-size: 0.85rem;
            }

            th,
            td {
                padding: 0.6rem;
            }
        }
    </style>
</head>

<body>
    <?php include('../includes/navbar/landlord-sidebar.html'); ?>

    <div class="main-content">
        <h1>User Management</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="notification <?= $_SESSION['message_type'] ?>" id="notification">
                <?= $_SESSION['message'] ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <button class="btn btn-primary" onclick="openAddModal()" style="margin-bottom: 1.5rem;">
            <i class="fas fa-plus"></i> Add New User
        </button>

        <?php if (empty($users)): ?>
            <div class="no-users">
                <i class="fas fa-users" style="font-size:2rem;"></i>
                <p>No users found.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="<?= !$user['is_active'] ? 'inactive-user' : '' ?>">
                            <td><?= htmlspecialchars($user['user_id']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td>
                                <span class="role <?= strtolower($user['role']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status <?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn edit-btn" onclick="openEditModal(
                                        <?= $user['user_id'] ?>, 
                                        '<?= htmlspecialchars($user['name']) ?>', 
                                        '<?= htmlspecialchars($user['email']) ?>', 
                                        '<?= htmlspecialchars($user['phone']) ?>', 
                                        '<?= htmlspecialchars($user['role']) ?>'
                                    )">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $user['is_active'] ?>">
                                        <button type="submit" name="toggle_status" class="action-btn toggle-btn <?= !$user['is_active'] ? 'inactive' : '' ?>">
                                            <i class="fas <?= $user['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                            <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Add New User</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="add_name">Name</label>
                    <input type="text" id="add_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="add_email">Email</label>
                    <input type="email" id="add_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="add_phone">Phone</label>
                    <input type="text" id="add_phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="add_password">Password</label>
                    <input type="password" id="add_password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="add_role">Role</label>
                    <select id="add_role" name="role" required>
                        <option value="tenant">Tenant</option>
                        <option value="landlord">Landlord</option>
                        <option value="general_user">General User</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit User</h2>
            <form method="POST">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="form-group">
                    <label for="edit_name">Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_phone">Phone</label>
                    <input type="text" id="edit_phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="edit_role">Role</label>
                    <select id="edit_role" name="role" required>
                        <option value="tenant">Tenant</option>
                        <option value="landlord">Landlord</option>
                        <option value="general_user">General User</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Open modals
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function openEditModal(userId, name, email, phone, role) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_role').value = role;
            document.getElementById('editModal').style.display = 'block';
        }

        // Close modals
        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                closeModal();
            }
        }

        // Auto-hide notification after 3 seconds
        const notification = document.getElementById('notification');
        if (notification) {
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    </script>
</body>

</html>