<?php
session_start();
include '../connection.php';

// Check if user is logged in

// Fetch all properties with their first photo
$query = "SELECT p.*, 
                 (SELECT file_path FROM PROPERTY_PHOTO 
                  WHERE property_id = p.property_id 
                  ORDER BY uploaded_at ASC LIMIT 1) AS first_photo
          FROM PROPERTY p";
$result = mysqli_query($conn, $query);
$properties = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - VELA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Image handling styles */
        .property-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
            background-color: #f0f7ff;
        }
        
        .no-image {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e3f2fd;
            color: #1666ba;
            font-size: 3rem;
        }
        
        /* Base styles */
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
            font-size: 2rem; 
            color: #1666ba; 
            font-weight: 700; 
        }
        
        .add-property-btn { 
            background-color: #1666ba; 
            color: white; 
            border: none; 
            padding: 0.75rem 1.5rem; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
            transition: background-color 0.3s ease; 
        }
        
        .add-property-btn:hover { 
            background-color: #12559e; 
        }
        
        .properties-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 2rem; 
        }
        
        .property-card { 
            background: white; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); 
            transition: transform 0.3s ease, box-shadow 0.3s ease; 
        }
        
        .property-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15); 
        }
        
        .property-status { 
            position: absolute; 
            top: 1rem; 
            right: 1rem; 
            padding: 0.25rem 0.75rem; 
            border-radius: 20px; 
            font-size: 0.8rem; 
            font-weight: 600; 
        }
        
        .status-available { 
            background-color: #4ade80; 
            color: white; 
        }
        
        .status-unavailable { 
            background-color: #f87171; 
            color: white; 
        }
        
        .status-maintenance { 
            background-color: #fbbf24; 
            color: white; 
        }
        
        .property-details { 
            padding: 1.5rem; 
        }
        
        .property-title { 
            font-size: 1.25rem; 
            font-weight: 700; 
            margin-bottom: 0.5rem; 
            color: #1666ba; 
        }
        
        .property-address { 
            color: #64748b; 
            margin-bottom: 1rem; 
            font-size: 0.9rem; 
        }
        
        .property-price { 
            font-size: 1.1rem; 
            font-weight: 700; 
            color: #1666ba; 
            margin-bottom: 1.5rem; 
        }
        
        .property-actions { 
            display: flex; 
            gap: 0.75rem; 
        }
        
        .action-btn { 
            flex: 1; 
            padding: 0.5rem; 
            border-radius: 6px; 
            border: none; 
            font-weight: 600; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 0.5rem; 
            transition: background-color 0.3s ease; 
        }
        
        .edit-btn { 
            background-color: #e0f2fe; 
            color: #0369a1; 
        }
        
        .edit-btn:hover { 
            background-color: #bae6fd; 
        }
        
        .delete-btn { 
            background-color: #fee2e2; 
            color: #b91c1c; 
        }
        
        .delete-btn:hover { 
            background-color: #fecaca; 
        }
        
        .empty-state { 
            text-align: center; 
            padding: 4rem; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); 
        }
        
        .empty-state i { 
            font-size: 3rem; 
            color: #1666ba; 
            margin-bottom: 1.5rem; 
        }
        
        .empty-state h3 { 
            font-size: 1.5rem; 
            margin-bottom: 1rem; 
            color: #1666ba; 
        }
        
        .empty-state p { 
            color: #64748b; 
            margin-bottom: 2rem; 
        }
        
        @media (max-width: 1024px) { 
            .properties-grid { 
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); 
            } 
        }
        
        @media (max-width: 768px) { 
            .main-content { 
                margin-left: 0; 
                padding: 1rem; 
            } 
            
            .header { 
                flex-direction: column; 
                align-items: flex-start; 
                gap: 1rem; 
            } 
            
            .add-property-btn { 
                width: 100%; 
            } 
        }
    </style>
</head>
<body>
    <?php include ('../includes/navbar/landlord-sidebar.html'); ?>

    <div class="main-content">
        <div class="header">
            <h1>My Properties</h1>
            <button class="add-property-btn" onclick="window.location.href='add-property.php'">
                <i class="fas fa-plus"></i> Add Property
            </button>
        </div>

        <?php if (empty($properties)): ?>
            <div class="empty-state">
                <i class="fas fa-home"></i>
                <h3>No Properties Yet</h3>
                <p>You haven't added any properties yet. Get started by adding your first property.</p>
                <button class="add-property-btn" onclick="window.location.href='add-property.php'">
                    <i class="fas fa-plus"></i> Add Property
                </button>
            </div>
        <?php else: ?>
            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                    <div class="property-card">
                        <div class="property-image <?php echo empty($property['first_photo']) ? 'no-image' : ''; ?>" 
                             style="<?php if (!empty($property['first_photo'])) echo "background-image: url('../" . htmlspecialchars($property['first_photo']) . "')"; ?>">
                            <?php if (empty($property['first_photo'])): ?>
                                <i class="fas fa-home"></i>
                            <?php endif; ?>
                            <span class="property-status status-<?php echo strtolower($property['status']); ?>">
                                <?php echo ucfirst($property['status']); ?>
                            </span>
                        </div>
                        <div class="property-details">
                            <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                            <p class="property-address"><?php echo htmlspecialchars($property['address']); ?></p>
                            <p class="property-price">₱<?php echo number_format($property['monthly_rent'], 2); ?>/month</p>
                            <div class="property-actions">
                                <button class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn delete-btn" data-id="<?php echo $property['property_id']; ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Delete property functionality
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const propertyId = this.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this property?')) {
                    fetch(`delete-property.php?id=${propertyId}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('.property-card').remove();
                            // Check if no properties left to show empty state
                            if (document.querySelectorAll('.property-card').length === 0) {
                                window.location.reload();
                            }
                        } else {
                            alert('Error deleting property: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while deleting the property');
                    });
                }
            });
        });
    </script>
</body>
</html>