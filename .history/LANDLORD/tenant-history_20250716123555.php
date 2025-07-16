
<?php
session_start();
require_once '../connection.php';
//require_once "../includes/auth/tenant_auth.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_lease'])) {
    header('Content-Type: application/json');
    
    $lease_id = $_POST['lease_id'] ?? null;
    $active = $_POST['active'] ?? null;
    
    if ($lease_id && $active !== null) {
        try {
            $stmt = $conn->prepare("UPDATE LEASE SET active = ? WHERE lease_id = ?");
            $stmt->bind_param("ii", $active, $lease_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    }
    exit;
}

$landlord_id = $_SESSION['user_id'] ?? 1;

$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'property';
$sort_order = $_GET['sort_order'] ?? 'asc';

$where_conditions = ["l.lease_id IS NOT NULL"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.address LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR a.co_tenants LIKE ?)";
    $search_term = "%$search%";
    $params = array_fill(0, 5, $search_term);
    $types = 'sssss';
}


$order_by = "p.title ASC, l.start_date DESC";
if ($sort_by === 'tenant') {
    $order_by = "u.name $sort_order, l.start_date DESC";
} elseif ($sort_by === 'date') {
    $order_by = "l.start_date $sort_order, p.title ASC";
} elseif ($sort_by === 'property') {
    $order_by = "p.title $sort_order, l.start_date DESC";
}

$where_clause = implode(' AND ', $where_conditions);

$history_query = "
    SELECT 
        p.property_id,
        p.title as property_title,
        p.address as property_address,
        u.name as tenant_name,
        u.phone as tenant_phone,
        u.email as tenant_email,
        l.lease_id,
        l.start_date,
        l.end_date,
        l.active,
        a.co_tenants
    FROM PROPERTY p
    LEFT JOIN LEASE l ON p.property_id = l.property_id
    LEFT JOIN USERS u ON l.tenant_id = u.user_id
    LEFT JOIN APPLICATIONS a ON (p.property_id = a.property_id AND u.user_id = a.applicant_id)
    WHERE $where_clause
    ORDER BY $order_by
";

