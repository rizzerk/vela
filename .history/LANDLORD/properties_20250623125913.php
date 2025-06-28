<?php
session_start();
include '../connection.php';

// Fetch properties from database using prepared statement
$query = "SELECT p.*, COUNT(pp.photo_id) as photo_count 
          FROM PROPERTY p 
          LEFT JOIN PROPERTY_PHOTO pp ON p.property_id = pp.property_id 
          GROUP BY p.property_id 
          ORDER BY p.property_id DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties - VELA</title>
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

        .header-left h1 {
            font-size: 2.5rem;
            color: #1666ba;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .header-left p {
            font-size: 1.1rem;
            color: #475569;
        }

        .add-property-btn {
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(22, 102, 186, 0.3);
        }

        .add-property-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(22, 102, 186, 0.4);
        }

        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .property-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
            border: 1px solid rgba(190, 218, 247, 0.3);
            transition: all 0.3s ease;
        }

        .property-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(22, 102, 186, 0.15);
        }

        .property-image {
            height: 200px;
            background: linear-gradient(135deg, #7ab3ef, #368ce7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            position: relative;
        }

        .property-image .photo-count {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .property-content {
            padding: 1.5rem;
        }

        .property-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1666ba;
            margin-bottom: 0.5rem;
        }

        .property-address {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .property-description {
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        .property-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .property-rent {
            font-size: 1.4rem;
            font-weight: 800;
            color: #1666ba;
        }

        .property-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-available {
            background: #dcfce7;
            color: #166534;
        }

        .status-unavailable {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-maintenance {
            background: #fef3c7;
            color: #92400e;
        }

        .property-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #f1f5f9;
            color: #1666ba;
            border: 1px solid #cbd5e1;
        }

        .btn-edit:hover {
            background: #e2e8f0;
        }

        .btn-delete {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-delete:hover {
            background: #fee2e2;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #475569;
        }

        .empty-state p {
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 200px;
            }
            
            .properties-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .header-left h1 {
                font-size: 2rem;
            }
            
            .properties-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('../includes/navbar/landlord-sidebar.html'); ?>

    <div class="main-content">
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #bbf7d0;">
                <i class="fas fa-check-circle"></i>
                Property added successfully!
            </div>
        <?php endif; ?>
        
        <div class="header">
            <div class="header-left">
                <h1>Properties</h1>
                <p>Manage your rental properties</p>
            </div>
            <a href="add-property.php" class="add-property-btn">
                <i class="fas fa-plus"></i>
                Add New Property
            </a>
        </div>

        <div class="properties-grid">
            <?php 
            if ($result->num_rows > 0) {
                while ($property = $result->fetch_assoc()) {
                    $status_class = 'status-' . $property['status'];
                    $rent_formatted = '₱' . number_format($property['monthly_rent'], 0);
                    $photo_text = $property['photo_count'] > 0 ? $property['photo_count'] . ' photos' : 'No photos';
            ?>
            <div class="property-card">
                <div class="property-image">
                    <i class="fas fa-building"></i>
                    <div class="photo-count"><?php echo $photo_text; ?></div>
                </div>
                <div class="property-content">
                    <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                    <div class="property-address">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($property['address']); ?>
                    </div>
                    <p class="property-description">
                        <?php echo htmlspecialchars($property['description']); ?>
                    </p>
                    <div class="property-footer">
                        <div class="property-rent"><?php echo $rent_formatted; ?>/month</div>
                        <div class="property-status <?php echo $status_class; ?>">
                            <?php echo ucfirst($property['status']); ?>
                        </div>
                    </div>
                    <div class="property-actions">
                        <button class="action-btn btn-edit" onclick="editProperty(<?php echo $property['property_id']; ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteProperty(<?php echo $property['property_id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
            <?php 
                }
            } else {
            ?>
            <div class="empty-state">
                <i class="fas fa-building"></i>
                <h3>No Properties Yet</h3>
                <p>Start by adding your first rental property to get started with property management.</p>
                <a href="add-property.php" class="add-property-btn">
                    <i class="fas fa-plus"></i>
                    Add Your First Property
                </a>
            </div>
            <?php } ?>
        </div>
    </div>

    <script>
        function editProperty(propertyId) {
            // Redirect to edit property page
            window.location.href = `edit-property.php?id=${propertyId}`;
        }

        function deleteProperty(propertyId) {
            if (confirm('Are you sure you want to delete this property? This action cannot be undone.')) {
                // Send delete request
                fetch('delete-property.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ property_id: propertyId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting property: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the property.');
                });
            }
        }
    </script>
</body>
</html>