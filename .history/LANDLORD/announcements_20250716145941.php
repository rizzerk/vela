<?php
session_start();
require_once '../connection.php';
require_once "../includes/auth/landlord_auth.php";


$landlord_id = $_SESSION['user_id'] ?? 1;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'add_announcement') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $priority = $_POST['priority'] ?? 'low';
        
        $insert_query = "INSERT INTO ANNOUNCEMENT (title, content, visible_to, priority, created_by, created_at) 
                         VALUES (?, ?, 'tenant', ?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param("sssi", $title, $content, $priority, $landlord_id);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: announcements.php?success=added");
        exit();
    }

    if (($_POST['action'] ?? '') === 'edit_announcement') {
        $id = $_POST['announcement_id'] ?? 0;
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $priority = $_POST['priority'] ?? 'low';
        
        if ($id > 0) {
            $update_query = "UPDATE ANNOUNCEMENT SET title = ?, content = ?, priority = ? WHERE announcement_id = ?";
            $stmt = $conn->prepare($update_query);
            if ($stmt) {
                $stmt->bind_param("sssi", $title, $content, $priority, $id);
                $stmt->execute();
                $stmt->close();
            }
        }
        header("Location: announcements.php?success=updated");
        exit();
    }

    if (($_POST['action'] ?? '') === 'delete_announcement') {
        $id = $_POST['announcement_id'] ?? 0;
        if ($id > 0) {
            $delete_query = "DELETE FROM ANNOUNCEMENT WHERE announcement_id = ?";
            $stmt = $conn->prepare($delete_query);
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
        }
        header("Location: announcements.php?success=deleted");
        exit();
    }
}

$announcement_query = "SELECT announcement_id, title, content, visible_to, priority, created_at 
                      FROM ANNOUNCEMENT 
                      ORDER BY created_at DESC";
$announcements = $conn->query($announcement_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - VELA</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            color: #1666ba;
            font-weight: 800;
        }

        .btn-primary {
            background: #1666ba;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: #0d4a8a;
        }

        .announcements-grid {
            display: grid;
            gap: 1.5rem;
        }

        .announcement-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .announcement-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .announcement-date {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }

        .announcement-badges {
            display: flex;
            gap: 0.5rem;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-visible-all {
            background: #e0f2fe;
            color: #0277bd;
        }

        .badge-visible-tenant {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .badge-priority-high {
            background: #ffebee;
            color: #c62828;
        }

        .badge-priority-medium {
            background: #fff3e0;
            color: #ef6c00;
        }

        .badge-priority-low {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .announcement-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-edit, .btn-delete {
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .btn-edit {
            background: #1666ba;
        }

        .btn-delete {
            background: #ef4444;
        }

        .announcement-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
        }

        .announcement-content {
            color: #475569;
            line-height: 1.6;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 1.5rem;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1666ba;
            font-weight: 600;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            margin-left: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #bedaf7;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            z-index: 2000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.error {
            background: #ef4444;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .announcement-header {
                flex-direction: column;
                gap: 1rem;
            }

            .announcement-actions {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <?php include ('../includes/navbar/landlord-sidebar.html'); ?>

    <div class="main-content">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="dashboard.php" style="color: #1666ba; font-size: 1.5rem; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1>Announcements</h1>
            </div>
            <button class="btn-primary" onclick="openModal()">
                <i class="fas fa-plus"></i> Add Announcement
            </button>
        </div>

        <div class="announcements-grid">
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <i class="fas fa-bullhorn"></i>
                    <h3>No announcements yet</h3>
                    <p>Create your first announcement to get started.</p>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <div class="announcement-meta">
                                <div class="announcement-date">
                                    <?= date('F j, Y \a\t g:i A', strtotime($announcement['created_at'])) ?>
                                </div>
                                <div class="announcement-badges">
                                    <span class="badge badge-priority-<?= $announcement['priority'] ?>">
                                        <?= ucfirst($announcement['priority']) ?> Priority
                                    </span>
                                </div>
                            </div>
                            <div class="announcement-actions">
                                <button class="btn-edit" onclick="editAnnouncement(<?= $announcement['announcement_id'] ?>, <?= htmlspecialchars(json_encode($announcement['title']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($announcement['content']), ENT_QUOTES) ?>, '<?= $announcement['visible_to'] ?>', '<?= $announcement['priority'] ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-delete" onclick="deleteAnnouncement(<?= $announcement['announcement_id'] ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <div class="announcement-title"><?= htmlspecialchars($announcement['title']) ?></div>
                        <div class="announcement-content"><?= nl2br(htmlspecialchars($announcement['content'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Announcement Modal -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle" style="color: #1666ba; margin-bottom: 1.5rem;">Add New Announcement</h3>
            <form method="POST" id="announcementForm">
                <input type="hidden" name="action" value="add_announcement" id="formAction">
                <input type="hidden" name="announcement_id" id="announcementId">
                
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" id="announcementTitle" required>
                </div>
                
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" id="announcementContent" required></textarea>
                </div>
                

                
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" id="announcementPriority" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary" id="submitBtn">Create Announcement</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3 style="color: #ef4444; margin-bottom: 1.5rem;">Delete Announcement</h3>
            <p style="margin-bottom: 1.5rem;">Are you sure you want to delete this announcement? This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete_announcement">
                <input type="hidden" name="announcement_id" id="deleteAnnouncementId">
                <button type="submit" style="background: #ef4444;" class="btn-primary">Delete</button>
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script>
        function openModal() {
            document.getElementById('modalTitle').textContent = 'Add New Announcement';
            document.getElementById('formAction').value = 'add_announcement';
            document.getElementById('submitBtn').textContent = 'Create Announcement';
            document.getElementById('announcementId').value = '';
            document.getElementById('announcementTitle').value = '';
            document.getElementById('announcementContent').value = '';

            document.getElementById('announcementPriority').value = 'low';
            document.getElementById('announcementModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('announcementModal').style.display = 'none';
        }
        
        function editAnnouncement(id, title, content, visibleTo, priority) {
            document.getElementById('modalTitle').textContent = 'Edit Announcement';
            document.getElementById('formAction').value = 'edit_announcement';
            document.getElementById('submitBtn').textContent = 'Update Announcement';
            document.getElementById('announcementId').value = id;
            document.getElementById('announcementTitle').value = title;
            document.getElementById('announcementContent').value = content;
            document.getElementById('announcementPriority').value = priority;
            document.getElementById('announcementModal').style.display = 'block';
        }
        
        function deleteAnnouncement(id) {
            document.getElementById('deleteAnnouncementId').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + (type === 'error' ? 'error' : '');
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        window.onclick = function(event) {
            const modal = document.getElementById('announcementModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === 'added') {
            showToast('Announcement created successfully!');
        } else if (urlParams.get('success') === 'updated') {
            showToast('Announcement updated successfully!');
        } else if (urlParams.get('success') === 'deleted') {
            showToast('Announcement deleted successfully!');
        }
    </script>
</body>
</html>