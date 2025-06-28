<?php
session_start();
include '../connection.php';

// Get landlord ID from session
$landlord_id = $_SESSION['user_id'] ?? 1; // Default to 1 for testing

// Fetch properties grouped by type
$property_query = "SELECT property_type, COUNT(*) as count, 
                         SUM(CASE WHEN status = 'vacant' THEN 1 ELSE 0 END) as vacant,
                         SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied
                  FROM PROPERTY 
                  WHERE landlord_id = ? 
                  GROUP BY property_type";
$stmt = $conn->prepare($property_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_properties = array_sum(array_column($properties, 'count'));
$total_vacant = array_sum(array_column($properties, 'vacant'));
$total_occupied = array_sum(array_column($properties, 'occupied'));

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
            text-align: center;
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
            <p>Property Management Overview</p>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h2 class="card-title">Properties</h2>
                <?php if (empty($properties)): ?>
                    <div style="text-align: center; padding: 2rem; color: #64748b;">
                        <i class="fas fa-home" style="font-size: 3rem; margin-bottom: 1rem; color: #bedaf7;"></i>
                        <p>No properties uploaded yet</p>
                        <p style="font-size: 0.9rem; margin-top: 0.5rem;">Start by adding your first property</p>
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
                    <div style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.8);">
                        <i class="fas fa-chart-bar" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>No status data available</p>
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
                <h2 class="card-title">Recent Announcements</h2>
                <div class="announcement">
                    <div class="announcement-date">December 15, 2024</div>
                    <div class="announcement-text">
                        Scheduled maintenance for Building A elevator will take place on December 20th from 9 AM to 3 PM.
                    </div>
                </div>
                <div class="announcement">
                    <div class="announcement-date">December 12, 2024</div>
                    <div class="announcement-text">
                        New parking regulations will be implemented starting January 1st, 2025. All tenants must register their vehicles.
                    </div>
                </div>
                <div class="announcement">
                    <div class="announcement-date">December 10, 2024</div>
                    <div class="announcement-text">
                        Holiday office hours: The management office will be closed on December 25th and January 1st.
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>