<?php
session_start();
require_once "../connection.php";

$userName = $_SESSION['name'] ?? 'Tenant';
$userId = $_SESSION['user_id'];

$sort_by = $_GET['sort'] ?? 'date';

$announcements = [];
if ($sort_by === 'priority') {
    $announcementQuery = "SELECT title, content, priority, created_at FROM ANNOUNCEMENT 
                         WHERE visible_to IN ('tenant', 'all') 
                         ORDER BY FIELD(priority, 'high', 'medium', 'low'), created_at DESC";
} else {
    $announcementQuery = "SELECT title, content, priority, created_at FROM ANNOUNCEMENT 
                         WHERE visible_to IN ('tenant', 'all') 
                         ORDER BY created_at DESC";
}

$announcementStmt = $conn->prepare($announcementQuery);
$announcementStmt->execute();
$announcementResult = $announcementStmt->get_result();

while ($row = $announcementResult->fetch_assoc()) {
    $announcements[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notices - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
            padding-top: 90px;
        }

        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem;
        }

        .header-section {
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.75rem;
            color: #1666ba;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
        }

        .back-btn {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid #1666ba;
            background: #ffffff;
            color: #1666ba;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: #1666ba;
            color: white;
        }

        .notice-item {
            background: #ffffff;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .notice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .notice-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-high {
            background: #fecaca;
            color: #dc2626;
        }

        .priority-medium {
            background: #fef3c7;
            color: #d97706;
        }

        .priority-low {
            background: #dbeafe;
            color: #1e40af;
        }

        .notice-content {
            font-size: 1rem;
            line-height: 1.6;
            color: #64748b;
            margin-bottom: 1rem;
        }

        .notice-date {
            font-size: 0.9rem;
            color: #94a3b8;
            font-weight: 500;
        }

        .no-notices {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 3rem;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar/tenant-navbar.php'; ?>

    <div class="content-wrapper">
        <div class="header-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h1 class="section-title">All Notices</h1>
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <label for="sort" style="color: #64748b; font-weight: 500;">Sort by:</label>
                <select id="sort" onchange="window.location.href='?sort=' + this.value" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white;">
                    <option value="date" <?= $sort_by === 'date' ? 'selected' : '' ?>>Date (Newest First)</option>
                    <option value="priority" <?= $sort_by === 'priority' ? 'selected' : '' ?>>Priority (High to Low)</option>
                </select>
            </div>
        </div>

        <?php if (!empty($announcements)): ?>
            <?php foreach ($announcements as $announcement): ?>
                <div class="notice-item">
                    <div class="notice-header">
                        <h2 class="notice-title"><?= htmlspecialchars($announcement['title']) ?></h2>
                        <span class="priority-badge priority-<?= strtolower($announcement['priority']) ?>">
                            <?= ucfirst($announcement['priority']) ?>
                        </span>
                    </div>
                    <div class="notice-content">
                        <?= nl2br(htmlspecialchars($announcement['content'])) ?>
                    </div>
                    <div class="notice-date">
                        Posted: <?= date('M d, Y \a\t g:i A', strtotime($announcement['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-notices">
                <i class="fas fa-bell-slash" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                <h3>No Notices Available</h3>
                <p>There are currently no announcements to display.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>