<?php
session_start();
require_once '../connection.php';

$landlord_id = $_SESSION['user_id'] ?? 1;
$properties = [];
$total_properties = 0;
$total_vacant = 0;
$total_occupied = 0;

// Fetch all properties since there's only one landlord
$property_query = "SELECT property_type, COUNT(*) as count, 
                         SUM(CASE WHEN status = 'vacant' THEN 1 ELSE 0 END) as vacant,
                         SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied
                  FROM PROPERTY 
                  GROUP BY property_type";
$result = $conn->query($property_query);
if ($result) {
    $properties = $result->fetch_all(MYSQLI_ASSOC);
    $total_properties = array_sum(array_column($properties, 'count'));
    $total_vacant = array_sum(array_column($properties, 'vacant'));
    $total_occupied = array_sum(array_column($properties, 'occupied'));
}

// Handle announcement creation
if ($_POST['action'] ?? '' === 'add_announcement') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $visible_to = $_POST['visible_to'];
    $priority = $_POST['priority'];
    
    $insert_query = "INSERT INTO ANNOUNCEMENT (title, content, visible_to, priority, created_by, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssssi", $title, $content, $visible_to, $priority, $landlord_id);
    $stmt->execute();
}

// Fetch announcements
$announcement_query = "SELECT title, content, created_at 
                      FROM ANNOUNCEMENT 
                      WHERE visible_to IN ('landlord', 'all') 
                      ORDER BY created_at DESC 
                      LIMIT 3";
$announcements = $conn->query($announcement_query)->fetch_all(MYSQLI_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Dashboard - VELA</title>
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
            text-align: left;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 3rem;
            color: #1666ba;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1.2rem;
            color: #475569;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
            border: 1px solid rgba(190, 218, 247, 0.3);
        }

        .card-title {
            font-size: 1.1rem;
            color: #1666ba;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .metric:last-child {
            margin-bottom: 0;
        }

        .metric.total {
            background: #1666ba;
            color: white;
            font-weight: 600;
        }

        .metric-label {
            font-size: 0.95rem;
            color: #64748b;
        }

        .metric.total .metric-label {
            color: white;
        }

        .metric-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1666ba;
        }

        .metric.total .metric-value {
            color: white;
        }

        .status-card {
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
        }

        .status-card .card-title {
            color: white;
        }

        .status-metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .status-metric:last-child {
            margin-bottom: 0;
        }

        .status-label {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .status-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
        }

        .announcements-card {
            grid-column: span 3;
            background: linear-gradient(135deg, #7ab3ef, #368ce7);
            color: white;
        }

        .announcements-card .card-title {
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .add-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .add-btn:hover {
            background: rgba(255,255,255,0.3);
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
            margin: 5% auto;
            padding: 2rem;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
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
            padding: 0.75rem;
            border: 1px solid #bedaf7;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .btn-primary {
            background: #1666ba;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
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
            padding: 2rem;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #bedaf7;
        }
        
        .empty-state p {
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .empty-state-white {
            color: rgba(255,255,255,0.8);
        }
        
        .empty-state-white i {
            color: rgba(255,255,255,0.5);
        }
        
        .empty-state-white p {
            color: rgba(255,255,255,0.7);
        }

        .announcement {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .announcement:last-child {
            margin-bottom: 0;
        }

        .announcement-date {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .announcement-text {
            font-size: 0.95rem;
            color: white;
            line-height: 1.6;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .announcements-card {
                grid-column: span 2;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2.5rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .announcements-card {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <?php include ('../includes/navbar/landlord-sidebar.html'); ?>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h2 class="card-title">Properties</h2>
                <?php if (empty($properties)): ?>
                    <div class="empty-state">
                        <i class="fas fa-home"></i>
                        <p>No properties yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($properties as $property): ?>
                        <div class="metric">
                            <span class="metric-label"><?= htmlspecialchars($property['property_type']) ?></span>
                            <span class="metric-value"><?= $property['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="metric total">
                        <span class="metric-label">Total Units</span>
                        <span class="metric-value"><?= $total_properties ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card status-card">
                <h2 class="card-title">Status</h2>
                <?php if (empty($properties)): ?>
                    <div class="empty-state empty-state-white">
                        <i class="fas fa-chart-bar"></i>
                        <p>No data available</p>
                    </div>
                <?php else: ?>
                    <div class="status-metric">
                        <span class="status-label">Vacant</span>
                        <span class="status-value"><?= $total_vacant ?></span>
                    </div>
                    <div class="status-metric">
                        <span class="status-label">Occupied</span>
                        <span class="status-value"><?= $total_occupied ?></span>
                    </div>
                    <div class="status-metric">
                        <span class="status-label">Maintenance</span>
                        <span class="status-value">0</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2 class="card-title">Quick Stats</h2>
                <div class="metric">
                    <span class="metric-label">Occupancy Rate</span>
                    <span class="metric-value">75%</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Monthly Revenue</span>
                    <span class="metric-value">₱45K</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Pending Requests</span>
                    <span class="metric-value">3</span>
                </div>
            </div>

            <div class="card announcements-card">
                <h2 class="card-title">
                    Recent Announcements
                    <button class="add-btn" onclick="openModal()"><i class="fas fa-plus"></i> Add</button>
                </h2>
                <?php if (empty($announcements)): ?>
                    <div class="empty-state empty-state-white">
                        <i class="fas fa-bullhorn"></i>
                        <p>No announcements</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement">
                            <div class="announcement-date"><?= date('F j, Y', strtotime($announcement['created_at'])) ?></div>
                            <div class="announcement-text">
                                <strong><?= htmlspecialchars($announcement['title']) ?></strong><br>
                                <?= htmlspecialchars($announcement['content']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Announcement Modal -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <h3 style="color: #1666ba; margin-bottom: 1.5rem;">Add New Announcement</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_announcement">
                
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                
                <div class="form-group">
                    <label>Content</label>
                    <textarea name="content" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Visible To</label>
                    <select name="visible_to" required>
                        <option value="all">Everyone</option>
                        <option value="tenant">Tenants Only</option>
                        <option value="landlord">Landlords Only</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary">Create Announcement</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('announcementModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('announcementModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('announcementModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>

</body>
</html>