if (!empty($params)) {
    $stmt = $conn->prepare($history_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($history_query);
}

$tenant_history = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $property_id = $row['property_id'];
        if (!isset($tenant_history[$property_id])) {
            $tenant_history[$property_id] = [
                'property_title' => $row['property_title'],
                'property_address' => $row['property_address'],
                'tenants' => []
            ];
        }
        
        if ($row['tenant_name']) {
            $tenant_history[$property_id]['tenants'][] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant History - VELA</title>
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
            margin-bottom: 2rem;
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

        .search-controls {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
            border: 1px solid rgba(190, 218, 247, 0.3);
        }

        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1666ba;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid #bedaf7;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .search-btn {
            background: #1666ba;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            height: fit-content;
        }

        .clear-btn {
            background: #64748b;
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            margin-left: 0.5rem;
        }

        .property-section {
            background: white;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(22, 102, 186, 0.1);
            border: 1px solid rgba(190, 218, 247, 0.3);
            overflow: hidden;
        }

        .property-header {
            background: linear-gradient(135deg, #368ce7, #1666ba);
            color: white;
            padding: 1.5rem 2rem;
        }

        .property-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .property-address {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .tenant-list {
            padding: 0;
        }

        .tenant-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #f1f5f9;
            align-items: center;
        }

        .tenant-item:last-child {
            border-bottom: none;
        }

        .tenant-item:hover {
            background-color: #f8fafc;
        }

        .tenant-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1rem 2rem;
            background: #deecfb;
            font-weight: 600;
            color: #1666ba;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tenant-name {
            font-weight: 600;
            color: #1e293b;
        }

        .co-tenants {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .contact-info {
            font-size: 0.9rem;
            color: #64748b;
        }

        .lease-dates {
            font-size: 0.9rem;
            color: #1e293b;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-ended {
            background: #fef2f2;
            color: #991b1b;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #bedaf7;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .empty-state p {
            font-size: 0.95rem;
        }

        .no-tenants {
            padding: 2rem;
            text-align: center;
            color: #64748b;
            font-style: italic;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .action-btn.activate {
            background: #22c55e;
            color: white;
        }

        .action-btn.deactivate {
            background: #ef4444;
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        @media (max-width: 1024px) {
            .tenant-item,
            .tenant-header {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .tenant-item {
                padding: 1rem;
            }
            
            .tenant-header {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2.5rem;
            }
            
            .property-header {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include ('../includes/navbar/landlord-sidebar.php'); ?>

    <div class="main-content">
        <div class="header">
            <h1>Tenant History</h1>
        </div>

        <div class="search-controls">
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Property, tenant name, email, or co-tenants">
                </div>
                <div class="form-group">
                    <label>Sort By</label>
                    <select name="sort_by">
                        <option value="property" <?= $sort_by === 'property' ? 'selected' : '' ?>>Property</option>
                        <option value="tenant" <?= $sort_by === 'tenant' ? 'selected' : '' ?>>Tenant Name</option>
                        <option value="date" <?= $sort_by === 'date' ? 'selected' : '' ?>>Lease Date</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Order</label>
                    <select name="sort_order">
                        <option value="asc" <?= $sort_order === 'asc' ? 'selected' : '' ?>>Ascending</option>
                        <option value="desc" <?= $sort_order === 'desc' ? 'selected' : '' ?>>Descending</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                    <a href="tenant-history.php" class="clear-btn">Clear</a>
                </div>
            </form>
        </div>

        <?php if (empty($tenant_history)): ?>
            <div class="property-section">
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>No Tenant History</h3>
                    <p>No lease records found. Tenant history will appear here once you have active or past leases.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($tenant_history as $property_id => $property_data): ?>
                <div class="property-section">
                    <div class="property-header">
                        <div class="property-title"><?= htmlspecialchars($property_data['property_title']) ?></div>
                        <div class="property-address"><?= htmlspecialchars($property_data['property_address']) ?></div>
                    </div>
                    
                    <?php if (empty($property_data['tenants'])): ?>
                        <div class="no-tenants">
                            No tenant history available for this property
                        </div>
                    <?php else: ?>
                        <div class="tenant-header">
                            <div>Tenant Details</div>
                            <div>Phone</div>
                            <div>Email</div>
                            <div>Lease Period</div>
                            <div>Status</div>
                            <div>Actions</div>
                        </div>
                        
                        <div class="tenant-list">
                            <?php foreach ($property_data['tenants'] as $tenant): ?>
                                <div class="tenant-item">
                                    <div>
                                        <div class="tenant-name"><?= htmlspecialchars($tenant['tenant_name']) ?></div>
                                        <?php if (!empty($tenant['co_tenants'])): ?>
                                            <div class="co-tenants">Co-tenants: <?= htmlspecialchars($tenant['co_tenants']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="contact-info"><?= htmlspecialchars($tenant['tenant_phone'] ?? 'N/A') ?></div>
                                    <div class="contact-info"><?= htmlspecialchars($tenant['tenant_email'] ?? 'N/A') ?></div>
                                    <div class="lease-dates">
                                        <?= date('M j, Y', strtotime($tenant['start_date'])) ?> - 
                                        <?= $tenant['end_date'] ? date('M j, Y', strtotime($tenant['end_date'])) : 'Ongoing' ?>
                                    </div>
                                    <div>
                                        <span class="status-badge <?= $tenant['active'] ? 'status-active' : 'status-ended' ?>">
                                            <?= $tenant['active'] ? 'Active' : 'Ended' ?>
                                        </span>
                                    </div>
                                    <div>
                                        <button onclick="toggleLease(<?= $tenant['lease_id'] ?>, <?= $tenant['active'] ? 0 : 1 ?>)" 
                                                class="action-btn <?= $tenant['active'] ? 'deactivate' : 'activate' ?>">
                                            <?= $tenant['active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    function toggleLease(leaseId, newStatus) {
        if (confirm('Are you sure you want to ' + (newStatus ? 'activate' : 'deactivate') + ' this lease?')) {
            const formData = new FormData();
            formData.append('toggle_lease', '1');
            formData.append('lease_id', leaseId);
            formData.append('active', newStatus);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error updating lease status');
            });
        }
    }
    </script>
</body>
</html>